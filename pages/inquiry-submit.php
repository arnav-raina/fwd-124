<?php
declare(strict_types=1);

$https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off';
ini_set('session.use_strict_mode', '1');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function redirectToForm(string $status, string $anchor): void
{
    header('Location: join.php?status=' . rawurlencode($status), true, 303);
    exit;
}

function postText(string $key, int $maxLength, bool $required = false): ?string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        throw new InvalidArgumentException('Invalid field format.');
    }
    $value = trim($value);
    if ($value === '') {
        if ($required) {
            throw new InvalidArgumentException('Required field is missing.');
        }
        return null;
    }
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value) === 1) {
        throw new InvalidArgumentException('Invalid control character.');
    }
    $length = preg_match_all('/./us', $value, $matches);
    if ($length === false || $length > $maxLength) {
        throw new InvalidArgumentException('Field is too long.');
    }
    return $value;
}

function writeInquiryDiagnostic(string $stage, string $category, string $sqlState = 'n/a', string $driverCode = 'n/a'): void
{
    $path = __DIR__ . '/private/pmsl-inquiry-errors.log';
    $line = sprintf("%s [PMSL inquiry][%s][SQLSTATE %s][MySQL code %s] %s\n", date(DATE_ATOM), $stage, $sqlState, $driverCode, $category);
    if (@file_put_contents($path, $line, FILE_APPEND | LOCK_EX) === false) {
        // InfinityFree free hosting does not expose its server-side PHP error log to users.
        error_log('[PMSL inquiry] Could not write the protected diagnostic file.');
    }
}

function logInquiryFailure(string $stage, Throwable $error): void
{
    $sqlState = $error instanceof PDOException ? (string) $error->getCode() : 'n/a';
    $driverCode = ($error instanceof PDOException && is_array($error->errorInfo) && isset($error->errorInfo[1]))
        ? (string) $error->errorInfo[1]
        : 'n/a';
    $categories = [
        '1044' => 'Database access was denied',
        '1045' => 'MySQL rejected the login credentials',
        '1049' => 'Configured database was not found',
        '2002' => 'Could not connect to the configured MySQL host',
        '2003' => 'Could not reach the configured MySQL host',
        '1054' => 'An expected table column is missing',
        '1064' => 'MySQL rejected the insert statement syntax',
        '1146' => 'The pmsl_inquiries table was not found',
        '1406' => 'A submitted value exceeds the database column length',
    ];
    $category = $categories[$driverCode] ?? ($error instanceof RuntimeException ? $error->getMessage() : 'Unclassified database or PHP error');
    writeInquiryDiagnostic($stage, $category, $sqlState, $driverCode);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

$type = isset($_POST['inquiry_type']) && is_string($_POST['inquiry_type']) ? $_POST['inquiry_type'] : '';
$anchors = [
    'application' => 'join-form',
    'collaboration' => 'collaborate',
    'visit' => 'visit',
];
if (!isset($anchors[$type])) {
    redirectToForm('invalid', 'join-form');
}
$anchor = $anchors[$type];

// Treat honeypot submissions as successful without storing them.
$honeypot = $_POST['website'] ?? '';
if (!is_string($honeypot) || trim($honeypot) !== '') {
    redirectToForm('saved', $anchor);
}

$postedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['pmsl_csrf_token'] ?? '';
if (!is_string($postedToken) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
    redirectToForm('expired', $anchor);
}

if (!empty($_SESSION['pmsl_last_inquiry']) && time() - (int) $_SESSION['pmsl_last_inquiry'] < 20) {
    redirectToForm('rate', $anchor);
}

try {
    $fullName = postText('full_name', 120, true);
    $email = postText('email', 190, true);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email address.');
    }

    $data = [
        'inquiry_type' => $type,
        'full_name' => $fullName,
        'email' => $email,
        'phone' => null,
        'organization' => null,
        'role_title' => null,
        'opportunity' => null,
        'research_area' => null,
        'purpose' => null,
        'preferred_visit_date' => null,
        'academic_background' => null,
        'message' => null,
    ];

    if ($type === 'application') {
        $data['opportunity'] = postText('opportunity', 120, true);
        $data['research_area'] = postText('research_area', 120, true);
        $data['academic_background'] = postText('academic_background', 5000);
        $data['message'] = postText('message', 5000, true);
    } elseif ($type === 'collaboration') {
        $data['organization'] = postText('organization', 160, true);
        $data['role_title'] = postText('role_title', 120);
        $data['opportunity'] = postText('opportunity', 120, true);
        $data['message'] = postText('message', 5000, true);
    } else {
        $data['phone'] = postText('phone', 40);
        $data['purpose'] = postText('purpose', 160, true);
        $data['message'] = postText('message', 5000);
        $date = postText('preferred_visit_date', 10);
        if ($date !== null) {
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                throw new InvalidArgumentException('Invalid visit date.');
            }
            $data['preferred_visit_date'] = $date;
        }
    }
} catch (InvalidArgumentException $error) {
    redirectToForm('invalid', $anchor);
}

$configPath = __DIR__ . '/private/config.php';
if (!is_file($configPath) || !is_readable($configPath)) {
    writeInquiryDiagnostic('load configuration', 'Private database configuration file is missing or unreadable');
    redirectToForm('unavailable', $anchor);
}

if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
    writeInquiryDiagnostic('check driver', 'PDO MySQL driver is unavailable');
    redirectToForm('unavailable', $anchor);
}

$dbStage = 'load configuration';
try {
    $config = require $configPath;
    if (!is_array($config) || empty($config['host']) || empty($config['database']) || empty($config['username']) || !array_key_exists('password', $config)) {
        throw new RuntimeException('Database configuration is incomplete.');
    }
    $charset = isset($config['charset']) && is_string($config['charset']) ? $config['charset'] : 'utf8mb4';
    $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . $config['database'] . ';charset=' . $charset;
    $dbStage = 'connect';
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $dbStage = 'prepare insert';
    $sql = 'INSERT INTO pmsl_inquiries (
        inquiry_type, full_name, email, phone, organization, role_title,
        opportunity, research_area, purpose, preferred_visit_date,
        academic_background, message
    ) VALUES (
        :inquiry_type, :full_name, :email, :phone, :organization, :role_title,
        :opportunity, :research_area, :purpose, :preferred_visit_date,
        :academic_background, :message
    )';
    $statement = $pdo->prepare($sql);
    $dbStage = 'bind insert fields';
    foreach ($data as $field => $value) {
        $statement->bindValue(':' . $field, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    }
    $dbStage = 'insert row';
    $statement->execute();

    $_SESSION['pmsl_last_inquiry'] = time();
    unset($_SESSION['pmsl_csrf_token']);
    redirectToForm('saved', $anchor);
} catch (Throwable $error) {
    // Keep database credentials and raw exception text out of both the page and diagnostic file.
    logInquiryFailure($dbStage, $error);
    redirectToForm('unavailable', $anchor);
}

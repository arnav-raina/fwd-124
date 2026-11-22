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
if (empty($_SESSION['pmsl_csrf_token']) || !is_string($_SESSION['pmsl_csrf_token'])) {
    $_SESSION['pmsl_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfHtml = htmlspecialchars($_SESSION['pmsl_csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
$statusMessages = [
    'saved' => 'Thank you. Your inquiry was received by PMSL.',
    'invalid' => 'Please check the form fields and try again.',
    'expired' => 'This form session expired. Please reload the page and submit again.',
    'unavailable' => 'The inquiry could not be saved right now. Please email shachi@sau.int instead.',
    'rate' => 'Please wait a short time before submitting another inquiry.',
];
$statusMessage = $statusMessages[$status] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="PMSL research, collaboration and visitor inquiries using secure inquiry forms.">
  <title>Join, collaborate or visit | PMSL · South Asian University</title>
  <link rel="stylesheet" href="pmsl.css">
  <script src="pmsl.js" defer></script>
</head>
<body id="top" data-page="Join or Visit">
  <div class="reading-line" id="readingLine" aria-hidden="true"></div>
  <div class="university-strip"><div class="container strip-inner"><span>Faculty of Engineering and Technology</span><span>South Asian University · New Delhi</span></div></div>
  <header class="site-header">
    <div class="container masthead">
      <a class="brand" href="index.html" aria-label="PMSL home"><span class="brand-mark">P<span>M</span>SL</span><span class="brand-name">PERFORMANCE MODELING &amp; SIMULATION LAB</span></a>
      <div class="masthead-tools">
        <form class="global-search" action="people.html" method="get"><label class="visually-hidden" for="globalSearch">Search people and alumni</label><input id="globalSearch" name="q" type="search" placeholder="Search people &amp; alumni"><button type="submit" aria-label="Search">⌕</button></form>
        <button class="theme-toggle" id="themeToggle" type="button" aria-label="Switch colour theme" aria-pressed="false"><span id="themeIcon" aria-hidden="true">☾</span><span id="themeLabel">Dark mode</span></button>
      </div>
    </div>
    <nav class="main-nav" aria-label="Main navigation"><div class="container nav-inner"><a href="index.html">Home</a><a href="people.html">People</a><a href="research.html">Research Areas</a><a href="publications.html">Publications</a><a href="news.html">News</a><a href="events.html">Events</a><a href="honors.html">Honors</a><a href="lab.html">Lab</a><a href="projectbase.html">ProjectBase</a><a href="join.php" aria-current="page" class="active">Join or Visit</a></div></nav>
  </header>
  <main><?php if ($statusMessage !== ''): ?><div class="container"><p class="backend-notice" role="status"><?php echo htmlspecialchars($statusMessage, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p></div><?php endif; ?><section class="page-hero"><div class="container"><p class="eyebrow teal-text">Connect with PMSL</p><h1>Join, collaborate or visit</h1><p>For research applications, student projects, collaboration discussions and campus visits, use the relevant inquiry form below.</p><div class="hero-pills"><span>Inquiry forms</span><span>No file uploads</span><span>Records stored for review</span></div></div></section><section class="section join-section"><div class="container join-layout"><aside class="join-sidebar"><p class="eyebrow teal-text">Get started</p><h2>How can we help?</h2><a href="#join-form">Join the lab</a><a href="#collaborate">Collaborate</a><a href="#visit">Plan a visit</a><div class="open-status"><span class="status-pill">CONTACT THE LAB</span><p>Current openings and student-project availability have not been confirmed here. Contact the PI for current information.</p></div><div class="contact-mini"><h3>Direct contact</h3><a href="mailto:shachi@sau.int">shachi@sau.int</a><a href="tel:+911135656500">+91 (11) 35656500</a><p>South Asian University<br>New Delhi 110068, India</p></div></aside><div class="join-content"><section class="join-panel" id="join-form"><div class="panel-heading"><div><p class="eyebrow teal-text">Research inquiry</p><h2>Ask about joining PMSL</h2></div><span class="step-number">01</span></div><p>Share a short introduction and the type of academic opportunity you are interested in. This form sends the inquiry to the PMSL database for review. It does not accept file uploads.</p><form class="backend-form" action="inquiry-submit.php" method="post">
<input type="hidden" name="inquiry_type" value="application"><input type="hidden" name="csrf_token" value="<?php echo $csrfHtml; ?>">
<div class="honeypot" aria-hidden="true"><label>Leave this field blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
<div class="form-grid">
<label>Your name<input name="full_name" type="text" autocomplete="name" required maxlength="120"></label>
<label>Email address<input name="email" type="email" autocomplete="email" required maxlength="190"></label>
<label>Interested in<select name="opportunity" required><option value="">Choose an option</option><option>PhD research inquiry</option><option>Master's thesis project</option><option>Bachelor's research project</option><option>Research collaboration</option><option>Other academic inquiry</option></select></label>
<label>Research area<select name="research_area" required><option value="">Choose an area</option><option>IoT traffic modeling</option><option>Privacy-aware attack detection</option><option>5G communication</option><option>Blockchain performance</option><option>Software cost estimation</option><option>Federated machine learning</option><option>Other / not sure</option></select></label>
<label class="full-width">Academic background<textarea name="academic_background" rows="3" maxlength="5000" placeholder="Degree, institution, and relevant experience"></textarea></label>
<label class="full-width">Short statement<textarea name="message" rows="4" required maxlength="5000" placeholder="Tell us briefly about your interest"></textarea></label>
</div><button class="button" type="submit">Submit research inquiry →</button><p class="form-note">Your inquiry will be sent to PMSL for review.</p></form></section>
<section class="join-panel" id="collaborate"><div class="panel-heading"><div><p class="eyebrow teal-text">Research partnerships</p><h2>Collaborate with PMSL</h2></div><span class="step-number">02</span></div><p>For research collaboration, joint student supervision, or a project discussion, contact the lab with a short note about the proposed topic.</p><form class="backend-form compact-form" action="inquiry-submit.php" method="post">
<input type="hidden" name="inquiry_type" value="collaboration"><input type="hidden" name="csrf_token" value="<?php echo $csrfHtml; ?>">
<div class="honeypot" aria-hidden="true"><label>Leave this field blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
<div class="form-grid">
<label>Your name<input name="full_name" type="text" autocomplete="name" required maxlength="120"></label>
<label>Work email<input name="email" type="email" autocomplete="email" required maxlength="190"></label>
<label>Organization<input name="organization" type="text" required maxlength="160"></label>
<label>Role or designation<input name="role_title" type="text" maxlength="120"></label>
<label>Collaboration type<select name="opportunity" required><option value="">Choose an option</option><option>Research collaboration</option><option>Joint research or publication</option><option>Industry partnership</option><option>Sponsored research</option><option>Other</option></select></label>
<label class="full-width">Proposed topic<textarea name="message" rows="3" required maxlength="5000" placeholder="Briefly describe the proposed collaboration"></textarea></label>
</div><button class="button" type="submit">Submit collaboration inquiry →</button><p class="form-note">Your inquiry will be stored for PMSL to review.</p></form><div class="soft-callout"><strong>Helpful details to include</strong><p>Your affiliation, proposed research topic, collaboration type and a preferred way to follow up.</p></div></section>
<section class="join-panel" id="visit"><div class="panel-heading"><div><p class="eyebrow teal-text">Campus visit</p><h2>Plan a visit</h2></div><span class="step-number">03</span></div><p>Contact the lab before travelling so that the meeting, access arrangements and campus-entry requirements can be confirmed.</p><div class="visit-address"><strong>South Asian University</strong><span>Faculty of Engineering and Technology</span><span>New Delhi 110068, India</span></div><form class="backend-form compact-form" action="inquiry-submit.php" method="post">
<input type="hidden" name="inquiry_type" value="visit"><input type="hidden" name="csrf_token" value="<?php echo $csrfHtml; ?>">
<div class="honeypot" aria-hidden="true"><label>Leave this field blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
<div class="form-grid">
<label>Your name<input name="full_name" type="text" autocomplete="name" required maxlength="120"></label>
<label>Email address<input name="email" type="email" autocomplete="email" required maxlength="190"></label>
<label>Phone<input name="phone" type="tel" autocomplete="tel" maxlength="40"></label>
<label>Preferred visit date<input name="preferred_visit_date" type="date"></label>
<label class="full-width">Purpose of visit<select name="purpose" required><option value="">Choose an option</option><option>Research discussion</option><option>Thesis or project consultation</option><option>Academic collaboration meeting</option><option>Seminar or academic talk</option><option>Other</option></select></label>
<label class="full-width">Additional context<textarea name="message" rows="3" maxlength="5000" placeholder="Include any scheduling or access information"></textarea></label>
</div><button class="button" type="submit">Submit visit inquiry →</button><p class="form-note">Submitting a request does not guarantee campus access. Contact PMSL before travelling.</p></form></section></div></div></section></main>
  <footer class="site-footer">
    <div class="container footer-main">
      <div class="footer-about"><a class="footer-logo" href="index.html">PMSL</a><p>Performance Modeling &amp; Simulation Lab<br>South Asian University</p><p class="footer-note">Research into performance, systems and modern networks.</p></div>
      <div><h2>Explore</h2><a href="research.html">Research areas</a><a href="people.html">People &amp; alumni</a><a href="publications.html">Publications</a><a href="projectbase.html">ProjectBase</a></div>
      <div><h2>Visit &amp; contact</h2><p>Faculty of Engineering and Technology<br>South Asian University<br>New Delhi 110068, India</p><a href="mailto:shachi@sau.int">shachi@sau.int</a><a href="tel:+911135656500">+91 (11) 35656500</a><a href="https://sau.int/" target="_blank" rel="noopener">South Asian University ↗</a></div>
    </div>
    <div class="container footer-bottom"><span>© PMSL · South Asian University</span><a href="#top">Back to top ↑</a><span>Information should be verified with the lab.</span></div>
  </footer>
  <button class="back-to-top" id="backToTop" type="button" aria-label="Back to top" hidden>↑</button>
  <section class="faq-widget" aria-label="PMSL Bot frequently asked questions">
    <button class="faq-launcher" id="faqLauncher" type="button" aria-expanded="false" aria-controls="faqPanel"><span class="bot-orbit" aria-hidden="true">P</span><span>PMSL Bot</span><span class="launcher-plus" aria-hidden="true">+</span></button>
    <div class="faq-panel" id="faqPanel" hidden>
      <div class="faq-heading"><div><p class="eyebrow">Quick answers</p><h2>PMSL Bot</h2></div><button type="button" id="faqClose" class="icon-button" aria-label="Close PMSL Bot">×</button></div>
      <p class="faq-intro">Choose a question to see a preset answer. This panel does not accept typed messages.</p>
      <div class="faq-questions" id="faqQuestions">
        <button type="button" data-faq="What does PMSL stand for?">What does PMSL stand for?</button>
        <button type="button" data-faq="What does the lab study?">What does the lab study?</button>
        <button type="button" data-faq="Who leads PMSL?">Who leads PMSL?</button>
        <button type="button" data-faq="What are the research areas?">What are the research areas?</button>
        <button type="button" data-faq="Who are the current scholars?">Who are the current scholars?</button>
        <button type="button" data-faq="How can I explore alumni projects?">How can I explore alumni projects?</button>
        <button type="button" data-faq="How do I ask about joining?">How do I ask about joining?</button>
        <button type="button" data-faq="Where is PMSL located?">Where is PMSL located?</button>
      </div>
      <div class="faq-answer" id="faqAnswer" aria-live="polite">Select a question above.</div>
    </div>
  </section>
</body>
</html>

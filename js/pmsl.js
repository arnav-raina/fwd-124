/* Shared, dependency-free interactions for the PMSL static website. */
(function () {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  // Theme toggle. The page still works if the browser blocks local storage.
  const themeButton = $('#themeToggle');
  const themeIcon = $('#themeIcon');
  const themeLabel = $('#themeLabel');
  const savedTheme = (() => {
    try { return localStorage.getItem('pmsl-theme'); }
    catch (error) { return null; }
  })();
  if (savedTheme === 'dark') document.documentElement.dataset.theme = 'dark';

  function updateThemeButton() {
    if (!themeButton) return;
    const isDark = document.documentElement.dataset.theme === 'dark';
    themeButton.setAttribute('aria-pressed', String(isDark));
    if (themeIcon) themeIcon.textContent = isDark ? '☀' : '☾';
    if (themeLabel) themeLabel.textContent = isDark ? 'Light mode' : 'Dark mode';
  }
  updateThemeButton();
  if (themeButton) {
    themeButton.addEventListener('click', function () {
      const willBeDark = document.documentElement.dataset.theme !== 'dark';
      if (willBeDark) document.documentElement.dataset.theme = 'dark';
      else delete document.documentElement.dataset.theme;
      try { localStorage.setItem('pmsl-theme', willBeDark ? 'dark' : 'light'); }
      catch (error) { /* Theme still changes for this page view. */ }
      updateThemeButton();
    });
  }

  // Scroll reading line and back-to-top control.
  const readingLine = $('#readingLine');
  const backToTop = $('#backToTop');
  let scrollScheduled = false;
  function updateScrollUi() {
    const scrollable = document.documentElement.scrollHeight - window.innerHeight;
    const percent = scrollable > 0 ? Math.min(100, Math.max(0, (window.scrollY / scrollable) * 100)) : 0;
    if (readingLine) readingLine.style.width = percent + '%';
    if (backToTop) backToTop.hidden = window.scrollY < 420;
    scrollScheduled = false;
  }
  window.addEventListener('scroll', function () {
    if (!scrollScheduled) {
      scrollScheduled = true;
      window.requestAnimationFrame(updateScrollUi);
    }
  }, { passive: true });
  window.addEventListener('resize', updateScrollUi);
  updateScrollUi();
  if (backToTop) backToTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

  // People directory filters and the global search form's query parameter.
  const personCards = $$('[data-person-card]');
  const peopleSearch = $('#peopleSearch');
  const peopleCount = $('#peopleResultCount');
  const peopleEmpty = $('#peopleEmpty');
  let peopleGroup = 'all';
  function filterPeople() {
    if (!personCards.length) return;
    const query = peopleSearch ? peopleSearch.value.trim().toLowerCase() : '';
    let count = 0;
    personCards.forEach((card) => {
      const groupMatches = peopleGroup === 'all' || card.dataset.group === peopleGroup;
      const searchText = [card.dataset.name, card.dataset.role, card.dataset.project, card.dataset.details, card.dataset.interests].join(' ').toLowerCase();
      const searchMatches = !query || searchText.includes(query);
      const show = groupMatches && searchMatches;
      card.hidden = !show;
      if (show) count += 1;
    });
    if (peopleCount) peopleCount.textContent = `Showing ${count} ${count === 1 ? 'person' : 'people'}.`;
    if (peopleEmpty) peopleEmpty.hidden = count !== 0;
  }
  const peopleQuery = new URLSearchParams(window.location.search).get('q');
  if (peopleSearch && peopleQuery) peopleSearch.value = peopleQuery;
  if (peopleSearch) peopleSearch.addEventListener('input', filterPeople);
  $$('[data-people-filter]').forEach((button) => {
    button.addEventListener('click', function () {
      peopleGroup = button.dataset.peopleFilter;
      $$('[data-people-filter]').forEach((item) => {
        const active = item === button;
        item.classList.toggle('is-active', active);
        item.setAttribute('aria-pressed', String(active));
      });
      filterPeople();
    });
  });
  filterPeople();

  // Profile details dialog; content is assigned with textContent rather than HTML.
  const profileDialog = $('#profileDialog');
  function addDialogLink(container, href, label) {
    if (!container || !href) return;
    if (!href.startsWith('https://') && !href.startsWith('mailto:')) return;
    const link = document.createElement('a');
    link.href = href;
    link.textContent = label;
    if (href.startsWith('https://')) {
      link.target = '_blank';
      link.rel = 'noopener';
    }
    container.appendChild(link);
  }
  if (profileDialog) {
    personCards.forEach((card) => card.addEventListener('click', function () {
      const projectLabel = $('#modalProjectLabel');
      if (projectLabel) projectLabel.textContent = card.dataset.group === 'alumni' ? 'Thesis project' : (card.dataset.group === 'current' ? 'Research project' : 'Position');
      const fields = {
        modalType: card.dataset.group === 'alumni' ? 'ALUMNI PROFILE' : card.dataset.role.toUpperCase(),
        modalName: card.dataset.name,
        modalRole: card.dataset.role,
        modalProject: card.dataset.project,
        modalDetails: card.dataset.details,
        modalInterests: card.dataset.interests
      };
      Object.entries(fields).forEach(([id, value]) => {
        const target = document.getElementById(id);
        if (target) target.textContent = value || 'No further details supplied.';
      });
      const links = $('#modalLinks');
      if (links) {
        links.replaceChildren();
        addDialogLink(links, card.dataset.email, 'Email');
        addDialogLink(links, card.dataset.profile, 'Faculty profile');
      }
      if (typeof profileDialog.showModal === 'function') profileDialog.showModal();
      else profileDialog.setAttribute('open', '');
    }));
    const closeProfile = $('#closeProfile');
    if (closeProfile) closeProfile.addEventListener('click', () => profileDialog.close());
    profileDialog.addEventListener('click', (event) => {
      if (event.target === profileDialog) profileDialog.close();
    });
  }

  // Research topic filters use visible card text and keyword metadata.
  const researchCards = $$('[data-research-card]');
  const researchCount = $('#researchResultCount');
  $$('[data-research-filter]').forEach((button) => {
    button.addEventListener('click', function () {
      const selected = button.dataset.researchFilter;
      let count = 0;
      researchCards.forEach((card) => {
        const text = card.dataset.search.toLowerCase();
        let show = selected === 'all';
        if (selected === 'iot') show = text.includes('iot');
        else if (selected === 'networks') show = text.includes('network') || text.includes('5g');
        else if (selected === 'security') show = text.includes('security') || text.includes('privacy');
        else if (selected === 'blockchain') show = text.includes('blockchain');
        else if (selected === 'software') show = text.includes('software') || text.includes('cost');
        else if (selected === 'learning') show = text.includes('learning') || text.includes('federated');
        card.hidden = !show;
        if (show) count += 1;
      });
      $$('[data-research-filter]').forEach((item) => {
        const active = item === button;
        item.classList.toggle('is-active', active);
        item.setAttribute('aria-pressed', String(active));
      });
      if (researchCount) researchCount.textContent = `Showing ${count} ${count === 1 ? 'research area' : 'research areas'}.`;
    });
  });

  // Publication search, type buttons and year selection; no placeholder is presented as verified.
  const publicationCards = $$('[data-publication]');
  const publicationSearch = $('#publicationSearch');
  const publicationYear = $('#publicationYear');
  const publicationCount = $('#publicationCount');
  const publicationEmpty = $('#publicationEmpty');
  let publicationType = 'all';
  function filterPublications() {
    if (!publicationCards.length) return;
    const query = publicationSearch ? publicationSearch.value.trim().toLowerCase() : '';
    const year = publicationYear ? publicationYear.value : 'all';
    let count = 0;
    publicationCards.forEach((card) => {
      const textMatches = !query || card.textContent.toLowerCase().includes(query);
      const typeMatches = publicationType === 'all' || card.dataset.type === publicationType;
      const yearMatches = year === 'all' || card.dataset.year === year;
      const show = textMatches && typeMatches && yearMatches;
      card.hidden = !show;
      if (show) count += 1;
    });
    if (publicationCount) publicationCount.textContent = `Showing ${count} ${count === 1 ? 'publication' : 'publications'}.`;
    if (publicationEmpty) publicationEmpty.hidden = count !== 0;
  }
  if (publicationSearch) publicationSearch.addEventListener('input', filterPublications);
  if (publicationYear) publicationYear.addEventListener('change', filterPublications);
  $$('[data-publication-filter]').forEach((button) => button.addEventListener('click', function () {
    publicationType = button.dataset.publicationFilter;
    $$('[data-publication-filter]').forEach((item) => {
      const active = item === button;
      item.classList.toggle('is-active', active);
      item.setAttribute('aria-pressed', String(active));
    });
    filterPublications();
  }));
  filterPublications();

  // Search the Project Base research themes.
  const projectSearch = $('#projectSearch');
  const projectCards = $$('[data-project-card]');
  const projectCount = $('#projectCount');
  const projectEmpty = $('#projectEmpty');
  function filterProjects() {
    if (!projectCards.length) return;
    const query = projectSearch ? projectSearch.value.trim().toLowerCase() : '';
    let count = 0;
    projectCards.forEach((card) => {
      const text = (card.dataset.search + ' ' + card.textContent).toLowerCase();
      const show = !query || text.includes(query);
      card.hidden = !show;
      if (show) count += 1;
    });
    if (projectCount) projectCount.textContent = `Showing ${count} ${count === 1 ? 'research theme' : 'research themes'}.`;
    if (projectEmpty) projectEmpty.hidden = count !== 0;
  }
  if (projectSearch) projectSearch.addEventListener('input', filterProjects);
  filterProjects();

  // Interactive workload illustration on the homepage.
  const workloadRange = $('#workloadRange');
  const workloadValue = $('#workloadValue');
  const demoTitle = $('#demoTitle');
  const demoSummary = $('#demoSummary');
  const demoBars = $$('.chart-bars .bar');
  let activeDemoTopic = 'IoT networks';
  function updateDemo() {
    if (!workloadRange || !demoBars.length) return;
    const workload = Number(workloadRange.value);
    if (workloadValue) workloadValue.value = workload + '%';
    if (demoTitle) demoTitle.textContent = activeDemoTopic;
    const factors = [0.34, 0.53, 0.75, 0.45];
    demoBars.forEach((bar, index) => {
      const value = Math.max(8, Math.min(98, factors[index] * (0.6 + workload / 125) * 100));
      bar.style.height = value + '%';
    });
    if (demoSummary) demoSummary.textContent = `${activeDemoTopic}: the illustrative chart changes with a ${workload}% workload setting. Illustrative only.`;
  }
  if (workloadRange) workloadRange.addEventListener('input', updateDemo);
  $$('[data-demo-topic]').forEach((button) => button.addEventListener('click', function () {
    activeDemoTopic = button.dataset.demoTopic;
    $$('[data-demo-topic]').forEach((item) => item.setAttribute('aria-selected', String(item === button)));
    updateDemo();
  }));
  updateDemo();

  // PMSL Bot: preset questions only; there is deliberately no text input.
  const faqLauncher = $('#faqLauncher');
  const faqPanel = $('#faqPanel');
  const faqClose = $('#faqClose');
  const faqAnswer = $('#faqAnswer');
  const faqAnswers = {
    'What does PMSL stand for?': 'PMSL means Performance Modeling & Simulation Lab at South Asian University.',
    'What does the lab study?': 'PMSL studies computer-system performance and modern communication networks using modeling and simulation.',
    'Who leads PMSL?': 'The listed Principal Investigator is Dr. Shachi Sharma, Associate Professor in Computer Science and Engineering.',
    'What are the research areas?': 'The six listed areas are IoT traffic modeling, privacy-aware attack detection, 5G communication, blockchain performance, software cost estimation, and federated machine learning.',
    'Who are the current scholars?': 'The available PMSL information lists Prakash Datt Bhatt, Mohit Dey, and Ahmed Jaamay as current PhD scholars.',
    'How can I explore alumni projects?': 'Open People & Alumni from the main navigation. Search by name, batch, research topic, or thesis title.',
    'How do I ask about joining?': 'Use Join or Visit to submit an inquiry to PMSL. Current opportunities should be confirmed directly with the lab.',
    'Where is PMSL located?': 'PMSL is at South Asian University, Faculty of Engineering and Technology, New Delhi 110068, India.'
  };
  if (faqLauncher && faqPanel) {
    faqLauncher.addEventListener('click', function () {
      const open = faqPanel.hidden;
      faqPanel.hidden = !open;
      faqLauncher.setAttribute('aria-expanded', String(open));
      if (open) $('#faqQuestions button')?.focus();
    });
    if (faqClose) faqClose.addEventListener('click', function () {
      faqPanel.hidden = true;
      faqLauncher.setAttribute('aria-expanded', 'false');
      faqLauncher.focus();
    });
    $$('#faqQuestions button').forEach((button) => button.addEventListener('click', function () {
      $$('#faqQuestions button').forEach((item) => item.classList.toggle('is-selected', item === button));
      if (faqAnswer) faqAnswer.textContent = faqAnswers[button.dataset.faq] || 'Please contact PMSL for the latest information.';
    }));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !faqPanel.hidden) {
        faqPanel.hidden = true;
        faqLauncher.setAttribute('aria-expanded', 'false');
      }
    });
  }


})();

// Homepage research spotlight carousel: uses verified research-area summaries only.
(function () {
  const title = document.getElementById('spotlightTitle');
  const copy = document.getElementById('spotlightCopy');
  const position = document.getElementById('spotlightPosition');
  const controls = Array.from(document.querySelectorAll('[data-spotlight-step]'));
  const slides = [
    { title: 'IoT traffic modeling', copy: 'Building traffic models for performance analysis of Internet of Things networks.' },
    { title: 'Privacy-aware attack detection', copy: 'Designing probabilistic models to identify IoT attacks while preserving data privacy.' },
    { title: 'Federated machine learning', copy: 'Evaluating performance and developing federated machine learning algorithms.' }
  ];
  if (!title || !copy || !position || controls.length === 0) return;
  let current = 0;
  function showSlide(index) {
    current = (index + slides.length) % slides.length;
    title.textContent = slides[current].title;
    copy.textContent = slides[current].copy;
    position.textContent = `RESEARCH AREA ${current + 1} OF ${slides.length}`;
  }
  controls.forEach((button) => button.addEventListener('click', () => {
    showSlide(current + Number(button.dataset.spotlightStep));
  }));
})();

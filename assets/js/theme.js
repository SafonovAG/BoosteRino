(function () {
  const KEY = 'theme';
  const root = document.documentElement;
  const btn = document.getElementById('theme-toggle');

  const themes = ['auto', 'light', 'dark'];
  let preference = localStorage.getItem(KEY) || 'auto';

  function systemDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  function resolve(pref) {
    if (pref === 'auto') return systemDark() ? 'dark' : 'light';
    return pref;
  }

  function apply(pref) {
    preference = pref;
    const resolved = resolve(pref);
    root.setAttribute('data-theme-pref', pref);
    root.setAttribute('data-theme', resolved);
    root.style.colorScheme = resolved;
    localStorage.setItem(KEY, pref);
    if (btn) {
      const icons = { auto: 'bi-circle-half', light: 'bi-sun-fill', dark: 'bi-moon-stars-fill' };
      const titles = { auto: 'авто', light: 'светлая', dark: 'тёмная' };
      btn.innerHTML = '<i class="bi ' + (icons[pref] || icons.auto) + ' app-icon app-icon--btn" aria-hidden="true"></i>';
      btn.title = 'Тема: ' + (titles[pref] || titles.auto);
    }
  }

  if (btn) {
    btn.addEventListener('click', () => {
      const idx = themes.indexOf(preference);
      apply(themes[(idx + 1) % themes.length]);
    });
  }

  const footerPlatforms = document.getElementById('footer-platforms');
  const footerPanel = document.getElementById('footer-platforms-panel');
  const footerToggle = document.getElementById('footer-platforms-toggle');

  if (footerPlatforms && footerPanel && footerToggle) {
    const COLLAPSED = 34;

    function syncFooterPlatforms(expand) {
      const open = expand ?? footerPlatforms.classList.contains('is-open');
      footerPanel.style.maxHeight = open
        ? footerPanel.scrollHeight + 'px'
        : COLLAPSED + 'px';
      footerToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      const label = footerToggle.querySelector('.platforms-expand-btn-text');
      if (label) label.textContent = open ? 'Свернуть' : 'Все платформы';
    }

    function refreshFooterPlatforms() {
      const wasOpen = footerPlatforms.classList.contains('is-open');
      footerPanel.style.maxHeight = 'none';
      const needsToggle = footerPanel.scrollHeight > COLLAPSED + 4;
      footerPlatforms.classList.toggle('has-toggle', needsToggle);

      if (!needsToggle) {
        footerPlatforms.classList.remove('is-open');
        footerPanel.style.maxHeight = '';
        return;
      }

      if (wasOpen) {
        footerPlatforms.classList.add('is-open');
        syncFooterPlatforms(true);
      } else {
        syncFooterPlatforms(false);
      }
    }

    footerToggle.addEventListener('click', () => {
      footerPlatforms.classList.toggle('is-open');
      syncFooterPlatforms();
    });

    refreshFooterPlatforms();
    window.addEventListener('resize', refreshFooterPlatforms);
  }

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (preference === 'auto') apply('auto');
  });

  apply(preference);
})();

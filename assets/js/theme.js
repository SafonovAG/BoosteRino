(function () {
  const KEY = 'theme';
  const root = document.documentElement;
  const btn = document.getElementById('theme-toggle');
  const navToggle = document.getElementById('nav-toggle');
  const nav = document.getElementById('store-nav') || document.getElementById('main-nav');

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

  if (navToggle && nav) {
    navToggle.addEventListener('click', () => nav.classList.toggle('open'));
  }

  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (preference === 'auto') apply('auto');
  });

  apply(preference);
})();

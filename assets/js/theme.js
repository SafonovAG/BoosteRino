(function () {
  const KEY = 'theme';
  const root = document.documentElement;
  const btn = document.getElementById('theme-toggle');
  const navToggle = document.getElementById('nav-toggle');
  const nav = document.getElementById('main-nav');

  const themes = ['auto', 'light', 'dark'];
  let current = localStorage.getItem(KEY) || 'auto';

  function apply(theme) {
    root.setAttribute('data-theme', theme);
    localStorage.setItem(KEY, theme);
    current = theme;
    if (btn) {
      const labels = { auto: '🌓', light: '☀️', dark: '🌙' };
      btn.textContent = labels[theme] || '🌓';
      btn.title = 'Тема: ' + theme;
    }
  }

  if (btn) {
    btn.addEventListener('click', () => {
      const idx = themes.indexOf(current);
      apply(themes[(idx + 1) % themes.length]);
    });
  }

  if (navToggle && nav) {
    navToggle.addEventListener('click', () => nav.classList.toggle('open'));
  }

  apply(current);
})();

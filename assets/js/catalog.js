(function () {
  const { api } = window.Boosterino;
  const container = document.getElementById('services-catalog');
  if (!container) return;

  const icons = ['📱', '👥', '❤️', '👁️', '💬', '⭐', '🎬', '📢', '🔥', '✨'];

  function formatPrice(n) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(n);
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  function serviceIcon(index) {
    return icons[index % icons.length];
  }

  async function load() {
    try {
      const data = await api('/api/v1/services');
      const services = data.services || [];
      if (!services.length) {
        container.innerHTML = '<div class="card reveal" style="text-align:center;padding:3rem"><span style="font-size:3rem">📭</span><p class="muted" style="margin-top:1rem">Услуги скоро появятся. Следите за обновлениями на <a href="https://boosterino.ru">boosterino.ru</a></p></div>';
        return;
      }

      let html = '';
      let lastCat = '';
      let idx = 0;

      services.forEach((s) => {
        if (s.category !== lastCat) {
          lastCat = s.category;
          html += '<h2 class="service-category reveal">' + escapeHtml(s.category) + '</h2>';
        }
        html += '<article class="card service-card reveal">' +
          '<span class="service-icon">' + serviceIcon(idx++) + '</span>' +
          '<h3>' + escapeHtml(s.name) + '</h3>' +
          '<div class="price">' + formatPrice(s.price_per_thousand_rub) + ' <span class="muted">/ 1000</span></div>' +
          '<p class="meta">мин. ' + s.min + ' - макс. ' + s.max +
          (s.refill ? ' · 🔄 рефилл' : '') + (s.cancel ? ' · ❌ отмена' : '') + '</p>' +
          '<a href="/register" class="btn btn-primary btn-sm">🛒 Заказать</a>' +
          '</article>';
      });

      container.innerHTML = html;
      document.querySelectorAll('#services-catalog .reveal').forEach((el, i) => {
        el.style.transitionDelay = (i % 6) * 0.08 + 's';
        requestAnimationFrame(() => el.classList.add('visible'));
      });
    } catch {
      container.innerHTML = '<div class="card" style="text-align:center"><span style="font-size:2rem">⚠️</span><p class="muted">Не удалось загрузить каталог. Попробуйте позже.</p></div>';
    }
  }

  load();
})();

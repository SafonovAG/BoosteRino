(function () {
  const { api } = window.Boosterino;
  const container = document.getElementById('services-catalog');
  if (!container) return;

  function formatPrice(n) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(n);
  }

  async function load() {
    try {
      const data = await api('/api/v1/services');
      const services = data.services || [];
      if (!services.length) {
        container.innerHTML = '<p class="muted">Услуги скоро появятся. Следите за обновлениями на <a href="https://boosterino.ru">boosterino.ru</a></p>';
        return;
      }

      let html = '';
      let lastCat = '';

      services.forEach((s) => {
        if (s.category !== lastCat) {
          lastCat = s.category;
          html += `<h2 class="service-category">${escapeHtml(s.category)}</h2>`;
        }
        html += `
          <article class="card service-card">
            <h3>${escapeHtml(s.name)}</h3>
            <div class="price">${formatPrice(s.price_per_thousand_rub)} <span class="muted">/ 1000</span></div>
            <p class="meta">мин. ${s.min} - макс. ${s.max}${s.refill ? ' · refill' : ''}${s.cancel ? ' · отмена' : ''}</p>
            <a href="/register" class="btn btn-primary btn-sm">Заказать</a>
          </article>`;
      });

      container.innerHTML = html;
    } catch {
      container.innerHTML = '<p class="muted">Не удалось загрузить каталог. Попробуйте позже.</p>';
    }
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }

  load();
})();

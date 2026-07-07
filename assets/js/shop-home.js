(function () {
  const { api } = window.Boosterino;
  const container = document.getElementById('featured-products');
  if (!container) return;

  const GENERIC_CATEGORIES = new Set(['api', 'other', 'others', 'misc', 'general', 'разное', 'прочее', 'другое', 'общее']);
  const PREFERRED_PLATFORMS = ['telegram', 'vk', 'youtube', 'instagram', 'tiktok', 'facebook', 'twitch', 'twitter'];

  function formatPrice(n) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(n);
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function isGenericCategory(s) {
    return GENERIC_CATEGORIES.has((s.category || '').toLowerCase().trim());
  }

  function pickFeatured(services) {
    const picked = [];
    const used = new Set();

    PREFERRED_PLATFORMS.forEach((plat) => {
      const match = services.find((s) => s.platform === plat && !used.has(s.id));
      if (match) {
        picked.push(match);
        used.add(match.id);
      }
    });

    const sorted = [...services].sort((a, b) =>
      a.price_per_thousand_rub - b.price_per_thousand_rub
    );

    sorted.forEach((s) => {
      if (picked.length >= 8) return;
      if (used.has(s.id)) return;
      if (isGenericCategory(s) && s.platform === 'other') return;
      picked.push(s);
      used.add(s.id);
    });

    return picked.slice(0, 8);
  }

  function renderCard(s) {
    const label = s.category_label || s.platform_name || s.category || '';
    return '<article class="product-card product-card-featured">' +
      '<div class="product-card-top">' +
        '<div class="product-card-logo"><img src="' + escapeHtml(s.logo) + '" alt="" width="40" height="40"></div>' +
        '<div class="product-card-meta">' +
          '<span class="product-card-category">' + escapeHtml(label) + '</span>' +
          '<h3 class="product-card-title">' + escapeHtml(s.name) + '</h3>' +
        '</div>' +
      '</div>' +
      '<div class="product-card-body">' +
        '<div class="product-card-price-row">' +
          '<span class="product-card-price">' + formatPrice(s.price_per_thousand_rub) + '</span>' +
          '<span class="product-card-price-unit">/ 1000</span>' +
        '</div>' +
        '<a href="/services' + (s.platform && s.platform !== 'other' ? '?platform=' + encodeURIComponent(s.platform) : '') +
          '" class="btn btn-primary btn-buy btn-block">В каталог</a>' +
      '</div>' +
    '</article>';
  }

  api('/api/v1/services').then((data) => {
    const services = pickFeatured(data.services || []);
    if (!services.length) {
      container.innerHTML = '<p class="muted">Каталог скоро наполнится. Загляните позже.</p>';
      return;
    }
    container.innerHTML = services.map(renderCard).join('');
  }).catch(() => {
    container.innerHTML = '';
  });
})();

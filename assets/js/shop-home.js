(function () {
  const { api } = window.Boosterino;
  const container = document.getElementById('featured-products');
  if (!container) return;

  function formatPrice(n) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(n);
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  api('/api/v1/services').then((data) => {
    const services = (data.services || []).slice(0, 8);
    if (!services.length) {
      container.innerHTML = '<p class="muted">Каталог скоро наполнится. Загляните позже.</p>';
      return;
    }
    container.innerHTML = services.map((s) =>
      '<article class="product-card product-card-featured">' +
        '<div class="product-card-top">' +
          '<div class="product-card-logo"><img src="' + escapeHtml(s.logo) + '" alt="" width="40" height="40"></div>' +
          '<div class="product-card-meta">' +
            '<span class="product-card-category">' + escapeHtml(s.category) + '</span>' +
            '<h3 class="product-card-title">' + escapeHtml(s.name) + '</h3>' +
          '</div>' +
        '</div>' +
        '<div class="product-card-body">' +
          '<div class="product-card-price-row">' +
            '<span class="product-card-price">' + formatPrice(s.price_per_thousand_rub) + '</span>' +
            '<span class="product-card-price-unit">/ 1000</span>' +
          '</div>' +
          '<a href="/services" class="btn btn-primary btn-buy btn-block">В каталог</a>' +
        '</div>' +
      '</article>'
    ).join('');
  }).catch(() => {
    container.innerHTML = '';
  });
})();

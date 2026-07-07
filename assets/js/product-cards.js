(function () {
  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function formatPrice(n) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(n);
  }

  function renderProductCard(s, options) {
    options = options || {};
    const badges = [];
    if (s.refill) badges.push('<span class="badge badge-refill">Рефилл</span>');
    if (s.cancel) badges.push('<span class="badge badge-cancel">Отмена</span>');
    const label = s.category_label || s.platform_name || s.category || '';
    const href = '/services/' + s.id;
    const featured = options.featured ? ' product-card-featured' : '';

    return '<article class="product-card' + featured + '" data-platform="' + escapeHtml(s.platform) + '">' +
      '<a href="' + href + '" class="product-card-clickable">' +
        '<div class="product-card-top">' +
          '<div class="product-card-logo"><img src="' + escapeHtml(s.logo) + '" alt="" width="40" height="40"></div>' +
          '<div class="product-card-meta">' +
            '<span class="product-card-category">' + escapeHtml(label) + '</span>' +
            '<h3 class="product-card-title">' + escapeHtml(s.name) + '</h3>' +
          '</div>' +
        '</div>' +
        (badges.length ? '<div class="product-card-badges">' + badges.join('') + '</div>' : '') +
      '</a>' +
      '<div class="product-card-body">' +
        '<div class="product-card-price-row">' +
          '<span class="product-card-price">' + formatPrice(s.price_per_thousand_rub) + '</span>' +
          '<span class="product-card-price-unit">/ 1000</span>' +
        '</div>' +
        '<div class="product-card-stats">' +
          '<span>мин. ' + s.min + '</span>' +
          '<span>макс. ' + s.max + '</span>' +
        '</div>' +
        '<div class="product-card-actions">' +
          '<a href="' + href + '" class="btn btn-secondary btn-sm">Подробнее</a>' +
          '<button type="button" class="btn btn-primary btn-sm btn-add-cart" data-service-id="' + s.id + '">В корзину</button>' +
        '</div>' +
      '</div>' +
    '</article>';
  }

  function bindQuickAdd(container, servicesById) {
    if (!container) return;
    container.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-add-cart, .catalog-row-add');
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      const s = servicesById.get(+btn.dataset.serviceId);
      if (!s || !window.BoosterinoCart) return;
      BoosterinoCart.add({
        service_id: s.id,
        name: s.name,
        logo: s.logo,
        category_label: s.category_label,
        platform_name: s.platform_name,
        price_per_thousand_rub: s.price_per_thousand_rub,
        min: s.min,
        max: s.max,
        quantity: s.min,
        link: '',
        refill: s.refill,
        cancel: s.cancel,
      });
      btn.classList.add('is-added');
      setTimeout(() => btn.classList.remove('is-added'), 600);
      const { toast } = window.Boosterino || {};
      if (toast) toast('В корзине');
    });
  }

  function renderCatalogRow(s) {
    const label = s.category_label || s.platform_name || s.category || '';
    const href = '/services/' + s.id;
    const badges = [];
    if (s.refill) badges.push('<span class="catalog-row-badge catalog-row-badge--refill">Рефилл</span>');
    if (s.cancel) badges.push('<span class="catalog-row-badge catalog-row-badge--cancel">Отмена</span>');

    return '<article class="catalog-row" data-platform="' + escapeHtml(s.platform) + '">' +
      '<a href="' + href + '" class="catalog-row-main">' +
        '<div class="catalog-row-logo"><img src="' + escapeHtml(s.logo) + '" alt="" width="22" height="22"></div>' +
        '<div class="catalog-row-info">' +
          '<span class="catalog-row-platform">' + escapeHtml(label) + '</span>' +
          '<h3 class="catalog-row-title">' + escapeHtml(s.name) + '</h3>' +
          (badges.length ? '<div class="catalog-row-meta">' + badges.join('') + '</div>' : '') +
        '</div>' +
        '<div class="catalog-row-price">' +
          '<strong>' + formatPrice(s.price_per_thousand_rub) + '</strong>' +
          '<span>/1000</span>' +
        '</div>' +
      '</a>' +
      '<div class="catalog-row-actions">' +
        '<button type="button" class="catalog-row-add" data-service-id="' + s.id + '" title="В корзину">+</button>' +
        '<a href="' + href + '" class="catalog-row-go" title="Подробнее">→</a>' +
      '</div>' +
    '</article>';
  }

  window.BoosterinoProductCard = { render: renderProductCard, renderCatalogRow, bindQuickAdd, formatPrice, escapeHtml };
})();

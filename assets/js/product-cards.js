(function () {
  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function formatPrice(n) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(n);
  }

  function formatQty(n) {
    return new Intl.NumberFormat('ru-RU').format(n);
  }

  function parseDeliveryUnit(name) {
    const n = String(name || '').toLowerCase();
    const rules = [
      [/подписчик|followers?|subscriber/i, 'подписчиков'],
      [/лайк|like/i, 'лайков'],
      [/просмотр|view/i, 'просмотров'],
      [/коммент/i, 'комментариев'],
      [/репост|repost|share/i, 'репостов'],
      [/сохранен|save/i, 'сохранений'],
      [/охват|reach/i, 'охвата'],
      [/показ/i, 'показов'],
      [/голос|vote/i, 'голосов'],
      [/участник|member/i, 'участников'],
      [/друг|friend/i, 'друзей'],
      [/прослуш|play/i, 'прослушиваний'],
      [/реакц/i, 'реакций'],
      [/отзыв|review/i, 'отзывов'],
    ];
    for (let i = 0; i < rules.length; i++) {
      if (rules[i][0].test(n)) return rules[i][1];
    }
    return 'единиц';
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
          '<span>мин. ' + formatQty(s.min) + '</span>' +
          '<span>макс. ' + formatQty(s.max) + '</span>' +
        '</div>' +
        '<div class="product-card-actions">' +
          '<a href="' + href + '" class="btn btn-secondary btn-sm">Подробнее</a>' +
          '<button type="button" class="btn btn-primary btn-sm btn-add-cart" data-service-id="' + s.id + '">В корзину</button>' +
        '</div>' +
      '</div>' +
    '</article>';
  }

  function renderHomeTile(s) {
    const label = s.category_label || s.platform_name || s.category || '';
    const href = '/services/' + s.id;
    const unit = parseDeliveryUnit(s.name);
    const badges = [];
    if (s.refill) badges.push('<span class="home-tile-badge home-tile-badge--refill">Р</span>');
    if (s.cancel) badges.push('<span class="home-tile-badge home-tile-badge--cancel">О</span>');

    return '<article class="home-tile" data-platform="' + escapeHtml(s.platform) + '">' +
      '<a href="' + href + '" class="home-tile-media">' +
        '<span class="home-tile-platform">' + escapeHtml(label) + '</span>' +
        (badges.length ? '<div class="home-tile-badges">' + badges.join('') + '</div>' : '') +
        '<img src="' + escapeHtml(s.logo) + '" alt="" width="52" height="52">' +
      '</a>' +
      '<div class="home-tile-body">' +
        '<h3 class="home-tile-title"><a href="' + href + '">' + escapeHtml(s.name) + '</a></h3>' +
        '<div class="home-tile-limits">' +
          '<span class="home-tile-limit">от ' + formatQty(s.min) + '</span>' +
          '<span class="home-tile-limit">' + formatQty(s.max) + ' ' + escapeHtml(unit) + '</span>' +
        '</div>' +
        '<div class="home-tile-foot">' +
          '<div class="home-tile-price">' +
            '<strong>' + formatPrice(s.price_per_thousand_rub) + '</strong>' +
            '<span>за 1000</span>' +
          '</div>' +
          '<div class="home-tile-actions">' +
            '<a href="' + href + '" class="home-tile-more" title="Подробнее">→</a>' +
            '<button type="button" class="home-tile-add btn-add-cart" data-service-id="' + s.id + '">В корзину</button>' +
          '</div>' +
        '</div>' +
      '</div>' +
    '</article>';
  }

  function bindQuickAdd(container, servicesById) {
    if (!container) return;
    container.addEventListener('click', (e) => {
      const btn = e.target.closest('.btn-add-cart, .catalog-tile-add, .home-tile-add');
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
        platform: s.platform,
        service_type: s.type,
        link_label: s.link_label,
        link_placeholder: s.link_placeholder,
        price_per_thousand_rub: s.price_per_thousand_rub,
        min: s.min,
        max: s.max,
        quantity: s.min,
        link: '',
        refill: s.refill,
        cancel: s.cancel,
      });
      if (window.BoosterinoCartFly) {
        BoosterinoCartFly.flyToCart(btn, s.logo);
      }
      btn.classList.add('is-added');
      const old = btn.textContent;
      btn.textContent = 'Добавлено';
      setTimeout(() => {
        btn.classList.remove('is-added');
        btn.textContent = old;
      }, 1200);
      const { toast } = window.Boosterino || {};
      if (toast) toast('В корзине');
    });
  }

  function renderCatalogRow(s) {
    const label = s.category_label || s.platform_name || s.category || '';
    const href = '/services/' + s.id;
    const unit = parseDeliveryUnit(s.name);
    const badges = [];
    if (s.refill) badges.push('<span class="catalog-tile-badge catalog-tile-badge--refill">Рефилл</span>');
    if (s.cancel) badges.push('<span class="catalog-tile-badge catalog-tile-badge--cancel">Отмена</span>');

    return '<article class="catalog-tile" data-platform="' + escapeHtml(s.platform) + '">' +
      '<div class="catalog-tile-glow" aria-hidden="true"></div>' +
      '<a href="' + href + '" class="catalog-tile-top">' +
        '<div class="catalog-tile-logo">' +
          '<img src="' + escapeHtml(s.logo) + '" alt="" width="32" height="32">' +
        '</div>' +
        '<div class="catalog-tile-head">' +
          '<span class="catalog-tile-platform">' + escapeHtml(label) + '</span>' +
          '<h3 class="catalog-tile-title">' + escapeHtml(s.name) + '</h3>' +
        '</div>' +
      '</a>' +
      (badges.length ? '<div class="catalog-tile-badges">' + badges.join('') + '</div>' : '') +
      '<div class="catalog-tile-meta">' +
        '<span class="catalog-tile-limit">от ' + formatQty(s.min) + '</span>' +
        '<span class="catalog-tile-limit">до ' + formatQty(s.max) + ' ' + escapeHtml(unit) + '</span>' +
      '</div>' +
      '<div class="catalog-tile-foot">' +
        '<div class="catalog-tile-price">' +
          '<strong>' + formatPrice(s.price_per_thousand_rub) + '</strong>' +
          '<span>за 1000</span>' +
        '</div>' +
        '<div class="catalog-tile-actions">' +
          '<a href="' + href + '" class="btn btn-ghost btn-sm catalog-tile-more">Подробнее</a>' +
          '<button type="button" class="btn btn-primary btn-sm catalog-tile-add" data-service-id="' + s.id + '">В корзину</button>' +
        '</div>' +
      '</div>' +
    '</article>';
  }

  window.BoosterinoProductCard = {
    render: renderProductCard,
    renderHomeTile,
    renderCatalogRow,
    bindQuickAdd,
    formatPrice,
    formatQty,
    parseDeliveryUnit,
    escapeHtml,
  };
})();

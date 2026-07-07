(function () {
  const { api } = window.Boosterino;
  const container = document.getElementById('services-catalog');
  const countEl = document.getElementById('catalog-count');
  const searchEl = document.getElementById('catalog-search');
  if (!container) return;

  let allServices = [];
  let platform = new URLSearchParams(location.search).get('platform') || 'all';

  function formatPrice(n) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(n);
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function matchesPlatform(s, slug) {
    if (!slug || slug === 'all') return true;
    return s.platform === slug;
  }

  function matchesSearch(s, q) {
    if (!q) return true;
    const hay = (s.name + ' ' + s.category).toLowerCase();
    return hay.includes(q.toLowerCase());
  }

  function renderCard(s) {
    const badges = [];
    if (s.refill) badges.push('<span class="badge badge-refill">Рефилл</span>');
    if (s.cancel) badges.push('<span class="badge badge-cancel">Отмена</span>');
    const buyHref = document.querySelector('.balance-pill') ? '/cabinet' : '/register';
    const buyLabel = document.querySelector('.balance-pill') ? '🛒 Купить' : '🛒 Заказать';

    return '<article class="product-card" data-platform="' + escapeHtml(s.platform) + '">' +
      '<div class="product-card-top">' +
        '<div class="product-card-logo"><img src="' + escapeHtml(s.logo) + '" alt="" width="40" height="40"></div>' +
        '<div class="product-card-meta">' +
          '<span class="product-card-category">' + escapeHtml(s.category) + '</span>' +
          '<h3 class="product-card-title">' + escapeHtml(s.name) + '</h3>' +
        '</div>' +
      '</div>' +
      '<div class="product-card-badges">' + badges.join('') + '</div>' +
      '<div class="product-card-body">' +
        '<div class="product-card-price-row">' +
          '<span class="product-card-price">' + formatPrice(s.price_per_thousand_rub) + '</span>' +
          '<span class="product-card-price-unit">/ 1000</span>' +
        '</div>' +
        '<div class="product-card-stats">' +
          '<span>мин. ' + s.min + '</span>' +
          '<span>макс. ' + s.max + '</span>' +
        '</div>' +
        '<a href="' + buyHref + '" class="btn btn-primary btn-buy btn-block">' + buyLabel + '</a>' +
      '</div>' +
    '</article>';
  }

  function render() {
    const q = searchEl?.value?.trim() || '';
    const filtered = allServices.filter((s) => matchesPlatform(s, platform) && matchesSearch(s, q));

    if (countEl) {
      countEl.textContent = filtered.length + ' ' + pluralGoods(filtered.length);
    }

    if (!filtered.length) {
      container.innerHTML = '<div class="catalog-empty card"><img src="/assets/images/logo/default.svg" alt="" width="48" height="48"><p>Нет услуг по выбранным фильтрам</p><button type="button" class="btn btn-secondary btn-sm" id="reset-filters">Сбросить</button></div>';
      document.getElementById('reset-filters')?.addEventListener('click', () => {
        platform = 'all';
        if (searchEl) searchEl.value = '';
        setActiveFilter('all');
        history.replaceState(null, '', '/services');
        render();
      });
      return;
    }

    const byCategory = {};
    filtered.forEach((s) => {
      const cat = s.category || 'Прочее';
      if (!byCategory[cat]) byCategory[cat] = [];
      byCategory[cat].push(s);
    });

    let html = '';
    Object.keys(byCategory).sort((a, b) => a.localeCompare(b, 'ru')).forEach((cat) => {
      const items = byCategory[cat];
      const catLogo = items[0].logo || '/assets/images/logo/default.svg';
      html += '<div class="catalog-category-head">' +
        '<img src="' + escapeHtml(catLogo) + '" alt="" width="32" height="32">' +
        '<h2 class="catalog-category-title">' + escapeHtml(cat) + '</h2>' +
        '</div>';
      items.forEach((s) => { html += renderCard(s); });
    });
    container.innerHTML = html;
  }

  function pluralGoods(n) {
    const m = n % 10;
    const m2 = n % 100;
    if (m2 >= 11 && m2 <= 14) return 'товаров';
    if (m === 1) return 'товар';
    if (m >= 2 && m <= 4) return 'товара';
    return 'товаров';
  }

  function setActiveFilter(slug) {
    document.querySelectorAll('#platform-filters .filter-item').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.platform === slug);
    });
  }

  document.querySelectorAll('#platform-filters .filter-item').forEach((btn) => {
    btn.addEventListener('click', () => {
      platform = btn.dataset.platform;
      setActiveFilter(platform);
      const url = platform === 'all' ? '/services' : '/services?platform=' + platform;
      history.replaceState(null, '', url);
      render();
    });
  });

  searchEl?.addEventListener('input', () => render());

  document.getElementById('filter-open')?.addEventListener('click', () => {
    document.getElementById('filter-sidebar')?.classList.add('open', 'is-open');
  });
  document.getElementById('filter-close')?.addEventListener('click', () => {
    document.getElementById('filter-sidebar')?.classList.remove('open', 'is-open');
  });

  setActiveFilter(platform);

  api('/api/v1/services').then((data) => {
    allServices = data.services || [];
    render();
  }).catch(() => {
    container.innerHTML = '<div class="catalog-empty card"><p>Не удалось загрузить каталог</p></div>';
  });
})();

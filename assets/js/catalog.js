(function () {
  const { api } = window.Boosterino;
  const container = document.getElementById('services-catalog');
  const countEl = document.getElementById('catalog-count');
  const searchEl = document.getElementById('catalog-search');
  const paginationEl = document.getElementById('catalog-pagination');
  const categoryFiltersEl = document.getElementById('category-filters');
  if (!container) return;

  const PAGE_SIZE = 24;
  const params = new URLSearchParams(location.search);

  let allServices = [];
  let platform = params.get('platform') || 'all';
  let category = params.get('category') || 'all';
  let page = Math.max(1, parseInt(params.get('page') || '1', 10) || 1);

  function formatPrice(n) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(n);
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function platformMeta() {
    const meta = { all: { name: 'Все платформы', logo: '/assets/images/logo/default.svg' } };
    document.querySelectorAll('#platform-filters .filter-item').forEach((btn) => {
      meta[btn.dataset.platform] = {
        name: btn.querySelector('span')?.textContent || btn.dataset.platform,
        logo: btn.querySelector('img')?.src || '/assets/images/logo/default.svg',
      };
    });
    return meta;
  }

  function matchesPlatform(s, slug) {
    if (!slug || slug === 'all') return true;
    return s.platform === slug;
  }

  function matchesCategory(s, cat) {
    if (!cat || cat === 'all') return true;
    return (s.category || 'Прочее') === cat;
  }

  function matchesSearch(s, q) {
    if (!q) return true;
    const hay = (s.name + ' ' + s.category).toLowerCase();
    return hay.includes(q.toLowerCase());
  }

  function getFiltered() {
    const q = searchEl?.value?.trim() || '';
    return allServices.filter((s) =>
      matchesPlatform(s, platform) &&
      matchesCategory(s, category) &&
      matchesSearch(s, q)
    );
  }

  function updateUrl() {
    const p = new URLSearchParams();
    if (platform && platform !== 'all') p.set('platform', platform);
    if (category && category !== 'all') p.set('category', category);
    if (page > 1) p.set('page', String(page));
    const qs = p.toString();
    history.replaceState(null, '', qs ? '/services?' + qs : '/services');
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

  function renderPagination(total, totalPages) {
    if (!paginationEl) return;
    if (totalPages <= 1) {
      paginationEl.innerHTML = '';
      return;
    }

    let html = '<div class="catalog-pagination-inner">';
    html += '<button type="button" class="catalog-page-btn" data-page="' + (page - 1) + '" ' +
      (page <= 1 ? 'disabled' : '') + '>← Назад</button>';
    html += '<div class="catalog-page-list">';

    const pages = buildPageList(page, totalPages);
    pages.forEach((n) => {
      if (n === '…') {
        html += '<span class="catalog-page-ellipsis">…</span>';
      } else {
        html += '<button type="button" class="catalog-page-btn' + (n === page ? ' active' : '') +
          '" data-page="' + n + '">' + n + '</button>';
      }
    });

    html += '</div>';
    html += '<button type="button" class="catalog-page-btn" data-page="' + (page + 1) + '" ' +
      (page >= totalPages ? 'disabled' : '') + '>Вперёд →</button>';
    html += '</div>';
    html += '<p class="catalog-page-info muted">Страница ' + page + ' из ' + totalPages + ' · ' + total + ' ' + pluralGoods(total) + '</p>';

    paginationEl.innerHTML = html;

    paginationEl.querySelectorAll('.catalog-page-btn[data-page]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const next = parseInt(btn.dataset.page, 10);
        if (!next || next < 1 || next > totalPages || next === page) return;
        page = next;
        updateUrl();
        render();
        document.querySelector('.catalog-main')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });
  }

  function buildPageList(current, total) {
    if (total <= 7) {
      return Array.from({ length: total }, (_, i) => i + 1);
    }
    const list = [1];
    if (current > 3) list.push('…');
    for (let i = Math.max(2, current - 1); i <= Math.min(total - 1, current + 1); i++) {
      list.push(i);
    }
    if (current < total - 2) list.push('…');
    list.push(total);
    return list;
  }

  function renderProducts(filtered) {
    const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
    if (page > totalPages) page = totalPages;

    if (countEl) {
      countEl.textContent = filtered.length + ' ' + pluralGoods(filtered.length);
    }

    if (!filtered.length) {
      container.innerHTML = '<div class="catalog-empty card"><img src="/assets/images/logo/default.svg" alt="" width="48" height="48"><p>Нет услуг по выбранным фильтрам</p><button type="button" class="btn btn-secondary btn-sm" id="reset-filters">Сбросить</button></div>';
      if (paginationEl) paginationEl.innerHTML = '';
      document.getElementById('reset-filters')?.addEventListener('click', resetFilters);
      return;
    }

    const start = (page - 1) * PAGE_SIZE;
    const pageItems = filtered.slice(start, start + PAGE_SIZE);

    const byCategory = {};
    pageItems.forEach((s) => {
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
    renderPagination(filtered.length, totalPages);
  }

  function render() {
    updateUrl();
    renderProducts(getFiltered());
    syncCategoryFilterUI();
  }

  function resetFilters() {
    platform = 'all';
    category = 'all';
    page = 1;
    if (searchEl) searchEl.value = '';
    setActivePlatform('all');
    setActiveCategory('all');
    render();
  }

  function pluralGoods(n) {
    const m = n % 10;
    const m2 = n % 100;
    if (m2 >= 11 && m2 <= 14) return 'товаров';
    if (m === 1) return 'товар';
    if (m >= 2 && m <= 4) return 'товара';
    return 'товаров';
  }

  function setActivePlatform(slug) {
    document.querySelectorAll('#platform-filters .filter-item').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.platform === slug);
    });
  }

  function setActiveCategory(cat) {
    document.querySelectorAll('.filter-category-item').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.category === cat);
    });
    document.querySelectorAll('.filter-category-all').forEach((btn) => {
      btn.classList.toggle('active', cat === 'all');
    });
  }

  function syncCategoryFilterUI() {
    document.querySelectorAll('.filter-category-group').forEach((group) => {
      const plat = group.dataset.platform;
      const visible = platform === 'all' || platform === plat;
      group.classList.toggle('is-hidden', !visible);
      group.classList.toggle('is-expanded', platform === plat);
    });
  }

  function buildCategoryFilters() {
    if (!categoryFiltersEl) return;

    const meta = platformMeta();
    const groups = {};

    allServices.forEach((s) => {
      const plat = s.platform || 'other';
      if (!groups[plat]) {
        groups[plat] = new Set();
      }
      groups[plat].add(s.category || 'Прочее');
    });

    const platformOrder = Object.keys(meta).filter((k) => k !== 'all');
    const sortedPlatforms = [
      ...platformOrder.filter((p) => groups[p]),
      ...Object.keys(groups).filter((p) => !platformOrder.includes(p)),
    ];

    let html = '<button type="button" class="filter-item filter-category-all' +
      (category === 'all' ? ' active' : '') + '" data-category="all">Все категории</button>';

    sortedPlatforms.forEach((plat) => {
      const cats = Array.from(groups[plat] || []).sort((a, b) => a.localeCompare(b, 'ru'));
      if (!cats.length) return;

      const info = meta[plat] || { name: plat, logo: '/assets/images/logo/default.svg' };
      const expanded = platform === plat || (platform === 'all' && plat === sortedPlatforms[0]);

      html += '<details class="filter-category-group" data-platform="' + escapeHtml(plat) + '"' +
        (expanded ? ' open' : '') + '>' +
        '<summary class="filter-category-group-head">' +
          '<img src="' + escapeHtml(info.logo) + '" alt="" width="20" height="20">' +
          '<span>' + escapeHtml(info.name) + '</span>' +
          '<span class="filter-category-count">' + cats.length + '</span>' +
        '</summary>' +
        '<ul class="filter-category-list">';

      cats.forEach((cat) => {
        html += '<li><button type="button" class="filter-item filter-category-item' +
          (category === cat && (platform === 'all' || platform === plat) ? ' active' : '') +
          '" data-category="' + escapeHtml(cat) + '" data-platform="' + escapeHtml(plat) + '">' +
          '<span>' + escapeHtml(cat) + '</span></button></li>';
      });

      html += '</ul></details>';
    });

    categoryFiltersEl.innerHTML = html;

    categoryFiltersEl.querySelector('.filter-category-all')?.addEventListener('click', () => {
      category = 'all';
      page = 1;
      setActiveCategory('all');
      render();
    });

    categoryFiltersEl.querySelectorAll('.filter-category-item').forEach((btn) => {
      btn.addEventListener('click', () => {
        category = btn.dataset.category;
        if (platform === 'all' && btn.dataset.platform) {
          platform = btn.dataset.platform;
          setActivePlatform(platform);
        }
        page = 1;
        setActiveCategory(category);
        render();
      });
    });
  }

  document.querySelectorAll('#platform-filters .filter-item').forEach((btn) => {
    btn.addEventListener('click', () => {
      platform = btn.dataset.platform;
      category = 'all';
      page = 1;
      setActivePlatform(platform);
      setActiveCategory('all');
      buildCategoryFilters();
      render();
    });
  });

  searchEl?.addEventListener('input', () => {
    page = 1;
    render();
  });

  document.getElementById('filter-open')?.addEventListener('click', () => {
    document.getElementById('filter-sidebar')?.classList.add('open', 'is-open');
  });
  document.getElementById('filter-close')?.addEventListener('click', () => {
    document.getElementById('filter-sidebar')?.classList.remove('open', 'is-open');
  });

  setActivePlatform(platform);

  api('/api/v1/services').then((data) => {
    allServices = data.services || [];
    buildCategoryFilters();
    setActiveCategory(category);
    render();
  }).catch(() => {
    container.innerHTML = '<div class="catalog-empty card"><p>Не удалось загрузить каталог</p></div>';
    if (categoryFiltersEl) categoryFiltersEl.innerHTML = '<p class="muted">Ошибка загрузки</p>';
  });
})();

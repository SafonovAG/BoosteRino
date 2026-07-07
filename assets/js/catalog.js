(function () {
  const { api } = window.Boosterino;
  const container = document.getElementById('services-catalog');
  const listStage = document.getElementById('catalog-list-stage');
  const countEl = document.getElementById('catalog-count');
  const searchEl = document.getElementById('catalog-search');
  const searchClear = document.getElementById('search-clear');
  const paginationEl = document.getElementById('catalog-pagination');
  const categoryFiltersEl = document.getElementById('category-filters');
  const categoryRailWrap = document.getElementById('category-rail-wrap');
  const activeFiltersEl = document.getElementById('active-filters');
  const scrollTopBtn = document.getElementById('scroll-top');
  if (!container) return;

  const PAGE_SIZE = 30;
  const params = new URLSearchParams(location.search);
  const renderRow = window.BoosterinoProductCard?.renderCatalogRow;

  let allServices = [];
  let platform = params.get('platform') || 'all';
  let category = params.get('category') || 'all';
  let page = Math.max(1, parseInt(params.get('page') || '1', 10) || 1);
  let searchTimer = null;
  let pageTransitionTimer = null;
  const PAGE_TRANSITION_MS = 220;

  function setupScrollRail(root) {
    const track = root.querySelector('.catalog-pro-rail, .catalog-pro-cats');
    const prev = root.querySelector('[data-scroll-prev]');
    const next = root.querySelector('[data-scroll-next]');
    if (!track || !prev || !next) return null;

    const STEP = 240;

    function update() {
      const max = Math.max(0, track.scrollWidth - track.clientWidth);
      const sl = track.scrollLeft;
      const overflow = max > 4;
      prev.classList.toggle('is-hidden', !overflow);
      next.classList.toggle('is-hidden', !overflow);
      prev.disabled = !overflow || sl <= 2;
      next.disabled = !overflow || sl >= max - 2;
    }

    prev.addEventListener('click', () => {
      track.scrollBy({ left: -STEP, behavior: 'smooth' });
    });
    next.addEventListener('click', () => {
      track.scrollBy({ left: STEP, behavior: 'smooth' });
    });

    track.addEventListener('scroll', update, { passive: true });
    track.addEventListener('wheel', (e) => {
      if (track.scrollWidth <= track.clientWidth) return;
      if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
      e.preventDefault();
      track.scrollLeft += e.deltaY;
    }, { passive: false });

    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(update).observe(track);
    }
    window.addEventListener('resize', update, { passive: true });
    update();

    return { refresh: update, track };
  }

  function initScrollRails() {
    refreshScrollRails(false);
  }

  function refreshScrollRails(forceReinit) {
    document.querySelectorAll('[data-catalog-scroll]').forEach((root) => {
      if (forceReinit) delete root._scrollRail;
      if (!root._scrollRail) {
        root._scrollRail = setupScrollRail(root);
      } else {
        root._scrollRail.refresh?.();
      }
    });
    requestAnimationFrame(() => {
      document.querySelectorAll('[data-catalog-scroll]').forEach((root) => {
        root._scrollRail?.refresh?.();
      });
    });
  }

  function renderCategoryChip(cat, label, logo, active) {
    return '<button type="button" class="catalog-pro-chip catalog-pro-chip--cat' +
      (active ? ' is-active' : '') + '" data-category="' + escapeHtml(cat) + '" role="tab"' +
      ' aria-selected="' + (active ? 'true' : 'false') + '">' +
      '<span class="catalog-pro-chip-icon">' +
        '<img src="' + escapeHtml(logo) + '" alt="" width="22" height="22">' +
      '</span>' +
      '<span class="catalog-pro-chip-label">' + escapeHtml(label) + '</span>' +
    '</button>';
  }

  function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function platformMeta() {
    const meta = { all: { name: 'Все', logo: '/assets/images/logo/default.svg' } };
    document.querySelectorAll('#platform-filters .catalog-pro-chip').forEach((btn) => {
      meta[btn.dataset.platform] = {
        name: btn.querySelector('.catalog-pro-chip-label')?.textContent || btn.dataset.platform,
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
    const hay = (s.name + ' ' + s.category + ' ' + (s.platform_name || '')).toLowerCase();
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

  function pluralServices(n) {
    const m = n % 10;
    const m2 = n % 100;
    if (m2 >= 11 && m2 <= 14) return 'услуг';
    if (m === 1) return 'услуга';
    if (m >= 2 && m <= 4) return 'услуги';
    return 'услуг';
  }

  function updateUrl() {
    const p = new URLSearchParams();
    if (platform && platform !== 'all') p.set('platform', platform);
    if (category && category !== 'all') p.set('category', category);
    if (page > 1) p.set('page', String(page));
    const qs = p.toString();
    history.replaceState(null, '', qs ? '/services?' + qs : '/services');
  }

  function setActivePlatform(slug) {
    document.querySelectorAll('#platform-filters .catalog-pro-chip').forEach((btn) => {
      const on = btn.dataset.platform === slug;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
  }

  function renderActiveTags() {
    if (!activeFiltersEl) return;
    const meta = platformMeta();
    let html = '';
    const q = searchEl?.value?.trim();

    if (platform !== 'all') {
      html += '<span class="catalog-pro-tag">' + escapeHtml(meta[platform]?.name || platform) +
        ' <button type="button" data-clear="platform">×</button></span>';
    }
    if (category !== 'all') {
      const label = allServices.find((s) => s.category === category)?.category_label || category;
      html += '<span class="catalog-pro-tag">' + escapeHtml(label) +
        ' <button type="button" data-clear="category">×</button></span>';
    }
    if (q) {
      html += '<span class="catalog-pro-tag">«' + escapeHtml(q) + '»' +
        ' <button type="button" data-clear="search">×</button></span>';
    }
    if (platform !== 'all' || category !== 'all' || q) {
      html += '<button type="button" class="catalog-pro-chip catalog-pro-chip--cat" data-clear="all">Сбросить всё</button>';
    }

    activeFiltersEl.innerHTML = html;
    activeFiltersEl.querySelectorAll('[data-clear]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const t = btn.dataset.clear;
        if (t === 'platform') { platform = 'all'; category = 'all'; }
        else if (t === 'category') category = 'all';
        else if (t === 'search') { if (searchEl) searchEl.value = ''; }
        else resetFilters();
        page = 1;
        buildCategoryFilters();
        render();
      });
    });
  }

  function buildCategoryFilters() {
    if (!categoryFiltersEl) return;

    const cats = new Set();
    allServices.forEach((s) => {
      if (!matchesPlatform(s, platform)) return;
      cats.add(s.category || 'Прочее');
    });

    const sorted = Array.from(cats).sort((a, b) => a.localeCompare(b, 'ru'));

    if (!sorted.length || (platform === 'all' && sorted.length > 12)) {
      categoryRailWrap?.classList.add('hidden');
      categoryFiltersEl.innerHTML = '';
      return;
    }

    categoryRailWrap?.classList.remove('hidden');

    const meta = platformMeta();
    const allLogo = platform !== 'all'
      ? (meta[platform]?.logo || '/assets/images/logo/default.svg')
      : '/assets/images/logo/default.svg';

    let html = renderCategoryChip('all', 'Все', allLogo, category === 'all');

    sorted.forEach((cat) => {
      const sample = allServices.find((s) => s.category === cat);
      const label = sample?.category_label || cat;
      const logo = sample?.logo || '/assets/images/logo/default.svg';
      html += renderCategoryChip(cat, label, logo, category === cat);
    });

    categoryFiltersEl.innerHTML = html;

    categoryFiltersEl.querySelectorAll('[data-category]').forEach((btn) => {
      btn.addEventListener('click', () => {
        category = btn.dataset.category;
        page = 1;
        render();
        btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
      });
    });

    const catScroll = categoryRailWrap?.querySelector('[data-catalog-scroll]');
    if (catScroll) delete catScroll._scrollRail;
    refreshScrollRails(true);
  }

  function scrollToCatalogTop() {
    const deck = document.getElementById('catalog-deck');
    if (!deck) return;
    const headerH = parseInt(
      getComputedStyle(document.documentElement).getPropertyValue('--shop-header-h') || '64',
      10
    ) || 64;
    const top = deck.getBoundingClientRect().top + window.scrollY - headerH;
    window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
  }

  function goToPage(next, totalPages) {
    if (!next || next < 1 || next > totalPages || next === page) return;
    if (pageTransitionTimer) clearTimeout(pageTransitionTimer);

    listStage?.classList.add('is-page-changing');
    paginationEl?.classList.add('is-busy');

    pageTransitionTimer = setTimeout(() => {
      page = next;
      updateUrl();
      renderProducts(getFiltered(), { pageChange: true });
      scrollToCatalogTop();

      requestAnimationFrame(() => {
        listStage?.classList.remove('is-page-changing');
        paginationEl?.classList.remove('is-busy');
        container.classList.add('is-animating');
        setTimeout(() => container.classList.remove('is-animating'), 460);
      });
    }, PAGE_TRANSITION_MS);
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

  function renderPagination(total, totalPages) {
    if (!paginationEl) return;
    if (totalPages <= 1) {
      paginationEl.innerHTML = '';
      return;
    }

    let html = '<div class="catalog-pagination-inner">';
    html += '<button type="button" class="catalog-page-btn" data-page="' + (page - 1) + '"' +
      (page <= 1 ? ' disabled' : '') + '>←</button>';

    const pages = buildPageList(page, totalPages);

    pages.forEach((n) => {
      if (n === '…') {
        html += '<span class="catalog-page-ellipsis">…</span>';
      } else {
        html += '<button type="button" class="catalog-page-btn' + (n === page ? ' active' : '') +
          '" data-page="' + n + '">' + n + '</button>';
      }
    });

    html += '<button type="button" class="catalog-page-btn" data-page="' + (page + 1) + '"' +
      (page >= totalPages ? ' disabled' : '') + '>→</button></div>';
    html += '<p class="catalog-page-info muted">' + page + ' / ' + totalPages + ' · ' + total + ' ' + pluralServices(total) + '</p>';

    paginationEl.innerHTML = html;

    paginationEl.querySelectorAll('.catalog-page-btn[data-page]').forEach((btn) => {
      btn.addEventListener('click', () => {
        goToPage(+btn.dataset.page, totalPages);
      });
    });
  }

  function renderProducts(filtered, options) {
    const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
    if (page > totalPages) page = totalPages;

    if (countEl) {
      countEl.textContent = filtered.length + ' ' + pluralServices(filtered.length);
    }

    container.classList.remove('is-loading');

    if (!filtered.length) {
      container.innerHTML =
        '<div class="catalog-pro-empty">' +
          '<div class="catalog-pro-empty-icon">🔍</div>' +
          '<p>Ничего не найдено</p>' +
          '<button type="button" class="btn btn-secondary btn-sm" id="reset-filters">Сбросить фильтры</button>' +
        '</div>';
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
      const catLabel = items[0].category_label || cat;

      html += '<div class="catalog-group">' +
        '<header class="catalog-group-head">' +
          '<img src="' + escapeHtml(catLogo) + '" alt="" width="36" height="36">' +
          '<span class="catalog-group-title">' + escapeHtml(catLabel) + '</span>' +
          '<span class="catalog-group-count">' + items.length + ' ' + pluralServices(items.length) + '</span>' +
        '</header>' +
        '<div class="catalog-group-rows catalog-group-tiles">';

      items.forEach((s) => {
        html += renderRow ? renderRow(s) : '';
      });

      html += '</div></div>';
    });

    container.innerHTML = html;

    if (!options?.pageChange) {
      container.classList.add('is-animating');
      setTimeout(() => container.classList.remove('is-animating'), 400);
    }

    renderPagination(filtered.length, totalPages);
  }

  function render() {
    updateUrl();
    renderActiveTags();
    renderProducts(getFiltered());
    if (searchClear) {
      searchClear.classList.toggle('hidden', !searchEl?.value?.trim());
    }
  }

  function resetFilters() {
    platform = 'all';
    category = 'all';
    page = 1;
    if (searchEl) searchEl.value = '';
    setActivePlatform('all');
    buildCategoryFilters();
    render();
  }

  document.querySelectorAll('#platform-filters .catalog-pro-chip').forEach((btn) => {
    btn.addEventListener('click', () => {
      platform = btn.dataset.platform;
      category = 'all';
      page = 1;
      setActivePlatform(platform);
      buildCategoryFilters();
      render();
      btn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    });
  });

  searchEl?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      page = 1;
      render();
    }, 200);
  });

  searchClear?.addEventListener('click', () => {
    if (searchEl) searchEl.value = '';
    page = 1;
    render();
    searchEl?.focus();
  });

  if (scrollTopBtn) {
    window.addEventListener('scroll', () => {
      scrollTopBtn.hidden = window.scrollY < 400;
    }, { passive: true });
    scrollTopBtn.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  setActivePlatform(platform);
  initScrollRails();

  api('/api/v1/services').then((data) => {
    allServices = data.services || [];
    const byId = new Map(allServices.map((s) => [s.id, s]));
    window.BoosterinoProductCard.bindQuickAdd(container, byId);
    buildCategoryFilters();
    render();
    refreshScrollRails();
  }).catch(() => {
    container.classList.remove('is-loading');
    container.innerHTML = '<div class="catalog-pro-empty"><p>Не удалось загрузить каталог</p></div>';
  });
})();

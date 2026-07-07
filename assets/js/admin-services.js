(function () {
  const { api, toast } = window.Boosterino;

  function escape(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  let servicesCache = [];
  let platformsCache = [];
  let svcFilter = { platform: null, subcategory: null, q: '' };
  let expandedServiceId = null;
  let sidebarCatsOpen = true;

  function buildPlatforms() {
    const counts = new Map();
    servicesCache.forEach((s) => {
      const slug = s.platform || 'other';
      counts.set(slug, (counts.get(slug) || 0) + 1);
    });

    const fromApi = platformsCache.filter((p) => p.slug !== 'all');
    const known = new Set(fromApi.map((p) => p.slug));

    fromApi.forEach((p) => {
      p.count = counts.get(p.slug) || 0;
    });

    if (counts.has('other') && !known.has('other')) {
      fromApi.push({
        slug: 'other',
        name: 'Прочее',
        logo: '/assets/images/logo/default.svg',
        count: counts.get('other') || 0,
      });
    }

    return fromApi.sort((a, b) => {
      if (a.count !== b.count) return b.count - a.count;
      return a.name.localeCompare(b.name, 'ru');
    });
  }

  function buildSubcategories(platformSlug) {
    const map = new Map();
    servicesCache.forEach((s) => {
      if ((s.platform || 'other') !== platformSlug) return;
      const cat = s.category || 'Прочее';
      if (!map.has(cat)) {
        map.set(cat, { category: cat, count: 0, active: 0, logo: s.logo || '/assets/images/logo/default.svg' });
      }
      const row = map.get(cat);
      row.count++;
      if (s.is_active) row.active++;
    });
    return [...map.values()].sort((a, b) => a.category.localeCompare(b.category, 'ru'));
  }

  function ensureFilterDefaults() {
    const platforms = buildPlatforms();
    if (!platforms.length) {
      svcFilter.platform = null;
      svcFilter.subcategory = null;
      return;
    }
    const withProducts = platforms.filter((p) => p.count > 0);
    const pickFrom = withProducts.length ? withProducts : platforms;
    if (!svcFilter.platform || !platforms.some((p) => p.slug === svcFilter.platform)) {
      svcFilter.platform = pickFrom[0].slug;
    }
    const subs = buildSubcategories(svcFilter.platform);
    if (!svcFilter.subcategory || !subs.some((s) => s.category === svcFilter.subcategory)) {
      svcFilter.subcategory = subs[0]?.category || null;
    }
  }

  function filteredServices() {
    const q = svcFilter.q;
    return servicesCache.filter((s) => {
      if (svcFilter.platform && (s.platform || 'other') !== svcFilter.platform) return false;
      if (!q && svcFilter.subcategory && (s.category || 'Прочее') !== svcFilter.subcategory) return false;
      if (q) {
        const hay = [s.id, s.name, s.category, s.type, s.external_id, s.platform_name].join(' ').toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    });
  }

  function serviceCard(s) {
    const open = expandedServiceId === s.id;
    const desc = s.description || '';
    return (
      '<article class="admin-svc-card' + (open ? ' is-open' : '') + (s.is_active ? '' : ' is-inactive') + '" data-svc-id="' + s.id + '">' +
        '<header class="admin-svc-card-head" data-svc-toggle="' + s.id + '">' +
          '<div class="admin-svc-card-meta">' +
            '<span class="admin-svc-id">#' + s.id + '</span>' +
            '<span class="admin-svc-ext">ID у поставщика: ' + s.external_id + '</span>' +
            (s.is_active ? '<span class="admin-svc-pill admin-svc-pill--ok">Активен</span>' : '<span class="admin-svc-pill">Скрыт</span>') +
          '</div>' +
          '<h3 class="admin-svc-card-title">' + escape(s.name) + '</h3>' +
          '<div class="admin-svc-card-sub">' +
            '<span>' + escape(s.platform_name || '') + '</span>' +
            '<span class="admin-svc-dot">·</span>' +
            '<span>' + escape(s.category) + '</span>' +
          '</div>' +
          '<div class="admin-svc-card-price">' +
            '<strong>' + (s.price_per_thousand_rub ?? s.rate) + ' ₽</strong>' +
            '<span>за 1000 · тариф поставщика: ' + s.rate + '</span>' +
          '</div>' +
        '</header>' +
        (open
          ? '<div class="admin-svc-card-body">' +
              '<div class="admin-svc-form-grid">' +
                '<label>Название<input type="text" data-f="name" value="' + escape(s.name) + '"></label>' +
                '<label>Категория<input type="text" data-f="category" value="' + escape(s.category) + '"></label>' +
                '<label>Тип<input type="text" data-f="type" value="' + escape(s.type) + '"></label>' +
                '<label>Rate (поставщик)<input type="number" step="0.0001" data-f="rate" value="' + s.rate + '"></label>' +
                '<label>Мин.<input type="number" data-f="min_qty" value="' + s.min_qty + '"></label>' +
                '<label>Макс.<input type="number" data-f="max_qty" value="' + s.max_qty + '"></label>' +
                '<label>Наценка %<input type="number" step="0.1" data-f="markup_override" value="' + (s.markup_override ?? '') + '" placeholder="глоб."></label>' +
                '<label class="admin-svc-check"><input type="checkbox" data-f="refill" ' + (s.refill ? 'checked' : '') + '> Рефилл</label>' +
                '<label class="admin-svc-check"><input type="checkbox" data-f="cancel" ' + (s.cancel ? 'checked' : '') + '> Отмена</label>' +
                '<label class="admin-svc-check"><input type="checkbox" data-f="is_active" ' + (s.is_active ? 'checked' : '') + '> Активен в магазине</label>' +
              '</div>' +
              '<label class="admin-svc-desc-label">Описание (для магазина)<textarea data-f="description" rows="3" placeholder="Дополнительное описание товара...">' + escape(desc) + '</textarea></label>' +
              '<div class="admin-svc-card-actions">' +
                '<button type="button" class="btn btn-primary btn-sm" data-svc-save="' + s.id + '">Сохранить</button>' +
                '<a href="/services/' + s.id + '" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Открыть на сайте</a>' +
              '</div>' +
            '</div>'
          : '') +
      '</article>'
    );
  }

  async function saveService(id, card) {
    const payload = { id };
    card.querySelectorAll('[data-f]').forEach((inp) => {
      const key = inp.dataset.f;
      if (inp.type === 'checkbox') payload[key] = inp.checked;
      else payload[key] = inp.value;
    });
    await api('/api/v1/admin/services/' + id, { method: 'PUT', body: JSON.stringify(payload) });
    toast('Товар сохранён');
    await loadAdminServices(false);
    expandedServiceId = id;
    renderServicesPanel();
  }

  function bindServiceListEvents(listEl) {
    listEl.querySelectorAll('[data-svc-toggle]').forEach((head) => {
      head.addEventListener('click', () => {
        const id = +head.dataset.svcToggle;
        expandedServiceId = expandedServiceId === id ? null : id;
        renderServicesPanel();
      });
    });

    listEl.querySelectorAll('[data-svc-save]').forEach((btn) => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const card = btn.closest('.admin-svc-card');
        try {
          await saveService(+btn.dataset.svcSave, card);
        } catch (err) {
          toast(err.message, 'error');
        }
      });
    });
  }

  function renderServicesPanel() {
    const el = document.getElementById('admin-services');
    if (!el) return;

    ensureFilterDefaults();
    const platforms = buildPlatforms();
    const subs = svcFilter.platform ? buildSubcategories(svcFilter.platform) : [];
    const currentPlatform = platforms.find((p) => p.slug === svcFilter.platform);
    const list = filteredServices();

    const sidebarToggle = document.getElementById('admin-svc-cats-toggle');
    const sidebarWrap = document.querySelector('.admin-svc-cats-wrap');
    if (sidebarToggle) sidebarToggle.classList.toggle('is-open', sidebarCatsOpen);
    if (sidebarWrap) sidebarWrap.classList.toggle('is-collapsed', !sidebarCatsOpen);

    const platformBlock = document.getElementById('admin-svc-platforms');
    if (platformBlock) {
      platformBlock.innerHTML = platforms.map((p) =>
        '<button type="button" class="admin-svc-platform' + (svcFilter.platform === p.slug ? ' is-active' : '') + '" data-platform="' + escape(p.slug) + '">' +
          '<img src="' + escape(p.logo || '/assets/images/logo/default.svg') + '" alt="" width="22" height="22" class="admin-svc-platform-logo" loading="lazy">' +
          '<span class="admin-svc-platform-name">' + escape(p.name) + '</span>' +
          '<span class="admin-svc-platform-count">' + p.count + '</span>' +
        '</button>'
      ).join('');
      platformBlock.querySelectorAll('[data-platform]').forEach((btn) => {
        btn.addEventListener('click', () => {
          svcFilter.platform = btn.dataset.platform;
          const nextSubs = buildSubcategories(svcFilter.platform);
          svcFilter.subcategory = nextSubs[0]?.category || null;
          svcFilter.q = '';
          const search = document.getElementById('admin-svc-search');
          if (search) search.value = '';
          renderServicesPanel();
        });
      });
    }

    const subWrap = document.getElementById('admin-svc-subcats-wrap');
    const subBlock = document.getElementById('admin-svc-subcats');
    const platformLabel = document.getElementById('admin-svc-platform-label');
    if (platformLabel && currentPlatform) {
      platformLabel.innerHTML =
        '<img src="' + escape(currentPlatform.logo || '/assets/images/logo/default.svg') + '" alt="" width="20" height="20" class="admin-svc-platform-label-logo" loading="lazy">' +
        '<span>' + escape(currentPlatform.name) + '</span>';
    } else if (platformLabel) {
      platformLabel.textContent = '—';
    }
    if (subWrap && subBlock) {
      const showSubs = subs.length > 0 && !svcFilter.q;
      subWrap.classList.toggle('hidden', !showSubs);
      if (showSubs) {
        subBlock.innerHTML = subs.map((sub) =>
          '<button type="button" class="admin-svc-subcat' + (svcFilter.subcategory === sub.category ? ' is-active' : '') + '" data-subcat="' + encodeURIComponent(sub.category) + '" title="' + escape(sub.category) + '">' +
            '<img src="' + escape(sub.logo || '/assets/images/logo/default.svg') + '" alt="" width="18" height="18" class="admin-svc-subcat-logo" loading="lazy">' +
            '<span class="admin-svc-subcat-name">' + escape(sub.category) + '</span>' +
            '<span class="admin-svc-subcat-count">' + sub.count + '</span>' +
          '</button>'
        ).join('');
        subBlock.querySelectorAll('[data-subcat]').forEach((btn) => {
          btn.addEventListener('click', () => {
            svcFilter.subcategory = decodeURIComponent(btn.dataset.subcat);
            renderServicesPanel();
          });
        });
      }
    }

    const listEl = document.getElementById('admin-svc-list');
    if (listEl) {
      if (!list.length) {
        listEl.innerHTML = '<p class="muted admin-svc-empty">' +
          (svcFilter.q ? 'Ничего не найдено по запросу' : 'Нет товаров в этой подкатегории') +
          '</p>';
      } else {
        listEl.innerHTML = list.map(serviceCard).join('');
        bindServiceListEvents(listEl);
      }
    }

    const countEl = document.getElementById('admin-svc-count');
    if (countEl) {
      const subLabel = svcFilter.q
        ? 'поиск'
        : (svcFilter.subcategory || 'подкатегория');
      countEl.textContent = list.length + ' товаров · ' + subLabel;
    }

    const oldInp = document.getElementById('admin-svc-cat-old');
    const newInp = document.getElementById('admin-svc-cat-new');
    if (oldInp) {
      oldInp.value = svcFilter.subcategory || '';
      if (newInp && !newInp.value && svcFilter.subcategory) {
        newInp.value = svcFilter.subcategory;
      }
    }
  }

  async function loadAdminServices(resetExpand) {
    const el = document.getElementById('admin-services');
    if (!el) return;
    if (resetExpand !== false) expandedServiceId = null;

    if (!el.dataset.shell) {
      el.dataset.shell = '1';
      el.innerHTML =
        '<div class="admin-svc-shell">' +
          '<div class="admin-svc-toolbar">' +
            '<div><h2><span class="panel-icon">📦</span> Товары</h2><p class="muted" id="admin-svc-count">Загрузка...</p></div>' +
            '<div class="admin-svc-toolbar-actions">' +
              '<input type="search" id="admin-svc-search" placeholder="Поиск в выбранной платформе...">' +
              '<button type="button" class="btn btn-primary btn-sm" id="sync-services">🔄 Синхронизировать</button>' +
            '</div>' +
          '</div>' +
          '<p class="muted admin-svc-sync-hint">Новые товары импортируются полностью. У уже добавленных обновляются цена, min/max, рефилл и отмена - название, видимость и описание сохраняются.</p>' +
          '<div class="admin-svc-layout">' +
            '<aside class="admin-svc-sidebar">' +
              '<button type="button" class="admin-svc-sidebar-head" id="admin-svc-cats-toggle">' +
                '<span>Платформы</span>' +
                '<span class="admin-svc-sidebar-chevron" aria-hidden="true"></span>' +
              '</button>' +
              '<div class="admin-svc-cats-wrap">' +
                '<div class="admin-svc-platforms" id="admin-svc-platforms"></div>' +
                '<div class="admin-svc-cat-edit" id="admin-svc-cat-edit">' +
                  '<label class="shop-field-label">Переименовать подкатегорию</label>' +
                  '<input type="text" id="admin-svc-cat-old" placeholder="Текущее имя" readonly>' +
                  '<input type="text" id="admin-svc-cat-new" placeholder="Новое имя">' +
                  '<button type="button" class="btn btn-secondary btn-sm" id="admin-svc-cat-save">Сохранить</button>' +
                '</div>' +
              '</div>' +
            '</aside>' +
            '<div class="admin-svc-main">' +
              '<div class="admin-svc-subcats-wrap" id="admin-svc-subcats-wrap">' +
                '<div class="admin-svc-subcats-head">' +
                  '<span class="admin-svc-subcats-kicker">Подкатегории</span>' +
                  '<strong id="admin-svc-platform-label" class="admin-svc-platform-label"></strong>' +
                '</div>' +
                '<div class="admin-svc-subcats" id="admin-svc-subcats"></div>' +
              '</div>' +
              '<div class="admin-svc-list" id="admin-svc-list"></div>' +
            '</div>' +
          '</div>' +
        '</div>';

      el.querySelector('#admin-svc-cats-toggle')?.addEventListener('click', () => {
        sidebarCatsOpen = !sidebarCatsOpen;
        renderServicesPanel();
      });

      el.querySelector('#sync-services')?.addEventListener('click', async () => {
        try {
          const r = await api('/api/v1/admin/services/sync', { method: 'POST', body: '{}' });
          toast('Обновлено позиций: ' + (r.synced ?? 0) + '. У существующих обновлены цена, лимиты и флаги.');
          await loadAdminServices();
        } catch (e) {
          toast(e.message, 'error');
        }
      });

      let searchTimer;
      el.querySelector('#admin-svc-search')?.addEventListener('input', (e) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
          svcFilter.q = e.target.value.trim().toLowerCase();
          renderServicesPanel();
        }, 250);
      });

      el.querySelector('#admin-svc-cat-save')?.addEventListener('click', async () => {
        const oldName = el.querySelector('#admin-svc-cat-old')?.value;
        const newName = el.querySelector('#admin-svc-cat-new')?.value?.trim();
        if (!oldName || !newName) {
          toast('Укажите новое имя подкатегории', 'error');
          return;
        }
        try {
          await api('/api/v1/admin/services/categories', {
            method: 'PUT',
            body: JSON.stringify({ old_name: oldName, new_name: newName }),
          });
          toast('Подкатегория переименована');
          svcFilter.subcategory = newName;
          await loadAdminServices(false);
        } catch (e) {
          toast(e.message, 'error');
        }
      });
    }

    try {
      const data = await api('/api/v1/admin/services');
      servicesCache = data.services || [];
      platformsCache = data.platforms || [];
      if (servicesCache.length && !window.AdminNav?.sampleServiceId) {
        window.AdminNav = window.AdminNav || {};
        window.AdminNav.sampleServiceId = servicesCache[0].id;
      }
      renderServicesPanel();
    } catch (e) {
      el.innerHTML = '<p class="muted">' + escape(e.message) + '</p>';
    }
  }

  window.loadAdminServices = loadAdminServices;
  window.AdminNav = window.AdminNav || {};
  window.AdminNav.openService = function (id) {
    document.querySelector('[data-panel="services"]')?.click();
    setTimeout(() => {
      const svc = servicesCache.find((s) => s.id === id);
      if (svc) {
        svcFilter.platform = svc.platform || 'other';
        svcFilter.subcategory = svc.category || null;
      }
      svcFilter.q = '';
      expandedServiceId = id;
      renderServicesPanel();
      document.querySelector('.admin-svc-card[data-svc-id="' + id + '"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 80);
  };
})();

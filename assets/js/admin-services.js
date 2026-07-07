(function () {
  const { api, toast } = window.Boosterino;

  function escape(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  let servicesCache = [];
  let categoriesCache = [];
  let svcFilter = { category: 'all', q: '' };
  let expandedServiceId = null;

  function filteredServices() {
    return servicesCache.filter((s) => {
      if (svcFilter.category !== 'all' && s.category !== svcFilter.category) return false;
      if (svcFilter.q) {
        const q = svcFilter.q.toLowerCase();
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
            '<span class="admin-svc-ext">ext ' + s.external_id + '</span>' +
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
            '<span>за 1000 · rate ' + s.rate + '</span>' +
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

  function renderServicesPanel() {
    const el = document.getElementById('admin-services');
    if (!el) return;
    const list = filteredServices();
    const catBlock = document.getElementById('admin-svc-cats');
    if (catBlock) {
      catBlock.innerHTML =
        '<button type="button" class="admin-svc-cat' + (svcFilter.category === 'all' ? ' is-active' : '') + '" data-cat="all">Все <span>' + servicesCache.length + '</span></button>' +
        categoriesCache.map((c) =>
          '<button type="button" class="admin-svc-cat' + (svcFilter.category === c.category ? ' is-active' : '') + '" data-cat="' + encodeURIComponent(c.category) + '">' +
            escape(c.category) + ' <span>' + c.cnt + '</span>' +
          '</button>'
        ).join('');
      catBlock.querySelectorAll('[data-cat]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const raw = btn.dataset.cat;
          svcFilter.category = raw === 'all' ? 'all' : decodeURIComponent(raw);
          renderServicesPanel();
        });
      });
    }

    const listEl = document.getElementById('admin-svc-list');
    if (listEl) {
      listEl.innerHTML = list.length
        ? list.map(serviceCard).join('')
        : '<p class="muted admin-svc-empty">Нет товаров по фильтру</p>';

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

    const countEl = document.getElementById('admin-svc-count');
    if (countEl) countEl.textContent = list.length + ' из ' + servicesCache.length;
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
              '<input type="search" id="admin-svc-search" placeholder="Поиск по названию, ID, категории...">' +
              '<button type="button" class="btn btn-primary btn-sm" id="sync-services">🔄 Синхронизировать</button>' +
            '</div>' +
          '</div>' +
          '<p class="muted admin-svc-sync-hint">Новые товары импортируются полностью. У уже добавленных обновляется только цена (rate) от поставщика - название, видимость и остальные настройки сохраняются.</p>' +
          '<div class="admin-svc-layout">' +
            '<aside class="admin-svc-sidebar">' +
              '<h3>Категории</h3>' +
              '<div class="admin-svc-cats" id="admin-svc-cats"></div>' +
              '<div class="admin-svc-cat-edit" id="admin-svc-cat-edit">' +
                '<label class="shop-field-label">Переименовать категорию</label>' +
                '<input type="text" id="admin-svc-cat-old" placeholder="Текущее имя" readonly>' +
                '<input type="text" id="admin-svc-cat-new" placeholder="Новое имя">' +
                '<button type="button" class="btn btn-secondary btn-sm" id="admin-svc-cat-save">Сохранить категорию</button>' +
              '</div>' +
            '</aside>' +
            '<div class="admin-svc-main"><div class="admin-svc-list" id="admin-svc-list"></div></div>' +
          '</div>' +
        '</div>';

      el.querySelector('#sync-services')?.addEventListener('click', async () => {
        try {
          const r = await api('/api/v1/admin/services/sync', { method: 'POST', body: '{}' });
          toast('Обновлено позиций: ' + (r.synced ?? 0) + '. У существующих товаров изменена только цена.');
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
          toast('Укажите новое имя категории', 'error');
          return;
        }
        try {
          await api('/api/v1/admin/services/categories', {
            method: 'PUT',
            body: JSON.stringify({ old_name: oldName, new_name: newName }),
          });
          toast('Категория переименована');
          svcFilter.category = newName;
          await loadAdminServices(false);
        } catch (e) {
          toast(e.message, 'error');
        }
      });
    }

    try {
      const data = await api('/api/v1/admin/services');
      servicesCache = data.services || [];
      categoriesCache = data.categories || [];
      if (servicesCache.length && !window.AdminNav?.sampleServiceId) {
        window.AdminNav = window.AdminNav || {};
        window.AdminNav.sampleServiceId = servicesCache[0].id;
      }
      renderServicesPanel();

      const oldInp = document.getElementById('admin-svc-cat-old');
      const newInp = document.getElementById('admin-svc-cat-new');
      if (svcFilter.category !== 'all' && oldInp) {
        oldInp.value = svcFilter.category;
        if (newInp && !newInp.value) newInp.value = svcFilter.category;
      } else if (oldInp) {
        oldInp.value = '';
      }
    } catch (e) {
      el.innerHTML = '<p class="muted">' + escape(e.message) + '</p>';
    }
  }

  window.loadAdminServices = loadAdminServices;
  window.AdminNav = window.AdminNav || {};
  window.AdminNav.openService = function (id) {
    document.querySelector('[data-panel="services"]')?.click();
    setTimeout(() => {
      svcFilter.category = 'all';
      svcFilter.q = String(id);
      expandedServiceId = id;
      renderServicesPanel();
      document.querySelector('.admin-svc-card[data-svc-id="' + id + '"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 80);
  };
})();

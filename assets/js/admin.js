(function () {
  const { api, toast } = window.Boosterino;
  const isSuper = document.body.dataset.superadmin === '1';

  const panels = document.querySelectorAll('.panel');
  let ordersFilter = { status: 'all', q: '' };
  let selectedOrderId = null;
  let sampleServiceId = null;

  window.AdminNav = window.AdminNav || {};
  window.AdminNav.selectedUserId = null;
    btn.addEventListener('click', () => {
      const id = btn.dataset.panel;
      document.querySelectorAll('[data-panel]').forEach((b) => b.classList.toggle('active', b === btn));
      panels.forEach((p) => p.classList.toggle('active', p.id === 'panel-' + id));
      if (id === 'dashboard') loadDashboard();
      if (id === 'services') (window.loadAdminServices || (() => {}))();
      if (id === 'orders') loadAdminOrders();
      if (id === 'users') (window.loadUsers || (() => {}))();
      if (id === 'diagnostics') initDiagnostics();
      if (id === 'settings' && isSuper) loadSettings();
    });
  });

  function escape(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  function statusClass(status) {
    const s = String(status || '').toLowerCase();
    if (s.includes('complet')) return 'status-ok';
    if (s.includes('progress') || s.includes('await')) return 'status-warn';
    if (s.includes('cancel') || s.includes('fail') || s.includes('error')) return 'status-bad';
    if (s.includes('pending')) return 'status-pending';
    return '';
  }

  function fmtDate(d) {
    if (!d) return '—';
    try {
      return new Date(d).toLocaleString('ru-RU');
    } catch {
      return d;
    }
  }

  async function loadDashboard() {
    const el = document.getElementById('admin-stats');
    if (!el) return;
    try {
      const data = await api('/api/v1/admin/dashboard');
      const s = data.stats || {};
      const tb = s.twiboost || {};
      const tbLabel = s.twiboost_error
        ? escape(s.twiboost_error)
        : (tb.balance ?? '-') + (tb.currency ? ' ' + tb.currency : '');
      el.innerHTML = '<div class="admin-shop-stats">' +
        '<div class="card stat-card"><span class="stat-icon">👥</span><div class="value">' + (s.users ?? 0) + '</div><div class="label">Клиентов</div></div>' +
        '<div class="card stat-card"><span class="stat-icon">🛒</span><div class="value">' + (s.orders_today ?? 0) + '</div><div class="label">Заказов сегодня</div></div>' +
        '<div class="card stat-card"><span class="stat-icon">💎</span><div class="value">' + tbLabel + '</div><div class="label">Баланс поставщика</div></div>' +
        '</div>';
    } catch (e) {
      el.innerHTML = '<p class="muted">' + escape(e.message) + '</p>';
    }
  }

  async function loadOrderDetail(id) {
    'pending', 'pending_payment', 'Awaiting', 'In progress', 'Partial',
    'Completed', 'Canceled', 'Cancelled', 'Fail', 'Failed', 'Error',
  ];

  function ensureOrderDrawer() {
    if (document.getElementById('admin-order-drawer')) return;
    const wrap = document.createElement('div');
    wrap.innerHTML =
      '<div class="admin-order-backdrop" id="admin-order-backdrop" hidden></div>' +
      '<div class="admin-order-drawer" id="admin-order-drawer" aria-hidden="true">' +
        '<div class="admin-order-drawer-handle" aria-hidden="true"></div>' +
        '<div id="admin-order-detail" class="admin-order-drawer-body"></div>' +
      '</div>';
    document.body.appendChild(wrap);
    document.getElementById('admin-order-backdrop')?.addEventListener('click', closeOrderDrawer);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeOrderDrawer();
    });
  }

  function openOrderDrawer() {
    ensureOrderDrawer();
    document.getElementById('admin-order-backdrop')?.removeAttribute('hidden');
    const drawer = document.getElementById('admin-order-drawer');
    drawer?.classList.add('is-open');
    drawer?.setAttribute('aria-hidden', 'false');
    document.body.classList.add('admin-order-open');
  }

  function closeOrderDrawer() {
    selectedOrderId = null;
    document.getElementById('admin-order-backdrop')?.setAttribute('hidden', '');
    const drawer = document.getElementById('admin-order-drawer');
    drawer?.classList.remove('is-open');
    drawer?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('admin-order-open');
    document.querySelectorAll('.admin-order-row.is-selected').forEach((r) => r.classList.remove('is-selected'));
    const panel = document.getElementById('admin-order-detail');
    if (panel) panel.innerHTML = '';
  }

  async function loadOrderDetail(id) {
    ensureOrderDrawer();
    const panel = document.getElementById('admin-order-detail');
    if (!panel) return;
    openOrderDrawer();
    panel.innerHTML = '<div class="admin-order-drawer-loading"><p class="muted">Загрузка заказа...</p></div>';
    try {
      const data = await api('/api/v1/admin/orders/' + id);
      const o = data.order;
      if (!o) throw new Error('Не найден');

      panel.innerHTML =
        '<div class="admin-order-drawer-top">' +
          '<div class="admin-order-drawer-title">' +
            '<span class="admin-order-drawer-label">Заказ</span>' +
            '<h3>#' + o.id + '</h3>' +
            '<span class="admin-status ' + statusClass(o.status) + '">' + escape(o.status) + '</span>' +
          '</div>' +
          '<button type="button" class="btn btn-sm btn-ghost admin-order-drawer-close" id="close-order-detail" aria-label="Закрыть">×</button>' +
        '</div>' +
        '<div class="admin-order-drawer-sections">' +
          '<section class="admin-order-section">' +
            '<h4>Клиент и услуга</h4>' +
            '<div class="admin-order-fields admin-order-fields--3">' +
              '<div class="admin-order-field"><span>Клиент</span><strong>' +
                '<button type="button" class="admin-link-btn" data-open-user="' + (o.user_id || '') + '">' + escape(o.email) + '</button>' +
              '</strong></div>' +
              '<div class="admin-order-field"><span>Услуга</span><strong>' +
                '<button type="button" class="admin-link-btn" data-open-service="' + (o.service_id || '') + '">' + escape(o.service_name) + '</button>' +
              '</strong></div>' +
              '<div class="admin-order-field"><span>Количество</span><strong>' + o.quantity + '</strong></div>' +
            '</div>' +
          '</section>' +
          '<section class="admin-order-section">' +
            '<h4>Оплата</h4>' +
            '<div class="admin-order-fields admin-order-fields--3">' +
              '<div class="admin-order-field"><span>Сумма</span><strong>' + o.cost_rub + ' ₽</strong></div>' +
              '<div class="admin-order-field"><span>Способ</span><strong>' + escape(o.payment_method) + '</strong></div>' +
              '<div class="admin-order-field"><span>ID поставщика</span><strong>' + (o.twiboost_order_id || '—') + '</strong></div>' +
            '</div>' +
          '</section>' +
          '<section class="admin-order-section admin-order-section--wide">' +
            '<h4>Ссылка</h4>' +
            '<a href="' + escape(o.link) + '" target="_blank" rel="noopener" class="admin-order-link">' + escape(o.link) + '</a>' +
          '</section>' +
          '<section class="admin-order-section">' +
            '<h4>Прогресс у поставщика</h4>' +
            '<div class="admin-order-fields admin-order-fields--4">' +
              '<div class="admin-order-field"><span>Осталось</span><strong>' + (o.remains ?? '—') + '</strong></div>' +
              '<div class="admin-order-field"><span>Старт</span><strong>' + (o.start_count ?? '—') + '</strong></div>' +
              '<div class="admin-order-field"><span>Списание</span><strong>' + (o.charge ?? '—') + '</strong></div>' +
              '<div class="admin-order-field"><span>Обновлён</span><strong>' + fmtDate(o.updated_at) + '</strong></div>' +
            '</div>' +
          '</section>' +
          '<section class="admin-order-section">' +
            '<h4>Даты</h4>' +
            '<div class="admin-order-fields admin-order-fields--2">' +
              '<div class="admin-order-field"><span>Создан</span><strong>' + fmtDate(o.created_at) + '</strong></div>' +
              '<div class="admin-order-field"><span>Категория</span><strong>' + escape(o.category || '—') + '</strong></div>' +
            '</div>' +
          '</section>' +
        '</div>' +
        '<div class="admin-order-drawer-footer">' +
          '<div class="admin-order-status-row">' +
            '<label>Статус вручную' +
              '<select id="admin-order-status" class="shop-select">' +
                ORDER_STATUSES.map((st) =>
                  '<option value="' + st + '"' + (o.status === st ? ' selected' : '') + '>' + st + '</option>'
                ).join('') +
              '</select>' +
            '</label>' +
            '<button type="button" class="btn btn-secondary btn-sm" id="admin-order-save-status">Сохранить</button>' +
          '</div>' +
          '<div class="admin-order-actions">' +
            '<button type="button" class="btn btn-primary btn-sm" id="admin-order-sync">Синхронизировать</button>' +
            '<button type="button" class="btn btn-secondary btn-sm" id="admin-order-refill">Рефилл</button>' +
            '<button type="button" class="btn btn-secondary btn-sm" id="admin-order-cancel">Отменить заказ</button>' +
            '<button type="button" class="btn btn-danger btn-sm" id="admin-order-delete">Удалить навсегда</button>' +
          '</div>' +
        '</div>';

      panel.querySelector('#close-order-detail')?.addEventListener('click', closeOrderDrawer);

      panel.querySelector('#admin-order-save-status')?.addEventListener('click', async () => {
        const status = panel.querySelector('#admin-order-status')?.value;
        await api('/api/v1/admin/orders/' + id, { method: 'PUT', body: JSON.stringify({ status }) });
        toast('Статус обновлён');
        loadOrderDetail(id);
        loadAdminOrders(false);
      });

      panel.querySelector('#admin-order-sync')?.addEventListener('click', async () => {
        try {
          await api('/api/v1/admin/orders/' + id + '/sync', { method: 'POST', body: '{}' });
          toast('Синхронизировано');
          loadOrderDetail(id);
          loadAdminOrders(false);
        } catch (e) {
          toast(e.message, 'error');
        }
      });

      panel.querySelector('#admin-order-refill')?.addEventListener('click', async () => {
        try {
          await api('/api/v1/admin/orders/' + id + '/refill', { method: 'POST', body: '{}' });
          toast('Рефилл отправлен');
        } catch (e) {
          toast(e.message, 'error');
        }
      });

      panel.querySelector('#admin-order-cancel')?.addEventListener('click', async () => {
        if (!confirm('Отменить заказ? Статус будет изменён в системе.')) return;
        try {
          const r = await api('/api/v1/admin/orders/' + id + '/cancel', { method: 'POST', body: '{}' });
          if (r.result?.supplier_error) {
            toast('Отменено локально. Поставщик: ' + r.result.supplier_error, 'error');
          } else {
            toast('Заказ отменён');
          }
          loadOrderDetail(id);
          loadAdminOrders(false);
        } catch (e) {
          toast(e.message, 'error');
        }
      });

      panel.querySelector('#admin-order-delete')?.addEventListener('click', async () => {
        if (!confirm('Удалить заказ #' + id + ' безвозвратно? Это действие нельзя отменить.')) return;
        try {
          await api('/api/v1/admin/orders/' + id, { method: 'DELETE' });
          toast('Заказ удалён');
          closeOrderDrawer();
          loadAdminOrders(false);
          if (window.AdminNav?.selectedUserId && window.AdminNav?.openUser) {
            window.AdminNav.openUser(window.AdminNav.selectedUserId);
          }
        } catch (e) {
          toast(e.message, 'error');
        }
      });

      panel.querySelector('[data-open-user]')?.addEventListener('click', () => {
        const uid = +panel.querySelector('[data-open-user]')?.dataset.openUser;
        if (uid && window.AdminNav?.openUser) window.AdminNav.openUser(uid);
      });
      panel.querySelector('[data-open-service]')?.addEventListener('click', () => {
        const sid = +panel.querySelector('[data-open-service]')?.dataset.openService;
        if (sid && window.AdminNav?.openService) window.AdminNav.openService(sid);
      });
    } catch (e) {
      panel.innerHTML = '<p class="muted">' + escape(e.message) + '</p>';
    }
  }

  async function loadAdminOrders(resetDetail) {
    if (resetDetail !== false) closeOrderDrawer();
    const el = document.getElementById('admin-orders');
    if (!el) return;

    const qs = new URLSearchParams();
    if (ordersFilter.status && ordersFilter.status !== 'all') qs.set('status', ordersFilter.status);
    if (ordersFilter.q) qs.set('q', ordersFilter.q);

    const data = await api('/api/v1/admin/orders' + (qs.toString() ? '?' + qs : ''));
    const rows = data.orders || [];

    el.innerHTML =
      '<div class="admin-orders-list card panel-card">' +
        '<div class="admin-orders-toolbar">' +
          '<h2><span class="panel-icon">🛒</span> Заказы</h2>' +
          '<div class="admin-orders-filters">' +
            '<select id="admin-orders-status-filter" class="shop-select">' +
              '<option value="all">Все статусы</option>' +
              ORDER_STATUSES.map((st) =>
                '<option value="' + st + '"' + (ordersFilter.status === st ? ' selected' : '') + '>' + st + '</option>'
              ).join('') +
            '</select>' +
            '<input type="search" id="admin-orders-search" placeholder="ID, email, ссылка..." value="' + escape(ordersFilter.q) + '">' +
            '<button type="button" class="btn btn-secondary btn-sm" id="admin-orders-refresh">Обновить</button>' +
            '<button type="button" class="btn btn-primary btn-sm" id="admin-orders-sync-all">Синхр. все</button>' +
          '</div>' +
        '</div>' +
        (rows.length
          ? '<div class="table-wrap admin-orders-table-wrap"><table><thead><tr>' +
            '<th>#</th><th>Дата</th><th>Email</th><th>Услуга</th><th>Кол-во</th><th>Сумма</th><th>Статус</th>' +
            '</tr></thead><tbody>' +
            rows.map((o) =>
              '<tr class="admin-order-row' + (selectedOrderId === o.id ? ' is-selected' : '') + '" data-order-id="' + o.id + '">' +
                '<td>' + o.id + '</td>' +
                '<td>' + fmtDate(o.created_at) + '</td>' +
                '<td><button type="button" class="admin-link-btn" data-open-user-inline="' + o.user_id + '">' + escape(o.email) + '</button></td>' +
                '<td class="admin-order-service">' + escape(o.service_name) + '</td>' +
                '<td>' + o.quantity + '</td>' +
                '<td>' + o.cost_rub + ' ₽</td>' +
                '<td><span class="admin-status ' + statusClass(o.status) + '">' + escape(o.status) + '</span></td>' +
              '</tr>'
            ).join('') +
            '</tbody></table></div>'
          : '<p class="muted">📭 Нет заказов</p>') +
      '</div>';

    el.querySelector('#admin-orders-status-filter')?.addEventListener('change', (e) => {
      ordersFilter.status = e.target.value;
      loadAdminOrders();
    });

    let searchTimer;
    el.querySelector('#admin-orders-search')?.addEventListener('input', (e) => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        ordersFilter.q = e.target.value.trim();
        loadAdminOrders();
      }, 300);
    });

    el.querySelector('#admin-orders-refresh')?.addEventListener('click', () => loadAdminOrders(false));
    el.querySelector('#admin-orders-sync-all')?.addEventListener('click', async () => {
      try {
        const r = await api('/api/v1/admin/orders/sync-all', { method: 'POST', body: '{}' });
        toast('Обновлено заказов: ' + (r.updated ?? 0));
        loadAdminOrders(false);
      } catch (e) {
        toast(e.message, 'error');
      }
    });

    el.querySelectorAll('.admin-order-row').forEach((row) => {
      row.addEventListener('click', () => {
        selectedOrderId = +row.dataset.orderId;
        el.querySelectorAll('.admin-order-row').forEach((r) => r.classList.toggle('is-selected', r === row));
        loadOrderDetail(selectedOrderId);
      });
    });

    el.querySelectorAll('[data-open-user-inline]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const uid = +btn.dataset.openUserInline;
        if (uid && window.AdminNav?.openUser) window.AdminNav.openUser(uid);
      });
    });
  }

  window.AdminNav.openOrder = function (id) {
    document.querySelector('[data-panel="orders"]')?.click();
    setTimeout(() => {
      selectedOrderId = id;
      loadOrderDetail(id);
      document.querySelector('.admin-order-row[data-order-id="' + id + '"]')?.classList.add('is-selected');
    }, 80);
  };

  function setNotifyUrl(url) {
    const inp = document.getElementById('yoomoney-notify-url');
    if (inp && url) inp.value = url;
  }

  let settingsBound = false;

  async function loadSettings() {
    const form = document.getElementById('settings-form');
    if (!form) return;
    const data = await api('/api/v1/admin/settings');
    const s = data.settings || {};
    setNotifyUrl(data.yoomoney_notify_url);
    Object.keys(s).forEach((key) => {
      const input = form.querySelector('[name="' + key + '"]');
      if (input && !s[key].is_sensitive) {
        input.value = s[key].value;
      }
    });
    if (!settingsBound) {
      settingsBound = true;
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(form));
        Object.keys(payload).forEach((k) => {
          if (payload[k] === '') delete payload[k];
        });
        await api('/api/v1/admin/settings', { method: 'PUT', body: JSON.stringify(payload) });
        if (payload.app_url) {
          setNotifyUrl((payload.app_url.replace(/\/$/, '')) + '/api/v1/payments/yoomoney/notify');
        }
        toast('Настройки сохранены');
      });
      form.querySelector('[name="app_url"]')?.addEventListener('input', (e) => {
        const base = (e.target.value || '').replace(/\/$/, '');
        if (base) setNotifyUrl(base + '/api/v1/payments/yoomoney/notify');
      });
    }
  }

  function buildApiProbes() {
    const sid = window.AdminNav?.sampleServiceId || sampleServiceId || 1;
    const oid = selectedOrderId || 1;
    const uid = 1;
    const probes = [
      { group: 'Публичное API', name: 'GET /api/v1/services', method: 'GET', url: '/api/v1/services' },
      { group: 'Публичное API', name: 'GET /api/v1/services/{id}', method: 'GET', url: '/api/v1/services/' + sid },
      { group: 'Публичное API', name: 'GET /api/v1/auth/verify-email', method: 'GET', url: '/api/v1/auth/verify-email?token=test', expect: [400, 422] },
      { group: 'Пользователь', name: 'GET /api/v1/user/profile', method: 'GET', url: '/api/v1/user/profile' },
      { group: 'Пользователь', name: 'GET /api/v1/user/orders', method: 'GET', url: '/api/v1/user/orders' },
      { group: 'Пользователь', name: 'GET /api/v1/user/transactions', method: 'GET', url: '/api/v1/user/transactions' },
      { group: 'Админ', name: 'GET /api/v1/admin/dashboard', method: 'GET', url: '/api/v1/admin/dashboard' },
      { group: 'Админ', name: 'GET /api/v1/admin/services', method: 'GET', url: '/api/v1/admin/services' },
      { group: 'Админ', name: 'GET /api/v1/admin/orders', method: 'GET', url: '/api/v1/admin/orders' },
      { group: 'Админ', name: 'GET /api/v1/admin/orders/{id}', method: 'GET', url: '/api/v1/admin/orders/' + oid, expect: [200, 404] },
      { group: 'Админ', name: 'GET /api/v1/admin/users', method: 'GET', url: '/api/v1/admin/users' },
      { group: 'Админ', name: 'GET /api/v1/admin/diagnostics/probe', method: 'GET', url: '/api/v1/admin/diagnostics/probe' },
    ];
    if (isSuper) {
      probes.push({ group: 'Админ', name: 'GET /api/v1/admin/settings', method: 'GET', url: '/api/v1/admin/settings' });
    }
    probes.push(
      { group: 'Админ POST', name: 'POST /api/v1/admin/services/sync', method: 'POST', url: '/api/v1/admin/services/sync', body: '{}', dry: true },
      { group: 'Админ POST', name: 'POST /api/v1/admin/orders/sync-all', method: 'POST', url: '/api/v1/admin/orders/sync-all', body: '{}', dry: true },
      { group: 'Админ POST', name: 'POST /api/v1/admin/diagnostics/run', method: 'POST', url: '/api/v1/admin/diagnostics/run', body: '{}', dry: true },
      { group: 'Админ POST', name: 'POST /api/v1/admin/diagnostics/supplier', method: 'POST', url: '/api/v1/admin/diagnostics/supplier', body: '{}', dry: true },
      { group: 'Auth POST', name: 'POST /api/v1/auth/logout', method: 'POST', url: '/api/v1/auth/logout', body: '{}', expect: [200, 401] },
      { group: 'Auth POST', name: 'POST /api/v1/auth/login', method: 'POST', url: '/api/v1/auth/login', body: JSON.stringify({ email: 'test@test.com', password: 'x' }), expect: [401, 422] },
      { group: 'Пользователь POST', name: 'POST /api/v1/user/orders', method: 'POST', url: '/api/v1/user/orders', body: JSON.stringify({ service_id: sid, link: 'https://example.com', quantity: 1 }), expect: [401, 422] },
      { group: 'Пользователь POST', name: 'PUT /api/v1/admin/users/{id}', method: 'PUT', url: '/api/v1/admin/users/' + uid, body: JSON.stringify({ role: 'user' }), expect: [200, 403, 404], dry: true }
    );
    return probes;
  }

  async function probeEndpoint(probe, dryRun) {
    const start = performance.now();
    if (dryRun && probe.dry) {
      return { status: 'skip', message: 'Пропущен (изменяет данные)', ms: 0 };
    }
    try {
      const opts = { method: probe.method };
      if (probe.body !== undefined) {
        opts.body = probe.body;
      }
      const res = await fetch(probe.url, {
        ...opts,
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        credentials: 'same-origin',
      });
      const ms = Math.round(performance.now() - start);
      const expected = probe.expect || [200];
      const ok = expected.includes(res.status);
      let message = 'HTTP ' + res.status;
      try {
        const j = await res.json();
        if (j.error?.message) message += ': ' + j.error.message;
        else if (j.message) message += ': ' + j.message;
      } catch { /* ignore */ }
      return { status: ok ? 'ok' : 'error', message, ms };
    } catch (e) {
      return { status: 'error', message: e.message, ms: Math.round(performance.now() - start) };
    }
  }

  let activeDiagTab = 'system';

  function renderDiagResults(serverResults, apiResults, targetId) {
    const el = document.getElementById(targetId || 'diagnostics-results');
    if (!el) return;

    const all = [];
    (serverResults || []).forEach((r) => all.push({ ...r, type: 'server' }));
    (apiResults || []).forEach((r) => all.push({ ...r, type: 'api' }));

    const groups = {};
    all.forEach((r) => {
      const g = r.group || 'Прочее';
      if (!groups[g]) groups[g] = [];
      groups[g].push(r);
    });

    let html = '';
    Object.keys(groups).forEach((g) => {
      html += '<div class="diag-group"><h3>' + escape(g) + '</h3><div class="diag-list">';
      groups[g].forEach((r) => {
        const icon = r.status === 'ok' ? '✓' : (r.status === 'warn' ? '!' : (r.status === 'skip' ? '○' : '✕'));
        html += '<div class="diag-item diag-item--' + r.status + '">' +
          '<span class="diag-icon">' + icon + '</span>' +
          '<div class="diag-body">' +
            '<strong>' + escape(r.name) + '</strong>' +
            '<span class="diag-msg">' + escape(r.message) + '</span>' +
          '</div>' +
          (r.ms ? '<span class="diag-ms">' + r.ms + ' мс</span>' : '') +
        '</div>';
      });
      html += '</div></div>';
    });

    const ok = all.filter((r) => r.status === 'ok').length;
    const total = all.filter((r) => r.status !== 'skip').length;
    el.innerHTML = '<div class="diag-summary">' + ok + ' / ' + total + ' проверок успешно</div>' + html;
  }

  async function runDiagnosticsTab(tab, dryRun) {
    const btn = document.getElementById('run-diagnostics');
    const resultsEl = document.getElementById('diagnostics-results');
    if (btn) btn.disabled = true;
    if (resultsEl) resultsEl.innerHTML = '<p class="muted">Выполняется проверка...</p>';

    try {
      if (!sampleServiceId && tab !== 'supplier') {
        try {
          const svc = await api('/api/v1/admin/services');
          if (svc.services?.length) sampleServiceId = svc.services[0].id;
        } catch { /* ignore */ }
      }

      if (tab === 'supplier') {
        const data = await api('/api/v1/admin/diagnostics/supplier', { method: 'POST', body: '{}' });
        const results = (data.results || []).map((r) => ({
          group: r.group,
          name: r.name,
          status: r.status === 'ok' ? 'ok' : (r.status === 'warn' ? 'warn' : 'error'),
          message: r.message,
          ms: r.ms,
        }));
        renderDiagResults(results, [], 'diagnostics-results');
        toast('Проверка API поставщика завершена');
        return;
      }

      if (tab === 'shop-api') {
        const probes = buildApiProbes();
        const apiResults = [];
        for (const probe of probes) {
          const r = await probeEndpoint(probe, dryRun);
          apiResults.push({
            group: probe.group,
            name: probe.name,
            ...r,
          });
        }
        renderDiagResults([], apiResults, 'diagnostics-results');
        toast('Проверка API магазина завершена');
        return;
      }

      const serverData = await api('/api/v1/admin/diagnostics/run', { method: 'POST', body: '{}' });
      const serverResults = (serverData.results || []).map((r) => ({
        group: r.group,
        name: r.name,
        status: r.status === 'ok' ? 'ok' : (r.status === 'warn' ? 'warn' : 'error'),
        message: r.message,
        ms: r.ms,
      }));
      renderDiagResults(serverResults, [], 'diagnostics-results');
      toast('Системная диагностика завершена');
    } catch (e) {
      if (resultsEl) resultsEl.innerHTML = '<p class="muted">' + escape(e.message) + '</p>';
      toast(e.message, 'error');
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  async function runDiagnostics(dryRun) {
    return runDiagnosticsTab(activeDiagTab, dryRun);
  }

  function setDiagTab(tab) {
    activeDiagTab = tab;
    const wrap = document.getElementById('admin-diagnostics');
    wrap?.querySelectorAll('.diag-tab').forEach((b) => b.classList.toggle('is-active', b.dataset.diagTab === tab));
    const dryLabel = wrap?.querySelector('.diag-dry-label');
    if (dryLabel) dryLabel.style.display = tab === 'shop-api' ? 'flex' : 'none';
    const desc = document.getElementById('diag-tab-desc');
    if (desc) {
      const texts = {
        system: 'База данных, настройки магазина, ЮMoney, SMTP и каталог.',
        'shop-api': 'Проверка всех эндпоинтов Boosterino API.',
        supplier: 'Проверка Twiboost API v2 по документации api/info: balance, services, status, refill, cancel и сверка каталога.',
      };
      desc.textContent = texts[tab] || '';
    }
    const results = document.getElementById('diagnostics-results');
    if (results) results.innerHTML = '<p class="muted">Нажмите кнопку для запуска проверки</p>';
  }

  function initDiagnostics() {
    const el = document.getElementById('admin-diagnostics');
    if (!el || el.dataset.ready === '1') return;
    el.dataset.ready = '1';
    el.innerHTML =
      '<div class="card panel-card">' +
        '<h2><span class="panel-icon">🔬</span> Диагностика</h2>' +
        '<div class="diag-tabs">' +
          '<button type="button" class="diag-tab is-active" data-diag-tab="system">Система</button>' +
          '<button type="button" class="diag-tab" data-diag-tab="shop-api">API магазина</button>' +
          '<button type="button" class="diag-tab" data-diag-tab="supplier">API поставщика</button>' +
        '</div>' +
        '<p class="muted" id="diag-tab-desc">База данных, настройки магазина, ЮMoney, SMTP и каталог.</p>' +
        '<div class="diag-toolbar">' +
          '<button type="button" class="btn btn-primary" id="run-diagnostics">Запустить проверку</button>' +
          '<label class="diag-dry-label" style="display:none"><input type="checkbox" id="diag-dry-run" checked> Безопасный режим (только API магазина)</label>' +
        '</div>' +
        '<div id="diagnostics-results" class="diag-results"><p class="muted">Нажмите кнопку для запуска проверки</p></div>' +
      '</div>';

    el.querySelectorAll('.diag-tab').forEach((btn) => {
      btn.addEventListener('click', () => setDiagTab(btn.dataset.diagTab));
    });

    el.querySelector('#run-diagnostics')?.addEventListener('click', () => {
      const dry = el.querySelector('#diag-dry-run')?.checked;
      runDiagnostics(dry);
    });
  }

  document.getElementById('copy-notify-url')?.addEventListener('click', () => {
    const inp = document.getElementById('yoomoney-notify-url');
    if (!inp) return;
    navigator.clipboard.writeText(inp.value).then(() => toast('URL скопирован')).catch(() => {
      inp.select();
      document.execCommand('copy');
      toast('URL скопирован');
    });
  });

  loadDashboard();
})();

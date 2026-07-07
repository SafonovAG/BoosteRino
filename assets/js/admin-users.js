(function () {
  const { api, toast } = window.Boosterino;
  const isSuper = document.body.dataset.superadmin === '1';

  function escape(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  function fmtDate(d) {
    if (!d) return '—';
    try {
      return new Date(d).toLocaleString('ru-RU');
    } catch {
      return d;
    }
  }

  function statusClass(status) {
    const s = String(status || '').toLowerCase();
    if (s.includes('complet')) return 'status-ok';
    if (s.includes('progress') || s.includes('await')) return 'status-warn';
    if (s.includes('cancel') || s.includes('fail') || s.includes('error')) return 'status-bad';
    if (s.includes('pending')) return 'status-pending';
    return '';
  }

  let usersFilter = { q: '' };
  let selectedUserId = null;
  let usersCache = [];

  window.AdminNav = window.AdminNav || {};

  const roleLabel = { user: 'Пользователь', admin: 'Админ', superadmin: 'Superadmin' };

  function ensureUserDrawer() {
    if (document.getElementById('admin-user-drawer')) return;
    const wrap = document.createElement('div');
    wrap.innerHTML =
      '<div class="admin-user-backdrop" id="admin-user-backdrop" hidden></div>' +
      '<aside class="admin-user-drawer" id="admin-user-drawer" aria-hidden="true">' +
        '<div id="admin-user-detail" class="admin-user-drawer-body"></div>' +
      '</aside>';
    document.body.appendChild(wrap);
    document.getElementById('admin-user-backdrop')?.addEventListener('click', closeUserDrawer);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && selectedUserId) closeUserDrawer();
    });
  }

  function openUserDrawer() {
    ensureUserDrawer();
    document.getElementById('admin-user-backdrop')?.removeAttribute('hidden');
    const drawer = document.getElementById('admin-user-drawer');
    drawer?.classList.add('is-open');
    drawer?.setAttribute('aria-hidden', 'false');
    document.body.classList.add('admin-user-open');
  }

  function closeUserDrawer() {
    selectedUserId = null;
    if (window.AdminNav) window.AdminNav.selectedUserId = null;
    document.getElementById('admin-user-backdrop')?.setAttribute('hidden', '');
    const drawer = document.getElementById('admin-user-drawer');
    drawer?.classList.remove('is-open');
    drawer?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('admin-user-open');
    document.querySelectorAll('.admin-user-row.is-selected').forEach((r) => r.classList.remove('is-selected'));
    const panel = document.getElementById('admin-user-detail');
    if (panel) panel.innerHTML = '';
  }

  function linkOrder(id) {
    return '<button type="button" class="admin-link-btn" data-open-order="' + id + '">#' + id + '</button>';
  }

  function linkService(id, name) {
    return '<button type="button" class="admin-link-btn" data-open-service="' + id + '">' + escape(name || ('#' + id)) + '</button>';
  }

  async function loadUserDetail(id, panelOnly) {
    ensureUserDrawer();
    const panel = document.getElementById('admin-user-detail');
    if (!panel) return;
    if (!panelOnly) {
      selectedUserId = id;
      if (window.AdminNav) window.AdminNav.selectedUserId = id;
      openUserDrawer();
    }
    panel.innerHTML = '<div class="admin-user-drawer-loading"><p class="muted">Загрузка...</p></div>';
    try {
      const data = await api('/api/v1/admin/users/' + id);
      const u = data.user;
      if (!u) throw new Error('Не найден');

      const stats = u.stats || {};
      panel.innerHTML =
        '<div class="admin-user-drawer-top">' +
          '<div>' +
            '<span class="admin-order-drawer-label">Клиент #' + u.id + '</span>' +
            '<h3>' + escape(u.email) + '</h3>' +
            '<div class="admin-user-badges">' +
              '<span class="admin-status ' + (u.is_active ? 'status-ok' : 'status-bad') + '">' + (u.is_active ? 'Активен' : 'Заблокирован') + '</span>' +
              '<span class="admin-status status-pending">' + escape(roleLabel[u.role] || u.role) + '</span>' +
              (u.email_verified_at ? '<span class="admin-status status-ok">Email подтверждён</span>' : '<span class="admin-status status-warn">Email не подтверждён</span>') +
            '</div>' +
          '</div>' +
          '<button type="button" class="btn btn-sm btn-ghost admin-order-drawer-close" id="close-user-detail">×</button>' +
        '</div>' +

        '<div class="admin-user-stats">' +
          '<div class="admin-user-stat"><span>Баланс</span><strong>' + u.balance_rub + ' ₽</strong></div>' +
          '<div class="admin-user-stat"><span>Заказов</span><strong>' + (stats.order_count ?? 0) + '</strong></div>' +
          '<div class="admin-user-stat"><span>Потрачено</span><strong>' + (stats.total_spent ?? 0) + ' ₽</strong></div>' +
          '<div class="admin-user-stat"><span>Регистрация</span><strong>' + fmtDate(u.created_at) + '</strong></div>' +
        '</div>' +

        '<section class="admin-user-section">' +
          '<h4>Управление</h4>' +
          '<div class="admin-user-form">' +
            (isSuper
              ? '<label>Роль<select id="user-role" class="shop-select">' +
                  ['user', 'admin', 'superadmin'].map((r) =>
                    '<option value="' + r + '"' + (u.role === r ? ' selected' : '') + '>' + (roleLabel[r] || r) + '</option>'
                  ).join('') +
                '</select></label>'
              : '<input type="hidden" id="user-role" value="' + escape(u.role) + '">') +
            '<label class="admin-svc-check"><input type="checkbox" id="user-active" ' + (u.is_active ? 'checked' : '') + '> Аккаунт активен</label>' +
            '<label>Баланс, ₽<input type="number" step="0.01" id="user-balance" value="' + u.balance_rub + '"></label>' +
            '<label>Корректировка ±<input type="number" step="0.01" id="user-balance-delta" placeholder="например 500 или -200"></label>' +
            '<button type="button" class="btn btn-primary btn-sm" id="user-save-profile">Сохранить изменения</button>' +
          '</div>' +
        '</section>' +

        '<section class="admin-user-section">' +
          '<h4>Заказы <span class="muted">(' + (u.orders?.length || 0) + ')</span></h4>' +
          (u.orders?.length
            ? '<div class="admin-user-scroll-table"><table><thead><tr><th>#</th><th>Дата</th><th>Услуга</th><th>Сумма</th><th>Статус</th></tr></thead><tbody>' +
              u.orders.map((o) =>
                '<tr class="admin-user-order-row" data-open-order="' + o.id + '">' +
                  '<td>' + linkOrder(o.id) + '</td>' +
                  '<td>' + fmtDate(o.created_at) + '</td>' +
                  '<td>' + linkService(o.service_id, o.service_name) + '</td>' +
                  '<td>' + o.cost_rub + ' ₽</td>' +
                  '<td><span class="admin-status ' + statusClass(o.status) + '">' + escape(o.status) + '</span></td>' +
                '</tr>'
              ).join('') +
              '</tbody></table></div>'
            : '<p class="muted">Нет заказов</p>') +
        '</section>' +

        '<section class="admin-user-section">' +
          '<h4>Операции баланса</h4>' +
          (u.transactions?.length
            ? '<div class="admin-user-scroll-table"><table><thead><tr><th>Дата</th><th>Тип</th><th>Сумма</th><th>После</th><th>Ссылка</th></tr></thead><tbody>' +
              u.transactions.map((t) =>
                '<tr>' +
                  '<td>' + fmtDate(t.created_at) + '</td>' +
                  '<td>' + escape(t.type) + '</td>' +
                  '<td>' + t.amount_rub + ' ₽</td>' +
                  '<td>' + t.balance_after + ' ₽</td>' +
                  '<td>' + (t.reference_type === 'order' && t.reference_id ? linkOrder(t.reference_id) : '—') + '</td>' +
                '</tr>'
              ).join('') +
              '</tbody></table></div>'
            : '<p class="muted">Нет операций</p>') +
        '</section>' +

        '<section class="admin-user-section">' +
          '<h4>Платежи</h4>' +
          (u.payments?.length
            ? '<div class="admin-user-scroll-table"><table><thead><tr><th>ID</th><th>Дата</th><th>Тип</th><th>Сумма</th><th>Статус</th><th>Заказ</th></tr></thead><tbody>' +
              u.payments.map((p) =>
                '<tr>' +
                  '<td>' + p.id + '</td>' +
                  '<td>' + fmtDate(p.created_at) + '</td>' +
                  '<td>' + escape(p.type) + '</td>' +
                  '<td>' + p.amount_rub + ' ₽</td>' +
                  '<td>' + escape(p.status) + '</td>' +
                  '<td>' + (p.order_id ? linkOrder(p.order_id) : '—') + '</td>' +
                '</tr>'
              ).join('') +
              '</tbody></table></div>'
            : '<p class="muted">Нет платежей</p>') +
        '</section>';

      panel.querySelector('#close-user-detail')?.addEventListener('click', closeUserDrawer);

      panel.querySelector('#user-save-profile')?.addEventListener('click', async () => {
        const payload = {
          is_active: panel.querySelector('#user-active')?.checked,
        };
        const roleEl = panel.querySelector('#user-role');
        if (roleEl?.tagName === 'SELECT') payload.role = roleEl.value;
        const delta = panel.querySelector('#user-balance-delta')?.value;
        const balance = panel.querySelector('#user-balance')?.value;
        if (delta !== '' && delta != null) payload.balance_delta = delta;
        else if (balance !== '' && balance != null) payload.balance_rub = balance;
        try {
          await api('/api/v1/admin/users/' + id, { method: 'PUT', body: JSON.stringify(payload) });
          toast('Профиль сохранён');
          loadUserDetail(id, true);
          loadUsers(false);
        } catch (e) {
          toast(e.message, 'error');
        }
      });

      bindCrossLinks(panel);
    } catch (e) {
      panel.innerHTML = '<p class="muted">' + escape(e.message) + '</p>';
    }
  }

  function bindCrossLinks(root) {
    root.querySelectorAll('[data-open-order]').forEach((el) => {
      el.addEventListener('click', (e) => {
        e.stopPropagation();
        const oid = +el.dataset.openOrder;
        if (window.AdminNav?.openOrder) window.AdminNav.openOrder(oid);
      });
    });
    root.querySelectorAll('[data-open-service]').forEach((el) => {
      el.addEventListener('click', (e) => {
        e.stopPropagation();
        const sid = +el.dataset.openService;
        if (window.AdminNav?.openService) window.AdminNav.openService(sid);
      });
    });
    root.querySelectorAll('.admin-user-order-row').forEach((row) => {
      row.addEventListener('click', () => {
        if (window.AdminNav?.openOrder) window.AdminNav.openOrder(+row.dataset.openOrder);
      });
    });
  }

  async function loadUsers(resetDrawer) {
    if (resetDrawer !== false) closeUserDrawer();
    const el = document.getElementById('admin-users');
    if (!el) return;

    const qs = usersFilter.q ? '?q=' + encodeURIComponent(usersFilter.q) : '';
    const data = await api('/api/v1/admin/users' + qs);
    usersCache = data.users || [];

    el.innerHTML =
      '<div class="admin-users-shell">' +
        '<div class="admin-users-toolbar">' +
          '<div><h2><span class="panel-icon">👥</span> Клиенты</h2><p class="muted">' + usersCache.length + ' пользователей</p></div>' +
          '<input type="search" id="admin-users-search" placeholder="ID или email..." value="' + escape(usersFilter.q) + '">' +
        '</div>' +
        '<div class="admin-users-grid">' +
          usersCache.map((u) =>
            '<article class="admin-user-card admin-user-row' + (selectedUserId === u.id ? ' is-selected' : '') + '" data-user-id="' + u.id + '">' +
              '<div class="admin-user-card-top">' +
                '<span class="admin-user-card-id">#' + u.id + '</span>' +
                '<span class="admin-status ' + (u.is_active ? 'status-ok' : 'status-bad') + '">' + (u.is_active ? 'Активен' : 'Блок') + '</span>' +
              '</div>' +
              '<h3 class="admin-user-card-email">' + escape(u.email) + '</h3>' +
              '<div class="admin-user-card-meta">' +
                '<span>' + escape(roleLabel[u.role] || u.role) + '</span>' +
                '<strong>' + u.balance_rub + ' ₽</strong>' +
              '</div>' +
              '<div class="admin-user-card-date muted">' + fmtDate(u.created_at) + '</div>' +
            '</article>'
          ).join('') +
        '</div>' +
      '</div>';

    let searchTimer;
    el.querySelector('#admin-users-search')?.addEventListener('input', (e) => {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        usersFilter.q = e.target.value.trim();
        loadUsers();
      }, 300);
    });

    el.querySelectorAll('.admin-user-row').forEach((row) => {
      row.addEventListener('click', () => {
        const id = +row.dataset.userId;
        el.querySelectorAll('.admin-user-row').forEach((r) => r.classList.toggle('is-selected', r === row));
        loadUserDetail(id);
      });
    });
  }

  window.loadUsers = loadUsers;
  window.AdminNav = window.AdminNav || {};
  window.AdminNav.openUser = function (id) {
    document.querySelector('[data-panel="users"]')?.click();
    setTimeout(() => loadUserDetail(id), 80);
  };
})();

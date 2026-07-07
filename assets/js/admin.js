(function () {
  const { api, toast } = window.Boosterino;
  const isSuper = document.body.dataset.superadmin === '1';

  const panels = document.querySelectorAll('.panel');
  document.querySelectorAll('[data-panel]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.panel;
      document.querySelectorAll('[data-panel]').forEach((b) => b.classList.toggle('active', b === btn));
      panels.forEach((p) => p.classList.toggle('active', p.id === 'panel-' + id));
      if (id === 'dashboard') loadDashboard();
      if (id === 'services') loadAdminServices();
      if (id === 'orders') loadAdminOrders();
      if (id === 'users') loadUsers();
      if (id === 'settings' && isSuper) loadSettings();
    });
  });

  function escape(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
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
      el.innerHTML = '<div class="stats-grid">' +
        '<div class="card stat-card stat-users"><span class="stat-icon">👥</span><div class="value">' + (s.users ?? 0) + '</div><div class="label">Пользователей</div></div>' +
        '<div class="card stat-card stat-orders"><span class="stat-icon">🛒</span><div class="value">' + (s.orders_today ?? 0) + '</div><div class="label">Заказов сегодня</div></div>' +
        '<div class="card stat-card stat-balance"><span class="stat-icon">💎</span><div class="value">' + tbLabel + '</div><div class="label">Баланс Twiboost</div></div>' +
        '</div>';
    } catch (e) {
      el.innerHTML = '<p class="muted">' + escape(e.message) + '</p>';
    }
  }

  async function loadAdminServices() {
    const el = document.getElementById('admin-services');
    if (!el) return;
    const data = await api('/api/v1/admin/services');
    const list = data.services || [];
    el.innerHTML = '<h2><span class="panel-icon">📦</span> Услуги</h2><div class="table-wrap"><table>' +
      '<thead><tr><th>ID</th><th>Название</th><th>Цена</th><th>Наценка %</th><th>Активна</th><th></th></tr></thead><tbody>' +
      list.map((s) => '<tr>' +
        '<td>' + s.id + '</td>' +
        '<td>' + escape(s.name) + '</td>' +
        '<td>' + s.rate + '</td>' +
        '<td><input type="number" step="0.1" value="' + (s.markup_override ?? '') + '" data-markup="' + s.id + '" placeholder="глоб." style="width:80px"></td>' +
        '<td><input type="checkbox" data-active="' + s.id + '" ' + (s.is_active ? 'checked' : '') + '></td>' +
        '<td><button class="btn btn-sm btn-secondary" data-save="' + s.id + '" type="button">Сохранить</button></td>' +
        '</tr>').join('') +
      '</tbody></table></div>' +
      '<p style="margin-top:1.25rem"><button class="btn btn-primary" id="sync-services" type="button">🔄 Синхронизировать с Twiboost</button></p>';

    el.querySelector('#sync-services')?.addEventListener('click', async () => {
      const r = await api('/api/v1/admin/services/sync', { method: 'POST', body: '{}' });
      toast('Синхронизировано: ' + (r.synced ?? 0));
      loadAdminServices();
    });

    el.querySelectorAll('[data-save]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.save;
        const markup = el.querySelector('[data-markup="' + id + '"]')?.value;
        const active = el.querySelector('[data-active="' + id + '"]')?.checked;
        await api('/api/v1/admin/services', {
          method: 'PUT',
          body: JSON.stringify({ id: +id, markup_override: markup, is_active: active }),
        });
        toast('Сохранено');
      });
    });
  }

  async function loadAdminOrders() {
    const el = document.getElementById('admin-orders');
    if (!el) return;
    const data = await api('/api/v1/admin/orders');
    const rows = data.orders || [];
    el.innerHTML = rows.length
      ? '<h2><span class="panel-icon">🛒</span> Заказы</h2><div class="table-wrap"><table>' +
        '<thead><tr><th>#</th><th>Email</th><th>Услуга</th><th>Сумма</th><th>Статус</th></tr></thead><tbody>' +
        rows.map((o) => '<tr><td>' + o.id + '</td><td>' + escape(o.email) + '</td><td>' + escape(o.service_name) + '</td><td>' + o.cost_rub + '</td><td>' + escape(o.status) + '</td></tr>').join('') +
        '</tbody></table></div>'
      : '<p class="muted">📭 Нет заказов</p>';
  }

  async function loadUsers() {
    const el = document.getElementById('admin-users');
    if (!el) return;
    const data = await api('/api/v1/admin/users');
    const rows = data.users || [];
    const roleLabel = { user: 'Пользователь', admin: 'Админ', superadmin: 'Superadmin' };
    el.innerHTML = '<h2><span class="panel-icon">👥</span> Пользователи</h2><div class="table-wrap"><table>' +
      '<thead><tr><th>ID</th><th>Email</th><th>Роль</th><th>Баланс</th>' + (isSuper ? '<th></th>' : '') + '</tr></thead><tbody>' +
      rows.map((u) => {
        let roleCell = roleLabel[u.role] || u.role;
        if (isSuper) {
          roleCell = '<select data-role-user="' + u.id + '">' +
            ['user', 'admin', 'superadmin'].map((r) =>
              '<option value="' + r + '"' + (u.role === r ? ' selected' : '') + '>' + (roleLabel[r] || r) + '</option>'
            ).join('') +
            '</select>';
        }
        return '<tr><td>' + u.id + '</td><td>' + escape(u.email) + '</td><td>' + roleCell + '</td><td>' + u.balance_rub + '</td>' +
          (isSuper ? '<td><button type="button" class="btn btn-sm btn-secondary" data-save-role="' + u.id + '">Сохранить</button></td>' : '') +
          '</tr>';
      }).join('') +
      '</tbody></table></div>';

    if (isSuper) {
      el.querySelectorAll('[data-save-role]').forEach((btn) => {
        btn.addEventListener('click', async () => {
          const id = btn.dataset.saveRole;
          const role = el.querySelector('[data-role-user="' + id + '"]')?.value;
          await api('/api/v1/admin/users/' + id, { method: 'PUT', body: JSON.stringify({ role }) });
          toast('Роль обновлена');
          loadUsers();
        });
      });
    }
  }

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

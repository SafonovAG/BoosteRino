(function () {
  const { api, toast } = window.Boosterino;
  const panels = document.querySelectorAll('.panel');
  const navBtns = document.querySelectorAll('[data-panel]');

  navBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.panel;
      navBtns.forEach((b) => b.classList.toggle('active', b === btn));
      panels.forEach((p) => p.classList.toggle('active', p.id === 'panel-' + id));
    });
  });

  const balanceEl = document.getElementById('user-balance');
  const ordersEl = document.getElementById('orders-list');
  const txEl = document.getElementById('transactions-list');
  const serviceSelect = document.getElementById('order-service');
  const orderForm = document.getElementById('order-form');

  let services = [];

  function statusClass(s) {
    const v = (s || '').toLowerCase();
    if (v.includes('complet')) return 'status-completed';
    if (v.includes('cancel') || v.includes('fail')) return 'status-canceled';
    if (v.includes('progress') || v.includes('partial') || v.includes('await')) return 'status-progress';
    return 'status-pending';
  }

  function fmt(n) {
    return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB' }).format(n);
  }

  function escape(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  async function loadProfile() {
    const data = await api('/api/v1/user/profile');
    const profile = data.user;
    if (balanceEl) balanceEl.textContent = fmt(profile.balance_rub);
    const verify = document.getElementById('email-warning');
    if (verify && !profile.email_verified_at) {
      verify.classList.remove('hidden');
    }
  }

  async function loadOrders() {
    if (!ordersEl) return;
    const data = await api('/api/v1/user/orders');
    const orders = data.orders || [];
    if (!orders.length) {
      ordersEl.innerHTML = '<p class="muted">Заказов пока нет</p>';
      return;
    }
    ordersEl.innerHTML = '<div class="table-wrap"><table>' +
      '<thead><tr><th>#</th><th>Услуга</th><th>Кол-во</th><th>Сумма</th><th>Статус</th><th></th></tr></thead><tbody>' +
      orders.map((o) => '<tr>' +
        '<td>' + o.id + '</td>' +
        '<td>' + escape(o.service_name || o.service_id) + '</td>' +
        '<td>' + o.quantity + '</td>' +
        '<td>' + fmt(o.cost_rub) + '</td>' +
        '<td><span class="status-badge ' + statusClass(o.status) + '">' + escape(o.status) + '</span></td>' +
        '<td>' +
        '<button class="btn btn-sm btn-secondary" data-refill="' + o.id + '" type="button">Рефилл</button> ' +
        '<button class="btn btn-sm btn-danger" data-cancel="' + o.id + '" type="button">Отмена</button>' +
        '</td></tr>').join('') +
      '</tbody></table></div>';

    ordersEl.querySelectorAll('[data-refill]').forEach((b) => {
      b.addEventListener('click', () => orderAction(b.dataset.refill, 'refill'));
    });
    ordersEl.querySelectorAll('[data-cancel]').forEach((b) => {
      b.addEventListener('click', () => orderAction(b.dataset.cancel, 'cancel'));
    });
  }

  async function orderAction(id, action) {
    try {
      await api('/api/v1/user/orders/' + id + '/' + action, { method: 'POST', body: '{}' });
      toast(action === 'refill' ? 'Рефилл запрошен' : 'Отмена запрошена');
      loadOrders();
    } catch (e) {
      toast(e.message, 'error');
    }
  }

  async function loadTransactions() {
    if (!txEl) return;
    const data = await api('/api/v1/user/transactions');
    const rows = data.transactions || [];
    if (!rows.length) {
      txEl.innerHTML = '<p class="muted">Нет операций</p>';
      return;
    }
    txEl.innerHTML = '<div class="table-wrap"><table>' +
      '<thead><tr><th>Дата</th><th>Тип</th><th>Сумма</th><th>Баланс</th></tr></thead><tbody>' +
      rows.map((t) => '<tr>' +
        '<td>' + escape(t.created_at) + '</td>' +
        '<td>' + escape(t.type) + '</td>' +
        '<td>' + fmt(t.amount_rub) + '</td>' +
        '<td>' + fmt(t.balance_after) + '</td>' +
        '</tr>').join('') +
      '</tbody></table></div>';
  }

  async function loadServices() {
    if (!serviceSelect) return;
    const data = await api('/api/v1/services');
    services = data.services || [];
    serviceSelect.innerHTML = services.map((s) =>
      '<option value="' + s.id + '">' + escape(s.name) + ' (' + fmt(s.price_per_thousand_rub) + '/1000)</option>'
    ).join('');
    updatePrice();
  }

  function updatePrice() {
    const preview = document.getElementById('order-price');
    if (!preview || !serviceSelect) return;
    const sid = +serviceSelect.value;
    const qty = +(document.getElementById('order-quantity')?.value || 0);
    const svc = services.find((s) => s.id === sid);
    if (!svc || !qty) {
      preview.textContent = '-';
      return;
    }
    const perUnit = svc.price_per_thousand_rub / 1000;
    preview.textContent = fmt(perUnit * qty);
  }

  if (serviceSelect) {
    serviceSelect.addEventListener('change', updatePrice);
    document.getElementById('order-quantity')?.addEventListener('input', updatePrice);
  }

  if (orderForm) {
    orderForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(orderForm);
      try {
        const result = await api('/api/v1/user/orders', {
          method: 'POST',
          body: JSON.stringify({
            service_id: +fd.get('service_id'),
            link: fd.get('link'),
            quantity: +fd.get('quantity'),
            payment_method: fd.get('payment_method'),
          }),
        });
        if (result.payment_url) {
          location.href = result.payment_url;
          return;
        }
        toast('Заказ создан');
        loadProfile();
        loadOrders();
      } catch (err) {
        toast(err.message, 'error');
      }
    });
  }

  document.getElementById('topup-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const amount = +new FormData(e.target).get('amount');
    try {
      const data = await api('/api/v1/user/balance/topup', {
        method: 'POST',
        body: JSON.stringify({ amount }),
      });
      if (data.payment_url) location.href = data.payment_url;
    } catch (err) {
      toast(err.message, 'error');
    }
  });

  document.getElementById('password-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    try {
      await api('/api/v1/user/change-password', {
        method: 'POST',
        body: JSON.stringify({
          current_password: fd.get('current_password'),
          new_password: fd.get('new_password'),
        }),
      });
      toast('Пароль изменён');
      e.target.reset();
    } catch (err) {
      toast(err.message, 'error');
    }
  });

  document.getElementById('logout-btn')?.addEventListener('click', async () => {
    await api('/api/v1/auth/logout', { method: 'POST', body: '{}' });
    location.href = '/';
  });

  Promise.all([loadProfile(), loadOrders(), loadTransactions(), loadServices()]).catch((e) => toast(e.message, 'error'));

  const paymentStatus = new URLSearchParams(location.search).get('payment');
  if (paymentStatus === 'ok' || paymentStatus === 'success') {
    toast('Оплата принята. Баланс обновится после подтверждения ЮMoney.');
  }
})();

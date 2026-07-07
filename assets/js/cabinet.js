(function () {
  const { api, toast } = window.Boosterino;
  const panels = document.querySelectorAll('.panel');
  const navBtns = document.querySelectorAll('[data-panel]');

  document.querySelectorAll('[data-panel-jump]').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const btn = document.querySelector('[data-panel="' + link.dataset.panelJump + '"]');
      btn?.click();
    });
  });

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

  function fmtDate(d) {
    if (!d) return '—';
    try {
      return new Date(d).toLocaleString('ru-RU', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
      });
    } catch {
      return d;
    }
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
      ordersEl.innerHTML =
        '<div class="cabinet-orders-empty">' +
          '<p class="muted">Заказов пока нет. Оформите первый заказ в каталоге.</p>' +
          '<a href="/services" class="btn btn-primary">Перейти в каталог</a>' +
        '</div>';
      return;
    }

    ordersEl.innerHTML =
      '<div class="cabinet-orders-list">' +
        orders.map((o) => {
          const num = o.order_number || o.id;
          const unit = o.quantity_unit || 'ед.';
          return (
            '<article class="cabinet-order-card">' +
              '<div class="cabinet-order-card-body">' +
                '<div class="cabinet-order-card-top">' +
                  '<span class="cabinet-order-number">№' + num + '</span>' +
                  '<span class="status-badge ' + statusClass(o.status) + '">' + escape(o.status_label || o.status) + '</span>' +
                '</div>' +
                '<h3 class="cabinet-order-title">' + escape(o.service_name || 'Услуга') + '</h3>' +
                '<ul class="cabinet-order-facts">' +
                  '<li><span>Количество</span><strong>' + o.quantity + ' ' + escape(unit) + '</strong></li>' +
                  '<li><span>Сумма</span><strong>' + fmt(o.cost_rub) + '</strong></li>' +
                  '<li><span>Дата</span><strong>' + fmtDate(o.created_at) + '</strong></li>' +
                '</ul>' +
              '</div>' +
              '<div class="cabinet-order-card-action">' +
                '<a href="/orders/' + o.id + '" class="btn btn-secondary">Посмотреть заказ</a>' +
              '</div>' +
            '</article>'
          );
        }).join('') +
      '</div>';
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

  Promise.all([loadProfile(), loadOrders(), loadTransactions()]).catch((e) => toast(e.message, 'error'));

  const paymentStatus = new URLSearchParams(location.search).get('payment');
  if (paymentStatus === 'ok' || paymentStatus === 'success') {
    toast('Оплата принята. Баланс обновится после подтверждения ЮMoney.');
  }
})();

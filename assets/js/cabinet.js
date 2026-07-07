(function () {
  const { api, toast } = window.Boosterino;
  const I = window.BoosterinoIcons;
  const panels = document.querySelectorAll('.cabinet-pro-panel, .panel');
  const navBtns = document.querySelectorAll('[data-panel]');

  document.querySelectorAll('[data-panel-jump]').forEach((link) => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const id = link.dataset.panelJump;
      document.querySelector('[data-panel="' + id + '"]')?.click();
    });
  });

  navBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.dataset.panel;
      if (!id) return;
      navBtns.forEach((b) => b.classList.toggle('active', b === btn));
      panels.forEach((p) => p.classList.toggle('active', p.id === 'panel-' + id));
    });
  });

  const balanceEl = document.getElementById('user-balance');
  const emailEl = document.getElementById('user-email');
  const ordersEl = document.getElementById('orders-list');
  const txEl = document.getElementById('transactions-list');
  const statsEl = document.getElementById('account-stats');

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
    if (typeof d === 'string' && d.includes(' г.')) return d;
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
    const stats = data.stats || {};
    if (balanceEl) balanceEl.textContent = fmt(profile.balance_rub);
    if (emailEl) emailEl.textContent = profile.email || '—';
    const verify = document.getElementById('email-warning');
    if (verify && !profile.email_verified_at) {
      verify.classList.remove('hidden');
    }
    renderStats(stats, profile);
  }

  function renderStats(stats, profile) {
    if (!statsEl) return;
    const verified = stats.email_verified ?? !!profile.email_verified_at;
    const stat = (icon, value, label, sm) =>
      '<div class="cabinet-pro-stat">' +
        '<span class="cabinet-pro-stat-icon">' + I.html(icon, 'app-icon--accent') + '</span>' +
        '<span class="cabinet-pro-stat-value' + (sm ? ' cabinet-pro-stat-value--sm' : '') + '">' + value + '</span>' +
        '<span class="cabinet-pro-stat-label">' + label + '</span>' +
      '</div>';
    statsEl.innerHTML =
      '<div class="cabinet-pro-stats-grid">' +
        stat('bag-check', stats.orders_total ?? 0, 'Всего заказов', false) +
        stat('hourglass-split', stats.orders_active ?? 0, 'В работе', false) +
        stat('check-circle', stats.orders_completed ?? 0, 'Выполнено', false) +
        stat('currency-dollar', fmt(stats.spent_rub ?? 0), 'Потрачено', false) +
        stat('wallet2', fmt(stats.topup_rub ?? 0), 'Пополнено', false) +
        stat('calendar-event', escape(stats.member_since || '—'), 'С нами с', true) +
      '</div>' +
      '<p class="cabinet-pro-stats-note muted">' +
        (verified
          ? '<i class="bi bi-patch-check-fill app-icon app-icon--success app-icon--inline" aria-hidden="true"></i> Email подтверждён'
          : '<i class="bi bi-envelope-exclamation app-icon app-icon--amber app-icon--inline" aria-hidden="true"></i> Email не подтверждён') +
      '</p>';
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
                  '<li><span>Дата</span><strong>' + escape(fmtDate(o.created_at_formatted || o.created_at)) + '</strong></li>' +
                '</ul>' +
              '</div>' +
              '<div class="cabinet-order-card-action">' +
                '<a href="/orders/' + o.id + '" class="btn btn-secondary">Подробнее</a>' +
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
      txEl.innerHTML = '<p class="cabinet-pro-empty">Операций пока нет</p>';
      return;
    }
    txEl.innerHTML =
      '<div class="cabinet-tx-list">' +
        rows.map((t) => {
          const amount = Number(t.amount_rub);
          const isPlus = amount >= 0;
          let typeLine = '<span class="cabinet-tx-type">' + escape(t.type_label || t.type);
          if (t.type === 'order' && t.order_id) {
            typeLine += ' · <a href="/orders/' + t.order_id + '" class="cabinet-tx-order-link">Заказ №' +
              escape(String(t.order_number || t.order_id)) + '</a>';
          }
          typeLine += '</span>';
          return (
            '<article class="cabinet-tx-item">' +
              '<div class="cabinet-tx-main">' +
                typeLine +
                '<span class="cabinet-tx-date">' + escape(t.created_at_formatted || fmtDate(t.created_at)) + '</span>' +
              '</div>' +
              '<div class="cabinet-tx-amount cabinet-tx-amount--' + (isPlus ? 'plus' : 'minus') + '">' +
                (isPlus ? '+' : '') + fmt(amount) +
              '</div>' +
              '<div class="cabinet-tx-balance">баланс ' + fmt(t.balance_after) + '</div>' +
            '</article>'
          );
        }).join('') +
      '</div>';
  }

  const topupForm = document.getElementById('topup-form');
  const amountInput = topupForm?.querySelector('[name="amount"]');

  document.querySelectorAll('.cabinet-pro-preset').forEach((btn) => {
    btn.addEventListener('click', () => {
      const val = btn.dataset.amount;
      if (amountInput) amountInput.value = val;
      document.querySelectorAll('.cabinet-pro-preset').forEach((b) => b.classList.toggle('is-active', b === btn));
    });
  });

  if (amountInput) {
    const preset500 = document.querySelector('.cabinet-pro-preset[data-amount="500"]');
    preset500?.classList.add('is-active');
  }

  topupForm?.addEventListener('submit', async (e) => {
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

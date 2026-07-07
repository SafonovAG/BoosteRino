(function () {
  const { api } = window.Boosterino;
  const root = document.getElementById('order-success-page');
  if (!root) return;

  const ids = (root.dataset.orderIds || '')
    .split(',')
    .map((x) => parseInt(x, 10))
    .filter((x) => x > 0);

  function escape(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  function fmtRub(n) {
    const v = Math.round(Number(n) * 100) / 100;
    return v.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' ₽';
  }

  function statusClass(status) {
    const s = String(status || '').toLowerCase();
    if (s.includes('complet')) return 'order-status--ok';
    if (s.includes('progress') || s.includes('await') || s.includes('partial')) return 'order-status--warn';
    if (s.includes('cancel') || s.includes('fail') || s.includes('error')) return 'order-status--bad';
    return 'order-status--pending';
  }

  function renderEmpty() {
    root.innerHTML =
      '<div class="order-result-card card">' +
        '<div class="order-result-icon">✅</div>' +
        '<h1>Спасибо за заказ!</h1>' +
        '<p class="muted">Перейдите в кабинет, чтобы посмотреть историю заказов.</p>' +
        '<div class="order-result-actions">' +
          '<a href="/cabinet" class="btn btn-primary">Мой кабинет</a>' +
          '<a href="/services" class="btn btn-secondary">Каталог</a>' +
        '</div>' +
      '</div>';
  }

  function render(orders) {
    const total = orders.reduce((s, o) => s + Number(o.cost_rub || 0), 0);
    const multi = orders.length > 1;

    root.innerHTML =
      '<div class="order-result-card card">' +
        '<div class="order-result-icon">✅</div>' +
        '<h1>' + (multi ? 'Заказы успешно оформлены' : 'Заказ успешно оплачен') + '</h1>' +
        '<p class="order-result-lead">' +
          (multi
            ? 'Оформлено заказов: <strong>' + orders.length + '</strong> на сумму <strong>' + fmtRub(total) + '</strong>'
            : 'Списано с баланса: <strong>' + fmtRub(total) + '</strong>') +
        '</p>' +
        '<div class="order-result-list">' +
          orders.map((o) =>
            '<article class="order-result-item">' +
              '<div class="order-result-item-top">' +
                '<span class="order-result-id">№' + o.id + '</span>' +
                '<span class="order-status-badge ' + statusClass(o.status) + '">' + escape(o.status_label || o.status) + '</span>' +
              '</div>' +
              '<h3>' + escape(o.service_name) + '</h3>' +
              '<p class="muted order-result-meta">' + o.quantity + ' ' + (o.quantity_unit || 'ед.') + ' · ' + fmtRub(o.cost_rub) + '</p>' +
              '<a href="/orders/' + o.id + '" class="btn btn-secondary btn-sm">Статус заказа →</a>' +
            '</article>'
          ).join('') +
        '</div>' +
        '<div class="order-result-actions">' +
          (orders.length === 1
            ? '<a href="/orders/' + orders[0].id + '" class="btn btn-primary">Статус заказа</a>'
            : '<a href="/cabinet" class="btn btn-primary">Все заказы в кабинете</a>') +
          '<a href="/services" class="btn btn-secondary">Продолжить покупки</a>' +
          '<a href="/cabinet" class="btn btn-ghost">Мой кабинет</a>' +
        '</div>' +
      '</div>';
  }

  if (!ids.length) {
    renderEmpty();
    return;
  }

  api('/api/v1/user/orders/batch?ids=' + ids.join(','))
    .then((data) => {
      const orders = data.orders || [];
      if (!orders.length) {
        renderEmpty();
        return;
      }
      render(orders);
    })
    .catch(() => renderEmpty());
})();

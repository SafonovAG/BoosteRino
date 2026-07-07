(function () {
  const { api, toast } = window.Boosterino;
  const root = document.getElementById('order-status-page');
  if (!root) return;

  const orderId = +root.dataset.orderId;
  if (!orderId) {
    root.innerHTML = '<div class="card"><p>Заказ не найден</p><a href="/cabinet" class="btn btn-secondary">В кабинет</a></div>';
    return;
  }

  let refreshTimer = null;

  function escape(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
  }

  function fmtRub(n) {
    const v = Math.round(Number(n) * 100) / 100;
    return v.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) + ' ₽';
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
    if (s.includes('complet')) return 'order-status--ok';
    if (s.includes('progress') || s.includes('await') || s.includes('partial')) return 'order-status--warn';
    if (s.includes('cancel') || s.includes('fail') || s.includes('error')) return 'order-status--bad';
    return 'order-status--pending';
  }

  function isActive(status) {
    const s = String(status || '').toLowerCase();
    return s.includes('progress') || s.includes('await') || s.includes('partial') || s.includes('pending');
  }

  function statusHint(status) {
    const s = String(status || '').toLowerCase();
    if (s.includes('complet')) return 'Заказ выполнен поставщиком.';
    if (s.includes('progress')) return 'Услуга выполняется. Данные обновляются автоматически.';
    if (s.includes('partial')) return 'Выполнена часть заказа. Остаток ещё в работе.';
    if (s.includes('await')) return 'Заказ принят поставщиком и ожидает запуска.';
    if (s.includes('cancel')) return 'Заказ отменён.';
    if (s.includes('pending_payment')) return 'Ожидается оплата через ЮMoney.';
    if (s.includes('pending')) return 'Заказ создан и обрабатывается.';
    return 'Статус обновляется по данным поставщика.';
  }

  function render(o) {
    const prog = o.progress;
    const showProgress = prog && prog.percent != null;

    root.innerHTML =
      '<div class="order-status-layout">' +
        '<header class="order-status-head card">' +
          '<div class="order-status-head-top">' +
            '<div>' +
              '<span class="order-status-kicker">Заказ #' + o.id + '</span>' +
              '<h1 class="order-status-title">' + escape(o.service_name) + '</h1>' +
            '</div>' +
            '<span class="order-status-badge ' + statusClass(o.status) + '">' + escape(o.status) + '</span>' +
          '</div>' +
          '<p class="order-status-hint muted">' + escape(statusHint(o.status)) + '</p>' +
          (showProgress
            ? '<div class="order-progress">' +
                '<div class="order-progress-labels">' +
                  '<span>Выполнено</span>' +
                  '<strong>' + prog.done + ' / ' + prog.total + ' (' + prog.percent + '%)</strong>' +
                '</div>' +
                '<div class="order-progress-bar"><div class="order-progress-fill" style="width:' + prog.percent + '%"></div></div>' +
                '<p class="order-progress-remains muted">Осталось у поставщика: <strong>' + prog.remains + '</strong></p>' +
              '</div>'
            : (o.supplier_synced
              ? '<p class="muted order-progress-placeholder">Прогресс появится, когда поставщик начнёт выполнение.</p>'
              : '<p class="muted order-progress-placeholder">Заказ передан в обработку.</p>')) +
          '<div class="order-status-actions">' +
            '<button type="button" class="btn btn-secondary btn-sm" id="order-refresh">Обновить статус</button>' +
            (o.service_refill ? '<button type="button" class="btn btn-secondary btn-sm" id="order-refill">Рефилл</button>' : '') +
            (o.service_cancel ? '<button type="button" class="btn btn-danger btn-sm" id="order-cancel">Отменить</button>' : '') +
          '</div>' +
        '</header>' +

        '<div class="order-status-grid">' +
          '<section class="card order-status-panel">' +
            '<h2>Детали заказа</h2>' +
            '<dl class="order-dl">' +
              '<div><dt>Услуга</dt><dd>' + escape(o.service_name) + '</dd></div>' +
              '<div><dt>Категория</dt><dd>' + escape(o.service_category || '—') + '</dd></div>' +
              '<div><dt>Количество</dt><dd>' + o.quantity + '</dd></div>' +
              '<div><dt>Сумма</dt><dd>' + fmtRub(o.cost_rub) + '</dd></div>' +
              '<div><dt>Оплата</dt><dd>' + escape(o.payment_method === 'balance' ? 'С баланса' : 'ЮMoney') + '</dd></div>' +
              '<div><dt>Создан</dt><dd>' + fmtDate(o.created_at) + '</dd></div>' +
              '<div><dt>Обновлён</dt><dd>' + fmtDate(o.updated_at) + '</dd></div>' +
            '</dl>' +
          '</section>' +

          '<section class="card order-status-panel">' +
            '<h2>Прогресс у поставщика</h2>' +
            (o.supplier_synced
              ? '<dl class="order-dl">' +
                  '<div><dt>ID поставщика</dt><dd>' + o.twiboost_order_id + '</dd></div>' +
                  '<div><dt>Стартовое значение</dt><dd>' + (o.start_count ?? '—') + '</dd></div>' +
                  '<div><dt>Осталось</dt><dd>' + (o.remains ?? '—') + '</dd></div>' +
                  '<div><dt>Списание</dt><dd>' + (o.charge ?? '—') + '</dd></div>' +
                '</dl>'
              : '<p class="muted">Заказ ещё не отправлен поставщику или ожидает оплаты.</p>') +
          '</section>' +

          '<section class="card order-status-panel order-status-panel--wide">' +
            '<h2>Ссылка</h2>' +
            '<a href="' + escape(o.link) + '" target="_blank" rel="noopener" class="order-status-link">' + escape(o.link) + '</a>' +
          '</section>' +
        '</div>' +

        '<div class="order-status-foot">' +
          '<a href="/cabinet" class="btn btn-secondary">← Мой кабинет</a>' +
          '<a href="/services" class="btn btn-ghost">Каталог</a>' +
        '</div>' +
      '</div>';

    document.getElementById('order-refresh')?.addEventListener('click', () => load(true));
    document.getElementById('order-refill')?.addEventListener('click', async () => {
      try {
        await api('/api/v1/user/orders/' + orderId + '/refill', { method: 'POST', body: '{}' });
        toast('Рефилл запрошен');
        load(true);
      } catch (e) {
        toast(e.message, 'error');
      }
    });
    document.getElementById('order-cancel')?.addEventListener('click', async () => {
      if (!confirm('Отменить заказ?')) return;
      try {
        await api('/api/v1/user/orders/' + orderId + '/cancel', { method: 'POST', body: '{}' });
        toast('Запрос на отмену отправлен');
        load(true);
      } catch (e) {
        toast(e.message, 'error');
      }
    });

    scheduleRefresh(o.status);
  }

  function scheduleRefresh(status) {
    if (refreshTimer) clearInterval(refreshTimer);
    if (!isActive(status)) return;
    refreshTimer = setInterval(() => load(false), 30000);
  }

  async function load(manual) {
    if (manual) root.classList.add('is-refreshing');
    try {
      const data = await api('/api/v1/user/orders/' + orderId);
      if (!data.order) throw new Error('Не найден');
      render(data.order);
    } catch (e) {
      root.innerHTML =
        '<div class="card order-status-error">' +
          '<p>' + escape(e.message) + '</p>' +
          '<a href="/cabinet" class="btn btn-secondary">В кабинет</a>' +
        '</div>';
    } finally {
      root.classList.remove('is-refreshing');
    }
  }

  load(false);
})();

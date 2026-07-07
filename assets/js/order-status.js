(function () {
  const { api, toast } = window.Boosterino;
  const root = document.getElementById('order-status-page');
  if (!root) return;

  const orderId = +root.dataset.orderId;
  const RING_R = 54;
  const RING_C = 2 * Math.PI * RING_R;
  const POLL_MS = 12000;

  let refreshTimer = null;
  let lastSynced = null;

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

  function statusLabel(o) {
    return o.status_label || o.status || '—';
  }

  function statusClass(status) {
    const s = String(status || '').toLowerCase();
    if (s.includes('complet')) return 'order-status--ok';
    if (s.includes('progress') || s.includes('await') || s.includes('partial')) return 'order-status--warn';
    if (s.includes('cancel') || s.includes('fail') || s.includes('error')) return 'order-status--bad';
    return 'order-status--pending';
  }

  function statusHint(o) {
    const s = String(o.status || '').toLowerCase();
    if (s.includes('complet')) return 'Заказ полностью выполнен поставщиком.';
    if (s.includes('progress')) return 'Услуга выполняется. Данные синхронизируются с поставщиком в реальном времени.';
    if (s.includes('partial')) return 'Часть заказа уже доставлена, остаток в работе.';
    if (s.includes('await')) return 'Заказ принят поставщиком и скоро начнёт выполняться.';
    if (s.includes('cancel')) return 'Заказ отменён.';
    if (o.status === 'pending_payment') return 'Ожидается оплата через ЮMoney.';
    if (o.status === 'pending') return 'Заказ создан и передаётся поставщику.';
    return 'Статус обновляется автоматически.';
  }

  function ringOffset(percent) {
    const p = Math.max(0, Math.min(100, Number(percent) || 0));
    return RING_C * (1 - p / 100);
  }

  function progressPercent(o) {
    if (o.progress?.percent != null) return o.progress.percent;
    const s = String(o.status || '').toLowerCase();
    if (s.includes('complet')) return 100;
    if (o.status_active || s.includes('progress') || s.includes('partial')) return null;
    return 0;
  }

  function renderRing(o) {
    const pct = progressPercent(o);
    const isLive = o.status_active && o.supplier_synced;
    const indeterminate = pct === null && isLive;
    const displayPct = pct === null ? '…' : Math.round(pct);
    const offset = pct === null ? RING_C * 0.75 : ringOffset(pct);
    const logo = o.service_logo || '/assets/images/logo/default.svg';

    return (
      '<div class="order-hero-ring' + (indeterminate ? ' is-live' : '') + '">' +
        '<svg class="order-hero-ring-svg" viewBox="0 0 120 120" aria-hidden="true">' +
          '<circle class="order-hero-ring-track" cx="60" cy="60" r="' + RING_R + '" />' +
          '<circle class="order-hero-ring-progress ' + statusClass(o.status) + '" cx="60" cy="60" r="' + RING_R + '"' +
            ' stroke-dasharray="' + RING_C.toFixed(3) + '"' +
            ' stroke-dashoffset="' + offset.toFixed(3) + '" />' +
        '</svg>' +
        '<div class="order-hero-ring-center">' +
          '<img src="' + escape(logo) + '" alt="" width="56" height="56">' +
        '</div>' +
        '<span class="order-hero-ring-pct">' + displayPct + (pct === null ? '' : '%') + '</span>' +
      '</div>'
    );
  }

  function render(o) {
    const prog = o.progress;
    const label = statusLabel(o);
    lastSynced = o.synced_at || o.updated_at || new Date().toISOString();

    root.innerHTML =
      '<div class="order-v2">' +
        '<section class="order-v2-hero card">' +
          '<div class="order-v2-hero-grid">' +
            '<div class="order-v2-hero-visual">' + renderRing(o) + '</div>' +
            '<div class="order-v2-hero-info">' +
              '<span class="order-v2-kicker">Заказ #' + o.id + '</span>' +
              '<h1 class="order-v2-title">' + escape(o.service_name) + '</h1>' +
              '<span class="order-status-badge ' + statusClass(o.status) + '">' + escape(label) + '</span>' +
              '<p class="order-v2-hint">' + escape(statusHint(o)) + '</p>' +
              (prog
                ? '<p class="order-v2-progress-text"><strong>' + prog.done + '</strong> из <strong>' + prog.total + '</strong> выполнено' +
                  (prog.remains != null ? ' · осталось <strong>' + prog.remains + '</strong>' : '') +
                  '</p>'
                : '') +
              '<p class="order-v2-sync muted" id="order-sync-label">Данные поставщика · обновлено ' + fmtDate(lastSynced) + '</p>' +
            '</div>' +
          '</div>' +
          '<div class="order-v2-metrics">' +
            '<div class="order-v2-metric"><span>Количество</span><strong>' + o.quantity + '</strong></div>' +
            '<div class="order-v2-metric"><span>Сумма</span><strong>' + fmtRub(o.cost_rub) + '</strong></div>' +
            '<div class="order-v2-metric"><span>Оплата</span><strong>' + escape(o.payment_method === 'balance' ? 'Баланс' : 'ЮMoney') + '</strong></div>' +
            '<div class="order-v2-metric"><span>Осталось</span><strong>' + (o.remains ?? (prog ? prog.remains : '—')) + '</strong></div>' +
          '</div>' +
        '</section>' +

        '<div class="order-v2-body">' +
          '<section class="card order-v2-panel">' +
            '<h2>Ссылка на объект</h2>' +
            '<a href="' + escape(o.link) + '" target="_blank" rel="noopener" class="order-v2-link">' + escape(o.link) + '</a>' +
          '</section>' +

          '<section class="card order-v2-panel">' +
            '<h2>Детали услуги</h2>' +
            '<ul class="order-v2-facts">' +
              '<li><span>Категория</span><strong>' + escape(o.service_category || '—') + '</strong></li>' +
              '<li><span>Тип</span><strong>' + escape(o.service_type || '—') + '</strong></li>' +
              '<li><span>Создан</span><strong>' + fmtDate(o.created_at) + '</strong></li>' +
              '<li><span>Обновлён</span><strong>' + fmtDate(o.updated_at) + '</strong></li>' +
            '</ul>' +
          '</section>' +

          '<section class="card order-v2-panel order-v2-panel--supplier">' +
            '<div class="order-v2-panel-head">' +
              '<h2>Данные поставщика</h2>' +
              (o.supplier_synced ? '<span class="order-v2-live-badge">● LIVE</span>' : '') +
            '</div>' +
            (o.supplier_synced
              ? '<ul class="order-v2-facts order-v2-facts--cols">' +
                  '<li><span>ID у поставщика</span><strong>' + o.twiboost_order_id + '</strong></li>' +
                  '<li><span>Стартовое значение</span><strong>' + (o.start_count ?? '—') + '</strong></li>' +
                  '<li><span>Осталось</span><strong>' + (o.remains ?? '—') + '</strong></li>' +
                  '<li><span>Списание</span><strong>' + (o.charge ?? '—') + '</strong></li>' +
                '</ul>'
              : '<p class="muted">Заказ ещё не отправлен поставщику или ожидает оплаты.</p>') +
          '</section>' +
        '</div>' +

        '<footer class="order-v2-footer">' +
          '<div class="order-v2-actions">' +
            '<button type="button" class="btn btn-primary btn-sm" id="order-refresh">Обновить сейчас</button>' +
            (o.service_refill ? '<button type="button" class="btn btn-secondary btn-sm" id="order-refill">Рефилл</button>' : '') +
            (o.service_cancel ? '<button type="button" class="btn btn-danger btn-sm" id="order-cancel">Отменить</button>' : '') +
          '</div>' +
          '<div class="order-v2-nav">' +
            '<a href="/cabinet" class="btn btn-secondary">← Кабинет</a>' +
            '<a href="/services" class="btn btn-ghost">Каталог</a>' +
          '</div>' +
        '</footer>' +
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

    scheduleRefresh(o);
  }

  function scheduleRefresh(o) {
    if (refreshTimer) clearInterval(refreshTimer);
    if (!o.status_active && !o.supplier_synced) return;
    if (!o.status_active && String(o.status || '').toLowerCase().includes('complet')) return;
    refreshTimer = setInterval(() => load(false), POLL_MS);
  }

  async function load(manual) {
    if (manual) root.classList.add('is-refreshing');
    try {
      const url = manual
        ? '/api/v1/user/orders/' + orderId + '/sync'
        : '/api/v1/user/orders/' + orderId;
      const opts = manual ? { method: 'POST', body: '{}' } : {};
      const data = await api(url, opts);
      if (!data.order) throw new Error('Не найден');
      render(data.order);
    } catch (e) {
      if (!root.querySelector('.order-v2')) {
        root.innerHTML =
          '<div class="card order-status-error">' +
            '<p>' + escape(e.message) + '</p>' +
            '<a href="/cabinet" class="btn btn-secondary">В кабинет</a>' +
          '</div>';
      } else {
        toast(e.message, 'error');
      }
    } finally {
      root.classList.remove('is-refreshing');
    }
  }

  if (!orderId) {
    root.innerHTML = '<div class="card"><p>Заказ не найден</p><a href="/cabinet" class="btn btn-secondary">В кабинет</a></div>';
    return;
  }

  load(false);
})();

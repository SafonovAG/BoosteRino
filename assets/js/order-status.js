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
    if (s.includes('complet')) return 'Заказ полностью выполнен.';
    if (s.includes('progress')) return 'Услуга выполняется. Статус обновляется автоматически.';
    if (s.includes('partial')) return 'Часть заказа уже доставлена, остаток в работе.';
    if (s.includes('await')) return 'Заказ принят и скоро начнёт выполняться.';
    if (s.includes('cancel')) return 'Заказ отменён.';
    if (o.status === 'pending_payment') return 'Ожидается оплата через ЮMoney.';
    if (o.status === 'pending') return 'Заказ создан и ожидает обработки.';
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
    const isLive = o.status_active;
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
    const d = o.delivery || {};
    const unit = d.unit || o.quantity_unit || 'единиц';
    const orderNum = o.order_number || o.id;
    const cat = o.service_category;
    const catHtml = cat
      ? '<a href="/services?category=' + encodeURIComponent(cat) + '" class="order-v2-category-link">' + escape(cat) + '</a>'
      : '—';
    lastSynced = o.synced_at || o.updated_at || new Date().toISOString();

    const breadcrumb = document.getElementById('order-breadcrumb-label');
    if (breadcrumb) breadcrumb.textContent = 'Заказ №' + orderNum;

    root.innerHTML =
      '<div class="order-v2">' +
        '<section class="order-v2-hero card">' +
          '<div class="order-v2-hero-grid">' +
            '<div class="order-v2-hero-visual">' + renderRing(o) + '</div>' +
            '<div class="order-v2-hero-info">' +
              '<span class="order-v2-kicker">Заказ №' + orderNum + '</span>' +
              '<h1 class="order-v2-title">' + escape(o.service_name) + '</h1>' +
              '<span class="order-status-badge ' + statusClass(o.status) + '">' + escape(label) + '</span>' +
              '<p class="order-v2-hint">' + escape(statusHint(o)) + '</p>' +
              (prog
                ? '<p class="order-v2-progress-text">Доставлено <strong>' + prog.done + '</strong> из <strong>' + prog.total + '</strong> ' + unit +
                  (prog.remains != null ? ' · осталось <strong>' + prog.remains + '</strong>' : '') +
                  '</p>'
                : '') +
              (o.status_active
                ? '<p class="order-v2-sync muted" id="order-sync-label">Обновлено ' + fmtDate(lastSynced) + '</p>'
                : '') +
            '</div>' +
          '</div>' +
          '<div class="order-v2-metrics">' +
            '<div class="order-v2-metric"><span>Заказано</span><strong>' + (d.ordered ?? o.quantity) + ' ' + unit + '</strong></div>' +
            '<div class="order-v2-metric"><span>Доставлено</span><strong>' + (d.delivered ?? '—') + '</strong></div>' +
            '<div class="order-v2-metric"><span>Оплачено</span><strong>' + fmtRub(o.cost_rub) + '</strong></div>' +
            '<div class="order-v2-metric"><span>Осталось</span><strong>' + (d.remains ?? '—') + '</strong></div>' +
          '</div>' +
        '</section>' +

        '<div class="order-v2-body">' +
          '<section class="card order-v2-panel">' +
            '<h2>Ссылка на объект</h2>' +
            '<a href="' + escape(o.link) + '" target="_blank" rel="noopener" class="order-v2-link">' + escape(o.link) + '</a>' +
          '</section>' +

          '<section class="card order-v2-panel">' +
            '<h2>Детали заказа</h2>' +
            '<ul class="order-v2-facts">' +
              '<li><span>Категория</span><strong>' + catHtml + '</strong></li>' +
              '<li><span>Способ оплаты</span><strong>' + escape(o.payment_method === 'balance' ? 'С баланса' : 'ЮMoney') + '</strong></li>' +
              '<li><span>Создан</span><strong>' + escape(o.created_at_formatted || fmtDate(o.created_at)) + '</strong></li>' +
              '<li><span>Обновлён</span><strong>' + escape(o.updated_at_formatted || fmtDate(o.updated_at)) + '</strong></li>' +
            '</ul>' +
          '</section>' +
        '</div>' +

        '<footer class="order-v2-footer">' +
          '<div class="order-v2-actions">' +
            '<button type="button" class="btn btn-primary btn-sm" id="order-refresh">Обновить сейчас</button>' +
            (o.service_refill ? '<button type="button" class="btn btn-secondary btn-sm" id="order-refill">Рефилл</button>' : '') +
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

    scheduleRefresh(o);
  }

  function scheduleRefresh(o) {
    if (refreshTimer) clearInterval(refreshTimer);
    if (!o.status_active) return;
    if (String(o.status || '').toLowerCase().includes('complet')) return;
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

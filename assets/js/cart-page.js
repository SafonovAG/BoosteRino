(function () {
  const { api, toast } = window.Boosterino;
  const cart = window.BoosterinoCart;
  const root = document.getElementById('cart-page');
  if (!root || !cart) return;

  const fmt = window.BoosterinoProductCard?.formatPrice || ((n) => n + ' ₽');
  const fmtQty = window.BoosterinoProductCard?.formatQty || ((n) => String(n));
  const escape = window.BoosterinoProductCard?.escapeHtml || ((s) => s);
  const isLoggedIn = !!document.querySelector('.balance-pill');

  const Q = () => window.BoosterinoQty || {
    snap: (q, min, max) => Math.max(min, Math.min(max, +q)),
    step: (q, d, min, max) => Math.max(min, Math.min(max, (+q || min) + d)),
    calcPriceActual: (price, q) => (price / 1000) * q,
    canDecrease: (q, min) => q > min,
    canIncrease: (q, max) => q < max,
  };

  let shellReady = false;
  let checkoutBusy = false;

  function renderEmpty() {
    shellReady = false;
    root.innerHTML =
      '<div class="cart-pro-empty">' +
        '<div class="cart-pro-empty-icon">🛒</div>' +
        '<h2>Корзина пуста</h2>' +
        '<p class="muted">Выберите услуги в каталоге и добавьте их сюда</p>' +
        '<a href="/services" class="btn btn-primary">Открыть каталог</a>' +
      '</div>';
  }

  function ensureShell() {
    if (shellReady) return;
    root.innerHTML =
      '<div class="cart-pro">' +
        '<div class="cart-pro-items" id="cart-pro-items"></div>' +
        '<aside class="cart-pro-summary" id="cart-pro-summary">' +
          '<h2>Ваш заказ</h2>' +
          '<div class="cart-pro-summary-rows">' +
            '<div class="cart-pro-summary-row"><span>Позиций</span><strong id="cart-pos-count">0</strong></div>' +
            '<div class="cart-pro-summary-row"><span>Единиц</span><strong id="cart-units-count">0</strong></div>' +
          '</div>' +
          '<div class="cart-pro-summary-total"><span>Итого</span><strong id="cart-grand-total">0 ₽</strong></div>' +
          '<div id="cart-checkout-block"></div>' +
          '<a href="/services" class="btn btn-ghost btn-block">Продолжить покупки</a>' +
          '<div class="cart-pro-progress" id="cart-progress" hidden><div class="cart-pro-progress-bar" id="cart-progress-bar"></div></div>' +
        '</aside>' +
      '</div>';
    shellReady = true;
    bindRootEvents();
    updateCheckoutBlock();
  }

  function updateCheckoutBlock() {
    const block = document.getElementById('cart-checkout-block');
    if (!block) return;
    block.innerHTML = isLoggedIn
      ? '<label>Способ оплаты<select id="cart-payment"><option value="balance">С баланса</option><option value="yoomoney">ЮMoney</option></select></label>' +
        '<button type="button" class="btn btn-primary btn-block btn-lg" id="cart-checkout">Оформить заказ</button>'
      : '<p class="muted">Войдите, чтобы оформить заказ</p>' +
        '<a href="/login?next=/cart" class="btn btn-primary btn-block">Войти</a>' +
        '<a href="/register" class="btn btn-secondary btn-block">Регистрация</a>';
    document.getElementById('cart-checkout')?.addEventListener('click', checkout);
  }

  function validateItemLink(item, raw, inputEl) {
    const v = window.BoosterinoLinkValidator;
    if (!v) return raw.trim();
    const r = v.validate(raw, item.platform, item.service_type, item.platform_name, item.name, item.category_label);
    if (!r.ok) {
      inputEl?.classList.add('is-invalid');
      return null;
    }
    inputEl?.classList.remove('is-invalid');
    return r.normalized || raw.trim();
  }

  function itemHtml(item, isNew) {
    const total = cart.lineTotal(item);
    const unit = item.delivery_unit || 'ед.';
    const linkLabel = item.link_label || window.BoosterinoLinkValidator?.hint(
      item.platform, item.service_type, item.platform_name, item.name, item.category_label
    )?.label || 'Ссылка';
    const linkPh = item.link_placeholder || 'https://...';
    return '<article class="cart-pro-item' + (isNew ? ' is-entering' : '') + '" data-service-id="' + item.service_id + '">' +
      '<a href="/services/' + item.service_id + '" class="cart-pro-item-logo">' +
        '<img src="' + escape(item.logo) + '" alt="" width="32" height="32">' +
      '</a>' +
      '<div class="cart-pro-item-main">' +
        '<div class="cart-pro-item-head">' +
          '<h3 class="cart-pro-item-title"><a href="/services/' + item.service_id + '">' + escape(item.name) + '</a></h3>' +
          '<span class="cart-pro-item-cat">' + escape(item.category_label || item.platform_name) + '</span>' +
        '</div>' +
        '<label class="cart-pro-item-link">' +
          '<span>' + escape(linkLabel) + '</span>' +
          '<input type="url" class="cart-pro-item-link-input" value="' + escape(item.link) + '" placeholder="' + escape(linkPh) + '">' +
        '</label>' +
        '<div class="cart-pro-item-controls">' +
          '<div class="cart-pro-qty-block">' +
            '<span class="cart-pro-qty-label">Сколько получите <span class="muted">(' + escape(unit) + ')</span></span>' +
            '<div class="cart-pro-stepper">' +
              '<button type="button" class="cart-qty-minus" aria-label="Меньше">−</button>' +
              '<input type="number" class="cart-pro-item-qty" min="' + item.min + '" max="' + item.max + '" step="1" value="' + item.quantity + '">' +
              '<button type="button" class="cart-qty-plus" aria-label="Больше">+</button>' +
            '</div>' +
          '</div>' +
          '<div class="cart-pro-item-side">' +
            '<div class="cart-pro-item-rate">' + fmt(item.price_per_thousand_rub) + ' · ' + escape(item.price_unit_label || 'за 1000') + '</div>' +
            '<div class="cart-pro-item-total">' + fmt(total) + '</div>' +
          '</div>' +
        '</div>' +
      '</div>' +
      '<button type="button" class="cart-pro-item-remove" title="Удалить" aria-label="Удалить">×</button>' +
    '</article>';
  }

  function updateTotals(animate) {
    const posEl = document.getElementById('cart-pos-count');
    const unitsEl = document.getElementById('cart-units-count');
    const grandEl = document.getElementById('cart-grand-total');
    if (posEl) posEl.textContent = String(cart.getItems().length);
    if (unitsEl) unitsEl.textContent = fmtQty(cart.totalUnits());
    if (grandEl) {
      grandEl.textContent = fmt(cart.total());
      if (animate) {
        grandEl.classList.add('is-pulse');
        setTimeout(() => grandEl.classList.remove('is-pulse'), 300);
      }
    }
  }

  function updateItemRow(row, item, animatePrice) {
    const qtyInput = row.querySelector('.cart-pro-item-qty');
    if (qtyInput && document.activeElement !== qtyInput) {
      qtyInput.value = item.quantity;
    }
    const linkInput = row.querySelector('.cart-pro-item-link-input');
    if (linkInput && document.activeElement !== linkInput) {
      linkInput.value = item.link;
    }
    const totalEl = row.querySelector('.cart-pro-item-total');
    if (totalEl) {
      totalEl.textContent = fmt(cart.lineTotal(item));
      if (animatePrice) {
        totalEl.classList.add('is-pulse');
        setTimeout(() => totalEl.classList.remove('is-pulse'), 300);
      }
    }
    const minus = row.querySelector('.cart-qty-minus');
    const plus = row.querySelector('.cart-qty-plus');
    if (minus) minus.disabled = !Q().canDecrease(item.quantity, item.min);
    if (plus) plus.disabled = !Q().canIncrease(item.quantity, item.max);
  }

  function syncItems(animate) {
    const list = document.getElementById('cart-pro-items');
    if (!list) return;
    const items = cart.getItems();
    const existing = new Map();
    list.querySelectorAll('.cart-pro-item').forEach((row) => {
      existing.set(+row.dataset.serviceId, row);
    });

    items.forEach((item) => {
      const sid = item.service_id;
      if (existing.has(sid)) {
        updateItemRow(existing.get(sid), item, animate);
        existing.delete(sid);
      } else {
        list.insertAdjacentHTML('beforeend', itemHtml(item, true));
      }
    });

    existing.forEach((row) => {
      row.classList.add('is-leaving');
      setTimeout(() => row.remove(), 250);
    });

    updateTotals(animate);
  }

  function render(isNew) {
    const items = cart.getItems();
    if (!items.length) {
      renderEmpty();
      return;
    }
    ensureShell();
    if (!document.getElementById('cart-pro-items').children.length) {
      document.getElementById('cart-pro-items').innerHTML = items.map((i) => itemHtml(i, isNew)).join('');
      updateTotals(false);
    } else {
      syncItems(isNew);
    }
  }

  function bindRootEvents() {
    root.addEventListener('click', (e) => {
      const row = e.target.closest('.cart-pro-item');
      if (!row) return;
      const sid = +row.dataset.serviceId;
      const item = cart.getItems().find((i) => i.service_id === sid);
      if (!item) return;

      if (e.target.closest('.cart-pro-item-remove')) {
        row.classList.add('is-leaving');
        setTimeout(() => {
          cart.remove(sid);
        }, 200);
        return;
      }

      if (e.target.closest('.cart-qty-minus')) {
        cart.update(sid, { quantity: Q().step(item.quantity, -1, item.min, item.max) });
        return;
      }

      if (e.target.closest('.cart-qty-plus')) {
        cart.update(sid, { quantity: Q().step(item.quantity, 1, item.min, item.max) });
      }
    });

    root.addEventListener('input', (e) => {
      const row = e.target.closest('.cart-pro-item');
      if (!row) return;
      const sid = +row.dataset.serviceId;
      const item = cart.getItems().find((i) => i.service_id === sid);
      if (!item) return;

      if (e.target.classList.contains('cart-pro-item-qty')) {
        cart.update(sid, { quantity: Q().snap(+e.target.value, item.min, item.max) });
      }
    });

    root.addEventListener('change', (e) => {
      const row = e.target.closest('.cart-pro-item');
      if (!row) return;
      const sid = +row.dataset.serviceId;
      const item = cart.getItems().find((i) => i.service_id === sid);
      if (!item) return;

      if (e.target.classList.contains('cart-pro-item-link-input')) {
        const val = e.target.value.trim();
        const normalized = validateItemLink(item, val, e.target);
        if (normalized === null) return;
        e.target.classList.toggle('is-invalid', !val);
        cart.update(sid, { link: normalized || val });
      }

      if (e.target.classList.contains('cart-pro-item-qty')) {
        cart.update(sid, { quantity: Q().snap(+e.target.value, item.min, item.max) });
      }
    });
  }

  function setProgress(pct) {
    const wrap = document.getElementById('cart-progress');
    const bar = document.getElementById('cart-progress-bar');
    if (!wrap || !bar) return;
    wrap.hidden = pct <= 0;
    bar.style.width = pct + '%';
  }

  async function checkout() {
    if (checkoutBusy) return;
    const items = cart.getItems();
    const payment = document.getElementById('cart-payment')?.value || 'balance';

    for (const item of items) {
      if (!item.link) {
        toast('Укажите ссылку для: ' + item.name, 'error');
        const row = root.querySelector('[data-service-id="' + item.service_id + '"] .cart-pro-item-link-input');
        row?.classList.add('is-invalid');
        row?.focus();
        return;
      }
      const row = root.querySelector('[data-service-id="' + item.service_id + '"] .cart-pro-item-link-input');
      const normalized = validateItemLink(item, item.link, row);
      if (normalized === null) {
        toast('Неверная ссылка для: ' + item.name, 'error');
        row?.focus();
        return;
      }
      if (normalized !== item.link) {
        cart.update(item.service_id, { link: normalized });
      }
    }

    if (payment === 'yoomoney' && items.length > 1) {
      toast('ЮMoney: оформляйте по одному товару или выберите оплату с баланса', 'error');
      return;
    }

    const btn = document.getElementById('cart-checkout');
    checkoutBusy = true;
    if (btn) btn.disabled = true;

    try {
      for (let i = 0; i < items.length; i++) {
        setProgress(((i) / items.length) * 100);
        const item = items[i];
        const result = await api('/api/v1/user/orders', {
          method: 'POST',
          body: JSON.stringify({
            service_id: item.service_id,
            link: item.link,
            quantity: item.quantity,
            payment_method: payment,
          }),
        });

        if (result.payment_url) {
          cart.remove(item.service_id);
          location.href = result.payment_url;
          return;
        }
      }

      setProgress(100);
      cart.clear();
      toast('Заказы оформлены');
      location.href = '/cabinet';
    } catch (e) {
      toast(e.message, 'error');
      setProgress(0);
      checkoutBusy = false;
      if (btn) btn.disabled = false;
    }
  }

  let lastCount = cart.getItems().length;
  window.addEventListener('cart:updated', () => {
    const isNew = cart.getItems().length > lastCount;
    lastCount = cart.getItems().length;
    render(isNew);
  });

  render(false);
})();

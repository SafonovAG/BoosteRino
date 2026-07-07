(function () {
  const { api, toast } = window.Boosterino;
  const root = document.getElementById('product-page');
  if (!root) return;

  const serviceId = +root.dataset.serviceId;
  const fmt = window.BoosterinoProductCard?.formatPrice || ((n) => n + ' ₽');
  const fmtQty = window.BoosterinoProductCard?.formatQty || ((n) => String(n));
  const parseUnit = window.BoosterinoProductCard?.parseDeliveryUnit || (() => 'единиц');
  const escape = window.BoosterinoProductCard?.escapeHtml || ((s) => s);

  if (!serviceId) {
    root.innerHTML = '<div class="card"><p>Товар не найден</p><a href="/services" class="btn btn-secondary">В каталог</a></div>';
    return;
  }

  let service = null;
  const Q = () => window.BoosterinoQty || { PACK: 1000, snap: (q, min, max) => q, step: (q, d, min, max) => q + d, calcPrice: (p, q) => (p / 1000) * q, canDecrease: (q, min) => q > min, canIncrease: (q, max) => q < max, labelSuffix: () => '' };

  function clampQty(raw) {
    if (!service) return 0;
    return Q().snap(raw, service.min, service.max);
  }

  function calcPrice(qty) {
    if (!service) return 0;
    return Q().calcPrice(service.price_per_thousand_rub, qty);
  }

  function getQty() {
    const el = document.getElementById('product-quantity');
    return clampQty(parseInt(el?.value || service.min, 10) || service.min);
  }

  function setQty(qty) {
    const el = document.getElementById('product-quantity');
    const v = clampQty(qty);
    if (el) el.value = v;
    updateUI();
  }

  function updateUI() {
    const qty = getQty();
    const unit = parseUnit(service.name);
    const deliveryEl = document.getElementById('product-delivery-value');
    const priceEl = document.getElementById('product-total-price');
    const minusBtn = document.getElementById('product-qty-minus');
    const plusBtn = document.getElementById('product-qty-plus');

    if (deliveryEl) {
      deliveryEl.innerHTML = 'Вы получите: <em>' + fmtQty(qty) + '</em> ' + escape(unit);
    }
    if (priceEl) priceEl.textContent = fmt(calcPrice(qty));
    if (minusBtn) minusBtn.disabled = !Q().canDecrease(qty, service.min);
    if (plusBtn) plusBtn.disabled = !Q().canIncrease(qty, service.max);
  }

  function renderPage() {
    const s = service;
    const breadcrumb = document.getElementById('product-breadcrumb');
    if (breadcrumb) breadcrumb.textContent = s.name;
    document.title = s.name + ' - Boosterino';

    const badges = [];
    if (s.refill) badges.push('<span class="badge badge-refill">Рефилл</span>');
    if (s.cancel) badges.push('<span class="badge badge-cancel">Отмена</span>');
    const unit = parseUnit(s.name);
    const label = s.category_label || s.platform_name || s.category || '';
    const linkLabel = s.link_label || 'Ссылка на профиль или пост';
    const linkPlaceholder = s.link_placeholder || s.link_example || 'https://...';

    root.innerHTML =
      '<div class="product-pro">' +
        '<aside class="product-pro-hero card">' +
          '<div class="product-pro-logo-wrap">' +
            '<img src="' + escape(s.logo) + '" alt="" width="64" height="64">' +
          '</div>' +
          '<span class="product-pro-platform">' + escape(label) + '</span>' +
          (badges.length ? '<div class="product-pro-badges">' + badges.join('') + '</div>' : '') +
        '</aside>' +
        '<div class="product-pro-main">' +
          '<h1 class="product-pro-title">' + escape(s.name) + '</h1>' +
          '<div class="product-pro-rate">' +
            '<strong>' + fmt(s.price_per_thousand_rub) + '</strong>' +
            '<span>за 1000 ' + escape(unit) + '</span>' +
          '</div>' +
          '<div class="product-pro-specs">' +
            '<div class="product-pro-spec"><label>Минимум</label><strong>' + fmtQty(s.min) + '</strong></div>' +
            '<div class="product-pro-spec"><label>Максимум</label><strong>' + fmtQty(s.max) + '</strong></div>' +
            '<div class="product-pro-spec"><label>Платформа</label><strong>' + escape(s.platform_name || '—') + '</strong></div>' +
          '</div>' +
          '<form id="product-add-form" class="product-pro-form">' +
            '<div class="product-pro-field">' +
              '<label for="product-link">' + escape(linkLabel) + '</label>' +
              '<input type="url" name="link" id="product-link" required placeholder="' + escape(linkPlaceholder) + '">' +
        '<p class="product-link-hint muted" id="product-link-hint">Пример: ' + escape(linkPlaceholder) + '</p>' +
            '</div>' +
            '<div class="product-pro-qty-row">' +
              '<div class="product-pro-field">' +
                '<label for="product-quantity">Количество' + escape(Q().labelSuffix()) + '</label>' +
                '<div class="product-pro-stepper">' +
                  '<button type="button" id="product-qty-minus" aria-label="Уменьшить на 1000">−</button>' +
                  '<input type="number" name="quantity" id="product-quantity" min="' + s.min + '" max="' + s.max + '" step="' + Q().PACK + '" value="' + s.min + '" required>' +
                  '<button type="button" id="product-qty-plus" aria-label="Увеличить на 1000">+</button>' +
                '</div>' +
                '<p class="muted product-qty-note">1 нажатие ± = 1000 ед. · цена указана за 1000</p>' +
              '</div>' +
              '<div class="product-pro-delivery">' +
                '<div class="product-pro-delivery-label">Результат заказа</div>' +
                '<div class="product-pro-delivery-value" id="product-delivery-value">' +
                  'Вы получите: <em>' + fmtQty(s.min) + '</em> ' + escape(unit) +
                '</div>' +
              '</div>' +
            '</div>' +
            '<div class="product-pro-summary">' +
              '<span>Итого к оплате</span>' +
              '<strong id="product-total-price">' + fmt(calcPrice(s.min)) + '</strong>' +
            '</div>' +
            '<div class="product-pro-actions">' +
              '<button type="submit" class="btn btn-primary btn-lg">В корзину</button>' +
              '<a href="/cart" class="btn btn-secondary">Открыть корзину</a>' +
            '</div>' +
          '</form>' +
        '</div>' +
      '</div>';

    document.getElementById('product-qty-minus')?.addEventListener('click', () => setQty(Q().step(getQty(), -1, service.min, service.max)));
    document.getElementById('product-qty-plus')?.addEventListener('click', () => setQty(Q().step(getQty(), 1, service.min, service.max)));
    document.getElementById('product-quantity')?.addEventListener('input', updateUI);
    document.getElementById('product-quantity')?.addEventListener('change', () => setQty(getQty()));

    const linkInput = document.getElementById('product-link');
    linkInput?.addEventListener('blur', () => validateLinkField(false));
    linkInput?.addEventListener('input', () => {
      linkInput.classList.remove('is-invalid');
      const hint = document.getElementById('product-link-hint');
      if (hint) hint.textContent = 'Пример: ' + linkPlaceholder;
    });

    function validateLinkField(showToast) {
      const val = linkInput?.value?.trim() || '';
      const v = window.BoosterinoLinkValidator;
      if (!v) return val;
      const r = v.validate(val, s.platform, s.type, s.platform_name, s.name, s.category);
      if (!r.ok) {
        linkInput?.classList.add('is-invalid');
        const hint = document.getElementById('product-link-hint');
        if (hint) hint.textContent = r.message;
        if (showToast) toast(r.message, 'error');
        return null;
      }
      linkInput?.classList.remove('is-invalid');
      if (r.normalized && linkInput && linkInput.value !== r.normalized) {
        linkInput.value = r.normalized;
      }
      return r.normalized || val;
    }

    document.getElementById('product-add-form')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const quantity = getQty();
      const link = validateLinkField(true);
      if (!link) return;
      window.BoosterinoCart.add({
        service_id: s.id,
        name: s.name,
        logo: s.logo,
        category_label: s.category_label,
        platform_name: s.platform_name,
        platform: s.platform,
        service_type: s.type,
        link_label: s.link_label,
        link_placeholder: s.link_placeholder,
        price_per_thousand_rub: s.price_per_thousand_rub,
        min: s.min,
        max: s.max,
        quantity,
        link,
        refill: s.refill,
        cancel: s.cancel,
      });
      const submitBtn = e.target.querySelector('button[type="submit"]');
      if (window.BoosterinoCartFly && submitBtn) {
        BoosterinoCartFly.flyToCart(submitBtn, s.logo);
      }
      toast('Товар добавлен в корзину');
    });

    updateUI();
  }

  api('/api/v1/services/' + serviceId + '?quantity=' + 100)
    .then((data) => {
      service = data.service;
      if (!service) throw new Error('Не найдено');
      renderPage();
    })
    .catch(() => {
      root.innerHTML = '<div class="card catalog-empty"><p>Товар не найден или недоступен</p><a href="/services" class="btn btn-primary">В каталог</a></div>';
    });
})();

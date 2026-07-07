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

  function clampQty(raw) {
    if (!service) return 0;
    return Math.max(service.min, Math.min(service.max, raw));
  }

  function calcPrice(qty) {
    if (!service) return 0;
    return (service.price_per_thousand_rub / 1000) * qty;
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
    if (minusBtn) minusBtn.disabled = qty <= service.min;
    if (plusBtn) plusBtn.disabled = qty >= service.max;
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
              '<label for="product-link">Ссылка на профиль или пост</label>' +
              '<input type="url" name="link" id="product-link" required placeholder="https://...">' +
            '</div>' +
            '<div class="product-pro-qty-row">' +
              '<div class="product-pro-field">' +
                '<label for="product-quantity">Количество</label>' +
                '<div class="product-pro-stepper">' +
                  '<button type="button" id="product-qty-minus" aria-label="Уменьшить">−</button>' +
                  '<input type="number" name="quantity" id="product-quantity" min="' + s.min + '" max="' + s.max + '" value="' + s.min + '" required>' +
                  '<button type="button" id="product-qty-plus" aria-label="Увеличить">+</button>' +
                '</div>' +
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

    document.getElementById('product-qty-minus')?.addEventListener('click', () => setQty(getQty() - 1));
    document.getElementById('product-qty-plus')?.addEventListener('click', () => setQty(getQty() + 1));
    document.getElementById('product-quantity')?.addEventListener('input', updateUI);
    document.getElementById('product-quantity')?.addEventListener('change', () => setQty(getQty()));

    document.getElementById('product-add-form')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const quantity = getQty();
      const link = String(fd.get('link') || '').trim();
      if (!link) {
        toast('Укажите ссылку', 'error');
        return;
      }
      window.BoosterinoCart.add({
        service_id: s.id,
        name: s.name,
        logo: s.logo,
        category_label: s.category_label,
        platform_name: s.platform_name,
        price_per_thousand_rub: s.price_per_thousand_rub,
        min: s.min,
        max: s.max,
        quantity,
        link,
        refill: s.refill,
        cancel: s.cancel,
      });
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

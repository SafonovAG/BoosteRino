(function () {
  const { api, toast } = window.Boosterino;
  const root = document.getElementById('product-page');
  if (!root) return;

  const serviceId = +root.dataset.serviceId;
  if (!serviceId) {
    root.innerHTML = '<div class="card"><p>Товар не найден</p><a href="/services" class="btn btn-secondary">В каталог</a></div>';
    return;
  }

  const fmt = window.BoosterinoProductCard?.formatPrice || ((n) => n + ' ₽');
  const escape = window.BoosterinoProductCard?.escapeHtml || ((s) => s);

  let service = null;

  function calcPrice(qty) {
    if (!service) return 0;
    return (service.price_per_thousand_rub / 1000) * qty;
  }

  function render() {
    const s = service;
    const qty = Math.max(s.min, Math.min(s.max, +document.getElementById('product-quantity')?.value || s.min));
    const price = calcPrice(qty);
    const priceEl = document.getElementById('product-total-price');
    if (priceEl) priceEl.textContent = fmt(price);

    const qtyInput = document.getElementById('product-quantity');
    if (qtyInput && +qtyInput.value !== qty) qtyInput.value = qty;
  }

  function renderPage() {
    const s = service;
    const breadcrumb = document.getElementById('product-breadcrumb');
    if (breadcrumb) breadcrumb.textContent = s.name;

    document.title = s.name + ' - Boosterino';

    const badges = [];
    if (s.refill) badges.push('<span class="badge badge-refill">Рефилл</span>');
    if (s.cancel) badges.push('<span class="badge badge-cancel">Отмена</span>');

    root.innerHTML =
      '<div class="product-page-grid">' +
        '<div class="product-page-gallery card">' +
          '<div class="product-page-logo">' +
            '<img src="' + escape(s.logo) + '" alt="" width="96" height="96">' +
          '</div>' +
          '<span class="product-card-category">' + escape(s.category_label || s.platform_name || s.category) + '</span>' +
          (badges.length ? '<div class="product-page-badges">' + badges.join('') + '</div>' : '') +
        '</div>' +
        '<div class="product-page-info card">' +
          '<h1 class="product-page-title">' + escape(s.name) + '</h1>' +
          '<div class="product-page-price-block">' +
            '<span class="product-page-price">' + fmt(s.price_per_thousand_rub) + '</span>' +
            '<span class="product-page-price-unit">/ 1000 ед.</span>' +
          '</div>' +
          '<ul class="product-page-specs">' +
            '<li><span>Минимум</span><strong>' + s.min + '</strong></li>' +
            '<li><span>Максимум</span><strong>' + s.max + '</strong></li>' +
            '<li><span>Платформа</span><strong>' + escape(s.platform_name || '—') + '</strong></li>' +
          '</ul>' +
          '<form id="product-add-form" class="form product-add-form">' +
            '<label>🔗 Ссылка на профиль или пост' +
              '<input type="url" name="link" id="product-link" required placeholder="https://...">' +
            '</label>' +
            '<label>Количество' +
              '<input type="number" name="quantity" id="product-quantity" min="' + s.min + '" max="' + s.max + '" value="' + s.min + '" required>' +
            '</label>' +
            '<div class="order-total-box product-total-box">' +
              '<span>Итого</span>' +
              '<strong id="product-total-price">' + fmt(calcPrice(s.min)) + '</strong>' +
            '</div>' +
            '<div class="product-page-actions">' +
              '<button type="submit" class="btn btn-primary btn-lg">🛒 В корзину</button>' +
              '<a href="/cart" class="btn btn-secondary">Перейти в корзину</a>' +
            '</div>' +
          '</form>' +
        '</div>' +
      '</div>';

    const qtyInput = document.getElementById('product-quantity');
    qtyInput?.addEventListener('input', render);

    document.getElementById('product-add-form')?.addEventListener('submit', (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const quantity = +fd.get('quantity');
      const link = String(fd.get('link') || '').trim();
      if (!link) {
        toast('Укажите ссылку', 'error');
        return;
      }
      if (quantity < s.min || quantity > s.max) {
        toast('Неверное количество', 'error');
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

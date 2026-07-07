(function () {
  const { api, toast } = window.Boosterino;
  const cart = window.BoosterinoCart;
  const root = document.getElementById('cart-page');
  if (!root || !cart) return;

  const fmt = window.BoosterinoProductCard?.formatPrice || ((n) => n + ' ₽');
  const escape = window.BoosterinoProductCard?.escapeHtml || ((s) => s);
  const isLoggedIn = !!document.querySelector('.balance-pill');

  function render() {
    const items = cart.getItems();

    if (!items.length) {
      root.innerHTML =
        '<div class="cart-empty card">' +
          '<div class="cart-empty-icon">🛒</div>' +
          '<h2>Корзина пуста</h2>' +
          '<p class="muted">Выберите услуги в каталоге и добавьте их сюда</p>' +
          '<a href="/services" class="btn btn-primary">Открыть каталог</a>' +
        '</div>';
      return;
    }

    let rows = '';
    items.forEach((item) => {
      const total = cart.lineTotal(item);
      rows +=
        '<article class="cart-item card" data-service-id="' + item.service_id + '">' +
          '<a href="/services/' + item.service_id + '" class="cart-item-logo">' +
            '<img src="' + escape(item.logo) + '" alt="" width="48" height="48">' +
          '</a>' +
          '<div class="cart-item-body">' +
            '<a href="/services/' + item.service_id + '" class="cart-item-title">' + escape(item.name) + '</a>' +
            '<span class="cart-item-category">' + escape(item.category_label || item.platform_name) + '</span>' +
            '<label class="cart-item-link-label">Ссылка' +
              '<input type="url" class="cart-item-link" value="' + escape(item.link) + '" placeholder="https://...">' +
            '</label>' +
            '<div class="cart-item-row">' +
              '<label>Кол-во' +
                '<input type="number" class="cart-item-qty" min="' + item.min + '" max="' + item.max + '" value="' + item.quantity + '">' +
              '</label>' +
              '<div class="cart-item-price">' +
                '<span class="muted">за 1000: ' + fmt(item.price_per_thousand_rub) + '</span>' +
                '<strong class="cart-item-total">' + fmt(total) + '</strong>' +
              '</div>' +
            '</div>' +
          '</div>' +
          '<button type="button" class="cart-item-remove" title="Удалить" aria-label="Удалить">×</button>' +
        '</article>';
    });

    root.innerHTML =
      '<div class="cart-layout">' +
        '<div class="cart-items-list">' + rows + '</div>' +
        '<aside class="cart-summary card">' +
          '<h2>Итого</h2>' +
          '<div class="cart-summary-row">' +
            '<span>Товаров</span><strong>' + cart.count() + ' ед.</strong>' +
          '</div>' +
          '<div class="cart-summary-total">' +
            '<span>Сумма</span><strong id="cart-grand-total">' + fmt(cart.total()) + '</strong>' +
          '</div>' +
          (isLoggedIn
            ? '<label>Способ оплаты<select id="cart-payment"><option value="balance">С баланса</option><option value="yoomoney">ЮMoney</option></select></label>' +
              '<button type="button" class="btn btn-primary btn-block btn-lg" id="cart-checkout">Оформить заказ</button>'
            : '<p class="muted">Войдите, чтобы оформить заказ</p>' +
              '<a href="/login?next=/cart" class="btn btn-primary btn-block">Войти</a>' +
              '<a href="/register" class="btn btn-secondary btn-block">Регистрация</a>') +
          '<a href="/services" class="btn btn-ghost btn-block">Продолжить покупки</a>' +
        '</aside>' +
      '</div>';

    bindEvents();
  }

  function bindEvents() {
    root.querySelectorAll('.cart-item').forEach((row) => {
      const sid = +row.dataset.serviceId;

      row.querySelector('.cart-item-remove')?.addEventListener('click', () => {
        cart.remove(sid);
        render();
      });

      row.querySelector('.cart-item-qty')?.addEventListener('change', (e) => {
        cart.update(sid, { quantity: +e.target.value });
        render();
      });

      row.querySelector('.cart-item-link')?.addEventListener('change', (e) => {
        cart.update(sid, { link: e.target.value.trim() });
      });
    });

    document.getElementById('cart-checkout')?.addEventListener('click', checkout);
  }

  async function checkout() {
    const items = cart.getItems();
    const payment = document.getElementById('cart-payment')?.value || 'balance';

    for (const item of items) {
      if (!item.link) {
        toast('Укажите ссылку для: ' + item.name, 'error');
        return;
      }
    }

    if (payment === 'yoomoney' && items.length > 1) {
      toast('ЮMoney: оформляйте по одному товару или выберите оплату с баланса', 'error');
      return;
    }

    const btn = document.getElementById('cart-checkout');
    if (btn) btn.disabled = true;

    try {
      for (let i = 0; i < items.length; i++) {
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

      cart.clear();
      toast('Заказы оформлены');
      location.href = '/cabinet';
    } catch (e) {
      toast(e.message, 'error');
      if (btn) btn.disabled = false;
    }
  }

  render();
  window.addEventListener('cart:updated', render);
})();

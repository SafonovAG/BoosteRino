(function () {
  const KEY = 'boosterino_cart';

  function load() {
    try {
      const raw = localStorage.getItem(KEY);
      const data = raw ? JSON.parse(raw) : null;
      return data && Array.isArray(data.items) ? data : { items: [] };
    } catch {
      return { items: [] };
    }
  }

  function save(cart) {
    localStorage.setItem(KEY, JSON.stringify(cart));
    syncBadge();
    window.dispatchEvent(new CustomEvent('cart:updated', { detail: cart }));
  }

  function syncBadge() {
    const el = document.getElementById('cart-count');
    if (!el) return;
    const n = BoosterinoCart.count();
    el.textContent = String(n);
    el.classList.toggle('is-empty', n === 0);
  }

  function count() {
    return load().items.reduce((sum, i) => sum + (i.quantity || 0), 0);
  }

  function lineTotal(item) {
    return (item.price_per_thousand_rub / 1000) * item.quantity;
  }

  function total() {
    return load().items.reduce((sum, i) => sum + lineTotal(i), 0);
  }

  function getItems() {
    return load().items;
  }

  function add(item) {
    const cart = load();
    const sid = +item.service_id;
    const existing = cart.items.find((i) => +i.service_id === sid);
    const qty = Math.max(1, +item.quantity || 1);

    if (existing) {
      existing.quantity = Math.min(existing.max, existing.quantity + qty);
      if (item.link) existing.link = item.link;
    } else {
      cart.items.push({
        service_id: sid,
        name: item.name,
        logo: item.logo,
        category_label: item.category_label || '',
        platform_name: item.platform_name || '',
        platform: item.platform || '',
        service_type: item.service_type || item.type || '',
        link_label: item.link_label || '',
        link_placeholder: item.link_placeholder || '',
        price_per_thousand_rub: +item.price_per_thousand_rub,
        min: +item.min,
        max: +item.max,
        quantity: Math.max(+item.min, Math.min(+item.max, qty)),
        link: item.link || '',
        refill: !!item.refill,
        cancel: !!item.cancel,
      });
    }
    save(cart);
    return cart;
  }

  function update(serviceId, patch) {
    const cart = load();
    const item = cart.items.find((i) => +i.service_id === +serviceId);
    if (!item) return cart;
    if (patch.quantity != null) {
      item.quantity = Math.max(item.min, Math.min(item.max, +patch.quantity));
    }
    if (patch.link != null) item.link = patch.link;
    save(cart);
    return cart;
  }

  function remove(serviceId) {
    const cart = load();
    cart.items = cart.items.filter((i) => +i.service_id !== +serviceId);
    save(cart);
    return cart;
  }

  function clear() {
    save({ items: [] });
  }

  const BoosterinoCart = { load, add, update, remove, clear, count, total, getItems, lineTotal };
  window.BoosterinoCart = BoosterinoCart;

  document.addEventListener('DOMContentLoaded', syncBadge);
  syncBadge();
})();

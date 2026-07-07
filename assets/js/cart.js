(function () {
  const KEY = 'boosterino_cart';
  const Q = () => window.BoosterinoQty || {
    PACK: 1000,
    minPacks: (min) => Math.max(1, Math.ceil(min / 1000)),
    fromPacks: (p, min, max) => Math.max(min || 1, Math.min(max || 1e9, p * 1000)),
    snap: (q, min, max) => Math.max(min, Math.min(max, +q)),
    calcPriceActual: (p, q) => (p / 1000) * q,
  };

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
    return load().items.length;
  }

  function totalUnits() {
    return load().items.reduce((sum, i) => sum + (i.quantity || 0), 0);
  }

  function lineTotal(item) {
    return Q().calcPriceActual(item.price_per_thousand_rub, item.quantity);
  }

  function total() {
    return load().items.reduce((sum, i) => sum + lineTotal(i), 0);
  }

  function getItems() {
    return load().items;
  }

  function normalizeQty(qty, min, max) {
    return Q().snap(+qty, +min, +max);
  }

  function add(item) {
    const cart = load();
    const sid = +item.service_id;
    const existing = cart.items.find((i) => +i.service_id === sid);
    const min = +item.min;
    const max = +item.max;
    const qty = normalizeQty(item.quantity || Q().fromPacks(Q().minPacks(min), min, max), min, max);

    if (existing) {
      existing.quantity = normalizeQty(existing.quantity + Q().PACK, existing.min, existing.max);
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
        price_unit_label: item.price_unit_label || '',
        delivery_unit: item.delivery_unit || '',
        price_per_thousand_rub: +item.price_per_thousand_rub,
        min,
        max,
        quantity: qty,
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
      item.quantity = normalizeQty(patch.quantity, item.min, item.max);
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

  const BoosterinoCart = { load, add, update, remove, clear, count, totalUnits, total, getItems, lineTotal, normalizeQty };
  window.BoosterinoCart = BoosterinoCart;

  document.addEventListener('DOMContentLoaded', syncBadge);
  syncBadge();
})();

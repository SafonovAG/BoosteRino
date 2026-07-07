(function () {
  const { api } = window.Boosterino;
  const container = document.getElementById('featured-products');
  if (!container) return;

  const GENERIC_CATEGORIES = new Set(['api', 'other', 'others', 'misc', 'general', 'разное', 'прочее', 'другое', 'общее']);
  const PREFERRED_PLATFORMS = ['telegram', 'vk', 'youtube', 'instagram', 'tiktok', 'facebook', 'twitch', 'twitter'];

  function isGenericCategory(s) {
    return GENERIC_CATEGORIES.has((s.category || '').toLowerCase().trim());
  }

  function pickFeatured(services) {
    const picked = [];
    const used = new Set();

    PREFERRED_PLATFORMS.forEach((plat) => {
      const match = services.find((s) => s.platform === plat && !used.has(s.id));
      if (match) {
        picked.push(match);
        used.add(match.id);
      }
    });

    const sorted = [...services].sort((a, b) =>
      a.price_per_thousand_rub - b.price_per_thousand_rub
    );

    sorted.forEach((s) => {
      if (picked.length >= 8) return;
      if (used.has(s.id)) return;
      if (isGenericCategory(s) && s.platform === 'other') return;
      picked.push(s);
      used.add(s.id);
    });

    return picked.slice(0, 8);
  }

  api('/api/v1/services').then((data) => {
    const all = data.services || [];
    const services = pickFeatured(all);
    if (!services.length) {
      container.innerHTML = '<p class="muted">Каталог скоро наполнится. Загляните позже.</p>';
      return;
    }
    const render = window.BoosterinoProductCard?.renderHomeTile || window.BoosterinoProductCard?.render;
    container.innerHTML = services.map((s) => render(s)).join('');
    const byId = new Map(services.map((s) => [s.id, s]));
    window.BoosterinoProductCard.bindQuickAdd(container, byId);
  }).catch(() => {
    container.innerHTML = '<p class="muted">Не удалось загрузить товары</p>';
  });
})();

(function () {
  function flyToCart(fromEl, logoUrl) {
    const target = document.getElementById('cart-link');
    if (!fromEl || !target) return;

    const from = fromEl.getBoundingClientRect();
    const to = target.getBoundingClientRect();
    const startX = from.left + from.width / 2;
    const startY = from.top + from.height / 2;
    const endX = to.left + to.width / 2;
    const endY = to.top + to.height / 2;
    const dx = endX - startX;
    const dy = endY - startY;
    const arc = Math.min(120, Math.abs(dx) * 0.25 + 40);

    const el = document.createElement('div');
    el.className = 'cart-fly-particle';
    el.style.left = startX + 'px';
    el.style.top = startY + 'px';
    el.style.setProperty('--tx', dx + 'px');
    el.style.setProperty('--ty', dy + 'px');
    el.style.setProperty('--arc', -arc + 'px');

    if (logoUrl) {
      const img = document.createElement('img');
      img.src = logoUrl;
      img.alt = '';
      el.appendChild(img);
    } else {
      el.textContent = '🛒';
      el.classList.add('cart-fly-particle--emoji');
    }

    document.body.appendChild(el);
    requestAnimationFrame(() => el.classList.add('is-flying'));

    const done = () => {
      el.remove();
      target.classList.add('cart-pill-bump');
      setTimeout(() => target.classList.remove('cart-pill-bump'), 450);
    };

    el.addEventListener('transitionend', done, { once: true });
    setTimeout(done, 800);
  }

  window.BoosterinoCartFly = { flyToCart };
})();

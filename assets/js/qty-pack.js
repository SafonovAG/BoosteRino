(function () {
  const PRICE_BASIS = 1000;

  function snap(qty, min, max) {
    let q = Math.round(Number(qty));
    if (!Number.isFinite(q)) q = min;
    return Math.max(min, Math.min(max, q));
  }

  function step(qty, direction, min, max) {
    const next = snap(qty, min, max) + direction;
    if (next < min) return min;
    if (next > max) return max;
    return next;
  }

  function canDecrease(qty, min) {
    return snap(qty, min, Number.MAX_SAFE_INTEGER) > min;
  }

  function canIncrease(qty, max) {
    return snap(qty, 1, max) < max;
  }

  function calcPrice(pricePerThousand, qty) {
    return (pricePerThousand / PRICE_BASIS) * snap(qty, 1, Number.MAX_SAFE_INTEGER);
  }

  function hintText(min, max, priceUnitLabel) {
    const label = priceUnitLabel || 'цена за 1000';
    return 'от ' + min + ' до ' + max + ' · ' + label;
  }

  window.BoosterinoQty = {
    PRICE_BASIS,
    snap,
    step,
    canDecrease,
    canIncrease,
    calcPrice,
    calcPriceActual: calcPrice,
    hintText,
  };
})();

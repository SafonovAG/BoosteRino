(function () {
  const PACK = 1000;

  function calcPrice(pricePerThousand, actualQty) {
    return (pricePerThousand / PACK) * actualQty;
  }

  function snap(actualQty, min, max) {
    let q = Math.max(min, Math.min(max, actualQty));
    if (q <= min) return min;
    const snapped = Math.round(q / PACK) * PACK;
    if (snapped < min) return min;
    if (snapped > max) return max;
    return snapped;
  }

  function step(actualQty, direction, min, max) {
    const delta = direction * PACK;
    let next = actualQty + delta;
    if (direction < 0 && next < min) return min;
    if (direction > 0 && next > max) return max;
    return snap(next, min, max);
  }

  function canDecrease(actualQty, min) {
    return actualQty > min;
  }

  function canIncrease(actualQty, max) {
    return step(actualQty, 1, 0, max) > actualQty;
  }

  function packs(actualQty) {
    return actualQty / PACK;
  }

  function labelSuffix() {
    return ' (шаг 1000)';
  }

  window.BoosterinoQty = {
    PACK,
    calcPrice,
    snap,
    step,
    canDecrease,
    canIncrease,
    packs,
    labelSuffix,
  };
})();

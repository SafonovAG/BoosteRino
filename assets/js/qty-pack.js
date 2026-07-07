(function () {
  const PACK = 1000;

  function minPacks(min) {
    return Math.max(1, Math.ceil(min / PACK));
  }

  function maxPacks(max) {
    return Math.max(minPacks(1), Math.floor(max / PACK));
  }

  function snapPacks(packs, min, max) {
    const lo = minPacks(min);
    const hi = maxPacks(max);
    let p = Math.round(packs);
    if (!Number.isFinite(p)) p = lo;
    return Math.max(lo, Math.min(hi, p));
  }

  function stepPacks(packs, direction, min, max) {
    return snapPacks(packs + direction, min, max);
  }

  function fromPacks(packs, min, max) {
    return snapPacks(packs, min, max) * PACK;
  }

  function toPacks(actual, min, max) {
    if (!actual) return minPacks(min);
    return snapPacks(Math.round(actual / PACK), min, max);
  }

  function actualUnits(packs, min, max) {
    return fromPacks(packs, min, max);
  }

  function calcPrice(pricePerThousand, packs) {
    return pricePerThousand * snapPacks(packs, 1, Number.MAX_SAFE_INTEGER);
  }

  function calcPriceActual(pricePerThousand, actualQty) {
    return pricePerThousand * (actualQty / PACK);
  }

  function canDecreasePacks(packs, min) {
    return snapPacks(packs, min, Number.MAX_SAFE_INTEGER) > minPacks(min);
  }

  function canIncreasePacks(packs, max) {
    return snapPacks(packs, 1, max) < maxPacks(max);
  }

  function labelSuffix() {
    return ' (шаг 1)';
  }

  function hintText() {
    return '1 в поле = 1000 ед. · цена указана за 1000';
  }

  function snap(actualQty, min, max) {
    return fromPacks(toPacks(actualQty, min, max), min, max);
  }

  function step(actualQty, direction, min, max) {
    const packs = toPacks(actualQty, min, max);
    return fromPacks(stepPacks(packs, direction, min, max), min, max);
  }

  function canDecrease(actualQty, min) {
    return canDecreasePacks(toPacks(actualQty, min, Number.MAX_SAFE_INTEGER), min);
  }

  function canIncrease(actualQty, max) {
    return canIncreasePacks(toPacks(actualQty, 1, max), max);
  }

  window.BoosterinoQty = {
    PACK,
    minPacks,
    maxPacks,
    snapPacks,
    stepPacks,
    fromPacks,
    toPacks,
    actualUnits,
    calcPrice,
    calcPriceActual,
    canDecreasePacks,
    canIncreasePacks,
    labelSuffix,
    hintText,
    snap,
    step,
    canDecrease,
    canIncrease,
  };
})();

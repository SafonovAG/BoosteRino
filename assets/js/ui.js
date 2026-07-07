(function () {
  const reveals = document.querySelectorAll('.reveal');
  if (!reveals.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );

  reveals.forEach((el) => observer.observe(el));

  window.BoosterinoPayment = {
    short: 'Карта, SberPay, МИР или ЮMoney',
    long: 'банковской картой, SberPay, МИР или кошельком ЮMoney',
    noun: 'Банковской картой, SberPay, МИР или кошелёк ЮMoney',
  };
})();

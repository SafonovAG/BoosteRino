(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  function toast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.textContent = message;
    container.appendChild(el);
    setTimeout(() => el.remove(), 4500);
  }

  async function api(path, options = {}) {
    const headers = {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrf,
      ...(options.headers || {}),
    };

    const res = await fetch(path, {
      credentials: 'same-origin',
      ...options,
      headers,
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok || data.success === false) {
      const msg = data.error?.message || 'Ошибка запроса';
      throw new Error(msg);
    }

    return data.data ?? data;
  }

  window.Boosterino = { api, toast, csrf };
})();

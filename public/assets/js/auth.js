(function () {
  const { api, toast } = window.Boosterino;

  function bindForm(id, handler) {
    const form = document.getElementById(id);
    if (!form) return;
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('[type=submit]');
      btn.disabled = true;
      try {
        await handler(new FormData(form));
      } catch (err) {
        toast(err.message, 'error');
      } finally {
        btn.disabled = false;
      }
    });
  }

  bindForm('login-form', async (fd) => {
    await api('/api/v1/auth/login', {
      method: 'POST',
      body: JSON.stringify({
        email: fd.get('email'),
        password: fd.get('password'),
      }),
    });
    toast('Добро пожаловать!');
    location.href = '/cabinet';
  });

  bindForm('register-form', async (fd) => {
    await api('/api/v1/auth/register', {
      method: 'POST',
      body: JSON.stringify({
        email: fd.get('email'),
        password: fd.get('password'),
      }),
    });
    toast('Проверьте email для подтверждения');
    location.href = '/login';
  });

  bindForm('forgot-form', async (fd) => {
    await api('/api/v1/auth/forgot-password', {
      method: 'POST',
      body: JSON.stringify({ email: fd.get('email') }),
    });
    toast('Если email зарегистрирован, мы отправили письмо');
  });

  bindForm('reset-form', async (fd) => {
    const params = new URLSearchParams(location.search);
    await api('/api/v1/auth/reset-password', {
      method: 'POST',
      body: JSON.stringify({
        token: params.get('token') || fd.get('token'),
        password: fd.get('password'),
      }),
    });
    toast('Пароль обновлён');
    location.href = '/login';
  });
})();

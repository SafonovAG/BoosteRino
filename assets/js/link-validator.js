(function () {
  const HOSTS = {
    telegram: ['t.me', 'telegram.me', 'telegram.dog'],
    'telegram-premium': ['t.me', 'telegram.me', 'telegram.dog'],
    instagram: ['instagram.com', 'www.instagram.com', 'instagr.am'],
    vk: ['vk.com', 'www.vk.com', 'm.vk.com', 'vk.ru', 'www.vk.ru'],
    youtube: ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'],
    tiktok: ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com'],
    facebook: ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'fb.com', 'fb.watch'],
    twitter: ['twitter.com', 'www.twitter.com', 'mobile.twitter.com', 'x.com', 'www.x.com'],
    twitch: ['twitch.tv', 'www.twitch.tv', 'm.twitch.tv'],
    discord: ['discord.com', 'www.discord.com', 'discord.gg'],
    dzen: ['dzen.ru', 'www.dzen.ru', 'zen.yandex.ru'],
    rutube: ['rutube.ru', 'www.rutube.ru'],
    odnoklassniki: ['ok.ru', 'www.ok.ru', 'm.ok.ru'],
    pinterest: ['pinterest.com', 'www.pinterest.com', 'pin.it'],
    linkedin: ['linkedin.com', 'www.linkedin.com'],
    spotify: ['open.spotify.com', 'spotify.com'],
    steam: ['steamcommunity.com', 'store.steampowered.com'],
    kick: ['kick.com', 'www.kick.com'],
    trovo: ['trovo.live', 'www.trovo.live'],
    likee: ['likee.video', 'l.likee.video'],
    threads: ['threads.net', 'www.threads.net'],
    avito: ['avito.ru', 'www.avito.ru'],
    vcru: ['vc.ru', 'www.vc.ru'],
    dtf: ['dtf.ru', 'www.dtf.ru'],
    yandexmusic: ['music.yandex.ru', 'music.yandex.com'],
  };

  const PROFILE_TYPES = ['subscribe', 'follow', 'friend', 'favorite'];

  const EXAMPLES = {
    telegram: { profile: 'https://t.me/channel', post: 'https://t.me/channel/123' },
    'telegram-premium': { profile: 'https://t.me/channel', post: 'https://t.me/channel/123' },
    instagram: { profile: 'https://instagram.com/username', post: 'https://instagram.com/p/ABC123/' },
    vk: { profile: 'https://vk.com/public123', post: 'https://vk.com/wall-123_456' },
    youtube: { profile: 'https://youtube.com/@channel', post: 'https://youtube.com/watch?v=abc' },
    tiktok: { profile: 'https://tiktok.com/@username', post: 'https://tiktok.com/@user/video/123' },
    facebook: { profile: 'https://facebook.com/pagename', post: 'https://facebook.com/post/123' },
    twitter: { profile: 'https://x.com/username', post: 'https://x.com/user/status/123' },
    twitch: { profile: 'https://twitch.tv/channel', post: 'https://twitch.tv/videos/123' },
    discord: { profile: 'https://discord.gg/invite', post: 'https://discord.com/channels/1/2' },
    dzen: { profile: 'https://dzen.ru/channel', post: 'https://dzen.ru/a/abc' },
    rutube: { profile: 'https://rutube.ru/channel/123', post: 'https://rutube.ru/video/abc/' },
    odnoklassniki: { profile: 'https://ok.ru/group/123', post: 'https://ok.ru/group/123/topic/456' },
  };

  function normalize(link) {
    let s = String(link || '').trim();
    if (!s) return '';
    if (s.startsWith('@') && /^@[A-Za-z0-9_]{3,}$/.test(s)) {
      return 'https://t.me/' + s.slice(1);
    }
    if (!/^https?:\/\//i.test(s)) {
      s = 'https://' + s.replace(/^\/+/, '');
    }
    return s;
  }

  function isProfileType(type, name, category) {
    const t = String(type || '').toLowerCase();
    if (PROFILE_TYPES.includes(t)) return true;
    const hay = (name + ' ' + category).toLowerCase();
    if (/подписчик|subscriber|follower|участник|member|друг|friend/.test(hay)) return true;
    if (/лайк|like|просмотр|view|коммент|comment|репост|repost|retweet|голос|vote|реакц/.test(hay)) return false;
    return PROFILE_TYPES.includes(t);
  }

  function pathMatches(platform, isProfile, path, url) {
    path = path.replace(/\/$/, '') || '/';
    const host = (() => {
      try { return new URL(url).hostname.toLowerCase(); } catch { return ''; }
    })();

    switch (platform) {
      case 'instagram':
        return isProfile ? /^\/[A-Za-z0-9._]{1,30}\/?$/.test(path) : /^\/(p|reel|tv)\/[A-Za-z0-9_-]+/.test(path);
      case 'telegram':
      case 'telegram-premium':
        return /^\/[A-Za-z0-9_]{3,}/.test(path);
      case 'vk':
        return isProfile ? /^\/(id\d+|club\d+|public\d+|[A-Za-z0-9._-]+)\/?$/.test(path) : /^\/(wall|video|clip|photo)-?\d/.test(path);
      case 'youtube':
        return isProfile ? /^\/(@[\w.-]+|channel\/|c\/|user\/)/.test(path) : /^\/watch/.test(path) || url.includes('youtu.be/');
      case 'tiktok':
        return isProfile ? /^\/@[\w.-]+\/?$/.test(path) : /^\/@[\w.-]+\/video\/\d+/.test(path);
      case 'twitter':
        return isProfile ? /^\/[A-Za-z0-9_]{1,15}\/?$/.test(path) : /^\/[A-Za-z0-9_]+\/status\/\d+/.test(path);
      case 'facebook':
        return path.length > 1;
      case 'twitch':
        return isProfile ? /^\/[a-z0-9_]{2,}\/?$/i.test(path) : /^\/(videos\/\d+|[a-z0-9_]+\/clip\/)/i.test(path);
      case 'discord':
        return path.includes('channels/') || path.includes('invite') || host === 'discord.gg';
      default:
        return path.length > 1 || url.includes('?');
    }
  }

  function hint(platform, type, platformName, name, category) {
    const isProfile = isProfileType(type, name, category);
    const kind = isProfile ? 'profile' : 'post';
    const ex = (EXAMPLES[platform] || {})[kind] || 'https://example.com/...';
    const target = isProfile ? 'профиль или канал' : 'публикацию или пост';
    const label = 'Ссылка на ' + target + ' (' + (platformName || platform) + ')';
    return {
      label,
      placeholder: ex,
      example: ex,
      error: 'Для этой услуги нужна ссылка на ' + target + ' ' + (platformName || platform) + '. Пример: ' + ex,
    };
  }

  function validate(link, platform, type, platformName, name, category) {
    const normalized = normalize(link);
    if (!normalized) {
      return { ok: false, message: 'Укажите ссылку.' };
    }

    let url;
    try {
      url = new URL(normalized);
    } catch {
      return { ok: false, message: 'Некорректный адрес ссылки.' };
    }

    const h = hint(platform, type, platformName, name, category);
    const isProfile = isProfileType(type, name, category);

    if (platform === 'other') {
      return { ok: true, normalized, message: '' };
    }

    const allowed = HOSTS[platform] || [];
    if (allowed.length && !allowed.includes(url.hostname.toLowerCase())) {
      return { ok: false, message: 'Ссылка не соответствует платформе ' + (platformName || platform) + '. Пример: ' + h.example };
    }

    if (!pathMatches(platform, isProfile, url.pathname, normalized)) {
      return { ok: false, message: h.error };
    }

    return { ok: true, normalized, message: '' };
  }

  window.BoosterinoLinkValidator = { normalize, validate, hint, isProfileType };
})();

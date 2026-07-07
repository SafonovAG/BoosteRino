(function (global) {
  function html(name, extraClass) {
    const cls = ['bi', 'bi-' + name, 'app-icon'];
    if (extraClass) cls.push(extraClass);
    return '<i class="' + cls.join(' ') + '" aria-hidden="true"></i>';
  }

  function panel(name) {
    return '<span class="panel-icon">' + html(name, 'app-icon--accent') + '</span>';
  }

  function nav(name, color) {
    return '<span class="nav-icon">' + html(name, color ? 'app-icon--' + color : 'app-icon--accent') + '</span>';
  }

  function stat(name, color) {
    return '<span class="stat-icon">' + html(name, 'app-icon--' + (color || 'accent')) + '</span>';
  }

  function empty(name) {
    return html(name, 'app-icon--accent app-icon--xl');
  }

  global.BoosterinoIcons = {
    html: html,
    panel: panel,
    nav: nav,
    stat: stat,
    empty: empty,
  };
})(window);

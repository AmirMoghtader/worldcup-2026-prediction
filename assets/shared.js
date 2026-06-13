(function () {
  'use strict';

  var API = '/backend/api.php';
  var DEFAULT_SETTINGS = {
    site_name: 'پیشبینی جام جهانی ۲۰۲۶',
    site_tagline: 'پیش‌بینی زنده بازی‌ها، شرط‌های اختصاصی هر مسابقه و جدول امتیازات کاربران',
    prediction_lock_minutes: 10,
    prediction_window_hours: 48,
    logo_url: '/assets/worldcup.jpeg',
    nav_logo_url: '',
    auth_logo_url: '',
    footer_logo_url: '',
    admin_logo_url: '',
    hero_banner_url: '',
    hero_banner_link_url: '',
    hero_banner_pure_mode: 0,
    hero_banner_mobile_url: '',
    hero_banner_height_desktop: 220,
    hero_banner_height_mobile: 168,
    home_sidebar_banner_url: '',
    home_sidebar_banner_link_url: '',
    live_scores_enabled: 0,
    live_scores_provider: 'varzesh3_html',
    live_scores_feed_url: '',
    live_scores_refresh_minutes: 5,
    live_scores_last_sync_at: '',
    footer_note: 'همه زمان‌ها به وقت ایران نمایش داده می‌شود.',
    footer_credit: 'طراحی و توسعه توسط ویرا وب آریا'
  };

  var _user = null;
  var _settings = null;
  var _loginCb = null;
  var _authCb = null;
  var _settingsPromise = null;
  var _loginState = { stage: 'phone', phone: '', isAdmin: false, hasUser: false };

  function getSettings() {
    return Object.assign({}, DEFAULT_SETTINGS, _settings || {});
  }

  function getLogoUrl(kind) {
    var settings = getSettings();
    var specific = settings[kind] || '';
    return specific || settings.logo_url || DEFAULT_SETTINGS.logo_url;
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function renderLogo(kind, className, alt) {
    var src = getLogoUrl(kind);
    return '<img class="' + className + '" src="' + escapeHtml(src) + '" alt="' + escapeHtml(alt || getSettings().site_name) + '">';
  }

  function ensureNav(cfg) {
    var nav = document.querySelector('.wc-nav');
    if (!nav) {
      nav = document.createElement('nav');
      nav.className = 'wc-nav';
      document.body.insertBefore(nav, document.body.firstChild);
    }
    var settings = getSettings();
    nav.innerHTML =
      '<div class="wc-nav-inner">' +
        '<a href="/" class="wc-nav-brand">' +
          '<span class="wc-brand-mark">' + renderLogo('nav_logo_url', 'wc-nav-logo', settings.site_name) + '</span>' +
          '<span class="wc-brand-copy">' +
            '<strong>' + escapeHtml(settings.site_name) + '</strong>' +
            '<small>' + escapeHtml(settings.site_tagline || 'جام جهانی ۲۰۲۶') + '</small>' +
          '</span>' +
        '</a>' +
        '<button class="wc-nav-toggle" id="wcNavToggle" type="button" aria-label="منو">☰</button>' +
        '<div class="wc-nav-panel" id="wcNavPanel">' +
          '<ul class="wc-nav-links">' +
            '<li><a href="/" ' + ((cfg.activePage || '') === 'home' ? 'class="active"' : '') + '>بازی‌ها</a></li>' +
            '<li><a href="/knockout" ' + ((cfg.activePage || '') === 'knockout' ? 'class="active"' : '') + '>حذفی</a></li>' +
            '<li><a href="/profile" ' + ((cfg.activePage || '') === 'profile' ? 'class="active"' : '') + '>حساب کاربری</a></li>' +
          '</ul>' +
          '<div class="wc-nav-auth" id="wcNavAuth"></div>' +
        '</div>' +
      '</div>';
    var toggle = document.getElementById('wcNavToggle');
    if (toggle) {
      toggle.addEventListener('click', function () {
        nav.classList.toggle('open');
      });
    }
    renderNavAuth();
  }

  function ensureFooter() {
    if (document.querySelector('.wc-footer')) return;
    var footer = document.createElement('footer');
    footer.className = 'wc-footer';
    document.body.appendChild(footer);
    renderFooter();
  }

  function renderFooter() {
    var footer = document.querySelector('.wc-footer');
    if (!footer) return;
    var settings = getSettings();
    footer.innerHTML =
      '<div class="wc-footer-inner">' +
        '<div class="wc-footer-brand">' +
          '<span class="wc-footer-mark">' + renderLogo('footer_logo_url', 'wc-footer-logo', settings.site_name) + '</span>' +
          '<div>' +
            '<strong>' + escapeHtml(settings.site_name) + '</strong>' +
            '<p>' + escapeHtml(settings.footer_note || DEFAULT_SETTINGS.footer_note) + '</p>' +
          '</div>' +
        '</div>' +
        '<div class="wc-footer-meta">' + escapeHtml(settings.footer_credit || DEFAULT_SETTINGS.footer_credit) + '</div>' +
      '</div>';
  }

  function ensureModal() {
    if (document.getElementById('wcOverlay')) return;
    var overlay = document.createElement('div');
    overlay.id = 'wcOverlay';
    overlay.className = 'wc-overlay';
    overlay.innerHTML =
      '<div class="wc-modal-box" role="dialog" aria-modal="true">' +
        '<button class="wc-modal-close" id="wcModalClose" type="button" aria-label="بستن">×</button>' +
        '<div class="wc-modal-head">' +
          '<div class="wc-modal-brand">' + renderLogo('auth_logo_url', 'wc-modal-logo', getSettings().site_name) + '</div>' +
          '<h3 id="wcModalTitle">' + escapeHtml(getSettings().site_name) + '</h3>' +
          '<p id="wcModalSubtitle">ورود کاربر یا ادمین از همین پنجره انجام می‌شود.</p>' +
        '</div>' +
        '<div class="wc-modal-body">' +
          '<div class="wc-modal-err" id="wcLoginErr"></div>' +
          '<label class="wc-field"><span>شماره موبایل</span><input type="tel" id="wcPhone" placeholder="09xxxxxxxxx" inputmode="numeric" autocomplete="tel"></label>' +
          '<label class="wc-field" id="wcNameField"><span>نام و نام خانوادگی</span><input type="text" id="wcName" placeholder="نام خود را وارد کنید" autocomplete="name"></label>' +
          '<label class="wc-field" id="wcPassField"><span>رمز عبور ادمین</span><input type="password" id="wcPass" placeholder="رمز عبور ادمین" autocomplete="current-password"></label>' +
          '<div class="wc-admin-toggle" id="wcLoginHint">برای زدن شماره تلفن همراه دقت کن چون کد تخفیف به همین شماره پیامک می‌شود.</div>' +
          '<button class="wc-modal-btn" id="wcLoginBtn" type="button">ادامه</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(overlay);

    var toastWrap = document.createElement('div');
    toastWrap.className = 'wc-toast-wrap';
    toastWrap.id = 'wcToastWrap';
    document.body.appendChild(toastWrap);

    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) {
        WC.closeLogin();
      }
    });
    document.getElementById('wcModalClose').addEventListener('click', WC.closeLogin);
    document.getElementById('wcLoginBtn').addEventListener('click', WC._doLogin);
    document.getElementById('wcPhone').addEventListener('input', function () {
      if (_loginState.stage !== 'phone' && this.value.trim() !== _loginState.phone) {
        _loginState = { stage: 'phone', phone: '', isAdmin: false, hasUser: false };
        document.getElementById('wcNameField').classList.remove('visible');
        document.getElementById('wcPassField').classList.remove('visible');
        var hint = document.getElementById('wcLoginHint');
        if (hint) hint.textContent = 'برای زدن شماره تلفن همراه دقت کن چون کد تخفیف به همین شماره پیامک می‌شود.';
      }
    });
    ['wcPhone', 'wcPass', 'wcName'].forEach(function (id) {
      document.getElementById(id).addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
          WC._doLogin();
        }
      });
    });
  }

  function renderNavAuth() {
    var holder = document.getElementById('wcNavAuth');
    if (!holder) return;
    var compact = window.innerWidth <= 760;
    if (_user && _user.role === 'admin') {
      holder.innerHTML =
        '<a href="/admin" class="wc-user-chip wc-user-chip-admin"><span>⚙</span><span>' + (compact ? 'پنل' : 'پنل ادمین') + '</span></a>' +
        '<button class="wc-btn-ghost" id="wcLogoutBtn" type="button">' + (compact ? 'خروج' : 'خروج') + '</button>';
    } else if (_user) {
      var userLabel = compact ? ((_user.name || 'حساب').split(' ')[0] || 'حساب') : (_user.name || _user.phone || 'کاربر');
      holder.innerHTML =
        '<a href="/profile" class="wc-user-chip"><span class="wc-user-avatar">' + escapeHtml((_user.name || 'ک').charAt(0)) + '</span><span>' + escapeHtml(userLabel) + '</span></a>' +
        '<button class="wc-btn-ghost" id="wcLogoutBtn" type="button">خروج</button>';
    } else {
      holder.innerHTML = '<button class="wc-btn-login" id="wcOpenLoginBtn" type="button">' + (compact ? 'ورود' : 'ورود / ثبت‌نام') + '</button>';
    }
    var openBtn = document.getElementById('wcOpenLoginBtn');
    if (openBtn) openBtn.addEventListener('click', function () { WC.openLogin(); });
    var logoutBtn = document.getElementById('wcLogoutBtn');
    if (logoutBtn) logoutBtn.addEventListener('click', WC.logout);
  }

  function renderSettingsBoundElements() {
    ensureNav(window.__wcConfig || {});
    renderFooter();
    var title = document.getElementById('wcModalTitle');
    if (title) title.textContent = getSettings().site_name;
    var subtitle = document.getElementById('wcModalSubtitle');
    if (subtitle) subtitle.textContent = getSettings().site_tagline || DEFAULT_SETTINGS.site_tagline;
    var modalBrand = document.querySelector('.wc-modal-brand');
    if (modalBrand) {
      modalBrand.innerHTML = renderLogo('auth_logo_url', 'wc-modal-logo', getSettings().site_name);
    }
  }

  function fetchSettings() {
    if (_settingsPromise) return _settingsPromise;
    _settingsPromise = fetch(API + '?action=admin_settings')
      .then(function (response) { return response.json(); })
      .then(function (data) {
        if (data && data.success && data.settings) {
          _settings = data.settings;
        }
        renderSettingsBoundElements();
        return getSettings();
      })
      .catch(function () {
        _settings = Object.assign({}, DEFAULT_SETTINGS);
        renderSettingsBoundElements();
        return getSettings();
      });
    return _settingsPromise;
  }

  function fetchAuth() {
    return fetch(API + '?action=me')
      .then(function (response) { return response.json(); })
      .then(function (data) {
        _user = (data && data.success && data.user) ? data.user : null;
        renderNavAuth();
        if (typeof _authCb === 'function') _authCb(_user);
        if (typeof window._wcOnAuth === 'function') window._wcOnAuth(_user);
        return _user;
      })
      .catch(function () {
        _user = null;
        renderNavAuth();
        if (typeof _authCb === 'function') _authCb(_user);
        return null;
      });
  }

  function showError(message) {
    var errorBox = document.getElementById('wcLoginErr');
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.style.display = 'block';
  }

  function resetModal() {
    ['wcPhone', 'wcName', 'wcPass'].forEach(function (id) {
      var input = document.getElementById(id);
      if (input) input.value = '';
    });
    _loginState = { stage: 'phone', phone: '', isAdmin: false, hasUser: false };
    document.getElementById('wcNameField').classList.remove('visible');
    document.getElementById('wcPassField').classList.remove('visible');
    var hint = document.getElementById('wcLoginHint');
    if (hint) hint.textContent = 'برای زدن شماره تلفن همراه دقت کن چون کد تخفیف به همین شماره پیامک می‌شود.';
    var errorBox = document.getElementById('wcLoginErr');
    if (errorBox) {
      errorBox.style.display = 'none';
      errorBox.textContent = '';
    }
  }

  function focusField(id) {
    setTimeout(function () {
      var field = document.getElementById(id);
      if (field) field.focus();
    }, 80);
  }

  var WC = {
    init: function (cfg) {
      cfg = cfg || {};
      window.__wcConfig = cfg;
      if (typeof cfg.onAuth === 'function') _authCb = cfg.onAuth;
      ensureNav(cfg);
      if (!cfg.skipFooter) ensureFooter();
      ensureModal();
      fetchSettings();
      fetchAuth();
      window.addEventListener('resize', renderNavAuth);
    },

    openLogin: function (cb) {
      _loginCb = typeof cb === 'function' ? cb : null;
      ensureModal();
      resetModal();
      document.getElementById('wcOverlay').classList.add('show');
      document.body.classList.add('wc-modal-open');
      focusField('wcPhone');
    },

    closeLogin: function () {
      var overlay = document.getElementById('wcOverlay');
      if (overlay) overlay.classList.remove('show');
      document.body.classList.remove('wc-modal-open');
      resetModal();
    },

    _doLogin: async function () {
      var phone = (document.getElementById('wcPhone').value || '').trim();
      var name = (document.getElementById('wcName').value || '').trim();
      var password = (document.getElementById('wcPass').value || '').trim();
      var hint = document.getElementById('wcLoginHint');
      var errorBox = document.getElementById('wcLoginErr');
      if (errorBox) errorBox.style.display = 'none';

      if (!phone) {
        showError('شماره موبایل را وارد کنید.');
        return;
      }

      try {
        if (_loginState.stage === 'phone') {
          var lookup = await WC.apiFetch('auth_lookup', { phone: phone }, 'POST');
          if (!lookup.success) {
            showError(lookup.error || 'شماره موبایل نامعتبر است.');
            return;
          }
          _loginState.phone = lookup.phone || phone;
          _loginState.isAdmin = !!lookup.is_admin;
          _loginState.hasUser = !!lookup.has_user;

          if (_loginState.isAdmin) {
            _loginState.stage = 'password';
            document.getElementById('wcPassField').classList.add('visible');
            document.getElementById('wcNameField').classList.remove('visible');
            if (hint) hint.textContent = 'این شماره ادمین است. رمز عبور را وارد کنید.';
            focusField('wcPass');
            return;
          }

          if (_loginState.hasUser) {
            var existingUserRes = await WC.apiFetch('register', { phone: phone, name: '' }, 'POST');
            if (!existingUserRes.success) {
              showError(existingUserRes.error || 'خطا در ورود.');
              return;
            }
            _user = existingUserRes.user || null;
            renderNavAuth();
            WC.closeLogin();
            WC.toast('ورود شما با موفقیت انجام شد.');
            if (_loginCb) _loginCb(_user);
            if (typeof _authCb === 'function') _authCb(_user);
            if (typeof window._wcOnAuth === 'function') window._wcOnAuth(_user);
            return;
          }

          _loginState.stage = 'name';
          document.getElementById('wcNameField').classList.add('visible');
          document.getElementById('wcPassField').classList.remove('visible');
            if (hint) hint.textContent = 'برای زدن شماره تلفن همراه دقت کن چون کد تخفیف به همین شماره پیامک می‌شود.';
          focusField('wcName');
          return;
        }

        if (_loginState.stage === 'password') {
          if (!password) {
            showError('برای ورود ادمین، رمز عبور لازم است.');
            focusField('wcPass');
            return;
          }
          var adminRes = await WC.apiFetch('admin_login', { phone: phone, password: password }, 'POST');
          if (!adminRes.success) {
            showError(adminRes.error || 'ورود ادمین ناموفق بود.');
            return;
          }
          window.location.href = '/admin';
          return;
        }

        if (!name) {
          showError('نام و نام خانوادگی را وارد کنید.');
          focusField('wcName');
          return;
        }

        var userRes = await WC.apiFetch('register', { phone: phone, name: name }, 'POST');
        if (!userRes.success) {
          showError(userRes.error || 'خطا در ورود.');
          return;
        }

        _user = userRes.user || null;
        renderNavAuth();
        WC.closeLogin();
        WC.toast('ورود شما با موفقیت انجام شد.');
        if (_loginCb) _loginCb(_user);
        if (typeof _authCb === 'function') _authCb(_user);
        if (typeof window._wcOnAuth === 'function') window._wcOnAuth(_user);
      } catch (_) {
        showError('خطا در ارتباط با سرور.');
      }
    },

    logout: async function () {
      try {
        await fetch(API + '?action=logout', { method: 'POST' });
      } catch (_) {}
      _user = null;
      renderNavAuth();
      if (window.location.pathname === '/admin' || window.location.pathname.indexOf('/admin/') === 0) {
        window.location.href = '/';
        return;
      }
      if (typeof _authCb === 'function') _authCb(_user);
      if (typeof window._wcOnAuth === 'function') window._wcOnAuth(_user);
    },

    apiFetch: async function (action, data, method) {
      method = method || 'GET';
      var url = API + '?action=' + action;
      var options = {
        method: method,
        headers: { 'Content-Type': 'application/json' }
      };
      if (method === 'POST') {
        options.body = JSON.stringify(data || {});
      } else if (data && Object.keys(data).length) {
        url += '&' + new URLSearchParams(data).toString();
      }
      var response = await fetch(url, options);
      return response.json();
    },

    toast: function (message, type) {
      var wrap = document.getElementById('wcToastWrap');
      if (!wrap) return;
      var toast = document.createElement('div');
      toast.className = 'wc-toast ' + (type || 'ok');
      toast.textContent = message;
      wrap.appendChild(toast);
      setTimeout(function () {
        if (toast.parentNode) toast.remove();
      }, 3200);
    },

    getUser: function () {
      return _user;
    },

    getSettings: function () {
      return getSettings();
    },

    refreshAuth: function () {
      return fetchAuth();
    },

    refreshSettings: function () {
      _settingsPromise = null;
      return fetchSettings();
    }
  };

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      WC.closeLogin();
    }
  });

  window.WC = WC;
})();

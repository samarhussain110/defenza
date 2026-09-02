(function () {
  function getCurrentLanguage() {
    const meta = document.querySelector('meta[name="lang"]');
    const fromMeta = meta && meta.getAttribute('content') ? meta.getAttribute('content') : '';
    const fromAttr = document.documentElement.getAttribute('data-lang') || '';
    const raw = (fromMeta || fromAttr || 'English').toLowerCase();
    if (raw === 'urdu' || raw === 'ur') return 'ur';
    return 'en';
  }

  const langCode = getCurrentLanguage();
  const dictUrl = (window.APP_URL || '') + '/assets/i18n/' + langCode + '.json';

  function warnMissing(key) {
    const message = 'MISSING TRANSLATION KEY: ' + key + ' — Urdu value abhi tak add nahi hui ur.json mein';
    if (window.__i18nWarnings) {
      window.__i18nWarnings.push(message);
    }
    console.warn(message);
  }

  function setText(el, value) {
    if (!el || typeof value !== 'string') return;
    el.textContent = value;
  }

  function applyTranslations(dict) {
    document.querySelectorAll('[data-i18n]').forEach(function (el) {
      const key = el.getAttribute('data-i18n');
      if (dict[key]) {
        setText(el, dict[key]);
      } else {
        warnMissing(key);
      }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
      const key = el.getAttribute('data-i18n-placeholder');
      if (dict[key]) {
        el.setAttribute('placeholder', dict[key]);
      } else {
        warnMissing(key);
      }
    });

    document.querySelectorAll('[data-i18n-title]').forEach(function (el) {
      const key = el.getAttribute('data-i18n-title');
      if (dict[key]) {
        el.setAttribute('title', dict[key]);
      } else {
        warnMissing(key);
      }
    });

    document.querySelectorAll('[data-i18n-aria-label]').forEach(function (el) {
      const key = el.getAttribute('data-i18n-aria-label');
      if (dict[key]) {
        el.setAttribute('aria-label', dict[key]);
      } else {
        warnMissing(key);
      }
    });
  }

  fetch(dictUrl)
    .then(function (response) { return response.ok ? response.json() : Promise.reject(response.statusText); })
    .then(applyTranslations)
    .catch(function () {
      const fallbackUrl = (window.APP_URL || '') + '/assets/i18n/en.json';
      fetch(fallbackUrl)
        .then(function (res) { return res.json(); })
        .then(applyTranslations);
    });
})();

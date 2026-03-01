/**
 * OCFilter mobile fix v7.1
 * Fixes for: iOS Safari 15+, iOS Yandex Browser, Opera iOS, Android
 *
 * v7.1 Changes vs v7.0:
 * - REVERTED: removed ajaxPrefilter that was breaking AJAX URLs
 * - KEPT: jQuery.ajaxSetup timeout=15000 (safe, global)
 * - KEPT: improved stuck button watchdog (detects "загрузка" text)
 * - KEPT: all reinit/watchdog/touch logic
 */
(function() {
  'use strict';

  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isYandexBrowser = /YaBrowser/.test(ua);
  var isMobile = window.innerWidth <= 1024 || /Mobi|Android|iPhone|iPad|iPod/.test(ua);

  var DELAYS = isIOS
    ? [100, 300, 700, 1200, 2000, 3000, 5000, 7000]
    : [200, 500, 1200, 2500, 5000];

  console.log('[OCFilter v7.1] iOS='+isIOS+' Yandex='+isYandexBrowser+' Mobile='+isMobile);

  // =============================================
  // Set jQuery AJAX global timeout (safe, no URL modification)
  // =============================================
  function installAjaxTimeout() {
    if (typeof jQuery === 'undefined') {
      setTimeout(installAjaxTimeout, 100);
      return;
    }
    jQuery.ajaxSetup({ timeout: 15000 });
    console.log('[OCFilter v7.1] jQuery AJAX timeout=15s installed');
  }

  // =============================================
  // Check if any OCFilter module needs init
  // =============================================
  function needsInit() {
    if (typeof jQuery === 'undefined') return false;
    var modules = document.querySelectorAll('[id^="ocf-module-"]');
    if (!modules.length) return false;
    for (var i = 0; i < modules.length; i++) {
      var data = jQuery(modules[i]).data('ocfilter');
      if (!data) return true;
    }
    return false;
  }

  // =============================================
  // Get ocfilter.js absolute URL
  // =============================================
  function getOCFilterScriptUrl(idx) {
    idx = idx || '1';
    var savedUrl = window['_ocf_js_url_' + idx];
    if (savedUrl) {
      if (savedUrl.indexOf('http') !== 0 && savedUrl.indexOf('//') !== 0) {
        savedUrl = window.location.protocol + '//' + window.location.host + '/' +
          savedUrl.replace(/^\/+/, '');
      }
      return savedUrl;
    }
    var scripts = document.querySelectorAll('script[src]');
    for (var i = 0; i < scripts.length; i++) {
      if (scripts[i].src && scripts[i].src.indexOf('ocfilter48/ocfilter') !== -1) {
        return scripts[i].src;
      }
    }
    return window.location.protocol + '//' + window.location.host +
      '/catalog/view/javascript/ocfilter48/ocfilter.js?v=4.8.2';
  }

  // =============================================
  // Load ocfilter.js via XHR + eval (iOS-safe)
  // =============================================
  function loadOCFilterScript(scriptUrl, onLoaded) {
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.ocfilter !== 'undefined') {
      onLoaded();
      return;
    }
    var cbUrl = scriptUrl.split('?')[0] + '?v=4.8.2&_fix=' + Date.now();
    try {
      var xhr = new XMLHttpRequest();
      xhr.open('GET', cbUrl, true);
      xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        if (xhr.status === 200) {
          try {
            (new Function(xhr.responseText)).call(window);
          } catch(e1) {
            try { (0, eval)(xhr.responseText); } catch(e2) {}
          }
          setTimeout(function() { onLoaded(); }, 50);
        }
      };
      xhr.onerror = function() { onLoaded(); };
      xhr.send();
    } catch(e) {
      onLoaded();
    }
  }

  // =============================================
  // Initialize OCFilter on all uninitialized modules
  // =============================================
  function initModules() {
    if (typeof jQuery === 'undefined') return false;
    var $ = jQuery;
    var modules = $('[id^="ocf-module-"]');
    if (!modules.length) return false;

    var anyInited = false;
    modules.each(function() {
      var $el = $(this);
      if ($el.data('ocfilter')) return;
      if (typeof $.fn.ocfilter === 'undefined') return;

      var idx = ($el.attr('id') || '').replace('ocf-module-', '') || '1';
      var cfg = window['_ocf_config_' + idx];

      try {
        if (cfg && cfg.index) {
          $el.ocfilter(cfg);
          anyInited = true;
        } else {
          $el.ocfilter({ index: idx });
          anyInited = true;
        }
      } catch(e) {
        console.warn('[OCFilter v7.1] reinit error:', e);
      }
    });
    return anyInited;
  }

  // =============================================
  // Full reinit: reload script if needed, then init
  // =============================================
  function forceReinit(onDone) {
    if (typeof jQuery === 'undefined') return;
    if (!needsInit()) { if (onDone) onDone(false); return; }

    var scriptUrl = getOCFilterScriptUrl('1');

    if (typeof jQuery.fn.ocfilter === 'undefined') {
      loadOCFilterScript(scriptUrl, function() {
        var ok = initModules();
        if (onDone) onDone(ok);
      });
    } else {
      var ok = initModules();
      if (onDone) onDone(ok);
    }
  }

  // =============================================
  // Retry sequence with delays
  // =============================================
  function tryInitSequence(delayIdx) {
    delayIdx = delayIdx || 0;
    if (delayIdx >= DELAYS.length) return;

    setTimeout(function() {
      if (!needsInit()) return;
      forceReinit(function(ok) {
        if (!ok && delayIdx < DELAYS.length - 1) {
          tryInitSequence(delayIdx + 1);
        }
      });
    }, DELAYS[delayIdx]);
  }

  // =============================================
  // Watchdog: detect stuck loading button and force-reset after 8s
  // =============================================
  function installWatchdog() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    setInterval(function() {
      $('[data-ocf="button"]').each(function() {
        var $b = $(this);
        var html = ($b.html() || '').toLowerCase();
        var isDisabled = $b.hasClass('ocf-disabled') || !!$b.attr('disabled');
        var isLoading = html.indexOf('fa-spin') !== -1 ||
                        html.indexOf('загрузка') !== -1 ||
                        html.indexOf('loading') !== -1;

        if (isDisabled && isLoading) {
          var stuckSince = $b.data('_ocf_stuck');
          if (!stuckSince) {
            $b.data('_ocf_stuck', Date.now());
            console.warn('[OCFilter v7.1] Watchdog: stuck loading detected');
          } else if (Date.now() - stuckSince > 8000) {
            console.warn('[OCFilter v7.1] Watchdog: force-reset after 8s');
            $b.removeData('_ocf_stuck');
            // Direct plugin flag reset
            var pd = $b.data('ocf.button');
            if (pd) pd.isLoading = false;
            // DOM reset
            $b.removeClass('ocf-disabled').removeAttr('disabled').prop('disabled', false);
            var rt = $b.data('resetText');
            if (rt) {
              $b.html(rt);
            } else {
              var cfg = window['_ocf_config_1'];
              if (cfg && cfg.textSelect) $b.html(cfg.textSelect);
            }
          }
        } else {
          $b.removeData('_ocf_stuck');
        }
      });
    }, 2000);

    $(document).off('click.ocfw touchend.ocfw').on(
      'click.ocfw touchend.ocfw',
      '[data-ocf="mobile"], .ocf-btn-mobile-fixed',
      function(e) {
        setTimeout(function() {
          var $container = $('[id^="ocf-module-"]').first();
          var isVisible = $container.hasClass('ocf-open') ||
            $container.find('.ocf-content').is(':visible') ||
            $('[id^="ocf-module-"]').data('ocfilter') !== undefined;
          if (!isVisible || needsInit()) {
            forceReinit(null);
          }
        }, 1500);
      }
    );
  }

  // =============================================
  // iOS touch trigger
  // =============================================
  function installTouchTrigger() {
    if (!isMobile) return;
    var triggered = false;
    function touchHandler(e) {
      if (triggered || !needsInit()) return;
      var t = e.target;
      var inOCF = false;
      while (t && t !== document.body) {
        if ((t.id && t.id.indexOf('ocf-module-') === 0) ||
            (t.className && (
              t.className.indexOf('ocf-container') !== -1 ||
              t.className.indexOf('ocf-btn-mobile') !== -1
            ))) {
          inOCF = true; break;
        }
        t = t.parentNode;
      }
      if (inOCF) {
        triggered = true;
        document.removeEventListener('touchstart', touchHandler, true);
        setTimeout(function() {
          forceReinit(function(ok) {
            if (!ok) setTimeout(function() { tryInitSequence(0); }, 500);
          });
        }, 50);
      }
    }
    document.addEventListener('touchstart', touchHandler, { passive: true, capture: true });
  }

  // =============================================
  // visibilitychange / bfcache
  // =============================================
  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible' && needsInit()) {
      setTimeout(function() { tryInitSequence(0); }, 300);
    }
  });

  window.addEventListener('pageshow', function(e) {
    var fromCache = e.persisted;
    var navEntry = (performance && performance.getEntriesByType) ?
      performance.getEntriesByType('navigation')[0] : null;
    var isBackForward = navEntry && navEntry.type === 'back_forward';
    if (fromCache || isBackForward) {
      tryInitSequence(0);
      setTimeout(installWatchdog, 500);
    } else {
      setTimeout(function() {
        if (needsInit()) tryInitSequence(0);
      }, isIOS ? 500 : 1000);
    }
  });

  // =============================================
  // Main start
  // =============================================
  function start() {
    installAjaxTimeout();
    tryInitSequence(0);
    var wdTries = 0;
    var wdInt = setInterval(function() {
      wdTries++;
      if (typeof jQuery !== 'undefined') {
        clearInterval(wdInt);
        installWatchdog();
        installTouchTrigger();
      } else if (wdTries > 200) {
        clearInterval(wdInt);
      }
    }, 100);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }

  window.addEventListener('load', function() {
    setTimeout(function() {
      if (typeof jQuery !== 'undefined') {
        installAjaxTimeout();
        installWatchdog();
        if (needsInit()) tryInitSequence(0);
      }
    }, isIOS ? 800 : 500);
  });

  // =============================================
  // Catalog button - direct link on desktop
  // =============================================
  function initCatalogButton() {
    var btn = document.querySelector('.ds-header-catalog-button');
    if (!btn) return;
    function isDesktop() { return window.innerWidth >= 1200; }
    btn.addEventListener('click', function(e) {
      if (!isDesktop()) return;
      var parent = btn.parentElement;
      var catalogMenu = parent ? parent.querySelector('.ds-menu-main-catalog, .ds-menu-catalog') : null;
      var firstLink = catalogMenu ? catalogMenu.querySelector('.ds-menu-catalog-item a') : null;
      if (firstLink && firstLink.href) {
        e.preventDefault(); e.stopImmediatePropagation();
        window.location.href = firstLink.href; return;
      }
      var anyLink = document.querySelector('.ds-menu-catalog-item a[href]');
      if (anyLink) {
        e.preventDefault(); e.stopImmediatePropagation();
        window.location.href = anyLink.href;
      }
    }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCatalogButton);
  } else {
    initCatalogButton();
  }

})();

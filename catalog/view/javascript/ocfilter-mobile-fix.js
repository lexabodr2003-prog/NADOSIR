/**
 * OCFilter mobile fix v7.0
 * Fixes for: iOS Safari 15+, iOS Yandex Browser, Opera iOS, Android
 *
 * v7.0 Changes vs v6.0:
 * - CRITICAL FIX: jQuery $.ajax timeout override to prevent stuck loading state
 * - CRITICAL FIX: Watchdog now detects loadingText ("Загрузка...") in button HTML
 * - CRITICAL FIX: Forces button('reset') if loading stuck > 8s
 * - Added global $.ajaxSetup with timeout:15000 for all OCFilter AJAX requests
 * - Improved stuck detection: checks both ocf-disabled class AND loadingText content
 * - Added direct AJAX interceptor via $.ajaxPrefilter to ensure timeout
 */
(function() {
  'use strict';

  // =============================================
  // Device detection
  // =============================================
  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isYandexBrowser = /YaBrowser/.test(ua);
  var isMobile = window.innerWidth <= 1024 || /Mobi|Android|iPhone|iPad|iPod/.test(ua);

  var DELAYS = isIOS
    ? [100, 300, 700, 1200, 2000, 3000, 5000, 7000]
    : [200, 500, 1200, 2500, 5000];

  console.log('[OCFilter v7.0] iOS='+isIOS+' Yandex='+isYandexBrowser+' Mobile='+isMobile);

  // =============================================
  // CRITICAL: Set jQuery AJAX timeout (run early, retry until jQuery loads)
  // =============================================
  function installAjaxTimeout() {
    if (typeof jQuery === 'undefined') {
      setTimeout(installAjaxTimeout, 100);
      return;
    }
    // Set global timeout for ALL ajax requests - prevents stuck loading on iOS
    jQuery.ajaxSetup({ timeout: 15000 });

    // Extra: intercept OCFilter AJAX calls and add cache-busting + timeout
    jQuery.ajaxPrefilter(function(options, originalOptions, jqXHR) {
      if (options.url && options.url.indexOf('ocfilter') !== -1) {
        if (!options.timeout || options.timeout < 10000) {
          options.timeout = 15000;
        }
        // Add cache buster for iOS Safari ITP
        var sep = options.url.indexOf('?') !== -1 ? '&' : '?';
        if (options.url.indexOf('_ios_t=') === -1) {
          options.url += sep + '_ios_t=' + Math.floor(Date.now() / 30000);
        }
        console.log('[OCFilter v7.0] AJAX intercepted:', options.url.slice(0, 100));
      }
    });

    console.log('[OCFilter v7.0] jQuery AJAX timeout=15s installed');
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
  // Get ocfilter.js absolute URL from page
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
    if (typeof $.fn.ocfilter !== 'undefined') {
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
        console.warn('[OCFilter v7.0] reinit error:', e);
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
  // CRITICAL: Watchdog - detect and fix stuck loading button
  // =============================================
  function installWatchdog() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    // Monitor for stuck loading state every 2 seconds
    var stuckWatchInterval = setInterval(function() {
      $('[data-ocf="button"]').each(function() {
        var $b = $(this);
        var html = ($b.html() || '').toLowerCase();
        var isDisabled = $b.hasClass('ocf-disabled') || $b.attr('disabled');
        var isLoading = html.indexOf('fa-spin') !== -1 ||
                        html.indexOf('загрузка') !== -1 ||
                        html.indexOf('loading') !== -1;

        if (isDisabled && isLoading) {
          // Check how long it's been stuck
          var stuckSince = $b.data('stuck-since');
          if (!stuckSince) {
            $b.data('stuck-since', Date.now());
            console.log('[OCFilter v7.0] Watchdog: detected stuck loading button');
          } else if (Date.now() - stuckSince > 8000) {
            // Stuck for > 8 seconds - force reset
            console.warn('[OCFilter v7.0] Watchdog: forcing button reset after 8s stuck');
            $b.removeData('stuck-since');

            // Use ocfButton plugin reset if available
            var pluginKey = 'ocf.button';
            var pluginData = $b.data(pluginKey);
            if (pluginData && typeof pluginData.setState === 'function') {
              pluginData.isLoading = false;
              pluginData.isLoading = false;
            }

            // Force DOM reset
            $b.removeClass('ocf-disabled').removeAttr('disabled').prop('disabled', false);
            var resetText = $b.data('resetText');
            if (resetText) {
              $b.html(resetText);
            } else {
              // Try to get textSelect from config
              var cfg = window['_ocf_config_1'];
              if (cfg && cfg.textSelect) {
                $b.html(cfg.textSelect);
              }
            }
          }
        } else {
          // Not stuck - clear timer
          $b.removeData('stuck-since');
        }
      });
    }, 2000);

    $(document).off('click.ocfw touchend.ocfw').on(
      'click.ocfw touchend.ocfw',
      '[data-ocf="button"], [data-ocf-discard], [data-filter-key], .ocf-btn-mobile-fixed, [data-ocf="mobile"]',
      function(e) {
        var $btn = $(e.currentTarget);

        // Special handling for mobile open button
        if ($btn.attr('data-ocf') === 'mobile' || $btn.closest('.ocf-btn-mobile-fixed').length) {
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
      }
    );
  }

  // =============================================
  // iOS touch trigger: reinit on first filter touch
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
          inOCF = true;
          break;
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
    installAjaxTimeout(); // CRITICAL: must be first

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
        e.preventDefault();
        e.stopImmediatePropagation();
        window.location.href = firstLink.href;
        return;
      }

      var anyLink = document.querySelector('.ds-menu-catalog-item a[href]');
      if (anyLink) {
        e.preventDefault();
        e.stopImmediatePropagation();
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

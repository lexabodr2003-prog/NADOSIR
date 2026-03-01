/**
 * OCFilter mobile fix v8.0
 * Fixes for: iOS Safari 15+, iOS Yandex Browser, Opera iOS, Android
 *
 * v8.0 Changes vs v7.1:
 * - REMOVED: XHR+eval loading (was causing iOS CSP issues and double-init)
 * - REMOVED: loadOCFilterScript (ocfilter.js loads normally via <script> tag in HTML)
 * - SIMPLIFIED: initModules just calls .ocfilter(cfg) if not yet initialized
 * - KEPT: jQuery.ajaxSetup timeout=15000 (safe, global)
 * - KEPT: Watchdog for stuck loading button (resets after 8s)
 * - KEPT: visibilitychange / bfcache / pageshow handling
 * - ADDED: Detailed console.log for iOS diagnostics
 * - FIXED: No more double-initialization that was breaking iOS filter
 */
(function() {
  'use strict';

  var VERSION = 'v8.0';
  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isYandexBrowser = /YaBrowser/.test(ua);
  var isAndroid = /Android/.test(ua);
  var isMobile = isIOS || isAndroid || window.innerWidth <= 1024;

  console.log('[OCFilter ' + VERSION + '] Start. iOS=' + isIOS +
    ' Yandex=' + isYandexBrowser +
    ' Android=' + isAndroid +
    ' Mobile=' + isMobile +
    ' UA=' + ua.slice(0, 80));

  // =============================================
  // Check if any OCFilter module needs initialization
  // =============================================
  function needsInit() {
    if (typeof jQuery === 'undefined') return false;
    var modules = document.querySelectorAll('[id^="ocf-module-"]');
    if (!modules.length) {
      console.log('[OCFilter ' + VERSION + '] needsInit: no ocf-module elements found');
      return false;
    }
    for (var i = 0; i < modules.length; i++) {
      var data = jQuery(modules[i]).data('ocfilter');
      if (!data) {
        console.log('[OCFilter ' + VERSION + '] needsInit: module #' + modules[i].id + ' NOT initialized');
        return true;
      }
    }
    console.log('[OCFilter ' + VERSION + '] needsInit: all modules already initialized');
    return false;
  }

  // =============================================
  // Initialize OCFilter modules using saved config
  // NOTE: Does NOT reload ocfilter.js - assumes it's already in DOM
  // =============================================
  function initModules() {
    if (typeof jQuery === 'undefined') {
      console.warn('[OCFilter ' + VERSION + '] initModules: jQuery not available');
      return false;
    }
    var $ = jQuery;

    if (typeof $.fn.ocfilter === 'undefined') {
      console.warn('[OCFilter ' + VERSION + '] initModules: $.fn.ocfilter not defined, skipping');
      return false;
    }

    var modules = $('[id^="ocf-module-"]');
    if (!modules.length) {
      console.warn('[OCFilter ' + VERSION + '] initModules: no modules found');
      return false;
    }

    var anyInited = false;
    modules.each(function() {
      var $el = $(this);
      if ($el.data('ocfilter')) {
        console.log('[OCFilter ' + VERSION + '] initModules: ' + $el.attr('id') + ' already has data, skip');
        return;
      }

      var idx = ($el.attr('id') || '').replace('ocf-module-', '') || '1';
      var cfg = window['_ocf_config_' + idx];

      console.log('[OCFilter ' + VERSION + '] initModules: initializing #' + $el.attr('id') +
        ' idx=' + idx + ' cfg=' + (cfg ? 'found' : 'missing'));

      try {
        if (cfg && cfg.index) {
          $el.ocfilter(cfg);
          console.log('[OCFilter ' + VERSION + '] initModules: SUCCESS with cfg');
          anyInited = true;
        } else {
          $el.ocfilter({ index: idx });
          console.log('[OCFilter ' + VERSION + '] initModules: SUCCESS with default idx');
          anyInited = true;
        }
      } catch(e) {
        console.warn('[OCFilter ' + VERSION + '] initModules: ERROR:', e.message || e);
      }
    });

    return anyInited;
  }

  // =============================================
  // Retry initialization with delays
  // =============================================
  var DELAYS = isIOS
    ? [200, 500, 1000, 2000, 4000]
    : [300, 700, 1500, 3000];

  function tryInitSequence(delayIdx) {
    delayIdx = delayIdx || 0;
    if (delayIdx >= DELAYS.length) {
      console.warn('[OCFilter ' + VERSION + '] tryInitSequence: exhausted all retries');
      return;
    }

    setTimeout(function() {
      if (!needsInit()) return;

      console.log('[OCFilter ' + VERSION + '] tryInitSequence: attempt ' + (delayIdx + 1) + '/' + DELAYS.length);

      var ok = initModules();
      if (ok) {
        console.log('[OCFilter ' + VERSION + '] tryInitSequence: init successful on attempt ' + (delayIdx + 1));
      } else if (delayIdx < DELAYS.length - 1) {
        tryInitSequence(delayIdx + 1);
      }
    }, DELAYS[delayIdx]);
  }

  // =============================================
  // Set global jQuery AJAX timeout
  // =============================================
  function installAjaxTimeout() {
    if (typeof jQuery === 'undefined') {
      setTimeout(installAjaxTimeout, 200);
      return;
    }
    jQuery.ajaxSetup({ timeout: 15000 });
    console.log('[OCFilter ' + VERSION + '] jQuery AJAX timeout=15s installed');

    // Extra console.log for AJAX debugging on iOS
    if (isMobile) {
      jQuery(document).ajaxError(function(event, jqXHR, settings, thrownError) {
        console.error('[OCFilter ' + VERSION + '] AJAX ERROR:',
          'url=' + (settings.url || '').slice(0, 80),
          'status=' + jqXHR.status,
          'error=' + thrownError,
          'timeout=' + (thrownError === 'timeout' ? 'YES' : 'no'));
      });
      console.log('[OCFilter ' + VERSION + '] AJAX error logger installed');
    }
  }

  // =============================================
  // Watchdog: detect stuck loading button
  // Resets after 8 seconds of stuck state
  // =============================================
  function installWatchdog() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    console.log('[OCFilter ' + VERSION + '] Watchdog: starting (interval 2s)');

    setInterval(function() {
      $('[data-ocf="button"]').each(function() {
        var $b = $(this);
        var html = ($b.html() || '').toLowerCase();
        var isDisabled = $b.hasClass('ocf-disabled') || !!$b.attr('disabled');
        // Detect loading state: spinner icon or loading text
        var isLoading = html.indexOf('fa-spin') !== -1 ||
                        html.indexOf('загрузк') !== -1 ||
                        html.indexOf('loading') !== -1;

        if (isDisabled && isLoading) {
          var stuckSince = $b.data('_ocf_stuck_since');
          if (!stuckSince) {
            $b.data('_ocf_stuck_since', Date.now());
            console.warn('[OCFilter ' + VERSION + '] Watchdog: stuck loading detected on button');
          } else {
            var elapsed = Date.now() - stuckSince;
            if (elapsed > 8000) {
              console.warn('[OCFilter ' + VERSION + '] Watchdog: force-reset after ' + Math.round(elapsed/1000) + 's');
              $b.removeData('_ocf_stuck_since');
              // Reset plugin internal isLoading flag
              var pd = $b.data('ocf.button');
              if (pd) {
                pd.isLoading = false;
                console.log('[OCFilter ' + VERSION + '] Watchdog: plugin isLoading reset to false');
              }
              // Reset DOM state
              $b.removeClass('ocf-disabled').removeAttr('disabled').prop('disabled', false);
              // Restore button text
              var rt = $b.data('resetText');
              if (rt) {
                $b.html(rt);
                console.log('[OCFilter ' + VERSION + '] Watchdog: restored resetText');
              } else {
                var cfg = window['_ocf_config_1'];
                if (cfg && cfg.textSelect) {
                  $b.html(cfg.textSelect);
                  console.log('[OCFilter ' + VERSION + '] Watchdog: restored textSelect from cfg');
                }
              }
            } else {
              console.log('[OCFilter ' + VERSION + '] Watchdog: stuck for ' + Math.round(elapsed/1000) + 's (max 8s)');
            }
          }
        } else {
          if ($b.data('_ocf_stuck_since')) {
            $b.removeData('_ocf_stuck_since');
          }
        }
      });
    }, 2000);

    // Mobile button click - check if filter panel opened properly
    $(document).off('click.ocfw touchend.ocfw').on(
      'click.ocfw touchend.ocfw',
      '[data-ocf="mobile"], .ocf-btn-mobile-fixed',
      function() {
        console.log('[OCFilter ' + VERSION + '] Mobile button clicked');
        setTimeout(function() {
          if (needsInit()) {
            console.log('[OCFilter ' + VERSION + '] Mobile button: filter not initialized, retrying');
            tryInitSequence(0);
          }
        }, 800);
      }
    );
  }

  // =============================================
  // visibilitychange handler (tab switching)
  // =============================================
  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
      console.log('[OCFilter ' + VERSION + '] visibilitychange: visible, checking init');
      if (needsInit()) {
        setTimeout(function() { tryInitSequence(0); }, 300);
      }
    }
  });

  // =============================================
  // pageshow handler (BFCache / back-forward)
  // =============================================
  window.addEventListener('pageshow', function(e) {
    var fromBFCache = e.persisted;
    var navEntry = (window.performance && performance.getEntriesByType) ?
      performance.getEntriesByType('navigation')[0] : null;
    var isBackForward = navEntry && navEntry.type === 'back_forward';

    console.log('[OCFilter ' + VERSION + '] pageshow: persisted=' + fromBFCache +
      ' backForward=' + isBackForward);

    if (fromBFCache || isBackForward) {
      console.log('[OCFilter ' + VERSION + '] pageshow: BFCache restore detected, re-initializing');
      tryInitSequence(0);
      setTimeout(installWatchdog, 500);
    } else {
      // Normal page load - check after a short delay
      setTimeout(function() {
        if (needsInit()) {
          console.log('[OCFilter ' + VERSION + '] pageshow: normal load, filter needs init');
          tryInitSequence(0);
        }
      }, isIOS ? 600 : 400);
    }
  });

  // =============================================
  // Main startup
  // =============================================
  function start() {
    console.log('[OCFilter ' + VERSION + '] start() called');
    installAjaxTimeout();

    // Wait for jQuery and $.fn.ocfilter to be ready, then check init
    var checkTries = 0;
    var checkInterval = setInterval(function() {
      checkTries++;
      if (typeof jQuery !== 'undefined' && typeof jQuery.fn.ocfilter !== 'undefined') {
        clearInterval(checkInterval);
        console.log('[OCFilter ' + VERSION + '] jQuery + ocfilter plugin ready after ' +
          (checkTries * 100) + 'ms');
        if (needsInit()) {
          tryInitSequence(0);
        }
        // Install watchdog after jQuery is available
        setTimeout(function() { installWatchdog(); }, 500);
      } else if (checkTries > 300) {
        clearInterval(checkInterval);
        console.warn('[OCFilter ' + VERSION + '] Timeout waiting for jQuery/ocfilter after 30s');
        // Still install watchdog
        if (typeof jQuery !== 'undefined') {
          setTimeout(function() { installWatchdog(); }, 200);
        }
      }
    }, 100);
  }

  // =============================================
  // Also run on window.load for late-loading scripts
  // =============================================
  window.addEventListener('load', function() {
    setTimeout(function() {
      if (typeof jQuery !== 'undefined') {
        installAjaxTimeout();
        if (needsInit()) {
          console.log('[OCFilter ' + VERSION + '] window.load: filter needs init');
          tryInitSequence(0);
        }
      }
    }, isIOS ? 500 : 300);
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
        window.location.href = firstLink.href;
        return;
      }
      var anyLink = document.querySelector('.ds-menu-catalog-item a[href]');
      if (anyLink) {
        e.preventDefault(); e.stopImmediatePropagation();
        window.location.href = anyLink.href;
      }
    }, true);

    console.log('[OCFilter ' + VERSION + '] catalog button handler installed');
  }

  // Start on DOMContentLoaded or immediately
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      start();
      initCatalogButton();
    });
  } else {
    start();
    initCatalogButton();
  }

})();

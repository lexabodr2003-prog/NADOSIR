/**
 * OCFilter mobile fix v10.0
 * Fixes for: iOS Safari 15+, iOS Yandex Browser, Opera iOS, Android
 *
 * v10.0 Changes vs v9.0:
 * - CRITICAL FIX: OCFilter button onclick redirect fix for iOS
 *   On iOS Safari, dynamically added onclick via jQuery .attr('onclick','location=...')
 *   does NOT fire on tap. This version intercepts button tap and performs navigation.
 * - ADDED: installOCFilterButtonFix() - intercepts button taps and reads stored href
 * - KEPT: All v9.0 fixes (touchend->click, watchdog, BFCache, AJAX timeout)
 */
(function() {
  'use strict';

  var VERSION = 'v10.0';
  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isYandexBrowser = /YaBrowser/.test(ua);
  var isOperaMini = /OPR\/|Opera Mini|OPiOS/.test(ua);
  var isAndroid = /Android/.test(ua);
  var isMobile = isIOS || isAndroid || window.innerWidth <= 1024;

  console.log('[OCFilter ' + VERSION + '] Start. iOS=' + isIOS +
    ' Yandex=' + isYandexBrowser +
    ' Opera=' + isOperaMini +
    ' Android=' + isAndroid +
    ' Mobile=' + isMobile);

  // =============================================
  // CRITICAL iOS FIX: touchend->click delegation for OCFilter
  // iOS Safari ignores clicks on non-interactive elements (div, span)
  // unless they have cursor:pointer OR touchend handler on same element.
  // OCFilter uses jQuery click() on parent container with event delegation.
  // On iOS/Yandex, touchend does not propagate properly to delegated handlers.
  // Solution: add explicit touchstart (empty) and touchend->click polyfill.
  // =============================================
  function installTouchClickFix() {
    if (!isMobile) return;
    if (typeof document.addEventListener === 'undefined') return;

    var OCF_SELECTORS = [
      '.ocf-value-item',
      '.ocf-value',
      '[data-value-id]',
      '[data-ocf="expand"]',
      '[data-ocf="specify"]',
      '[data-ocf="mobile"]',
      '[data-ocf-discard]',
      '.ocf-search-btn',
      '.ocf-search-btn-static',
      '.ocf-search-btn-popover',
      '[data-ocf="button"]',
      '.ocf-btn',
      '.ocf-collapse-trigger'
    ];

    var touchMoved = false;
    var touchStartX = 0;
    var touchStartY = 0;

    // touchstart: remember position to detect scroll vs tap
    document.addEventListener('touchstart', function(e) {
      var target = e.target;
      if (!target) return;
      var ocfEl = target.closest ? target.closest(OCF_SELECTORS.join(', ')) : null;
      if (!ocfEl) return;

      touchMoved = false;
      touchStartX = e.touches[0] ? e.touches[0].clientX : 0;
      touchStartY = e.touches[0] ? e.touches[0].clientY : 0;
      // Empty touchstart removes 300ms iOS delay
    }, { passive: true, capture: true });

    // touchmove: detect scroll
    document.addEventListener('touchmove', function(e) {
      if (!touchMoved) {
        var dx = Math.abs((e.touches[0] ? e.touches[0].clientX : 0) - touchStartX);
        var dy = Math.abs((e.touches[0] ? e.touches[0].clientY : 0) - touchStartY);
        if (dx > 10 || dy > 10) {
          touchMoved = true;
        }
      }
    }, { passive: true, capture: true });

    // touchend: if no movement -> fire click
    document.addEventListener('touchend', function(e) {
      if (touchMoved) return;

      var target = e.target;
      if (!target) return;

      var ocfEl = target.closest ? target.closest(OCF_SELECTORS.join(', ')) : null;
      if (!ocfEl) return;

      // Links and buttons have native click support
      if (ocfEl.tagName === 'A' || ocfEl.tagName === 'BUTTON') return;

      // Prevent ghost click after 300ms
      e.preventDefault();

      // Fire jQuery click in next tick
      setTimeout(function() {
        if (typeof jQuery !== 'undefined') {
          jQuery(ocfEl).trigger('click');
          console.log('[OCFilter ' + VERSION + '] touchend->click fired on ' + (ocfEl.className || ocfEl.tagName));
        } else {
          ocfEl.click();
        }
      }, 0);
    }, { passive: false, capture: true });

    console.log('[OCFilter ' + VERSION + '] touchend->click polyfill installed for iOS');
  }


  // =============================================
  // CRITICAL iOS FIX: OCFilter button redirect
  // On iOS Safari, .attr('onclick', 'location=...') set by OCFilter's search()
  // method does NOT fire on tap. This fix intercepts click/touchend on the button
  // and manually reads its onclick string to navigate.
  // Root cause: ocfilter.js line ~665:
  //   that.$button.attr('onclick', 'location = \'' + json.href + '\'');
  // iOS WebKit bug: dynamically set onclick strings are ignored for touch events.
  // =============================================
  function installOCFilterButtonFix() {
    if (typeof document.addEventListener === 'undefined') return;

    var lastTouchEndTime = 0;

    function getButtonHref($btn) {
      // Method 1: data attribute we set ourselves
      var href = $btn.data('_ocf_href');
      if (href) return href;
      
      // Method 2: parse onclick attribute string
      var onclickStr = $btn.attr('onclick') || '';
      if (onclickStr) {
        var match = onclickStr.match(/location\s*=\s*['"]([^'"]+)['"]/);
        if (match && match[1]) return match[1];
      }
      
      // Method 3: href attribute
      var hrefAttr = $btn.attr('href');
      if (hrefAttr && hrefAttr !== '#' && hrefAttr !== 'javascript:void(0)') return hrefAttr;
      
      return null;
    }

    // Monitor OCFilter button changes - store href in data attribute
    function monitorButtonHref() {
      if (typeof jQuery === 'undefined') return;
      var $ = jQuery;
      
      // Override jQuery attr to intercept onclick='location=...' 
      var originalAttr = $.fn.attr;
      $.fn.attr = function() {
        var result = originalAttr.apply(this, arguments);
        // When setting onclick with location=...
        if (arguments.length >= 2 && arguments[0] === 'onclick') {
          var val = arguments[1] || '';
          var match = val.match ? val.match(/location\s*=\s*['"]([^'"]+)['"]/): null;
          if (match && match[1]) {
            this.data('_ocf_href', match[1]);
            console.log('[OCFilter v10.0] Button href stored:', match[1].slice(0, 80));
          }
        }
        return result;
      };
      console.log('[OCFilter v10.0] jQuery.fn.attr override installed');
    }

    // Handle button tap on iOS
    document.addEventListener('touchend', function(e) {
      if (Date.now() - lastTouchEndTime < 300) return; // debounce
      
      var target = e.target;
      if (!target) return;
      
      // Find OCFilter button
      var btn = null;
      if (typeof jQuery !== 'undefined') {
        var $target = jQuery(target);
        var $btn = $target.closest('[data-ocf="button"]');
        if ($btn.length) btn = $btn;
      }
      
      if (!btn) return;
      
      var href = getButtonHref(btn);
      if (!href) return;
      
      // Check button is not disabled/loading
      var isDisabled = btn.hasClass('ocf-disabled') || btn.prop('disabled');
      if (isDisabled) return;
      
      lastTouchEndTime = Date.now();
      
      console.log('[OCFilter v10.0] Button touchend -> navigate to:', href.slice(0, 80));
      e.preventDefault();
      e.stopPropagation();
      
      // Navigate
      window.location.href = href;
      
    }, { passive: false, capture: true });

    // Also fix click event (for desktop and some iOS cases)
    document.addEventListener('click', function(e) {
      var target = e.target;
      if (!target || typeof jQuery === 'undefined') return;
      
      var $btn = jQuery(target).closest('[data-ocf="button"]');
      if (!$btn.length) return;
      
      var href = getButtonHref($btn);
      if (!href) return;
      
      var isDisabled = $btn.hasClass('ocf-disabled') || $btn.prop('disabled');
      if (isDisabled) return;
      
      console.log('[OCFilter v10.0] Button click -> navigate to:', href.slice(0, 80));
      e.preventDefault();
      e.stopPropagation();
      
      window.location.href = href;
      
    }, { capture: true });

    // Start monitoring after jQuery loads
    var monitorAttempts = 0;
    var monitorInterval = setInterval(function() {
      monitorAttempts++;
      if (typeof jQuery !== 'undefined') {
        clearInterval(monitorInterval);
        monitorButtonHref();
      } else if (monitorAttempts > 100) {
        clearInterval(monitorInterval);
      }
    }, 100);

    console.log('[OCFilter v10.0] OCFilter button redirect fix installed');
  }

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

    if (isMobile) {
      jQuery(document).ajaxError(function(event, jqXHR, settings, thrownError) {
        console.error('[OCFilter ' + VERSION + '] AJAX ERROR:',
          'url=' + (settings.url || '').slice(0, 80),
          'status=' + jqXHR.status,
          'error=' + thrownError);
      });
    }
  }

  // =============================================
  // Watchdog: detect stuck loading button (8s timeout)
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
        var isLoading = html.indexOf('fa-spin') !== -1 ||
                        html.indexOf('loading') !== -1;

        if (isDisabled && isLoading) {
          var stuckSince = $b.data('_ocf_stuck_since');
          if (!stuckSince) {
            $b.data('_ocf_stuck_since', Date.now());
            console.warn('[OCFilter ' + VERSION + '] Watchdog: stuck loading detected');
          } else {
            var elapsed = Date.now() - stuckSince;
            if (elapsed > 8000) {
              console.warn('[OCFilter ' + VERSION + '] Watchdog: force-reset after ' + Math.round(elapsed/1000) + 's');
              $b.removeData('_ocf_stuck_since');
              var pd = $b.data('ocf.button');
              if (pd) pd.isLoading = false;
              $b.removeClass('ocf-disabled').removeAttr('disabled').prop('disabled', false);
              var rt = $b.data('resetText');
              if (rt) {
                $b.html(rt);
              } else {
                var cfg = window['_ocf_config_1'];
                if (cfg && cfg.textSelect) $b.html(cfg.textSelect);
              }
            }
          }
        } else {
          if ($b.data('_ocf_stuck_since')) $b.removeData('_ocf_stuck_since');
        }
      });
    }, 2000);

    $(document).off('click.ocfw touchend.ocfw').on(
      'click.ocfw touchend.ocfw',
      '[data-ocf="mobile"], .ocf-btn-mobile-fixed',
      function() {
        console.log('[OCFilter ' + VERSION + '] Mobile filter button clicked');
        setTimeout(function() {
          if (needsInit()) tryInitSequence(0);
        }, 800);
      }
    );
  }

  // =============================================
  // visibilitychange handler
  // =============================================
  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
      console.log('[OCFilter ' + VERSION + '] visibilitychange: visible');
      if (needsInit()) setTimeout(function() { tryInitSequence(0); }, 300);
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
      console.log('[OCFilter ' + VERSION + '] pageshow: BFCache restore, re-initializing');
      tryInitSequence(0);
      setTimeout(installWatchdog, 500);
    } else {
      setTimeout(function() {
        if (needsInit()) {
          console.log('[OCFilter ' + VERSION + '] pageshow: filter needs init');
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
    installTouchClickFix();
    installOCFilterButtonFix();

    var checkTries = 0;
    var checkInterval = setInterval(function() {
      checkTries++;
      if (typeof jQuery !== 'undefined' && typeof jQuery.fn.ocfilter !== 'undefined') {
        clearInterval(checkInterval);
        console.log('[OCFilter ' + VERSION + '] jQuery + ocfilter plugin ready after ' +
          (checkTries * 100) + 'ms');
        if (needsInit()) tryInitSequence(0);
        setTimeout(function() { installWatchdog(); }, 500);
      } else if (checkTries > 300) {
        clearInterval(checkInterval);
        console.warn('[OCFilter ' + VERSION + '] Timeout waiting for jQuery/ocfilter after 30s');
        if (typeof jQuery !== 'undefined') setTimeout(function() { installWatchdog(); }, 200);
      }
    }, 100);
  }

  // =============================================
  // window.load fallback
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

  // =============================================
  // Start
  // =============================================
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

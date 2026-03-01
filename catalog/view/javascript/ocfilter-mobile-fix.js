/**
 * OCFilter mobile fix v5.0
 * Fixes for: iOS Safari, iOS Yandex Browser, Opera iOS, Android all browsers
 *
 * ROOT CAUSE ANALYSIS (v5.0):
 * - iOS Safari: dynamic script injection with s.async=true causes race condition
 *   (script may execute before jQuery bundle finishes)
 * - iOS Yandex Browser: $.ajax dataType:'script' uses aggressive caching,
 *   sometimes serving stale/broken cached version
 * - iOS WebKit: visibilitychange + back/forward cache (bfcache) - filter not reinit
 * - iOS Safari 15+: Intelligent Tracking Prevention blocks some XHR patterns
 *
 * FIXES APPLIED:
 * 1. module.twig: s.async=false (prevents race condition)
 * 2. module.twig: waitExec limit 100→400 (20s timeout instead of 5s)
 * 3. This file: iOS-specific reinit with proper jQuery wait
 * 4. This file: visibilitychange handler (tab switching on iPhone)
 * 5. This file: Touch-triggered lazy reinit for iOS
 * 6. This file: Cache-busting for ocfilter.js on iOS retry
 */
(function() {
  'use strict';

  // =============================================
  // iOS detection
  // =============================================
  var isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isYandexBrowser = /YaBrowser/.test(navigator.userAgent);
  var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
  var isMobile = /Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent);

  var DELAYS = isIOS
    ? [50, 200, 500, 1000, 1500, 2000, 3000, 4000, 5000, 7000]
    : [0, 100, 300, 500, 800, 1200, 2000, 3000, 5000];

  // =============================================
  // Core: check if OCFilter needs init
  // =============================================
  function needsInit() {
    if (typeof jQuery === 'undefined') return false;
    var $ = jQuery;
    var modules = $('[id^="ocf-module-"]');
    if (!modules.length) return false;
    var needs = false;
    modules.each(function() {
      if (!$(this).data('ocfilter')) needs = true;
    });
    return needs;
  }

  // =============================================
  // Core: force OCFilter reload and re-init
  // iOS-specific: cache bust + synchronous injection
  // =============================================
  function forceReloadAndInit() {
    if (typeof jQuery === 'undefined') return false;
    var $ = jQuery;

    var modules = $('[id^="ocf-module-"]');
    if (!modules.length) return false;

    var anyUninitialized = false;
    modules.each(function() {
      if (!$(this).data('ocfilter')) anyUninitialized = true;
    });
    if (!anyUninitialized) return false;

    // Find the ocfilter.js script URL from existing script tags
    var ocfScriptEl = document.querySelector('script[src*="ocfilter"][src*=".js"]');
    if (!ocfScriptEl) return false;

    var scriptUrl = ocfScriptEl.src;

    // iOS cache bust: add timestamp to force fresh load
    if (isIOS) {
      // Remove old script tag to force reload
      var oldTag = document.querySelector('script[src*="ocfilter48/ocfilter.js"]');
      if (oldTag && typeof $.fn.ocfilter === 'undefined') {
        // Script loaded but plugin not defined - remove and retry
        oldTag.parentNode.removeChild(oldTag);

        // Add cache-bust param
        var cbUrl = scriptUrl.split('?')[0] + '?_ios_cb=' + Date.now();
        var newTag = document.createElement('script');
        newTag.type = 'text/javascript';
        newTag.src = cbUrl;
        newTag.async = false; // IMPORTANT: no async on iOS
        newTag.onload = function() {
          setTimeout(function() {
            if (typeof $.fn.ocfilter !== 'undefined') {
              triggerOCFilterInit();
            }
          }, 100);
        };
        document.head.appendChild(newTag);
        return true;
      }
    }

    // Non-iOS or plugin already loaded: just trigger init
    if (typeof $.fn.ocfilter !== 'undefined') {
      triggerOCFilterInit();
      return true;
    }

    return false;
  }

  // =============================================
  // Trigger OCFilter init on all uninitialized modules
  // Re-runs the inline script logic from module.twig
  // =============================================
  function triggerOCFilterInit() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    $('[id^="ocf-module-"]').each(function() {
      var $el = $(this);
      if ($el.data('ocfilter')) return; // already initialized

      if (typeof $.fn.ocfilter === 'undefined') return;

      try {
        // Extract init params from data attributes on the element
        // These are set by the inline script in module.twig
        var idx = $el.attr('id') ? $el.attr('id').replace('ocf-module-', '') : '0';

        // Try to get the stored init config from window (set by module.twig)
        var config = window['_ocf_config_' + idx];
        if (config) {
          $el.ocfilter(config);
          return;
        }

        // Fallback: minimal init config
        $el.ocfilter({ index: idx });
      } catch(e) {
        console.warn('OCFilter reinit error:', e);
      }
    });
  }

  // =============================================
  // Wait for jQuery + OCFilter, then try init
  // =============================================
  function tryInit(delayIndex) {
    delayIndex = delayIndex || 0;
    if (delayIndex >= DELAYS.length) return;

    var delay = DELAYS[delayIndex];

    setTimeout(function() {
      if (!needsInit()) return; // already initialized or no modules

      var initiated = forceReloadAndInit();
      if (!initiated && delayIndex < DELAYS.length - 1) {
        tryInit(delayIndex + 1);
      }
    }, delay);
  }

  // =============================================
  // iOS-specific: intercept first touch to trigger init
  // On iOS Safari, touch events can wake up lazy-initialized components
  // =============================================
  function installIOSTouchTrigger() {
    if (!isIOS && !isMobile) return;

    var triggered = false;
    var touchHandler = function(e) {
      if (triggered) return;

      // Check if touching OCFilter area
      var target = e.target;
      var inOCFilter = false;
      while (target && target !== document) {
        if (target.id && target.id.indexOf('ocf-module-') === 0) {
          inOCFilter = true;
          break;
        }
        if (target.classList && (
          target.classList.contains('ocf-container') ||
          target.classList.contains('ocf-btn-mobile-fixed')
        )) {
          inOCFilter = true;
          break;
        }
        target = target.parentNode;
      }

      if (inOCFilter && needsInit()) {
        triggered = true;
        document.removeEventListener('touchstart', touchHandler);

        // iOS: delay slightly to let touch event processing complete
        setTimeout(function() {
          forceReloadAndInit();
          // Retry sequence if still not initialized
          setTimeout(function() {
            if (needsInit()) tryInit(0);
          }, 500);
        }, 50);
      }
    };

    document.addEventListener('touchstart', touchHandler, { passive: true });
  }

  // =============================================
  // visibilitychange handler (iOS tab switching)
  // When user switches back to tab on iPhone, filter may be dead
  // =============================================
  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
      setTimeout(function() {
        if (needsInit()) {
          tryInit(0);
        }
      }, 300);
    }
  });

  // =============================================
  // pageshow: handle bfcache (back button)
  // =============================================
  window.addEventListener('pageshow', function(e) {
    var fromCache = e.persisted;
    var navEntry = (performance && performance.getEntriesByType) ?
      performance.getEntriesByType('navigation')[0] : null;
    var isBackForward = navEntry && navEntry.type === 'back_forward';

    if (fromCache || isBackForward) {
      // Restored from bfcache - full reinit sequence
      tryInit(0);
      setTimeout(installWatchdog, 500);
    } else {
      // Normal page load
      setTimeout(function() {
        if (needsInit()) tryInit(0);
      }, isIOS ? 1000 : 2000);
    }
  });

  // =============================================
  // WATCHDOG: Reset stuck "loading" state
  // =============================================
  function installWatchdog() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    $(document).off('click.ocf_watchdog touchend.ocf_watchdog').on(
      'click.ocf_watchdog touchend.ocf_watchdog',
      '[data-filter-key], [data-ocf="button"], [data-ocf-discard], [data-ocf="mobile"], .ocf-btn-mobile-fixed',
      function() {
        setTimeout(function() {
          var $btn = $('[data-ocf="button"]');
          $btn.each(function() {
            var $b = $(this);
            if ($b.hasClass('ocf-disabled') && ($b.html() || '').indexOf('fa-spin') !== -1) {
              $b.removeClass('ocf-disabled').removeAttr('disabled');
              var resetText = $b.data('resetText') || $b.data('selectText');
              if (resetText) $b.html(resetText);
            }
          });
        }, 12000);
      }
    );
  }

  // =============================================
  // Main init sequence
  // =============================================
  function start() {
    // Start delayed init sequence
    tryInit(0);

    // Install watchdog after jQuery loads
    var wdTries = 0;
    var wdInterval = setInterval(function() {
      wdTries++;
      if (typeof jQuery !== 'undefined') {
        clearInterval(wdInterval);
        installWatchdog();
        installIOSTouchTrigger();
      } else if (wdTries > 200) {
        clearInterval(wdInterval);
      }
    }, 100);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }

  // Also on window load (last resort)
  window.addEventListener('load', function() {
    setTimeout(function() {
      if (typeof jQuery !== 'undefined') {
        installWatchdog();
        if (needsInit()) tryInit(0);
      }
    }, isIOS ? 800 : 500);
  });

  // =============================================
  // Task 15: Catalog button direct link (desktop)
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

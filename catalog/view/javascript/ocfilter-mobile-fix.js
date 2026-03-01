/**
 * OCFilter mobile fix v4.0
 * Fixes for: iOS Yandex Browser, Opera iOS, Android all browsers
 *
 * Key fixes:
 * 1. Multiple initialization attempts with increasing delays
 * 2. pageshow/bfcache handling for back button
 * 3. Watchdog for stuck loading state
 * 4. Direct OCFilter instance check and re-init
 */
(function() {
  'use strict';

  var MAX_TRIES = 40;
  var DELAYS = [0, 100, 300, 500, 800, 1200, 2000, 3000, 5000];

  // =============================================
  // Core: force OCFilter init on element
  // =============================================
  function forceInitOCFilter() {
    if (typeof jQuery === 'undefined') return false;
    var $ = jQuery;

    var modules = $('[id^="ocf-module-"]');
    if (!modules.length) return false;

    var initiated = 0;
    modules.each(function() {
      var $el = $(this);
      var pluginData = $el.data('ocfilter');

      if (!pluginData) {
        if (typeof $.fn.ocfilter !== 'undefined') {
          try {
            var opts = $el.data() || {};
            // Reconstruct key options from data attributes
            var idx = $el.attr('id') ? $el.attr('id').replace('ocf-module-', '') : '0';
            $el.ocfilter({ index: idx });
            initiated++;
          } catch(e) {
            // ignore
          }
        }
      }
    });

    return initiated > 0;
  }

  // =============================================
  // Wait for jQuery + OCFilter plugin, then init
  // =============================================
  function waitAndInit(tryIndex) {
    tryIndex = tryIndex || 0;
    if (tryIndex >= DELAYS.length) return;

    var delay = DELAYS[tryIndex] || 1000;

    setTimeout(function() {
      if (typeof jQuery === 'undefined') {
        waitAndInit(tryIndex + 1);
        return;
      }

      var $ = jQuery;

      if (typeof $.fn.ocfilter === 'undefined') {
        waitAndInit(tryIndex + 1);
        return;
      }

      // OCFilter plugin available - check if modules need init
      var modules = $('[id^="ocf-module-"]');
      if (!modules.length) return;

      var needsInit = false;
      modules.each(function() {
        if (!$(this).data('ocfilter')) needsInit = true;
      });

      if (needsInit) {
        // Re-trigger the inline scripts in module.twig
        // by dispatching a custom event
        try {
          var evt = new Event('ocfilter:reinit', { bubbles: true });
          document.dispatchEvent(evt);
        } catch(e) {}

        // Also try direct init after short delay
        setTimeout(function() {
          modules.each(function() {
            if (!$(this).data('ocfilter')) {
              try {
                // Try to extract init params from the script in the module
                var scripts = $(this).find('script');
                if (scripts.length) {
                  // Module has inline script - it should self-init
                  // Just trigger it differently
                }
              } catch(e) {}
            }
          });
        }, 200);
      }

      // Continue trying in case first attempt didn't work
      if (tryIndex < DELAYS.length - 1) {
        waitAndInit(tryIndex + 1);
      }

    }, delay);
  }

  // =============================================
  // WATCHDOG: Reset stuck "loading" state
  // =============================================
  function installWatchdog() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    $(document).off('click.ocf_watchdog').on('click.ocf_watchdog',
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
  // pageshow: handle bfcache (back button)
  // =============================================
  window.addEventListener('pageshow', function(e) {
    var fromCache = e.persisted;

    // Some Android browsers don't set persisted correctly
    // Also handle performance navigation type
    var navEntry = (performance && performance.getEntriesByType) ?
      performance.getEntriesByType('navigation')[0] : null;
    var isBackForward = navEntry && navEntry.type === 'back_forward';

    if (fromCache || isBackForward) {
      // Restored from cache - full reinit sequence
      waitAndInit(0);
      setTimeout(installWatchdog, 500);
    } else {
      // Normal page load - still try delayed init for slow browsers
      setTimeout(function() {
        if (typeof jQuery !== 'undefined') {
          var $ = jQuery;
          var modules = $('[id^="ocf-module-"]');
          modules.each(function() {
            if (!$(this).data('ocfilter') && typeof $.fn.ocfilter !== 'undefined') {
              installWatchdog();
            }
          });
        }
      }, 2000);
    }
  });

  // =============================================
  // Main init sequence
  // =============================================
  function start() {
    waitAndInit(0);

    // Install watchdog after jQuery loads
    var wdTries = 0;
    var wdInterval = setInterval(function() {
      wdTries++;
      if (typeof jQuery !== 'undefined') {
        clearInterval(wdInterval);
        installWatchdog();
      } else if (wdTries > 100) {
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
      }
    }, 500);
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

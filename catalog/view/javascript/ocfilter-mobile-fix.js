/**
 * OCFilter mobile fix v11.0
 * Fixes for: iOS Safari 15+, iOS Yandex Browser, Opera iOS, Android
 *
 * v11.0 Changes vs v10.0:
 * - CRITICAL FIX: Replaced dangerous jQuery.fn.attr override with MutationObserver
 *   The $.fn.attr override could break OCFilter's own attr() calls.
 * - ADDED: MutationObserver to watch onclick attribute changes on [data-ocf="button"]
 * - ADDED: cache:false to jQuery AJAX to prevent iOS Safari GET caching
 * - IMPROVED: More reliable button click handler with direct jQuery binding
 * - ADDED: Button retry mechanism - if button enabled but no href, re-trigger search
 * - KEPT: All v10.0 fixes (touchend->click, watchdog, BFCache, AJAX timeout)
 */
(function() {
  'use strict';

  var VERSION = 'v11.0';
  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isYandexBrowser = /YaBrowser/.test(ua);
  var isOperaMini = /OPR\/|Opera Mini|OPiOS/.test(ua);
  var isAndroid = /Android/.test(ua);
  var isMobile = isIOS || isAndroid || window.innerWidth <= 1024;

  // IMMEDIATE: Apply cache:false as soon as jQuery becomes available
  // This must happen before OCFilter's first search() AJAX call
  (function applyCacheNow() {
    if (typeof jQuery !== 'undefined') {
      jQuery.ajaxSetup({ cache: false, timeout: 15000 });
      console.log('[OCFilter v11.0] Immediate: jQuery AJAX cache=false applied');
    } else {
      // Poll every 50ms - must be fast to catch early AJAX calls
      var pollCount = 0;
      var poll = setInterval(function() {
        pollCount++;
        if (typeof jQuery !== 'undefined') {
          clearInterval(poll);
          jQuery.ajaxSetup({ cache: false, timeout: 15000 });
          console.log('[OCFilter v11.0] Immediate poll: jQuery AJAX cache=false applied after ' + pollCount*50 + 'ms');
        } else if (pollCount > 200) {
          clearInterval(poll);
        }
      }, 50);
    }
  })();

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
      '.ocf-btn',
      '.ocf-collapse-trigger'
    ];
    // NOTE: [data-ocf="button"] is excluded from this list intentionally.
    // It's handled separately by installOCFilterButtonFix.

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

      // Don't handle [data-ocf="button"] here - handled by installOCFilterButtonFix
      if (target.closest && target.closest('[data-ocf="button"]')) return;

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
  // CRITICAL iOS FIX: OCFilter search button navigation
  // 
  // Problem: OCFilter sets onclick='location=...' via jQuery .attr()
  // iOS Safari WKWebView sometimes ignores dynamically set onclick attributes.
  //
  // Solution v11.0: 
  // 1. MutationObserver watches onclick attribute on [data-ocf="button"]
  // 2. When onclick changes, store href in data attribute
  // 3. touchend on button reads stored href and navigates
  // 4. Also uses cache:false to prevent iOS GET caching
  // =============================================
  function installOCFilterButtonFix() {
    if (typeof document.addEventListener === 'undefined') return;

    var lastNavTime = 0;

    // Read href from button by multiple methods
    function getButtonHref($btn) {
      // Method 1: data attribute we set via MutationObserver
      var href = $btn.data('_ocf_href');
      if (href) return href;

      // Method 2: parse onclick attribute string directly
      var onclickStr = $btn[0] ? ($btn[0].getAttribute('onclick') || '') : '';
      if (onclickStr) {
        var match = onclickStr.match(/location\s*[=:]\s*['"]([^'"]+)['"]/);
        if (match && match[1]) return match[1];
      }

      // Method 3: href attribute (for <a> buttons)
      var hrefAttr = $btn.attr('href');
      if (hrefAttr && hrefAttr !== '#' && hrefAttr !== 'javascript:void(0)') return hrefAttr;

      return null;
    }

    // Setup MutationObserver for each button to track onclick changes
    function observeButton(btnEl) {
      if (!btnEl || btnEl._ocf_observed) return;
      btnEl._ocf_observed = true;

      if (typeof MutationObserver === 'undefined') return;

      var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
          if (mutation.type === 'attributes' && mutation.attributeName === 'onclick') {
            var onclickStr = btnEl.getAttribute('onclick') || '';
            var match = onclickStr.match(/location\s*[=:]\s*['"]([^'"]+)['"]/);
            if (match && match[1]) {
              jQuery(btnEl).data('_ocf_href', match[1]);
              console.log('[OCFilter ' + VERSION + '] MutationObserver: button href stored:', match[1].slice(0, 80));
            } else if (!onclickStr) {
              // onclick removed - clear stored href
              jQuery(btnEl).removeData('_ocf_href');
              console.log('[OCFilter ' + VERSION + '] MutationObserver: onclick removed, href cleared');
            }
          }
        });
      });

      observer.observe(btnEl, {
        attributes: true,
        attributeFilter: ['onclick', 'disabled', 'class']
      });

      console.log('[OCFilter ' + VERSION + '] MutationObserver attached to button:', btnEl.className);
    }

    // Observe all existing and future buttons
    function observeAllButtons() {
      if (typeof jQuery === 'undefined') return;
      jQuery('[data-ocf="button"]').each(function() {
        observeButton(this);
      });
    }

    // Also use MutationObserver to catch dynamically added buttons
    function observeDOM() {
      if (typeof MutationObserver === 'undefined') return;

      var domObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
          mutation.addedNodes && mutation.addedNodes.forEach && mutation.addedNodes.forEach(function(node) {
            if (node.nodeType === 1) {
              if (node.getAttribute && node.getAttribute('data-ocf') === 'button') {
                observeButton(node);
              }
              var btns = node.querySelectorAll ? node.querySelectorAll('[data-ocf="button"]') : [];
              btns.forEach && btns.forEach(function(btn) { observeButton(btn); });
            }
          });
        });
      });

      domObserver.observe(document.body || document.documentElement, {
        childList: true,
        subtree: true
      });
    }

    // Handle button tap/click
    function handleButtonActivation(e, target) {
      if (typeof jQuery === 'undefined') return false;

      var $btn = jQuery(target).closest('[data-ocf="button"]');
      if (!$btn.length) return false;

      var isDisabled = $btn.hasClass('ocf-disabled') || $btn.prop('disabled');
      if (isDisabled) {
        console.log('[OCFilter ' + VERSION + '] Button is disabled, skip navigation');
        return false;
      }

      var href = getButtonHref($btn);

      if (!href) {
        var btnText = $btn.text().trim();
        console.log('[OCFilter ' + VERSION + '] Button enabled but no href. Text:', btnText.slice(0, 40));
        
        // Try to get href from OCFilter instance params
        try {
          var ocfModule = jQuery('[id^="ocf-module-"]').first();
          if (ocfModule.length) {
            var ocfData = ocfModule.data('ocfilter');
            if (ocfData && ocfData.options) {
              var params = ocfData.getParams ? ocfData.getParams() : '';
              var cfg = window['_ocf_config_1'] || {};
              if (params && cfg.urlHost) {
                var constructedHref = cfg.urlHost + 'index.php?route=extension/module/ocfilter/search&' + 
                  cfg.paramsIndex + '=' + encodeURIComponent(params);
                // Actually, we should just re-trigger the last search
                // by triggering a click on the first selected value
                var $firstSelected = ocfModule.find('.ocf-value.ocf-selected').first();
                if ($firstSelected.length) {
                  console.log('[OCFilter ' + VERSION + '] Retrying search via click on selected value');
                  e.preventDefault();
                  e.stopPropagation();
                  setTimeout(function() { $firstSelected.trigger('click'); }, 50);
                  return true;
                }
              }
            }
          }
        } catch(err) {
          console.warn('[OCFilter ' + VERSION + '] Retry search error:', err);
        }
        
        // Button enabled but no href - let natural events handle it
        return false;
      }

      if (Date.now() - lastNavTime < 500) {
        console.log('[OCFilter ' + VERSION + '] Debounced navigation');
        return true; // prevent but don't navigate
      }

      lastNavTime = Date.now();
      e.preventDefault();
      e.stopPropagation();

      console.log('[OCFilter ' + VERSION + '] Button activated -> navigate to:', href.slice(0, 80));
      window.location.href = href;
      return true;
    }

    // touchend on button (capture phase - runs before iOS 300ms delay)
    document.addEventListener('touchend', function(e) {
      var target = e.target;
      if (!target || typeof jQuery === 'undefined') return;
      handleButtonActivation(e, target);
    }, { passive: false, capture: true });

    // click on button (fallback for desktop and some iOS cases)
    document.addEventListener('click', function(e) {
      var target = e.target;
      if (!target || typeof jQuery === 'undefined') return;
      handleButtonActivation(e, target);
    }, { capture: true });

    // Start observing buttons after jQuery loads
    var observeAttempts = 0;
    var observeInterval = setInterval(function() {
      observeAttempts++;
      if (typeof jQuery !== 'undefined' && document.body) {
        clearInterval(observeInterval);
        observeAllButtons();
        observeDOM();
        console.log('[OCFilter ' + VERSION + '] Button observers installed after ' + observeAttempts * 100 + 'ms');
      } else if (observeAttempts > 100) {
        clearInterval(observeInterval);
        console.warn('[OCFilter ' + VERSION + '] Timeout waiting for jQuery for button observers');
      }
    }, 100);

    console.log('[OCFilter ' + VERSION + '] OCFilter button navigation fix installed');
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
  // Set global jQuery AJAX: timeout + cache:false
  // cache:false is CRITICAL for iOS Safari which aggressively caches GET requests!
  // =============================================
  function installAjaxTimeout() {
    if (typeof jQuery === 'undefined') {
      setTimeout(installAjaxTimeout, 200);
      return;
    }
    jQuery.ajaxSetup({ 
      timeout: 15000,
      cache: false  // CRITICAL: prevents iOS Safari GET request caching
    });
    console.log('[OCFilter ' + VERSION + '] jQuery AJAX timeout=15s, cache=false installed');

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

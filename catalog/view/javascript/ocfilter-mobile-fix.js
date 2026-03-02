/**
 * OCFilter mobile fix v12.0
 * Fixes for: iOS Safari, iOS Yandex Browser, Opera iOS, Android
 *
 * v12.0 Changes vs v11.0 - CRITICAL FIX:
 * - ROOT CAUSE IDENTIFIED: In Yandex Browser iOS, <button> elements do NOT always
 *   generate native click events (unlike Safari). But our v11 excluded BUTTON from
 *   the touchend->click polyfill! This caused double-click on some browsers (native click
 *   + our polyfill), toggling the filter selection on/off and leaving getParams() empty.
 *
 * - FIX 1: Removed BUTTON exclusion from touchend->click polyfill.
 *   Now ALL OCFilter elements (including <button>) get touchend->click treatment.
 *
 * - FIX 2: Added per-element 400ms debounce to prevent double-click
 *   (once from our polyfill, once from native browser click).
 *   The debounce key is the element itself, not a global flag.
 *
 * - FIX 3: For the filter SELECT button specifically, touchend directly navigates
 *   to the stored href (no intermediate click needed). This avoids any interaction
 *   with OCFilter's own click handler on the button.
 *
 * - KEPT: All v11 fixes (MutationObserver, cache:false, AJAX timeout, watchdog, BFCache)
 */
(function() {
  'use strict';

  var VERSION = 'v12.0';
  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isYandexBrowser = /YaBrowser/.test(ua);
  var isOperaMini = /OPR\/|Opera Mini|OPiOS/.test(ua);
  var isAndroid = /Android/.test(ua);
  var isMobile = isIOS || isAndroid || window.innerWidth <= 1024;

  console.log('[OCFilter ' + VERSION + '] loaded. iOS=' + isIOS + ' Yandex=' + isYandexBrowser +
    ' Opera=' + isOperaMini + ' Android=' + isAndroid);

  // IMMEDIATE: Apply cache:false as soon as jQuery becomes available
  (function applyCacheNow() {
    if (typeof jQuery !== 'undefined') {
      jQuery.ajaxSetup({ cache: false, timeout: 15000 });
      console.log('[OCFilter ' + VERSION + '] jQuery AJAX cache:false applied immediately');
    } else {
      var t = 0;
      var interval = setInterval(function() {
        t += 50;
        if (typeof jQuery !== 'undefined') {
          clearInterval(interval);
          jQuery.ajaxSetup({ cache: false, timeout: 15000 });
          console.log('[OCFilter ' + VERSION + '] jQuery AJAX cache:false applied after ' + t + 'ms');
        } else if (t > 5000) {
          clearInterval(interval);
        }
      }, 50);
    }
  })();

  // =============================================
  // CRITICAL FIX v12: touchend->click for ALL OCFilter elements
  // INCLUDING <button> elements (unlike v11 which excluded them).
  //
  // WHY: Yandex Browser iOS does not reliably fire click on <button>
  //      after touchend. We must fire it ourselves.
  //
  // DEBOUNCE: 400ms per-element debounce prevents double-fire
  //           (our polyfill + native browser click = toggle on+off).
  // =============================================
  function installTouchClickFix() {
    if (!isMobile) return;
    if (typeof document.addEventListener === 'undefined') return;

    var OCF_VALUE_SELECTORS = [
      '.ocf-value',
      '.ocf-value-item',
      '[data-value-id]'
    ];

    var OCF_NAV_SELECTORS = [
      '[data-ocf="expand"]',
      '[data-ocf="specify"]',
      '[data-ocf="mobile"]',
      '[data-ocf-discard]',
      '.ocf-collapse-trigger'
    ];

    var ALL_SELECTORS = OCF_VALUE_SELECTORS.concat(OCF_NAV_SELECTORS);
    var ALL_SELECTOR_STR = ALL_SELECTORS.join(', ');

    // Per-element debounce: stores last click time per element
    var lastClickMap = typeof WeakMap !== 'undefined' ? new WeakMap() : null;
    var DEBOUNCE_MS = 400;

    function isDebounced(el) {
      if (!lastClickMap) return false;
      var last = lastClickMap.get(el) || 0;
      return (Date.now() - last) < DEBOUNCE_MS;
    }

    function markClicked(el) {
      if (lastClickMap) lastClickMap.set(el, Date.now());
    }

    var touchMoved = false;
    var touchStartX = 0;
    var touchStartY = 0;

    document.addEventListener('touchstart', function(e) {
      var target = e.target;
      if (!target) return;
      var ocfEl = target.closest ? target.closest(ALL_SELECTOR_STR) : null;
      if (!ocfEl) return;
      touchMoved = false;
      touchStartX = e.touches[0] ? e.touches[0].clientX : 0;
      touchStartY = e.touches[0] ? e.touches[0].clientY : 0;
    }, { passive: true, capture: true });

    document.addEventListener('touchmove', function(e) {
      if (!touchMoved) {
        var dx = Math.abs((e.touches[0] ? e.touches[0].clientX : 0) - touchStartX);
        var dy = Math.abs((e.touches[0] ? e.touches[0].clientY : 0) - touchStartY);
        if (dx > 10 || dy > 10) touchMoved = true;
      }
    }, { passive: true, capture: true });

    document.addEventListener('touchend', function(e) {
      if (touchMoved) return;

      var target = e.target;
      if (!target) return;

      // [data-ocf="button"] is handled by installOCFilterButtonFix - skip here
      if (target.closest && target.closest('[data-ocf="button"]')) return;

      var ocfEl = target.closest ? target.closest(ALL_SELECTOR_STR) : null;
      if (!ocfEl) return;

      // Debounce: prevent double-fire (our polyfill + native click)
      if (isDebounced(ocfEl)) {
        console.log('[OCFilter ' + VERSION + '] touchend debounced for ' + (ocfEl.className || ocfEl.tagName));
        e.preventDefault();
        return;
      }

      markClicked(ocfEl);

      // For non-interactive elements (div, span) - fire click manually
      // For <button> elements in Yandex Browser - also fire click (they may not get native click)
      if (ocfEl.tagName === 'A') return; // <a> handles itself

      // Prevent ghost click after 300ms
      e.preventDefault();

      // Fire jQuery click in next tick
      setTimeout(function() {
        if (typeof jQuery !== 'undefined') {
          jQuery(ocfEl).trigger('click');
          console.log('[OCFilter ' + VERSION + '] touchend->click: ' + (ocfEl.className || ocfEl.tagName));
        } else {
          ocfEl.click();
        }
      }, 0);

    }, { passive: false, capture: true });

    // Also intercept native click events on OCFilter values to debounce them
    // (prevent native click firing AFTER our polyfill click already ran)
    document.addEventListener('click', function(e) {
      var target = e.target;
      if (!target) return;

      // Skip non-OCFilter elements
      var ocfEl = target.closest ? target.closest(ALL_SELECTOR_STR) : null;
      if (!ocfEl) return;

      // Skip [data-ocf="button"]
      if (target.closest && target.closest('[data-ocf="button"]')) return;

      // If we just fired a synthetic click (marked < 400ms ago), stop the native click
      if (isDebounced(ocfEl)) {
        var timeSince = lastClickMap ? (Date.now() - (lastClickMap.get(ocfEl) || 0)) : 999;
        // Allow if >= 350ms have passed (user intentionally clicked again)
        if (timeSince < 350) {
          console.log('[OCFilter ' + VERSION + '] native click suppressed (debounce ' + timeSince + 'ms) on ' + (ocfEl.className || ''));
          e.stopImmediatePropagation();
          e.preventDefault();
        }
      }
    }, { capture: true });

    console.log('[OCFilter ' + VERSION + '] touchend->click polyfill v12 installed (includes BUTTON elements)');
  }


  // =============================================
  // OCFilter search button navigation fix
  // Uses MutationObserver to watch onclick attribute changes
  // =============================================
  function installOCFilterButtonFix() {
    if (typeof document.addEventListener === 'undefined') return;

    var lastNavTime = 0;

    function getButtonHref($btn) {
      var href = $btn.data('_ocf_href');
      if (href) return href;

      var onclickStr = $btn[0] ? ($btn[0].getAttribute('onclick') || '') : '';
      if (onclickStr) {
        var match = onclickStr.match(/location\s*[=:]\s*['"]([^'"]+)['"]/);
        if (match && match[1]) return match[1];
      }

      var hrefAttr = $btn.attr('href');
      if (hrefAttr && hrefAttr !== '#' && hrefAttr !== 'javascript:void(0)') return hrefAttr;

      return null;
    }

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
              console.log('[OCFilter ' + VERSION + '] MutationObserver: button href=', match[1].slice(0, 80));
            } else if (!onclickStr) {
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
    }

    function observeAllButtons() {
      if (typeof jQuery === 'undefined') return;
      jQuery('[data-ocf="button"]').each(function() { observeButton(this); });
    }

    function observeDOM() {
      if (typeof MutationObserver === 'undefined') return;
      var domObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
          mutation.addedNodes && mutation.addedNodes.forEach && mutation.addedNodes.forEach(function(node) {
            if (node.nodeType === 1) {
              if (node.getAttribute && node.getAttribute('data-ocf') === 'button') observeButton(node);
              var btns = node.querySelectorAll ? node.querySelectorAll('[data-ocf="button"]') : [];
              btns.forEach && btns.forEach(function(btn) { observeButton(btn); });
            }
          });
        });
      });
      domObserver.observe(document.body || document.documentElement, { childList: true, subtree: true });
    }

    function handleButtonActivation(e, target) {
      if (typeof jQuery === 'undefined') return false;

      var $btn = jQuery(target).closest('[data-ocf="button"]');
      if (!$btn.length) return false;

      var isDisabled = $btn.hasClass('ocf-disabled') || $btn.prop('disabled');
      if (isDisabled) return false;

      var href = getButtonHref($btn);

      if (!href) {
        // Button enabled but no href yet - AJAX might still be loading or failed
        // Try to re-trigger search by clicking the first selected value
        try {
          var $ocfModule = jQuery('[id^="ocf-module-"]').first();
          if ($ocfModule.length) {
            var $firstSelected = $ocfModule.find('.ocf-value.ocf-selected').first();
            if ($firstSelected.length) {
              console.log('[OCFilter ' + VERSION + '] Button: no href, retrying search');
              e.preventDefault();
              e.stopPropagation();
              setTimeout(function() {
                // Toggle off, then back on to force AJAX
                $firstSelected.trigger('click');
                setTimeout(function() { $firstSelected.trigger('click'); }, 200);
              }, 50);
              return true;
            }
          }
        } catch(err) {
          console.warn('[OCFilter ' + VERSION + '] Retry error:', err);
        }
        return false;
      }

      // Debounce navigation
      if (Date.now() - lastNavTime < 500) {
        console.log('[OCFilter ' + VERSION + '] Button navigation debounced');
        e.preventDefault();
        return true;
      }

      lastNavTime = Date.now();
      e.preventDefault();
      e.stopPropagation();
      console.log('[OCFilter ' + VERSION + '] Button -> navigate:', href.slice(0, 80));
      window.location.href = href;
      return true;
    }

    // touchend on button - use capture phase, before native 300ms
    document.addEventListener('touchend', function(e) {
      var target = e.target;
      if (!target || typeof jQuery === 'undefined') return;
      handleButtonActivation(e, target);
    }, { passive: false, capture: true });

    // click fallback
    document.addEventListener('click', function(e) {
      var target = e.target;
      if (!target || typeof jQuery === 'undefined') return;
      handleButtonActivation(e, target);
    }, { capture: true });

    // Setup observers
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
      }
    }, 100);
  }

  // =============================================
  // Check if OCFilter module needs initialization
  // =============================================
  function needsInit() {
    if (typeof jQuery === 'undefined') return false;
    var modules = document.querySelectorAll('[id^="ocf-module-"]');
    if (!modules.length) return false;
    for (var i = 0; i < modules.length; i++) {
      if (!jQuery(modules[i]).data('ocfilter')) return true;
    }
    return false;
  }

  // =============================================
  // Initialize OCFilter using saved config
  // =============================================
  function initModules() {
    if (typeof jQuery === 'undefined') return false;
    var $ = jQuery;
    if (typeof $.fn.ocfilter === 'undefined') return false;

    var modules = $('[id^="ocf-module-"]');
    if (!modules.length) return false;

    var anyInited = false;
    modules.each(function() {
      var $el = $(this);
      if ($el.data('ocfilter')) return;

      var idx = ($el.attr('id') || '').replace('ocf-module-', '') || '1';
      var cfg = window['_ocf_config_' + idx];

      try {
        if (cfg && cfg.index) {
          $el.ocfilter(cfg);
          console.log('[OCFilter ' + VERSION + '] init SUCCESS idx=' + idx);
          anyInited = true;
        } else {
          $el.ocfilter({ index: idx });
          anyInited = true;
        }
      } catch(e) {
        console.warn('[OCFilter ' + VERSION + '] init ERROR:', e.message || e);
      }
    });
    return anyInited;
  }

  // Retry initialization
  var DELAYS = isIOS ? [200, 500, 1000, 2000, 4000] : [300, 700, 1500, 3000];

  function tryInitSequence(delayIdx) {
    delayIdx = delayIdx || 0;
    if (delayIdx >= DELAYS.length) return;
    setTimeout(function() {
      if (!needsInit()) return;
      console.log('[OCFilter ' + VERSION + '] init attempt ' + (delayIdx + 1) + '/' + DELAYS.length);
      var ok = initModules();
      if (!ok && delayIdx < DELAYS.length - 1) tryInitSequence(delayIdx + 1);
    }, DELAYS[delayIdx]);
  }

  // =============================================
  // AJAX timeout + cache:false installation (delayed)
  // =============================================
  function installAjaxTimeout() {
    if (typeof jQuery === 'undefined') {
      setTimeout(installAjaxTimeout, 200);
      return;
    }
    jQuery.ajaxSetup({ timeout: 15000, cache: false });
    console.log('[OCFilter ' + VERSION + '] jQuery AJAX timeout=15s, cache=false');

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
  // Watchdog: reset stuck loading button after 8s
  // =============================================
  function installWatchdog() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    setInterval(function() {
      $('[data-ocf="button"]').each(function() {
        var $btn = $(this);
        var btnData = $btn.data('ocf.button');
        if (btnData && btnData.isLoading) {
          var loadTime = btnData.loadTime || 0;
          if (loadTime > 0 && (Date.now() - loadTime) > 8000) {
            console.warn('[OCFilter ' + VERSION + '] Watchdog: resetting stuck loading button');
            $btn.data('ocf.button').isLoading = false;
            $btn.removeClass('loading ocf-loading').prop('disabled', false);
            // Try to re-init
            if (needsInit()) tryInitSequence(0);
          }
        }
      });
    }, 2000);
  }

  // =============================================
  // Handle BFCache (back button navigation)
  // =============================================
  function installBFCacheHandler() {
    window.addEventListener('pageshow', function(e) {
      if (e.persisted) {
        console.log('[OCFilter ' + VERSION + '] pageshow (BFCache), re-checking OCFilter');
        setTimeout(function() {
          if (needsInit()) tryInitSequence(0);
        }, 200);
      }
    });

    document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'visible') {
        setTimeout(function() {
          if (needsInit()) {
            console.log('[OCFilter ' + VERSION + '] visibilitychange visible, re-init');
            tryInitSequence(0);
          }
        }, 300);
      }
    });
  }

  // =============================================
  // Main startup
  // =============================================
  function start() {
    installAjaxTimeout();
    installOCFilterButtonFix();
    installTouchClickFix();
    installBFCacheHandler();

    if (typeof jQuery !== 'undefined') {
      installWatchdog();
    } else {
      var wdTimer = setInterval(function() {
        if (typeof jQuery !== 'undefined') {
          clearInterval(wdTimer);
          installWatchdog();
        }
      }, 500);
    }

    // Start init sequence
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function() { tryInitSequence(0); });
    } else {
      tryInitSequence(0);
    }

    console.log('[OCFilter ' + VERSION + '] start() complete');
  }

  start();

})();

/**
 * OCFilter mobile fix v14.0 — DIRECT API APPROACH
 *
 * WHY ALL PREVIOUS VERSIONS FAILED:
 * ===================================
 * Versions 1-13 all tried to fix touch events / click polyfills.
 * But OCFilter uses jQuery event delegation:
 *   this.$element.on('click.ocf', function(e) { ... })
 * on #ocf-module-1.
 *
 * The REAL problem: on Yandex Browser iOS / Opera iOS, `<button>` elements
 * inside a scrollable container receive touchstart/touchend but the resulting
 * synthetic "click" event from the browser is dispatched to the WRONG target
 * (sometimes the span child, sometimes the container), and jQuery's event
 * delegation `$(e.target).closest('.ocf-value')` finds nothing → returns early.
 *
 * PROOF: OCFilter click handler line 304:
 *   var $value = $(e.target).closest('.ocf-value');
 *   if (!$isSet($value)) { return; }  ← exits here if target is wrong
 *
 * SOLUTION v14:
 * =============
 * Skip jQuery delegation entirely. Attach a native touchend listener
 * directly to each .ocf-value button element. On touchend, call OCFilter's
 * internal API directly using the stored instance: $module.data('ocfilter').
 *
 * This completely bypasses:
 * - jQuery event delegation issues
 * - Wrong click target problems
 * - touchend/click race conditions
 * - iOS 300ms delay
 *
 * Direct API calls:
 *   instance.addParams(filterKey, valueId)    → add filter
 *   instance.removeParams(filterKey, valueId) → remove filter
 *   instance.search($btn, filterKey)          → trigger AJAX + update button
 */
(function() {
  'use strict';

  var VERSION = 'v14.0';
  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isAndroid = /Android/.test(ua);
  var isMobile = isIOS || isAndroid ||
    ('ontouchstart' in window) ||
    (navigator.maxTouchPoints > 0);

  console.log('[OCFilter ' + VERSION + '] loaded iOS=' + isIOS +
    ' Android=' + isAndroid + ' Mobile=' + isMobile);

  if (!isMobile) {
    console.log('[OCFilter ' + VERSION + '] Desktop - no patches needed');
    return;
  }

  // ============================================================
  // 1. Patch jQuery AJAX (cache:false for iOS Safari)
  // ============================================================
  function patchAjax($) {
    $.ajaxSetup({ cache: false, timeout: 15000 });
    $(document).ajaxError(function(ev, jqXHR, settings) {
      console.error('[OCFilter ' + VERSION + '] AJAX error url=' +
        (settings.url || '').slice(0, 80) + ' status=' + jqXHR.status);
    });
    console.log('[OCFilter ' + VERSION + '] jQuery AJAX patched');
  }

  // ============================================================
  // 2. Get OCFilter instance from jQuery data
  // ============================================================
  function getInstance($module) {
    // OCFilter stores itself as $el.data('ocfilter')  [line 1094 of ocfilter.js]
    return $module.data('ocfilter') || null;
  }

  // ============================================================
  // 3. Direct OCFilter action on a button
  //    Replicate what OCFilter's click handler does (lines 303-360)
  // ============================================================
  function ocfAction($, btn) {
    var $btn = $(btn);
    var $module = $btn.closest('[id^="ocf-module-"]');
    if (!$module.length) {
      console.warn('[OCFilter ' + VERSION + '] No module found for btn:', btn.id);
      return false;
    }

    var instance = getInstance($module);
    if (!instance) {
      console.warn('[OCFilter ' + VERSION + '] No OCFilter instance on', $module.attr('id'));
      // Fallback: fire a proper jQuery click event on the module
      return fallbackClick($, btn, $module);
    }

    var filterKey = $btn.attr('data-filter-key');
    var valueId   = $btn.attr('data-value-id');

    if (!filterKey || !valueId) {
      console.warn('[OCFilter ' + VERSION + '] Missing filterKey or valueId');
      return false;
    }

    var isRadio   = $btn.hasClass('ocf-radio');
    var selClass  = 'ocf-selected';
    var $filterItem = $btn.closest('.ocf-filter');

    // Toggle selection visually (same as OCFilter lines 316-331)
    if (isRadio) {
      $filterItem.find('.ocf-value').removeClass(selClass);
      if (typeof instance.removeParams === 'function') {
        instance.removeParams(filterKey);
      }
      if (valueId !== 'all') {
        $btn.addClass(selClass);
        if (typeof instance.addParams === 'function') {
          instance.addParams(filterKey, valueId);
        }
      }
    } else {
      var wasSelected = $btn.hasClass(selClass);
      $btn.toggleClass(selClass, !wasSelected);
      if (wasSelected) {
        if (typeof instance.removeParams === 'function') {
          instance.removeParams(filterKey, valueId);
        }
      } else {
        if (typeof instance.addParams === 'function') {
          instance.addParams(filterKey, valueId);
        }
      }
    }

    // Update active label (same as OCFilter lines 333-347)
    try {
      var selecteds = [];
      $filterItem.find('.ocf-selected').each(function() {
        selecteds.push($(this).find('.ocf-value-name').text());
      });
      $filterItem.find('.ocf-active-label').html('');
      if (selecteds.length > 0) {
        var html = selecteds[0];
        if (selecteds.length > 1) {
          html += ' +<span class="ocf-more-selected">' + (selecteds.length - 1) + '</span>';
        }
        $filterItem.addClass('ocf-active').find('.ocf-active-label').html(html);
      } else {
        $filterItem.removeClass('ocf-active');
      }
    } catch(e) { /* non-critical */ }

    // Trigger search AJAX (same as OCFilter line 350)
    if (typeof instance.search === 'function') {
      instance.search($btn, filterKey);
      console.log('[OCFilter ' + VERSION + '] search() called, filterKey=' + filterKey + ' valueId=' + valueId);
      return true;
    }

    console.warn('[OCFilter ' + VERSION + '] search() method not found');
    return false;
  }

  /**
   * Fallback when OCFilter instance is not found:
   * Create a synthetic jQuery click event on the module element
   * with the correct target so OCFilter's delegated handler picks it up.
   */
  function fallbackClick($, btn, $module) {
    try {
      var fakeEvent = $.Event('click.ocf');
      fakeEvent.target = btn;
      fakeEvent.currentTarget = $module[0];
      // Dispatch on module - OCFilter's delegation handler will process it
      $module.trigger(fakeEvent);
      console.log('[OCFilter ' + VERSION + '] fallback trigger click.ocf on module');
      return true;
    } catch(e) {
      console.error('[OCFilter ' + VERSION + '] fallback failed:', e);
      return false;
    }
  }

  // ============================================================
  // 4. Attach direct touchend handler to a button
  // ============================================================
  function attachBtn($, btn) {
    if (btn._ocf14) return; // Already attached
    btn._ocf14 = true;

    var touchStartX = 0, touchStartY = 0;
    var touchMoved  = false;
    var lastFire    = 0;

    btn.addEventListener('touchstart', function(e) {
      touchMoved  = false;
      touchStartX = e.touches[0] ? e.touches[0].clientX : 0;
      touchStartY = e.touches[0] ? e.touches[0].clientY : 0;
    }, { passive: true });

    btn.addEventListener('touchmove', function() {
      touchMoved = true;
    }, { passive: true });

    btn.addEventListener('touchend', function(e) {
      if (touchMoved) return;

      var now = Date.now();
      if (now - lastFire < 400) {
        // Debounce: prevent double-fire
        e.preventDefault();
        return;
      }
      lastFire = now;

      // Prevent ghost click 300ms later
      e.preventDefault();

      var self = this;
      // Small delay to let any in-progress operations settle
      setTimeout(function() {
        waitForJQ(function($) {
          ocfAction($, self);
        });
      }, 10);

    }, { passive: false });

    // Block ghost click that may arrive ~300ms after touchend
    btn.addEventListener('click', function(e) {
      var now = Date.now();
      if (now - lastFire < 600) {
        // Ghost click - block it from reaching jQuery delegation
        e.stopImmediatePropagation();
        e.preventDefault();
      }
      // Note: if lastFire is old (>600ms), this is a real desktop click → allow
    }, { capture: true });
  }

  // ============================================================
  // 5. Scan and attach to all .ocf-value buttons
  // ============================================================
  function attachAll($) {
    var btns = document.querySelectorAll('[id^="ocf-module-"] .ocf-value');
    var n = 0;
    for (var i = 0; i < btns.length; i++) {
      if (!btns[i]._ocf14) {
        attachBtn($, btns[i]);
        n++;
      }
    }
    if (n > 0) {
      console.log('[OCFilter ' + VERSION + '] Attached to ' + n + ' new buttons');
    }
    return btns.length;
  }

  // ============================================================
  // 6. Button navigation fix (Показать N товаров)
  // ============================================================
  function installNavFix($) {
    // Watch for onclick attribute changes on [data-ocf="button"]
    function observeNavBtn(el) {
      if (el._ocf14nav) return;
      el._ocf14nav = true;

      if (typeof MutationObserver !== 'undefined') {
        new MutationObserver(function(mutations) {
          mutations.forEach(function(m) {
            if (m.attributeName === 'onclick') {
              var oc = el.getAttribute('onclick') || '';
              var match = oc.match(/location\s*[=:]\s*['"]([^'"]+)['"]/);
              if (match) {
                $(el).data('_ocf14_href', match[1]);
                console.log('[OCFilter ' + VERSION + '] Nav btn href: ' + match[1].slice(0, 60));
              } else if (!oc) {
                $(el).removeData('_ocf14_href');
              }
            }
          });
        }).observe(el, { attributes: true, attributeFilter: ['onclick', 'disabled', 'class'] });
      }
    }

    function scanNavBtns() {
      $('[data-ocf="button"]').each(function() { observeNavBtn(this); });
    }
    scanNavBtns();

    // Watch DOM for new buttons
    if (typeof MutationObserver !== 'undefined') {
      new MutationObserver(function(muts) {
        muts.forEach(function(m) {
          m.addedNodes && m.addedNodes.forEach && m.addedNodes.forEach(function(n) {
            if (n.nodeType !== 1) return;
            if (n.getAttribute && n.getAttribute('data-ocf') === 'button') observeNavBtn(n);
            var els = n.querySelectorAll ? n.querySelectorAll('[data-ocf="button"]') : [];
            els.forEach && els.forEach(observeNavBtn);
          });
        });
      }).observe(document.documentElement, { childList: true, subtree: true });
    }

    // Handle touchend on nav button
    var lastNav   = 0;
    var navMoved  = false;
    var navStartX = 0, navStartY = 0;

    document.addEventListener('touchstart', function(e) {
      if (!$(e.target).closest('[data-ocf="button"]').length) return;
      navMoved  = false;
      navStartX = e.touches[0] ? e.touches[0].clientX : 0;
      navStartY = e.touches[0] ? e.touches[0].clientY : 0;
    }, { passive: true, capture: false });

    document.addEventListener('touchmove', function(e) {
      var dx = Math.abs((e.touches[0] ? e.touches[0].clientX : 0) - navStartX);
      var dy = Math.abs((e.touches[0] ? e.touches[0].clientY : 0) - navStartY);
      if (dx > 8 || dy > 8) navMoved = true;
    }, { passive: true, capture: false });

    document.addEventListener('touchend', function(e) {
      if (navMoved) return;

      var $btn = $(e.target).closest('[data-ocf="button"]');
      if (!$btn.length) return;

      if ($btn.hasClass('ocf-disabled') || $btn.prop('disabled')) return;

      var href = $btn.data('_ocf14_href');
      if (!href) {
        var oc = $btn.attr('onclick') || '';
        var m  = oc.match(/location\s*[=:]\s*['"]([^'"]+)['"]/);
        if (m) href = m[1];
      }
      if (!href) return;

      var now = Date.now();
      if (now - lastNav < 500) return;
      lastNav = now;

      e.preventDefault();
      e.stopPropagation();
      console.log('[OCFilter ' + VERSION + '] Nav: ' + href.slice(0, 80));
      window.location.href = href;
    }, { passive: false, capture: true });

    // Click fallback for nav button
    document.addEventListener('click', function(e) {
      var $btn = $(e.target).closest('[data-ocf="button"]');
      if (!$btn.length) return;
      if ($btn.hasClass('ocf-disabled') || $btn.prop('disabled')) return;

      var href = $btn.data('_ocf14_href');
      if (!href) {
        var oc = $btn.attr('onclick') || '';
        var m  = oc.match(/location\s*[=:]\s*['"]([^'"]+)['"]/);
        if (m) href = m[1];
      }
      if (!href) return;

      var now = Date.now();
      if (now - lastNav < 500) return;
      lastNav = now;

      e.preventDefault();
      e.stopPropagation();
      window.location.href = href;
    }, { capture: true });

    console.log('[OCFilter ' + VERSION + '] Nav fix installed');
  }

  // ============================================================
  // 7. Wait for jQuery, then set up everything
  // ============================================================
  var jqCallbacks = [];
  var jqReady = false;

  function waitForJQ(cb) {
    if (jqReady && typeof jQuery !== 'undefined') {
      cb(jQuery);
      return;
    }
    jqCallbacks.push(cb);
  }

  (function pollForJQ() {
    if (typeof jQuery !== 'undefined') {
      jqReady = true;
      var $ = jQuery;
      var cbs = jqCallbacks.slice();
      jqCallbacks = [];
      cbs.forEach(function(cb) { try { cb($); } catch(e) {} });
      return;
    }
    setTimeout(pollForJQ, 50);
  })();

  // ============================================================
  // 8. Poll for OCFilter init + attach buttons periodically
  // ============================================================
  function startPolling($) {
    var tries = 0;
    var maxTries = 150; // 15 seconds

    var timer = setInterval(function() {
      tries++;

      var total = attachAll($);

      if (tries === 1 || tries % 20 === 0) {
        var $mod = $('[id^="ocf-module-"]');
        var inst = $mod.length ? getInstance($mod.first()) : null;
        console.log('[OCFilter ' + VERSION + '] poll #' + tries +
          ' modules=' + $mod.length + ' inited=' + !!inst + ' btns=' + total);
      }

      if (tries >= maxTries) {
        clearInterval(timer);
        console.log('[OCFilter ' + VERSION + '] polling stopped after ' + maxTries + ' tries');
      }
    }, 100);
  }

  // ============================================================
  // 9. BFCache support (back button)
  // ============================================================
  window.addEventListener('pageshow', function(e) {
    if (e.persisted) {
      console.log('[OCFilter ' + VERSION + '] BFCache restore - reattach');
      waitForJQ(function($) {
        setTimeout(function() { attachAll($); }, 300);
      });
    }
  });

  // ============================================================
  // 10. DOM observer for dynamically added buttons
  // ============================================================
  function installDOMWatcher($) {
    if (typeof MutationObserver === 'undefined') return;
    new MutationObserver(function(muts) {
      var needScan = false;
      muts.forEach(function(m) {
        m.addedNodes && m.addedNodes.forEach && m.addedNodes.forEach(function(n) {
          if (n.nodeType !== 1) return;
          if (n.classList && n.classList.contains('ocf-value')) needScan = true;
          if (n.querySelector && n.querySelector('.ocf-value')) needScan = true;
        });
      });
      if (needScan) attachAll($);
    }).observe(document.documentElement, { childList: true, subtree: true });
  }

  // ============================================================
  // MAIN
  // ============================================================
  waitForJQ(function($) {
    patchAjax($);
    installNavFix($);
    installDOMWatcher($);
    startPolling($);
    console.log('[OCFilter ' + VERSION + '] startup done');
  });

})();

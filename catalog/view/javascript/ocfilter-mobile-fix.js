/**
 * OCFilter mobile fix v22.0
 *
 * BUGS FIXED:
 * ===========
 * v21: After clicking "Показать" the filter panel auto-opens on page reload.
 *
 * ROOT CAUSE (v22 analysis):
 * When user opens filter panel → toggleMobile(true) → localStorage['OCf.mobile.opened'] = '1'
 * When user clicks "Показать" → mousedown → localStorage['OCf.mobile.opened'] = '-1'
 * BUT on iOS touch: touchstart fires before mousedown, navigation starts before mousedown fires!
 * → localStorage stays at '1'
 * → On reload: isMobileOpened() = ('1' > 0) = TRUE → showMobile() is called → panel auto-opens
 *
 * FIX v22:
 * A) On page load with ocf= URL params: immediately set localStorage = '-1'
 *    This prevents showMobile() from running (isMobileOpened() returns false)
 * B) Patch toggleMobile: if navigating away (button has onclick with location=), set '-1' first
 * C) Keep v21 fixes: auto-search, ghost-click protection, touch-to-click
 *
 * Also: isMobileSearched() bug: localStorage returns string '-1', not number -1
 *       '===' comparison always fails. Not our bug to fix (it's just a TODO comment anyway).
 */
(function () {
  'use strict';

  var VERSION = 'v22.0';
  var OCF_LS_KEY = 'OCf.mobile.opened';

  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) ||
    (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var isAndroid = /Android/.test(ua);
  var isMobile = isIOS || isAndroid ||
    ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

  console.log('[OCF ' + VERSION + '] loaded. iOS=' + isIOS + ' mobile=' + isMobile);

  // ─────────────────────────────────────────────────────────────
  // FIX A: Prevent auto-open on page load when ocf= params present
  // When navigating to a filtered page, suppress mobile panel auto-open
  // ─────────────────────────────────────────────────────────────
  function suppressAutoOpen() {
    if (!isMobile) return;

    // Check if current URL has filter params
    var hasParams = window.location.href.indexOf('ocf=') !== -1 ||
                    window.location.search.indexOf('ocf') !== -1;

    if (hasParams) {
      try {
        var stored = localStorage.getItem(OCF_LS_KEY);
        // If panel was open (stored = '1'), prevent auto-open by setting to '-1'
        if (stored !== null && parseInt(stored, 10) > 0) {
          localStorage.setItem(OCF_LS_KEY, '-1');
          console.log('[OCF ' + VERSION + '] suppressed auto-open (was: ' + stored + ')');
        }
      } catch (e) {
        // localStorage unavailable
      }
    }
  }

  // Run immediately before OCFilter initializes
  suppressAutoOpen();

  // ─────────────────────────────────────────────────────────────
  // FIX B: Patch toggleMobile to set '-1' before navigating
  // Also patches the button mousedown to fire on touchstart (iOS)
  // ─────────────────────────────────────────────────────────────
  function patchToggleMobile($) {
    var el = document.querySelector('[id^="ocf-module-"]');
    if (!el) return false;

    var inst = $(el).data('ocfilter');
    if (!inst) return false;

    var OCFilter = inst.constructor;
    if (!OCFilter || !OCFilter.prototype) return false;
    if (OCFilter.prototype._ocf22togglePatched) return true;
    OCFilter.prototype._ocf22togglePatched = true;

    // Patch toggleMobile: when status=false (hideMobile), ensure localStorage is updated
    var origToggle = OCFilter.prototype.toggleMobile;
    OCFilter.prototype.toggleMobile = function(status) {
      // When explicitly closing (status=false), mark as searched/closed
      if (status === false && isMobile) {
        try {
          // Check if this close is happening because user clicked "Показать"
          var $btn = this.$button;
          if ($btn && $btn.attr('onclick') && $btn.attr('onclick').indexOf('location') !== -1) {
            localStorage.setItem(OCF_LS_KEY, '-1');
            console.log('[OCF ' + VERSION + '] pre-nav: set mobile.opened=-1');
          }
        } catch(e) {}
      }
      return origToggle.apply(this, arguments);
    };

    console.log('[OCF ' + VERSION + '] toggleMobile patched');
    return true;
  }

  // ─────────────────────────────────────────────────────────────
  // FIX C: Touch-based navigation for "Показать N товаров"
  // On iOS, touchstart fires before mousedown, causing navigation
  // before mousedown sets localStorage to '-1'.
  // We intercept touchstart on the button and set '-1' immediately.
  // ─────────────────────────────────────────────────────────────
  function patchButtonTouch($) {
    if (!isMobile) return;

    // Use event delegation on the document
    if (document._ocf22btnTouchPatched) return;
    document._ocf22btnTouchPatched = true;

    document.addEventListener('touchstart', function(e) {
      var btn = e.target;
      // Walk up to find data-ocf="button"
      var el = btn;
      for (var i = 0; i < 5 && el; i++) {
        if (el.getAttribute && el.getAttribute('data-ocf') === 'button') {
          // Set localStorage immediately on touchstart
          try {
            localStorage.setItem(OCF_LS_KEY, '-1');
            console.log('[OCF ' + VERSION + '] touchstart on button: set mobile.opened=-1');
          } catch(e2) {}
          break;
        }
        el = el.parentElement;
      }
    }, { passive: true });
  }

  // ─────────────────────────────────────────────────────────────
  // FIX D: Auto-search after OCFilter init (fixes grey button on reload)
  // ─────────────────────────────────────────────────────────────
  function autoSearchOnInit($) {
    var modules = document.querySelectorAll('[id^="ocf-module-"]');
    var fired = 0;

    for (var i = 0; i < modules.length; i++) {
      var $el = $(modules[i]);
      var inst = $el.data('ocfilter');
      if (!inst) continue;
      if (inst._ocf22autoSearchDone) continue;
      inst._ocf22autoSearchDone = true;

      var params = inst.options.params || inst.getParams();
      if (params && params.length > 0) {
        console.log('[OCF ' + VERSION + '] auto-search for #' + modules[i].id +
          ' params=' + params.slice(0, 30));
        (function (instance) {
          setTimeout(function () {
            try {
              instance.search(instance.$button);
            } catch (e) {
              console.warn('[OCF ' + VERSION + '] auto-search error:', e);
            }
          }, 100);
        })(inst);
        fired++;
      }
    }
    return fired;
  }

  // ─────────────────────────────────────────────────────────────
  // FIX E: Ghost click protection (iOS only)
  // ─────────────────────────────────────────────────────────────
  function patchSearchPrototype($) {
    if (!isMobile) return true;

    var el = document.querySelector('[id^="ocf-module-"]');
    if (!el) return false;

    var inst = $(el).data('ocfilter');
    if (!inst) return false;

    var OCFilter = inst.constructor;
    if (!OCFilter || !OCFilter.prototype || !OCFilter.prototype.search) return false;
    if (OCFilter.prototype._ocf22searchPatched) return true;
    OCFilter.prototype._ocf22searchPatched = true;

    var origSearch = OCFilter.prototype.search;
    OCFilter.prototype.search = function ($value, filter_key) {
      var params = this.getParams();
      var now = Date.now();

      if (!params && this._ocf22lastTs && (now - this._ocf22lastTs < 800)) {
        console.log('[OCF ' + VERSION + '] ghost-undo BLOCKED (age=' +
          (now - this._ocf22lastTs) + 'ms)');
        if (this._ocf22lastValue) {
          this._ocf22lastValue.addClass(this.valueSelectedClass);
        }
        if (this._ocf22lastParams) {
          this.setParams(this._ocf22lastParams);
        }
        return;
      }

      if (params) {
        this._ocf22lastTs = now;
        this._ocf22lastValue = $value;
        this._ocf22lastParams = params;
      }

      return origSearch.apply(this, arguments);
    };

    console.log('[OCF ' + VERSION + '] search prototype patched');
    return true;
  }

  // ─────────────────────────────────────────────────────────────
  // FIX F: Touch-to-click for filter value buttons (removes 300ms delay)
  // ─────────────────────────────────────────────────────────────
  function attachTouchToClick(btn) {
    if (!isMobile) return;
    if (btn._ocf22touch) return;
    btn._ocf22touch = true;

    var sx = 0, sy = 0, moved = false;

    btn.addEventListener('touchstart', function (e) {
      moved = false;
      if (e.touches[0]) {
        sx = e.touches[0].clientX;
        sy = e.touches[0].clientY;
      }
    }, { passive: true });

    btn.addEventListener('touchmove', function (e) {
      if (e.touches[0]) {
        var dx = Math.abs(e.touches[0].clientX - sx);
        var dy = Math.abs(e.touches[0].clientY - sy);
        if (dx > 10 || dy > 10) moved = true;
      }
    }, { passive: true });

    btn.addEventListener('touchend', function (e) {
      if (moved) return;
      e.preventDefault();
      var el = this;
      setTimeout(function () { el.click(); }, 0);
    }, { passive: false });
  }

  function attachAllButtons() {
    if (!isMobile) return 0;
    var btns = document.querySelectorAll('[id^="ocf-module-"] .ocf-value');
    var n = 0;
    for (var i = 0; i < btns.length; i++) {
      if (!btns[i]._ocf22touch) {
        attachTouchToClick(btns[i]);
        n++;
      }
    }
    if (n > 0) console.log('[OCF ' + VERSION + '] attached touch to ' + n + ' buttons');
    return btns.length;
  }

  // ─────────────────────────────────────────────────────────────
  // DOM watcher
  // ─────────────────────────────────────────────────────────────
  function watchDOM() {
    if (typeof MutationObserver === 'undefined') return;
    new MutationObserver(function (muts) {
      var need = false;
      muts.forEach(function (m) {
        if (!m.addedNodes) return;
        m.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return;
          if ((n.classList && n.classList.contains('ocf-value')) ||
              (n.querySelector && n.querySelector('.ocf-value'))) {
            need = true;
          }
        });
      });
      if (need) attachAllButtons();
    }).observe(document.documentElement, { childList: true, subtree: true });
  }

  // ─────────────────────────────────────────────────────────────
  // Polling loop
  // ─────────────────────────────────────────────────────────────
  var _patchedSearch = false;
  var _patchedToggle = false;
  var _autoSearchDone = false;
  var _pollCount = 0;
  var _pollTimer = null;

  function poll($) {
    _pollCount++;

    if (isMobile) attachAllButtons();

    if (!_patchedSearch) {
      _patchedSearch = patchSearchPrototype($);
    }

    if (!_patchedToggle) {
      _patchedToggle = patchToggleMobile($);
    }

    if (!_autoSearchDone) {
      var fired = autoSearchOnInit($);
      if (fired > 0) {
        _autoSearchDone = true;
        console.log('[OCF ' + VERSION + '] auto-search fired for ' + fired + ' instances');
      }
    }

    if (_pollCount >= 100) {
      clearInterval(_pollTimer);
      console.log('[OCF ' + VERSION + '] poll done. searchPatched=' + _patchedSearch +
        ' togglePatched=' + _patchedToggle + ' autoSearch=' + _autoSearchDone);
    }
  }

  function startPolling($) {
    if (_pollTimer) return;
    _pollTimer = setInterval(function () { poll($); }, 100);
  }

  // BFCache support
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) {
      console.log('[OCF ' + VERSION + '] BFCache restore');
      suppressAutoOpen();
      _patchedSearch = false;
      _patchedToggle = false;
      _autoSearchDone = false;
      _pollCount = 0;
      clearInterval(_pollTimer);
      _pollTimer = null;
      if (typeof jQuery !== 'undefined') startPolling(jQuery);
    }
  });

  // ─────────────────────────────────────────────────────────────
  // MAIN INIT
  // ─────────────────────────────────────────────────────────────
  function init($) {
    $.ajaxSetup({ cache: false, timeout: 15000 });
    patchButtonTouch($);
    watchDOM();
    startPolling($);
    console.log('[OCF ' + VERSION + '] init done');
  }

  (function waitJQ() {
    if (typeof jQuery !== 'undefined') {
      init(jQuery);
    } else {
      setTimeout(waitJQ, 50);
    }
  })();

})();

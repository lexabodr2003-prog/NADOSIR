/**
 * popup-dropdown-fix.js v6.0
 *
 * New approach: manual CSS-based modal display for iOS reliability.
 */
(function () {
  'use strict';

  function closeAllDropdowns() {
    var overlay = document.getElementById('overlay');
    document.querySelectorAll('.ds-dropdown-box.active').forEach(function (d) {
      d.classList.remove('active');
    });
    if (overlay) {
      overlay.classList.remove('active', 'active-sidebar');
      overlay.style.display = 'none';
      setTimeout(function() { overlay.style.display = ''; }, 500);
    }
    document.body.classList.remove('no-scroll');
  }

  function cancelSubscribeTimer() {
    if (window._octSubscribeTimer) {
      clearTimeout(window._octSubscribeTimer);
      window._octSubscribeTimer = null;
    }
  }

  function clearLoader() {
    if (typeof masked === 'function') masked('body', false);
    document.querySelectorAll('.ds-loader-overlay').forEach(function (el) {
      el.remove();
    });
  }

  function showModalManual(el) {
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop show';
    backdrop.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:12500;';
    document.body.appendChild(backdrop);

    el.style.cssText = 'display:block;position:fixed;top:0;left:0;width:100%;height:100%;z-index:13000;overflow-x:hidden;overflow-y:auto;outline:0;';
    el.classList.add('show');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('role', 'dialog');
    el.removeAttribute('aria-hidden');
    document.body.classList.add('modal-open');
    document.body.style.overflow = 'hidden';

    function doClose() {
      el.classList.remove('show');
      el.style.display = 'none';
      el.setAttribute('aria-hidden', 'true');
      el.removeAttribute('aria-modal');
      el.removeAttribute('role');
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      if (backdrop && backdrop.parentNode) backdrop.remove();
    }

    backdrop.addEventListener('click', doClose);
    el.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function(btn) {
      btn.addEventListener('click', doClose);
    });
    console.log('[popup-fix v6] Manual modal shown: #' + el.id);
  }

  function showModalDirect(modalId) {
    var id = modalId.replace('#', '');
    var el = document.getElementById(id);
    if (!el) {
      console.error('[popup-fix v6] modal #' + id + ' NOT FOUND');
      return;
    }

    el.classList.remove('fade');
    closeAllDropdowns();
    document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });

    if (window.bootstrap && window.bootstrap.Modal) {
      try {
        var existing = window.bootstrap.Modal.getInstance(el);
        if (existing) { try { existing.dispose(); } catch(e) {} }
        var bsModal = new window.bootstrap.Modal(el, { backdrop: true, keyboard: true, focus: true });
        bsModal.show();
        console.log('[popup-fix v6] Bootstrap Modal.show() OK: #' + id);
        return;
      } catch(e) {
        console.warn('[popup-fix v6] Bootstrap Modal failed, using manual:', e.message);
      }
    }

    showModalManual(el);
  }

  function buildLoginFn(name) {
    return function () {
      cancelSubscribeTimer();
      closeAllDropdowns();
      clearLoader();
      document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });

      var url = name === 'octPopupLogin'
        ? 'index.php?route=octemplates/module/oct_popup_login'
        : 'index.php?route=octemplates/module/oct_otp_login';

      if (typeof masked === 'function') masked('body', true);

      jQuery.ajax({
        type: 'post',
        dataType: 'html',
        url: url,
        data: '',
        cache: false,
        timeout: 15000,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: function (html) {
          clearLoader();
          if (!html || html.trim() === '') {
            console.error('[popup-fix v6] Empty AJAX response');
            return;
          }
          jQuery('.modal-holder').html(html);
          var modalId = name === 'octPopupLogin' ? 'loginModal' : 'otpModal';
          setTimeout(function() { showModalDirect(modalId); }, 50);
          if (typeof popupClose === 'function') popupClose();
        },
        error: function (xhr, status, err) {
          clearLoader();
          console.error('[popup-fix v6] AJAX error:', status, err, 'HTTP:', xhr.status);
        }
      });
    };
  }

  function patchGenericFn(name) {
    var original = window[name];
    if (typeof original !== 'function') return false;
    window[name] = function () {
      cancelSubscribeTimer();
      closeAllDropdowns();
      return original.apply(this, arguments);
    };
    return true;
  }

  function addTouchBridge() {
    document.querySelectorAll('[onclick*="octPopupLogin"]').forEach(function (btn) {
      if (btn.dataset.touchBridgeAdded) return;
      btn.dataset.touchBridgeAdded = '1';
      btn.addEventListener('touchstart', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof window.octPopupLogin === 'function') window.octPopupLogin();
      }, { passive: false });
    });
  }

  function applyPatches() {
    window.octPopupLogin = buildLoginFn('octPopupLogin');
    window.octPopupOTPLogin = buildLoginFn('octPopupOTPLogin');
    ['octPopupCallPhone', 'octPopupCart', 'octPopupFoundCheaper',
     'octPopupProductOptions'].forEach(patchGenericFn);
    addTouchBridge();
    console.log('[popup-fix v6] patches applied');
  }

  var attempts = 0;
  var pollTimer = setInterval(function () {
    attempts++;
    if (typeof octPopupLogin === 'function') {
      clearInterval(pollTimer);
      applyPatches();
    } else if (attempts >= 200) {
      clearInterval(pollTimer);
      console.warn('[popup-fix v6] octPopupLogin not found after 10s');
    }
  }, 50);

})();

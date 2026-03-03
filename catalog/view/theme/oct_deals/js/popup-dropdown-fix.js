/**
 * popup-dropdown-fix.js v6.1
 *
 * Manual CSS-based modal display for iOS reliability.
 * Fixed: use individual style properties instead of cssText
 * to preserve Bootstrap CSS variables and modal-dialog layout.
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
    // Создаём backdrop
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop show';
    backdrop.style.position = 'fixed';
    backdrop.style.top = '0';
    backdrop.style.left = '0';
    backdrop.style.width = '100%';
    backdrop.style.height = '100%';
    backdrop.style.background = 'rgba(0,0,0,0.5)';
    backdrop.style.zIndex = '12500';
    document.body.appendChild(backdrop);

    // Показываем modal - только необходимые свойства
    el.style.display = 'block';
    el.style.zIndex = '13000';
    el.classList.add('show');
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('role', 'dialog');
    el.removeAttribute('aria-hidden');
    document.body.classList.add('modal-open');
    document.body.style.overflow = 'hidden';

    function doClose() {
      el.classList.remove('show');
      el.style.display = 'none';
      el.style.zIndex = '';
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
    console.log('[popup-fix v6.1] Manual modal shown: #' + el.id);
  }

  function showModalDirect(modalId) {
    var id = modalId.replace('#', '');
    var el = document.getElementById(id);
    if (!el) {
      console.error('[popup-fix v6.1] modal #' + id + ' NOT FOUND in DOM');
      return;
    }

    // Убираем fade анимацию (вызывает проблемы на iOS)
    el.classList.remove('fade');
    closeAllDropdowns();
    document.querySelectorAll('.modal-backdrop').forEach(function(b) { b.remove(); });

    // Пробуем Bootstrap API
    if (window.bootstrap && window.bootstrap.Modal) {
      try {
        var existing = window.bootstrap.Modal.getInstance(el);
        if (existing) { try { existing.dispose(); } catch(e) {} }
        var bsModal = new window.bootstrap.Modal(el, {
          backdrop: true,
          keyboard: true,
          focus: true
        });
        bsModal.show();
        console.log('[popup-fix v6.1] Bootstrap Modal.show() OK: #' + id);
        return;
      } catch(e) {
        console.warn('[popup-fix v6.1] Bootstrap Modal failed, using manual:', e.message);
      }
    }

    // Фолбэк: ручное управление
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
            console.error('[popup-fix v6.1] Empty AJAX response for', name);
            return;
          }
          jQuery('.modal-holder').html(html);
          var modalId = name === 'octPopupLogin' ? 'loginModal' : 'otpModal';
          setTimeout(function() { showModalDirect(modalId); }, 50);
          if (typeof popupClose === 'function') popupClose();
        },
        error: function (xhr, status, err) {
          clearLoader();
          console.error('[popup-fix v6.1] AJAX error:', status, err, 'HTTP:', xhr.status);
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
    console.log('[popup-fix v6.1] patches applied');
  }

  var attempts = 0;
  var pollTimer = setInterval(function () {
    attempts++;
    if (typeof octPopupLogin === 'function') {
      clearInterval(pollTimer);
      applyPatches();
    } else if (attempts >= 200) {
      clearInterval(pollTimer);
      console.warn('[popup-fix v6.1] octPopupLogin not found after 10s');
    }
  }, 50);

})();

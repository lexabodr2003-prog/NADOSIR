/**
 * popup-dropdown-fix.js v5.0
 *
 * ROOT CAUSES FIXED:
 *
 * 1. Bootstrap 5 "doesn't allow more than one instance per element" +
 *    iOS Safari CSS transition glitch when showing dynamically-inserted modal.
 *    FIX: Use bootstrap.Modal.getOrCreateInstance() + requestAnimationFrame.
 *
 * 2. #overlay.active (z-index:12001) covers Bootstrap modal.
 *    CSS fix in kamtek-mobile-fixes.css + explicit overlay removal here.
 *
 * 3. oct_subscribe race condition: footer.twig stores timer in
 *    window._octSubscribeTimer. We clearTimeout() it before any popup.
 *
 * 4. iOS touchstart bridge: touchstart fires octPopupLogin immediately,
 *    bypassing potential click-event swallowing inside dropdown.
 */
(function () {
  "use strict";

  /* ---- helpers ---- */
  function closeAllDropdowns() {
    var overlay = document.getElementById("overlay");
    document.querySelectorAll(".ds-dropdown-box.active").forEach(function (d) {
      d.classList.remove("active");
    });
    if (overlay) {
      overlay.classList.remove("active", "active-sidebar");
    }
    document.body.classList.remove("no-scroll");
  }

  function cancelSubscribeTimer() {
    if (window._octSubscribeTimer) {
      clearTimeout(window._octSubscribeTimer);
      window._octSubscribeTimer = null;
    }
  }

  function clearLoader() {
    if (typeof masked === "function") masked("body", false);
    document.querySelectorAll(".ds-loader-overlay").forEach(function (el) {
      el.remove();
    });
  }

  /**
   * Show Bootstrap 5 modal reliably on iOS.
   * Uses native bootstrap.Modal API with requestAnimationFrame
   * to avoid iOS Safari CSS transition glitch.
   */
  function showModal(modalId) {
    var el = document.getElementById(modalId.replace("#", ""));
    if (!el) {
      console.warn("[popup-fix v5] modal element not found:", modalId);
      return;
    }

    /* Ensure overlay is gone BEFORE Bootstrap adds its own backdrop */
    closeAllDropdowns();

    /* Use native Bootstrap 5 API */
    if (window.bootstrap && window.bootstrap.Modal) {
      /* Dispose any stale instance (leftover from previous .html() injection) */
      var existing = window.bootstrap.Modal.getInstance(el);
      if (existing) {
        existing.dispose();
      }
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          /* Two rAF: first lets browser reflow, second starts transition */
          var bsModal = new window.bootstrap.Modal(el, {
            backdrop: true,
            keyboard: true,
            focus: true
          });
          bsModal.show();
        });
      });
    } else {
      /* Fallback: jQuery Bootstrap plugin */
      requestAnimationFrame(function () {
        $(el).modal("show");
      });
    }
  }

  /* ---- patched login function ---- */
  function buildLoginFn(name) {
    return function () {
      cancelSubscribeTimer();
      closeAllDropdowns();
      clearLoader();
      $(".modal-backdrop").remove();

      var url = name === "octPopupLogin"
        ? "index.php?route=octemplates/module/oct_popup_login"
        : "index.php?route=octemplates/module/oct_otp_login";

      if (typeof masked === "function") masked("body", true);

      $.ajax({
        type: "post",
        dataType: "html",
        url: url,
        data: "",
        cache: false,
        timeout: 15000,
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        },
        success: function (html) {
          clearLoader();

          if (!html || html.trim() === "") {
            console.error("[popup-fix v5] Empty AJAX response for", name);
            return;
          }

          $(".modal-holder").html(html);

          var modalId = name === "octPopupLogin" ? "loginModal" : "otpModal";
          showModal(modalId);

          if (typeof popupClose === "function") popupClose();
        },
        error: function (xhr, status, err) {
          clearLoader();
          console.error("[popup-fix v5] AJAX failed:", status, err,
            "HTTP:", xhr.status, xhr.responseText ? xhr.responseText.slice(0, 200) : "");
        }
      });
    };
  }

  /* ---- patch generic popup functions ---- */
  function patchGenericFn(name) {
    var original = window[name];
    if (typeof original !== "function") return false;
    window[name] = function () {
      cancelSubscribeTimer();
      closeAllDropdowns();
      return original.apply(this, arguments);
    };
    return true;
  }

  /* ---- iOS touchstart bridge ---- */
  function addTouchBridge() {
    document.querySelectorAll('[onclick*="octPopupLogin"]').forEach(function (btn) {
      if (btn.dataset.touchBridgeAdded) return;
      btn.dataset.touchBridgeAdded = "1";
      btn.addEventListener("touchstart", function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof window.octPopupLogin === "function") {
          window.octPopupLogin();
        }
      }, { passive: false });
    });
  }

  /* ---- apply all patches ---- */
  function applyPatches() {
    window.octPopupLogin = buildLoginFn("octPopupLogin");
    window.octPopupOTPLogin = buildLoginFn("octPopupOTPLogin");
    ["octPopupCallPhone", "octPopupCart", "octPopupFoundCheaper",
     "octPopupProductOptions"].forEach(patchGenericFn);
    addTouchBridge();
    console.log("[popup-fix v5] patches applied. bootstrap.Modal=",
      !!(window.bootstrap && window.bootstrap.Modal),
      "subscribeTimer=", window._octSubscribeTimer);
  }

  /* ---- poll for octPopupLogin (loaded in bundle) ---- */
  var attempts = 0;
  var pollTimer = setInterval(function () {
    attempts++;
    if (typeof octPopupLogin === "function") {
      clearInterval(pollTimer);
      applyPatches();
    } else if (attempts >= 200) {
      clearInterval(pollTimer);
      console.warn("[popup-fix v5] octPopupLogin not found after 10s");
    }
  }, 50);

})();

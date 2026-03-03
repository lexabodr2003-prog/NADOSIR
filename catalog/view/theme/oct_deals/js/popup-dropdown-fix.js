/**
 * popup-dropdown-fix.js v4.0
 *
 * ROOT CAUSES FIXED:
 *
 * 1. #overlay.active (z-index:12001) covers Bootstrap modal (z-index:1055).
 *    CSS fix in kamtek-mobile-fixes.css raises modal to 13000.
 *    This script ALSO explicitly removes overlay.active before modal show.
 *
 * 2. oct_subscribe race condition (first homepage visit):
 *    footer.twig saves timer as window._octSubscribeTimer.
 *    We call clearTimeout(window._octSubscribeTimer) before any popup.
 *
 * 3. Missing error handler in octPopupLogin -> loader never removed on failure.
 *
 * 4. iOS Safari: t.preventDefault() in dropdownToggle can swallow click events
 *    inside dropdown. Added touchstart-to-click bridge on login button.
 *
 * 5. Bootstrap modal not shown if overlay covers it:
 *    We force-remove overlay.active and ds-dropdown-box.active before modal.show().
 */
(function () {
  "use strict";

  /* ---------- helpers ---------- */
  function closeAllDropdowns() {
    var overlay = document.getElementById("overlay");
    document.querySelectorAll(".ds-dropdown-box.active").forEach(function (d) {
      d.classList.remove("active");
    });
    if (overlay) {
      overlay.classList.remove("active");
      overlay.classList.remove("active-sidebar");
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
    var overlays = document.querySelectorAll(".ds-loader-overlay");
    overlays.forEach(function(el){ el.remove(); });
  }

  /* ---------- patched login function ---------- */
  function buildLoginFn(name) {
    return function () {
      cancelSubscribeTimer();
      closeAllDropdowns();
      clearLoader();
      $(".modal-backdrop").remove();
      $(".modal.show").modal("hide");

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
        success: function (html) {
          clearLoader();
          $(".modal-holder").html(html);
          /* small delay so DOM is ready, then show modal */
          setTimeout(function () {
            var modalId = name === "octPopupLogin" ? "#loginModal" : "#otpModal";
            var $modal = $(modalId);
            if ($modal.length) {
              /* ensure overlay is gone before Bootstrap adds backdrop */
              closeAllDropdowns();
              $modal.modal("show");
            } else {
              console.warn("[popup-fix v4] modal element not found:", modalId);
            }
            if (typeof popupClose === "function") popupClose();
          }, 50);
        },
        error: function (xhr, status, err) {
          clearLoader();
          console.error("[popup-fix v4] AJAX failed:", status, err);
        }
      });
    };
  }

  /* ---------- patch generic popup functions ---------- */
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

  /* ---------- iOS touchstart bridge for login button ---------- */
  function addTouchBridgeToLoginButton() {
    /* The button has onclick="octPopupLogin();" — on iOS inside an open dropdown
       the click event is sometimes eaten by the document click handler.
       Adding touchstart ensures the function is called. */
    document.querySelectorAll('[onclick*="octPopupLogin"]').forEach(function (btn) {
      if (btn.dataset.touchBridgeAdded) return;
      btn.dataset.touchBridgeAdded = "1";
      btn.addEventListener("touchstart", function (e) {
        /* Don't prevent default — let iOS also fire click for non-JS fallback */
        /* But call the function immediately on touch */
        if (typeof window.octPopupLogin === "function") {
          e.preventDefault();
          window.octPopupLogin();
        }
      }, { passive: false });
    });
  }

  /* ---------- apply all patches ---------- */
  function applyPatches() {
    window.octPopupLogin = buildLoginFn("octPopupLogin");
    window.octPopupOTPLogin = buildLoginFn("octPopupOTPLogin");
    ["octPopupCallPhone", "octPopupCart", "octPopupFoundCheaper",
     "octPopupProductOptions"].forEach(patchGenericFn);
    addTouchBridgeToLoginButton();
    console.log("[popup-fix v4] patches applied. subscribeTimer=", window._octSubscribeTimer);
  }

  /* ---------- wait for octPopupLogin to be defined ---------- */
  var attempts = 0;
  var pollTimer = setInterval(function () {
    attempts++;
    if (typeof octPopupLogin === "function") {
      clearInterval(pollTimer);
      applyPatches();
    } else if (attempts >= 200) {  /* 200 * 50ms = 10s */
      clearInterval(pollTimer);
      console.warn("[popup-fix v4] octPopupLogin not found after 10s");
    }
  }, 50);

  /* ---------- also run on DOM ready in case script loads late ---------- */
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      if (typeof octPopupLogin === "function" && !window._popupFixApplied) {
        window._popupFixApplied = true;
        applyPatches();
      }
    });
  }

})();

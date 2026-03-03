/**
 * popup-dropdown-fix.js v3.0
 *
 * ROOT CAUSES FIXED:
 *
 * 1. z-index conflict:
 *    #overlay.active z-index:12001 hides Bootstrap modal (z-index:1055).
 *    CSS fix in kamtek-mobile-fixes.css raises modal to 13000.
 *
 * 2. oct_subscribe race condition - the REAL bug on homepage first visit:
 *    footer.twig runs: window._octSubscribeTimer = setTimeout(octPopupSubscribe, N)
 *    BEFORE main.min.js loads. v2.0 tried to intercept window.setTimeout but
 *    the timer was already created before our script loaded - did not work.
 *    v3.0 FIX: footer.twig now saves timer as window._octSubscribeTimer.
 *    We simply call clearTimeout(window._octSubscribeTimer) when user opens login.
 *
 * 3. Missing error handler in octPopupLogin:
 *    On AJAX failure masked spinner never removed -> infinite loader.
 *
 * 4. Dropdown must be closed before modal opens (overlay z-index conflict).
 */
(function () {
  "use strict";

  function closeAllDropdowns() {
    var overlay = document.getElementById("overlay");
    document.querySelectorAll(".ds-dropdown-box.active").forEach(function (d) {
      d.classList.remove("active");
    });
    if (overlay) overlay.classList.remove("active");
  }

  function cancelSubscribeTimer() {
    if (window._octSubscribeTimer) {
      clearTimeout(window._octSubscribeTimer);
      window._octSubscribeTimer = null;
      console.log("[popup-fix v3] subscribe timer cancelled");
    }
  }

  function patchLoginFn(name) {
    var original = window[name];
    if (typeof original !== "function") return false;

    window[name] = function () {
      cancelSubscribeTimer();
      closeAllDropdowns();
      if (typeof masked === "function") masked("body", false);
      $(".modal-backdrop").remove();
      masked("body", true);
      $.ajax({
        type: "post",
        dataType: "html",
        url: name === "octPopupLogin"
          ? "index.php?route=octemplates/module/oct_popup_login"
          : "index.php?route=octemplates/module/oct_otp_login",
        data: "",
        cache: false,
        success: function (html) {
          masked("body", false);
          $(".modal-holder").html(html);
          $(name === "octPopupLogin" ? "#loginModal" : "#otpModal").modal("show");
          if (typeof popupClose === "function") popupClose();
        },
        error: function (xhr, status, err) {
          masked("body", false);
          console.error("[popup-fix v3] " + name + " failed:", status, err);
        }
      });
    };
    return true;
  }

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

  function applyPatches() {
    patchLoginFn("octPopupLogin");
    patchLoginFn("octPopupOTPLogin");
    ["octPopupCallPhone", "octPopupCart", "octPopupFoundCheaper",
     "octPopupProductOptions"].forEach(patchGenericFn);
    console.log("[popup-fix v3] patches applied. subscribeTimer=", window._octSubscribeTimer);
  }

  var attempts = 0;
  var pollTimer = setInterval(function () {
    attempts++;
    if (typeof octPopupLogin === "function") {
      clearInterval(pollTimer);
      applyPatches();
    } else if (attempts >= 100) {
      clearInterval(pollTimer);
      console.warn("[popup-fix v3] octPopupLogin not found after 5s");
    }
  }, 50);

})();

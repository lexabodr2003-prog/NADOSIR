/**
 * popup-dropdown-fix.js v2.0
 *
 * ROOT CAUSES FIXED:
 *
 * 1. z-index conflict:
 *    #overlay.active has z-index:12001 (oct_deals theme).
 *    CSS fix in kamtek-mobile-fixes.css raises modal z-index to 13000.
 *
 * 2. oct_subscribe race condition (main bug on homepage first visit):
 *    On first visit, footer.twig schedules octPopupSubscribe() via setTimeout.
 *    If user taps login button before the timer fires, both AJAX requests run.
 *    octPopupSubscribe() replaces .modal-holder content, overwriting loginModal HTML.
 *    FIX: Cancel the subscribe timer when any popup is opened.
 *
 * 3. Missing error handler in octPopupLogin:
 *    If AJAX fails, masked("body", false) is never called → infinite loader.
 *    FIX: Wrap original function and add error/complete handler.
 *
 * 4. Dropdown stays open while modal loads:
 *    Close all dropdowns before any popup opens to prevent overlay blocking modal.
 */
(function () {
  "use strict";

  // ── Utility: close all dropdowns ─────────────────────────────
  function closeAllDropdowns() {
    var overlay = document.getElementById("overlay");
    var dropdowns = document.querySelectorAll(".ds-dropdown-box.active");
    dropdowns.forEach(function (d) { d.classList.remove("active"); });
    if (overlay) { overlay.classList.remove("active"); }
  }

  // ── Cancel subscribe timer ────────────────────────────────────
  // The subscribe auto-popup timer id is stored here after we intercept it.
  var _subscribeTimerId = null;

  function cancelSubscribeTimer() {
    if (_subscribeTimerId !== null) {
      clearTimeout(_subscribeTimerId);
      _subscribeTimerId = null;
      console.log("[popup-fix] subscribe timer cancelled");
    }
  }

  // Intercept setTimeout to catch the oct_subscribe timer.
  // The timer is scheduled via: setTimeout(function(){ octPopupSubscribe(); }, N)
  // We detect it by checking if the callback string contains "octPopupSubscribe".
  var _origSetTimeout = window.setTimeout;
  window.setTimeout = function (fn, delay) {
    var fnStr = (typeof fn === "function") ? fn.toString() : String(fn);
    var id = _origSetTimeout.apply(window, arguments);
    if (fnStr.indexOf("octPopupSubscribe") !== -1) {
      _subscribeTimerId = id;
      console.log("[popup-fix] caught subscribe timer id=" + id + " delay=" + delay);
    }
    return id;
  };

  // ── Patch a popup function ────────────────────────────────────
  function patchPopupFn(name) {
    var original = window[name];
    if (typeof original !== "function") return false;

    if (name === "octPopupLogin" || name === "octPopupOTPLogin") {
      // Full replacement with error handler + subscribe cancel
      window[name] = function () {
        cancelSubscribeTimer();
        closeAllDropdowns();
        // Remove existing loader and backdrop before starting
        window.masked && masked("body", false);
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
          success: function (e) {
            masked("body", false);
            $(".modal-holder").html(e);
            var modalId = name === "octPopupLogin" ? "#loginModal" : "#otpModal";
            $(modalId).modal("show");
            window.popupClose && popupClose();
          },
          error: function (xhr, status, err) {
            masked("body", false);
            console.error("[popup-fix] " + name + " AJAX error:", status, err);
          }
        });
      };
    } else {
      // Generic patch: cancel subscribe + close dropdowns
      window[name] = function () {
        cancelSubscribeTimer();
        closeAllDropdowns();
        return original.apply(this, arguments);
      };
    }
    return true;
  }

  var POPUP_FNS = [
    "octPopupLogin", "octPopupOTPLogin", "octPopupCallPhone",
    "octPopupCart", "octPopupSubscribe", "octPopupFoundCheaper",
    "octPopupProductOptions"
  ];

  function applyPatches() {
    POPUP_FNS.forEach(function (name) { patchPopupFn(name); });
    console.log("[popup-fix] v2.0 patches applied");
  }

  // Wait for octPopupLogin to be defined (it lives in main.min.js loaded via footer scripts)
  var attempts = 0;
  var timer = setInterval(function () {
    attempts++;
    if (typeof octPopupLogin === "function") {
      applyPatches();
      clearInterval(timer);
    } else if (attempts > 100) {
      clearInterval(timer);
      console.warn("[popup-fix] octPopupLogin not found after 5s");
    }
  }, 50);

})();

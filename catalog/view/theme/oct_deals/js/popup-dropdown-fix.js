/**
 * popup-dropdown-fix.js v1.0
 *
 * ROOT CAUSE:
 * #overlay.active has z-index:12001 (oct_deals theme).
 * When user taps "Login" inside an open dropdown (.ds-dropdown-box.active),
 * the #overlay covers Bootstrap modal (default z-index:1055).
 * CSS fix in kamtek-mobile-fixes.css raises modal z-index to 13000.
 * This JS additionally closes all dropdowns before any popup opens,
 * so the overlay does not block modal interaction.
 */
(function () {
  "use strict";

  function closeAllDropdowns() {
    var overlay = document.getElementById("overlay");
    var dropdowns = document.querySelectorAll(".ds-dropdown-box.active");
    dropdowns.forEach(function (d) { d.classList.remove("active"); });
    if (overlay) { overlay.classList.remove("active"); }
  }

  function patchPopupFn(name) {
    var original = window[name];
    if (typeof original !== "function") return false;
    window[name] = function () {
      closeAllDropdowns();
      return original.apply(this, arguments);
    };
    return true;
  }

  var POPUP_FNS = [
    "octPopupLogin", "octPopupOTPLogin", "octPopupCallPhone",
    "octPopupCart", "octPopupSubscribe", "octPopupFoundCheaper",
    "octPopupProductOptions"
  ];

  function applyPatches() {
    POPUP_FNS.forEach(function (name) { patchPopupFn(name); });
  }

  var attempts = 0;
  var timer = setInterval(function () {
    attempts++;
    if (typeof octPopupLogin === "function") {
      applyPatches();
      clearInterval(timer);
    } else if (attempts > 100) {
      clearInterval(timer);
    }
  }, 50);
})();

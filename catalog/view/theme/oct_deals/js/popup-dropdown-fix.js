/**
 * popup-dropdown-fix.js v7.0
 *
 * Минимальный патч: только touchstart-мост для iOS.
 * Основная логика octPopupLogin находится прямо в бандле (e2a2c3fc...js).
 * Этот файл ТОЛЬКО добавляет обработку touchstart для надёжности на iOS Safari/Yandex.
 */
(function () {
  "use strict";

  function addTouchBridge() {
    document.querySelectorAll("[onclick*=\"octPopupLogin\"]").forEach(function (btn) {
      if (btn.dataset.tbAdded) return;
      btn.dataset.tbAdded = "1";
      btn.addEventListener("touchend", function (e) {
        // touchend надёжнее touchstart - не конфликтует со скроллом
        e.preventDefault();
        if (typeof window.octPopupLogin === "function") {
          window.octPopupLogin();
        }
      }, { passive: false });
    });
  }

  // Ждём загрузки DOM и функции octPopupLogin
  function waitAndPatch() {
    if (typeof window.octPopupLogin === "function") {
      addTouchBridge();
      console.log("[popup-fix v7.0] touchBridge applied");
    } else {
      setTimeout(waitAndPatch, 100);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", waitAndPatch);
  } else {
    waitAndPatch();
  }
})();

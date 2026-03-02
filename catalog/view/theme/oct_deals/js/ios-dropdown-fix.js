/**
 * ios-dropdown-fix.js v3.0
 *
 * ПРОБЛЕМА (диагностировано точно):
 *   Swiper регистрирует touchstart на document с {passive:false, capture:true}
 *   и в onTouchStart вызывает e.preventDefault() (touchStartPreventDefault:true).
 *   На iOS Safari это блокирует синтетический click на ЛЮБОМ элементе хедера:
 *   1) .ds-dropdown-toggle — дропдаун не открывается
 *   2) button[onclick="octPopupLogin()"] — форма входа не появляется
 *
 * РЕШЕНИЕ:
 *   Перехватываем touchstart раньше Swiper (capture:true, passive:false,
 *   зарегистрирован раньше — при парсинге скрипта в <head> или до Swiper init).
 *   Для элементов внутри .ds-dropdown-box и .ds-dropdown-toggle:
 *   вызываем e.stopPropagation() — Swiper не получает событие.
 *   Дропдаун открывается нативным click (без блокировки Swiper).
 */
(function () {
  'use strict';

  if (!('ontouchstart' in window) && !(navigator.maxTouchPoints > 0)) return;

  var touchTarget = null;
  var touchMoved = false;
  var startX = 0, startY = 0;

  /**
   * Проверяем: находится ли элемент внутри .ds-dropdown-box или .ds-dropdown-toggle?
   * (хедер аккаунта + хедер телефона)
   */
  function getDropdownEl(el) {
    while (el && el !== document.body) {
      if (el.classList) {
        if (el.classList.contains('ds-dropdown-toggle') ||
            el.classList.contains('ds-dropdown-box') ||
            el.classList.contains('ds-dropdown') ||
            el.classList.contains('ds-dropdown-inner')) {
          return el;
        }
      }
      el = el.parentNode;
    }
    return null;
  }

  // Регистрируемся ПЕРВЫМИ (до Swiper) через capture:true, passive:false
  document.addEventListener('touchstart', function (e) {
    touchMoved = false;
    touchTarget = null;

    var t = e.touches ? e.touches[0] : null;
    if (t) { startX = t.clientX; startY = t.clientY; }

    var found = getDropdownEl(e.target);
    if (!found) return;

    touchTarget = found;
    // Блокируем Swiper — он НЕ получит этот touchstart и НЕ вызовет preventDefault
    e.stopPropagation();
    // НЕ вызываем e.preventDefault() — click должен сгенерироваться нормально

  }, { capture: true, passive: false });

  document.addEventListener('touchmove', function (e) {
    if (!touchTarget) return;
    var t = e.touches ? e.touches[0] : null;
    if (!t) return;
    if (Math.abs(t.clientX - startX) > 8 || Math.abs(t.clientY - startY) > 8) {
      touchMoved = true;
      touchTarget = null;
    }
  }, { capture: false, passive: true });

  document.addEventListener('touchend', function (e) {
    var target = touchTarget;
    touchTarget = null;
    if (!target || touchMoved) return;

    // Тап был внутри дропдауна без скролла
    // Проверяем: был ли тап на .ds-dropdown-toggle (открытие дропдауна)?
    var el = e.target || e.changedTouches && e.changedTouches[0] && document.elementFromPoint(
      e.changedTouches[0].clientX, e.changedTouches[0].clientY
    );

    // Ищем .ds-dropdown-toggle в цепочке
    var toggleEl = null;
    var cur = el;
    while (cur && cur !== document.body) {
      if (cur.classList && cur.classList.contains('ds-dropdown-toggle')) {
        toggleEl = cur; break;
      }
      cur = cur.parentNode;
    }

    if (!toggleEl) {
      // Тап был внутри .ds-dropdown но НЕ на toggle — это кнопка внутри дропдауна
      // Нативный click должен сработать (Swiper заблокирован через stopPropagation)
      // Ничего дополнительно не делаем
      return;
    }

    // Тап был на .ds-dropdown-toggle
    // Даём 80мс для нативного click, потом проверяем
    var box = toggleEl.parentNode;
    setTimeout(function () {
      if (!box) return;
      if (box.classList.contains('active')) return; // click сработал

      // Click не сработал — открываем вручную
      var allBoxes = document.querySelectorAll('.ds-dropdown-box.active');
      for (var i = 0; i < allBoxes.length; i++) allBoxes[i].classList.remove('active');
      var ov = document.getElementById('overlay');
      if (ov) ov.classList.remove('active');
      box.classList.add('active');
      if (ov) ov.classList.add('active');
    }, 80);

  }, { capture: false, passive: true });

})();

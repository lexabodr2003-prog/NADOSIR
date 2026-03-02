/**
 * ios-dropdown-fix.js v2.0
 * Минимальный фикс: на iOS Safari Swiper может заблокировать click на .ds-dropdown-toggle.
 * Решение: дублируем логику dropdownToggle через touchstart (non-passive, с preventDefault),
 * чтобы обойти перехват Swiper. НЕ трогаем обработчики click.
 * 
 * Работает параллельно с dropdownToggle — не заменяет его.
 */
(function() {
  'use strict';

  // Только на iOS / touch-устройствах
  var isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  if (!isTouch) return;

  var moved = false;
  var startX = 0, startY = 0;

  document.addEventListener('touchstart', function(e) {
    moved = false;
    if (e.touches && e.touches[0]) {
      startX = e.touches[0].clientX;
      startY = e.touches[0].clientY;
    }
  }, { passive: true, capture: false });

  document.addEventListener('touchmove', function(e) {
    if (e.touches && e.touches[0]) {
      var dx = Math.abs(e.touches[0].clientX - startX);
      var dy = Math.abs(e.touches[0].clientY - startY);
      if (dx > 6 || dy > 6) moved = true;
    }
  }, { passive: true, capture: false });

  document.addEventListener('touchend', function(e) {
    if (moved) return;
    var target = e.target;
    var toggle = target.closest ? target.closest('.ds-dropdown-toggle') : null;
    if (!toggle) return;

    // Даём время нативному click сработать (если сработает — ничего не делаем)
    setTimeout(function() {
      // Проверяем: если дропдаун ещё НЕ открыт — открываем его сами
      var box = toggle.parentNode;
      if (!box || box.classList.contains('active')) return;

      // Открываем дропдаун: сначала закрываем все остальные
      var allBoxes = document.querySelectorAll('.ds-dropdown-box.active');
      for (var i = 0; i < allBoxes.length; i++) {
        allBoxes[i].classList.remove('active');
      }
      var overlay = document.getElementById('overlay');
      if (overlay) overlay.classList.remove('active');

      // Открываем текущий
      box.classList.add('active');
      if (overlay) overlay.classList.add('active');
    }, 150);
  }, { passive: true, capture: false });

})();

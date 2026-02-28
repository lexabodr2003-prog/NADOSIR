/**
 * OCFilter mobile fix for Yandex Browser on iOS
 * Принудительная инициализация фильтра при загрузке страницы
 * Fix: фильтр не работал при первом заходе в Яндекс.Браузере на iPhone
 */
(function() {
  'use strict';

  /**
   * Попытка инициализировать ocfilter если jQuery и OCFilter уже загружены,
   * но плагин не был проинициализирован
   */
  function tryReinitOCFilter() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    // Ищем все контейнеры ocfilter на странице
    var containers = document.querySelectorAll('[id^="ocf-module-"]');
    if (!containers.length) return;

    containers.forEach(function(container) {
      // Если плагин уже инициализирован - пропускаем
      if ($.data(container, 'ocfilter')) return;

      // Если OCFilter плагин доступен - пробуем инициализировать
      if (typeof $.fn.ocfilter !== 'undefined') {
        // Получаем параметры из data-атрибутов если есть
        var $c = $(container);
        try {
          $c.ocfilter($c.data());
        } catch(e) {
          // тихо игнорируем ошибки переинициализации
        }
      }
    });
  }

  /**
   * Подождать пока jQuery станет доступен (iOS YaBrowser иногда задерживает)
   */
  function waitForJQuery(callback, tries) {
    tries = tries || 0;
    if (tries > 50) return; // не ждать больше 5 секунд
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.ocfilter !== 'undefined') {
      callback();
    } else {
      setTimeout(function() {
        waitForJQuery(callback, tries + 1);
      }, 100);
    }
  }

  // Запускаем по window.load (после загрузки всех скриптов)
  window.addEventListener('load', function() {
    waitForJQuery(tryReinitOCFilter);
  });

  // Также запускаем через небольшую задержку после DOMContentLoaded
  // для случаев когда load уже прошёл
  if (document.readyState === 'complete') {
    waitForJQuery(tryReinitOCFilter);
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      // Задержка 300мс чтобы скрипты успели загрузиться
      setTimeout(function() {
        waitForJQuery(tryReinitOCFilter);
      }, 300);
    });
  }

})();

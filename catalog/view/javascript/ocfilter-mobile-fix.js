/**
 * OCFilter mobile fix for Yandex Browser on iOS
 * Принудительная инициализация фильтра при загрузке страницы
 * Fix: фильтр не работал при первом заходе в Яндекс.Браузере на iPhone
 *
 * + Task 15: кнопка "Каталог товаров" на десктопе ведёт сразу к первой категории
 *   (без промежуточного sidebar-окна)
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

  /**
   * Task 15: кнопка "Каталог товаров" (десктоп d-xl-flex) — прямой переход.
   *
   * На десктопе (≥1200px) клик по .ds-header-catalog-button должен
   * перенаправлять сразу на страницу первой категории каталога,
   * минуя промежуточный sidebar-дропдаун.
   *
   * URL берём из первой ссылки внутри ближайшего .ds-menu-catalog:
   *   .ds-menu-catalog-item a  (первая категория в выпадающем списке)
   * Если ссылки нет — открываем /catalog (fallback) или стандартный sidebar.
   */
  function initCatalogButtonDirectLink() {
    var btn = document.querySelector('.ds-header-catalog-button.d-xl-flex, .ds-header-catalog-button');
    if (!btn) return;

    // Только для десктопа (>=1200px)
    function isDesktop() {
      return window.innerWidth >= 1200;
    }

    btn.addEventListener('click', function(e) {
      if (!isDesktop()) return; // на мобильном — штатное поведение (sidebar)

      // Пытаемся найти href первой категории в sidebar каталога
      var catalogMenu = btn.nextElementSibling;
      // Иногда sidebar идёт не сразу — ищем в пределах родителя
      if (!catalogMenu || !catalogMenu.classList.contains('ds-menu-catalog')) {
        var parent = btn.parentElement;
        if (parent) {
          catalogMenu = parent.querySelector('.ds-menu-main-catalog');
        }
      }

      var firstLink = null;
      if (catalogMenu) {
        firstLink = catalogMenu.querySelector('.ds-menu-catalog-item a');
      }

      if (firstLink && firstLink.href) {
        e.preventDefault();
        e.stopImmediatePropagation();
        window.location.href = firstLink.href;
        return;
      }

      // Fallback: ссылка на страницу категорий OpenCart
      // (используем /catalog или route=product/category если первая ссылка не найдена)
      var fallbackUrl = '/catalog';
      // Пробуем взять из первой ссылки в меню шапки
      var anyMenuLink = document.querySelector('.ds-menu-catalog-item a[href*="route=product"]');
      if (anyMenuLink) {
        fallbackUrl = anyMenuLink.href;
      }

      e.preventDefault();
      e.stopImmediatePropagation();
      window.location.href = fallbackUrl;
    }, true); // capture=true чтобы перехватить до JS темы
  }

  // Инициализируем после загрузки DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCatalogButtonDirectLink);
  } else {
    initCatalogButtonDirectLink();
  }

})();

/**
 * OCFilter mobile fix for Yandex Browser on iOS
 * v3.0 - полный рефактор
 *
 * Проблемы исправленные:
 * 1. $.ajax dataType:'script' — ненадёжен при кэше на iOS (исправлено в module.twig)
 * 2. ready() вызывалась до загрузки jQuery (исправлено в module.twig)
 * 3. Двойная инициализация при pageshow/bfcache — исправлено здесь
 * 4. Зависание фильтра при потере ответа AJAX — добавлен watchdog
 *
 * + Task 15: кнопка "Каталог товаров" на десктопе → первая категория
 */
(function() {
  'use strict';

  // ===================================================
  // 1. WATCHDOG: Если AJAX фильтра завис — сбрасываем
  // ===================================================
  function installFilterWatchdog() {
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;

    // Перехватываем нажатие на любой элемент фильтра
    $(document).on('click.ocf_watchdog', '[data-filter-key], [data-ocf="button"], [data-ocf-discard]', function() {
      var $btn = $('[data-ocf="button"]');
      if (!$btn.length) return;

      // Если через 15 секунд кнопка ещё в состоянии loading — сбрасываем
      setTimeout(function() {
        var btnHtml = $btn.html() || '';
        if ($btn.hasClass('ocf-disabled') && btnHtml.indexOf('fa-spin') !== -1) {
          // Принудительно сбрасываем состояние
          $btn.removeClass('ocf-disabled').removeAttr('disabled').prop('ocf-disabled', false);
          var resetText = $btn.data('resetText');
          if (resetText) $btn.html(resetText);
          console.warn('OCFilter watchdog: reset stuck loading state');
        }
      }, 15000);
    });
  }

  // ===================================================
  // 2. PAGESHOW / bfcache: переинициализация при возврате через «Назад»
  // ===================================================
  window.addEventListener('pageshow', function(e) {
    if (!e.persisted) return;

    // Страница восстановлена из bfcache (кнопка «Назад»)
    // Нужно переинициализировать фильтр
    setTimeout(function() {
      if (typeof jQuery === 'undefined') return;
      var $ = jQuery;

      $('[id^="ocf-module-"]').each(function() {
        var $c = $(this);
        var pluginData = $.data(this, 'ocfilter');

        if (!pluginData) {
          // Фильтр не инициализирован — ждём ocfilter.js
          var tries = 0;
          var wait = setInterval(function() {
            tries++;
            if (typeof $.fn.ocfilter !== 'undefined') {
              clearInterval(wait);
              // Инициализируем с данными из data-атрибутов
              var opts = $c.data() || {};
              try { $c.ocfilter(opts); } catch(e) {}
            } else if (tries > 80) {
              clearInterval(wait);
            }
          }, 100);
        }
        // Если уже инициализирован — ничего не делаем (bfcache сохранил состояние)
      });
    }, 200);
  });

  // ===================================================
  // 3. Устанавливаем watchdog после загрузки jQuery
  // ===================================================
  function waitAndInstallWatchdog(tries) {
    tries = tries || 0;
    if (tries > 100) return;
    if (typeof jQuery !== 'undefined') {
      installFilterWatchdog();
    } else {
      setTimeout(function() { waitAndInstallWatchdog(tries + 1); }, 100);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() { waitAndInstallWatchdog(0); });
  } else {
    waitAndInstallWatchdog(0);
  }

  window.addEventListener('load', function() { waitAndInstallWatchdog(0); });

  // ===================================================
  // 4. Task 15: Каталог → прямой переход к первой категории (десктоп)
  // ===================================================
  function initCatalogButtonDirectLink() {
    var btn = document.querySelector('.ds-header-catalog-button.d-xl-flex, .ds-header-catalog-button');
    if (!btn) return;

    function isDesktop() {
      return window.innerWidth >= 1200;
    }

    btn.addEventListener('click', function(e) {
      if (!isDesktop()) return;

      var catalogMenu = btn.nextElementSibling;
      if (!catalogMenu || !catalogMenu.classList.contains('ds-menu-catalog')) {
        var parent = btn.parentElement;
        if (parent) catalogMenu = parent.querySelector('.ds-menu-main-catalog');
      }

      var firstLink = catalogMenu ? catalogMenu.querySelector('.ds-menu-catalog-item a') : null;

      if (firstLink && firstLink.href) {
        e.preventDefault();
        e.stopImmediatePropagation();
        window.location.href = firstLink.href;
        return;
      }

      var fallbackUrl = '/catalog';
      var anyMenuLink = document.querySelector('.ds-menu-catalog-item a[href*="route=product"]');
      if (anyMenuLink) fallbackUrl = anyMenuLink.href;

      e.preventDefault();
      e.stopImmediatePropagation();
      window.location.href = fallbackUrl;
    }, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCatalogButtonDirectLink);
  } else {
    initCatalogButtonDirectLink();
  }

})();

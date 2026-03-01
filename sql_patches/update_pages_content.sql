-- ============================================================
-- Патч: заполнение страниц "О компании" и "Оплата и доставка"
-- Находим information_id по title в oc_information_description
-- Дата: 2026-02-28
-- ============================================================

-- Для страницы "О компании" используем UPSERT-подход:
-- Сначала проверяем существование, если нет - создаём
-- Если есть - обновляем description и meta

-- ============================================================
-- Шаг 1: Найти ID страниц (запустите SELECT перед UPDATE)
-- SELECT information_id, title FROM `oc_information_description` WHERE language_id = (SELECT language_id FROM `oc_language` WHERE code = 'ru-ru' LIMIT 1);
-- ============================================================

-- Шаг 2: Обновляем "О компании" (замените @ABOUT_ID на реальный ID)
-- UPDATE `oc_information_description` SET
--   `description` = '...',
--   `meta_title` = 'Производство и поставки погружных насосов ЭЦВ | НасосыДаром',
--   `meta_description` = 'Компания «НасосыДаром» — производитель и поставщик погружных насосов ЭЦВ для водоснабжения. Собственное производство, доставка по всей России.'
-- WHERE information_id = @ABOUT_ID AND language_id = (SELECT language_id FROM `oc_language` WHERE code = 'ru-ru' LIMIT 1);

-- NOTE: Полный HTML-контент вставляется через PHP-скрипт update_pages.php

-- ============================================================
-- Патч: замена неправильной единицы мощности "I4кВт" на "кВт"
-- Таблица: oc_ocfilter_filter_description (suffix)
-- Также исправление в таблице oc_attribute_value (unit/text)
-- Дата: 2026-02-28
-- ============================================================

-- 1. Исправляем suffix в описаниях фильтров ocfilter
UPDATE `oc_ocfilter_filter_description`
SET `suffix` = REPLACE(`suffix`, 'I4кВт', 'кВт')
WHERE `suffix` LIKE '%I4кВт%';

-- 2. Также на случай если это хранится как "l4кВт" (строчная L)
UPDATE `oc_ocfilter_filter_description`
SET `suffix` = REPLACE(`suffix`, 'l4кВт', 'кВт')
WHERE `suffix` LIKE '%l4кВт%';

-- 3. Исправляем в названиях фильтров (name)
UPDATE `oc_ocfilter_filter_description`
SET `name` = REPLACE(`name`, 'I4кВт', 'кВт')
WHERE `name` LIKE '%I4кВт%';

-- 4. Исправляем в значениях атрибутов OpenCart (oc_product_attribute)
-- Если единица прописана прямо в тексте значения атрибута
UPDATE `oc_product_attribute`
SET `text` = REPLACE(`text`, 'I4кВт', 'кВт')
WHERE `text` LIKE '%I4кВт%';

UPDATE `oc_product_attribute`
SET `text` = REPLACE(`text`, 'l4кВт', 'кВт')
WHERE `text` LIKE '%l4кВт%';

-- 5. Исправляем в названиях атрибутов
UPDATE `oc_attribute_description`
SET `name` = REPLACE(`name`, 'I4кВт', 'кВт')
WHERE `name` LIKE '%I4кВт%';

-- 6. Исправляем в настройках расширений (oc_setting) если там хранится что-то
UPDATE `oc_setting`
SET `value` = REPLACE(`value`, 'I4кВт', 'кВт')
WHERE `value` LIKE '%I4кВт%';

-- Проверочный запрос (запустите после патча чтобы убедиться):
-- SELECT * FROM `oc_ocfilter_filter_description` WHERE `suffix` LIKE '%кВт%';
-- SELECT * FROM `oc_product_attribute` WHERE `text` LIKE '%кВт%' LIMIT 10;

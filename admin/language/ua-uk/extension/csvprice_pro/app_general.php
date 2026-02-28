<?php
// Heading
$_['heading_title'] = 'Основні';
$_['heading_title_normal'] = 'CSV Price Pro import/export OC3';

// Text
$_['text_module'] = 'Модулі';
$_['text_extension'] = 'Розширення';
$_['text_yes'] = 'Так';
$_['text_no'] = 'Ні';
$_['text_auto_ajax'] = 'Автоматичний AJAX';
$_['text_auto'] = 'Автоматичний';
$_['text_mirror'] = 'Дзеркало';
$_['text_manual'] = 'Ручной';
$_['text_success'] = 'Настройки успішно обновлені!';
$_['text_product_backup'] = 'Товари';
$_['text_category_backup'] = 'Категорії';
$_['text_manufacturer_backup'] = 'Виробники';
$_['text_product_all_backup'] = 'Товари, Категорії, Виробники';
$_['text_full_backup'] = 'Повний бекап';
$_['text_opencart_backup'] = 'OpenCart';
$_['text_raw_backup'] = 'MySQL Dump (DELETE TABLE, CREATE TABLE, INSERT)';
$_['text_core_update'] = 'Перезавантажити конфігурацію ядра';
$_['text_before_creating_indexes'] = 'Перед тем как добавить индексы переключитие OpenCart в режим обслуживания в разделе Настройки -> Список магазинов -> Изменить.';

// Buttons
$_['button_save'] = 'Зберегти';
$_['button_export'] = 'Експорт';
$_['button_delete_image_cache'] = 'Очистити таблицю з кешем зображень';
$_['button_create_index'] = 'Створіть усі відсутні індекси';

// Tabs
$_['tab_setting'] = 'Основні настройки';
$_['tab_tool_image'] = 'Інструменти для зображень';
$_['tab_tool_backup'] = 'Створення резервних копій';
$_['tab_tool_database'] = 'Оптимізація бази даних';

// Entry
$_['entry_csv_import_mod'] = 'Режим імпорту товаров';
$_['entry_each_iteration_timeout'] = 'Тайм-аут для кожної ітерації AJAX';
$_['entry_image_download_mod'] = 'Режим скачуванням зображення';
$_['entry_save_img_table'] = 'Зберегти таблицю з зображеннями';
$_['entry_work_directory'] = 'Рабочая директория';
$_['entry_product_log'] = 'Включить журнал імпорту товаров';
$_['entry_clear_image_cache'] = 'Видалити кеш зображень';
$_['entry_backup_data'] = 'Дані';
$_['entry_backup_type'] = 'Тип';
$_['entry_backup_zip'] = 'Використовувати архівацію ZIP';
$_['error_permission'] = 'У Вас немає прав для зміни CSV Price Pro import/export!';

// Prop Description
$_['prop_descr'] = ' 
prop_descr[1]="<p><b>Режим скачуванням зображення</b></p><p><i>Автоматичний</i> - Зображення будуть завантажені і збережені в автоматичному режимі, директорії та імена зображення будуть створюватися модулем по своєму власному алгоритму.</p><p><i>Дзеркало</i> - Імена зображень і директорії (шлях) по можливості будуть збережені в оіргінальном вигляді.</p>";
prop_descr[2]="<p><b>Зберегти таблицю з зображеннями</b></p><p>Якщо дана опція включена, то таблиця * csvprice_pro_images не очищено при видаленні модуля, всі дані про скачали зображеннях будуть збережені для подальшого використання.</p>";
prop_descr[3]="<p><b>Рабочая директория</b></p><p>Ця директорія повинна існувати і повинна бути доступна для запису.</p>";
prop_descr[4]="<p>Після встановлення розширення, який поддрежівается CSV Price Pro import/export, необхідно оновити конфігурацію ядра.</p>";
prop_descr[5]="<p>Ми рекомендуємо вам налаштувати параметр тайм-ауту кожної ітерації на 60-180 секунд, щоб дозволити кожній ітерації завершитися і коректно завершити роботу розширення.</p><p>Цей параметр повинен бути менше встановленого значення max_execution_time у налаштуваннях PHP.</p>";
';

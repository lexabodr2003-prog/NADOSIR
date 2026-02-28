<?php
// Heading
$_['heading_title'] = 'Планировщик заданий';
$_['heading_title_normal'] = 'CSV Price Pro import/export';

// Text Global
$_['text_module'] = 'Модули';
$_['text_extension'] = 'Расширения';
$_['text_default'] = ' <b>(По умолчанию)</b>';
$_['text_yes'] = 'Да';
$_['text_no'] = 'Нет';
$_['text_enabled'] = 'Включено';
$_['text_disabled'] = 'Отключено';
$_['text_select_all'] = 'Выделить все';
$_['text_unselect_all'] = 'Снять выделение';
$_['text_select'] = 'Выбрать';
$_['text_show_all'] = 'Показать все';
$_['text_hide_all'] = 'Скрыть не отмеченные';
$_['text_all'] = 'Все';
$_['text_no_results'] = 'Нет данных!';
$_['text_none'] = ' --- Не выбрано --- ';
$_['text_edit'] = 'Изменить';
$_['text_delete'] = 'Удалить';
$_['text_as'] = 'As %s';
$_['text_confirm_delete'] = 'Удаление невозможно отменить! Вы уверены, что хотите это сделать?';
$_['text_success'] = 'Настройки успешно обновлены!';

// Text
$_['text_hours'] = 'Часы';
$_['text_minutes'] = 'Минуты';
$_['text_import'] = 'Импорт';
$_['text_import_category'] = 'Импорт категорий';
$_['text_import_manufacturer'] = 'Импорт производителей';
$_['text_import_order'] = 'Импорт заказов';
$_['text_export'] = 'Экспорт';
$_['text_export_product'] = 'Экспорт товаров';
$_['text_export_category'] = 'Экспорт категорий';
$_['text_export_manufacturer'] = 'Экспорт производителей';
$_['text_export_order'] = 'Экспорт заказов';
$_['text_import_product'] = 'Импорт товаров';

$_['cron_description'] = '
<p><strong>Cron (Command Run ON)</strong> — планировщик задач. Используется для выполнения команд и скриптов на сервере хостинга в определённое время.</p>
<p>Для того что бы добавить новую задачу на хостинге, необходимо воспользоваться инструкцией для настройки соответствующего раздела в панели управления аккаунтом вашего хостинга или при подключении по SSH.
Соответствующий раздел в панелях управления может называться "Cron", "Crontab" или "Планировщик", для получения инструкций и дополнительной информации обратитесь в службу технической поддержки вашего хостинга.</p>
<p>&nbsp;<br>Для того что бы запустить <strong>CSV Price Pro import/export</strong> используя Cron на хостинге, вам потребуется добавить команду на выполнение PHP-скрипта:</p>
<pre>php -d max_execution_time=0 %s -k 1619759987 > /dev/null 2>&1</pre>
<p>Параметр <strong>-d max_execution_time=0</strong> указывает не ограничивать выполнение PHP-скрипта по времени.</p>
<p>Параметр <strong>-k</strong> и номер <strong>(Ключ)</strong> указывает какую именно задачу нужно запустить, список доступных команд можно посмотреть если нажать на <button disabled type="button" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></button>  в списке задач.</p>
<p>Как именно должна быть указана команда на выполнение PHP-скрипта вы найдете в инструкции для настройки соответствующего раздела в панели управления аккаунтом вашего хостинга или уточнив в службе технической поддержки вашего хостинга.</p>
';

// Butons
$_['button_save'] = 'Сохранить';
$_['button_cancel'] = 'Отменить';
$_['button_delete'] = 'Удалить';
$_['button_add'] = 'Добавить';
$_['button_view'] = 'Просмотр';
$_['button_close'] = 'Закрыть';
$_['button_edit'] = 'Изменить';
$_['button_show_description'] = 'Показать описание';
$_['button_hide_description'] = 'Скрыть описание';

// Columns
$_['column_job_id'] = 'id';
$_['column_job_type'] = 'Тип';
$_['column_profile_name'] = 'Профиль';
$_['column_job_key'] = 'Ключ';
$_['column_job_file_location'] = 'Источник / Назначение';
$_['column_job_time_start'] = 'Время запуска';
$_['column_status'] = 'Статус';
$_['column_job_file'] = 'Файл';
$_['column_action'] = 'Действие';

// Entry
$_['entry_job_id'] = 'id';
$_['entry_job_type'] = 'Тип';
$_['entry_profile'] = 'Профиль';
$_['entry_file_location'] = 'Источник / Назначение';
$_['entry_time_start'] = 'Время запуска';
$_['entry_ftp_host'] = 'FTP хост';
$_['entry_ftp_user'] = 'FTP логин';
$_['entry_ftp_passwd'] = 'FTP пароль';
$_['entry_file_path'] = 'Путь к файлу/URL';
$_['entry_status'] = 'Статус';
$_['entry_job_offline'] = 'Запуск по времени';

// Error
$_['error_permission'] = 'У Вас нет прав для управления модулем CSV Price Pro import/export!';
$_['error_import_field_caption'] = 'Incorrect CSV header';

// Prop Description
$_['prop_descr'] = 'prop_descr[0]="<p><b>FTP хост</b></p><p>Имя сервера хостинга, IP-адрес сервера хостинга или домен<br>Через двоеточие можно указать FTP порт (можно не указывать)<br><br>Пример с FTP портом: <b>exmple.com:21</b></p>";';
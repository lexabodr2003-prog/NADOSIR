<?php
// Heading
$_['heading_title'] = 'Планувальник завдань';
$_['heading_title_normal'] = 'CSV Price Pro import/export';

// Text Global
$_['text_module'] = 'Модулі';
$_['text_extension'] = 'Розширення';
$_['text_default'] = ' <b>(За промовчанням)</b>';
$_['text_yes'] = 'Так';
$_['text_no'] = 'Ні';
$_['text_enabled'] = 'Включено';
$_['text_disabled'] = 'Відключено';
$_['text_select_all'] = 'Виділити все';
$_['text_unselect_all'] = 'Зняти виділення';
$_['text_select'] = 'Виділити';
$_['text_show_all'] = 'Показати все';
$_['text_hide_all'] = 'Приховати не позначені';
$_['text_all'] = 'Всі';
$_['text_no_results'] = 'Немає даних!';
$_['text_none'] = ' --- Не вибрано --- ';
$_['text_edit'] = 'Редагувати';
$_['text_delete'] = 'Видалити';
$_['text_as'] = 'У вигляді %s';
$_['text_confirm_delete'] = 'Видалення неможливо скасувати! Ви впевнені, що бажаєте це зробити?';
$_['text_success'] = 'Налаштування вдало оновлені!';

// Text
$_['text_hours'] = 'Година';
$_['text_minutes'] = 'Хвилина';
$_['text_import'] = 'Імпорт';
$_['text_import_product'] = 'Імпорт товарів';
$_['text_import_category'] = 'Імпорт категорій';
$_['text_import_manufacturer'] = 'Імпорт виробників';
$_['text_import_order'] = 'Імпорт замовлень';
$_['text_export'] = 'Експорт';
$_['text_export_product'] = 'Експорт товарів';
$_['text_export_category'] = 'Експорт категорій';
$_['text_export_manufacturer'] = 'Експорт виробників';
$_['text_export_order'] = 'Експорт замовлень';

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

// Buttons
$_['button_save'] = 'Зберегти';
$_['button_cancel'] = 'Скасувати';
$_['button_delete'] = 'Видалити';
$_['button_view'] = 'Перегляд';
$_['button_close'] = 'Закрити';
$_['button_edit'] = 'Редагувати';
$_['button_add'] = 'Додати';
$_['button_show_description'] = 'Показати опис';
$_['button_hide_description'] = 'Приховати опис';

// Columns
$_['column_job_id'] = 'id';
$_['column_job_type'] = 'Тип';
$_['column_profile_name'] = 'Профіль';
$_['column_job_key'] = 'Ключ';
$_['column_job_file_location'] = 'Джерело / Призначення';
$_['column_job_time_start'] = 'Час запуску';
$_['column_job_file'] = 'Файл';
$_['column_status'] = 'Статус';
$_['column_action'] = 'Дія';

// Entry
$_['entry_job_id'] = 'id';
$_['entry_job_type'] = 'Тип';
$_['entry_profile'] = 'Профіль';
$_['entry_file_location'] = 'Джерело / Призначення';
$_['entry_time_start'] = 'Час запуску';
$_['entry_ftp_host'] = 'FTP хост';
$_['entry_ftp_user'] = 'FTP логін';
$_['entry_ftp_passwd'] = 'FTP пароль';
$_['entry_file_path'] = 'Шлях до файлу/URL';
$_['entry_status'] = 'Статус';
$_['entry_job_offline'] = 'Запуск за часом';

// Error
$_['error_permission'] = 'У Вас немає прав для зміни CSV Price Pro import/export!';
$_['error_import_field_caption'] = 'Не знайдено рядок із заголовками CSV';

// Prop Description
$_['prop_descr'] = 'prop_descr[0]="<p><b>FTP хост</ b></ p><p>Ім\'я сервера хостингу, IP-адреса сервера хостингу або домен <br> Через двокрапку можна вказати FTP порт (можна невказувати)<br><br>приклад з FTP портом:<b>exmple.com:21</b></p>";';
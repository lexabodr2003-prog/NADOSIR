<?php
/**
 * update_video.php — заменяет Rutube iframe в HTML-модуле "Видео на главной"
 * 
 * Запуск: https://насосыдаром.рф/update_video.php?key=video2026
 * УДАЛИТЬ файл после выполнения!
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'video2026') {
    http_response_code(403);
    die('Access denied. Use ?key=video2026');
}

// DB credentials — читаем из config.php
require_once(__DIR__ . '/config.php');

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
if ($db->connect_error) {
    die('DB connect error: ' . $db->connect_error);
}
$db->set_charset('utf8');

// Новый Rutube iframe (с красной кнопкой)
$new_iframe = '<iframe width="720" height="405" src="https://rutube.ru/play/embed/492f623d6991c245c71702d6fdae7f75?skinColor=7CB342" allow="clipboard-write; autoplay" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';

// Получаем текущий модуль
$r = $db->query("SELECT setting FROM oc_module WHERE module_id = 33 LIMIT 1");
if (!$r) {
    die('Query error: ' . $db->error);
}
$row = $r->fetch_assoc();
if (!$row) {
    die('Module 33 not found');
}

$setting = json_decode($row['setting'], true);
echo '<pre>';
echo "Current iframe found in module:\n";

// Заменяем старый iframe на новый
if (isset($setting['module_description'])) {
    foreach ($setting['module_description'] as $lang_id => &$desc) {
        if (isset($desc['description'])) {
            // Ищем и заменяем старый rutube iframe
            $old_desc = $desc['description'];
            $new_desc = preg_replace(
                '/<iframe[^>]*rutube\.ru[^>]*>.*?<\/iframe>/si',
                $new_iframe,
                $old_desc
            );
            if ($new_desc !== $old_desc) {
                echo "✅ Заменён iframe в lang_id=$lang_id\n";
                $desc['description'] = $new_desc;
            } else {
                echo "⚠️ iframe не найден в lang_id=$lang_id, добавляем принудительно\n";
                // Если iframe не нашли — ищем div.video-wrapper и вставляем внутрь
                $desc['description'] = preg_replace(
                    '/(<div class="video-wrapper">)[\s\S]*?(<\/div>)/i',
                    '$1' . "\n      " . $new_iframe . "\n    " . '$2',
                    $old_desc
                );
            }
        }
    }
    unset($desc);
}

$new_setting = json_encode($setting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$stmt = $db->prepare("UPDATE oc_module SET setting = ? WHERE module_id = 33");
$stmt->bind_param("s", $new_setting);
if ($stmt->execute()) {
    echo "✅ Модуль 33 (Видео на главной) обновлён в БД\n";
} else {
    echo "❌ Ошибка обновления: " . $stmt->error . "\n";
}
$stmt->close();

echo "\n⚠️ УДАЛИТЕ ЭТОТ ФАЙЛ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!\n";
echo "</pre>";

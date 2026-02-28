<?php
/**
 * Скрипт исправления единицы мощности: I4кВт → кВт
 * 
 * ВАЖНО: После использования УДАЛИТЬ ЭТОТ ФАЙЛ с сервера!
 * 
 * Запуск: https://насосыдаром.рф/fix_units.php?key=fix_units_2026
 */

// Защитный ключ
define('SECRET_KEY', 'fix_units_2026');

if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    die('Access denied');
}

// Подключаем конфиг OpenCart
define('DIR_ROOT', dirname(__FILE__) . '/');
require_once DIR_ROOT . 'config.php';

// Подключение к БД
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_error) {
    die('DB Error: ' . $db->connect_error);
}
$db->set_charset('utf8mb4');

$prefix = DB_PREFIX;
$results = [];

// Варианты написания некорректной единицы
$wrong_values = ['I4кВт', 'l4кВт', 'І4кВт', ' I4кВт', 'I4 кВт'];
$correct = 'кВт';

// Таблицы для обработки
$tables = [
    [$prefix . 'ocfilter_filter_description', 'suffix'],
    [$prefix . 'ocfilter_filter_description', 'name'],
    [$prefix . 'product_attribute', 'text'],
    [$prefix . 'attribute_description', 'name'],
    [$prefix . 'setting', 'value'],
];

foreach ($tables as [$table, $field]) {
    foreach ($wrong_values as $wrong) {
        $escaped_wrong = $db->real_escape_string($wrong);
        $escaped_correct = $db->real_escape_string($correct);
        
        // Проверяем есть ли такие записи
        $check = $db->query("SELECT COUNT(*) as cnt FROM `{$table}` WHERE `{$field}` LIKE '%" . $escaped_wrong . "%'");
        if ($check) {
            $row = $check->fetch_assoc();
            if ($row['cnt'] > 0) {
                $sql = "UPDATE `{$table}` SET `{$field}` = REPLACE(`{$field}`, '{$escaped_wrong}', '{$escaped_correct}') WHERE `{$field}` LIKE '%" . $escaped_wrong . "%'";
                $db->query($sql);
                $results[] = "✅ {$table}.{$field}: заменено {$db->affected_rows} записей ('{$wrong}' → '{$correct}')";
            }
        }
    }
}

$db->close();

echo '<pre>';
echo "=== Результаты исправления единиц мощности ===\n\n";
if (empty($results)) {
    echo "Ничего не найдено. Возможно, данные уже исправлены или хранятся в другом формате.\n";
} else {
    foreach ($results as $r) {
        echo $r . "\n";
    }
}
echo "\n⚠️ УДАЛИТЕ ЭТОТ ФАЙЛ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!\n";
echo '</pre>';

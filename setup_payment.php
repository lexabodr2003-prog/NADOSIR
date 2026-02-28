<?php
/**
 * Скрипт настройки способов оплаты насосыдаром.рф
 *
 * Включает:
 *  1. tbank        — «Оплата картой или СБП онлайн» (для физических лиц)
 *  2. bank_transfer — «Выставление счёта» (для юридических лиц)
 *  3. free_checkout — для заказов на 0 руб (на всякий случай отключаем)
 *
 * ВАЖНО: После использования УДАЛИТЬ ЭТОТ ФАЙЛ с сервера!
 * Запуск: https://насосыдаром.рф/setup_payment.php?key=payment2026
 */

define('SECRET_KEY', 'payment2026');
if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    die('Access denied');
}

define('DIR_ROOT', dirname(__FILE__) . '/');
require_once DIR_ROOT . 'config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_error) die('DB Error: ' . $db->connect_error);
$db->set_charset('utf8mb4');

$p = DB_PREFIX;
$results = [];

function setSetting($db, $p, $key, $value, $serialized = 0) {
    global $results;
    $k = $db->real_escape_string($key);
    $v = $db->real_escape_string($value);
    $check = $db->query("SELECT setting_id FROM `{$p}setting` WHERE store_id=0 AND `key`='{$k}' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $db->query("UPDATE `{$p}setting` SET value='{$v}', serialized={$serialized} WHERE store_id=0 AND `key`='{$k}'");
        $results[] = "✅ UPDATE {$key} = {$value}";
    } else {
        // Определяем code из key
        $code = preg_replace('/_[^_]+$/', '', $key);
        $db->query("INSERT INTO `{$p}setting` (store_id, code, `key`, value, serialized) VALUES (0, '{$code}', '{$k}', '{$v}', {$serialized})");
        $results[] = "✅ INSERT {$key} = {$value}";
    }
}

function ensureExtension($db, $p, $type, $code) {
    global $results;
    $t = $db->real_escape_string($type);
    $c = $db->real_escape_string($code);
    $check = $db->query("SELECT extension_id FROM `{$p}extension` WHERE type='{$t}' AND code='{$c}' LIMIT 1");
    if (!$check || $check->num_rows == 0) {
        $db->query("INSERT INTO `{$p}extension` (type, code) VALUES ('{$t}', '{$c}')");
        $results[] = "✅ Зарегистрировано расширение {$type}/{$code}";
    } else {
        $results[] = "ℹ️ Расширение {$type}/{$code} уже зарегистрировано";
    }
}

// ─────────────────────────────────────────────────────────
// 1. Т-БАНК — Оплата картой / СБП (для физических лиц)
//    Требует terminal_key и secret_key от Т-Банк.
//    Включаем модуль, реквизиты нужно вставить вручную.
// ─────────────────────────────────────────────────────────
ensureExtension($db, $p, 'payment', 'tbank');
setSetting($db, $p, 'payment_tbank_status',          '1');
setSetting($db, $p, 'payment_tbank_sort_order',       '1');
// Terminal Key и Secret Key — оставляем пустыми (нужно заполнить в админке)
// setSetting($db, $p, 'payment_tbank_terminal',      'YOUR_TERMINAL_KEY');
// setSetting($db, $p, 'payment_tbank_secret_key',    'YOUR_SECRET_KEY');
$results[] = "⚠️ Т-Банк: укажите Terminal Key и Secret Key в Расширения → Оплата → Т-Банк";

// ─────────────────────────────────────────────────────────
// 2. BANK TRANSFER — Выставление счёта (для юрлиц)
// ─────────────────────────────────────────────────────────
ensureExtension($db, $p, 'payment', 'bank_transfer');
setSetting($db, $p, 'payment_bank_transfer_status',     '1');
setSetting($db, $p, 'payment_bank_transfer_sort_order', '2');
setSetting($db, $p, 'payment_bank_transfer_geo_zone_id', '0'); // 0 = все зоны
setSetting($db, $p, 'payment_bank_transfer_total',       '0'); // без мин. суммы
// Реквизиты для счёта (language_id обычно 4 для ru-ru, уточняем)
$lang_q = $db->query("SELECT language_id FROM `{$p}language` WHERE code='ru-ru' LIMIT 1");
$lang_id = ($lang_q && ($lr = $lang_q->fetch_assoc())) ? (int)$lr['language_id'] : 4;
$bank_details = $db->real_escape_string(
    "Получатель: ООО / ИП «НасосыДаром»\n" .
    "ИНН: (укажите ваш ИНН)\n" .
    "КПП: (укажите ваш КПП)\n" .
    "Банк: (укажите банк)\n" .
    "БИК: (укажите БИК)\n" .
    "Р/с: (укажите расчётный счёт)\n" .
    "К/с: (укажите корр. счёт)\n" .
    "Назначение платежа: Оплата по счёту № [номер заказа]"
);
$key = 'payment_bank_transfer_bank' . $lang_id;
setSetting($db, $p, $key, $bank_details);
$results[] = "ℹ️ Bank transfer: заполните реквизиты в Расширения → Оплата → Банковский перевод";

// ─────────────────────────────────────────────────────────
// 3. COD — Наложенный платёж (отключаем — не актуален)
// ─────────────────────────────────────────────────────────
setSetting($db, $p, 'payment_cod_status', '0');
$results[] = "ℹ️ Наложенный платёж (COD) — отключён";

// ─────────────────────────────────────────────────────────
// 4. FREE CHECKOUT — отключаем
// ─────────────────────────────────────────────────────────
setSetting($db, $p, 'payment_free_checkout_status', '0');
$results[] = "ℹ️ Free checkout — отключён";

// ─────────────────────────────────────────────────────────
// 5. Проверяем shipping pickup — убеждаемся что включён
// ─────────────────────────────────────────────────────────
ensureExtension($db, $p, 'shipping', 'pickup');
setSetting($db, $p, 'shipping_pickup_status',     '1');
setSetting($db, $p, 'shipping_pickup_sort_order', '1');
$results[] = "✅ Shipping pickup (По согласованию с менеджером) — включён";

$db->close();

echo '<pre>';
echo "=== Настройка способов оплаты и доставки ===\n\n";
foreach ($results as $r) echo $r . "\n";
echo "\n⚠️ ПОСЛЕ ИСПОЛЬЗОВАНИЯ УДАЛИТЕ ЭТОТ ФАЙЛ!\n";
echo "\n📋 Следующие шаги:\n";
echo "  1. Т-Банк: Расширения → Оплата → Т-Банк → укажите Terminal Key + Secret Key\n";
echo "  2. Счёт: Расширения → Оплата → Банковский перевод → заполните реквизиты\n";
echo "  3. Очистить кэш: Расширения → Модификации → Обновить\n";
echo '</pre>';

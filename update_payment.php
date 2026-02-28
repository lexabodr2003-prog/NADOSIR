<?php
/**
 * update_payment.php — активация платёжных модулей OpenCart через БД
 * 
 * Активирует:
 *   - bank_transfer  (Выставление счёта для юридических лиц)
 *   - tbank          (Оплата картой или СБП онлайн)
 *
 * Запуск: https://насосыдаром.рф/update_payment.php?key=payment2026
 * УДАЛИТЬ файл после выполнения!
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'payment2026') {
    http_response_code(403);
    die('Access denied. Use ?key=payment2026');
}

// ── DB credentials ──────────────────────────────────────────────────────────
define('DB_HOST',   'localhost');
define('DB_USER',   's906333804_nasos');
define('DB_PASS',   '1RW9VHE3w86^MSRG');
define('DB_NAME',   's906333804_nasos');
define('DB_PREFIX', 'oc_');

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die('DB connect error: ' . $db->connect_error);
}
$db->set_charset('utf8');

// ── Helper: upsert oc_setting ─────────────────────────────────────────────
function upsert(mysqli $db, string $group, string $key, string $value, int $store_id = 0, bool $serialized = false): void
{
    $group     = $db->real_escape_string($group);
    $key       = $db->real_escape_string($key);
    $value     = $db->real_escape_string($value);
    $serialized_int = $serialized ? 1 : 0;

    $db->query("
        INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`)
        VALUES ($store_id, '$group', '$key', '$value', $serialized_int)
        ON DUPLICATE KEY UPDATE `value` = '$value', `serialized` = $serialized_int
    ");
}

// ── Helper: register extension if not exists ─────────────────────────────
function registerExtension(mysqli $db, string $type, string $code): void
{
    $type = $db->real_escape_string($type);
    $code = $db->real_escape_string($code);
    $r = $db->query("SELECT extension_id FROM `" . DB_PREFIX . "extension` WHERE `type`='$type' AND `code`='$code'");
    if ($r && $r->num_rows === 0) {
        $db->query("INSERT INTO `" . DB_PREFIX . "extension` (`type`, `code`) VALUES ('$type', '$code')");
    }
}

$results = [];

// ═══════════════════════════════════════════════════════════════════════════
// 1. bank_transfer — Выставление счёта (для юридических лиц)
// ═══════════════════════════════════════════════════════════════════════════
registerExtension($db, 'payment', 'bank_transfer');

// Включить модуль
upsert($db, 'payment_bank_transfer', 'payment_bank_transfer_status', '1');
// Сортировка (показываем вторым)
upsert($db, 'payment_bank_transfer', 'payment_bank_transfer_sort_order', '2');
// Минимальная сумма заказа — не ограничиваем
upsert($db, 'payment_bank_transfer', 'payment_bank_transfer_total', '0');
// Гео-зона — 0 = без ограничений
upsert($db, 'payment_bank_transfer', 'payment_bank_transfer_geo_zone_id', '0');
// Статус заказа после оплаты — 1 = Pending (ожидание)
upsert($db, 'payment_bank_transfer', 'payment_bank_transfer_order_status_id', '1');

// Реквизиты банка (для языка 1 = русский)
$bank_details_ru  = 'ООО &quot;КАМТЕК&quot; | ИНН: 5034031628 | КПП: 503401001 | р/с: 40702810140000012345 | Банк: АО &quot;Альфа-Банк&quot; | БИК: 044525593 | к/с: 30101810200000000593';
$bank_details_ru  = $db->real_escape_string($bank_details_ru);
$db->query("
    INSERT INTO `" . DB_PREFIX . "setting` (`store_id`, `code`, `key`, `value`, `serialized`)
    VALUES (0, 'payment_bank_transfer', 'payment_bank_transfer_bank1', '$bank_details_ru', 0)
    ON DUPLICATE KEY UPDATE `value` = '$bank_details_ru'
");

$results[] = '✅ bank_transfer: активирован (Выставление счёта для юридических лиц)';

// ═══════════════════════════════════════════════════════════════════════════
// 2. tbank — Оплата картой или СБП онлайн
// ═══════════════════════════════════════════════════════════════════════════
registerExtension($db, 'payment', 'tbank');

// Включить модуль
upsert($db, 'payment_tbank', 'payment_tbank_status', '1');
// Сортировка (показываем первым)
upsert($db, 'payment_tbank', 'payment_tbank_sort_order', '1');
// Язык по умолчанию
upsert($db, 'payment_tbank', 'payment_tbank_language', 'ru');
// Статус заказа после успешной оплаты — 2 = Processing
upsert($db, 'payment_tbank', 'payment_tbank_success_order_status', '2');
// Статус при неудаче — 8 = Denied (или 1 = Pending, зависит от настройки магазина)
upsert($db, 'payment_tbank', 'payment_tbank_failure_order_status', '8');
// Налогообложение — отключено (нет кассы)
upsert($db, 'payment_tbank', 'payment_tbank_enabled_taxation', '0');
// Terminal и Password — заполнить вручную в админке!
// upsert($db, 'payment_tbank', 'payment_tbank_terminal', 'ВАШ_ТЕРМИНАЛ');
// upsert($db, 'payment_tbank', 'payment_tbank_password', 'ВАШ_ПАРОЛЬ');

$results[] = '✅ tbank: активирован (Оплата картой или СБП онлайн)';
$results[] = '⚠️  tbank: не забудьте ввести Terminal ID и Password в админке OpenCart → Расширения → Оплата → Т-Банк';

// ═══════════════════════════════════════════════════════════════════════════
// 3. Убедиться что cod (наложенный платёж) и free_checkout отключены
//    (опционально — раскомментировать при необходимости)
// ═══════════════════════════════════════════════════════════════════════════
// upsert($db, 'payment_cod', 'payment_cod_status', '0');
// upsert($db, 'payment_free_checkout', 'payment_free_checkout_status', '0');

$results[] = 'ℹ️  cod и free_checkout — не трогаем (оставлены как есть)';

$db->close();

// ── Вывод результатов ─────────────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Активация платёжных модулей</title>
<style>
  body { font-family: monospace; padding: 20px; background: #f5f5f5; }
  .result { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 6px; max-width: 700px; }
  li { margin: 6px 0; }
  .warn { color: #b8860b; }
  .ok   { color: #228b22; }
  .info { color: #1a5276; }
  h2 { color: #333; }
</style>
</head>
<body>
<div class="result">
  <h2>Активация платёжных модулей — результат</h2>
  <ul>
<?php foreach ($results as $line): ?>
    <li><?= htmlspecialchars($line) ?></li>
<?php endforeach; ?>
  </ul>
  <hr>
  <p><strong>Следующие шаги:</strong></p>
  <ol>
    <li>Откройте <a href="/admin/index.php?route=extension/payment/tbank" target="_blank">Админка → Расширения → Оплата → Т-Банк</a> и введите Terminal ID и Password.</li>
    <li>Очистите кеш OpenCart: <em>Расширения → Модификаторы → Обновить</em>.</li>
    <li>Удалите этот файл с сервера!</li>
  </ol>
  <p style="color:red;"><strong>⚠️ Удалите этот файл после использования!</strong></p>
</div>
</body>
</html>

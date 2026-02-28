<?php
/**
 * Скрипт заполнения информационных страниц сайта насосыдаром.рф
 *
 * Страницы:
 *  1. "О компании"        (SEO: /o-kompanii)
 *  2. "Оплата и доставка" (SEO: /payment-and-delivery)
 *  3. "Публичная оферта"  (SEO: /oferta) — создаётся если не существует
 *
 * ВАЖНО: После использования УДАЛИТЬ ЭТОТ ФАЙЛ с сервера!
 * Запуск: https://насосыдаром.рф/update_pages.php?key=pages2026
 */

define('SECRET_KEY', 'pages2026');

if (!isset($_GET['key']) || $_GET['key'] !== SECRET_KEY) {
    die('Access denied');
}

define('DIR_ROOT', dirname(__FILE__) . '/');
require_once DIR_ROOT . 'config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_error) {
    die('DB Error: ' . $db->connect_error);
}
$db->set_charset('utf8mb4');

$prefix = DB_PREFIX;
$results = [];

// ──────────────────────────────────────────────
// Вспомогательная функция
// ──────────────────────────────────────────────
function esc($db, $s) {
    return $db->real_escape_string($s);
}

function getLanguageId($db, $prefix, $code = 'ru-ru') {
    $r = $db->query("SELECT language_id FROM `{$prefix}language` WHERE code = '{$code}' LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) return (int)$row['language_id'];
    // Fallback: первый язык
    $r = $db->query("SELECT language_id FROM `{$prefix}language` ORDER BY language_id LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) return (int)$row['language_id'];
    return 1;
}

function getInformationIdByTitle($db, $prefix, $lang_id, $search_titles) {
    foreach ($search_titles as $title) {
        $t = esc($db, $title);
        $r = $db->query("SELECT information_id FROM `{$prefix}information_description`
                         WHERE language_id = {$lang_id} AND title LIKE '%{$t}%' LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) return (int)$row['information_id'];
    }
    return null;
}

function getInformationIdBySeoUrl($db, $prefix, $keywords) {
    foreach ($keywords as $kw) {
        $k = esc($db, $kw);
        $r = $db->query("SELECT `query` FROM `{$prefix}seo_url`
                         WHERE keyword LIKE '%{$k}%' LIMIT 1");
        if ($r && $row = $r->fetch_assoc()) {
            // query = 'route=information/information&information_id=X'
            if (preg_match('/information_id=(\d+)/', $row['query'], $m)) {
                return (int)$m[1];
            }
        }
    }
    return null;
}

function upsertInformationPage($db, $prefix, $lang_id, $information_id, $title, $description, $meta_title, $meta_description, $meta_keyword = '') {
    global $results;

    if ($information_id === null) {
        // Создать запись в oc_information
        $db->query("INSERT IGNORE INTO `{$prefix}information`
            (sort_order, bottom, status)
            VALUES (99, 0, 1)");
        $information_id = (int)$db->insert_id;
        if (!$information_id) {
            $results[] = "⚠️ Не удалось создать запись information для '{$title}'";
            return null;
        }
        $results[] = "✅ Создана новая страница information_id={$information_id} для '{$title}'";
    }

    $t   = esc($db, $title);
    $d   = esc($db, $description);
    $mt  = esc($db, $meta_title);
    $md  = esc($db, $meta_description);
    $mk  = esc($db, $meta_keyword);

    // Проверяем есть ли уже запись в description
    $check = $db->query("SELECT information_id FROM `{$prefix}information_description`
                         WHERE information_id = {$information_id} AND language_id = {$lang_id} LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $db->query("UPDATE `{$prefix}information_description` SET
            `title`            = '{$t}',
            `description`      = '{$d}',
            `meta_title`       = '{$mt}',
            `meta_description` = '{$md}',
            `meta_keyword`     = '{$mk}'
            WHERE information_id = {$information_id} AND language_id = {$lang_id}");
        $results[] = "✅ Обновлена страница information_id={$information_id} '{$title}' (affected: {$db->affected_rows})";
    } else {
        $db->query("INSERT INTO `{$prefix}information_description`
            (information_id, language_id, title, description, meta_title, meta_description, meta_keyword)
            VALUES ({$information_id}, {$lang_id}, '{$t}', '{$d}', '{$mt}', '{$md}', '{$mk}')");
        $results[] = "✅ Создана description для information_id={$information_id} '{$title}'";
    }

    return $information_id;
}

// ──────────────────────────────────────────────
// Определяем language_id
// ──────────────────────────────────────────────
$lang_id = getLanguageId($db, $prefix);
$results[] = "ℹ️ Язык ru-ru: language_id={$lang_id}";

// ──────────────────────────────────────────────
// 1. СТРАНИЦА "О КОМПАНИИ"
// ──────────────────────────────────────────────
$about_id = getInformationIdBySeoUrl($db, $prefix, ['o-kompanii', 'about', 'o-company']);
if (!$about_id) {
    $about_id = getInformationIdByTitle($db, $prefix, $lang_id, ['О компании', 'о компании', 'About']);
}
$results[] = "ℹ️ О компании: information_id=" . ($about_id ?: 'не найдено, будет создано');

$about_description = <<<'HTML'
<div class="about-company">

  <p><strong>Компания «НасосыДаром»</strong> — производитель и официальный поставщик погружных насосов серии ЭЦВ для артезианского и скважинного водоснабжения. Мы работаем напрямую с заводами-изготовителями, что позволяет предлагать продукцию по ценам ниже рыночных — без посредников.</p>

  <h2>Собственное производство — наше главное преимущество</h2>
  <p>В отличие от перекупщиков, «НасосыДаром» сотрудничает непосредственно с производственными предприятиями. Насосы серий <strong>4&quot; (4"), 5&quot;, 6&quot;, 8&quot; и 10&quot;</strong> соответствуют российским ГОСТам и адаптированы под характеристики отечественных скважин:</p>
  <ul>
    <li>Адаптированы к особенностям российских скважин и водоносных горизонтов</li>
    <li>Все узлы ремонтопригодны — сервисное обслуживание в любом регионе</li>
    <li>Заводская гарантия на каждое изделие</li>
    <li>Сертификаты соответствия ГОСТ прилагаются к каждой поставке</li>
  </ul>

  <h2>Доставка по всей России</h2>
  <p>Мы организуем поставки насосного оборудования <strong>во все регионы России</strong>. Склад и самовывоз расположены в <strong>г. Лобня (Московская область)</strong>. Отправляем транспортными компаниями: СДЭК, ПЭК, Деловые Линии, Байкал Сервис и другими — по выбору клиента.</p>
  <ul>
    <li>Срок доставки: 3–10 рабочих дней в зависимости от региона</li>
    <li>Надёжная упаковка исключает повреждение при транспортировке</li>
    <li>Страхование груза по запросу</li>
  </ul>

  <h2>Ассортимент продукции</h2>
  <p>Основное направление — <strong>погружные скважинные насосы ЭЦВ</strong> для водоснабжения частных домов, дачных участков, предприятий агропромышленного комплекса и промышленных объектов:</p>
  <ul>
    <li><strong>4" насосы ЭЦВ</strong> — для скважин диаметром от 110 мм, бытовое и дачное водоснабжение</li>
    <li><strong>5" насосы ЭЦВ</strong> — повышенная производительность, малоэтажное строительство</li>
    <li><strong>6" насосы ЭЦВ</strong> — для глубоких артезианских скважин, коттеджные посёлки</li>
    <li><strong>8" и 10" насосы ЭЦВ</strong> — промышленное водоснабжение, орошение, водозаборные станции</li>
  </ul>

  <h2>Почему выбирают «НасосыДаром»</h2>
  <ul>
    <li>🏭 Прямые поставки с завода — цены без наценки посредников</li>
    <li>📦 Широкий склад в Лобне — большинство позиций в наличии</li>
    <li>🛡️ Гарантия завода-изготовителя на всю продукцию</li>
    <li>🚚 Доставка по всей России транспортными компаниями</li>
    <li>📞 Консультации специалистов по подбору оборудования</li>
    <li>🔧 Помощь в подборе насоса под параметры вашей скважины</li>
  </ul>

  <h2>Контакты</h2>
  <p>📍 Склад и самовывоз: <strong>Московская область, г. Лобня</strong><br>
  📧 E-mail: <a href="mailto:info@kamtek.pro">info@kamtek.pro</a><br>
  🌐 Сайт: <a href="https://насосыдаром.рф">насосыдаром.рф</a></p>

</div>
HTML;

$about_id = upsertInformationPage(
    $db, $prefix, $lang_id, $about_id,
    'О компании',
    $about_description,
    'О компании «НасосыДаром» — производитель насосов ЭЦВ | насосыдаром.рф',
    'Компания НасосыДаром — производитель и поставщик погружных насосов ЭЦВ для водоснабжения. Прямые поставки с завода, доставка по всей России.',
    'насосы ЭЦВ, производитель насосов, о компании, насосыдаром'
);

// ──────────────────────────────────────────────
// 2. СТРАНИЦА "ОПЛАТА И ДОСТАВКА"
// ──────────────────────────────────────────────
$delivery_id = getInformationIdBySeoUrl($db, $prefix, ['payment-and-delivery', 'oplata', 'dostavka', 'delivery']);
if (!$delivery_id) {
    $delivery_id = getInformationIdByTitle($db, $prefix, $lang_id, ['Оплата и доставка', 'оплата', 'доставка', 'Delivery']);
}
$results[] = "ℹ️ Оплата и доставка: information_id=" . ($delivery_id ?: 'не найдено, будет создано');

$delivery_description = <<<'HTML'
<div class="payment-delivery">

  <h2>Способы оплаты</h2>
  <ul>
    <li><strong>Наличными</strong> при самовывозе из офиса/склада в г. Лобня</li>
    <li><strong>Безналичный расчёт</strong> — перевод на расчётный счёт (для юридических лиц и ИП)</li>
    <li><strong>Оплата картой</strong> онлайн при оформлении заказа</li>
    <li><strong>Наложенный платёж</strong> при доставке транспортными компаниями (по согласованию)</li>
  </ul>

  <h2>Самовывоз</h2>
  <p>Вы можете забрать товар самостоятельно со склада в <strong>г. Лобня, Московская область</strong>. Уточняйте наличие и точный адрес по телефону или e-mail перед поездкой.</p>

  <h2>Доставка по России</h2>
  <p>Доставляем насосы ЭЦВ во все регионы России. Отправка осуществляется транспортными компаниями по вашему выбору. Срок доставки: <strong>3–10 рабочих дней</strong>.</p>

  <div class="delivery-partners" style="margin: 24px 0;">
    <h3>Наши транспортные партнёры:</h3>
    <div style="display:flex; flex-wrap:wrap; gap:24px; align-items:center; margin-top:16px;">

      <div style="text-align:center;">
        <img src="/image/catalog/delivery/cdek.png" alt="СДЭК — доставка насосов" width="120" height="48" style="object-fit:contain;" onerror="this.style.display='none'">
        <p style="margin-top:8px; font-weight:600;">СДЭК</p>
      </div>

      <div style="text-align:center;">
        <img src="/image/catalog/delivery/pek.png" alt="ПЭК — доставка насосов" width="120" height="48" style="object-fit:contain;" onerror="this.style.display='none'">
        <p style="margin-top:8px; font-weight:600;">ПЭК</p>
      </div>

      <div style="text-align:center;">
        <img src="/image/catalog/delivery/delovye_linii.png" alt="Деловые Линии — доставка насосов" width="120" height="48" style="object-fit:contain;" onerror="this.style.display='none'">
        <p style="margin-top:8px; font-weight:600;">Деловые Линии</p>
      </div>

      <div style="text-align:center;">
        <img src="/image/catalog/delivery/baykal_service.png" alt="Байкал Сервис — доставка насосов" width="120" height="48" style="object-fit:contain;" onerror="this.style.display='none'">
        <p style="margin-top:8px; font-weight:600;">Байкал Сервис</p>
      </div>

    </div>
  </div>

  <h3>Условия доставки:</h3>
  <ul>
    <li>Стоимость доставки рассчитывается по тарифам транспортной компании</li>
    <li>Надёжная упаковка товара — исключает повреждение при транспортировке</li>
    <li>Страхование груза — по запросу клиента</li>
    <li>Уведомление о готовности груза к отправке — по телефону или e-mail</li>
    <li>Доставка «до двери» или на склад транспортной компании в вашем городе</li>
  </ul>

  <h2>Возврат товара</h2>
  <p>Возврат и обмен товара осуществляется в соответствии с <strong>Законом РФ «О защите прав потребителей»</strong> и Публичной офертой. Гарантийные случаи рассматриваются в течение 14 рабочих дней с момента обращения.</p>

  <h2>Вопросы по доставке</h2>
  <p>По всем вопросам, связанным с оплатой и доставкой, обращайтесь:<br>
  📧 <a href="mailto:info@kamtek.pro">info@kamtek.pro</a></p>

</div>
HTML;

$delivery_id = upsertInformationPage(
    $db, $prefix, $lang_id, $delivery_id,
    'Оплата и доставка',
    $delivery_description,
    'Оплата и доставка насосов ЭЦВ по России | НасосыДаром',
    'Доставка погружных насосов ЭЦВ по всей России: СДЭК, ПЭК, Деловые Линии, Байкал Сервис. Самовывоз из г. Лобня.',
    'доставка насосов, оплата насосов, СДЭК, ПЭК, Деловые Линии, Байкал Сервис'
);

// ──────────────────────────────────────────────
// 3. СТРАНИЦА "ПУБЛИЧНАЯ ОФЕРТА"
// ──────────────────────────────────────────────
$oferta_id = getInformationIdBySeoUrl($db, $prefix, ['oferta', 'offer', 'public-offer']);
if (!$oferta_id) {
    $oferta_id = getInformationIdByTitle($db, $prefix, $lang_id, ['Публичная оферта', 'оферта', 'Оферта', 'Offer']);
}
$results[] = "ℹ️ Публичная оферта: information_id=" . ($oferta_id ?: 'не найдено, будет создано');

$oferta_description = <<<'HTML'
<div class="oferta">

  <p><strong>Дата размещения: 01.01.2025</strong></p>

  <p>Настоящий документ является публичной офертой Индивидуального предпринимателя, осуществляющего деятельность под торговой маркой <strong>«НасосыДаром»</strong> (далее — «Продавец»), и содержит все существенные условия договора розничной купли-продажи товаров дистанционным способом.</p>

  <h2>1. Общие положения</h2>
  <ol>
    <li>В соответствии со статьёй 437 Гражданского кодекса РФ настоящий документ является публичной офертой.</li>
    <li>Акцептом настоящей оферты является оформление Покупателем заказа через интернет-магазин <strong>насосыдаром.рф</strong>.</li>
    <li>С момента акцепта настоящей оферты договор купли-продажи считается заключённым.</li>
  </ol>

  <h2>2. Предмет договора</h2>
  <ol>
    <li>Продавец обязуется передать в собственность Покупателя товары (погружные скважинные насосы серии ЭЦВ и сопутствующее оборудование), а Покупатель обязуется принять и оплатить их в соответствии с условиями настоящего договора.</li>
    <li>Наименование, количество, ассортимент и цена товаров определяются оформленным Покупателем заказом.</li>
  </ol>

  <h2>3. Цена и порядок оплаты</h2>
  <ol>
    <li>Цены на товары указаны в рублях РФ и включают НДС (если применимо).</li>
    <li>Оплата производится одним из способов: наличными при самовывозе, безналичным переводом на расчётный счёт, банковской картой онлайн, наложенным платежом.</li>
    <li>Продавец вправе изменять цены на товары в одностороннем порядке. Цена товара фиксируется на момент оформления заказа.</li>
  </ol>

  <h2>4. Доставка товара</h2>
  <ol>
    <li>Доставка осуществляется транспортными компаниями (СДЭК, ПЭК, Деловые Линии, Байкал Сервис и др.) или самовывозом со склада в г. Лобня.</li>
    <li>Срок доставки: 3–10 рабочих дней с момента отгрузки.</li>
    <li>Стоимость доставки рассчитывается по тарифам перевозчика и оплачивается Покупателем.</li>
    <li>Риск случайной гибели товара переходит к Покупателю с момента передачи товара транспортной компании.</li>
  </ol>

  <h2>5. Возврат и обмен товара</h2>
  <ol>
    <li>Возврат и обмен товара надлежащего качества осуществляется в течение 14 дней с момента получения, при условии сохранения товарного вида и потребительских свойств.</li>
    <li>Возврат товара ненадлежащего качества производится в соответствии с Законом РФ «О защите прав потребителей».</li>
    <li>Расходы по возврату товара несёт Покупатель (за исключением случаев ненадлежащего качества товара).</li>
  </ol>

  <h2>6. Гарантийные обязательства</h2>
  <ol>
    <li>На все товары распространяется гарантия завода-изготовителя согласно сертификатам, прилагаемым к товару.</li>
    <li>Гарантийные случаи рассматриваются в течение 14 рабочих дней с момента обращения Покупателя.</li>
  </ol>

  <h2>7. Персональные данные</h2>
  <ol>
    <li>Оформляя заказ, Покупатель даёт согласие на обработку своих персональных данных в целях исполнения договора и информирования об акциях и специальных предложениях.</li>
    <li>Обработка персональных данных осуществляется в соответствии с Федеральным законом № 152-ФЗ «О персональных данных».</li>
  </ol>

  <h2>8. Ответственность сторон</h2>
  <ol>
    <li>Продавец не несёт ответственности за задержки доставки, вызванные действиями транспортных компаний или обстоятельствами непреодолимой силы.</li>
    <li>Споры решаются путём переговоров, при недостижении согласия — в судебном порядке по месту нахождения Продавца.</li>
  </ol>

  <h2>9. Реквизиты Продавца</h2>
  <p>Торговая марка: <strong>НасосыДаром</strong><br>
  Склад и самовывоз: <strong>Московская область, г. Лобня</strong><br>
  E-mail: <a href="mailto:info@kamtek.pro">info@kamtek.pro</a><br>
  Сайт: <a href="https://насосыдаром.рф">насосыдаром.рф</a></p>

</div>
HTML;

$oferta_id = upsertInformationPage(
    $db, $prefix, $lang_id, $oferta_id,
    'Публичная оферта',
    $oferta_description,
    'Публичная оферта | НасосыДаром',
    'Публичная оферта интернет-магазина насосыдаром.рф — условия купли-продажи погружных насосов ЭЦВ.',
    'публичная оферта, оферта, насосыдаром, условия покупки'
);

// ──────────────────────────────────────────────
// 4. Добавляем SEO URL для /oferta если нет
// ──────────────────────────────────────────────
if ($oferta_id) {
    $check = $db->query("SELECT url_alias_id FROM `{$prefix}seo_url`
                         WHERE keyword = 'oferta' AND store_id = 0 AND language_id = {$lang_id} LIMIT 1");
    if (!$check || $check->num_rows == 0) {
        $q = esc($db, "route=information/information&information_id={$oferta_id}");
        $db->query("INSERT INTO `{$prefix}seo_url` (store_id, language_id, query, keyword)
                    VALUES (0, {$lang_id}, '{$q}', 'oferta')");
        $results[] = "✅ Добавлен SEO URL /oferta для information_id={$oferta_id}";
    } else {
        $results[] = "ℹ️ SEO URL /oferta уже существует";
    }
}

// ──────────────────────────────────────────────
// 5. Устанавливаем страницу оферты как "Согласие" в checkout
// ──────────────────────────────────────────────
if ($oferta_id) {
    // Устанавливаем config_checkout_id = $oferta_id (страница для чекбокса)
    $store_id = 0;
    $check = $db->query("SELECT setting_id FROM `{$prefix}setting`
                         WHERE `store_id` = {$store_id} AND `code` = 'config' AND `key` = 'config_checkout_id' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $db->query("UPDATE `{$prefix}setting` SET `value` = {$oferta_id}
                    WHERE `store_id` = {$store_id} AND `code` = 'config' AND `key` = 'config_checkout_id'");
        $results[] = "✅ Обновлён config_checkout_id = {$oferta_id} (страница Публичной оферты)";
    } else {
        $db->query("INSERT INTO `{$prefix}setting` (store_id, code, `key`, value, serialized)
                    VALUES ({$store_id}, 'config', 'config_checkout_id', {$oferta_id}, 0)");
        $results[] = "✅ Создан config_checkout_id = {$oferta_id} (страница Публичной оферты)";
    }

    // Включаем отображение чекбокса согласия (config_checkout_agree = 1)
    $check2 = $db->query("SELECT setting_id FROM `{$prefix}setting`
                          WHERE `store_id` = {$store_id} AND `code` = 'config' AND `key` = 'config_checkout_agree' LIMIT 1");
    if ($check2 && $check2->num_rows > 0) {
        $db->query("UPDATE `{$prefix}setting` SET `value` = '1'
                    WHERE `store_id` = {$store_id} AND `code` = 'config' AND `key` = 'config_checkout_agree'");
        $results[] = "✅ Обновлён config_checkout_agree = 1 (чекбокс активен)";
    } else {
        $db->query("INSERT INTO `{$prefix}setting` (store_id, code, `key`, value, serialized)
                    VALUES ({$store_id}, 'config', 'config_checkout_agree', '1', 0)");
        $results[] = "✅ Создан config_checkout_agree = 1 (чекбокс активен)";
    }
}

// ──────────────────────────────────────────────
// 6. Создаём директорию для логотипов доставки
// ──────────────────────────────────────────────
$delivery_img_dir = DIR_IMAGE . 'catalog/delivery/';
if (!is_dir($delivery_img_dir)) {
    if (@mkdir($delivery_img_dir, 0755, true)) {
        $results[] = "✅ Создана директория image/catalog/delivery/";
    } else {
        $results[] = "ℹ️ Директория image/catalog/delivery/ уже существует или не удалось создать (создайте вручную)";
    }
} else {
    $results[] = "ℹ️ Директория image/catalog/delivery/ уже существует";
}

// ──────────────────────────────────────────────
// 7. Включаем email-уведомления для отзывов
// ──────────────────────────────────────────────
// config_mail_alert - массив типов уведомлений (сериализован)
$check_alert = $db->query("SELECT setting_id, value, serialized FROM `{$prefix}setting`
                            WHERE store_id = 0 AND code = 'config' AND `key` = 'config_mail_alert' LIMIT 1");
if ($check_alert && $check_alert->num_rows > 0) {
    $row = $check_alert->fetch_assoc();
    if ($row['serialized']) {
        $alert_arr = @unserialize($row['value']);
        if (!is_array($alert_arr)) $alert_arr = [];
    } else {
        $alert_arr = $row['value'] ? explode(',', $row['value']) : [];
    }
    if (!in_array('review', $alert_arr)) {
        $alert_arr[] = 'review';
        $new_val = esc($db, serialize($alert_arr));
        $db->query("UPDATE `{$prefix}setting` SET value = '{$new_val}', serialized = 1
                    WHERE store_id = 0 AND code = 'config' AND `key` = 'config_mail_alert'");
        $results[] = "✅ Включено email-уведомление при новом отзыве (config_mail_alert += 'review')";
    } else {
        $results[] = "ℹ️ Email-уведомление для отзывов уже включено";
    }
} else {
    $new_val = esc($db, serialize(['review']));
    $db->query("INSERT INTO `{$prefix}setting` (store_id, code, `key`, value, serialized)
                VALUES (0, 'config', 'config_mail_alert', '{$new_val}', 1)");
    $results[] = "✅ Создан config_mail_alert = ['review']";
}

$db->close();

// ──────────────────────────────────────────────
// Вывод результатов
// ──────────────────────────────────────────────
echo '<pre>';
echo "=== Результаты обновления страниц сайта ===\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n⚠️ УДАЛИТЕ ЭТОТ ФАЙЛ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!\n";
echo "\n📋 Следующие шаги:\n";
echo "  1. Загрузите реальные PNG-логотипы в image/catalog/delivery/\n";
echo "     (cdek.png, pek.png, delovye_linii.png, baykal_service.png)\n";
echo "  2. Добавьте SEO URL для страниц О компании и Оплата и доставка\n";
echo "     в панели администратора (Дизайн → SEO URL), если ещё не заданы\n";
echo "  3. Чекбокс согласия с офертой уже активирован через config_checkout_agree\n";
echo "  4. Удалите этот файл: rm " . __FILE__ . "\n";
echo '</pre>';

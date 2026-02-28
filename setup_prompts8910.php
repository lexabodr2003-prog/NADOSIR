<?php
/**
 * setup_prompts8910.php — Промты 8, 9, 10
 * Выполняет:
 *  - П.10/18: Исправление config_meta_title, config_meta_description
 *  - П.8/13: Проверка и настройка отзывов + email-уведомлений
 *  - П.9/19: Создание подкатегорий серий ЭЦВ 4,5,6,8,10 + привязка товаров
 *  - П.9/19: Настройка модуля oct_product_modules для показа по 2 товара из серии
 *  - П.9/16: Создание таблицы подписки + включение модуля
 *
 * Запуск: https://насосыдаром.рф/setup_prompts8910.php?key=setup2026
 * УДАЛИТЬ после выполнения!
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'setup2026') {
    http_response_code(403); die('Access denied. Use ?key=setup2026');
}
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/config.php');
$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
if ($db->connect_error) die('DB error: '.$db->connect_error);
$db->set_charset('utf8');

$ok = 0; $err = 0;
function ok($msg) { global $ok; $ok++; echo "✅ $msg\n"; }
function er($msg) { global $err; $err++; echo "❌ $msg\n"; }
function upd($db,$key,$val,$store=0) {
    $r=$db->query("SELECT setting_id FROM oc_setting WHERE `key`='$key' AND store_id=$store LIMIT 1");
    $row=$r->fetch_assoc();
    $val_esc=$db->real_escape_string($val);
    if($row) { $db->query("UPDATE oc_setting SET `value`='$val_esc' WHERE `key`='$key' AND store_id=$store"); }
    else { $db->query("INSERT INTO oc_setting (store_id,code,`key`,`value`,serialized) VALUES ($store,'config','$key','$val_esc',0)"); }
    return $db->affected_rows > 0;
}

echo "=== П.10/18: Исправление мета-тегов сайта ===\n";
$r = upd($db,'config_meta_title','Скважинные насосы ЭЦВ — купить насос для скважины | КАМТЕК');
ok("config_meta_title → 'Скважинные насосы ЭЦВ — купить насос для скважины | КАМТЕК'");
$r = upd($db,'config_meta_description','КАМТЕК — производитель и поставщик погружных скважинных насосов ЭЦВ серий 4, 5, 6, 8, 10. Надёжные насосы для скважин от производителя. Доставка по России.');
ok("config_meta_description обновлён");
// og:title и og:description хранятся в category/product descriptions, для главной — в config
$r = upd($db,'config_store_description','Производитель и поставщик скважинных насосов ЭЦВ. Серии 4, 5, 6, 8, 10. Доставка по всей России.');
ok("config_store_description обновлён");

echo "\n=== П.8/13: Настройка отзывов и email-уведомлений ===\n";
// Проверяем config_review_status
$r=$db->query("SELECT value FROM oc_setting WHERE `key`='config_review_status' AND store_id=0 LIMIT 1");
$row=$r->fetch_assoc(); $rv=$row ? $row['value'] : '0';
if($rv=='1') ok("Отзывы включены (review_status=1)");
else { upd($db,'config_review_status','1'); ok("Отзывы включены"); }

$r2=$db->query("SELECT value FROM oc_setting WHERE `key`='config_review_guest' AND store_id=0 LIMIT 1");
$row2=$r2->fetch_assoc(); $rv2=$row2 ? $row2['value'] : '0';
if($rv2=='1') ok("Гостевые отзывы включены");
else { upd($db,'config_review_guest','1'); ok("Гостевые отзывы включены"); }

// Email уведомление при новом отзыве
$r3=$db->query("SELECT value FROM oc_setting WHERE `key`='config_mail_alert' AND store_id=0 LIMIT 1");
$row3=$r3->fetch_assoc();
if($row3) {
    $alerts=unserialize($row3['value']);
    if(!is_array($alerts)) $alerts=array();
    if(!in_array('review',$alerts)) {
        $alerts[]='review';
        $new_val=serialize($alerts);
        $db->query("UPDATE oc_setting SET `value`='".addslashes($new_val)."' WHERE `key`='config_mail_alert' AND store_id=0");
        ok("Email-уведомление об отзывах активировано");
    } else ok("Email-уведомление об отзывах уже активно: ".implode(',',$alerts));
} else {
    $new_val=serialize(array('review'));
    $db->query("INSERT INTO oc_setting (store_id,code,`key`,`value`,serialized) VALUES (0,'config','config_mail_alert','".addslashes($new_val)."',1)");
    ok("config_mail_alert создан с review");
}
// Проверяем email магазина
$r4=$db->query("SELECT value FROM oc_setting WHERE `key`='config_email' AND store_id=0 LIMIT 1");
$row4=$r4->fetch_assoc();
ok("Email для уведомлений: ".($row4 ? $row4['value'] : 'N/A')." (настроен в config_email)");

echo "\n=== П.9/19: Создание подкатегорий серий ЭЦВ ===\n";
$series_info = array(
    '4'  => array('name'=>'Серия 4 (ЭЦВ 4)','seo'=>'ecv-seria-4','sort'=>1),
    '5'  => array('name'=>'Серия 5 (ЭЦВ 5)','seo'=>'ecv-seria-5','sort'=>2),
    '6'  => array('name'=>'Серия 6 (ЭЦВ 6)','seo'=>'ecv-seria-6','sort'=>3),
    '8'  => array('name'=>'Серия 8 (ЭЦВ 8)','seo'=>'ecv-seria-8','sort'=>4),
    '10' => array('name'=>'Серия 10 (ЭЦВ 10)','seo'=>'ecv-seria-10','sort'=>5),
);

// Определяем какие товары к каким сериям относятся
$series_product_ids = array('4'=>array(),'5'=>array(),'6'=>array(),'8'=>array(),'10'=>array());
$rp=$db->query("SELECT product_id, model FROM oc_product ORDER BY product_id");
while($row=$rp->fetch_assoc()) {
    if(preg_match('/^ЭЦВ\s+(10|4|5|6|8)-/', $row['model'], $m)) {
        $series_product_ids[$m[1]][]=$row['product_id'];
    }
}

$cat_ids = array();
foreach($series_info as $snum => $sinfo) {
    // Проверяем, существует ли уже подкатегория
    $sname_esc=$db->real_escape_string($sinfo['name']);
    $r5=$db->query("SELECT c.category_id FROM oc_category c JOIN oc_category_description cd ON c.category_id=cd.category_id WHERE cd.name='$sname_esc' AND cd.language_id=1 AND c.parent_id=1 LIMIT 1");
    $row5=$r5->fetch_assoc();
    if($row5) {
        $cat_id=$row5['category_id'];
        ok("Подкатегория 'Серия $snum' уже существует (id=$cat_id)");
    } else {
        // Создаём подкатегорию
        $db->query("INSERT INTO oc_category (parent_id,image,top,column,sort_order,status,date_added,date_modified) VALUES (1,'',0,1,".$sinfo['sort'].",1,NOW(),NOW())");
        $cat_id=$db->insert_id;
        // Описание
        $db->query("INSERT INTO oc_category_description (category_id,language_id,name,description,meta_title,meta_description,meta_keyword) VALUES ($cat_id,1,'".addslashes($sinfo['name'])."','','".addslashes($sinfo['name'])." — погружные скважинные насосы КАМТЕК','Скважинные насосы ЭЦВ серии $snum от производителя КАМТЕК','насос ЭЦВ серия $snum')");
        // Путь категории (oc_category_path)
        $db->query("INSERT INTO oc_category_path (category_id,path_id,level) VALUES ($cat_id,1,0)");
        $db->query("INSERT INTO oc_category_path (category_id,path_id,level) VALUES ($cat_id,$cat_id,1)");
        // SEO URL
        $seo_esc=$db->real_escape_string($sinfo['seo']);
        $db->query("INSERT INTO oc_seo_url (store_id,language_id,query,keyword) VALUES (0,1,'category_id=$cat_id','$seo_esc')");
        ok("Создана подкатегория 'Серия $snum' (id=$cat_id, seo=/$seo_esc)");
    }
    $cat_ids[$snum]=$cat_id;
    
    // Привязываем товары к подкатегории
    $products=$series_product_ids[$snum];
    $added=0;
    foreach($products as $pid) {
        // Проверяем привязку
        $rc=$db->query("SELECT * FROM oc_product_to_category WHERE product_id=$pid AND category_id=$cat_id LIMIT 1");
        if(!$rc->fetch_assoc()) {
            $db->query("INSERT INTO oc_product_to_category (product_id,category_id,main_category) VALUES ($pid,$cat_id,0)");
            $added++;
        }
    }
    ok("Серия $snum: ".count($products)." товаров привязано к подкатегории (новых: $added)");
}

echo "\n=== П.9/19: Настройка модуля oct_product_modules (показ по сериям) ===\n";
// Текущий модуль 32 показывает категорию 1 целиком, limit=10/6/6
// Изменяем на показ подкатегорий серий, по 2 из каждой
// Это реализуем через 5 отдельных oct_product_modules или через настройку featured
// Самое простое: обновить модуль featured (id=28) показывая по 2 самых дешёвых из каждой серии
$featured_products = array();
foreach(array('4','5','6','8','10') as $s) {
    $ids=implode(',',$series_product_ids[$s]);
    if(!$ids) continue;
    $r6=$db->query("SELECT product_id FROM oc_product WHERE product_id IN ($ids) ORDER BY price ASC LIMIT 2");
    while($row6=$r6->fetch_assoc()) $featured_products[]=$row6['product_id'];
}
$featured_json=json_encode(array('name'=>'Товары по сериям','product'=>$featured_products,'limit'=>10,'width'=>200,'height'=>200,'status'=>1),JSON_UNESCAPED_UNICODE);
$featured_json_esc=$db->real_escape_string($featured_json);
$db->query("UPDATE oc_module SET setting='$featured_json_esc' WHERE module_id=28");
ok("Модуль featured (id=28) обновлён: ".implode(',',$featured_products)." (по 2 из каждой серии)");

echo "\n=== П.9/16: Кнопка 'Подписаться' ===\n";
// Проверяем таблицу oc_oct_subscribe
$r7=$db->query("SHOW TABLES LIKE 'oc_oct_subscribe'");
if(!$r7->fetch_assoc()) {
    // Создаём таблицу
    $db->query("CREATE TABLE IF NOT EXISTS oc_oct_subscribe (
        subscribe_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(96) NOT NULL,
        store_id INT(11) NOT NULL DEFAULT 0,
        language_id INT(11) NOT NULL DEFAULT 1,
        status TINYINT(1) NOT NULL DEFAULT 1,
        ip VARCHAR(40) NOT NULL DEFAULT '',
        date_added DATETIME NOT NULL,
        UNIQUE KEY email_store (email, store_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
    ok("Таблица oc_oct_subscribe создана");
} else ok("Таблица oc_oct_subscribe уже существует");

// Включаем модуль oct_subscribe в настройках
$r8=$db->query("SELECT setting_id FROM oc_extension WHERE type='module' AND code='oct_subscribe' LIMIT 1");
if(!$r8->fetch_assoc()) {
    $db->query("INSERT INTO oc_extension (type,code) VALUES ('module','oct_subscribe')");
    ok("oct_subscribe добавлен в oc_extension");
} else ok("oct_subscribe уже в oc_extension");

// Настройка модуля подписки
$r9=$db->query("SELECT module_id FROM oc_module WHERE code='oct_subscribe' LIMIT 1");
$row9=$r9->fetch_assoc();
$sub_setting=json_encode(array('name'=>'Подписка','status'=>1,'notification_email'=>'info@kamtek.pro'),JSON_UNESCAPED_UNICODE);
$sub_esc=$db->real_escape_string($sub_setting);
if($row9) {
    $db->query("UPDATE oc_module SET setting='$sub_esc' WHERE code='oct_subscribe' LIMIT 1");
    ok("oct_subscribe модуль обновлён (status=1, email=info@kamtek.pro)");
} else {
    $db->query("INSERT INTO oc_module (name,code,setting) VALUES ('Подписка','oct_subscribe','$sub_esc')");
    ok("oct_subscribe модуль создан");
}

echo "\n=== ИТОГО ===\n";
echo "OK: $ok, ERR: $err\n";
echo "\nУДАЛИТЕ ЭТОТ ФАЙЛ ПОСЛЕ ИСПОЛЬЗОВАНИЯ!\n";

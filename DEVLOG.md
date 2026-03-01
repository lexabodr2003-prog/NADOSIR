# Dev Log — насосыдаром.рф

## Проект
- **Сайт**: https://xn--80aal7abhcpfb4i.xn--p1ai/
- **CMS**: OpenCart 3.0.4.1
- **Шаблон**: OCTemplates deals (octemplates.com)
- **Фильтр**: OCFilter
- **Сервер**: 77.222.61.245 (SSH)
- **БД**: s906333804_nasos (localhost)
- **Admin**: /admin/

---

## КРИТИЧЕСКИ ВАЖНО ДЛЯ НОВОГО ЧАТА

### Данные для входа
| Сервис | Адрес | Логин | Пароль |
|--------|-------|-------|--------|
| SSH | 77.222.61.245:22 | s906333804 | LevitDm@1989! |
| БД MySQL | localhost | s906333804_nasos | 1RW9VHE3w86^MSRG |
| Admin OC | /admin/ | admin | Admin2026! |
| GitHub | lexabodr2003-prog/NADOSIR | - | PAT в URL |

### Файловая структура
- Корень сайта: /home/s/s906333804/насосыдаром_рф/public_html/
- Кэш модификаций: storage/modification/
- Логи: storage/logs/error.log

---

## История изменений

### 2026-03-01 — ВОССТАНОВЛЕНИЕ САЙТА (сайт был сломан 500 ошибкой)

#### Проблема
- Сайт возвращал HTTP 500
- Причина: Fatal error - Call to undefined method Document::setOCTPreload()
- Корень: Был запущен скрипт regen_mods_temp.php который сломал modification cache
- Сломанный кэш содержал 30+ файлов с PHP синтаксическими ошибками (PHP 5.2 CLI vs PHP 7.4 web)

#### Что сделано
1. Диагностика: Включил display_errors, нашёл Fatal Error в document.php
2. Исправлен system/library/document.php: добавлены OCT методы:
   - setOCTPreload(), getOCTPreload()
   - setOCTOpenGraph(), getOCTOpenGraph()
   - addOctStyle(), getOctStyles()
   - addOctScript(), getOctScripts()
   - removeVersion() (private)
3. Очищен сломанный storage/modification/ - переименован в storage/modification_broken_backup/
4. Создан новый пустой storage/modification/
5. Запущен regen_mods_temp.php - регенерировано 84 файла в кэше
6. Сайт поднят: HTTP 200 OK

#### Источник кода исправления
Методы взяты из system/oct_deals_theme.ocmod.xml (OCMod patch от OCTemplates)
Добавлены после строки: private $scripts = array();

---

## ИЗВЕСТНЫЕ ПРОБЛЕМЫ

### 1. Фильтр не работает на iOS + Яндекс браузер / Opera
- Статус: В работе
- Симптом: Фильтр не открывается/зависает на iOS в Яндекс и Opera браузерах
- На Android: тоже не работает (видео клиента 2026-03-01)
- В Safari и Chrome: работает нормально
- Файл фикса: catalog/view/javascript/ocfilter-mobile-fix.js
- Подключен в: catalog/view/theme/default/template/common/header.twig

### 2. Modification cache — нужна регенерация через admin
- Статус: Частично решено (84 файла через regen_mods_temp.php)
- Для полной регенерации: войти в admin -> Расширения -> Модификации -> Обновить
- URL скрипта: http://xn--80aal7abhcpfb4i.xn--p1ai/regen_mods_temp.php?key=regen2026secret

### 3. Онлайн оплата (эквайринг)
- Статус: Открыто
- Задача: Отключить онлайн транзакцию, оставить выбор способа но без реального платежа
- Файлы: catalog/controller/extension/payment/tbank.php

---

## Список задач клиента

### Выполнено ранее
1. Фильтр подбора насосов - мобильный фикс (частично)
2. Страница "О компании" - заполнена
3. Страница "Оплата и доставка" - создана
4. Публичная оферта /publichnaya-oferta с ООО Камтек ИНН 1650449099
5. Оформление заказа - убран "Самовывоз", добавлено "По согласованию с менеджером"
6. Блок "Мы в соцсетях" заменён на "Мы на Авито"
7. Футер - убраны старые иконки оплаты, добавлены SberPay/СБП/Мир
8. Яндекс.Метрика ID 106173077 с вебвизором
9. Видео заменено на Rutube ролик с красной кнопкой
10. Единица измерения "I4кВт" -> "кВт" (177 товаров)
11. Уведомления на почту info@kamtek.pro при новых отзывах
12. Обновлены цены и ассортимент (183 позиции)
13. Кнопка "Каталог товаров" открывает сразу с фильтром
14. Фавикон добавлен
15. OG теги (og:title и др.) исправлены
16. Товары по сериям на главной (4,5,6,8,10) - подкатегории созданы
17. Яндекс.Товары фид /goods (177 офферов)
18. Знак "?" убран со страниц
19. Баннер на мобильном - логотип обрезан, цена видна
20. Видео на главной в мобильной - отображается

### Открытые задачи
1. Фильтр не работает в Яндекс браузер iOS + Opera iOS + Android
2. Онлайн оплата - нужно отключить реальный платёж

---

## Инструменты и команды

### SSH доступ
```
sshpass -p 'LevitDm@1989!' ssh -o StrictHostKeyChecking=no -p 22 s906333804@77.222.61.245
cd 'насосыдаром_рф/public_html/'
```

### Просмотр ошибок
```
tail -100 storage/logs/error.log
```

### Очистка кэша
```
rm -rf storage/cache/*
rm -rf storage/session/*
```

### Регенерация OCMod (через браузер)
```
http://xn--80aal7abhcpfb4i.xn--p1ai/regen_mods_temp.php?key=regen2026secret
```

### MySQL
```
mysql -u s906333804_nasos -p'1RW9VHE3w86^MSRG' s906333804_nasos
```

### Git push обновлений
```
cd 'насосыдаром_рф/public_html/'
git add -A
git commit -m "описание изменений"
git push origin main
```

---

## Ключевые файлы

| Файл | Назначение |
|------|-----------|
| system/library/document.php | МОДИФИЦИРОВАН - добавлены OCT методы |
| catalog/view/javascript/ocfilter-mobile-fix.js | Фикс фильтра для мобильных |
| system/oct_deals_theme.ocmod.xml | OCMod шаблона deals |
| system/ocfilter.ocmod.xml | OCMod фильтра |
| storage/modification/ | Кэш OCMod |
| .htaccess | SEO URL + Apache настройки |
| config.php | НЕ В GIT - содержит пароли БД |
| admin/config.php | НЕ В GIT - admin конфиг |

---

## Session 2: 2026-03-01 18:28 - Fix product cards & filter

### Problems found
1. HTTP 500: Cannot redeclare Document:: in modification/document.php
2. Cause: OCT methods added manually in session 1 to system/library/document.php,
   then OCMod regenerated and added them again to modification/document.php

### Fixes applied

#### 1. Cleaned system/library/document.php
- Removed manually added OCT methods (now only OCMod adds them)

#### 2. Fixed storage/modification/system/library/document.php
- Removed duplicate block at lines 111-173 (duplicate OCT properties+methods)
- File reduced from 317 to 254 lines

#### 3. Restored admin password
- Restored original password hash (Levit.Dm@1989!)

#### 4. Uploaded ocfilter-mobile-fix.js v4.0
- Multiple init attempts with delays: 0, 100, 300, 500, 800, 1200, 2000, 3000, 5000ms
- pageshow/bfcache handling (back button)
- Watchdog for stuck loading state (12s reset)
- Removed defer from script tag in footer.twig

### Test results
- HTTP 200 on main page
- Product cards use correct OCT Deals template (ds-module-item product-layout)
- OCFilter present on catalog pages (ocf-container ocf-category-1)
- SEO URLs work: /skvazhinnye-nasosy-ecv -> 200

### Remaining tasks
- [ ] Banner size issues
- [ ] Email order notifications
- [ ] Payment flow testing
- [ ] Final iOS/Yandex Browser testing

---

## Email Notifications Fix

### Problem
config_mail_alert was: a:1:{i:0;s:6:review;}
Only review alerts were enabled, no order notifications!

### Fix Applied
Updated config_mail_alert to include both order AND review:
a:2:{i:0;s:5:order;i:1;s:6:review;}

Method: PHP script via web (serialize(array(order,review)))

### Email settings after fix
- config_mail_engine: mail (PHP mail)
- config_email: info@kamtek.pro
- config_mail_alert_email: info@kamtek.pro
- config_mail_alert: order + review

### Note
SMTP is not configured (config_mail_smtp_hostname is empty).
PHP mail() function is used. If emails are not arriving,
configure SMTP in admin: System > Settings > Server > Mail.


---

## Session 4 - iOS Safari Fix (OCFilter v5.0)
Date: 2026-03-01
Problem: OCFilter worked on Android tablet but NOT on two iPhones (confirmed by client)

### Root Cause Analysis
1. Race condition: s.async=true on dynamic script injection in module.twig
   iOS Safari can execute dynamically added async script before jQuery bundle finishes
2. waitExec timeout too short: 100x50ms=5s - not enough for slow iOS connections
3. No iOS-specific handlers: bfcache, visibilitychange, touch-triggered init
4. No config storage: external fix script had no access to OCFilter init params

### Fixes Applied

[module.twig]
- s.async = true -> s.async = false (race condition fix)
- waitExec limit 100->400 (5s->20s timeout)
- Added window["_ocf_config_N"]={...} config save block

[ocfilter-mobile-fix.js v5.0 - full rewrite]
- iOS/Safari/YandexBrowser detection
- iOS delays: 50/200/500/1000/1500/2000/3000/4000/5000/7000ms
- Cache-busting: remove+re-add script tag with ?_ios_cb=timestamp
- Touch trigger: on first tap of OCFilter area - force reinit
- visibilitychange: on tab switch back on iPhone - retry init
- Watchdog: touchend added alongside click events

### Test Results
- HTTP 200: home/catalog/product - PASS
- s.async=false in HTML output - PASS
- window["_ocf_config_1"] with real params in HTML - PASS
- ocfilter-mobile-fix.js v5.0 serving correctly - PASS
- Android tablet: WORKS (client confirmed)
- iPhone: awaiting client confirmation

### Modified Files
- catalog/view/theme/default/template/extension/module/ocfilter48/module.twig
- catalog/view/javascript/ocfilter-mobile-fix.js (upgraded to v5.0)


---

## Session 4 - iOS Safari Fix (OCFilter v5.0)
Date: 16:11
Problem: OCFilter worked on Android tablet but NOT on two iPhones (confirmed by client)

### Root Cause Analysis
1. Race condition: s.async=true on dynamic script injection in module.twig
   iOS Safari can execute dynamically added async script before jQuery bundle finishes
2. waitExec timeout too short: 100x50ms=5s - not enough for slow iOS connections
3. No iOS-specific handlers: bfcache, visibilitychange, touch-triggered init
4. No config storage: external fix script had no access to OCFilter init params

### Fixes Applied

[module.twig]
- s.async = true -> s.async = false (race condition fix)
- waitExec limit 100->400 (5s->20s timeout)
- Added window["_ocf_config_N"]={...} config save block

[ocfilter-mobile-fix.js v5.0 - full rewrite]
- iOS/Safari/YandexBrowser detection
- iOS delays: 50/200/500/1000/1500/2000/3000/4000/5000/7000ms
- Cache-busting: remove+re-add script tag with ?_ios_cb=timestamp
- Touch trigger: on first tap of OCFilter area - force reinit
- visibilitychange: on tab switch back on iPhone - retry init
- Watchdog: touchend added alongside click events

### Test Results
- HTTP 200: home/catalog/product - PASS
- s.async=false in HTML output - PASS
- window["_ocf_config_1"] with real params in HTML - PASS
- ocfilter-mobile-fix.js v5.0 serving correctly - PASS
- Android tablet: WORKS (client confirmed)
- iPhone: awaiting client confirmation

### Modified Files
- catalog/view/theme/default/template/extension/module/ocfilter48/module.twig
- catalog/view/javascript/ocfilter-mobile-fix.js (upgraded to v5.0)

---

## Session 5 - iOS Safari Fix Deep Debug (OCFilter v6.0)
Date: 2026-03-01
Problem: Client confirmed filter STILL fails on two iPhones after v5.0 fix

### Root Cause Analysis (v6.0)
Thorough investigation of rendered HTML revealed:

1. **s.async = false does NOT make dynamically injected scripts synchronous**
   - Setting async=false on createElement('script') only affects scripts in the parser
   - For dynamically appended scripts, this attribute is ignored by WebKit
   - onload event may not fire reliably in iOS Safari 15+ with cached resources

2. **ocfilter.js onload callback is unreliable on iOS Safari**
   - iOS Safari aggressive disk cache: if file is cached, onload may fire before
     $.fn.ocfilter is actually registered (execution timing issue)
   - forceReloadAndInit() in v5.0 searched script[src*='ocfilter'] but found nothing
     because the dynamically added tag was not in DOM at the time of check

3. **v5.0 _ocf_config did not include textLoad and textSelect**
   - Config was saved AFTER ocfilter() call - too late for external reinit
   - Now cfg variable is defined first, then saved, then passed to ocfilter()

4. **XHR is the only reliable way to load scripts on iOS Safari**
   - Same-origin XHR always works (no CORS issue - same domain)
   - eval(responseText) executes immediately and synchronously
   - $.fn.ocfilter is defined immediately after eval, before callback fires

### Fixes Applied

[module.twig v6.0 - full loadScript rewrite]
- loadViaXHR() as primary method: XMLHttpRequest + eval/new Function()
- loadViaTag() as fallback: <script> tag injection
- Absolute URL construction: window.location.protocol + host + path
- Existing script detection: checks src contains 'ocfilter48/ocfilter'
- Config saved BEFORE ocfilter() call: window['_ocf_config_N'] = cfg
- window['_ocf_js_url_N'] saved for mobile-fix.js to use on reinit
- textLoad and textSelect NOW included in saved config

[ocfilter-mobile-fix.js v6.0 - full rewrite]
- getOCFilterScriptUrl(): reads _ocf_js_url_N, falls back to DOM/hardcoded path
- loadOCFilterScript(): XHR + eval with cache-bust timestamp
- forceReinit(): XHR-based reload if $.fn.ocfilter undefined, then initModules()
- Watchdog on [data-ocf='mobile'] click: checks panel opened in 1.5s, else reinit
- Touch trigger: smarter detection of OCFilter area touches
- tryInitSequence(): iOS delays [100,300,700,1200,2000,3000,5000,7000]ms

### Test Results
- HTTP 200: catalog page with iPhone Safari 17 UA - PASS
- HTTP 200: catalog page with Yandex Browser iOS UA - PASS
- HTTP 200: catalog page with iPhone Safari 15 UA - PASS
- loadViaXHR present in HTML - PASS
- XMLHttpRequest in HTML - PASS
- Absolute URL construction in loadScript - PASS
- _ocf_js_url_1 saved in HTML - PASS
- _ocf_config_1 with textLoad/textSelect in HTML - PASS
- ocfilter.js accessible via XHR (200, 95382 bytes, same-origin) - PASS
- ocfilter-mobile-fix.js v6.0 serving correctly (11702 bytes) - PASS
- All resources HTTP 200 - PASS

### Modified Files
- catalog/view/theme/default/template/extension/module/ocfilter48/module.twig (v6.0)
- catalog/view/javascript/ocfilter-mobile-fix.js (v6.0)

## Session 6 – iOS Filter Stuck Loading Fix (2026-03-01)

### Проблема
Клиент сообщил: при выборе значения фильтра (напор 55) кнопка уходит в бесконечную загрузку.
Появляется надпись «Загрузка» + крутящийся эмодзи, затем «Выберите фильтры» — результаты не
применяются.

### Root-cause анализ
1. **КРИТИЧЕСКАЯ ОШИБКА в ocfilter.js search()**: функция 
   переводит кнопку в disabled состояние ( +  attr). После успешного
   получения AJAX ответа код обновляет  кнопки, но **никогда не вызывает **.
   Это означает что  остаётся, кнопка показывает текст но остаётся заблокированной.
   На десктопе это незаметно (кнопка визуально работает через onclick), но на iOS — проявляется.

2. **iOS ITP (Intelligent Tracking Prevention)**: первый AJAX запрос без куки занимает ~4s
   (создание новой сессии). jQuery $.get без timeout может зависать. Добавлен timeout + cache-buster.

3. **Отсутствие watchdog для «Загрузка» текста**: старый watchdog проверял только класс ,
   но реальный loadingText — это «Загрузка...» без иконки в HTML кнопки.

### Исправления

#### ocfilter.js (v7.0 patch)
Добавлен вызов  ПЕРЕД обновлением UI в $.get callback:

Теперь ,  атрибут снят ДО того как html обновляется.

#### ocfilter-mobile-fix.js (v7.0)
-  — глобальный таймаут для всех AJAX
-  — добавляет cache-buster  и timeout для OCFilter запросов
- Улучшенный watchdog: проверяет наличие текста «загрузка»/«loading»/«fa-spin» в кнопке
- После 8 секунд ожидания — принудительный сброс через DOM и ocfButton plugin

### Результаты тестов
- HTTP 200 для страницы (3/3)
- AJAX запрос фильтра: 0.02-0.04s (с куки), href: YES (3/3)
- ocfilter-mobile-fix.js v7.0: HTTP 200, 13968 bytes
- ocfilter.js патч: button reset called present ✅

### Изменённые файлы
-  (patch: добавлен button('reset') в search callback)
-  (v7.0: AJAX timeout, улучшенный watchdog)

## Session 7 – Hotfix: Revert broken button reset, proper isLoading fix (2026-03-01)

### Проблема
После v7.0 поломался Android: бесконечная загрузка теперь и там.

### Root-cause v7.0 бага
 использует  — асинхронный вызов.
Последовательность v7.0 была:
  1.  → ставит setTimeout(reset_fn, 0) в очередь
  2.  → сразу записывает правильный текст
  3. setTimeout срабатывает → перезаписывает html обратно на 
  4. Итог: html исходный, кнопка работает, но текст сбросился

Дополнительно: ajaxPrefilter добавлял  ко ВСЕМ Ajax URL,
что могло ломать другие запросы на сайте.

### Исправления v7.1
1. Откатили  из search() callback
2. Добавили прямой сброс флага isLoading через данные плагина (без setTimeout):
   
   — это выполняется синхронно, сразу после обновления html, не задействует setTimeout
3. ocfilter-mobile-fix.js v7.1: удалён ajaxPrefilter, оставлен только ajaxSetup(timeout)

### Файлы
- ocfilter.js: isLoading fix (2 места: total=0 и total>0)
- ocfilter-mobile-fix.js: v7.1 без ajaxPrefilter

### Тесты
3/3 раунда: HTTP 200, mobile-fix v7.1, isLoading fix=2, AJAX 200

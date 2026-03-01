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

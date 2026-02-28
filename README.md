# насосыдаром.рф — Анализ структуры сайта

## Общие сведения

| Параметр | Значение |
|---|---|
| **CMS** | OpenCart 3.0.4.1 |
| **Тема** | OcTemplates Deals v1.1.0 (Octemplates.net) |
| **Директория** | `/home/user/webapp/` |
| **Всего файлов** | ~5 300 (без vendor) |
| **Размер** | ~186 MB (с изображениями) |
| **Git репозиторий** | https://github.com/lexabodr2003-prog/NADOSIR.git |
| **Ветка** | `main` |

---

## База данных

Параметры подключения (из `config.php` и `admin/config.php`):

```
DB_DRIVER:   mysqli
DB_HOSTNAME: localhost
DB_USERNAME: s906333804_nasos
DB_PASSWORD: 1RW9VHE3w86^MSRG
DB_DATABASE: s906333804_nasos
DB_PORT:     3306
DB_PREFIX:   oc_
```

> **Примечание (28.02.2026):** Пароль MySQL БД был изменён на хостинге. Актуальный пароль — `1RW9VHE3w86^MSRG`. Старый пароль `s78SZV$BBF3T5MLK` больше не работает. Файлы `config.php` и `admin/config.php` обновлены.

---

## Шаблон (тема)

**OcTemplates Deals** — коммерческая тема от octemplates.net.  
Путь: `catalog/view/theme/oct_deals/`  
Лицензионных файлов не найдено — вероятно, используется без лицензии (нелицензионная копия).

Ключевые характеристики темы:
- Bootstrap-based, Twig-шаблонизатор
- Dark/Light mode поддержка
- Smart Checkout (oct_smartcheckout)
- Встроенный блог, FAQ, стикеры на товары
- Live-поиск, попапы (вход, корзина, покупка, звонок)
- ocfilter v4.8 (расширенный фильтр)
- Поддержка Open Graph, SEO URL

---

## Структура ключевых файлов

### 🎨 Тема (шаблоны — Twig)
```
catalog/view/theme/oct_deals/
├── template/
│   ├── common/
│   │   ├── header.twig        — Шапка сайта (DOCTYPE, мета, меню, поиск)
│   │   ├── footer.twig        — Подвал (контакты, соцсети, копирайт)
│   │   ├── home.twig          — Главная страница (обёртка)
│   │   ├── menu.twig          — Навигационное меню
│   │   └── search.twig        — Форма поиска
│   ├── product/
│   │   ├── category.twig      — Страница категории (листинг)
│   │   ├── product.twig       — Карточка товара
│   │   └── search.twig        — Результаты поиска
│   ├── checkout/
│   │   ├── checkout.twig      — Страница оформления заказа
│   │   ├── confirm.twig       — Подтверждение заказа
│   │   └── oct_smartcheckout/ — Быстрое оформление (OcTemplates)
│   ├── extension/module/
│   │   ├── filter.twig        — Встроенный фильтр OpenCart
│   │   └── slideshow.twig     — Слайдер на главной
│   └── octemplates/
│       ├── module/            — Попапы, живой поиск, корзина (oct_*)
│       └── product/           — oct_all_categories.twig
├── stylesheet/
│   ├── all.css                — Основные стили темы
│   ├── smartcheckout.css      — Стили быстрого оформления
│   └── css/                   — Дополнительные CSS
├── js/
│   ├── main.min.js            — Основной JS (минифицированный)
│   └── common.js              — Общие скрипты
└── fonts/ / images/           — Шрифты и изображения темы
```

### 🧩 OCMOD-модификации (system/)
```
system/
├── oct_deals_theme.ocmod.xml          — Ядро темы Deals v1.1.0
├── oct_deals_module_position.ocmod.xml — Позиции модулей
├── oct_deals_seotitle.ocmod.xml        — SEO заголовки
├── oct_deals_seourl.ocmod.xml          — SEO URL-ы
├── oct_deals_use_option_image.ocmod.xml— Изображения опций
├── oct_deals_ocfilter-4-8.ocmod.xml    — Интеграция с ocfilter
└── tweak.ocmod.xml                     — Твики для OpenCart 3x RU (v3.0.4.0)
```

### 🔍 Фильтр товаров (ocfilter)
| Тип | Путь |
|---|---|
| Контроллер | `catalog/controller/extension/module/ocfilter.php` |
| Модель | `catalog/model/extension/module/ocfilter.php` |
| Twig-шаблон | `catalog/view/theme/oct_deals/template/extension/module/filter.twig` |
| Админ-контроллер | `admin/controller/extension/module/ocfilter.php` |
| Админ-модель | `admin/model/extension/module/ocfilter/filter.php` |

### 🛒 Оформление заказа (Checkout)
| Тип | Путь |
|---|---|
| Контроллер (стандарт) | `catalog/controller/checkout/checkout.php` |
| Контроллер (SmartCheckout) | `catalog/controller/checkout/oct_smartcheckout.php` |
| Шаблон checkout | `catalog/view/theme/oct_deals/template/checkout/checkout.twig` |
| Шаблон SmartCheckout | `catalog/view/theme/oct_deals/template/checkout/oct_smartcheckout/` |

### 🦶 Подвал (Footer)
| Тип | Путь |
|---|---|
| Контроллер | `catalog/controller/common/footer.php` |
| Шаблон (Twig) | `catalog/view/theme/oct_deals/template/common/footer.twig` |
| Язык RU | `catalog/language/ru-ru/common/footer.php` |

### 🏠 Главная страница
| Тип | Путь |
|---|---|
| Шаблон-обёртка | `catalog/view/theme/oct_deals/template/common/home.twig` |
| Контент (модули) | задаётся через Layouts в админке OpenCart |
| Слайдер | `catalog/view/theme/oct_deals/template/extension/module/slideshow.twig` |
| Баннер+ | `catalog/view/theme/oct_deals/template/octemplates/module/oct_banner_plus.twig` |
| Контроллер главной | `catalog/controller/common/home.php` |

### ⚙️ Языковые файлы (Русский)
```
catalog/language/ru-ru/
├── ru-ru.php                    — Общие переводы
├── common/ (header.php, footer.php, menu.php, …)
├── product/ (category.php, product.php, …)
├── checkout/ (checkout.php, …)
└── octemplates/ (переводы модулей)
```

---

## Установленные расширения (ключевые)

| Расширение | Назначение |
|---|---|
| **ocfilter** | Расширенный фильтр товаров по параметрам |
| **oct_smartcheckout** | Быстрое оформление заказа в один шаг |
| **csvprice_pro** | Импорт/экспорт товаров по CSV |
| **oct_live_search** | Живой поиск с подсказками |
| **oct_popup_cart** | Попап корзины |
| **oct_popup_purchase** | Попап покупки/быстрого заказа |
| **oct_subscribe** | Подписка на рассылку |
| **oct_stickers** | Стикеры (новинка, хит, скидка) на товарах |

---

## Технический стек

- **PHP** 7.3+ (требование OpenCart 3.0.4.1)
- **MySQL** 5.x / MariaDB (mysqli driver, prefix `oc_`)
- **Twig** — шаблонизатор (встроен в OpenCart 3.x)
- **Bootstrap** — CSS-фреймворк темы
- **jQuery** — JS основа
- **Slick/Swiper** — слайдеры
- **Fancybox** — галерея изображений

---

## Выполненные задачи (Prompt 2 + 3)

### Prompt 2 — Фильтр, единицы измерения, кнопка каталога

| # | Задача | Файлы | Коммит |
|---|--------|-------|--------|
| **Task 1** | Fix фильтра на iOS Яндекс.Браузер — добавлен `window.addEventListener('load')` fallback | `catalog/view/javascript/ocfilter-mobile-fix.js`, `footer.twig` | `ece3c8e` |
| **Task 12** | Замена `I4кВт` → `кВт`: SQL-патч + PHP-скрипт для обновления БД | `sql_patches/fix_i4kvt_unit.sql`, `fix_units.php` | `d13eac8` |
| **Task 15** | Кнопка «Каталог товаров» на десктопе — прямой переход к первой категории без промежуточного sidebar | `catalog/view/javascript/ocfilter-mobile-fix.js` | `af1bda9` |

### Prompt 3 — Страницы контента

| # | Задача | Детали |
|---|--------|--------|
| **3.1** | Страница «О компании» | SEO-текст, H1/H2/H3, USP, контакты. Обновляется через `update_pages.php` |
| **3.2** | Страница «Оплата и доставка» | Способы оплаты, условия доставки, логотипы ПЭК/СДЭК/ДЛ/БС (SVG в `image/catalog/delivery/`) |
| **3.3** | Публичная оферта `/oferta` | Полный текст оферты; SEO URL; `config_checkout_agree=1` (обязательный чекбокс) |
| **3.4** | Email-уведомления | `config_mail_alert += 'review'` — уведомления при новых отзывах включены |

### Как применить изменения на сервере

1. **Фикс фильтра (Task 1, 15)** — уже в файлах темы, применится после выгрузки.
2. **Исправление единиц (Task 12)**:
   - Запустить: `https://насосыдаром.рф/fix_units.php?key=fix_units_2026`
   - **Удалить файл** после выполнения
3. **Страницы контента (Prompt 3)**:
   - Запустить: `https://насосыдаром.рф/update_pages.php?key=pages2026`
   - **Удалить файл** после выполнения
4. **Логотипы ТК** — SVG-заглушки уже в `image/catalog/delivery/`. Заменить реальными PNG при необходимости.

---

## Выполненные задачи (Prompt 4 + 5)

### Prompt 4 — Checkout & способы оплаты

| Задача | Файлы | Детали |
|--------|-------|--------|
| **Доставка** | `catalog/language/ru-ru/extension/shipping/pickup.php`, `ru-ru_/...`, `admin/language/...` | `text_title` → «По согласованию с менеджером»; `text_description` → «Самовывоз из г. Лобня или доставка по согласованию с менеджером» |
| **Оплата (bank_transfer)** | `catalog/language/ru-ru/extension/payment/bank_transfer.php` | Переименован в «Выставление счёта (для юридических лиц)» с инструкцией |
| **Оплата (tbank)** | `catalog/language/ru-ru/extension/payment/tbank.php` | Переименован в «Оплата картой или СБП онлайн» |
| **Активация модулей** | `update_payment.php` | PHP-скрипт включает `bank_transfer` и `tbank` в `oc_setting`/`oc_extension`. Запустить: `/update_payment.php?key=payment2026` |

### Prompt 5 — Футер, соцсети

| Задача | Файлы | Детали |
|--------|-------|--------|
| **Блок «Мы на Авито»** | `footer.twig` | Заменён блок «Мы в соцсетях»; добавлен логотип Авито, рейтинг ★★★★★, ссылка на профиль |
| **Иконки оплаты** | `footer.twig`, `payments/sberpay.svg`, `sbp.svg`, `mir.svg` | Убраны все зарубежные/украинские платёжки; оставлены только SberPay, СБП, Мир |
| **Логотип Авито** | `image/catalog/avito/avito_logo.svg` | SVG-логотип в корпоративном синем цвете |
| **Копирайт** | `catalog/language/ru-ru/common/footer.php` | Проверен: `© 2026 Kamtek. Продажа скважинных насосов ЭЦВ` — актуален |

### Как применить на сервере

1. **git pull** или загрузить файлы по FTP.
2. **Запустить** `https://насосыдаром.рф/update_payment.php?key=payment2026`
   - Активирует `bank_transfer` (выставление счёта) и `tbank` (оплата картой/СБП).
   - **Удалить файл** после выполнения.
3. В **AdminPanel → Расширения → Оплата → Т-Банк** ввести Terminal ID и Password.
4. Очистить кеш: **Расширения → Модификаторы → Обновить**.

---

## Выполненные задачи (Prompts 6–7) — деплой 28.02.2026

### Prompt 6 — Мобильная версия: обрезка баннера Камтек

| Задача | Файлы | Детали |
|--------|-------|--------|
| **Обрезка логотипа** | `catalog/view/theme/oct_deals/stylesheet/kamtek-mobile-fixes.css` | CSS media-query `@media (max-width: 767px)`: скрывает верхние ~22% (логотип KAMTEK PRO) через `object-fit: cover` + `object-position: center 24%` на контейнере `.ds-slideshow-plus-item-fullimg` |
| **Подключение CSS** | `catalog/view/theme/oct_deals/template/common/header.twig` | `<link rel="stylesheet" href="...kamtek-mobile-fixes.css">` добавлен перед `</head>` |
| **Responsive видео** | `kamtek-mobile-fixes.css` | `.video-wrapper` с `padding-bottom: 56.25%` для корректного 16:9 iframe на всех экранах |

**Логика обрезки:** Мобильные баннеры (`*MOB*.PNG`, 1108×978px) имеют логотип KAMTEK PRO в верхних ~216px (~22%). Через `max-height: 75vw` на контейнере и `object-position: center 24%` на `<img>` логотип скрывается, показывая только контентную часть насоса.

---

### Prompt 7 — Яндекс.Метрика, Rutube видео, Фавикон

| # | Задача | Файлы | Детали |
|---|--------|-------|--------|
| **7.10** | Яндекс.Метрика | `header.twig` | Счётчик ID=106173077, добавлен перед `</head>`. Параметры: webvisor, clickmap, ecommerce, accurateTrackBounce |
| **7.11** | Видео Rutube | `oc_module.setting` (module_id=33) | Заменён src iframe: новый ID `492f623d6991c245c71702d6fdae7f75?skinColor=7CB342`; модуль включён (status=1). HTML-контент записан в БД через `write_video.php` |
| **7.17** | Фавикон | `image/favicon.ico` (645B), `image/favicon.svg` (961B) | ICO-файл создан из логотипа logo-kamtek.webp (300×41px); SVG-favicon с K-образным символом. Теги подключены в `header.twig`: `rel="icon"` (ICO+SVG), `rel="shortcut icon"`, `rel="apple-touch-icon"` |

**Структура видео-блока на главной (модуль 33):**
```html
<section class="company-grid">
  <div class="company-grid__video">
    <div class="video-wrapper">
      <iframe src="https://rutube.ru/play/embed/492f623d6991c245c71702d6fdae7f75?skinColor=7CB342" ...></iframe>
    </div>
  </div>
  <div class="company-grid__content">...</div>
</section>
```

### Новые файлы (Prompts 6–7)

| Файл | Назначение |
|------|-----------|
| `catalog/view/theme/oct_deals/stylesheet/kamtek-mobile-fixes.css` | Мобильные CSS-фиксы (баннер + видео) |
| `image/favicon.ico` | Фавикон ICO (645 bytes) |
| `image/favicon.svg` | Фавикон SVG (961 bytes) |

---

## Деплой на сервер (28.02.2026) — восстановление сайта

### Что было обнаружено
1. **Имя директории** было изменено с `насосыдаром_рф` на `NASOS`, потом обратно — директория теперь `насосыдаром_рф` ✅
2. **Пароль БД** в config.php устарел — сайт выдавал HTTP 500. Исправлен на `1RW9VHE3w86^MSRG` ✅

### Что было сделано
1. Восстановлено подключение к БД (обновлён пароль в config.php и admin/config.php)
2. Загружены все изменения из git на сервер по SSH (26 файлов)
3. Запущен и выполнен `update_payment.php` — активированы bank_transfer и tbank
4. Запущен и выполнен `update_pages.php` — обновлены страницы «О компании», «Оплата и доставка», создана «Публичная оферта»
5. Запущен и выполнен `fix_units.php` — исправлена единица «I4кВт» → «кВт» в БД
6. Удалены временные PHP-скрипты с сервера
7. Очищен кеш OpenCart

---

## Деплой на сервер (28.02.2026) — Prompts 6–7

### Что было сделано
1. **Мобильный баннер** — создан `kamtek-mobile-fixes.css` с обрезкой логотипа KAMTEK PRO ✅
2. **Яндекс.Метрика** — счётчик 106173077 добавлен в `header.twig` ✅
3. **Rutube видео** — обновлён src iframe в HTML-модуле №33 (новый ID `492f623d...`), модуль включён ✅
4. **Фавикон** — добавлены `favicon.ico` и `favicon.svg`, теги подключены в `header.twig` ✅
5. Все файлы загружены на сервер через SCP ✅
6. Временные PHP-скрипты удалены с сервера ✅
7. Кеш OpenCart очищен ✅

### Статус проверок после деплоя

| URL | Статус |
|-----|--------|
| `https://насосыдаром.рф/` | ✅ 200 OK |
| `https://насосыдаром.рф/contact` | ✅ 200 OK |
| `https://насосыдаром.рф/admin/` | ✅ 200 OK |
| `https://насосыдаром.рф/image/favicon.ico` | ✅ 200 OK |
| `https://насосыдаром.рф/image/favicon.svg` | ✅ 200 OK |
| `.../stylesheet/kamtek-mobile-fixes.css` | ✅ 200 OK |
| Яндекс.Метрика в HTML | ✅ 1 вхождение |
| favicon теги в HTML | ✅ ICO×3, SVG×1 |
| Rutube iframe на главной | ✅ ID 492f623d |

---

## GitHub

- **Репозиторий:** https://github.com/lexabodr2003-prog/NADOSIR
- **Ветка:** `main`
- **Последние коммиты:**
  - `a55e6a0` — исходное состояние сайта
  - `8a69b7c` — README и .gitignore
  - `ece3c8e` — fix: фильтр iOS Яндекс.Браузер
  - `af1bda9` — feat: кнопка каталога (Task 15)
  - `d13eac8` — fix: I4кВт → кВт (Task 12)
  - `9b84c04` — feat: страницы контента (Prompt 3)
  - `1c2470a` — docs: README обновлён
  - `b7db136` — feat: prompts 4+5 — оплата, доставка, футер
  - `9e75d0d` — docs: README prompts 4+5
  - `ce69800` — fix: пароль БД (28.02.2026)
  - `39f1215` — docs: README деплой 28.02.2026
  - `a7a74a2` — feat: prompts 6-7 (баннер, метрика, видео, фавикон)
  - `4bb0fec` — docs: README prompts 6-7

---

## Деплой (28.02.2026) — Prompts 8–10 + технические исправления

### Что было сделано

#### П.10/18 — Meta-теги
- `config_meta_title` → «Скважинные насосы ЭЦВ — купить насос для скважины | КАМТЕК» ✅
- `config_meta_description` → полное описание ✅
- Добавлены OG-теги в `header.twig`: `og:site_name`, `og:title`, `og:description`, `og:type`, `og:url`, `og:image` ✅

#### П.8/13 — Отзывы
- Отзывы включены (`review_status=1`), гостевые отзывы разрешены ✅
- Email-уведомления при новом отзыве активированы (`config_mail_alert` содержит `review`) ✅
- Уведомления уходят на `info@kamtek.pro` ✅

#### П.9/19 — Товары на главной по сериям
- Созданы 5 подкатегорий: Серия 4 (id=2), Серия 5 (id=3), Серия 6 (id=4), Серия 8 (id=5), Серия 10 (id=6) ✅
- SEO URL: `/ecv-seria-4`, `/ecv-seria-5`, `/ecv-seria-6`, `/ecv-seria-8`, `/ecv-seria-10` ✅
- Все 177 товаров привязаны к подкатегориям по серии модели ✅
- На главной используется модуль `featured.28` с 2 дешевейшими из каждой серии (товары 28,2,73,74,118,91,162,151,175,176) ✅

#### П.9/16 — Кнопка «Подписаться»
- Таблица `oc_oct_subscribe` пересоздана с правильной структурой (поля `approved`, `hash`) ✅
- `oct_subscribe_status=1` активирован в настройках ✅
- AJAX-подписка работает, возвращает подтверждение ✅

#### Технические исправления
- SEO URL `/publichnaya-oferta` добавлен, страница привязана к store_id=0 ✅
- Дубли SEO URL для серий удалены ✅
- Все 12 ключевых страниц возвращают HTTP 200 ✅

### Статус всех страниц

| URL | Статус |
|-----|--------|
| `https://насосыдаром.рф/` | ✅ 200 |
| `https://насосыдаром.рф/o-kompanii` | ✅ 200 |
| `https://насосыдаром.рф/payment-and-delivery` | ✅ 200 |
| `https://насосыдаром.рф/publichnaya-oferta` | ✅ 200 |
| `https://насосыдаром.рф/ecv-seria-4` | ✅ 200 |
| `https://насосыдаром.рф/ecv-seria-5` | ✅ 200 |
| `https://насосыдаром.рф/ecv-seria-6` | ✅ 200 |
| `https://насосыдаром.рф/ecv-seria-8` | ✅ 200 |
| `https://насосыдаром.рф/ecv-seria-10` | ✅ 200 |
| `https://насосыдаром.рф/goods` (Яндекс.Товары) | ✅ 200 |
| `https://насосыдаром.рф/image/favicon.ico` | ✅ 200 |
| `https://насосыдаром.рф/image/favicon.svg` | ✅ 200 |
| OG-теги на главной | ✅ og:title, og:description, og:url, og:image |
| Главная — товары из всех серий 4,5,6,8,10 | ✅ |
| Подписка AJAX | ✅ работает |

### Ожидает выполнения (нужен Excel-файл)
- **Обновление ассортимента и цен** — требуется загрузка Excel прайс-листа

### Ожидает ручного действия
- **Т-Банк** — ввести Terminal ID и Password в Admin → Extensions → Payments → T-Bank
- **Refresh OCMOD** — Admin → Extensions → Modifications → Обновить

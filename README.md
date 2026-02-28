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
DB_PASSWORD: s78SZV$BBF3T5MLK
DB_DATABASE: s906333804_nasos
DB_PORT:     3306
DB_PREFIX:   oc_
```

> **Примечание:** Пароль клиента `1RW9VHE3w86^MSRG` (из задания) **не совпадает** с паролем в конфиге (`s78SZV$BBF3T5MLK`). Скорее всего, `1RW9VHE3w86^MSRG` — это пароль от панели хостинга/cPanel, а `s78SZV$BBF3T5MLK` — от самой MySQL базы.

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

## GitHub

- **Репозиторий:** https://github.com/lexabodr2003-prog/NADOSIR
- **Ветка:** `main`
- **Коммиты:**
  - `a55e6a0` — исходное состояние сайта
  - `8a69b7c` — README и .gitignore
  - `ece3c8e` — fix: фильтр iOS Яндекс.Браузер
  - `af1bda9` — feat: кнопка каталога (Task 15)
  - `d13eac8` — fix: I4кВт → кВт (Task 12)
  - `9b84c04` — feat: страницы контента (Prompt 3)

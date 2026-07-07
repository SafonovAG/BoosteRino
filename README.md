<div align="center">

# BoosteRino

**Премиальная SMM-платформа для продвижения в социальных сетях**

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/)
[![License](https://img.shields.io/badge/License-GPL--3.0-blue?style=for-the-badge)](LICENSE)

[Демо](https://boosterino.ru) · [Репозиторий](https://github.com/SafonovAG/BoosteRino) · [Issues](https://github.com/SafonovAG/BoosteRino/issues)

</div>

---

## О проекте

**BoosteRino** — современное веб-приложение для заказа услуг продвижения и накрутки. Интеграция с API поставщика Twiboost, личный кабинет покупателя, админ-панель с наценкой и приём платежей через ЮMoney.

Создано с помощью [Cursor](https://cursor.com).

### Возможности

| Модуль | Описание |
|--------|----------|
| Каталог услуг | Синхронизация с Twiboost, цены в ₽ с наценкой |
| Личный кабинет | Баланс, заказы, refill и отмена |
| Оплата | Предоплата на баланс или оплата заказа через ЮMoney |
| Админ-панель | Роли superadmin/admin, настройки из БД |
| UI | Адаптивный дизайн, светлая / тёмная / авто-тема |

### Стек

```
PHP 8.3  →  REST API + серверные шаблоны
MySQL 8  →  данные и настройки (без .env)
Apache   →  mod_rewrite, document root: public/
JS       →  vanilla fetch, без фреймворков
```

---

## Быстрый старт

```bash
# 1. Зависимости
composer install

# 2. Подключение к БД (файл не коммитится в git)
cp config/database.example.php config/database.php
# отредактируйте config/database.php

# 3. Схема базы
mysql -u brino -p brino < database/schema.sql

# 4. Первый superadmin
php bin/create_superadmin.php admin@example.com YourPassword123

# 5. Apache: DocumentRoot → public/
```

После установки войдите в админку и заполните в **Настройках**: API-ключ Twiboost, кошелёк ЮMoney, SMTP.

---

## Структура

```
app/          — ядро, API, сервисы, middleware
config/       — маршруты, database.example.php
database/     — schema.sql
public/       — точка входа (index.php, assets)
views/        — PHP-шаблоны
cron/         — синхронизация услуг и статусов заказов
bin/          — CLI-утилиты
```

---

## Cron (хостинг)

| Скрипт | Интервал |
|--------|----------|
| `php cron/sync_services.php` | каждые 6 ч |
| `php cron/sync_orders.php` | каждые 3 мин |

---

## Безопасность

- Секреты (API-ключи, пароли) — **только** в `config/database.php` (локально) и таблице `settings` в MySQL
- В публичный репозиторий не попадают: `.cursor/`, `.githooks/`, `config/database.php`

---

<div align="center">

**[boosterino.ru](https://boosterino.ru)** · Made with Cursor

</div>

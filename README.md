<div align="center">

# BoosteRino

**Премиальная SMM-платформа для продвижения в социальных сетях**

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/)
[![Live](https://img.shields.io/badge/Сайт-boosterino.ru-6366f1?style=for-the-badge)](https://boosterino.ru)

### [boosterino.ru](https://boosterino.ru) - смотрите проект вживую и заказывайте услуги прямо сейчас

[Перейти на сайт](https://boosterino.ru) · [Каталог услуг](https://boosterino.ru/services) · [Регистрация](https://boosterino.ru/register) · [GitHub](https://github.com/SafonovAG/BoosteRino)

</div>

---

## Живой проект

**BoosteRino** уже работает на [boosterino.ru](https://boosterino.ru):

- Просмотрите лендинг и каталог услуг в реальном времени
- Зарегистрируйтесь и оформите заказ в личном кабинете
- Пополните баланс или оплатите заказ через ЮMoney
- Оцените адаптивный интерфейс и тёмную/светлую тему на своём устройстве

> Этот репозиторий - исходный код production-сайта [boosterino.ru](https://boosterino.ru).

---

## О проекте

**BoosteRino** - веб-приложение для заказа услуг продвижения и накрутки. Интеграция с API поставщика Twiboost, личный кабинет покупателя, админ-панель с наценкой и приём платежей через ЮMoney.

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
PHP 8.3   ->  REST API + серверные шаблоны, без фреймворков
MySQL 8   ->  данные и настройки (без .env)
Apache    ->  mod_rewrite, document root: корень проекта
JavaScript -> vanilla fetch, без фреймворков
```

**Без Composer на сервере** - автозагрузка через `bootstrap/autoload.php`, SMTP через встроенный `fsockopen`.

---

## Установка на хостинг (PHP + MySQL)

1. Загрузите файлы проекта на хостинг (FTP, PhpStorm Deployment и т.п.)
2. Импортируйте схему: `mysql -u USER -p DB < database/schema.sql`
3. Скопируйте `config/database.example.php` в `config/database.php` и укажите доступ к MySQL
4. Создайте superadmin через phpMyAdmin (SQL):

```sql
-- Готовый вариант: admin@boosterino.ru / ChangeMe123!
-- Полный файл: debug/sql/create_superadmin.sql (локально, не в git)

INSERT INTO users (email, password_hash, role, email_verified_at)
VALUES (
    'admin@boosterino.ru',
    '$2b$12$H5MFLDm.NeSeB2UoMf.QceJUS/TfO.5kwOneLLmTpqRdJ/Ux1ThRi',
    'superadmin',
    NOW()
)
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    role = 'superadmin',
    email_verified_at = NOW();
```

Свой email и пароль: локально `php debug/tools/generate_superadmin_sql.php email password` - скопируйте вывод в phpMyAdmin.

5. В админке заполните: API-ключ Twiboost, кошелёк ЮMoney, SMTP, глобальную наценку
6. Настройте cron (см. ниже)

Требования: PHP 8.3+, MySQL 8, Apache с `mod_rewrite`.

---

## Структура

```
index.php       - точка входа (front controller)
.htaccess       - маршрутизация, защита служебных папок
app/            - ядро, API, сервисы, middleware
assets/         - CSS и JavaScript
bootstrap/      - PSR-4 autoload без Composer
config/         - маршруты, database.example.php
database/       - schema.sql
views/          - PHP-шаблоны
cron/           - синхронизация услуг и статусов заказов
bin/            - CLI-утилиты (если есть SSH)
debug/          - локальные SQL и тесты (не в git)
storage/cache/  - кэш (не в git, кроме .gitkeep)
```

---

## Cron (хостинг)

| Скрипт | Интервал |
|--------|----------|
| `php cron/sync_services.php` | каждые 6 ч |
| `php cron/sync_orders.php` | каждые 3 мин |

---

## Безопасность

- Доступ к MySQL - **только** в `config/database.php` (локально, не в git)
- API-ключ Twiboost, ЮMoney, SMTP - в таблице `settings` в MySQL
- В публичный репозиторий не попадают: `config/database.php`, `.cursor/`, `.githooks/`, `api/info/`, `debug/`, `SECURITY.md`

---

<div align="center">

**[boosterino.ru](https://boosterino.ru)** - заказывайте SMM-услуги онлайн · Made with Cursor

</div>

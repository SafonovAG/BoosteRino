# Безопасность и секреты

Репозиторий **публичный**. Никогда не коммитьте реальные ключи и пароли.

## Где хранить секреты

| Данные | Где |
|--------|-----|
| Пароль MySQL | `config/database.php` (не в git) |
| Twiboost API key | Таблица `settings` → админка |
| ЮMoney wallet/secret | Таблица `settings` → админка |
| SMTP пароль | Таблица `settings` → админка |
| `app_secret` | Генерируется автоматически в БД |

## Первоначальная настройка

```bash
cp config/database.example.php config/database.php
# Отредактируйте database.php локально — файл в .gitignore
```

Включите pre-commit hook:

```bash
git config core.hooksPath .githooks
```

На Windows (Git Bash): `chmod +x .githooks/pre-commit`

## Что безопасно в git

- `config/database.example.php` — только плейсхолдеры
- `database/schema.sql` — пустые значения для sensitive settings
- Код без захардкоженных ключей

## Утечка секрета

1. Немедленно смените ключ/пароль у провайдера
2. Обновите значение на сервере / в БД
3. При необходимости очистите историю git (`git filter-repo`)

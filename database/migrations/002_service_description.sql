-- Добавление описания товара (услуги) для админки
ALTER TABLE services ADD COLUMN description TEXT NULL DEFAULT NULL AFTER category;

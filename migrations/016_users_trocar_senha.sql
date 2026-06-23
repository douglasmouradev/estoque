ALTER TABLE users
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER ativo;

UPDATE users SET must_change_password = 1 WHERE email = 'admin@oficina.local';

# Sistema de Estoque e Orçamentos — Oficina Mecânica

PHP 8.3 + MySQL 8, MVC artesanal, frontend HTML/CSS/JS vanilla.

## Requisitos

- PHP 8.3+ (pdo_mysql, json, mbstring, gd)
- MySQL 8.0+
- Composer

## Instalação

```bash
composer install
copy .env.example .env
php bin/migrate.php
cd public && php -S localhost:8080 router.php
```

**Login:** `admin@oficina.local` / `admin123`

## Módulos

| Módulo | Rota | Descrição |
|--------|------|-----------|
| Estoque | `/estoque` | Peças, CSV, alertas |
| Orçamentos | `/orcamentos` | Versionamento, e-mail, portal |
| OS | `/os` | Itens, financeiro, portal cliente |
| Financeiro | `/financeiro` | Contas a receber, export CSV |
| Relatórios | `/relatorios` | KPIs, gráficos, export |
| Auditoria | `/auditoria` | Log de ações |
| Serviços | `/servicos` | Catálogo com autocomplete |
| Portal | `/portal/*` | Cliente aprova orçamento / acompanha OS |
| API Docs | `/api/docs` | OpenAPI + Swagger UI |

## E-mail

Configure no `.env`:

```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.seudominio.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM=noreply@oficina.local
```

Em desenvolvimento use `MAIL_DRIVER=log` (grava em `storage/logs`).

## Scripts úteis

```bash
php bin/migrate.php              # migrations
php bin/backup.php               # backup MySQL
php bin/process-notifications.php # fila de e-mails
composer test                    # PHPUnit
```

## Docker

```bash
docker compose up -d
```

Migrations rodam automaticamente no startup.

## Segurança

- CSP, HSTS (HTTPS), rate limit login/portal
- Recuperação de senha por e-mail
- CSRF, auditoria, permissões por perfil

## Produção

- `APP_DEBUG=false`, `APP_AUTO_CREATE_DB=false`
- HTTPS obrigatório para cookies secure
- Cron: `php bin/process-notifications.php` a cada minuto
- Cron: `php bin/backup.php` diário

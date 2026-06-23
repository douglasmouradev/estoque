# Sistema de Estoque e Orçamentos — Oficina Mecânica

PHP 8.3 + MySQL 8, MVC artesanal, frontend HTML/CSS/JS vanilla.

## Requisitos

- PHP 8.3+ (extensões: pdo_mysql, json, mbstring, gd ou imagick para Dompdf)
- MySQL 8.0+
- Composer
- Apache com `mod_rewrite` (ou servidor embutido apontando para `public/`)

## Instalação

```bash
cd C:\Users\Douglas\Desktop\Projetos\estoque
composer install
copy .env.example .env
```

Edite `.env` com credenciais do MySQL e crie o banco:

```sql
CREATE DATABASE oficina_estoque CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Execute as migrations:

```bash
php bin/migrate.php
```

Servidor de desenvolvimento:

```bash
cd public
php -S localhost:8080 router.php
```

Ou na raiz: `composer serve`

Acesse: http://localhost:8080

**Login padrão:** `admin@oficina.local` / `admin123` (troca obrigatória no primeiro acesso)

## Docker

```bash
docker compose up -d
```

App em http://localhost:8080 — MySQL na porta 3306.

## Módulos

| Módulo | Descrição |
|--------|-----------|
| Estoque | Peças, movimentações, CSV, alertas de mínimo |
| Orçamentos | Versionamento, envio, portal do cliente, reserva de estoque |
| OS | Itens avulsos, checklist, horas, financeiro |
| Serviços | Catálogo de serviços reutilizáveis |
| Relatórios | Dashboard operacional e financeiro |
| Busca global | Clientes, placas, peças e OS no header |

## Portal do cliente

Ao enviar um orçamento, o sistema gera um link público (`/portal/orcamento/{token}`) válido por 30 dias. O cliente pode aprovar ou reprovar sem login.

Configure `APP_URL` no `.env` para links corretos em produção.

## Variáveis `.env` importantes

| Variável | Uso |
|----------|-----|
| `APP_URL` | Base dos links do portal |
| `APP_DEBUG` | `false` em produção |
| `APP_AUTO_CREATE_DB` | `false` em produção |
| `DB_*` | Conexão MySQL |

## Health check

`GET /health` — retorna status da aplicação e do banco (útil para Docker/CI).

## Testes

```bash
composer test
```

## Estrutura

- `public/` — document root
- `src/Core/` — Router, DB, Auth, Events, Security
- `src/Services/` — regras de negócio (Orçamento, Financeiro, Relatórios, PDF)
- `src/Models/` — PDO
- `migrations/` — SQL numerado (001–017)
- `storage/` — PDFs, uploads, logs

## Perfis

| Perfil   | Acesso                                      |
|----------|---------------------------------------------|
| admin    | Tudo, incluindo `/config` e usuários        |
| gerente  | Tudo exceto configurações                   |
| mecânico | OS + estoque (leitura), sem orçamentos/relatórios |

## CSV de peças

Separador `;`, cabeçalho: `codigo_interno;descricao;unidade;codigo_oem;marca;localizacao;estoque_minimo;preco_venda;estoque_inicial`

## Produção

- `APP_DEBUG=false` e `APP_AUTO_CREATE_DB=false`
- HTTPS (cookie `secure` ativado automaticamente)
- Rate limit no login
- Headers de segurança via `SecurityHeaders`

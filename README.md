# Sistema de Ordenes de Compra

Aplicacion web para gestion de ordenes de compra con Laravel. Incluye autenticacion por usuario, control por roles, flujo de aprobaciones, auditoria, exportaciones PDF, adjuntos y dashboard operativo.

## Stack tecnico

- Backend: Laravel 13, PHP 8.3
- Base de datos: PostgreSQL 16
- Web server: Nginx 1.27 (reverse proxy hacia PHP-FPM)
- Infra local: Docker Compose + Makefile
- Exportaciones: barryvdh/laravel-dompdf
- API autenticada: Laravel Sanctum (`/api/v1/auth/me`)

## Arquitectura del repositorio

- `backend/`: aplicacion Laravel (controladores, modelos, rutas, vistas, tests)
- `infra/nginx/default.conf`: configuracion Nginx
- `infra/php/Dockerfile`: imagen PHP-FPM
- `scripts/`: utilidades para subir/bajar servicios y backup/restore de BD
- `docker-compose.yml`: orquestacion de contenedores
- `backups/`: backups comprimidos `.sql.gz`

## Puertos de contenedores

- Web: `8090:80`
- PostgreSQL: `5434:5432`

## Requisitos

- Docker
- Docker Compose

## Primer arranque

1) Crear variables de entorno del root (si no existen):

```bash
cp .env.example .env
```

2) Crear variables de entorno de Laravel (si no existen):

```bash
cp backend/.env.example backend/.env
```

3) Levantar servicios:

```bash
docker compose up -d --build
```

4) Generar clave de Laravel (primera vez):

```bash
docker compose exec php php artisan key:generate
```

5) Ejecutar migraciones y seeders:

```bash
docker compose exec php php artisan migrate --seed
```

6) Abrir aplicacion:

- `http://localhost:8090`

## Usuario inicial

- Login: `http://localhost:8090/login`
- Usuario: `admin`
- Password inicial: `admin`
- En primer login obliga cambio de password.

## Roles y permisos

Roles de negocio vigentes:

- `administrador`
- `usuario_solicitante`
- `usuario_autorizado`

Comportamiento principal:

- `administrador`: acceso total (dashboard, ordenes, aprobaciones, proveedores, auditoria, usuarios).
- `usuario_solicitante`: crea/edita ordenes y proveedores; envia ordenes a aprobacion.
- `usuario_autorizado`: puede aprobar/rechazar ordenes pendientes y autoaprobar envios propios por rol.

## Modulos funcionales

- Dashboard (`/dashboard`)
- Ordenes (`/orders`)
- Bandeja de aprobaciones (`/approvals`)
- Proveedores (`/suppliers`)
- Auditoria (`/audit-logs`, solo administrador)
- Usuarios (`/users`, solo administrador)
- API perfil autenticado (`/api/v1/auth/me`)

## Flujo de ordenes

Estados principales:

- `draft`
- `pending_approval`
- `approved`
- `rejected`

Regla actual de aprobacion:

- `administrador` y `usuario_autorizado`: al enviar (`submit`) autoaprueban.
- `usuario_solicitante`: al enviar pasa a `pending_approval`.

## Numeracion automatica

- Ordenes: `OC-YYYY-######`
- Proveedores: `PRV-######`

## Adjuntos

- Endpoint upload: `POST /orders/{order}/attachments`
- Endpoint download: `GET /orders/{order}/attachments/{attachment}`
- Tipos permitidos: `pdf,png,jpg,jpeg,doc,docx,xls,xlsx,csv,txt`
- Tamano maximo: `10 MB` por archivo

## Exportaciones PDF

- Ordenes: `/orders/export`
- Orden individual (solo aprobada): `/orders/{order}/export`
- Proveedores: `/suppliers/export`
- Auditoria: `/audit-logs/export`

## Comandos utiles

### Docker Compose

- Estado: `docker compose ps`
- Logs: `docker compose logs -f`
- Bajar servicios: `docker compose down`
- Bajar y borrar volumenes: `docker compose down -v`

### Makefile

- `make help`
- `make up`
- `make down`
- `make reset`
- `make logs`
- `make ps`
- `make migrate`
- `make seed`
- `make test`
- `make backup`
- `make restore FILE=backups/<archivo.sql.gz>`

### Scripts

- Levantar todo: `./scripts/up_all.sh`
- Levantar y compilar assets: `./scripts/up_all.sh --with-assets`
- Bajar servicios: `./scripts/down_services.sh`
- Bajar con volumenes: `./scripts/down_services.sh --volumes`
- Backup BD: `./scripts/backup_db.sh`
- Restore BD: `./scripts/restore_db.sh backups/<archivo.sql.gz>`

## Persistencia

- Volumen de PostgreSQL: `postgres_data`
- Volumen de storage app: `app_storage`
- Backups en host: `backups/`

## Pruebas

Ejecutar:

```bash
docker compose exec php php artisan test
```

Cobertura principal en:

- Auth (login, cambio de password, roles, gestion de usuarios)
- Ordenes (draft, aprobacion, adjuntos, export)
- Proveedores (CRUD)
- Auditoria (acceso y export)
- Dashboard (metricas)

## Notas tecnicas

- Healthchecks configurados en `nginx`, `php` y `postgres`.
- Nginx incluye headers base de seguridad y limite de subida de `12m`.
- `PO_APPROVAL_THRESHOLD` existe en configuracion, pero el flujo actual opera por rol de usuario.

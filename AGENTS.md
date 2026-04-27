# AGENTS Guide

## Objetivo
- Este repositorio contiene un sistema de ordenes de compra en Laravel.
- Usa este archivo como guia rapida para agentes que trabajen en el proyecto.

## Estructura clave
- `backend/`: aplicacion Laravel (rutas, controladores, modelos, vistas, tests).
- `docker-compose.yml`: orquestacion local (nginx, php, postgres).
- `infra/nginx/default.conf`: configuracion web.
- `infra/php/Dockerfile`: imagen de PHP-FPM.
- `scripts/`: utilidades operativas (up/down, backup/restore).

## Puertos locales
- App web: `http://localhost:8090`
- PostgreSQL host: `localhost:5434`

## Flujo recomendado de trabajo
- Levantar stack: `docker compose up -d --build`
- Ejecutar migraciones y seeders: `docker compose exec php php artisan migrate --seed`
- Ejecutar tests: `docker compose exec php php artisan test`
- Ver estado: `docker compose ps`

## Reglas funcionales importantes
- Roles canonicos: `administrador`, `usuario_solicitante`, `usuario_autorizado`.
- Login por `username` + `password`.
- Usuarios inactivos (`is_active = false`) no pueden ingresar.
- Si `must_change_password = true`, el usuario debe cambiar contrasena antes de continuar.

## Archivos de referencia para cambios
- Rutas y permisos: `backend/routes/web.php`
- Middleware y aliases: `backend/bootstrap/app.php`
- Flujo de ordenes/aprobacion: `backend/app/Http/Controllers/PurchaseOrderController.php`
- Proveedores: `backend/app/Http/Controllers/SupplierController.php`
- Usuarios: `backend/app/Http/Controllers/UserController.php`

## Verificacion minima antes de cerrar cambios
- Revisar que la app responda en `http://localhost:8090`.
- Correr al menos tests del modulo tocado.
- Si se tocan roles/rutas, validar tests de auth/roles y ordenes.

## Nota de datos
- Backups se guardan en `backups/`.
- Scripts utiles:
  - `./scripts/backup_db.sh`
  - `./scripts/restore_db.sh backups/<archivo.sql.gz>`

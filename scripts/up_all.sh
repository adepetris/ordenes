#!/usr/bin/env bash
set -euo pipefail

WITH_ASSETS=0

for arg in "$@"; do
  case "$arg" in
    --with-assets)
      WITH_ASSETS=1
      ;;
    *)
      printf 'Uso: %s [--with-assets]\n' "$0"
      exit 1
      ;;
  esac
done

# printf '==> Preparando variables de entorno...\n'
# [ -f .env ] || cp .env.example .env
# [ -f backend/.env ] || cp backend/.env.example backend/.env

printf '==> Levantando contenedores...\n'
docker compose up -d --build

printf '==> Esperando que php este listo...\n'
until docker compose exec -T php php -v >/dev/null 2>&1; do
  sleep 2
done

printf '==> Ajustando permisos de Laravel (storage/bootstrap)...\n'
docker compose exec -T php sh -lc 'chmod -R a+rwX storage bootstrap/cache'

printf '==> Instalando dependencias PHP...\n'
docker compose exec -T php composer install --no-interaction --prefer-dist

printf '==> Generando APP_KEY si falta...\n'
if ! grep -q '^APP_KEY=base64:' backend/.env; then
  docker compose exec -T php php artisan key:generate --force
fi

printf '==> Ejecutando migraciones y seed...\n'
docker compose exec -T php php artisan migrate --seed --force

if [ "$WITH_ASSETS" -eq 1 ]; then
  printf '==> Instalando dependencias frontend...\n'
  docker compose exec -T php npm install --no-audit --no-fund
  printf '==> Compilando assets...\n'
  docker compose exec -T php npm run build
fi

printf '==> Estado de servicios:\n'
docker compose ps

printf '==> Listo. Abri: http://localhost:8090\n'

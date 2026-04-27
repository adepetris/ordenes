#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
COMPOSE_CMD=(docker compose -f "$ROOT_DIR/docker-compose.yml" --project-directory "$ROOT_DIR")

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

printf '==> Preparando variables de entorno...\n'
[ -f "$ROOT_DIR/.env" ] || cp "$ROOT_DIR/.env.example" "$ROOT_DIR/.env"
[ -f "$ROOT_DIR/backend/.env" ] || cp "$ROOT_DIR/backend/.env.example" "$ROOT_DIR/backend/.env"

printf '==> Levantando contenedores...\n'
"${COMPOSE_CMD[@]}" up -d --build

printf '==> Esperando que php este listo...\n'
until "${COMPOSE_CMD[@]}" exec -T php php -v >/dev/null 2>&1; do
  sleep 2
done

printf '==> Ajustando permisos de Laravel (storage/bootstrap)...\n'
"${COMPOSE_CMD[@]}" exec -T php sh -lc 'chmod -R a+rwX storage bootstrap/cache'

printf '==> Instalando dependencias PHP...\n'
"${COMPOSE_CMD[@]}" exec -T php composer install --no-interaction --prefer-dist

printf '==> Generando APP_KEY si falta...\n'
if ! "${COMPOSE_CMD[@]}" exec -T php sh -lc "grep -q '^APP_KEY=base64:' .env"; then
  "${COMPOSE_CMD[@]}" exec -T php php artisan key:generate --force
fi

printf '==> Ejecutando migraciones y seed...\n'
"${COMPOSE_CMD[@]}" exec -T php php artisan migrate --seed --force

if [ "$WITH_ASSETS" -eq 1 ]; then
  printf '==> Instalando dependencias frontend...\n'
  "${COMPOSE_CMD[@]}" exec -T php npm install --no-audit --no-fund
  printf '==> Compilando assets...\n'
  "${COMPOSE_CMD[@]}" exec -T php npm run build
fi

printf '==> Estado de servicios:\n'
"${COMPOSE_CMD[@]}" ps

printf '==> Listo. Abri: http://localhost:8090\n'

#!/usr/bin/env bash
set -euo pipefail

if [ "${1:-}" = "--volumes" ]; then
  printf '==> Bajando servicios y borrando volumenes...\n'
  docker compose down -v
elif [ "${1:-}" = "" ]; then
  printf '==> Bajando servicios (manteniendo datos)...\n'
  docker compose down
else
  printf 'Uso: %s [--volumes]\n' "$0"
  exit 1
fi

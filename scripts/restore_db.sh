#!/usr/bin/env bash

set -euo pipefail

if [[ $# -lt 1 ]]; then
    printf 'Uso: scripts/restore_db.sh <archivo.sql.gz>\n' >&2
    exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
INPUT_FILE="$1"

if [[ "${INPUT_FILE}" != /* ]]; then
    INPUT_FILE="${ROOT_DIR}/${INPUT_FILE}"
fi

if [[ ! -f "${INPUT_FILE}" ]]; then
    printf 'No existe el archivo: %s\n' "${INPUT_FILE}" >&2
    exit 1
fi

gunzip -c "${INPUT_FILE}" | docker compose exec -T postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"'

printf 'Restore completado desde: %s\n' "${INPUT_FILE}"

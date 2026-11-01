#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."
exec docker compose exec -T db sh -c 'exec mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" "$@"' sh "$@"

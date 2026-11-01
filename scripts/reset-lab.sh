#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    echo "Thiếu tệp .env. Sao chép .env.example thành .env trước khi chạy."
    exit 1
fi

docker compose down -v
docker compose up -d --build
docker compose ps

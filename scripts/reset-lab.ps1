$ErrorActionPreference = "Stop"
Set-Location (Join-Path $PSScriptRoot "..")

if (-not (Test-Path ".env")) {
    Write-Output "Thiếu tệp .env. Sao chép .env.example thành .env trước khi chạy."
    exit 1
}

docker compose down -v
docker compose up -d --build
docker compose ps

#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost:8080}"
JAR="$(pwd)/.smoke-cookies.txt"
trap 'rm -f "$JAR"' EXIT

check() {
    local name="$1"
    local url="$2"
    local expected="$3"
    local code
    code="$(curl -sS -o /dev/null -w '%{http_code}' "$url")"
    if [ "$code" != "$expected" ]; then
        echo "LỖI: $name trả $code, mong đợi $expected"
        exit 1
    fi
    echo "ĐẠT: $name ($code)"
}

check "trang chủ" "$BASE_URL/" 200
check "đăng nhập" "$BASE_URL/login" 200
check "tìm kiếm" "$BASE_URL/search?q=an" 200
check "trạng thái" "$BASE_URL/health" 200
check "trang DOM" "$BASE_URL/demo/dom" 200

html="$(curl -sS -c "$JAR" "$BASE_URL/login")"
token="$(printf '%s' "$html" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)"
code="$(curl -sS -b "$JAR" -c "$JAR" -o /dev/null -w '%{http_code}' \
    --data-urlencode "username=admin" \
    --data-urlencode "password=Admin@Lab#2026" \
    --data-urlencode "_csrf=$token" \
    "$BASE_URL/login")"

if [ "$code" != "302" ]; then
    echo "LỖI: đăng nhập quản trị trả $code"
    exit 1
fi

code="$(curl -sS -b "$JAR" -o /dev/null -w '%{http_code}' "$BASE_URL/admin")"
if [ "$code" != "200" ]; then
    echo "LỖI: trang quản trị trả $code, mong đợi 200"
    exit 1
fi
echo "ĐẠT: trang quản trị ($code)"
echo "Hoàn tất kiểm tra nhanh."

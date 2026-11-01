#!/bin/sh
set -eu

mkdir -p /var/www/html/storage/logs /var/www/html/storage/uploads /var/www/html/storage/private /var/www/html/storage/sessions
chown -R www-data:www-data /var/www/html/storage

if [ ! -f /var/www/html/storage/uploads/huong-dan-lab.txt ]; then
    cat > /var/www/html/storage/uploads/huong-dan-lab.txt <<'EOF'
TÀI LIỆU HƯỚNG DẪN LAB

Đây là tệp công khai dùng cho kịch bản tải tệp.
Tệp chỉ chứa nội dung giả lập, không chứa bí mật thật.
EOF
fi

if [ ! -f /var/www/html/storage/private/demo-secret.txt ]; then
    cat > /var/www/html/storage/private/demo-secret.txt <<'EOF'
NOI_DUNG_NOI_BO=chi-dung-cho-kiem-thu-duong-dan
GHI_CHU=tep-moi-khong-duoc-phep-truy-cap-tu-ben-ngoai
EOF
fi

chown -R www-data:www-data /var/www/html/storage

i=0
while [ "$i" -lt 30 ]; do
    if php -r '
        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", getenv("DB_HOST"), getenv("DB_PORT"), getenv("DB_NAME"));
        new PDO($dsn, getenv("DB_USER"), getenv("DB_PASSWORD"), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    ' >/dev/null 2>&1; then
        break
    fi
    i=$((i + 1))
    sleep 1
done

exec "$@"

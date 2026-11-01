# Mục 5. Quản lý cấu hình sai

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 5. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Bí mật lộ trong repo, endpoint hoặc log có thể cho phép truy cập trái phép.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** Rà tệp bị ignore, endpoint cấu hình, header phản hồi và phạm vi mạng của dịch vụ.

**Dữ liệu/phạm vi thử:** Rà `.env`, quy tắc ignore, endpoint web và header phản hồi; không đưa bí mật thật vào phép thử.

**Công cụ:** Git, `curl`, kiểm tra cấu hình Apache/Docker.

**Mã nguồn và cấu hình liên quan:** [`.gitignore`](../../../../.gitignore), [`.dockerignore`](../../../../.dockerignore), [`docker/php/apache-vhost.conf`](../../../../docker/php/apache-vhost.conf), [`.env.example`](../../../../.env.example).

## Cách kiểm tra an toàn

```bash
git ls-files | grep -E '(^|/)\.env$' || echo "Không có .env trong Git"
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost:8080/.env
curl -sS -I http://localhost:8080/ | grep -i 'x-powered-by' || true
```

Kiểm tra cấu hình máy chủ:

```bash
bash scripts/mysql-root.sh -e "SHOW VARIABLES LIKE 'general_log';"
bash scripts/mysql-root.sh -e "SHOW VARIABLES LIKE 'log_error';"
```

## Kết quả

```text
Không có .env trong Git
404
```

Không có header `X-Powered-By`. `display_errors` và `expose_php` bị tắt trong `docker/php/php.ini`; lỗi được ghi vào `storage/logs/php-error.log`, ngoài thư mục công khai. `.env` bị loại khỏi Git và Docker context; `.env.example` chỉ chứa giá trị mẫu.

Cổng cơ sở dữ liệu chỉ gắn với `127.0.0.1` trong `docker-compose.yml`. Tài khoản ứng dụng không phải `root`.

## Biện pháp phòng thủ

- Đưa bí mật ra khỏi mã nguồn, dùng biến môi trường hoặc kho bí mật.
- Không commit `.env`, khóa riêng, nhật ký hoặc dữ liệu người dùng.
- Đặt tài liệu và mã nguồn ngoài thư mục gốc web.
- Che bí mật trong nhật ký; không ghi mật khẩu, mã đặt lại hay cookie.
- Giới hạn phạm vi mạng của dịch vụ cơ sở dữ liệu.
- Trong triển khai thật, dùng Docker secret hoặc dịch vụ quản lý bí mật thay vì biến môi trường.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

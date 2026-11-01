# Hướng dẫn vận hành

## 1. Kiểm tra môi trường

```bash
docker --version
docker compose version
```

Ba cổng mặc định phải còn trống:

- `8080`: ứng dụng blog.
- `8088`: trang mô phỏng CSRF.
- `3307`: cơ sở dữ liệu, chỉ gắn với loopback.

Kiểm tra cổng trên Windows:

```powershell
Get-NetTCPConnection -LocalPort 8080,8088,3307 -ErrorAction SilentlyContinue
```

## 2. Chuẩn bị cấu hình

```bash
cp .env.example .env
```

Mở `.env` và thay:

```dotenv
APP_MODE=fixed
APP_URL=http://localhost:8080
DB_NAME=atud_blog
DB_USER=atud_app
DB_PASSWORD=<mật-khẩu-mạnh-riêng>
MYSQL_ROOT_PASSWORD=<mật-khẩu-quản-trị-riêng>
APP_PORT=8080
ATTACKER_PORT=8088
DB_PORT=3307
```

Không commit `.env`. Tệp này đã nằm trong `.gitignore` và `.dockerignore`.

Nếu đổi `APP_PORT`, cập nhật `APP_URL` tương ứng để liên kết đặt lại mật khẩu trong nhật ký trỏ đúng địa chỉ.

## 3. Khởi động

```bash
docker compose up -d --build
docker compose ps
docker compose logs --tail=50 app
docker compose logs --tail=50 db
```

Kiểm tra:

```bash
curl http://localhost:8080/health
```

Kết quả hợp lệ:

```json
{"status":"ok","mode":"fixed","database":"ok"}
```

Mở `http://localhost:8080/` và `http://localhost:8088/`.

## 4. Dừng và khởi động lại

```bash
docker compose stop
docker compose start
```

Tạo lại riêng dịch vụ ứng dụng sau khi đổi mã nguồn hoặc `APP_MODE`:

```bash
docker compose up -d --force-recreate app
```

Xóa toàn bộ container và dữ liệu:

```bash
docker compose down -v
```

## 5. Chuyển cấu hình

Sửa `.env`:

```dotenv
APP_MODE=vulnerable
```

```bash
docker compose up -d --force-recreate app
curl http://localhost:8080/health
```

Quay lại:

```dotenv
APP_MODE=fixed
```

```bash
docker compose up -d --force-recreate app
```

Không cần đặt lại cơ sở dữ liệu khi chuyển cấu hình. Tuy nhiên, dữ liệu phát sinh từ kịch bản trước vẫn còn; nên đặt lại trước buổi trình diễn.

## 6. Tài khoản

| Tên | Mật khẩu | Vai trò |
|---|---|---|
| `admin` | `Admin@Lab#2026` | Quản trị viên |
| `bien` | `Bien@Lab#2026` | Tác giả |
| `minh` | `Minh@Lab#2026` | Tác giả |
| `ngoc` | `Ngoc@Lab#2026` | Tác giả |
| `lan` | `Lan@Lab#2026` | Độc giả |

Trong shell, đặt mật khẩu trong dấu nháy đơn:

```bash
curl --data-urlencode 'password=Bien@Lab#2026' ...
```

## 7. Đặt lại dữ liệu

Cách đầy đủ:

```bash
docker compose down -v
docker compose up -d --build
```

Cách này xóa volume `db_data`, nạp lại schema và dữ liệu mẫu, đồng thời xóa các tệp đã tải lên nếu volume được tạo lại. Thư mục `app/storage` là bind mount từ máy chủ; muốn xóa tệp tải lên thủ công:

```bash
rm -f app/storage/uploads/*
```

Không xóa hai tệp mẫu:

```text
app/storage/uploads/huong-dan-lab.txt
app/storage/uploads/anh-mau.png
```

Đặt lại nhanh bộ đếm đăng nhập:

```bash
bash scripts/mysql-app.sh -e "DELETE FROM login_attempts;"
```

Đưa bài viết `id=5` về nháp:

```bash
bash scripts/mysql-app.sh -e "UPDATE posts SET status='draft' WHERE id=5;"
```

## 8. Truy cập cơ sở dữ liệu

Bằng tài khoản ứng dụng:

```bash
bash scripts/mysql-app.sh -e "SELECT id, username, role FROM users;"
```

Bằng tài khoản quản trị trong container:

```bash
bash scripts/mysql-root.sh -e "SHOW GRANTS FOR 'atud_app'@'%';"
```

Không dùng `root` để cấu hình ứng dụng. Không mở cổng `3307` ra ngoài loopback.

## 9. Nhật ký

```bash
docker compose logs -f app
docker compose logs -f db
```

Nhật ký ứng dụng:

```bash
docker compose exec -T app tail -n 100 storage/logs/php-error.log
docker compose exec -T app tail -n 20 storage/logs/mail.log
```

Nhật ký Apache:

```bash
docker compose exec -T app tail -n 50 storage/logs/apache-access.log
docker compose exec -T app tail -n 50 storage/logs/apache-error.log
```

Không ghi mật khẩu, mã đặt lại hoặc cookie vào nhật ký. Nếu cần điều tra, che dữ liệu nhạy cảm trước khi chia sẻ.

## 10. Sự cố thường gặp

### Cổng đã được sử dụng

Đổi `APP_PORT`, `ATTACKER_PORT` hoặc `DB_PORT` trong `.env`, sau đó:

```bash
docker compose up -d --force-recreate
```

Nếu đổi `APP_PORT`, cập nhật `APP_URL` và địa chỉ trong `attacker/index.html` cho khớp.

### Dịch vụ db không khỏe

```bash
docker compose logs db
docker compose ps
```

Nguyên nhân thường gặp: mật khẩu trong `.env` thay đổi sau khi volume đã được tạo. MySQL chỉ dùng mật khẩu khởi tạo ở lần đầu. Khi đổi mật khẩu, phải đặt lại volume:

```bash
docker compose down -v
docker compose up -d --build
```

### Ứng dụng trả 500

```bash
docker compose exec -T app tail -n 50 storage/logs/php-error.log
```

Kiểm tra `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` và trạng thái `db`.

### Không đăng nhập được sau khi thử vét cạn

Cơ chế giới hạn khóa theo tên đăng nhập và địa chỉ nguồn trong 15 phút. Đặt lại:

```bash
bash scripts/mysql-app.sh -e "DELETE FROM login_attempts;"
```

### Không thấy liên kết đặt lại ở cấu hình đã vá

Đây là hành vi đúng: liên kết không hiển thị trên trang. Xem:

```bash
docker compose exec -T app tail -n 1 storage/logs/mail.log
```

### Tệp tải lên không đọc được

Kiểm tra quyền thư mục:

```bash
docker compose exec -T app ls -la storage/uploads
```

Trên Linux, thư mục cần ghi được bởi `www-data`. Entrypoint đã đặt quyền; nếu bind mount che mất quyền, chạy:

```bash
docker compose exec -T app chown -R www-data:www-data storage
```

### Dòng kết thúc tệp trên Windows

Các tệp `.sh` phải dùng ký tự xuống dòng LF. Nếu container báo `exec format error`, kiểm tra:

```bash
file docker/php/entrypoint.sh
```

Chuyển sang LF bằng editor hoặc:

```bash
sed -i 's/\r$//' docker/php/entrypoint.sh scripts/*.sh
```

## 11. Sao lưu và di chuyển

Sao lưu dữ liệu:

```bash
docker compose exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --databases "$DB_NAME"' > backup.sql
```

Chỉ dùng bản sao lưu cho lab. Không đưa tệp sao lưu lên kho mã nguồn công khai.

Phục hồi:

```bash
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$DB_NAME"' < backup.sql
```

## 12. Cập nhật thành phần

```bash
docker compose pull
docker compose up -d --build
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SELECT VERSION();"'
```

Sau cập nhật, chạy lại bộ kiểm chứng và kiểm tra các kịch bản chính. Ghi lại phiên bản trước và sau trong biên bản của nhóm.

## 13. Nguyên tắc an toàn

- Không chạy `APP_MODE=vulnerable` trên Internet.
- Không mở cổng ra ngoài `127.0.0.1`.
- Không dùng mật khẩu lab cho hệ thống khác.
- Không đưa dữ liệu thật vào ứng dụng.
- Đặt lại dữ liệu sau mỗi buổi trình diễn.
- Không chạy công cụ quét hoặc khai thác ngoài phạm vi ứng dụng của nhóm.

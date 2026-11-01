# Mười lỗ hổng cơ sở dữ liệu và biện pháp phòng chống

Tài liệu đối chiếu mười mục trong phần Top 10 lỗ hổng cơ sở dữ liệu của giáo trình với cách kiểm tra, kết quả quan sát và biện pháp áp dụng trong lab. Các thao tác chỉ chạy trên cơ sở dữ liệu của đồ án.

## 1. Quy ước

Các lệnh dùng hai trình bao bọc:

```bash
bash scripts/mysql-app.sh -e "SELECT ...;"
bash scripts/mysql-root.sh -e "SELECT ...;"
```

`mysql-app.sh` kết nối bằng tài khoản ứng dụng `atud_app`; `mysql-root.sh` kết nối bằng tài khoản quản trị trong container. Mật khẩu được đọc từ biến môi trường của container, không ghi trong mã nguồn.

Khi cần chạy trực tiếp trên máy kiểm chứng, thay lệnh bằng:

```bash
mysql -h 127.0.0.1 -P 3307 -u atud_app -p"$DB_PASSWORD" atud_blog -e "SELECT ...;"
```

## 2. D1 - Default and Weak Passwords

### Cách kiểm tra

```bash
bash scripts/mysql-root.sh -e "SELECT user, host, plugin FROM mysql.user ORDER BY user, host;"
bash scripts/mysql-root.sh -e "SELECT COUNT(*) AS anonymous_users FROM mysql.user WHERE user = '';"
bash scripts/mysql-root.sh -e "SELECT COUNT(*) AS legacy_plugin_accounts FROM mysql.user WHERE plugin = 'mysql_native_password';"
```

Thử đăng nhập bằng mật khẩu yếu:

```bash
docker compose exec -T db sh -c 'mysql -u"$DB_USER" -p"matkhauyeu" -e "SELECT 1;"'
echo $?
```

### Kết quả quan sát

```text
user        host         plugin
atud_app    %            caching_sha2_password
root        localhost    caching_sha2_password
...
anonymous_users
0
legacy_plugin_accounts
0
```

Lần đăng nhập bằng `matkhauyeu`:

```text
ERROR 1045 (28000): Access denied for user 'atud_app'@'127.0.0.1' (using password: YES)
exit=1
```

### Biện pháp

- Mật khẩu quản trị và mật khẩu ứng dụng được sinh riêng, lưu trong `.env` không được commit.
- Không tạo tài khoản ẩn danh; không dùng tài khoản `root` cho ứng dụng.
- Tài khoản ứng dụng dùng `caching_sha2_password`.
- Mật khẩu của người dùng blog được băm bằng bcrypt, không lưu dạng rõ.
- Khi chuyển sang môi trường thật, thay toàn bộ mật khẩu lab và dùng kho bí mật của hạ tầng.

## 3. D2 - SQL Injection in the DBMS

### Cách kiểm tra

Truy vấn ghép chuỗi mô phỏng lỗi của ứng dụng:

```bash
bash scripts/mysql-app.sh -e "SELECT id, username, role FROM users WHERE username = '' OR '1'='1' -- ' AND password_legacy = MD5('x') LIMIT 5;"
```

Truy vấn tham số hóa tương đương:

```bash
bash scripts/mysql-app.sh -e "
PREPARE stmt FROM 'SELECT id, username, role FROM users WHERE username = ? LIMIT 5';
SET @u = ''' OR ''1''=''1'' -- ';
EXECUTE stmt USING @u;
DEALLOCATE PREPARE stmt;"
```

### Kết quả quan sát

Truy vấn ghép chuỗi trả về toàn bộ tài khoản:

```text
id  username  role
1   admin     admin
2   bien      user
3   minh      user
4   ngoc      user
```

Truy vấn tham số hóa không trả về bản ghi nào, vì toàn bộ chuỗi được so sánh như giá trị của cột `username`. Ứng dụng ở cấu hình đã vá dùng PDO với `ATTR_EMULATE_PREPARES=false`, nên tham số được truyền riêng khỏi câu lệnh.

Kịch bản W5 trong [kich-ban-web.md](kich-ban-web.md) trình bày cùng lỗi ở tầng ứng dụng: cấu hình có lỗi đăng nhập được vào `/admin`, cấu hình đã vá từ chối.

### Biện pháp

- Không ghép chuỗi đầu vào vào SQL.
- Dùng prepared statement cho mọi câu lệnh có tham số.
- Kiểm tra kiểu dữ liệu và độ dài trước khi truy vấn.
- Không hiển thị câu lệnh SQL hoặc lỗi cơ sở dữ liệu cho người dùng ở cấu hình đã vá.

## 4. D3 - Excessive User & Group Privileges

### Cách kiểm tra

```bash
bash scripts/mysql-app.sh -e "SHOW GRANTS FOR CURRENT_USER();"
bash scripts/mysql-app.sh -e "SHOW DATABASES;"
```

Thử các thao tác ngoài quyền:

```bash
bash scripts/mysql-app.sh -e "CREATE DATABASE lab_privilege_test;" || true
bash scripts/mysql-app.sh -e "CREATE TABLE lab_privilege_test (id INT);" || true
bash scripts/mysql-app.sh -e "DROP TABLE users;" || true
bash scripts/mysql-app.sh -e "SELECT * FROM mysql.user;" || true
bash scripts/mysql-app.sh -e "SET GLOBAL max_connections = 500;" || true
```

### Kết quả quan sát

```text
Grants for atud_app@%
GRANT USAGE ON *.* TO `atud_app`@`%`
GRANT SELECT, INSERT, UPDATE, DELETE ON `atud_blog`.* TO `atud_app`@`%`
```

Các thao tác quản trị bị từ chối:

```text
ERROR 1044 (42000): Access denied for user 'atud_app'@'%' to database 'lab_privilege_test'
ERROR 1142 (42000): CREATE command denied to user 'atud_app'@'127.0.0.1' for table 'lab_privilege_test'
ERROR 1142 (42000): DROP command denied to user 'atud_app'@'127.0.0.1' for table 'users'
ERROR 1142 (42000): SELECT command denied to user 'atud_app'@'127.0.0.1' for table 'user'
ERROR 1227 (42000): Access denied; you need (at least one of) the SUPER or SYSTEM_VARIABLES_ADMIN privilege(s) for this operation
```

Tài khoản ứng dụng chỉ có quyền đọc và ghi dữ liệu trên đúng lược đồ `atud_blog`. Không có quyền tạo, xóa, thay đổi cấu trúc, cấp quyền hoặc đọc bảng hệ thống.

### Biện pháp

- Cấp quyền tối thiểu theo lược đồ và theo thao tác.
- Tách tài khoản quản trị và tài khoản ứng dụng.
- Không cấp `FILE`, `PROCESS`, `SUPER`, `GRANT OPTION` cho ứng dụng.
- Rà soát `SHOW GRANTS` định kỳ và sau mỗi lần thay đổi tính năng.

## 5. D4 - Unnecessary Enabled DBMS Features

### Cách kiểm tra

```bash
bash scripts/mysql-root.sh -e "SHOW VARIABLES LIKE 'local_infile';"
bash scripts/mysql-root.sh -e "SHOW VARIABLES LIKE 'mysqlx%';"
bash scripts/mysql-root.sh -e "SHOW VARIABLES LIKE 'secure_file_priv';"
bash scripts/mysql-root.sh -e "SHOW PLUGINS;"
```

### Kết quả quan sát

```text
local_infile    OFF
```

Truy vấn `mysqlx%` không trả về dòng nào, tương ứng với việc MySQL X bị tắt trong `docker/mysql/conf.d/lab.cnf` bằng `mysqlx=0`. Danh sách plugin chỉ gồm các thành phần cần cho lab; không bật plugin thử nghiệm.

### Biện pháp

- Tắt `local_infile`.
- Tắt MySQL X nếu ứng dụng không dùng giao thức X.
- Không cấp quyền `FILE`, không dùng `LOAD DATA LOCAL`.
- Loại bỏ cơ sở dữ liệu mẫu và tài khoản không dùng.
- Ghi lại cấu hình sau khi thay đổi và kiểm tra lại bằng `SHOW VARIABLES`.

## 6. D5 - Broken Configuration Management

### Cách kiểm tra

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

### Kết quả quan sát

```text
Không có .env trong Git
404
```

Không có header `X-Powered-By`. `display_errors` và `expose_php` bị tắt trong `docker/php/php.ini`; lỗi được ghi vào `storage/logs/php-error.log`, ngoài thư mục công khai. `.env` bị loại khỏi Git và Docker context; `.env.example` chỉ chứa giá trị mẫu.

Cổng cơ sở dữ liệu chỉ gắn với `127.0.0.1` trong `docker-compose.yml`. Tài khoản ứng dụng không phải `root`.

### Biện pháp

- Đưa bí mật ra khỏi mã nguồn, dùng biến môi trường hoặc kho bí mật.
- Không commit `.env`, khóa riêng, nhật ký hoặc dữ liệu người dùng.
- Đặt tài liệu và mã nguồn ngoài thư mục gốc web.
- Che bí mật trong nhật ký; không ghi mật khẩu, mã đặt lại hay cookie.
- Giới hạn phạm vi mạng của dịch vụ cơ sở dữ liệu.
- Trong triển khai thật, dùng Docker secret hoặc dịch vụ quản lý bí mật thay vì biến môi trường.

## 7. D6 - Buffer Overflows

### Cách kiểm tra

Không chạy khai thác tràn bộ đệm trên MySQL. Lab kiểm tra biên đầu vào của ứng dụng và ghi nhận cấu hình runtime:

```bash
LONG="$(printf 'a%.0s' $(seq 1 5000))"
curl -sS -G --data-urlencode "q=$LONG" http://localhost:8080/search -o /dev/null -w '%{http_code}\n'
```

```bash
bash scripts/mysql-root.sh -e "SELECT VERSION(); SHOW VARIABLES LIKE 'max_allowed_packet'; SHOW VARIABLES LIKE 'sql_mode';"
```

### Kết quả quan sát

```text
400
```

Cấu hình có lỗi trả về `200` với cùng đầu vào. Cấu hình đã vá giới hạn từ khóa ở 100 ký tự. MySQL nhận `max_allowed_packet` 16 MB; ứng dụng giới hạn `post_max_size` 3 MB và `upload_max_filesize` 2 MB.

### Biện pháp

- Kiểm tra độ dài và kiểu dữ liệu ở biên.
- Dùng API an toàn, không tự quản lý bộ đệm trong mã ứng dụng.
- Dùng phiên bản DBMS còn hỗ trợ và cập nhật bản vá.
- Giới hạn kích thước gói, kích thước tệp và thời gian xử lý.
- Ghi rõ trong báo cáo: nhóm không khai thác tràn bộ đệm; bằng chứng là biên đầu vào bị từ chối và cấu hình runtime.

## 8. D7 - Privilege Escalation

### Cách kiểm tra ở tầng ứng dụng

Đăng nhập `bien`, sau đó gọi trang quản trị:

```bash
html="$(curl -sS -c "$JAR" http://localhost:8080/login)"
token="$(printf '%s' "$html" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)"
curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "username=bien" \
  --data-urlencode "password=Bien@Lab#2026" \
  --data-urlencode "_csrf=$token" \
  -o /dev/null http://localhost:8080/login
curl -sS -b "$JAR" -o /dev/null -w '%{http_code}\n' http://localhost:8080/admin
```

### Cách kiểm tra ở tầng cơ sở dữ liệu

```bash
bash scripts/mysql-app.sh -e "CREATE USER 'lab_escalation'@'%' IDENTIFIED BY 'Lab@2026Test';" || true
bash scripts/mysql-app.sh -e "GRANT ALL PRIVILEGES ON *.* TO 'atud_app'@'%';" || true
```

### Kết quả quan sát

```text
403
```

Tài khoản người dùng thường không vào được trang quản trị. Tài khoản DB ứng dụng không tạo được người dùng và không tự cấp quyền:

```text
ERROR 1227 (42000): Access denied; you need (at least one of) the CREATE USER privilege(s)
ERROR 1045 (28000): Access denied for user 'atud_app'@'%'
```

### Biện pháp

- Kiểm tra vai trò ở máy chủ cho từng yêu cầu, không dựa vào giao diện.
- Kiểm tra chủ sở hữu tài nguyên và trạng thái nghiệp vụ.
- Phân quyền tối thiểu ở DB; tách tài khoản quản trị.
- Ghi nhật ký các lần từ chối và rà soát định kỳ.

## 9. D8 - Denial of Service

### Cách kiểm tra

Đo một truy vấn ngủ có kiểm soát:

```bash
time bash scripts/mysql-app.sh -e "SELECT SLEEP(3) AS slow_query;"
```

Kiểm tra giới hạn:

```bash
bash scripts/mysql-root.sh -e "SELECT @@max_execution_time AS max_execution_time_ms, @@max_connections AS max_connections;"
```

### Kết quả quan sát

```text
slow_query
1

real    0m2.048s
```

`SLEEP(3)` bị ngắt sau khoảng 2 giây; giá trị `1` cho biết truy vấn bị dừng bởi `max_execution_time=2000`. `max_connections` là 40. Ứng dụng giới hạn số bài viết mỗi trang, giới hạn từ khóa và giới hạn số lần đăng nhập.

### Biện pháp

- Đặt `max_execution_time` cho truy vấn đọc.
- Giới hạn `max_connections`, kích thước kết quả và thời gian xử lý yêu cầu.
- Phân trang và giới hạn đầu vào.
- Giới hạn tần suất đăng nhập và thao tác tốn kém.
- Không chạy thử nghiệm tải kéo dài hoặc flood trong lab.

## 10. D9 - Unpatched Database

### Cách kiểm tra

```bash
bash scripts/mysql-root.sh -e "SELECT VERSION() AS version, @@version_comment AS comment;"
docker compose images db
```

### Kết quả quan sát

```text
version   comment
8.4.6     MySQL Community Server - GPL
```

Image cơ sở dữ liệu dùng thẻ `mysql:lts`, nhánh hỗ trợ dài hạn. Phiên bản trong lần kiểm chứng là `8.4.6`. Nhóm không khai thác lỗi của phiên bản cũ.

### Biện pháp

- Dùng image LTS từ nguồn chính thức.
- Có quy trình cập nhật:

```bash
docker compose pull db
docker compose up -d db
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SELECT VERSION();"'
```

- Ghi lại phiên bản trước và sau khi cập nhật.
- Chạy lại bộ kiểm chứng chức năng sau khi cập nhật.
- Theo dõi thông báo bảo mật của nhà cung cấp.

## 11. D10 - Unencrypted Data

### Cách kiểm tra

```bash
bash scripts/mysql-app.sh -e "SELECT username, LEFT(password_hash, 7) AS hash_prefix, LENGTH(password_hash) AS hash_length, password_legacy FROM users ORDER BY id;"
bash scripts/mysql-app.sh -e "SELECT id, user_id, LEFT(token_hash, 16) AS token_prefix, LENGTH(token_hash) AS token_length, expires_at, used_at FROM password_reset_tokens ORDER BY id DESC LIMIT 3;"
```

### Kết quả quan sát

```text
username  hash_prefix  hash_length  password_legacy
admin     $2b$12$      60           45a1878eca685306b8f0020de07eafea
bien      $2b$12$      60           1695899fc0f66161045ae0050a05df13
minh      $2b$12$      60           bfb9aef75959ca36ca54985ac8dc3498
ngoc      $2b$12$      60           6abb86f880f1387b956788571111927c

id  user_id  token_prefix      token_length  expires_at           used_at
1   3        d29f5b8d2e4f7f65  64            2026-11-01 21:11:56  NULL
```

Mật khẩu được băm bằng bcrypt với tiền tố `$2b$12$` và độ dài 60. Mã đặt lại được lưu dưới dạng băm SHA-256, độ dài 64, không lưu mã rõ. Email vẫn ở dạng rõ vì là dữ liệu giả lập phục vụ tra cứu; trong triển khai thật cần đánh giá mã hóa trường này và mã hóa bản sao lưu.

### Biện pháp

- Băm mật khẩu bằng thuật toán dành cho mật khẩu, không dùng MD5 hoặc SHA-1.
- Băm mã đặt lại trước khi lưu; không ghi mã vào nhật ký.
- Không lưu mật khẩu, cookie hoặc mã đặt lại trong nhật ký.
- Mã hóa bản sao lưu và phương tiện lưu trữ; kiểm soát quyền truy cập tệp dữ liệu.
- Dùng TLS khi kết nối qua mạng; lab cục bộ chưa cấu hình TLS.

### Ghi chú về cột `password_legacy`

Cột này chứa băm MD5 của mật khẩu và tồn tại có chủ đích để tái hiện kịch bản SQL Injection ở cấu hình có lỗi. Cấu hình đã vá không dùng cột này để xác thực; đăng nhập dùng `password_hash` và `password_verify`. Đây là nợ kỹ thuật của lab, không phải mô hình dữ liệu nên dùng khi triển khai thật. Sau khi hoàn tất minh họa, cần xóa cột và chuyển toàn bộ tài khoản sang bcrypt.

## 12. Bảng đối chiếu

| Mã | Mục giáo trình | Bằng chứng chính | Mức đáp ứng |
|---|---|---|---|
| D1 | Default and Weak Passwords | Mật khẩu yếu bị từ chối; không có tài khoản ẩn danh | Đã kiểm chứng |
| D2 | SQL Injection in the DBMS | Ghép chuỗi trả về 4 dòng; tham số hóa trả về 0 dòng | Đã kiểm chứng |
| D3 | Excessive User & Group Privileges | `SHOW GRANTS`; các thao tác quản trị bị từ chối | Đã kiểm chứng |
| D4 | Unnecessary Enabled DBMS Features | `local_infile=OFF`, MySQL X tắt | Đã kiểm chứng |
| D5 | Broken Configuration Management | `.env` ngoài Git; `/.env` trả 404; không lộ `X-Powered-By` | Đã kiểm chứng |
| D6 | Buffer Overflows | Đầu vào 5000 ký tự bị từ chối; runtime được vá | Kiểm tra biên, không khai thác |
| D7 | Privilege Escalation | Người dùng thường nhận 403; DB từ chối cấp quyền | Đã kiểm chứng |
| D8 | Denial of Service | Truy vấn 3 giây bị ngắt sau khoảng 2 giây | Kiểm tra có kiểm soát |
| D9 | Unpatched Database | Image LTS, phiên bản được ghi nhận | Đã kiểm chứng cấu hình |
| D10 | Unencrypted Data | Bcrypt cho mật khẩu; SHA-256 cho mã đặt lại | Đã kiểm chứng, còn nợ kỹ thuật `password_legacy` |

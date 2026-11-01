# Nhật ký kiểm chứng mười mục cơ sở dữ liệu

## 1. Thông tin môi trường

- Hệ quản trị: MySQL LTS, phiên bản ghi nhận `8.4.6`.
- Cấu hình: `docker/mysql/conf.d/lab.cnf`.
- Ứng dụng kết nối bằng `atud_app`, không dùng `root`.
- Dữ liệu: `atud_blog`, bốn tài khoản giả lập.
- Thời điểm: 01/11/2026.

## 2. Tài khoản và quyền

```text
user        host         plugin
atud_app    %            caching_sha2_password
root        localhost    caching_sha2_password
anonymous_users
0
```

```text
Grants for atud_app@%
GRANT USAGE ON *.* TO `atud_app`@`%`
GRANT SELECT, INSERT, UPDATE, DELETE ON `atud_blog`.* TO `atud_app`@`%`
```

Thử mật khẩu yếu:

```text
ERROR 1045 (28000): Access denied for user 'atud_app'@'127.0.0.1' (using password: YES)
```

## 3. Chèn mã SQL

Truy vấn ghép chuỗi:

```text
id  username  role
1   admin     admin
2   bien      user
3   minh      user
4   ngoc      user
```

Truy vấn tham số hóa với cùng đầu vào:

```text
(Không có bản ghi)
```

## 4. Quyền bị từ chối

```text
ERROR 1044 (42000): Access denied for user 'atud_app'@'%' to database 'lab_privilege_test'
ERROR 1142 (42000): CREATE command denied to user 'atud_app'@'127.0.0.1' for table 'lab_privilege_test'
ERROR 1142 (42000): DROP command denied to user 'atud_app'@'127.0.0.1' for table 'users'
ERROR 1142 (42000): SELECT command denied to user 'atud_app'@'127.0.0.1' for table 'user'
ERROR 1227 (42000): Access denied; you need (at least one of) the SUPER or SYSTEM_VARIABLES_ADMIN privilege(s) for this operation
ERROR 1227 (42000): Access denied; you need (at least one of) the CREATE USER privilege(s) for this operation
```

## 5. Cấu hình

```text
local_infile            OFF
max_connections         40
max_execution_time      2000
max_allowed_packet      16777216
general_log             OFF
sql_mode                STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
```

`SHOW VARIABLES LIKE 'mysqlx%'` không trả về dòng nào. `SHOW PLUGINS` không có plugin MySQL X ở trạng thái hoạt động.

## 6. Giới hạn truy vấn

```text
max_execution_time_ms  max_allowed_packet_bytes  max_connections
2000                   16777216                  40
```

Đo truy vấn ngủ:

```text
slow_query
1
thời gian thực tế: 2048 ms
```

Giá trị `1` cho biết truy vấn bị ngắt bởi giới hạn thời gian, không hoàn tất sau 3 giây.

## 7. Lưu trữ dữ liệu nhạy cảm

```text
username  hash_prefix  hash_length  password_legacy
admin     $2b$12$      60           45a1878eca685306b8f0020de07eafea
bien      $2b$12$      60           1695899fc0f66161045ae0050a05df13
minh      $2b$12$      60           bfb9aef75959ca36ca54985ac8dc3498
ngoc      $2b$12$      60           6abb86f880f1387b956788571111927c

id  user_id  token_prefix      token_length  expires_at           used_at
1   3        d29f5b8d2e4f7f65  64            2026-11-01 21:11:56  NULL
```

## 8. Ghi chú

- Cột `password_legacy` là thành phần có chủ đích của lab, phục vụ kịch bản SQL Injection; cấu hình đã vá không dùng cột này để xác thực.
- Không chạy khai thác tràn bộ đệm hoặc từ chối dịch vụ. Phần D6 và D8 chỉ kiểm tra biên, giới hạn và cấu hình.
- Các giá trị mật khẩu trong nhật ký không được ghi lại; chỉ ghi mã trạng thái và thông báo lỗi.

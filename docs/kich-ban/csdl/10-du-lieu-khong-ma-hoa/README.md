# Mục 10. Dữ liệu không mã hóa

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 10. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Mật khẩu/token lưu dạng rõ sẽ bị lạm dụng nếu dữ liệu bị đọc trái phép.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** Kiểm tra tiền tố, độ dài của hash mật khẩu và token đặt lại; đối chiếu cách lưu trữ với yêu cầu bảo vệ dữ liệu nhạy cảm.

**Dữ liệu/phạm vi thử:** Tài khoản/token giả; kiểm tra loại và độ dài hash.

**Công cụ:** MySQL CLI và rà mã băm trong ứng dụng.

**Mã nguồn và cấu hình liên quan:** [`docker/mysql/init/01-schema.sql`](../../../../docker/mysql/init/01-schema.sql), [`app/src/Controllers/AuthController.php`](../../../../app/src/Controllers/AuthController.php).

## Cách kiểm tra an toàn

```bash
bash scripts/mysql-app.sh -e "SELECT username, LEFT(password_hash, 7) AS hash_prefix, LENGTH(password_hash) AS hash_length, password_legacy FROM users ORDER BY id;"
bash scripts/mysql-app.sh -e "SELECT id, user_id, LEFT(token_hash, 16) AS token_prefix, LENGTH(token_hash) AS token_length, expires_at, used_at FROM password_reset_tokens ORDER BY id DESC LIMIT 3;"
```

## Kết quả

```text
username  hash_prefix  hash_length  password_legacy
admin     $2b$12$      60           45a1878eca685306b8f0020de07eafea
bien      $2b$12$      60           1695899fc0f66161045ae0050a05df13
minh      $2b$12$      60           bfb9aef75959ca36ca54985ac8dc3498
ngoc      $2b$12$      60           6abb86f880f1387b956788571111927c
lan       $2b$12$      60           93a20562525dd2a30485ea543bf04f3e

id  user_id  token_prefix      token_length  expires_at           used_at
1   3        d29f5b8d2e4f7f65  64            2026-11-01 21:11:56  NULL
```

Mật khẩu được băm bằng bcrypt với tiền tố `$2b$12$` và độ dài 60. Mã đặt lại được lưu dưới dạng băm SHA-256, độ dài 64, không lưu mã rõ. Email vẫn ở dạng rõ vì là dữ liệu giả lập phục vụ tra cứu; trong triển khai thật cần đánh giá mã hóa trường này và mã hóa bản sao lưu.

## Biện pháp phòng thủ

- Băm mật khẩu bằng thuật toán dành cho mật khẩu, không dùng MD5 hoặc SHA-1.
- Băm mã đặt lại trước khi lưu; không ghi mã vào nhật ký.
- Không lưu mật khẩu, cookie hoặc mã đặt lại trong nhật ký.
- Mã hóa bản sao lưu và phương tiện lưu trữ; kiểm soát quyền truy cập tệp dữ liệu.
- Dùng TLS khi kết nối qua mạng; lab cục bộ chưa cấu hình TLS.

### Ghi chú về cột `password_legacy`

Cột này chứa băm MD5 của mật khẩu và tồn tại có chủ đích để tái hiện kịch bản SQL Injection ở cấu hình có lỗi. Cấu hình đã vá không dùng cột này để xác thực; đăng nhập dùng `password_hash` và `password_verify`. Đây là nợ kỹ thuật của lab, không phải mô hình dữ liệu nên dùng khi triển khai thật. Sau khi hoàn tất minh họa, cần xóa cột và chuyển toàn bộ tài khoản sang bcrypt.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

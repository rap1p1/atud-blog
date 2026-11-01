# Nhật ký kiểm chứng mười kịch bản web

## 1. Điều kiện chạy

- Ứng dụng PHP chạy tại `http://127.0.0.1:8080` với `APP_MODE=vulnerable`.
- Ứng dụng PHP chạy tại `http://127.0.0.1:8081` với `APP_MODE=fixed`.
- Cơ sở dữ liệu MySQL LTS tại `127.0.0.1:3307`, lược đồ `atud_blog`.
- Dữ liệu được đặt lại từ `docker/mysql/init/01-schema.sql` và `docker/mysql/init/02-data.sql` trước mỗi nhóm kiểm chứng.
- Mã trạng thái và nội dung dưới đây là kết quả quan sát từ lần chạy ngày 01/11/2026.

## 2. Bảng tổng hợp

| Mã | Bản có lỗi | Bản đã vá |
|---|---|---|
| W1 | Gửi bình luận `302`; trang chứa `<script>alert('stored-demo')</script>` | Gửi bình luận `302`; trang chứa `&lt;script&gt;...` |
| W2 | Trang kết quả chứa `<script>alert('reflected-demo')</script>` | Trang kết quả chứa chuỗi đã mã hóa |
| W3 | `dom-vulnerable.js` dùng `innerHTML` | `dom-fixed.js` dùng `textContent` |
| W4 | Đổi email không mã CSRF trả `302`; email thành `attacker@gocnho.example` | Trả `403`; email giữ nguyên |
| W5 | Đăng nhập SQLi trả `302` tới `/admin` | Trả `200`, thông báo sai thông tin |
| W6 | Năm lần sai đều `200`; mật khẩu đúng `302` | Năm lần sai `200`; mật khẩu đúng `429` |
| W7 | Không đổi mã phiên; tài khoản vẫn `200` sau đăng xuất | Mã phiên đổi; tài khoản cũ `302` |
| W8 | Mã 20 ký tự, dùng lại được | Mã 64 ký tự, dùng một lần |
| W9 | Tự xuất bản trả `302`; trạng thái `published` | Trả `403`; trạng thái `draft` |
| W10 | Đọc tệp riêng trả `200` | Trả `400`; tệp công khai vẫn `200` |

## 3. Trích xuất kết quả

### W1 - Stored XSS

Bản có lỗi:

```text
gửi bình luận: 302
... <script>alert('stored-demo')</script> ...
```

Bản đã vá:

```text
gửi bình luận: 302
... &lt;script&gt;alert(&#039;stored-demo&#039;)&lt;/script&gt; ...
```

### W2 - Reflected XSS

Bản có lỗi:

```text
... <h2 class="search-summary">Kết quả cho: <script>alert('reflected-demo')</script></h2> ...
```

Bản đã vá:

```text
... <h2 class="search-summary">Kết quả cho: &lt;script&gt;alert(&#039;reflected-demo&#039;)&lt;/script&gt;</h2> ...
```

### W3 - DOM-based XSS

Bản có lỗi:

```text
dom-vulnerable.js
output.innerHTML = '<p><strong>Giá trị nhận được:</strong> ' + value + '</p>';
```

Bản đã vá:

```text
dom-fixed.js
output.textContent = 'Giá trị nhận được: ' + value;
```

### W4 - CSRF

Bản có lỗi:

```text
gửi không mã CSRF: 302
value="attacker@gocnho.example"
```

Bản đã vá:

```text
gửi không mã CSRF: 403
value="bien@gocnho.example"
```

### W5 - SQL Injection

Bản có lỗi:

```text
HTTP/1.1 302 Found
Location: /admin
```

Bản đã vá:

```text
HTTP/1.1 200 OK
Tên đăng nhập hoặc mật khẩu không đúng.
```

### W6 - Vét cạn mật khẩu

Bản có lỗi:

```text
lần 1: 200
lần 2: 200
lần 3: 200
lần 4: 200
lần 5: 200
đăng nhập đúng: 302
```

Bản đã vá:

```text
lần 1: 200
lần 2: 200
lần 3: 200
lần 4: 200
lần 5: 200
đăng nhập đúng: 429
tạm khóa do quá nhiều lần đăng nhập thất bại. Thử lại sau 15 phút.
```

### W7 - Cố định và phát lại phiên

Bản có lỗi:

```text
Không đổi mã phiên sau đăng nhập
phát lại trước đăng xuất: 200
đăng xuất: 302
phát lại sau đăng xuất: 200
```

Bản đã vá:

```text
Set-Cookie: ATUDBLOG_SID=<mã mới>; path=/; HttpOnly; SameSite=Strict
phát lại mã cũ trước đăng xuất: 302
đăng xuất: 302
phát lại sau đăng xuất: 302
```

### W8 - Đặt lại mật khẩu

Bản có lỗi:

```text
Liên kết: /reset?token=18ddf59c073c2b049a9a
đặt lại lần một: 302
mở lại liên kết: 200
```

Bản đã vá:

```text
Liên kết được ghi trong storage/logs/mail.log
đặt lại lần một: 302
mở lại liên kết: 200
không hợp lệ, đã hết hạn hoặc đã được sử dụng.
```

### W9 - Lạm dụng quy trình

Bản có lỗi:

```text
gửi trạng thái xuất bản: 302
published
```

Bản đã vá:

```text
gửi trạng thái xuất bản: 403
Trạng thái không được phép
draft
```

### W10 - Duyệt đường dẫn

Bản có lỗi:

```text
yêu cầu duyệt: 200
NOI_DUNG_NOI_BO=chi-dung-cho-kiem-thu-duong-dan
GHI_CHU=tep-moi-khong-duoc-phep-truy-cap-tu-ben-ngoai
```

Bản đã vá:

```text
yêu cầu duyệt: 400
Chức năng tải tệp chỉ nhận mã tệp hợp lệ, không nhận đường dẫn.
tệp công khai: 200 (24853 byte)
```

## 4. Ghi chú

- Các mã trạng thái `302` là chuyển hướng hợp lệ sau khi xử lý thành công.
- Kịch bản W7 dùng cookie `ATUDBLOG_SID` cố định; cấu hình đã vá từ chối mã không do máy chủ tạo.
- Kịch bản W8 làm thay đổi mật khẩu của `ngoc`; cần đặt lại dữ liệu trước khi trình diễn tiếp.
- Kịch bản W9 làm thay đổi trạng thái bài viết `id=5`; cần đặt lại dữ liệu.
- Kịch bản W6 làm khóa địa chỉ lab trong 15 phút; cần đặt lại dữ liệu hoặc xóa `login_attempts`.

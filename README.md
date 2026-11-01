# ATUD Blog - môi trường thực hành an toàn ứng dụng web và cơ sở dữ liệu


## Bắt đầu nhanh

Yêu cầu: Docker Engine kèm Docker Compose; các cổng `8080`, `8088`, `3307` còn trống.

```bash
cp .env.example .env
# Mở .env và đổi DB_PASSWORD, MYSQL_ROOT_PASSWORD
docker compose up -d --build
curl http://localhost:8080/health
```

Mở `http://localhost:8080/` để xem blog và `http://localhost:8088/` cho trang mô phỏng CSRF. Tài khoản mẫu:

| Tên đăng nhập | Mật khẩu | Vai trò |
|---|---|---|
| `admin` | `Admin@Lab#2026` | Quản trị viên |
| `bien` | `Bien@Lab#2026` | Tác giả |
| `minh` | `Minh@Lab#2026` | Tác giả |
| `ngoc` | `Ngoc@Lab#2026` | Tác giả |
| `lan` | `Lan@Lab#2026` | Độc giả |

Trên Windows, thay `cp .env.example .env` bằng `Copy-Item .env.example .env`.

Hướng dẫn đầy đủ, cách chuyển cấu hình và đặt lại dữ liệu ở mục 7 đến mục 9.

## 1. Giới thiệu

Đây là mã nguồn của một blog nhỏ dùng PHP, Apache và MySQL LTS, phục vụ đồ án môn An toàn ứng dụng Web và Cơ sở dữ liệu. Ứng dụng có hai cấu hình chạy trên cùng một mã nguồn:

- `vulnerable`: chủ động vô hiệu hóa một số điểm kiểm soát để trình diễn mười kịch bản tấn công web.
- `fixed`: áp dụng các biện pháp phòng thủ tương ứng để chạy lại cùng kịch bản và đối chiếu kết quả.

Ứng dụng chỉ dùng dữ liệu giả lập. Không triển khai cấu hình `vulnerable` lên Internet. Các cổng trong Docker Compose chỉ gắn với địa chỉ loopback của máy chạy lab.

## 2. Phạm vi và cảnh báo

- Chỉ chạy trên `localhost` hoặc mạng lab do nhóm kiểm soát.
- Không dùng mật khẩu, email, tệp hoặc dữ liệu cá nhân thật.
- Không chạy các lệnh thử nghiệm trong tài liệu ra ngoài phạm vi ứng dụng của nhóm.
- Cấu hình `vulnerable` là cấu hình cố ý có lỗi, không phải cấu hình triển khai.
- Các kịch bản tràn bộ đệm và từ chối dịch vụ chỉ được kiểm tra bằng giới hạn đầu vào, cấu hình và phép đo có kiểm soát; không chạy khai thác phá hoại trên MySQL.

## 3. Kiến trúc

| Thành phần | Lựa chọn |
|---|---|
| Ngôn ngữ | PHP |
| Máy chủ web | Apache |
| Cơ sở dữ liệu | MySQL LTS |
| Kết nối | PDO MySQL, truy vấn tham số hóa ở cấu hình đã vá |
| Giao diện | HTML, CSS, JavaScript |
| Đóng gói | Docker Compose |
| Kiểm thử | curl, trình duyệt, công cụ dòng lệnh MySQL |

Luồng xử lý chính:

```text
Trình duyệt
    |
    v
Apache + PHP (public/index.php)
    |
    +-- Lớp kiểm soát: phiên, CSRF, phân quyền, kiểm tra đầu vào
    |
    +-- Lớp nghiệp vụ: bài viết, bình luận, tệp, đặt lại mật khẩu
    |
    v
MySQL LTS (schema atud_blog)
```

Mã nguồn nằm ngoài thư mục công khai. Tệp tải lên được lưu trong `app/storage/uploads`, không nằm trong `app/public`, nên không thể thực thi trực tiếp qua máy chủ web.

## 4. Chức năng

- Xem danh sách, chi tiết và tìm kiếm bài viết.
- Đăng ký, đăng nhập, đăng xuất.
- Bình luận bài viết.
- Đổi email, đổi mật khẩu, yêu cầu đặt lại mật khẩu.
- Tải lên, tải xuống và xem tệp mẫu.
- Khu vực tác giả: tạo, sửa, xóa bài viết; lưu nháp và gửi duyệt.
- Khu vực quản trị: duyệt bài, xuất bản, xóa bài viết và bình luận.
- Trang minh họa DOM để quan sát khác biệt giữa `innerHTML` và `textContent`.
- Điểm kiểm tra trạng thái `/health`.


![Giao diện trang chủ](docs/hinh/web/01-trang-chu.png)

![Trang quản trị](docs/hinh/web/03-quan-tri.png)

## 5. Cấu trúc thư mục

```text
.
├── app
│   ├── public              # thư mục gốc của máy chủ web
│   │   ├── assets          # CSS, JavaScript
│   │   ├── .htaccess
│   │   └── index.php       # bộ định tuyến
│   ├── src
│   │   ├── Controllers
│   │   ├── Core
│   │   └── helpers.php
│   ├── views
│   └── storage
│       ├── logs
│       ├── private         # tệp mồi cho kịch bản duyệt đường dẫn
│       ├── sessions
│       └── uploads         # tệp công khai và tệp riêng
├── attacker                # trang mô phỏng CSRF, chạy ở cổng riêng
├── docker
│   ├── mysql
│   └── php
├── docs
│   ├── bang-chung
│   ├── hinh
│   ├── kich-ban-web.md
│   ├── loi-hong-csdl.md
│   ├── kiem-chung.md
│   ├── phan-cong.md
│   └── van-hanh.md
├── scripts
│   ├── kiem-chung-web.sh
│   ├── mysql-app.sh
│   ├── mysql-root.sh
│   └── reset-lab.sh
├── docker-compose.yml
└── .env.example
```

## 6. Yêu cầu

- Docker Engine kèm Docker Compose.
- Cổng `8080`, `8088` và `3307` còn trống trên máy.
- Khoảng 1 GB dung lượng trống cho image và dữ liệu lab.

## 7. Khởi chạy

```bash
cp .env.example .env
```

Mở `.env`, thay `DB_PASSWORD` và `MYSQL_ROOT_PASSWORD` bằng giá trị riêng. Giữ `APP_MODE=fixed` cho lần chạy đầu.

```bash
docker compose up -d --build
docker compose ps
curl http://localhost:8080/health
```

Kết quả mong đợi:

```json
{"status":"ok","database":"ok"}
```

Mở `http://localhost:8080/`. Trang mô phỏng CSRF ở `http://localhost:8088/`.

Trên Windows, có thể dùng `Copy-Item .env.example .env` thay cho `cp`.

## 8. Tài khoản lab

| Tên đăng nhập | Mật khẩu | Vai trò |
|---|---|---|
| `admin` | `Admin@Lab#2026` | Quản trị viên |
| `bien` | `Bien@Lab#2026` | Tác giả |
| `minh` | `Minh@Lab#2026` | Tác giả |
| `ngoc` | `Ngoc@Lab#2026` | Tác giả |
| `lan` | `Lan@Lab#2026` | Độc giả |

Đây là mật khẩu công khai dành cho lab. Không dùng lại cho bất kỳ hệ thống nào khác. Khi chạy lệnh trong shell, đặt mật khẩu trong dấu nháy đơn để ký tự `#` không bị hiểu là chú thích.

## 9. Chuyển cấu hình có lỗi và đã vá

Mặc định ứng dụng chạy ở cấu hình đã vá. Để trình diễn cấu hình có lỗi, sửa `.env`:

```dotenv
APP_MODE=vulnerable
```

Sau đó tạo lại dịch vụ ứng dụng:

```bash
docker compose up -d --force-recreate app
curl http://localhost:8080/health
docker compose exec -T app printenv APP_MODE
```

Kết quả mong đợi: `health` trả trạng thái `ok`; `APP_MODE` là `vulnerable`.

Quay lại cấu hình đã vá bằng cách đặt `APP_MODE=fixed` và lặp lại lệnh tạo lại dịch vụ. Có thể đặt biến môi trường trực tiếp cho một lần chạy:

```bash
APP_MODE=vulnerable docker compose up -d --force-recreate app
```

Không cần tạo lại cơ sở dữ liệu khi chuyển cấu hình. Tuy nhiên, nên đặt lại dữ liệu trước mỗi buổi trình diễn để trạng thái bài viết, bình luận và mật khẩu về mặc định.

## 10. Ma trận kịch bản web

| Mã | Kịch bản | Căn cứ giáo trình | Chức năng | Kết quả bản có lỗi | Biện pháp ở bản đã vá |
|---|---|---|---|---|---|
| W1 | Stored XSS | Chương 2, mục 2.1.2.1 | Bình luận | Mã script được lưu và thực thi khi mở bài viết | Mã hóa đầu ra theo ngữ cảnh HTML |
| W2 | Reflected XSS | Chương 2, mục 2.1.2.2 | Tìm kiếm | Chuỗi tìm kiếm được phản hồi vào HTML | Mã hóa từ khóa khi hiển thị |
| W3 | DOM-based XSS | Chương 2, mục 2.1.2.3 | Trang minh họa DOM | Tham số URL được gán vào `innerHTML` | Dùng `textContent` |
| W4 | CSRF | Chương 2, mục 2.2 | Đổi email | Biểu mẫu từ cổng khác được chấp nhận | Kiểm tra mã CSRF và `SameSite=Strict` |
| W5 | SQL Injection | Chương 2, mục 2.3 | Đăng nhập | Chuỗi `' OR '1'='1' -- ` vượt qua xác thực | Truy vấn tham số hóa và `password_verify` |
| W6 | Vét cạn mật khẩu | Chương 2, mục 2.4.2.2 | Đăng nhập | Không giới hạn số lần thử | Giới hạn 5 lần trong 15 phút |
| W7 | Cố định và phát lại phiên | Mục 2.4.2.1; Chương 3, mục 3.2.2 | Phiên đăng nhập | Mã phiên không đổi sau đăng nhập, không bị thu hồi khi đăng xuất | Đổi mã phiên, hủy phiên, cờ cookie |
| W8 | Mã đặt lại mật khẩu yếu | Chương 2, mục 2.4.2.4 | Quên mật khẩu | Mã dễ đoán và dùng lại được | Mã ngẫu nhiên, hết hạn, dùng một lần |
| W9 | Lạm dụng quy trình | Chương 2, mục 2.5.2.1 | Xuất bản bài viết | Người dùng thường tự đặt `status=published` | Kiểm tra vai trò, chủ sở hữu và trạng thái |
| W10 | Duyệt đường dẫn | Chương 3, mục 3.1.3.2 | Tải tệp | Đọc được tệp ngoài thư mục công khai | Chỉ nhận mã tệp, chuẩn hóa và giới hạn thư mục |

Chi tiết lệnh, kết quả quan sát và cách đặt lại dữ liệu nằm trong [docs/kich-ban-web.md](docs/kich-ban-web.md).

## 11. Ma trận lỗ hổng cơ sở dữ liệu

| Mã | Mục trong giáo trình | Cách kiểm tra trong lab | Biện pháp và bằng chứng |
|---|---|---|---|
| D1 | Default and Weak Passwords | Thử mật khẩu yếu với tài khoản ứng dụng | Mật khẩu mạnh, không có tài khoản ẩn danh, dùng `caching_sha2_password` |
| D2 | SQL Injection in the DBMS | So sánh truy vấn ghép chuỗi và truy vấn tham số hóa | Ứng dụng dùng prepared statement; đầu vào không đổi nghĩa truy vấn |
| D3 | Excessive User & Group Privileges | Kiểm tra `SHOW GRANTS` của `atud_app` | Chỉ `SELECT`, `INSERT`, `UPDATE`, `DELETE` trên một lược đồ |
| D4 | Unnecessary Enabled DBMS Features | Liệt kê plugin, `local_infile`, MySQL X | Tắt `local_infile`, tắt MySQL X, không cấp `FILE` |
| D5 | Broken Configuration Management | Kiểm tra vị trí bí mật, tệp `.env`, nhật ký | Bí mật nằm trong biến môi trường; `.env` bị loại khỏi Git và Docker context |
| D6 | Buffer Overflows | Gửi đầu vào vượt giới hạn vào tìm kiếm | Giới hạn độ dài, `max_allowed_packet`, runtime được quản lý và vá |
| D7 | Privilege Escalation | Tài khoản thường gọi trang quản trị; tài khoản DB thử thao tác quản trị | Kiểm tra vai trò ở máy chủ; tài khoản DB bị từ chối |
| D8 | Denial of Service | Đo truy vấn `SLEEP` có giới hạn, kiểm tra cấu hình | `max_execution_time`, `max_connections`, giới hạn kết quả và tần suất |
| D9 | Unpatched Database | Ghi nhận phiên bản và nguồn image | Dùng image `mysql:lts`, có quy trình cập nhật và kiểm tra lại |
| D10 | Unencrypted Data | Kiểm tra mật khẩu và mã đặt lại trong CSDL | Băm mật khẩu bằng bcrypt; mã đặt lại lưu dạng băm SHA-256 |

Chi tiết lệnh và kết quả nằm trong [docs/loi-hong-csdl.md](docs/loi-hong-csdl.md).

## 12. Kiểm chứng

Chạy từng kịch bản web:

```bash
BASE_URL=http://localhost:8080 bash scripts/kiem-chung-web.sh 01
BASE_URL=http://localhost:8080 bash scripts/kiem-chung-web.sh 05
```

Xem toàn bộ mã và cách diễn giải trong [docs/kiem-chung.md](docs/kiem-chung.md). Kết quả thô được lưu trong `docs/bang-chung`.

## 13. Đặt lại dữ liệu

Cách đầy đủ, xóa cả cơ sở dữ liệu và tệp tải lên:

```bash
docker compose down -v
docker compose up -d --build
```

Trên Windows:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/reset-lab.ps1
```

Sau khi đặt lại, chạy lại `curl http://localhost:8080/health` trước khi trình diễn.

## 14. Tài liệu

- [Kịch bản tấn công web](docs/kich-ban-web.md)
- [Mười lỗ hổng cơ sở dữ liệu](docs/loi-hong-csdl.md)
- [Kiểm chứng và bằng chứng](docs/kiem-chung.md)
- [Hướng dẫn vận hành](docs/van-hanh.md)
- [Phân công công việc](docs/phan-cong.md)
- [Hình ảnh kiểm chứng](docs/hinh/web/README.md)

Báo cáo Word và slide bảo vệ được giữ ngoài kho mã nguồn để tránh đưa tài liệu trình bày vào lịch sử Git.

## 15. Giới hạn

- Ứng dụng là môi trường lab, không phải sản phẩm triển khai thật.
- TLS, sao lưu mã hóa và giám sát tập trung chỉ được mô tả trong báo cáo, chưa cấu hình đầy đủ trong môi trường cục bộ.
- Cột `password_legacy` tồn tại có chủ đích để tái hiện kịch bản SQL Injection. Cột này không được dùng trong xác thực ở cấu hình đã vá và cần được loại bỏ khi chuyển sang triển khai thật.
- Kết quả tràn bộ đệm và từ chối dịch vụ trong tài liệu là kết quả kiểm tra giới hạn và cấu hình, không phải khai thác phá hoại.

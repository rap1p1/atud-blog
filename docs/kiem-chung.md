# Kiểm chứng trước và sau khi áp dụng biện pháp

## 1. Mục tiêu

Tài liệu ghi lại cách nhóm kiểm chứng mười kịch bản web và mười mục cơ sở dữ liệu, đối chiếu cấu hình có lỗi với cấu hình đã vá. Mỗi kết quả phải tái hiện được bằng lệnh hoặc thao tác được nêu, không dựa vào ảnh chụp không có ngữ cảnh.

## 2. Môi trường kiểm chứng

Lần kiểm chứng được thực hiện trên máy Windows của nhóm với:

- PHP chạy máy chủ phát triển ở cổng `8080` cho cấu hình có lỗi và `8081` cho cấu hình đã vá.
- MySQL LTS cài trực tiếp ở cổng `3307`, nạp cùng schema và dữ liệu mẫu trong `docker/mysql/init`.
- Cấu hình MySQL lấy từ `docker/mysql/conf.d/lab.cnf`.
- Mã nguồn, biến môi trường và logic ứng dụng giống với Docker Compose.

Máy kiểm chứng không có Docker Engine, nên nhóm chưa chạy trực tiếp `docker compose up`. Cấu hình Compose được kiểm tra cú pháp và đối chiếu với môi trường chạy thật. Khi chạy bằng Docker, chỉ cần đổi `APP_MODE` và dùng cổng `8080`; các lệnh trong tài liệu giữ nguyên giá trị `BASE_URL`.

## 3. Cách chạy lại

```bash
cp .env.example .env
docker compose up -d --build
curl http://localhost:8080/health
```

Chạy một kịch bản:

```bash
BASE_URL=http://localhost:8080 bash scripts/kiem-chung-web.sh 01
```

Đổi sang cấu hình có lỗi:

```bash
APP_MODE=vulnerable docker compose up -d --force-recreate app
curl http://localhost:8080/health
docker compose exec -T app printenv APP_MODE
```

## 4. Bảng kiểm chứng web

| Mã | Kịch bản | Bản có lỗi | Bản đã vá | Bằng chứng |
|---|---|---|---|---|
| W1 | Stored XSS | Mã script được lưu, hiển thị thô | Chuỗi được mã hóa | [ket-qua-web.md](bang-chung/ket-qua-web.md) |
| W2 | Reflected XSS | Từ khóa phản hồi thô | Từ khóa được mã hóa | [ket-qua-web.md](bang-chung/ket-qua-web.md) |
| W3 | DOM XSS | `innerHTML` | `textContent` | [ket-qua-web.md](bang-chung/ket-qua-web.md) |
| W4 | CSRF | Không có mã vẫn đổi email | `403`, email không đổi | [ket-qua-web.md](bang-chung/ket-qua-web.md) |
| W5 | SQL Injection | `302` tới `/admin` | `200`, từ chối | [ket-qua-web.md](bang-chung/ket-qua-web.md) |
| W6 | Vét cạn | Không giới hạn | Khóa sau 5 lần sai | [ket-qua-web.md](bang-chung/ket-qua-web.md) |
| W7 | Phát lại phiên | Mã cố định còn hiệu lực | Mã đổi, phiên cũ bị từ chối | [ket-qua-web.md](bang-chung/ket-qua-web.md) |
| W8 | Mã đặt lại | Dễ đoán, dùng lại được | Ngẫu nhiên, một lần | [ket-qua-web.md](bang-chung/ket-qua-web.md) |
| W9 | Lạm dụng quy trình | Tự xuất bản | `403`, giữ nháp | [ket-qua-web.md](bang-chung/ket-qua-web.md) |
| W10 | Duyệt đường dẫn | Đọc tệp riêng | `400` | [ket-qua-web.md](bang-chung/ket-qua-web.md) |

Chi tiết lệnh và kết quả đầy đủ ở [hướng dẫn kịch bản Web](kich-ban/web/README.md).

## 5. Bảng kiểm chứng cơ sở dữ liệu

| Mã | Mục kiểm tra | Kết quả | Bằng chứng |
|---|---|---|---|
| D1 | Default and Weak Passwords | Mật khẩu yếu bị từ chối; không có tài khoản ẩn danh | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |
| D2 | SQL Injection in the DBMS | Ghép chuỗi trả về 5 dòng; tham số hóa trả về 0 | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |
| D3 | Excessive User & Group Privileges | Chỉ DML trên một lược đồ | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |
| D4 | Unnecessary Enabled DBMS Features | `local_infile` tắt, MySQL X tắt | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |
| D5 | Broken Configuration Management | Bí mật ngoài Git; không lộ cấu hình | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |
| D6 | Buffer Overflows | Đầu vào vượt biên bị từ chối | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |
| D7 | Privilege Escalation | Vai trò và quyền DB bị kiểm tra | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |
| D8 | Denial of Service | Truy vấn bị ngắt sau khoảng 2 giây | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |
| D9 | Unpatched Database | Image LTS; phiên bản được ghi nhận | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |
| D10 | Unencrypted Data | Bcrypt và SHA-256; còn nợ kỹ thuật | [ket-qua-csdl.md](bang-chung/ket-qua-csdl.md) |

Chi tiết ở [hướng dẫn kiểm tra CSDL](kich-ban/csdl/README.md).

## 6. Tiêu chí đạt

- Mười kịch bản web đều có kết quả khác nhau rõ ràng giữa hai cấu hình.
- Mười mục cơ sở dữ liệu đều có lệnh kiểm tra và kết quả quan sát.
- Các kịch bản nguy hiểm như tràn bộ đệm và từ chối dịch vụ chỉ được kiểm tra bằng biên, cấu hình và phép đo có giới hạn.
- Mọi kết quả có thể đặt lại bằng cách xóa volume và khởi động lại.
- Không có dữ liệu thật, không có mật khẩu thật và không có tệp bí mật ngoài phạm vi lab.

## 7. Giới hạn của lần kiểm chứng

- Chưa chạy Docker Compose trực tiếp trên máy kiểm chứng; cấu hình được đối chiếu với tiến trình PHP và MySQL chạy cùng mã nguồn, cùng tham số.
- Chưa có ảnh chụp màn hình cho mọi bước. Bằng chứng chính là lệnh, mã trạng thái, nội dung phản hồi và truy vấn cơ sở dữ liệu; nhóm bổ sung ảnh khi trình bày.
- TLS, sao lưu mã hóa và giám sát tập trung chưa được cấu hình trong lab.
- Kết quả D6 và D8 không phải khai thác thành công; đây là kiểm tra giới hạn và biện pháp giảm thiểu.


## 8. Hình ảnh kiểm chứng

Ảnh chụp đầy đủ nằm trong [docs/hinh/web](hinh/web/README.md). Mỗi ảnh có thanh chú thích ghi rõ kịch bản, vị trí nhập và kết quả.

### 8.1. SQL Injection

| Nhập payload tại ô đăng nhập | Kết quả trên bản có lỗi |
|---|---|
| ![Nhập payload SQL Injection](kich-ban/web/05-sql-injection-dang-nhap/evidence/05-sqli-nhap.png) | ![Kết quả SQL Injection](kich-ban/web/05-sql-injection-dang-nhap/evidence/06-sqli-ket-qua.png) |

| Cùng payload trên bản đã vá | Kết quả bị từ chối |
|---|---|
| ![Nhập payload trên bản đã vá](kich-ban/web/05-sql-injection-dang-nhap/evidence/26-sqli-nhap-da-va.png) | ![Kết quả bị từ chối](kich-ban/web/05-sql-injection-dang-nhap/evidence/27-sqli-ket-qua-da-va.png) |

### 8.2. Stored XSS

| Nhập payload tại ô bình luận | Kết quả thực thi trên bản có lỗi |
|---|---|
| ![Nhập payload Stored XSS](kich-ban/web/01-stored-xss/evidence/07-stored-xss-nhap.png) | ![Kết quả Stored XSS](kich-ban/web/01-stored-xss/evidence/08-stored-xss-ket-qua.png) |

| Kết quả trên bản đã vá |
|---|
| ![Stored XSS đã vá](kich-ban/web/01-stored-xss/evidence/21-stored-xss-da-va.png) |

### 8.3. Reflected XSS và DOM XSS

| Reflected XSS qua URL | DOM XSS qua URL |
|---|---|
| ![Reflected XSS](kich-ban/web/02-reflected-xss/evidence/09-reflected-xss.png) | ![DOM XSS](kich-ban/web/03-dom-xss/evidence/10-dom-xss.png) |

| Reflected XSS đã vá | DOM XSS đã vá |
|---|---|
| ![Reflected XSS đã vá](kich-ban/web/02-reflected-xss/evidence/22-reflected-xss-da-va.png) | ![DOM XSS đã vá](kich-ban/web/03-dom-xss/evidence/23-dom-xss-da-va.png) |

### 8.4. CSRF

| Biểu mẫu tại trang tấn công | Kết quả đổi email trên bản có lỗi |
|---|---|
| ![Biểu mẫu CSRF](kich-ban/web/04-csrf-doi-email/evidence/11-csrf-nhap.png) | ![Kết quả CSRF](kich-ban/web/04-csrf-doi-email/evidence/12-csrf-ket-qua.png) |

| Yêu cầu trên bản đã vá | Kết quả 403 |
|---|---|
| ![CSRF đã vá](kich-ban/web/04-csrf-doi-email/evidence/24-csrf-da-va.png) | ![Kết quả CSRF đã vá](kich-ban/web/04-csrf-doi-email/evidence/25-csrf-ket-qua-da-va.png) |

### 8.5. Các kịch bản còn lại

| Vét cạn mật khẩu | Phát lại phiên |
|---|---|
| ![Vét cạn mật khẩu](kich-ban/web/06-vet-can-mat-khau/evidence/13-brute-force.png) | ![Phát lại phiên](kich-ban/web/07-phat-lai-phien/evidence/14-session-replay.png) |

| Mã đặt lại hiển thị | Dùng lại mã |
|---|---|
| ![Mã đặt lại](kich-ban/web/08-dat-lai-mat-khau/evidence/15-reset-nhap.png) | ![Dùng lại mã](kich-ban/web/08-dat-lai-mat-khau/evidence/16-reset-ket-qua.png) |

| Lạm dụng quy trình | Duyệt đường dẫn |
|---|---|
| ![Lạm dụng quy trình](kich-ban/web/09-lam-dung-quy-trinh/evidence/17-workflow-nhap.png) | ![Duyệt đường dẫn](kich-ban/web/10-duyet-duong-dan/evidence/19-traversal.png) |

| Khóa đăng nhập trên bản đã vá | Phiên cũ bị từ chối |
|---|---|
| ![Khóa đăng nhập](kich-ban/web/06-vet-can-mat-khau/evidence/28-brute-force-da-va.png) | ![Phiên cũ bị từ chối](kich-ban/web/07-phat-lai-phien/evidence/29-session-da-va.png) |

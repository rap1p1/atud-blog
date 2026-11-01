# Bảng phân công công việc

## 1. Thành viên và vai trò

| Thành viên | Vai trò | Phạm vi phụ trách |
|---|---|---|
| Thành viên 1 | Nhóm trưởng, phụ trách kiến trúc | Thiết kế CSDL, khung ứng dụng, Docker Compose, tổng hợp báo cáo |
| Thành viên 2 | Phụ trách bảo mật ứng dụng web | Mười kịch bản web, kiểm chứng trước và sau khi vá, ghi bằng chứng |
| Thành viên 3 | Phụ trách cơ sở dữ liệu và vận hành | Quyền, cấu hình MySQL, mười mục CSDL, kịch bản trình diễn |

## 2. Phân công theo hạng mục

| Hạng mục | Thành viên 1 | Thành viên 2 | Thành viên 3 |
|---|---|---|---|
| Khảo sát đề và tài liệu môn học | Chủ trì | Tham gia | Tham gia |
| Thiết kế lược đồ `users`, `posts`, `comments`, `uploads`, `password_reset_tokens` | Chủ trì | Góp ý | Kiểm tra ràng buộc |
| Xây dựng đăng nhập, phiên, phân quyền | Chủ trì | Kiểm thử | Góp ý |
| Bài viết, bình luận, tìm kiếm, tệp | Tham gia | Chủ trì | Góp ý |
| Mười kịch bản tấn công web | Góp ý | Chủ trì | Kiểm tra chéo |
| Mười mục lỗ hổng CSDL | Góp ý | Kiểm tra chéo | Chủ trì |
| Docker Compose và cấu hình máy chủ | Chủ trì | Góp ý | Kiểm tra vận hành |
| Kịch bản trình diễn và đặt lại dữ liệu | Góp ý | Chủ trì | Chủ trì |
| Báo cáo Word | Chủ trì | Viết phần web | Viết phần CSDL |
| Slide trình bày | Tham gia | Chủ trì | Tham gia |
| Kiểm tra chéo toàn bộ | Chủ trì | Tham gia | Tham gia |

## 3. Tiến độ

| Giai đoạn | Nội dung | Kết quả cần có |
|---|---|---|
| 1 | Chốt ma trận mười kịch bản web và mười mục CSDL | Bảng đối chiếu có mã, mục kiểm tra, chức năng |
| 2 | Hoàn thiện blog cơ bản | Đăng nhập, bài viết, bình luận, tìm kiếm, quản trị, tệp |
| 3 | Tạo dữ liệu lab | Bốn tài khoản, năm bài viết, bình luận, tệp mồi |
| 4 | Thêm điểm lỗi có kiểm soát | Mỗi lỗi nằm ở đúng chức năng, có công tắc cấu hình |
| 5 | Áp dụng biện pháp vá | Mã hóa đầu ra, CSRF, prepared statement, giới hạn, phân quyền |
| 6 | Kiểm chứng trước và sau | Lệnh, mã trạng thái, phản hồi, truy vấn |
| 7 | Hoàn thiện báo cáo và slide | Bản Word, slide, mã nguồn, bảng phân công |
| 8 | Tổng duyệt | Đặt lại dữ liệu, chạy lại toàn bộ kịch bản, phân công người trình bày |

## 4. Quy ước làm việc

- Mỗi thay đổi mã nguồn phải kèm mô tả ngắn và cách kiểm tra.
- Không đưa dữ liệu thật, mật khẩu thật hoặc tệp cấu hình cá nhân vào kho.
- Mỗi kịch bản phải có một lệnh hoặc thao tác tái hiện được, không chỉ mô tả bằng lời.
- Kết quả trước và sau khi vá phải dùng cùng dữ liệu, cùng tài khoản và cùng thao tác.
- Thay đổi liên quan đến CSDL phải cập nhật cả `01-schema.sql`, `02-data.sql` và tài liệu.
- Trước khi nộp, chạy lại `scripts/kiem-chung-web.sh` và kiểm tra `git status`.

## 5. Phân công trình bày

| Phần | Người trình bày | Thời lượng dự kiến |
|---|---|---|
| Giới thiệu mục tiêu, kiến trúc, phạm vi lab | Thành viên 1 | 3 phút |
| Chức năng blog và dữ liệu lab | Thành viên 1 | 2 phút |
| Năm kịch bản web đầu | Thành viên 2 | 7 phút |
| Năm kịch bản web còn lại | Thành viên 2 | 6 phút |
| Mười mục CSDL | Thành viên 3 | 8 phút |
| Đối chiếu trước và sau khi vá | Thành viên 2 và 3 | 4 phút |
| Kết luận, hạn chế, hướng phát triển | Thành viên 1 | 2 phút |

## 6. Kiểm tra chéo

| Nội dung kiểm tra | Người kiểm tra | Cách kiểm tra |
|---|---|---|
| Mã nguồn không chứa mật khẩu thật | Thành viên 3 | Rà `.env`, lịch sử Git, chuỗi cấu hình |
| Mười kịch bản chạy đúng | Thành viên 2 | Chạy lại script và đối chiếu bằng chứng |
| Quyền CSDL đúng | Thành viên 1 | `SHOW GRANTS`, thử thao tác ngoài quyền |
| Dữ liệu đặt lại được | Thành viên 3 | `docker compose down -v` và chạy lại |
| Báo cáo khớp mã nguồn | Cả nhóm | Đối chiếu tên endpoint, tên trường, mã trạng thái |

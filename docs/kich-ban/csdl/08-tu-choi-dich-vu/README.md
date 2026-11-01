# Mục 8. Từ chối dịch vụ

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 8. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Truy vấn dài hoặc không giới hạn có thể chiếm tài nguyên; lab đo đúng một truy vấn có timeout.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** Đo một truy vấn ngủ có kiểm soát, đối chiếu thời gian thực tế với `max_execution_time`; không gửi tải lặp.

**Dữ liệu/phạm vi thử:** Một lần `SELECT SLEEP(3)`, không flood.

**Công cụ:** MySQL CLI, đồng hồ, timeout cấu hình.

**Mã nguồn và cấu hình liên quan:** [`app/src/Core/Database.php`](../../../../app/src/Core/Database.php), [`docker/mysql/conf.d/lab.cnf`](../../../../docker/mysql/conf.d/lab.cnf).

## Cách kiểm tra an toàn

Đo một truy vấn ngủ có kiểm soát:

```bash
time bash scripts/mysql-app.sh -e "SELECT SLEEP(3) AS slow_query;"
```

Kiểm tra giới hạn:

```bash
bash scripts/mysql-root.sh -e "SELECT @@max_execution_time AS max_execution_time_ms, @@max_connections AS max_connections;"
```

## Kết quả

```text
slow_query
1

real    0m2.048s
```

`SLEEP(3)` bị ngắt sau khoảng 2 giây; giá trị `1` cho biết truy vấn bị dừng bởi `max_execution_time=2000`. `max_connections` là 40. Ứng dụng giới hạn số bài viết mỗi trang, giới hạn từ khóa và giới hạn số lần đăng nhập.

## Biện pháp phòng thủ

- Đặt `max_execution_time` cho truy vấn đọc.
- Giới hạn `max_connections`, kích thước kết quả và thời gian xử lý yêu cầu.
- Phân trang và giới hạn đầu vào.
- Giới hạn tần suất đăng nhập và thao tác tốn kém.
- Không chạy thử nghiệm tải kéo dài hoặc flood trong lab.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

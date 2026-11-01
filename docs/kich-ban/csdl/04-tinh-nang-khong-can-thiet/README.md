# Mục 4. Tính năng DBMS không cần thiết

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 4. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Dịch vụ hoặc plugin không sử dụng làm tăng bề mặt tấn công và cần được tắt.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** Đọc biến cấu hình và danh sách plugin; không bật thử tính năng nguy hiểm.

**Dữ liệu/phạm vi thử:** Đọc cấu hình và biến trạng thái; không bật thử tính năng nguy hiểm.

**Công cụ:** MySQL CLI, `SHOW VARIABLES`, `SHOW PLUGINS`, Docker config.

**Mã nguồn và cấu hình liên quan:** [`docker/mysql/conf.d/lab.cnf`](../../../../docker/mysql/conf.d/lab.cnf), [`docker/mysql/Dockerfile`](../../../../docker/mysql/Dockerfile).

## Cách kiểm tra an toàn

```bash
bash scripts/mysql-root.sh -e "SHOW VARIABLES LIKE 'local_infile';"
bash scripts/mysql-root.sh -e "SHOW VARIABLES LIKE 'mysqlx%';"
bash scripts/mysql-root.sh -e "SHOW VARIABLES LIKE 'secure_file_priv';"
bash scripts/mysql-root.sh -e "SHOW PLUGINS;"
```

## Kết quả

```text
local_infile    OFF
```

Truy vấn `mysqlx%` không trả về dòng nào, tương ứng với việc MySQL X bị tắt trong `docker/mysql/conf.d/lab.cnf` bằng `mysqlx=0`. Danh sách plugin chỉ gồm các thành phần cần cho lab; không bật plugin thử nghiệm.

## Biện pháp phòng thủ

- Tắt `local_infile`.
- Tắt MySQL X nếu ứng dụng không dùng giao thức X.
- Không cấp quyền `FILE`, không dùng `LOAD DATA LOCAL`.
- Loại bỏ cơ sở dữ liệu mẫu và tài khoản không dùng.
- Ghi lại cấu hình sau khi thay đổi và kiểm tra lại bằng `SHOW VARIABLES`.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

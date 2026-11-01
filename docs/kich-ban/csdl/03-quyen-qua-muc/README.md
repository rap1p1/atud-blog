# Mục 3. Quyền người dùng và nhóm quá mức

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 3. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Tài khoản ứng dụng có quyền vượt quá nghiệp vụ cần thiết sẽ làm tăng hậu quả khi ứng dụng bị xâm nhập.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** Đọc grants của tài khoản ứng dụng, sau đó thử tạo, xóa đối tượng và đọc bảng hệ thống.

**Dữ liệu/phạm vi thử:** Kiểm tra grants và thử thao tác quản trị trong database lab.

**Công cụ:** MySQL CLI với tài khoản `atud_app`, xác nhận bằng `SHOW GRANTS`.

**Mã nguồn và cấu hình liên quan:** [`docker/mysql/init/03-nguoi-dung.sh`](../../../../docker/mysql/init/03-nguoi-dung.sh), [`docker/mysql/init/01-schema.sql`](../../../../docker/mysql/init/01-schema.sql).

## Cách kiểm tra an toàn

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

## Kết quả

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

## Biện pháp phòng thủ

- Cấp quyền tối thiểu theo lược đồ và theo thao tác.
- Tách tài khoản quản trị và tài khoản ứng dụng.
- Không cấp `FILE`, `PROCESS`, `SUPER`, `GRANT OPTION` cho ứng dụng.
- Rà soát `SHOW GRANTS` định kỳ và sau mỗi lần thay đổi tính năng.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

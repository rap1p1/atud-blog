# Kiểm tra an toàn cơ sở dữ liệu

Mỗi mục được tách thành thư mục riêng, gồm hướng dẫn kiểm tra, kết quả quan sát và biện pháp phòng thủ. Lệnh và kết nối dùng chung được mô tả dưới đây. Chỉ chạy trên CSDL lab của đồ án.

```bash
bash scripts/mysql-app.sh -e "SELECT ...;"
bash scripts/mysql-root.sh -e "SELECT ...;"
```

`mysql-app.sh` kết nối bằng `atud_app`; `mysql-root.sh` chỉ dùng để xác nhận cấu hình quản trị. Nhật ký chi tiết nằm trong [bằng chứng CSDL](../../bang-chung/ket-qua-csdl.md).

## Các mục CSDL

| Số | Mục | Thư mục |
|---:|---|---|
| 1 | Mật khẩu mặc định và yếu | [01-mat-khau-yeu](01-mat-khau-yeu/README.md) |
| 2 | SQL Injection tại DBMS | [02-sql-injection-dbms](02-sql-injection-dbms/README.md) |
| 3 | Quyền tài khoản và nhóm quá mức | [03-quyen-qua-muc](03-quyen-qua-muc/README.md) |
| 4 | Tính năng DBMS không cần thiết | [04-tinh-nang-khong-can-thiet](04-tinh-nang-khong-can-thiet/README.md) |
| 5 | Quản lý cấu hình sai | [05-quan-ly-cau-hinh](05-quan-ly-cau-hinh/README.md) |
| 6 | Tràn bộ đệm (kiểm tra giới hạn) | [06-tran-bo-dem](06-tran-bo-dem/README.md) |
| 7 | Leo thang đặc quyền | [07-leo-thang-dac-quyen](07-leo-thang-dac-quyen/README.md) |
| 8 | Từ chối dịch vụ (kiểm tra giới hạn) | [08-tu-choi-dich-vu](08-tu-choi-dich-vu/README.md) |
| 9 | DBMS chưa cập nhật | [09-dbms-chua-cap-nhat](09-dbms-chua-cap-nhat/README.md) |
| 10 | Dữ liệu không mã hóa | [10-du-lieu-khong-ma-hoa](10-du-lieu-khong-ma-hoa/README.md) |

# Mục 9. DBMS chưa cập nhật

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 9. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Phiên bản cũ có thể chứa lỗi đã biết; xác minh phiên bản/image, không khai thác CVE.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** Ghi nhận phiên bản máy chủ và tag image, sau đó đối chiếu quy trình cập nhật; không chạy mã khai thác CVE.

**Dữ liệu/phạm vi thử:** `SELECT VERSION()` và tag image.

**Công cụ:** MySQL CLI và Docker.

**Mã nguồn và cấu hình liên quan:** [`docker/mysql/Dockerfile`](../../../../docker/mysql/Dockerfile), [`docker-compose.yml`](../../../../docker-compose.yml).

## Cách kiểm tra an toàn

```bash
bash scripts/mysql-root.sh -e "SELECT VERSION() AS version, @@version_comment AS comment;"
docker compose images db
```

## Kết quả

```text
version   comment
8.4.6     MySQL Community Server - GPL
```

Image cơ sở dữ liệu dùng thẻ `mysql:lts`, nhánh hỗ trợ dài hạn. Phiên bản trong lần kiểm chứng là `8.4.6`. Nhóm không khai thác lỗi của phiên bản cũ.

## Biện pháp phòng thủ

- Dùng image LTS từ nguồn chính thức.
- Có quy trình cập nhật:

```bash
docker compose pull db
docker compose up -d db
docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SELECT VERSION();"'
```

- Ghi lại phiên bản trước và sau khi cập nhật.
- Chạy lại bộ kiểm chứng chức năng sau khi cập nhật.
- Theo dõi thông báo bảo mật của nhà cung cấp.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

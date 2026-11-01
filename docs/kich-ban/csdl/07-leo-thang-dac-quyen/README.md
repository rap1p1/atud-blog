# Mục 7. Leo thang đặc quyền

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 7. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Thiếu kiểm soát vai trò hoặc quyền DB quá rộng cho phép tài khoản thường thực hiện thao tác quản trị.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** Kiểm tra hai lớp: vai trò ứng dụng khi gọi trang quản trị và quyền của tài khoản DB khi thực hiện lệnh quản trị.

**Dữ liệu/phạm vi thử:** Tài khoản giả gọi trang admin và thử lệnh quản trị bằng tài khoản DB ứng dụng.

**Công cụ:** Trình duyệt/`curl`, MySQL CLI.

**Mã nguồn và cấu hình liên quan:** [`app/src/Core/Auth.php`](../../../../app/src/Core/Auth.php), [`app/src/Controllers/AdminController.php`](../../../../app/src/Controllers/AdminController.php), [`docker/mysql/init/03-nguoi-dung.sh`](../../../../docker/mysql/init/03-nguoi-dung.sh).

## Cách kiểm tra an toàn

### Tại ứng dụng

Đăng nhập `bien`, sau đó gọi trang quản trị:

```bash
html="$(curl -sS -c "$JAR" http://localhost:8080/login)"
token="$(printf '%s' "$html" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)"
curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "username=bien" \
  --data-urlencode "password=Bien@Lab#2026" \
  --data-urlencode "_csrf=$token" \
  -o /dev/null http://localhost:8080/login
curl -sS -b "$JAR" -o /dev/null -w '%{http_code}\n' http://localhost:8080/admin
```

### Tại CSDL

```bash
bash scripts/mysql-app.sh -e "CREATE USER 'lab_escalation'@'%' IDENTIFIED BY 'Lab@2026Test';" || true
bash scripts/mysql-app.sh -e "GRANT ALL PRIVILEGES ON *.* TO 'atud_app'@'%';" || true
```

## Kết quả

```text
403
```

Tài khoản người dùng thường không vào được trang quản trị. Tài khoản DB ứng dụng không tạo được người dùng và không tự cấp quyền:

```text
ERROR 1227 (42000): Access denied; you need (at least one of) the CREATE USER privilege(s)
ERROR 1045 (28000): Access denied for user 'atud_app'@'%'
```

## Biện pháp phòng thủ

- Kiểm tra vai trò ở máy chủ cho từng yêu cầu, không dựa vào giao diện.
- Kiểm tra chủ sở hữu tài nguyên và trạng thái nghiệp vụ.
- Phân quyền tối thiểu ở DB; tách tài khoản quản trị.
- Ghi nhật ký các lần từ chối và rà soát định kỳ.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

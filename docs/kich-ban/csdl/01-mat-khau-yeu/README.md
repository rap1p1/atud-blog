# Mục 1. Mật khẩu mặc định và yếu

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 1. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Tài khoản quản trị mặc định, mật khẩu yếu và tài khoản ẩn danh có thể tạo cửa ngõ truy cập CSDL.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** Liệt kê tài khoản và plugin xác thực; thử một mật khẩu yếu với tài khoản ứng dụng và ghi nhận mã lỗi.

**Dữ liệu/phạm vi thử:** Mật khẩu giả yếu; chỉ thử đăng nhập vào tài khoản ứng dụng lab.

**Công cụ:** MySQL CLI qua `scripts/mysql-root.sh` và `scripts/mysql-app.sh`; `mysql.user`.

**Mã nguồn và cấu hình liên quan:** [`docker/mysql/init/03-nguoi-dung.sh`](../../../../docker/mysql/init/03-nguoi-dung.sh), [`docker/mysql/conf.d/lab.cnf`](../../../../docker/mysql/conf.d/lab.cnf).

## Cách kiểm tra an toàn

```bash
bash scripts/mysql-root.sh -e "SELECT user, host, plugin FROM mysql.user ORDER BY user, host;"
bash scripts/mysql-root.sh -e "SELECT COUNT(*) AS anonymous_users FROM mysql.user WHERE user = '';"
bash scripts/mysql-root.sh -e "SELECT COUNT(*) AS legacy_plugin_accounts FROM mysql.user WHERE plugin = 'mysql_native_password';"
```

Thử đăng nhập bằng mật khẩu yếu:

```bash
docker compose exec -T db sh -c 'mysql -u"$DB_USER" -p"matkhauyeu" -e "SELECT 1;"'
echo $?
```

## Kết quả

```text
user        host         plugin
atud_app    %            caching_sha2_password
root        localhost    caching_sha2_password
...
anonymous_users
0
legacy_plugin_accounts
0
```

Lần đăng nhập bằng `matkhauyeu`:

```text
ERROR 1045 (28000): Access denied for user 'atud_app'@'127.0.0.1' (using password: YES)
exit=1
```

## Biện pháp phòng thủ

- Mật khẩu quản trị và mật khẩu ứng dụng được sinh riêng, lưu trong `.env` không được commit.
- Không tạo tài khoản ẩn danh; không dùng tài khoản `root` cho ứng dụng.
- Tài khoản ứng dụng dùng `caching_sha2_password`.
- Mật khẩu của người dùng blog được băm bằng bcrypt, không lưu dạng rõ.
- Khi chuyển sang môi trường thật, thay toàn bộ mật khẩu lab và dùng kho bí mật của hạ tầng.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

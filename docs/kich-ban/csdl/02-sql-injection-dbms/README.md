# Mục 2. SQL Injection tại DBMS

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 2. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Truy vấn ghép chuỗi có thể làm dữ liệu đầu vào thay đổi cấu trúc hoặc điều kiện truy vấn.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** So sánh cùng một chuỗi đầu vào ở truy vấn ghép chuỗi và truy vấn tham số hóa.

**Dữ liệu/phạm vi thử:** Cùng một chuỗi thử trong truy vấn ghép và truy vấn tham số hóa, chỉ trên schema `atud_blog`.

**Công cụ:** MySQL CLI; truy vấn đọc, không chạy lệnh phá hoại.

**Mã nguồn và cấu hình liên quan:** [`app/src/Core/Database.php`](../../../../app/src/Core/Database.php), [`docs/bang-chung/ket-qua-csdl.md`](../../../bang-chung/ket-qua-csdl.md).

## Cách kiểm tra an toàn

Truy vấn ghép chuỗi mô phỏng lỗi của ứng dụng:

```bash
bash scripts/mysql-app.sh -e "SELECT id, username, role FROM users WHERE username = '' OR '1'='1' -- ' AND password_legacy = MD5('x') LIMIT 5;"
```

Truy vấn tham số hóa tương đương:

```bash
bash scripts/mysql-app.sh -e "
PREPARE stmt FROM 'SELECT id, username, role FROM users WHERE username = ? LIMIT 5';
SET @u = ''' OR ''1''=''1'' -- ';
EXECUTE stmt USING @u;
DEALLOCATE PREPARE stmt;"
```

## Kết quả

Truy vấn ghép chuỗi trả về toàn bộ tài khoản:

```text
id  username  role
1   admin     admin
2   bien      author
3   minh      author
4   ngoc      author
5   lan       user
```

Truy vấn tham số hóa không trả về bản ghi nào, vì toàn bộ chuỗi được so sánh như giá trị của cột `username`. Ứng dụng ở cấu hình đã vá dùng PDO với `ATTR_EMULATE_PREPARES=false`, nên tham số được truyền riêng khỏi câu lệnh.

Kịch bản W5 trong [Kịch bản 5 về SQL Injection ở tầng ứng dụng](../../web/05-sql-injection-dang-nhap/README.md) trình bày cùng lỗi ở tầng ứng dụng: cấu hình có lỗi đăng nhập được vào `/admin`, cấu hình đã vá từ chối.

## Biện pháp phòng thủ

- Không ghép chuỗi đầu vào vào SQL.
- Dùng prepared statement cho mọi câu lệnh có tham số.
- Kiểm tra kiểu dữ liệu và độ dài trước khi truy vấn.
- Không hiển thị câu lệnh SQL hoặc lỗi cơ sở dữ liệu cho người dùng ở cấu hình đã vá.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

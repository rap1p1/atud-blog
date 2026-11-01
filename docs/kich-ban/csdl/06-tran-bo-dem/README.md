# Mục 6. Tràn bộ đệm

Tài liệu này tách riêng nội dung kiểm tra, kết quả và biện pháp của mục CSDL số 6. Hướng dẫn lệnh dùng chung nằm trong [hướng dẫn kiểm tra CSDL](../README.md). Nhật ký chi tiết được lưu tại [bằng chứng CSDL](../../../bang-chung/ket-qua-csdl.md).

## Lý thuyết

Đầu vào quá dài có thể gây lỗi xử lý; bài lab chỉ kiểm tra giới hạn và cấu hình, không khai thác tràn bộ đệm DBMS.

## Kỹ thuật và công cụ sử dụng

**Phương pháp:** Gửi đầu vào vượt biên, ghi mã HTTP và đối chiếu với giới hạn runtime của PHP/MySQL.

**Dữ liệu/phạm vi thử:** Chuỗi truy vấn 5000 ký tự và thông số giới hạn runtime.

**Công cụ:** `curl`, MySQL CLI, cấu hình PHP/MySQL.

**Mã nguồn và cấu hình liên quan:** [`app/src/Controllers/PostController.php`](../../../../app/src/Controllers/PostController.php), [`docker/php/php.ini`](../../../../docker/php/php.ini), [`docker/mysql/conf.d/lab.cnf`](../../../../docker/mysql/conf.d/lab.cnf).

## Cách kiểm tra an toàn

Không chạy khai thác tràn bộ đệm trên MySQL. Lab kiểm tra biên đầu vào của ứng dụng và ghi nhận cấu hình runtime:

```bash
LONG="$(printf 'a%.0s' $(seq 1 5000))"
curl -sS -G --data-urlencode "q=$LONG" http://localhost:8080/search -o /dev/null -w '%{http_code}\n'
```

```bash
bash scripts/mysql-root.sh -e "SELECT VERSION(); SHOW VARIABLES LIKE 'max_allowed_packet'; SHOW VARIABLES LIKE 'sql_mode';"
```

## Kết quả

```text
400
```

Cấu hình có lỗi trả về `200` với cùng đầu vào. Cấu hình đã vá giới hạn từ khóa ở 100 ký tự. MySQL nhận `max_allowed_packet` 16 MB; ứng dụng giới hạn `post_max_size` 3 MB và `upload_max_filesize` 2 MB.

## Biện pháp phòng thủ

- Kiểm tra độ dài và kiểu dữ liệu ở biên.
- Dùng API an toàn, không tự quản lý bộ đệm trong mã ứng dụng.
- Dùng phiên bản DBMS còn hỗ trợ và cập nhật bản vá.
- Giới hạn kích thước gói, kích thước tệp và thời gian xử lý.
- Ghi rõ trong báo cáo: nhóm không khai thác tràn bộ đệm; bằng chứng là biên đầu vào bị từ chối và cấu hình runtime.

## Nhật ký và bằng chứng

Kết quả đo và đầu ra lệnh được ghi trong [nhật ký kiểm chứng CSDL](../../../bang-chung/ket-qua-csdl.md). Phép thử này chỉ dùng dữ liệu trong lab.

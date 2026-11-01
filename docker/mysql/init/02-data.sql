SET NAMES utf8mb4;

INSERT INTO users (id, username, email, password_hash, password_legacy, role, is_active) VALUES
(1, 'admin', 'admin@gocnho.example', '$2b$12$aDq0daGlH72Ap3LjEvoBTeWo6pgS5ocLuY3yZ9JuDKXJXauKtt9fe', '45a1878eca685306b8f0020de07eafea', 'admin', 1),
(2, 'bien', 'bien@gocnho.example', '$2b$12$kKFr3hFKn/2Y1oAPPtIya.virmMXFPMusRUvqF/rNvQI0r3I9UCMK', '1695899fc0f66161045ae0050a05df13', 'author', 1),
(3, 'minh', 'minh@gocnho.example', '$2b$12$qXqppYiIrA37egpLwMIj6euNKefglCWDv1HbIeigsFo6Ytjfp3SOG', 'bfb9aef75959ca36ca54985ac8dc3498', 'author', 1),
(4, 'ngoc', 'ngoc@gocnho.example', '$2b$12$nBuEtfrWWQfCR/SVKAFzje376FrG9HA345Kb.P5Do2j6SH8xCy0SW', '6abb86f880f1387b956788571111927c', 'author', 1),
(5, 'lan', 'lan@gocnho.example', '$2b$12$oEatmAJWKMAN2O2/IoPgluLha3NwxH5uZlUWDwMTIfmARkxeucK6W', '93a20562525dd2a30485ea543bf04f3e', 'user', 1);

INSERT INTO posts (id, author_id, title, summary, body, status, created_at) VALUES
(1, 1, 'Một buổi chiều ở Đà Lạt', 'Ghi lại vài khoảnh khắc bình yên sau chuyến đi ngắn.', 'Đà Lạt những ngày cuối tuần không quá đông. Buổi chiều, mình ngồi ở một quán nhỏ ven đồi, nhìn sương xuống chậm và nghe tiếng xe đạp đi ngang con dốc.

Chuyến đi không có lịch trình cố định. Mình chỉ đi bộ, ghé vài tiệm sách, rồi tìm một chỗ ngồi đủ lâu để viết lại vài dòng. Có lẽ những chuyến đi như vậy để lại nhiều điều hơn những nơi đã đánh dấu trên bản đồ.', 'published', '2026-10-20 08:30:00'),
(2, 3, 'Ba cuốn sách nên đọc chậm', 'Một vài cuốn sách phù hợp để đọc lại, ghi chú và suy ngẫm.', 'Có những cuốn sách không nên đọc vội. Mình thường đọc một chương, gấp sách lại, ghi vài dòng rồi mới quay lại chương tiếp theo.

Ba cuốn đang nằm trên bàn làm việc của mình là một cuốn tản văn, một cuốn về thói quen và một cuốn tiểu thuyết ngắn. Điều mình thích không phải là đọc hết nhanh, mà là để nội dung ở lại lâu hơn một chút.', 'published', '2026-10-16 09:15:00'),
(3, 4, 'Cách mình giữ thói quen viết mỗi ngày', 'Một vài cách nhỏ để duy trì việc viết mà không tạo áp lực.', 'Mình bắt đầu bằng mười phút mỗi sáng, trước khi mở điện thoại. Không đặt mục tiêu phải viết hay, chỉ cần ghi lại một ý nghĩ hoặc một việc đã xảy ra.

Sau vài tuần, việc viết trở thành một phần của buổi sáng. Khi bận, mình viết ngắn hơn, nhưng vẫn giữ thói quen mở cuốn sổ ra. Điều quan trọng là sự đều đặn, không phải độ dài.', 'published', '2026-10-12 13:40:00'),
(4, 2, 'Bữa sáng nhanh cho ngày bận rộn', 'Vài gợi ý đơn giản để bắt đầu ngày mới mà không vội vàng.', 'Một bữa sáng gọn nhẹ không cần quá cầu kỳ. Mình thường chuẩn bị trứng, bánh mì và một ít rau từ tối hôm trước, sáng chỉ cần hâm lại hoặc áp chảo vài phút.

Nếu có thêm thời gian, mình pha một tách trà và ngồi yên vài phút trước khi bắt đầu công việc. Bữa sáng không chỉ để no, mà còn là khoảng đệm nhỏ trước một ngày dài.', 'published', '2026-10-08 10:05:00'),
(5, 5, 'Ghi chú về một chuyến đi sắp tới', 'Bản nháp vài điều cần chuẩn bị cho chuyến đi cuối tháng.', 'Mình đang lên danh sách những thứ cần mang cho chuyến đi cuối tháng. Danh sách không dài: một cuốn sổ, một cuốn sách, áo khoác mỏng và đôi giày đi bộ.

Vẫn còn vài tuần để sắp xếp. Mình muốn chuyến đi lần này nhẹ hơn, ít lịch trình hơn và dành nhiều thời gian hơn cho việc đi bộ.', 'draft', '2026-10-18 16:20:00');

INSERT INTO comments (id, post_id, user_id, body, created_at) VALUES
(1, 1, 3, 'Đà Lạt mùa này đẹp lắm, cảm ơn bạn đã chia sẻ.', '2026-10-21 09:10:00'),
(2, 1, 4, 'Mình cũng thích những chuyến đi không lịch trình như vậy.', '2026-10-21 15:45:00'),
(3, 2, 2, 'Đọc chậm thật sự giúp mình nhớ lâu hơn.', '2026-10-17 11:25:00');
INSERT INTO uploads (id, user_id, original_name, stored_name, mime_type, size_bytes, is_public, created_at) VALUES
(1, 1, 'huong-dan-lab.txt', 'huong-dan-lab.txt', 'text/plain', 465, 1, '2026-10-01 08:00:00'),
(2, 1, 'anh-mau.png', 'anh-mau.png', 'image/png', 24853, 1, '2026-10-01 08:05:00');

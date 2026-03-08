# doan-web-congty-tnhh-halinh
- Đồ án Phát triển Ứng dụng Web - Web thương mại điện tử Công ty TNHH Giải pháp Công nghệ Hà Linh.
- Đề tài: Xây dựng website thương mại điện tử (B2C) bán linh kiện máy tính (CPU, RAM, mainboard, v.v.) cho công ty Hà Linh.
- Đồ án tập trung phát triển một website thương mại điện tử (mô hình B2C) chuyên bán linh kiện máy tính (CPU, RAM, mainboard, card đồ họa, v.v.) dành cho khách hàng cá nhân và doanh nghiệp nhỏ, hỗ trợ chuyển đổi số cho Công ty TNHH Giải pháp Công nghệ Hà Linh – hiện đang kinh doanh chủ yếu offline và chưa có nền tảng trực tuyến.

# Tính năng chính:
- Trang chủ & Hiển thị sản phẩm: Hiển thị danh mục linh kiện nổi bật, sản phẩm khuyến mãi/hot.
- Trang chi tiết sản phẩm: Hiển thị thông tin chi tiết (tên, giá, mô tả, hình ảnh carousel Bootstrap), số lượng tồn kho, nút "Thêm vào giỏ hàng".
- Tìm kiếm & Lọc sản phẩm: Form tìm kiếm theo từ khóa/danh mục, kết quả load động bằng AJAX/JSON, hiển thị kết quả realtime mà không reload toàn trang.
- Giỏ hàng & Quy trình đặt hàng: Thêm/xóa/sửa số lượng sản phẩm trong giỏ, tính tổng tiền, form đặt hàng cơ bản (tên, địa chỉ, số điện thoại). Cập nhật giỏ hàng động bằng AJAX.
- Quản trị cơ bản:
  + Đăng nhập/đăng xuất admin
  + Quản lý danh mục: Thêm/sửa/xóa category.
  + Quản lý sản phẩm: Thêm/sửa/xóa sản phẩm (upload ảnh, nhập giá, mô tả, liên kết category)

# Yêu cầu kỹ thuật chính:
- Frontend: Sử dụng Bootstrap làm framework responsive (mobile-first), grid system, components như navbar, cards sản phẩm, carousel hình ảnh, table giỏ hàng.
- Backend: Viết theo hướng đối tượng (OOP) trong PHP, ưu tiên mô hình MVC (Model-View-Controller) để code sạch, dễ bảo trì. Sử dụng PDO kết nối MySQL với prepared statements chống SQL injection.
- Tích hợp AJAX (jQuery.ajax) kết hợp JSON để load dữ liệu động (tìm kiếm sản phẩm, cập nhật giỏ hàng không reload trang), webservice mức sử dụng (không tạo mới).
- Cơ sở dữ liệu: MySQL với các bảng chính: categories, products (id, name, price, description, image, category_id), users (quản trị), orders (giỏ hàng).
- Chức năng chính:
  + Khách hàng: Xem danh mục/sản phẩm, chi tiết sản phẩm, tìm kiếm AJAX, giỏ hàng, quy trình đặt hàng cơ bản.
  + Quản trị (admin): Login cơ bản, thêm/sửa/xóa danh mục, sản phẩm (quản trị mức cơ bản).

## Thành viên nhóm (Nhóm 3)
- **Nguyễn Phương Bảo Minh** (GitHub: BaoMinh1111) - Trưởng nhóm
- **Nguyễn Thị Hương Giang** (GitHub: Hương Giang) - Thành viên nhóm
- **Bùi Phạm Xuân Nghi** (GitHub: clarice-xn) - Thành viên nhóm
- **Phạm Hoàng Lâm Vũ** (GitHub: UEH.Woo) - Thành viên nhóm

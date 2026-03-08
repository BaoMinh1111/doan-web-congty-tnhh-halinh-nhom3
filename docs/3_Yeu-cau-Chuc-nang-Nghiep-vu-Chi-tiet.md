# 3. Yêu cầu Chức năng & Nghiệp vụ Chi tiết

## 1. Phân hệ Hiển thị & Duyệt sản phẩm
- Trang chủ: hiển thị sản phẩm nổi bật (lưới Bootstrap cards).
- Trang chi tiết sản phẩm: carousel hình ảnh, mô tả, giá, nút thêm giỏ hàng.
- Responsive trên mobile và desktop.

## 2. Phân hệ Tìm kiếm & Lọc sản phẩm
- Tìm kiếm theo từ khóa (AJAX tải động JSON từ ProductModel->search()).
- Lọc theo danh mục (CategoryModel) và khoảng giá.

## 3. Phân hệ Quản lý Giỏ hàng & Đặt hàng
- Thêm/cập nhật/xóa sản phẩm trong giỏ (SESSION + AJAX trả JSON).
- Xem giỏ hàng (bảng responsive).
- Nhập thông tin đặt hàng → tạo đơn (OrderModel->createOrder()), lưu chi tiết đơn (bảng order_details).

## 4. Phân hệ Quản trị Hệ thống
- Đăng nhập quản trị (UserModel->login() với mật khẩu mã hóa).
- Quản lý danh mục: thêm/sửa/xóa (CategoryModel).
- Quản lý sản phẩm: thêm/sửa/xóa, tải ảnh (ProductModel).
- Quản lý đơn hàng: xem danh sách, cập nhật trạng thái (OrderModel).

## 5. Yêu cầu Phi chức năng
- **Bảo mật**: Prepared statements (PDO) chống SQL injection, mã hóa mật khẩu (password_hash), chống XSS (htmlspecialchars).
- **Hiệu suất**: Kết nối cơ sở dữ liệu singleton, tải dữ liệu động bằng AJAX.
- **Khả năng sử dụng**: Giao diện responsive Bootstrap, thân thiện trên mobile.
- **Khả năng mở rộng**: Cấu trúc MVC dễ thêm tính năng mới.
- **Làm việc nhóm**: GitHub branch theo tính năng, trao đổi Teams, host chia sẻ cơ sở dữ liệu.
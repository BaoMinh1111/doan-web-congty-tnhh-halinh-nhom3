# 4. Đặc tả Kỹ thuật Hệ thống

## 1. Sơ đồ Quan hệ Thực thể (Entity – Relationship Diagram)
Xem file: docs/sơ đồ erd.png 
- Các thực thể chính: Category, Product, User, Order, OrderDetail.  
- Quan hệ: Category 1–n Product, User 1–n Order, Order 1–n OrderDetail, Product n–m Order (qua OrderDetail).  
- Khóa ngoại và thuộc tính đầy đủ (price_at_purchase, total_price, status…).

## 2. Sơ đồ Lớp (OOP Class Diagram)
Xem file: docs/sơ đồ oop.png  
**11 lớp chính**:

- **Database** (Singleton): quản lý kết nối PDO duy nhất.
- **BaseModel** (trừu tượng): lớp cha cho Model, có PDO và các hàm CRUD cơ bản.
- **ProductModel**, **CategoryModel**, **UserModel**, **OrderModel** (kế thừa BaseModel).
- **BaseController**: lớp cha cho Controller, có renderView(), jsonResponse().
- **HomeController**, **ProductController**, **CartController**, **AdminController** (kế thừa BaseController).

## 3. Sơ đồ Chức năng (Functional Diagram)
Xem file: docs/sơ đồ fd.png
- Các nhóm chức năng chính: Hiển thị và duyệt sản phẩm, Tìm kiếm & lọc sản phẩm, Quản lý giỏ hàng, Đặt hàng & quản lý đơn hàng, Quản trị hệ thống.  
- Quan hệ phân cấp: Hệ thống TMĐT Hà Linh Tech (cấp 0) phân rã thành 5 nhóm chức năng cấp 1, mỗi nhóm tiếp tục phân rã thành các chức năng chi tiết cấp 2 (ví dụ: "Hiển thị và duyệt sản phẩm" → Hiển thị trang chủ, Hiển thị danh mục sản phẩm, Xem chi tiết sản phẩm).  
- Chức năng chi tiết đầy đủ: Hiển thị trang chủ, Hiển thị danh mục sản phẩm, Xem chi tiết sản phẩm, Tìm kiếm theo từ khóa, Lọc theo danh mục, Lọc theo giá, Thêm sản phẩm vào giỏ, Cập nhật/xóa sản phẩm giỏ, Xem giỏ hàng, Nhập thông tin đặt hàng, Xác nhận & Tạo đơn hàng, Theo dõi trạng thái đơn hàng, Quản lý danh mục, Quản lý sản phẩm, Quản lý đơn hàng, Đăng nhập quản trị.

## 3. Các thành phần kỹ thuật chính
- **Frontend**: Bootstrap, components (navbar, cards, carousel, table), jQuery AJAX.
- **Backend**: PHP OOP, MVC tùy chỉnh, AJAX trả JSON.
- **Cơ sở dữ liệu**: MySQL, kết nối PDO singleton, prepared statements.
- **Cấu trúc thư mục**: /public (index.php, assets), /app (Models, Controllers, Views), /config (Database.php).
- **Triển khai**: XAMPP local → hosting miễn phí, minh chứng GitHub branch và commit.

## 4. Bảo mật & Hiệu suất
- Chống SQL injection: prepared statements.
- Chống XSS: htmlspecialchars khi hiển thị dữ liệu.
- Hiệu suất: singleton kết nối DB, tải dữ liệu động AJAX.

# 1. Tài liệu Giới thiệu Dự án

## 1. Tổng quan Dự án
Dự án là đồ án môn Phát triển Ứng dụng Web, với đề tài:  
**Xây dựng website thương mại điện tử cho Công ty TNHH Giải pháp Công nghệ Hà Linh**.

Công ty hiện đang kinh doanh linh kiện máy tính (CPU, RAM, mainboard, VGA, ổ cứng, nguồn, tản nhiệt…) chủ yếu theo hình thức bán hàng truyền thống (offline). Việc chưa có nền tảng trực tuyến khiến công ty bỏ lỡ cơ hội lớn trong thị trường thương mại điện tử Việt Nam với tốc độ tăng trưởng cao nhất Đông Nam Á, đặc biệt phân khúc linh kiện máy tính nhờ nhu cầu gaming và máy trạm AI.

## 2. Mục tiêu Kỹ thuật & Nghiệp vụ
**Mục tiêu nghiệp vụ**:
- Xây dựng website bán hàng theo mô hình B2C (doanh nghiệp đến cá nhân/doanh nghiệp nhỏ).
- Giúp công ty Hà Linh tiếp cận khách hàng rộng hơn, tăng doanh thu, quảng bá thương hiệu.
- Hỗ trợ quản lý kho hàng và đơn hàng cơ bản.

**Mục tiêu kỹ thuật**:
- Frontend: Sử dụng Bootstrap làm framework responsive (mobile-first), grid system, components như navbar, cards sản phẩm, carousel hình ảnh, table giỏ hàng.
- Backend: Viết theo hướng đối tượng (OOP) trong PHP, ưu tiên mô hình MVC (Model-View-Controller) để code sạch, dễ bảo trì. Sử dụng PDO kết nối MySQL với prepared statements chống SQL injection.
- Tích hợp AJAX (jQuery.ajax) kết hợp JSON để load dữ liệu động (tìm kiếm sản phẩm, cập nhật giỏ hàng không reload trang), webservice mức sử dụng (không tạo mới).
- Cơ sở dữ liệu: MySQL với các bảng chính: categories, products (id, name, price, description, image, category_id), users (quản trị), orders (giỏ hàng).
- Chức năng chính:
+ Khách hàng: Xem danh mục/sản phẩm, chi tiết sản phẩm, tìm kiếm AJAX, giỏ hàng, quy trình đặt hàng cơ bản.
+ Quản trị (admin): Login cơ bản, thêm/sửa/xóa danh mục, sản phẩm (quản trị mức cơ bản).

## 3. Kiến trúc Hệ thống (tổng quan)
- **Frontend**: HTML, CSS, JavaScript, jQuery, Bootstrap.
- **Backend**: PHP (OOP và MVC tùy chỉnh), 11 lớp chính (Database Singleton, BaseModel và 4 Model, BaseController và 4 Controller).
- **Cơ sở dữ liệu**: MySQL (bảng products, categories, users, orders, order_details).
- **Môi trường**: Phát triển cục bộ XAMPP, triển khai hosting miễn phí (InfinityFree).

## 4. Định hướng Phát triển (tương lai)
- Tích hợp thanh toán trực tuyến (VNPAY, Momo).
- Thêm tính năng gợi ý sản phẩm dựa trên lịch sử xem.
- Phát triển ứng dụng di động hoặc tích hợp chatbot hỗ trợ khách hàng.
- Mở rộng quản lý kho hàng thực tế và theo dõi vận chuyển.
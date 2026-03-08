# 2. Tổng quan & Câu chuyện Người dùng (User Stories)

## 1. Giới thiệu
Đồ án thuộc chủ đề Thương mại điện tử.  
Thương mại điện tử là hoạt động mua bán hàng hóa/dịch vụ qua Internet. Mô hình B2C phù hợp với Công ty Hà Linh (bán linh kiện máy tính trực tiếp cho cá nhân và doanh nghiệp nhỏ).  

**Lợi ích chính**:
- Tăng doanh thu và tiếp cận khách hàng rộng rãi.
- Giảm chi phí kho bãi và quảng cáo truyền thống.
- Cập nhật sản phẩm nhanh chóng, theo xu hướng gaming/AI workstation.

**Thách thức**:
- Bảo mật thông tin khách hàng.
- Quản lý vận chuyển và logistics.
- Cạnh tranh từ các sàn lớn (Shopee, Lazada).

**Công cụ sử dụng**:
- Giao diện: Bootstrap (responsive), jQuery AJAX.
- Server: PHP OOP và MVC tùy chỉnh.
- Cơ sở dữ liệu: MySQL và PDO.

## 2. Các nhóm người dùng
- **Khách hàng** (người mua linh kiện): duyệt sản phẩm, tìm kiếm, đặt hàng.
- **Quản trị viên** (admin công ty): quản lý danh mục, sản phẩm, đơn hàng.

## 3. Câu chuyện Người dùng (User Stories)
### 3.1. Dành cho Khách hàng:
- **US01**: Là khách hàng, tôi muốn xem trang chủ với sản phẩm nổi bật
- **US02**: Là khách hàng, tôi muốn tìm kiếm sản phẩm bằng từ khóa 
- **US03**: Là khách hàng, tôi muốn lọc sản phẩm theo danh mục và khoảng giá
- **US04**: Là khách hàng, tôi muốn thêm sản phẩm vào giỏ hàng
- **US05**: Là khách hàng, tôi muốn cập nhật/xóa sản phẩm trong giỏ và đặt hàng
### 3.2. Dành cho Quản trị viên:
- **US06**: Là quản trị viên, tôi muốn đăng nhập an toàn
- **US07**: Là quản trị viên, tôi muốn thêm/sửa/xóa danh mục sản phẩm
- **US08**: Là quản trị viên, tôi muốn thêm/sửa/xóa sản phẩm
- **US09**: Là quản trị viên, tôi muốn xem và quản lý đơn hàng

## 4. Phạm vi Nghiệp vụ Chính
- Hiển thị, tìm kiếm, lọc, chi tiết sản phẩm.
- Giỏ hàng và đặt hàng cơ bản.
- Quản trị danh mục, sản phẩm, đơn hàng (cơ bản).
- Không bao gồm: thanh toán trực tuyến, đăng ký khách hàng phức tạp, quản lý kho vật lý.
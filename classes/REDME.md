#Cart & Promotion System (MVC PHP)

 # Kiến trúc
- MVC Pattern
- Service Layer (CartService, OrderService)
- Entity Layer (ProductEntity, PromotionEntity)

# Chức năng
- Thêm sản phẩm vào giỏ hàng
- Xóa sản phẩm khỏi giỏ
- Tính tổng tiền
- Áp dụng mã khuyến mãi

# Nguyên tắc thiết kế
- CartService: xử lý giỏ hàng
- OrderService: xử lý thanh toán & khuyến mãi
- Controller không chứa business logic

# API
- cart_add
- cart_remove
- cart_total

# Điểm nổi bật
- Validate dữ liệu đầy đủ
- Kiểm tra tồn kho
- Áp dụng promotion linh hoạt
- Xử lý exception rõ ràng

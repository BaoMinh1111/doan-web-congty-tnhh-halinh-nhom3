DELIMITER $$

-- Chức năng: Lấy tất cả sản phẩm thuộc 1 danh mục cụ thể
CREATE PROCEDURE GetProductsByCategory(IN cat_id INT)
BEGIN
    SELECT 
        p.id,
        p.name,
        p.price,
        p.stock,
        p.image,
        p.description,
        c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = cat_id
    ORDER BY p.price ASC;
END $$

-- Chức năng: Tìm sản phẩm theo tên hoặc mô tả
CREATE PROCEDURE SearchProducts(IN keyword VARCHAR(100))
BEGIN
    SELECT 
        p.id,
        p.name,
        p.price,
        p.stock,
        c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.name LIKE CONCAT('%', keyword, '%')
       OR p.description LIKE CONCAT('%', keyword, '%')
    ORDER BY p.created_at DESC;
END $$

-- Chức năng: Xem lịch sử đơn hàng của khách
CREATE PROCEDURE GetOrdersByCustomer(IN customer_id INT)
BEGIN
    SELECT 
        o.id,
        o.total_price,
        o.status,
        o.created_at,
        o.customer_name,
        o.customer_address,
        p.code AS promotion_code
    FROM orders o
    LEFT JOIN promotions p ON o.promotion_id = p.id
    WHERE o.customer_id = customer_id
    ORDER BY o.created_at DESC;
END $$

-- Chức năng: Lấy danh sách sản phẩm trong 1 đơn hàng
CREATE PROCEDURE GetOrderDetails(IN order_id INT)
BEGIN
    SELECT 
        od.id,
        od.quantity,
        od.price_at_purchase,
        (od.quantity * od.price_at_purchase) AS subtotal,
        p.name AS product_name,
        p.image
    FROM orderdetails od
    JOIN products p ON od.product_id = p.id
    WHERE od.order_id = order_id;
END $$

-- Chức năng: Thống kê sản phẩm bán được nhiều nhất
CREATE PROCEDURE GetTopSellingProducts(IN top_limit INT)
BEGIN
    SELECT 
        p.id,
        p.name,
        p.price,
        SUM(od.quantity) AS total_sold,
        SUM(od.quantity * od.price_at_purchase) AS total_revenue
    FROM orderdetails od
    JOIN products p ON od.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT top_limit;
END $$

-- Chức năng: Thống kê tổng doanh thu và số đơn trong tháng
CREATE PROCEDURE GetRevenueByMonth(IN year INT, IN month INT)
BEGIN
    SELECT 
        SUM(total_price) AS total_revenue,
        COUNT(*) AS total_orders
    FROM orders
    WHERE YEAR(created_at) = year 
      AND MONTH(created_at) = month;
END $$

-- Chức năng: Kiểm tra 1 mã khuyến mãi có dùng được không
CREATE PROCEDURE CheckPromotionValid(IN promo_code VARCHAR(50))
BEGIN
    SELECT 
        id,
        code,
        type,
        value,
        min_order_amount,
        used_count,
        max_uses,
        start_date,
        end_date,
        active
    FROM promotions
    WHERE code = promo_code
      AND active = 1
      AND (end_date IS NULL OR end_date >= CURDATE())
      AND (max_uses IS NULL OR used_count < max_uses);
END $$

-- Chức năng: Giảm giá đơn hàng theo mã khuyến mãi và cập nhật used_count
CREATE PROCEDURE ApplyPromotionToOrder(IN order_id INT, IN promo_code VARCHAR(50))
BEGIN
    DECLARE discount DECIMAL(10,2);
    DECLARE promo_id INT;

    -- Tìm khuyến mãi hợp lệ
    SELECT id, value INTO promo_id, discount
    FROM promotions
    WHERE code = promo_code
      AND active = 1
      AND (end_date IS NULL OR end_date >= CURDATE())
      AND (max_uses IS NULL OR used_count < max_uses)
    LIMIT 1;

    IF promo_id IS NOT NULL THEN
        -- Áp dụng giảm giá
        UPDATE orders
        SET total_price = total_price * (1 - discount / 100),
            promotion_id = promo_id
        WHERE id = order_id;

        -- Tăng số lần sử dụng
        UPDATE promotions
        SET used_count = used_count + 1
        WHERE id = promo_id;
    END IF;
END $$

-- Chức năng: Giảm quantity trong inventories và products khi bán hàng
CREATE PROCEDURE UpdateStockAfterSale(IN product_id INT, IN sold_quantity INT)
BEGIN
    -- Giảm tồn kho trong inventories
    UPDATE inventories
    SET quantity = quantity - sold_quantity,
        last_updated = CURRENT_TIMESTAMP
    WHERE product_id = product_id;

    -- Giảm stock tổng trong products
    UPDATE products
    SET stock = stock - sold_quantity
    WHERE id = product_id;
END $$

-- Chức năng: Lọc đơn hàng theo trạng thái (pending, shipped, cancelled...)
CREATE PROCEDURE GetOrdersByStatus(IN order_status VARCHAR(20))
BEGIN
    SELECT 
        o.id,
        o.total_price,
        o.status,
        o.created_at,
        c.name AS customer_name
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.status = order_status
    ORDER BY o.created_at DESC;
END $$

-- Chức năng: Đếm tổng số đơn và tổng tiền của 1 khách
CREATE PROCEDURE GetCustomerOrderStats(IN customer_id INT)
BEGIN
    SELECT 
        COUNT(*) AS total_orders,
        SUM(total_price) AS total_spent
    FROM orders
    WHERE customer_id = customer_id;
END $$

-- Chức năng: Cảnh báo sản phẩm sắp hết hàng
CREATE PROCEDURE GetLowStockProducts(IN threshold INT)
BEGIN
    SELECT 
        p.id,
        p.name,
        p.stock,
        c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.stock <= threshold
    ORDER BY p.stock ASC;
END $$

DELIMITER ;
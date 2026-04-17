DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `ApplyPromotionToOrder`(IN p_order_id INT, IN p_promo_code VARCHAR(50))
BEGIN
    DECLARE v_discount_val DECIMAL(10,2);
    DECLARE v_promo_id INT;
    DECLARE v_promo_type VARCHAR(20);
    DECLARE v_min_amount DECIMAL(10,2);
    DECLARE v_current_total DECIMAL(12,2);
    DECLARE v_order_status VARCHAR(50);
    DECLARE v_existing_promo_id INT;

    -- 1. Lấy thông tin đơn hàng hiện tại
    SELECT total_price, status, promotion_id 
    INTO v_current_total, v_order_status, v_existing_promo_id
    FROM orders 
    WHERE id = p_order_id;

    -- KIỂM TRA A: Trạng thái đơn hàng (Chỉ cho phép khi đang pending)
    IF v_order_status != 'pending' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Chỉ có thể áp dụng mã cho đơn hàng đang chờ xử lý (pending).';
    END IF;

    -- KIỂM TRA B: Đã áp mã trước đó chưa
    IF v_existing_promo_id IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Đơn hàng này đã được áp dụng một mã giảm giá trước đó.';
    END IF;

    -- 2. Tìm mã khuyến mãi hợp lệ
    SELECT id, value, type, min_order_amount 
    INTO v_promo_id, v_discount_val, v_promo_type, v_min_amount
    FROM promotions
    WHERE code = p_promo_code AND active = 1
      AND (end_date IS NULL OR end_date >= CURDATE())
      AND (max_uses IS NULL OR used_count < max_uses)
    LIMIT 1;

    -- KIỂM TRA C: Mã tồn tại/Hợp lệ
    IF v_promo_id IS NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Mã giảm giá không hợp lệ, hết hạn hoặc đã hết lượt dùng.';
    END IF;

    -- KIỂM TRA D: Giá trị tối thiểu
    IF v_current_total < v_min_amount THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Đơn hàng chưa đạt giá trị tối thiểu để sử dụng mã này.';
    END IF;

    -- 3. Thực hiện giảm giá (Bảo vệ giá trị >= 0)
    IF v_promo_type = 'percentage' THEN
        UPDATE orders 
        SET total_price = GREATEST(0, total_price * (1 - v_discount_val / 100)), 
            promotion_id = v_promo_id 
        WHERE id = p_order_id;
    ELSE 
        UPDATE orders 
        SET total_price = GREATEST(0, total_price - v_discount_val), 
            promotion_id = v_promo_id 
        WHERE id = p_order_id;
    END IF;

    -- 4. Cập nhật lượt dùng mã
    UPDATE promotions SET used_count = used_count + 1 WHERE id = v_promo_id;

END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetCustomerOrderStats`(IN p_customer_id INT)
BEGIN
    SELECT COUNT(*) AS total_orders, SUM(total_price) AS total_spent
    FROM orders
    WHERE customer_id = p_customer_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetProductsByCategory`(IN p_cat_id INT)
BEGIN
    SELECT p.id, p.name, p.price, p.stock, p.image, p.description, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.category_id = p_cat_id
    ORDER BY p.price ASC;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RefundPromotion`(IN p_order_id INT)
BEGIN
    DECLARE v_promo_id INT;
    DECLARE v_order_status VARCHAR(50);

    -- 1. Lấy thông tin mã giảm giá và trạng thái đơn hàng
    SELECT promotion_id, status INTO v_promo_id, v_order_status 
    FROM orders 
    WHERE id = p_order_id;

    -- KIỂM TRA: Chỉ hoàn mã nếu đơn hàng bị hủy (cancelled)
    -- Và đơn hàng đó THỰC SỰ có dùng mã (v_promo_id không NULL)
    IF v_order_status = 'cancelled' AND v_promo_id IS NOT NULL THEN
        
        -- A. Giảm số lần đã sử dụng trong bảng promotions
        -- Dùng GREATEST để đảm bảo used_count không bị âm (lỗi logic hiếm gặp)
        UPDATE promotions 
        SET used_count = GREATEST(0, used_count - 1) 
        WHERE id = v_promo_id;

        -- B. Gỡ bỏ liên kết mã giảm giá khỏi đơn hàng để tránh hoàn mã 2 lần
        UPDATE orders 
        SET promotion_id = NULL 
        WHERE id = p_order_id;

    ELSEIF v_order_status != 'cancelled' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Chỉ có thể hoàn mã khi trạng thái đơn hàng là đã hủy (cancelled).';
    END IF;

END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RestoreStockAfterCancel`(IN p_order_id INT)
BEGIN
    -- Sử dụng Cursor hoặc vòng lặp để hoàn kho cho TẤT CẢ sản phẩm trong đơn hàng đó
    -- Ở mức độ đồ án đơn giản, bạn có thể thực hiện hoàn kho từng sản phẩm từ PHP
    -- Hoặc dùng lệnh UPDATE kết hợp JOIN như sau:
    
    UPDATE products p
    JOIN orderdetails od ON p.id = od.product_id
    SET p.stock = p.stock + od.quantity
    WHERE od.order_id = p_order_id;

    UPDATE inventories i
    JOIN orderdetails od ON i.product_id = od.product_id
    SET i.quantity = i.quantity + od.quantity, i.last_updated = CURRENT_TIMESTAMP
    WHERE od.order_id = p_order_id;
END$$
DELIMITER ;

DELIMITER $$
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateStockAfterSale`(IN p_prod_id INT, IN p_sold_qty INT)
BEGIN
    UPDATE inventories SET quantity = quantity - p_sold_qty, last_updated = CURRENT_TIMESTAMP WHERE product_id = p_prod_id;
    UPDATE products SET stock = stock - p_sold_qty WHERE id = p_prod_id;
END$$
DELIMITER ;

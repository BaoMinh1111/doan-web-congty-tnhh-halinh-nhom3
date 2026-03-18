<?php

/**
 * Class SessionHelper
 *
 * Lớp tiện ích tĩnh (static class) quản lý session một cách thống nhất.
 * Xử lý 2 nhóm chức năng chính: giỏ hàng (cart) và trạng thái đăng nhập (login).
 * Không kế thừa và không cần khởi tạo — gọi trực tiếp qua tên lớp.
 *
 * Cách dùng điển hình:
 *   SessionHelper::addToCart(['product_id' => 1, 'quantity' => 2, 'price' => 34990000]);
 *   $total = SessionHelper::getCartTotal();
 *   if (SessionHelper::isLoggedIn()) { ... }
 *
 * Lưu ý: AuthService chịu trách nhiệm ghi session đăng nhập.
 * SessionHelper chỉ ĐỌC session đăng nhập, không tự ghi.
 *
 * @package App\Helpers
 * @author  Ha Linh Technology Solutions
 */
class SessionHelper
{
    // HẰNG SỐ – khoá lưu trong $_SESSION

    /**
     * Key lưu giỏ hàng trong session.
     */
    private const KEY_CART    = 'cart';

    /**
     * Key lưu ID người dùng — phải khớp với AuthService::SESSION_USER_ID.
     */
    private const KEY_USER_ID = 'auth_user_id';

    /**
     * Key lưu role người dùng — phải khớp với AuthService::SESSION_ROLE.
     */
    private const KEY_ROLE    = 'auth_role';


    // KHỞI ĐỘNG SESSION

    /**
     * Đảm bảo session đã được khởi động trước mọi thao tác.
     * Được gọi nội bộ ở đầu mỗi method.
     *
     * @return void
     */
    private static function ensureStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }


    // GIỎ HÀNG – CART

    /**
     * Lấy toàn bộ giỏ hàng từ session.
     * Mỗi phần tử là mảng: ['product_id' => int, 'quantity' => int, 'price' => float].
     * Key của mảng trả về là product_id.
     *
     * Cách dùng:
     *   $cart = SessionHelper::getCart();
     *   foreach ($cart as $productId => $item) { ... }
     *
     * @return array Mảng giỏ hàng, key là product_id. Rỗng nếu chưa có.
     */
    public static function getCart(): array
    {
        self::ensureStarted();
        return $_SESSION[self::KEY_CART] ?? [];
    }

    /**
     * Ghi đè toàn bộ giỏ hàng bằng mảng mới.
     * Dùng khi CartService cần cập nhật lại toàn bộ giỏ sau khi xử lý nghiệp vụ.
     *
     * Cách dùng:
     *   SessionHelper::setCart($updatedCart);
     *
     * @param  array $cart Mảng giỏ hàng mới, key là product_id.
     * @return void
     */
    public static function setCart(array $cart): void
    {
        self::ensureStarted();
        $_SESSION[self::KEY_CART] = $cart;
    }

    /**
     * Thêm hoặc cập nhật số lượng một sản phẩm trong giỏ hàng.
     * Nếu sản phẩm đã có trong giỏ → cộng dồn số lượng.
     * Nếu chưa có → thêm mới vào giỏ.
     *
     * Cách dùng:
     *   SessionHelper::addToCart([
     *       'product_id' => 1,
     *       'quantity'   => 2,
     *       'price'      => 34990000.00,
     *   ]);
     *
     * @param  array $item Mảng thông tin sản phẩm.
     *                     Bắt buộc có key: product_id (int), quantity (int), price (float).
     * @return void
     * @throws InvalidArgumentException Nếu thiếu key bắt buộc hoặc quantity <= 0.
     */
    public static function addToCart(array $item): void
    {
        self::ensureStarted();

        if (empty($item['product_id']) || !isset($item['quantity']) || !isset($item['price'])) {
            throw new InvalidArgumentException(
                'Item giỏ hàng phải có đủ các key: product_id, quantity, price.'
            );
        }

        $productId = (int)   $item['product_id'];
        $quantity  = (int)   $item['quantity'];
        $price     = (float) $item['price'];

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Số lượng sản phẩm phải lớn hơn 0.');
        }

        $cart = self::getCart();

        if (isset($cart[$productId])) {
            // Sản phẩm đã tồn tại → cộng dồn số lượng
            $cart[$productId]['quantity'] += $quantity;
        } else {
            // Thêm mới vào giỏ
            $cart[$productId] = [
                'product_id' => $productId,
                'quantity'   => $quantity,
                'price'      => $price,
            ];
        }

        $_SESSION[self::KEY_CART] = $cart;
    }

    /**
     * Cập nhật số lượng của một sản phẩm cụ thể trong giỏ.
     * Nếu quantity = 0 → xoá sản phẩm khỏi giỏ.
     * Nếu product_id không tồn tại trong giỏ → bỏ qua, không báo lỗi.
     *
     * Cách dùng:
     *   SessionHelper::updateCartItem(1, 3); // cập nhật sp id=1 thành 3 cái
     *   SessionHelper::updateCartItem(1, 0); // xoá sp id=1 khỏi giỏ
     *
     * @param  int $productId
     * @param  int $quantity  Số lượng mới (0 = xoá khỏi giỏ).
     * @return void
     * @throws InvalidArgumentException Nếu quantity < 0.
     */
    public static function updateCartItem(int $productId, int $quantity): void
    {
        self::ensureStarted();

        if ($quantity < 0) {
            throw new InvalidArgumentException('Số lượng không được âm.');
        }

        $cart = self::getCart();

        if (!isset($cart[$productId])) {
            return;
        }

        if ($quantity === 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId]['quantity'] = $quantity;
        }

        $_SESSION[self::KEY_CART] = $cart;
    }

    /**
     * Xoá một sản phẩm khỏi giỏ hàng theo product_id.
     *
     * @param  int $productId
     * @return void
     */
    public static function removeFromCart(int $productId): void
    {
        self::ensureStarted();
        $cart = self::getCart();
        unset($cart[$productId]);
        $_SESSION[self::KEY_CART] = $cart;
    }

    /**
     * Xoá toàn bộ giỏ hàng (dùng sau khi đặt hàng thành công).
     *
     * Cách dùng:
     *   SessionHelper::clearCart();
     *
     * @return void
     */
    public static function clearCart(): void
    {
        self::ensureStarted();
        $_SESSION[self::KEY_CART] = [];
    }

    /**
     * Đếm tổng số lượng sản phẩm trong giỏ (tính theo quantity, không phải số loại).
     * Dùng để hiển thị badge số lượng trên icon giỏ hàng.
     *
     * @return int
     */
    public static function getCartCount(): int
    {
        $cart = self::getCart();
        return array_sum(array_column($cart, 'quantity'));
    }

    /**
     * Tính tổng tiền giỏ hàng (price × quantity cho từng sản phẩm).
     *
     * Cách dùng:
     *   $total = SessionHelper::getCartTotal();
     *
     * @return float Tổng tiền. 0.0 nếu giỏ rỗng.
     */
    public static function getCartTotal(): float
    {
        $cart  = self::getCart();
        $total = 0.0;

        foreach ($cart as $item) {
            $total += (float) $item['price'] * (int) $item['quantity'];
        }

        return $total;
    }


    // TRẠNG THÁI ĐĂNG NHẬP – LOGIN

    /**
     * Kiểm tra người dùng có đang đăng nhập không.
     * Chỉ kiểm tra key có tồn tại — AuthService chịu trách nhiệm kiểm tra hết hạn.
     *
     * Cách dùng:
     *   if (!SessionHelper::isLoggedIn()) { header('Location: /login'); exit; }
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        self::ensureStarted();
        return !empty($_SESSION[self::KEY_USER_ID]);
    }

    /**
     * Kiểm tra người dùng đang đăng nhập có phải Admin không.
     *
     * Cách dùng:
     *   if (!SessionHelper::isAdmin()) { header('Location: /403'); exit; }
     *
     * @return bool
     */
    public static function isAdmin(): bool
    {
        self::ensureStarted();
        return self::isLoggedIn() && ($_SESSION[self::KEY_ROLE] ?? '') === 'admin';
    }
}

/* Các vấn đề cần sửa:
* key session hard-code trùng với AuthService nhưng không liên kết
* addToCart() không cập nhật price khi sản phẩm đã tồn tại: Nếu admin thay đổi giá sản phẩm trong lúc khách đang có trong giỏ, lần thêm sau vẫn giữ giá cũ.
* isLoggedIn() và isAdmin() không kiểm tra hết hạn session
* Thiếu getCartItems() trả về mảng có thể dùng được ngay trong View
*/

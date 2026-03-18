<?php

/**
 * Class ValidatorHelper
 *
 * Lớp tiện ích tĩnh (static class) cung cấp các hàm validate và sanitize input chung.
 * Được dùng trong Service và Controller để kiểm tra dữ liệu trước khi xử lý nghiệp vụ.
 * Không kế thừa và không cần khởi tạo — gọi trực tiếp qua tên lớp.
 *
 * Quy ước trả về:
 *   - true  → hợp lệ.
 *   - string → thông báo lỗi cụ thể (không hợp lệ).
 *
 * Cách dùng điển hình:
 *   $result = ValidatorHelper::validateEmail('lananh@gmail.com');
 *   if ($result !== true) { echo $result; } // in thông báo lỗi
 *
 *   $clean = ValidatorHelper::sanitizeInput($_POST['name']);
 *
 * @package App\Helpers
 * @author  Ha Linh Technology Solutions
 */
class ValidatorHelper
{
    // VALIDATE – kiểm tra tính hợp lệ

    /**
     * Kiểm tra trường bắt buộc không được rỗng.
     *
     * Cách dùng:
     *   $result = ValidatorHelper::validateRequired($_POST['username'], 'Tên đăng nhập');
     *   if ($result !== true) { echo $result; }
     *
     * @param  mixed  $value     Giá trị cần kiểm tra.
     * @param  string $fieldName Tên trường hiển thị trong thông báo lỗi.
     * @return true|string       true nếu hợp lệ, chuỗi lỗi nếu không.
     */
    public static function validateRequired(mixed $value, string $fieldName): true|string
    {
        if ($value === null || trim((string) $value) === '') {
            return "{$fieldName} không được để trống.";
        }
        return true;
    }

    /**
     * Kiểm tra địa chỉ email hợp lệ.
     *
     * Cách dùng:
     *   $result = ValidatorHelper::validateEmail('lananh@gmail.com');
     *
     * @param  mixed  $email
     * @return true|string
     */
    public static function validateEmail(mixed $email): true|string
    {
        if (empty(trim((string) $email))) {
            return 'Email không được để trống.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email không hợp lệ.';
        }
        if (mb_strlen((string) $email) > 100) {
            return 'Email không được vượt quá 100 ký tự.';
        }
        return true;
    }

    /**
     * Kiểm tra giá sản phẩm hợp lệ: là số và lớn hơn 0.
     *
     * Cách dùng:
     *   $result = ValidatorHelper::validatePrice(34990000);
     *
     * @param  mixed  $price
     * @return true|string
     */
    public static function validatePrice(mixed $price): true|string
    {
        if (!is_numeric($price)) {
            return 'Giá phải là một số hợp lệ.';
        }
        if ((float) $price <= 0) {
            return 'Giá phải lớn hơn 0.';
        }
        return true;
    }

    /**
     * Kiểm tra số lượng hợp lệ: là số nguyên và lớn hơn 0.
     *
     * Cách dùng:
     *   $result = ValidatorHelper::validateQuantity(2);
     *
     * @param  mixed  $quantity
     * @return true|string
     */
    public static function validateQuantity(mixed $quantity): true|string
    {
        if (!is_numeric($quantity) || (int) $quantity != $quantity) {
            return 'Số lượng phải là số nguyên.';
        }
        if ((int) $quantity <= 0) {
            return 'Số lượng phải lớn hơn 0.';
        }
        return true;
    }

    /**
     * Kiểm tra độ dài chuỗi nằm trong khoảng cho phép.
     *
     * Cách dùng:
     *   $result = ValidatorHelper::validateLength($username, 'Tên đăng nhập', 3, 50);
     *
     * @param  mixed  $value     Giá trị cần kiểm tra.
     * @param  string $fieldName Tên trường hiển thị trong thông báo lỗi.
     * @param  int    $min       Độ dài tối thiểu.
     * @param  int    $max       Độ dài tối đa.
     * @return true|string
     */
    public static function validateLength(mixed $value, string $fieldName, int $min, int $max): true|string
    {
        $len = mb_strlen((string) $value);
        if ($len < $min) {
            return "{$fieldName} phải có ít nhất {$min} ký tự.";
        }
        if ($len > $max) {
            return "{$fieldName} không được vượt quá {$max} ký tự.";
        }
        return true;
    }

    /**
     * Kiểm tra số điện thoại Việt Nam hợp lệ.
     * Chấp nhận định dạng: 10 chữ số, bắt đầu bằng 0.
     *
     * Cách dùng:
     *   $result = ValidatorHelper::validatePhone('0987654321');
     *
     * @param  mixed  $phone
     * @return true|string
     */
    public static function validatePhone(mixed $phone): true|string
    {
        if (empty(trim((string) $phone))) {
            return 'Số điện thoại không được để trống.';
        }
        if (!preg_match('/^0[0-9]{9}$/', (string) $phone)) {
            return 'Số điện thoại không hợp lệ (phải có 10 chữ số, bắt đầu bằng 0).';
        }
        return true;
    }

    /**
     * Kiểm tra giá trị có nằm trong danh sách cho phép không.
     * Dùng để validate các trường enum như status, role, type.
     *
     * Cách dùng:
     *   $result = ValidatorHelper::validateInList($status, 'Trạng thái', ['pending', 'shipped', 'delivered']);
     *
     * @param  mixed  $value     Giá trị cần kiểm tra.
     * @param  string $fieldName Tên trường hiển thị trong thông báo lỗi.
     * @param  array  $allowed   Danh sách giá trị hợp lệ.
     * @return true|string
     */
    public static function validateInList(mixed $value, string $fieldName, array $allowed): true|string
    {
        if (!in_array($value, $allowed, true)) {
            return "{$fieldName} phải là một trong các giá trị: " . implode(', ', $allowed) . '.';
        }
        return true;
    }


    // SANITIZE – làm sạch dữ liệu đầu vào

    /**
     * Làm sạch một chuỗi hoặc mảng chuỗi đầu vào từ người dùng.
     * Áp dụng: trim() + htmlspecialchars() để chống XSS.
     * Không dùng cho mật khẩu (không nên encode mật khẩu trước khi hash).
     *
     * Cách dùng:
     *   $name  = ValidatorHelper::sanitizeInput($_POST['name']);         // chuỗi
     *   $data  = ValidatorHelper::sanitizeInput($_POST);                 // mảng
     *
     * @param  string|array $data Chuỗi hoặc mảng cần làm sạch.
     * @return string|array       Dữ liệu đã được làm sạch, cùng kiểu với đầu vào.
     */
    public static function sanitizeInput(string|array $data): string|array
    {
        if (is_array($data)) {
            return array_map(
                fn($item) => is_string($item)
                    ? htmlspecialchars(trim($item), ENT_QUOTES, 'UTF-8')
                    : $item,
                $data
            );
        }

        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Làm sạch và ép kiểu về số nguyên.
     * Dùng cho các trường ID, quantity từ GET/POST.
     *
     * Cách dùng:
     *   $id = ValidatorHelper::sanitizeInt($_GET['id']); // trả 0 nếu không hợp lệ
     *
     * @param  mixed $value
     * @return int          0 nếu không phải số hợp lệ.
     */
    public static function sanitizeInt(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Làm sạch và ép kiểu về số thực.
     * Dùng cho các trường price, total từ GET/POST.
     *
     * Cách dùng:
     *   $price = ValidatorHelper::sanitizeFloat($_POST['price']);
     *
     * @param  mixed $value
     * @return float        0.0 nếu không hợp lệ.
     */
    public static function sanitizeFloat(mixed $value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }


    // TIỆN ÍCH – validate nhiều trường cùng lúc

    /**
     * Chạy nhiều rule validate cùng lúc và trả về tất cả lỗi.
     * Mỗi rule là một callable trả về true|string.
     *
     * Cách dùng:
     *   $errors = ValidatorHelper::validateAll([
     *       'email'    => fn() => ValidatorHelper::validateEmail($_POST['email']),
     *       'price'    => fn() => ValidatorHelper::validatePrice($_POST['price']),
     *       'username' => fn() => ValidatorHelper::validateRequired($_POST['username'], 'Tên đăng nhập'),
     *   ]);
     *   if (!empty($errors)) { // hiển thị lỗi }
     *
     * @param  array $rules Mảng [fieldName => callable] mỗi callable trả true|string.
     * @return array        Mảng lỗi [fieldName => message]. Rỗng nếu tất cả hợp lệ.
     */
    public static function validateAll(array $rules): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $result = $rule();
            if ($result !== true) {
                $errors[$field] = $result;
            }
        }
        return $errors;
    }
}
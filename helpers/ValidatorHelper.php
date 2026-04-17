<?php
class ValidatorHelper {
    /**
     * Làm sạch dữ liệu đầu vào (Xóa khoảng trắng, loại bỏ ký tự đặc biệt)
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
        } else {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }

    /**
     * Kiểm tra các trường bắt buộc không được để trống
     */
    public static function required($fields, $data) {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = ucfirst($field) . " không được để trống.";
            }
        }
        return $errors;
    }

    /**
     * Kiểm tra định dạng Email
     */
    public static function isEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
<?php
class UploadHelper {
    private static $uploadDir = "assets/images/products/";
    private static $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private static $maxSize = 2 * 1024 * 1024; // 2MB

    /**
     * Xử lý upload ảnh sản phẩm
     * @return string|array Tên file mới nếu thành công, hoặc mảng lỗi
     */
    public static function uploadProductImage($file) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => "Không có file nào được tải lên hoặc lỗi upload."];
        }

        // 1. Kiểm tra định dạng
        if (!in_array($file['type'], self::$allowedTypes)) {
            return ['error' => "Định dạng file không hỗ trợ. Chỉ nhận JPG, PNG, GIF, WEBP."];
        }

        // 2. Kiểm tra dung lượng
        if ($file['size'] > self::$maxSize) {
            return ['error' => "Dung lượng file quá lớn (Tối đa 2MB)."];
        }

        // 3. Tạo tên file duy nhất (Tránh trùng lặp)
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('product_', true) . '.' . $extension;
        $targetPath = self::$uploadDir . $newFileName;

        // 4. Di chuyển file vào thư mục lưu trữ
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $newFileName;
        }

        return ['error' => "Không thể lưu file vào server."];
    }
}
<?php

/**
 * Class UploadHelper
 *
 * Lớp tiện ích tĩnh (static class) xử lý upload ảnh sản phẩm.
 * Validate file trước khi lưu, tạo tên file duy nhất, xoá file cũ khi cập nhật.
 * Không kế thừa và không cần khởi tạo — gọi trực tiếp qua tên lớp.
 *
 * Quy ước:
 *   - DB chỉ lưu tên file (VD: 'iphone16pm.jpg'), không lưu đường dẫn đầy đủ.
 *   - File vật lý lưu tại: BASE_PATH/public/uploads/products/
 *   - Tên file được sinh tự động bằng uniqid() để tránh trùng lặp.
 *
 * Cách dùng điển hình:
 *   $result = UploadHelper::uploadProductImage($_FILES['image']);
 *   if ($result['success']) {
 *       $filename = $result['filename']; // lưu vào DB
 *   } else {
 *       echo $result['message'];
 *   }
 *
 * @package App\Helpers
 * @author  Ha Linh Technology Solutions
 */
class UploadHelper
{
    // HẰNG SỐ – cấu hình upload

    /**
     * Các MIME type được phép upload.
     * Chỉ chấp nhận ảnh phổ biến cho sản phẩm.
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Các extension được phép (kiểm tra song song với MIME type).
     */
    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    /**
     * Kích thước file tối đa: 2MB (tính bằng byte).
     */
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;

    /**
     * Thư mục lưu ảnh sản phẩm, tương đối từ BASE_PATH/public/.
     */
    private const UPLOAD_DIR = 'uploads/products/';


    // LẤY ĐƯỜNG DẪN

    /**
     * Trả về đường dẫn tuyệt đối đến thư mục upload trên server.
     * Dùng BASE_PATH nếu đã định nghĩa, fallback về dirname(__DIR__).
     *
     * @return string Đường dẫn tuyệt đối, có dấu / cuối.
     */
    private static function getUploadPath(): string
    {
        $base = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__);
        return $base . '/public/' . self::UPLOAD_DIR;
    }


    // UPLOAD ẢNH SẢN PHẨM

    /**
     * Validate và upload ảnh sản phẩm từ $_FILES.
     *
     * Quy trình:
     *   1. Kiểm tra lỗi upload từ PHP ($file['error']).
     *   2. Validate kích thước file (tối đa 2MB).
     *   3. Validate MIME type thực tế bằng finfo (không tin $_FILES['type']).
     *   4. Validate extension.
     *   5. Tạo tên file duy nhất bằng uniqid().
     *   6. Đảm bảo thư mục đích tồn tại.
     *   7. Di chuyển file từ tmp sang thư mục đích.
     *
     * Cách dùng:
     *   $result = UploadHelper::uploadProductImage($_FILES['image']);
     *   if ($result['success']) {
     *       // lưu $result['filename'] vào cột image của bảng products
     *   }
     *
     * @param  array  $file    Phần tử từ $_FILES (VD: $_FILES['image']).
     * @param  string $oldFile Tên file cũ cần xoá sau khi upload thành công (khi cập nhật).
     *                         Truyền chuỗi rỗng nếu là thêm mới.
     * @return array  ['success' => bool, 'filename' => string|null, 'message' => string]
     */
    public static function uploadProductImage(array $file, string $oldFile = ''): array
    {
        // Bước 1: Kiểm tra lỗi upload từ PHP
        $uploadError = self::checkUploadError($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== true) {
            return ['success' => false, 'filename' => null, 'message' => $uploadError];
        }

        // Bước 2: Validate kích thước
        $sizeError = self::validateFileSize($file['size'] ?? 0);
        if ($sizeError !== true) {
            return ['success' => false, 'filename' => null, 'message' => $sizeError];
        }

        // Bước 3: Validate MIME type thực tế (không dùng $file['type'] — dễ giả mạo)
        $mimeError = self::validateMimeType($file['tmp_name'] ?? '');
        if ($mimeError !== true) {
            return ['success' => false, 'filename' => null, 'message' => $mimeError];
        }

        // Bước 4: Validate extension
        $extError = self::validateExtension($file['name'] ?? '');
        if ($extError !== true) {
            return ['success' => false, 'filename' => null, 'message' => $extError];
        }

        // Bước 5: Tạo tên file duy nhất
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('product_', true) . '.' . $ext;

        // Bước 6: Đảm bảo thư mục đích tồn tại
        $uploadPath = self::getUploadPath();
        if (!is_dir($uploadPath) && !mkdir($uploadPath, 0755, true)) {
            return [
                'success'  => false,
                'filename' => null,
                'message'  => 'Không thể tạo thư mục upload. Vui lòng kiểm tra quyền ghi.',
            ];
        }

        // Bước 7: Di chuyển file từ tmp sang thư mục đích
        $destination = $uploadPath . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success'  => false,
                'filename' => null,
                'message'  => 'Không thể lưu file. Vui lòng thử lại.',
            ];
        }

        // Upload thành công → xoá file cũ nếu có (khi cập nhật sản phẩm)
        if (!empty($oldFile)) {
            self::deleteProductImage($oldFile);
        }

        return [
            'success'  => true,
            'filename' => $filename,
            'message'  => 'Upload ảnh thành công.',
        ];
    }

    /**
     * Kiểm tra có file được upload trong request không.
     * Dùng để phân biệt "người dùng không chọn file" với "có lỗi upload".
     *
     * Cách dùng:
     *   if (UploadHelper::hasFile($_FILES['image'])) {
     *       $result = UploadHelper::uploadProductImage($_FILES['image'], $oldImage);
     *   }
     *
     * @param  array $file Phần tử từ $_FILES.
     * @return bool
     */
    public static function hasFile(array $file): bool
    {
        return isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE;
    }


    // XOÁ FILE

    /**
     * Xoá file ảnh sản phẩm khỏi thư mục upload theo tên file.
     * Bỏ qua nếu file không tồn tại — tránh throw lỗi khi DB và file system lệch nhau.
     *
     * Cách dùng:
     *   UploadHelper::deleteProductImage('iphone16pm.jpg');
     *
     * @param  string $filename Tên file cần xoá (chỉ tên file, không có đường dẫn).
     * @return bool             true nếu xoá thành công hoặc file không tồn tại, false nếu xoá thất bại.
     */
    public static function deleteProductImage(string $filename): bool
    {
        if (empty($filename)) {
            return true;
        }

        $filePath = self::getUploadPath() . basename($filename);

        // basename() ngăn path traversal: 'iphone16pm.jpg' → an toàn
        // '../../../etc/passwd' → chỉ lấy 'passwd' → không xoá được file ngoài thư mục
        if (!file_exists($filePath)) {
            return true; // File không tồn tại → coi như đã xoá
        }

        return unlink($filePath);
    }


    // LẤY URL HIỂN THỊ

    /**
     * Trả về URL công khai của ảnh sản phẩm để dùng trong View.
     * Nếu file không tồn tại → trả URL ảnh placeholder mặc định.
     *
     * Cách dùng trong View:
     *   <img src="<?= UploadHelper::getProductImageUrl($product->getImage()) ?>">
     *
     * @param  string $filename        Tên file ảnh lưu trong DB.
     * @param  string $placeholderUrl  URL ảnh mặc định khi không có ảnh.
     * @return string                  URL công khai của ảnh.
     */
    public static function getProductImageUrl(
        string $filename,
        string $placeholderUrl = '/public/images/placeholder.jpg'
    ): string {
        if (empty($filename)) {
            return $placeholderUrl;
        }

        $filePath = self::getUploadPath() . basename($filename);

        return file_exists($filePath)
            ? '/public/' . self::UPLOAD_DIR . basename($filename)
            : $placeholderUrl;
    }


    // VALIDATE NỘI BỘ

    /**
     * Kiểm tra mã lỗi upload từ PHP và trả về thông báo tiếng Việt.
     *
     * @param  int        $errorCode Mã lỗi từ $_FILES['error'].
     * @return true|string
     */
    private static function checkUploadError(int $errorCode): true|string
    {
        return match ($errorCode) {
            UPLOAD_ERR_OK       => true,
            UPLOAD_ERR_NO_FILE  => 'Vui lòng chọn file ảnh.',
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => 'File quá lớn. Kích thước tối đa là ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB.',
            UPLOAD_ERR_PARTIAL  => 'File chỉ được upload một phần. Vui lòng thử lại.',
            default             => 'Lỗi upload không xác định (mã: ' . $errorCode . ').',
        };
    }

    /**
     * Kiểm tra kích thước file không vượt quá MAX_FILE_SIZE.
     *
     * @param  int        $size Kích thước file (byte).
     * @return true|string
     */
    private static function validateFileSize(int $size): true|string
    {
        if ($size <= 0) {
            return 'File không hợp lệ hoặc rỗng.';
        }
        if ($size > self::MAX_FILE_SIZE) {
            return 'Kích thước file vượt quá ' . (self::MAX_FILE_SIZE / 1024 / 1024) . 'MB.';
        }
        return true;
    }

    /**
     * Kiểm tra MIME type thực tế của file bằng finfo.
     * KHÔNG tin vào $_FILES['type'] vì client có thể giả mạo.
     *
     * @param  string     $tmpPath Đường dẫn file tạm trên server.
     * @return true|string
     */
    private static function validateMimeType(string $tmpPath): true|string
    {
        if (empty($tmpPath) || !is_uploaded_file($tmpPath)) {
            return 'File upload không hợp lệ.';
        }

        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return 'Chỉ chấp nhận ảnh định dạng JPG, PNG, WEBP.';
        }

        return true;
    }

    /**
     * Kiểm tra extension của tên file có nằm trong danh sách cho phép không.
     * Kiểm tra song song với MIME type để tăng độ an toàn.
     *
     * @param  string     $filename Tên file gốc từ $_FILES['name'].
     * @return true|string
     */
    private static function validateExtension(string $filename): true|string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return 'Phần mở rộng file không hợp lệ. Chỉ chấp nhận: '
                . implode(', ', self::ALLOWED_EXTENSIONS) . '.';
        }

        return true;
    }
}
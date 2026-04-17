<?php
// 1. Cấu hình hệ thống
define('APP_ROOT', __DIR__);
define('URL_ROOT', 'http://localhost/Demo_02.1'); // Sửa lại cho đúng tên thư mục của Vũ

// 2. Khởi động Session
require_once 'helpers/SessionHelper.php';
SessionHelper::start();

// 3. Tự động nạp các lớp (Autoloading đơn giản)
// Trong đồ án, Vũ có thể require thủ công các file core trước
require_once 'database/Database.php';
require_once 'models/BaseModel.php';
require_once 'controller/BaseController.php';
require_once 'helpers/ValidatorHelper.php';
require_once 'helpers/UploadHelper.php';

// Nạp các Model và Service chính để dùng chung
require_once 'models/ProductModel.php';
require_once 'models/CategoryModel.php';
require_once 'services/CartService.php';

// Khởi tạo các đối tượng dùng chung toàn hệ thống
$productModel = new ProductModel();
$cartService = new CartService($productModel);
<?php
require_once 'config.php';

if (isset($conn) && $conn instanceof PDO) {
    echo "<h1>Trạng thái: Đã kết nối đến Database!</h1>";
    
    // Lấy thử tên Database đang dùng
    $dbname = $conn->query('SELECT DATABASE()')->fetchColumn();
    echo "Bạn đang kết nối đến database: <strong>" . $dbname . "</strong>";
} else {
    echo "<h1>Trạng thái: Mất kết nối!</h1>";
}
?>
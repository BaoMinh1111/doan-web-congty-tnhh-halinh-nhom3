<?php
$host = "sql303.infinityfree.com";
$dbname = "if0_41015985_if0_41015985_halinh_db";
$username = "if0_41015985";
$password = "DoAnWeb2026";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Kết nối thất bại: " . $e->getMessage();
}
?>
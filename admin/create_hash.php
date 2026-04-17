<?php
// Mật khẩu Vũ muốn đặt cho Admin
$pass = 'Admin@123'; 
// Máy băm hoạt động:
echo password_hash($pass, PASSWORD_DEFAULT);
?>
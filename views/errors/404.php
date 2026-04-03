<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không tìm thấy trang</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 100px 20px; background: #f8f9fa; }
        h1 { font-size: 80px; color: #e74c3c; margin: 0; }
        p { font-size: 20px; color: #555; }
        a { color: #3498db; text-decoration: none; font-size: 18px; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p><?= htmlspecialchars($message ?? 'Trang bạn tìm không tồn tại.') ?></p>
    <a href="/">← Về trang chủ</a>
</body>
</html>
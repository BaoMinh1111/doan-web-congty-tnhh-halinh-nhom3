<?php http_response_code(500); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Lỗi hệ thống</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 100px 20px; background: #f8f9fa; }
        h1 { font-size: 80px; color: #e67e22; margin: 0; }
        p { font-size: 20px; color: #555; }
        a { color: #3498db; text-decoration: none; font-size: 18px; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>500</h1>
    <p><?= htmlspecialchars($message ?? 'Đã xảy ra lỗi. Vui lòng thử lại sau.') ?></p>
    <a href="/">← Về trang chủ</a>
</body>
</html>
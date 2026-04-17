<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hà Linh Tech - Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #004aad 0%, #04152d 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            color: white;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }
        .btn-admin {
            background-color: #ffc107;
            border: none;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-admin:hover { transform: scale(1.05); background-color: #e0a800; }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <h3 class="mb-4 text-warning"><i class="fas fa-user-shield me-2"></i> ADMIN LOGIN</h3>
        <p class="small text-light mb-4">Hệ thống quản trị linh kiện Hà Linh Tech</p>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger p-2 small"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="index.php?controller=auth&action=login" method="POST">
            <div class="mb-3 text-start">
                <label class="form-label small">Tên đăng nhập</label>
                <input type="text" name="username" class="form-control bg-transparent text-white" required>
            </div>
            <div class="mb-4 text-start">
                <label class="form-label small">Mật khẩu</label>
                <input type="password" name="password" class="form-control bg-transparent text-white" required>
            </div>
            <button type="submit" class="btn btn-admin w-100 py-2">ĐĂNG NHẬP HỆ THỐNG</button>
        </form>
        
        <div class="mt-4 pt-3 border-top border-secondary">
            <a href="../index.php" class="text-white-50 text-decoration-none small">
                <i class="fas fa-arrow-left"></i> Quay lại Website
            </a>
        </div>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Thêm danh mục mới - Hà Linh Admin</title>
</head>
<body class="bg-light py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white fw-bold">
                    <i class="fas fa-plus-circle me-2"></i> THÊM DANH MỤC LINH KIỆN
                </div>
                <div class="card-body p-4">
                    <form action="index.php?controller=category&action=store" method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Tên danh mục</label>
                            <input type="text" name="name" class="form-control form-control-lg" 
                                   placeholder="Ví dụ: Card đồ họa (VGA), CPU..." required>
                            <div class="form-text">Nhập tên danh mục linh kiện mà Hà Linh Tech kinh doanh.</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php?controller=category" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                            <button type="submit" class="btn btn-success px-5 fw-bold">
                                LƯU DANH MỤC
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
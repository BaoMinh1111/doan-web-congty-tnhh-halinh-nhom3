<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Viết bài mới - Hà Linh Admin</title>
</head>
<body class="bg-light py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white fw-bold">
                    <i class="fas fa-pen-nib me-2"></i> SOẠN THẢO BÀI VIẾT MỚI
                </div>
                <div class="card-body p-4">
                    <form action="index.php?controller=post&action=store" method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Tiêu đề bài viết</label>
                            <input type="text" name="title" class="form-control form-control-lg" 
                                   placeholder="Ví dụ: Top 5 Card đồ họa đáng mua nhất 2026..." required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Ảnh đại diện bài viết</label>
                            <input type="file" name="image" class="form-control" required>
                            <div class="form-text">Nên chọn ảnh nằm ngang để hiển thị đẹp nhất trên tin tức.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Nội dung chi tiết</label>
                            <textarea name="content" class="form-control" rows="12" 
                                      placeholder="Viết nội dung bài viết tại đây..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-between border-top pt-4">
                            <a href="index.php?controller=post" class="btn btn-outline-secondary px-4">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                            <button type="submit" class="btn btn-primary px-5 fw-bold shadow">
                                <i class="fas fa-paper-plane me-2"></i> ĐĂNG BÀI VIẾT
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
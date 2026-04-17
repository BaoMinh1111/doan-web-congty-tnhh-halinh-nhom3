<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Sửa sản phẩm - Hà Linh Admin</title>
</head>
<body class="bg-light py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-warning text-dark fw-bold">
                    <i class="fas fa-edit me-2"></i> CHỈNH SỬA LINH KIỆN
                </div>
                <div class="card-body">
                    <form action="index.php?controller=product&action=update" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="old_image" value="<?php echo $product['image']; ?>">
                        <input type="hidden" name="cat_id" value="<?php echo $_GET['cat_id'] ?? ''; ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tên sản phẩm</label>
                            <input type="text" name="name" class="form-control" value="<?php echo $product['name']; ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Giá bán (VNĐ)</label>
                                <input type="number" name="price" class="form-control" value="<?php echo $product['price']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Danh mục</label>
                                <select name="category_id" class="form-select">
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($cat['id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo $cat['name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 text-center">
                            <label class="form-label d-block fw-bold text-start">Ảnh hiện tại</label>
                            <?php
                            // Xử lý đường dẫn ảnh
                            $imagePath = $product['image'];
                            // Nếu đường dẫn chưa có assets/images/products thì thêm vào
                            if (strpos($imagePath, 'assets/images/') === 0) {
                                $imagePath = '/' . $imagePath;
                            } elseif (strpos($imagePath, 'products/') !== false) {
                                $imagePath = '/assets/images/' . $imagePath;
                            } else {
                                $imagePath = '/assets/images/products/' . $imagePath;
                            }
                            ?>
                            <img src="<?= $imagePath ?>" class="img-fluid" style="max-height: 200px;">
                            <input type="file" name="image" class="form-control mt-2">
                            <small class="text-muted">Để trống nếu không muốn thay đổi ảnh</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo $product['description']; ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php?controller=product" class="btn btn-secondary">Hủy bỏ</a>
                            <button type="submit" class="btn btn-warning px-5 fw-bold">CẬP NHẬT THÔNG TIN</button>
                        </div>

                        <div class="form-group">
                            <h4>Thông số kỹ thuật</h4>

                            <?php if (!empty($spec_fields)): ?>
                                <?php foreach ($spec_fields as $field): ?>
                                    <div class="form-group row">
                                        <label class="col-sm-3"><?= htmlspecialchars($field) ?></label>
                                        <div class="col-sm-9">
                                            <input
                                                    type="text"
                                                    name="specs[<?= htmlspecialchars($field) ?>]"
                                                    value="<?= htmlspecialchars($current_specs[$field] ?? '') ?>"
                                                    class="form-control"
                                            >
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Danh mục này chưa có cấu hình thông số.</p>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php
class ProductService {
    private $productModel;
    private $categoryModel;

    public function __construct(ProductModel $pm, CategoryModel $cm) {
        $this->productModel = $pm;
        $this->categoryModel = $cm;
    }

    /**
     * Lấy danh sách sản phẩm hiển thị trang chủ (Kèm logic Business)
     */
    public function getFeaturedProducts($limit = 8) {
        $products = $this->productModel->getAll();
        // Giả sử logic Business: Chỉ lấy sản phẩm còn hàng
        return array_slice(array_filter($products, function($p) {
            return $p['stock'] > 0;
        }), 0, $limit);
    }

    /**
     * Logic thêm sản phẩm mới (Kết hợp với UploadHelper)
     */
    public function createNewProduct($data, $file) {
        // BƯỚC 1: Xử lý upload ảnh trước
        $uploadResult = UploadHelper::uploadProductImage($file);
        
        if (is_array($uploadResult) && isset($uploadResult['error'])) {
            return $uploadResult; // Trả về lỗi upload
        }

        // BƯỚC 2: Gán tên file ảnh vào data để lưu DB
        $data['image'] = $uploadResult;

        // BƯỚC 3: Gọi Model để lưu
        $success = $this->productModel->add($data);
        
        return $success ? ['success' => true] : ['error' => "Lỗi lưu dữ liệu vào Database."];
    }

    /**
     * Lấy sản phẩm theo danh mục và sắp xếp theo giá (Business Requirement)
     */
    public function getProductsByCategory($catId, $sort = 'desc') {
        $products = $this->productModel->search(""); // Lấy toàn bộ tạm thời
        $filtered = array_filter($products, function($p) use ($catId) {
            return $p['category_id'] == $catId;
        });

        if ($sort === 'desc') {
            usort($filtered, fn($a, $b) => $b['price'] <=> $a['price']);
        } else {
            usort($filtered, fn($a, $b) => $a['price'] <=> $b['price']);
        }

        return $filtered;
    }
}
<?php

// require_once đã được xóa — để bootstrap.php lo việc này.

/**
 * Class ProductService
 *
 * Xử lý logic nghiệp vụ liên quan đến sản phẩm và danh mục.
 * Là tầng trung gian giữa Controller và Model.
 * Nhận Model qua constructor (Dependency Injection).
 *
 * @package App\Services
 * @author  Ha Linh Technology Solutions
 */
class ProductService
{
    private ProductModel  $productModel;
    private CategoryModel $categoryModel;

    public function __construct(ProductModel $productModel, CategoryModel $categoryModel)
    {
        $this->productModel  = $productModel;
        $this->categoryModel = $categoryModel;
    }


    // ── PRIVATE HELPER ────────────────────────────────────────

    /**
     * Build lookup map [category_id => CategoryEntity] trong 1 query duy nhất.
     *
     * Nhất quán: cả getProductsWithCategory() lẫn getFeatured() đều dùng helper này
     * thay vì mỗi method tự load category theo cách riêng.
     *
     * Bảng categories thường nhỏ (< 50 bản ghi) nên load all là hợp lý,
     * đơn giản hơn getByIds() và tránh N query riêng lẻ trong loop.
     *
     * @return array<int, CategoryEntity>
     */
    private function buildCategoryMap(): array
    {
        $map = [];
        foreach ($this->categoryModel->getAll() as $cat) {
            $map[$cat->getId()] = $cat;
        }
        return $map;
    }

    /**
     * Gắn category vào danh sách products từ map sẵn có.
     * Tái sử dụng pattern chung cho mọi method trả array[].
     *
     * @param  ProductEntity[] $products
     * @param  array           $categoryMap [id => CategoryEntity]
     * @return array[]         [['product' => ..., 'category' => ...], ...]
     */
    private function attachCategories(array $products, array $categoryMap): array
    {
        return array_values(array_map(fn(ProductEntity $p) => [
            'product'  => $p,
            'category' => $categoryMap[$p->getCategoryId()] ?? null,
        ], $products));
    }


    // ── PUBLIC API ────────────────────────────────────────────

    /**
     * Lấy tất cả sản phẩm kèm danh mục — dùng cho trang chủ chế độ đầy đủ.
     *
     * @return array[] [['product' => ProductEntity, 'category' => CategoryEntity|null], ...]
     */
    public function getProductsWithCategory(): array
    {
        $products = $this->productModel->getAll();

        if (empty($products)) {
            return [];
        }

        return $this->attachCategories($products, $this->buildCategoryMap());
    }

    /**
     * Lấy sản phẩm nổi bật (mới nhất) kèm danh mục.
     *
     * Fix N+1: trước đây gọi getById() trong loop (N query).
     * Bây giờ dùng buildCategoryMap() — 1 query duy nhất, nhất quán với getProductsWithCategory().
     *
     * @param  int   $limit Mặc định 8.
     * @return array[]
     */
    public function getFeatured(int $limit = 8): array
    {
        if ($limit < 1) {
            return [];
        }

        $products = $this->productModel->getFeatured($limit);

        if (empty($products)) {
            return [];
        }

        return $this->attachCategories($products, $this->buildCategoryMap());
    }

    /**
     * Lấy chi tiết 1 sản phẩm kèm danh mục — dùng cho trang detail.
     *
     * @param  int        $id
     * @return array|null     null nếu không tồn tại.
     */
    public function getProductDetail(int $id): ?array
    {
        $product = $this->productModel->getById($id);

        if ($product === null) {
            return null;
        }

        return [
            'product'  => $product,
            'category' => $this->categoryModel->getById($product->getCategoryId()),
        ];
    }

    /**
     * Lấy sản phẩm theo danh mục.
     *
     * Method độc lập, tách biệt khỏi searchAndFilter().
     * Intent rõ ràng: "lọc theo danh mục" ≠ "tìm kiếm theo từ khoá".
     * Sau này có thể thêm sort/paginate riêng mà không ảnh hưởng searchAndFilter().
     *
     * Cách dùng:
     *   $items = $productService->getByCategory(3);
     *
     * @param  int   $categoryId
     * @return array[]
     */
    public function getByCategory(int $categoryId): array
    {
        if ($categoryId <= 0) {
            return [];
        }

        $products = $this->productModel->getByCategory($categoryId);

        if (empty($products)) {
            return [];
        }

        return $this->attachCategories($products, $this->buildCategoryMap());
    }

    /**
     * Lấy thông tin 1 danh mục theo ID.
     * Dùng cho Controller khi chỉ cần check tồn tại hoặc lấy tên —
     * không cần load toàn bộ danh sách rồi foreach.
     *
     * @param  int                $id
     * @return CategoryEntity|null
     */
    public function getCategoryById(int $id): ?CategoryEntity
    {
        return $this->categoryModel->getById($id);
    }

    /**
     * Tìm kiếm sản phẩm theo từ khoá, tuỳ chọn kết hợp lọc danh mục.
     *
     * Intent: "tìm kiếm" — khác getByCategory() là "lọc theo danh mục".
     *
     * Fix DB filter: khi có cả keyword + categoryId, dùng ProductModel::searchByCategory()
     * (WHERE name LIKE ? AND category_id = ?) thay vì tải toàn bộ rồi array_filter PHP-side.
     *
     * Guard: cả 2 rỗng → trả [] ngay, không query DB.
     *
     * @param  string $keyword
     * @param  int    $categoryId 0 = không lọc theo danh mục.
     * @return array[]
     */
    public function searchAndFilter(string $keyword, int $categoryId = 0): array
    {
        $keyword = trim($keyword);

        if ($keyword === '' && $categoryId <= 0) {
            return [];
        }

        $products = ($keyword !== '' && $categoryId > 0)
            // 1 query DB với WHERE name LIKE ? AND category_id = ?
            ? $this->productModel->searchByCategory($keyword, $categoryId)
            // Chỉ keyword
            : $this->productModel->search($keyword);

        if (empty($products)) {
            return [];
        }

        return $this->attachCategories($products, $this->buildCategoryMap());
    }

    /**
     * Lấy tất cả danh mục — dùng cho menu sidebar, dropdown lọc.
     *
     * @return CategoryEntity[]
     */
    public function getAllCategories(): array
    {
        return $this->categoryModel->getAll();
    }

    /**
     * Đếm số sản phẩm thuộc một danh mục.
     * Dùng trong AdminController trước khi xóa danh mục.
     *
     * @param  int $categoryId
     * @return int
     */
    public function countProductsByCategory(int $categoryId): int
    {
        if ($categoryId <= 0) {
            return 0;
        }

        return count($this->productModel->getByCategory($categoryId));
    }
}

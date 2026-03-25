<?php

require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/CategoryModel.php';

/**
 * Class ProductService
 *
 * Xử lý logic nghiệp vụ liên quan đến sản phẩm và danh mục.
 * Là tầng trung gian giữa Controller và Model — Controller không gọi Model trực tiếp,
 * mà gọi Service để nhận dữ liệu đã được xử lý và kết hợp sẵn.
 *
 * Nhận Model qua constructor (Dependency Injection) thay vì tự new bên trong
 * → dễ test, dễ thay thế Model giả khi unit test.
 *
 * @package App\Services
 * @author  Ha Linh Technology Solutions
 */
class ProductService
{
    // THUỘC TÍNH

    /** @var ProductModel */
    private ProductModel $productModel;

    /** @var CategoryModel */
    private CategoryModel $categoryModel;


    // CONSTRUCTOR

    /**
     * @param ProductModel  $productModel
     * @param CategoryModel $categoryModel
     */
    public function __construct(ProductModel $productModel, CategoryModel $categoryModel)
    {
        $this->productModel  = $productModel;
        $this->categoryModel = $categoryModel;
    }


    // PHƯƠNG THỨC CHÍNH

    /**
     * Lấy tất cả sản phẩm kèm thông tin danh mục đã được nhúng sẵn.
     *
     * Cách hoạt động:
     *   1. Lấy tất cả sản phẩm từ ProductModel → ProductEntity[].
     *   2. Lấy tất cả danh mục từ CategoryModel → build lookup map [id => CategoryEntity].
     *   3. Kết hợp: mỗi phần tử trả về là mảng gồm product + category đã gắn vào.
     *
     * Trả về mảng thay vì Entity vì kết quả là dữ liệu join từ 2 bảng,
     * không map 1-1 vào một Entity đơn lẻ.
     *
     * Cách dùng:
     *   $items = $productService->getProductsWithCategory();
     *   // $items[0] = ['product' => ProductEntity, 'category' => CategoryEntity|null]
     *
     * @return array[] Mỗi phần tử là ['product' => ProductEntity, 'category' => CategoryEntity|null].
     */
    public function getProductsWithCategory(): array
    {
        $products = $this->productModel->getAll();

        if (empty($products)) {
            return [];
        }

        // Build lookup map để tránh N+1 query
        // [category_id => CategoryEntity]
        $categoryMap = [];
        foreach ($this->categoryModel->getAll() as $category) {
            $categoryMap[$category->getId()] = $category;
        }

        return array_map(function (ProductEntity $product) use ($categoryMap) {
            return [
                'product'  => $product,
                'category' => $categoryMap[$product->getCategoryId()] ?? null,
            ];
        }, $products);
    }

    /**
     * Lấy sản phẩm nổi bật (mới nhất) để hiển thị ở trang chủ.
     * Kèm thông tin danh mục đã gắn sẵn.
     *
     * Cách dùng:
     *   $featured = $productService->getFeatured(8);
     *
     * @param  int   $limit Số sản phẩm tối đa cần lấy. Mặc định 8.
     * @return array[]      Mỗi phần tử là ['product' => ProductEntity, 'category' => CategoryEntity|null].
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

        // Chỉ load những danh mục thực sự xuất hiện trong kết quả
        // → tránh load toàn bộ bảng categories khi chỉ cần một phần
        $categoryIds = array_unique(array_map(
            fn(ProductEntity $p) => $p->getCategoryId(),
            $products
        ));

        $categoryMap = [];
        foreach ($categoryIds as $catId) {
            $cat = $this->categoryModel->getById($catId);
            if ($cat !== null) {
                $categoryMap[$catId] = $cat;
            }
        }

        return array_map(function (ProductEntity $product) use ($categoryMap) {
            return [
                'product'  => $product,
                'category' => $categoryMap[$product->getCategoryId()] ?? null,
            ];
        }, $products);
    }

    /**
     * Lấy chi tiết một sản phẩm kèm thông tin danh mục.
     * Dùng cho trang chi tiết sản phẩm.
     *
     * Cách dùng:
     *   $detail = $productService->getProductDetail(5);
     *   if ($detail === null) { // sản phẩm không tồn tại }
     *   // $detail = ['product' => ProductEntity, 'category' => CategoryEntity|null]
     *
     * @param  int        $id Product ID.
     * @return array|null     null nếu sản phẩm không tồn tại.
     */
    public function getProductDetail(int $id): ?array
    {
        $product = $this->productModel->getById($id);

        if ($product === null) {
            return null;
        }

        $category = $this->categoryModel->getById($product->getCategoryId());

        return [
            'product'  => $product,
            'category' => $category,
        ];
    }

    /**
     * Tìm kiếm và lọc sản phẩm theo từ khoá và/hoặc danh mục.
     *
     * Logic kết hợp:
     *   - Chỉ keyword          → tìm theo tên + mô tả (LIKE).
     *   - Chỉ categoryId       → lấy tất cả sản phẩm thuộc danh mục đó.
     *   - Cả keyword + category → tìm theo tên + mô tả, sau đó lọc tiếp theo category.
     *   - Cả 2 đều rỗng/0      → trả mảng rỗng (không dump toàn bộ bảng).
     *
     * Kết quả kèm thông tin category đã gắn sẵn, sẵn sàng cho json_encode() khi AJAX.
     *
     * Cách dùng:
     *   // Tìm kiếm AJAX từ search bar
     *   $results = $productService->searchAndFilter('RTX 4090', 0);
     *
     *   // Lọc theo danh mục (từ menu sidebar)
     *   $results = $productService->searchAndFilter('', 3);
     *
     *   // Vừa tìm vừa lọc
     *   $results = $productService->searchAndFilter('Intel', 2);
     *
     * @param  string $keyword    Từ khoá tìm kiếm (trim trước khi truyền vào).
     * @param  int    $categoryId ID danh mục cần lọc. 0 = không lọc.
     * @return array[]            Mảng kết quả, mỗi phần tử là
     *                            ['product' => ProductEntity, 'category' => CategoryEntity|null].
     *                            Rỗng nếu không tìm thấy hoặc cả 2 tham số đều trống/0.
     */
    public function searchAndFilter(string $keyword, int $categoryId = 0): array
    {
        $keyword = trim($keyword);

        // Guard: cả 2 đều trống → không query, trả rỗng
        if ($keyword === '' && $categoryId <= 0) {
            return [];
        }

        // Lấy kết quả từ Model theo điều kiện
        if ($keyword !== '' && $categoryId > 0) {
            // Vừa tìm kiếm vừa lọc category
            $products = $this->productModel->search($keyword);
            $products = array_filter(
                $products,
                fn(ProductEntity $p) => $p->getCategoryId() === $categoryId
            );
        } elseif ($keyword !== '') {
            // Chỉ tìm kiếm
            $products = $this->productModel->search($keyword);
        } else {
            // Chỉ lọc theo category
            $products = $this->productModel->getByCategory($categoryId);
        }

        if (empty($products)) {
            return [];
        }

        // Gắn thông tin category vào kết quả
        $categoryMap = [];
        foreach ($products as $product) {
            $catId = $product->getCategoryId();
            if (!isset($categoryMap[$catId])) {
                $cat = $this->categoryModel->getById($catId);
                if ($cat !== null) {
                    $categoryMap[$catId] = $cat;
                }
            }
        }

        return array_values(array_map(
            function (ProductEntity $product) use ($categoryMap) {
                return [
                    'product'  => $product,
                    'category' => $categoryMap[$product->getCategoryId()] ?? null,
                ];
            },
            $products
        ));
    }

    /**
     * Lấy tất cả danh mục để hiển thị menu sidebar / dropdown lọc.
     * Wrapper đơn giản quanh CategoryModel::getAll() —
     * Controller không cần inject thêm CategoryModel riêng.
     *
     * @return CategoryEntity[]
     */
    public function getAllCategories(): array
    {
        return $this->categoryModel->getAll();
    }
}

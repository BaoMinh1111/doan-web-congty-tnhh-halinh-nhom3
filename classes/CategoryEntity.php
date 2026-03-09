<?php

/**
 * Class CategoryEntity
 *
 * Đại diện cho một danh mục sản phẩm (CPU, RAM, VGA, ...).
 * Là lớp Entity thuần túy: chỉ chứa dữ liệu và các method tiện ích.
 * Không phụ thuộc vào bất kỳ lớp nào khác trong hệ thống.
 *
 * @package App\Entities
 * @author  Ha Linh Technology Solutions
 */
class CategoryEntity
{
    // THUỘC TÍNH

    /**
     * ID danh mục (primary key trong bảng categories).
     *
     * @var int
     */
    private int $id;

    /**
     * Tên danh mục (VD: "CPU", "RAM", "Mainboard").
     *
     * @var string
     */
    private string $name;

    /**
     * Mô tả chi tiết về danh mục.
     *
     * @var string
     */
    private string $description;


    // CONSTRUCTOR

    /**
     * Khởi tạo CategoryEntity từ mảng dữ liệu (thường là từ kết quả PDO fetch).
     *
     * Cách dùng:
     *   $category = new CategoryEntity($row);          // từ DB
     *   $category = new CategoryEntity($_POST);        // từ form
     *
     * @param array $data Mảng dữ liệu với các key: id, name, description.
     */
    public function __construct(array $data)
    {
        $this->id          = isset($data['id'])          ? (int)    $data['id']          : 0;
        $this->name        = isset($data['name'])        ? (string) trim($data['name'])        : '';
        $this->description = isset($data['description']) ? (string) trim($data['description']) : '';
    }


    // GETTERS

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }


    // VALIDATE

    /**
     * Kiểm tra tính hợp lệ của dữ liệu danh mục.
     *
     * Quy tắc:
     *   - name: không được rỗng, không vượt quá 100 ký tự.
     *   - description: không bắt buộc, nhưng nếu có thì không vượt quá 500 ký tự.
     *
     * Cách dùng:
     *   $errors = $category->validate();
     *   if (!empty($errors)) { // hiển thị lỗi }
     *
     * @return array Mảng lỗi (rỗng nếu hợp lệ). Key là tên trường, value là thông báo lỗi.
     */
    public function validate(): array
    {
        $errors = [];

        // Validate name
        if (empty($this->name)) {
            $errors['name'] = 'Tên danh mục không được để trống.';
        } elseif (mb_strlen($this->name) > 100) {
            $errors['name'] = 'Tên danh mục không được vượt quá 100 ký tự.';
        }

        // Validate description (không bắt buộc)
        if (!empty($this->description) && mb_strlen($this->description) > 500) {
            $errors['description'] = 'Mô tả không được vượt quá 500 ký tự.';
        }

        return $errors;
    }


    // SERIALIZE

    /**
     * Chuyển entity thành mảng kết hợp.
     * Dùng để truyền dữ liệu vào View hoặc chuẩn bị cho json_encode().
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
        ];
    }

    /**
     * Chuyển entity thành chuỗi JSON.
     * Dùng cho AJAX response khi cần trả dữ liệu danh mục về client.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}

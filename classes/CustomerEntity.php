<?php

/**
 * Class CustomerEntity
 *
 * Đại diện cho một khách hàng trong hệ thống.
 * Hỗ trợ 2 loại khách hàng:
 *   - Có tài khoản: đăng ký, đăng nhập, xem lịch sử đơn hàng.
 *   - Vãng lai (guest): không cần tài khoản, chỉ điền thông tin giao hàng khi đặt.
 *
 * Phân biệt bằng thuộc tính $userId:
 *   - $userId = int  → khách có tài khoản (liên kết bảng users)
 *   - $userId = null → khách vãng lai (không liên kết users, tránh FK constraint)
 *
 * @package App\Entities
 * @author  Ha Linh Technology Solutions
 */
class CustomerEntity
{
    // THUỘC TÍNH

    /**
     * ID khách hàng (primary key bảng customers).
     * null khi Entity được tạo từ form (chưa INSERT vào DB).
     * int  khi Entity được lấy từ DB (đã có ID thật).
     *
     * @var int|null
     */
    private ?int $id;

    /** @var string Họ tên khách hàng */
    private string $name;

    /** @var string Email liên hệ */
    private string $email;

    /** @var string Số điện thoại */
    private string $phone;

    /** @var string Địa chỉ giao hàng */
    private string $address;

    /**
     * FK liên kết bảng users.
     * null nếu là khách vãng lai — tránh FK constraint với bảng users.
     * int  nếu là khách có tài khoản.
     *
     * @var int|null
     */
    private ?int $userId;

    /** @var string Ghi chú thêm của khách (tùy chọn) */
    private string $note;


    // CONSTRUCTOR

    /**
     * Khởi tạo CustomerEntity từ mảng dữ liệu.
     *
     * Cách dùng:
     *   // Khách có tài khoản (từ DB)
     *   $customer = new CustomerEntity($rowFromDb);
     *
     *   // Khách vãng lai (từ form checkout) — id và user_id tự động = null
     *   $customer = new CustomerEntity($_POST);
     *
     * @param array $data Mảng dữ liệu với các key: id, name, email, phone, address, user_id, note.
     */
    public function __construct(array $data)
    {
        // null khi chưa có trong DB (tạo từ form), int khi lấy từ DB
        $this->id      = isset($data['id'])      ? (int)    $data['id']            : null;
        $this->name    = isset($data['name'])    ? (string) trim($data['name'])    : '';
        $this->email   = isset($data['email'])   ? (string) trim($data['email'])   : '';
        $this->phone   = isset($data['phone'])   ? (string) trim($data['phone'])   : '';
        $this->address = isset($data['address']) ? (string) trim($data['address']) : '';
        // null cho guest — không ghi FK vào DB, tránh FK constraint với bảng users
        $this->userId  = isset($data['user_id']) && $data['user_id'] !== '' && $data['user_id'] !== null
                         ? (int) $data['user_id']
                         : null;
        $this->note    = isset($data['note'])    ? (string) trim($data['note'])    : '';
    }


    // GETTERS

    public function getId(): ?int     { return $this->id;      }
    public function getName(): string  { return $this->name;    }
    public function getEmail(): string { return $this->email;   }
    public function getPhone(): string { return $this->phone;   }
    public function getAddress(): string { return $this->address; }
    public function getUserId(): ?int  { return $this->userId;  }
    public function getNote(): string  { return $this->note;    }

    /**
     * Kiểm tra khách hàng có tài khoản hay là vãng lai.
     * Dùng nullable thay vì so sánh với 0 — rõ ràng hơn về mặt ý nghĩa.
     *
     * @return bool true nếu có tài khoản (userId không null).
     */
    public function isRegistered(): bool
    {
        return $this->userId !== null;
    }


    // VALIDATE

    /**
     * Kiểm tra tính hợp lệ của dữ liệu khách hàng.
     *
     * Quy tắc:
     *   - name    : bắt buộc, 2–100 ký tự.
     *   - email   : bắt buộc, đúng định dạng email, tối đa 255 ký tự.
     *   - phone   : bắt buộc, chỉ chứa số/dấu cộng/gạch ngang, 8–15 ký tự.
     *   - address : bắt buộc, 10–500 ký tự.
     *   - note    : không bắt buộc, tối đa 500 ký tự.
     *
     * @return array Mảng lỗi (rỗng nếu hợp lệ).
     */
    public function validate(): array
    {
        $errors = [];

        // name
        if (empty($this->name)) {
            $errors['name'] = 'Họ tên không được để trống.';
        } elseif (mb_strlen($this->name) < 2) {
            $errors['name'] = 'Họ tên phải có ít nhất 2 ký tự.';
        } elseif (mb_strlen($this->name) > 100) {
            $errors['name'] = 'Họ tên không được vượt quá 100 ký tự.';
        }

        // email
        if (empty($this->email)) {
            $errors['email'] = 'Email không được để trống.';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không đúng định dạng.';
        } elseif (mb_strlen($this->email) > 255) {
            $errors['email'] = 'Email không được vượt quá 255 ký tự.';
        }

        // phone: cho phép số, dấu +, dấu -
        if (empty($this->phone)) {
            $errors['phone'] = 'Số điện thoại không được để trống.';
        } elseif (!preg_match('/^[0-9+\-]{8,15}$/', $this->phone)) {
            $errors['phone'] = 'Số điện thoại không hợp lệ (8–15 ký tự, chỉ gồm số, + và -).';
        }

        // address
        if (empty($this->address)) {
            $errors['address'] = 'Địa chỉ không được để trống.';
        } elseif (mb_strlen($this->address) < 10) {
            $errors['address'] = 'Địa chỉ phải có ít nhất 10 ký tự.';
        } elseif (mb_strlen($this->address) > 500) {
            $errors['address'] = 'Địa chỉ không được vượt quá 500 ký tự.';
        }

        // note (không bắt buộc)
        if (!empty($this->note) && mb_strlen($this->note) > 500) {
            $errors['note'] = 'Ghi chú không được vượt quá 500 ký tự.';
        }

        return $errors;
    }


    // SERIALIZE

    /**
     * Chuyển entity thành mảng.
     * Dùng khi truyền dữ liệu vào View hoặc chuẩn bị cho json_encode().
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'      => $this->id,      // null nếu chưa INSERT vào DB
            'name'    => $this->name,
            'email'   => $this->email,
            'phone'   => $this->phone,
            'address' => $this->address,
            'user_id' => $this->userId,  // null nếu là khách vãng lai
            'note'    => $this->note,
        ];
    }

    /**
     * Chuyển entity thành chuỗi JSON.
     *
     * @return string
     * @throws RuntimeException Nếu json_encode thất bại.
     */
    public function toJson(): string
    {
        $json = json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException(
                'Không thể encode CustomerEntity sang JSON: ' . json_last_error_msg()
            );
        }

        return $json;
    }
}

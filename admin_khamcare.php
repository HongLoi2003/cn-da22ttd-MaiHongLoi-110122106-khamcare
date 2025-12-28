<?php
/**
 * Trang quản trị viên cho hệ thống KhamCare
 * Đăng nhập bằng tài khoản quản trị viên mặc định, không cho phép đăng ký admin mới.
 * Khi bấm đăng nhập sẽ vào thẳng trang quản trị viên.
 */

session_start();

// Tài khoản quản trị viên mặc định (có thể chỉnh sửa tại đây)
define('ADMIN_USERNAME', 'khamcare');
define('ADMIN_PASSWORD', 'khamcare123'); // Nên đổi mật khẩu này khi triển khai thực tế

// Xử lý đăng xuất admin
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: admin_khamcare.php?logout=1');
    exit;
}

// Xử lý đăng nhập admin
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        // Đăng nhập thành công
        $_SESSION['user_id'] = 0; // 0 là admin mặc định
        $_SESSION['role'] = 'admin';
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        
        // Regenerate session ID để bảo mật
        session_regenerate_id(true);
        
        // Redirect với tham số để tránh resubmit form
        header('Location: admin_khamcare.php?logged_in=1');
        exit;
    } else {
        $login_error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
    }
}

// Kiểm tra nếu admin chưa đăng nhập, hiển thị form đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || 
    !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Đăng nhập Quản Trị Viên - KhamCare</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body {
                background: #f4f6fa;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #495057;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-box {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 32px 0 rgba(34,41,47,.10);
                padding: 40px 32px;
                max-width: 400px;
                width: 100%;
            }
            .login-box .form-control {
                border-radius: 8px;
                padding: 12px 16px;
                border: 1.5px solid #e3e6ef;
                margin-bottom: 18px;
                font-size: 1rem;
            }
            .login-box .form-control:focus {
                border-color: #2563eb;
                box-shadow: 0 0 0 0.1rem rgba(37,99,235,.12);
            }
            .login-title {
                font-weight: 700;
                color: #2563eb;
                margin-bottom: 24px;
                text-align: center;
                font-size: 1.6rem;
                letter-spacing: 0.5px;
            }
            .btn-primary {
                background: linear-gradient(90deg, #2563eb 0%, #38bdf8 100%);
                border: none;
                border-radius: 8px;
                padding: 12px 0;
                font-weight: 600;
                width: 100%;
                transition: background 0.2s;
                font-size: 1.1rem;
            }
            .btn-primary:hover {
                background: linear-gradient(90deg, #38bdf8 0%, #2563eb 100%);
            }
            .admin-icon {
                font-size: 2.8rem;
                color: #2563eb;
                display: block;
                text-align: center;
                margin-bottom: 12px;
            }
            .login-box .alert {
                font-size: 1rem;
                padding: 10px 14px;
                margin-bottom: 14px;
            }
            .login-box .form-label {
                font-weight: 500;
                color: #495057;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="admin-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h2 class="login-title">Đăng nhập Quản Trị Viên</h2>
            <?php if (!empty($login_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success">Đã đăng xuất thành công.</div>
            <?php endif; ?>
            <?php if (isset($_GET['session_error'])): ?>
                <div class="alert alert-warning">Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.</div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" name="admin_login" class="btn btn-primary">Đăng nhập</button>
            </form>
            <div class="mt-3 text-center text-muted" style="font-size: 0.95rem;">
                <i class="fas fa-info-circle"></i> Chỉ dành cho quản trị viên hệ thống.
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// Nếu đã đăng nhập, tiếp tục hiển thị dashboard như cũ
require_once 'db_config.php'; // Đảm bảo db_config.php có hàm getDBConnection() và getUserById()

// Lấy thông tin admin
if ($_SESSION['user_id'] === 0) {
    $admin_info = ['full_name' => 'Quản trị viên'];
} else {
    $admin_info = getUserById($_SESSION['user_id']);
    if (!$admin_info) {
        session_unset();
        session_destroy();
        header('Location: admin_khamcare.php?error=Lỗi xác thực, vui lòng đăng nhập lại.');
        exit;
    }
}

// Khởi tạo kết nối database và tạo bảng nếu cần
$pdo = getDBConnection();
if ($pdo && $pdo instanceof PDO) {
    // Tạo bảng cơ bản nếu chưa có
    try {
        createBasicTables($pdo);
    } catch (Exception $e) {
        die('<div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Lỗi tạo bảng database!</h4>
            <p>Không thể tạo các bảng cần thiết: ' . htmlspecialchars($e->getMessage()) . '</p>
            <hr>
            <p class="mb-0">Vui lòng kiểm tra quyền truy cập MySQL hoặc tạo bảng thủ công trong phpMyAdmin.</p>
        </div>');
    }
    
    // Đảm bảo các cột cần thiết tồn tại
    try {
        // Thêm các cột còn thiếu cho bảng specialties
        $stmt = $pdo->query("SHOW COLUMNS FROM specialties LIKE 'icon'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE specialties ADD COLUMN icon VARCHAR(50) DEFAULT 'fas fa-stethoscope' AFTER description");
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM specialties LIKE 'color'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE specialties ADD COLUMN color VARCHAR(20) DEFAULT '#2ecc71' AFTER icon");
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM specialties LIKE 'sort_order'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE specialties ADD COLUMN sort_order INT DEFAULT 0 AFTER color");
        }
        
        // Thêm các cột còn thiếu cho bảng doctors
        $stmt = $pdo->query("SHOW COLUMNS FROM doctors LIKE 'education'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE doctors ADD COLUMN education TEXT DEFAULT NULL AFTER total_reviews");
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM doctors LIKE 'bio'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE doctors ADD COLUMN bio TEXT DEFAULT NULL AFTER education");
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM doctors LIKE 'hospital_affiliation'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE doctors ADD COLUMN hospital_affiliation VARCHAR(255) DEFAULT NULL AFTER bio");
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM doctors LIKE 'special_interests'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE doctors ADD COLUMN special_interests TEXT DEFAULT NULL AFTER hospital_affiliation");
        }
        
    } catch (PDOException $e) {
        error_log("Error adding missing columns: " . $e->getMessage());
    }
}

// Lưu tab hiện tại vào session
if (isset($_GET['section'])) {
    $_SESSION['admin_section'] = $_GET['section'];
}
$current_section = isset($_SESSION['admin_section']) ? $_SESSION['admin_section'] : 'dashboard';

// Tạm giữ POST để xử lý sau khi các hàm đã được định nghĩa
$__ADMIN_POST = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $__ADMIN_POST = $_POST;
}

// --- Các hàm hỗ trợ (addDoctor, updateDoctor, deleteDoctor, addSpecialty, v.v.) ---
// ... giữ nguyên phần khai báo các hàm như code gốc ...

if (!function_exists('addDoctor')) {
function addDoctor($data) {
    $pdo = getDBConnection();
    if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
    
    try {
            // ... (Logic thêm bác sĩ như code gốc của bạn) ...
        $username = trim($data['username']);
        $email = trim($data['email']);
        $fullName = trim($data['full_name']);
        $phone = trim($data['phone']);

        if ($username === '' || $email === '' || $fullName === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập đủ Username, Email, Họ tên'];
        }

        $stmt = $pdo->prepare("SELECT ten_dang_nhap, email FROM nguoi_dung WHERE ten_dang_nhap = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        $exists = $stmt->fetch();
        if ($exists) {
            if (strcasecmp($exists['ten_dang_nhap'], $username) === 0) {
                return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.'];
            }
            if (strcasecmp($exists['email'], $email) === 0) {
                return ['success' => false, 'message' => 'Email đã tồn tại. Vui lòng dùng email khác.'];
            }
        }

        // Xử lý upload file chứng chỉ hành nghề
        $license_path = null;
        if (isset($_FILES['license_file']) && $_FILES['license_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['license_file'];
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                return ['success' => false, 'message' => 'Định dạng file không hợp lệ. Chỉ chấp nhận JPG, PNG, PDF'];
            }
            if ($file['size'] > $max_size) {
                return ['success' => false, 'message' => 'File quá lớn. Kích thước tối đa là 5MB'];
            }
            
            // Create uploads directory if not exists
            $upload_dir = 'uploads/licenses/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $license_filename = 'license_' . time() . '_' . uniqid() . '.' . $file_extension;
            $license_path = $upload_dir . $license_filename;
            
            if (!move_uploaded_file($file['tmp_name'], $license_path)) {
                return ['success' => false, 'message' => 'Có lỗi khi tải file lên. Vui lòng thử lại'];
            }
        }

        // Tạo mật khẩu mặc định: 123456
        $default_password = '123456';
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO nguoi_dung (ten_dang_nhap, email, mat_khau, ho_ten, so_dien_thoai, vai_tro, giay_phep_hanh_nghe) VALUES (?, ?, ?, ?, ?, 'bac_si', ?)");
        $stmt->execute([$username, $email, $hashed_password, $fullName, $phone, $license_path]);
        $user_id = $pdo->lastInsertId();
        
        $specialty_id = $data['specialty_id'] ?? 6;
        $experience_years = $data['experience_years'] ?? 0;
        $consultation_fee = $data['consultation_fee'] ?? 300000;
        $education = $data['education'] ?? '';
        $bio = $data['bio'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO bac_si (nguoi_dung_id, ho_ten, chuyen_khoa_id, nam_kinh_nghiem, phi_kham, hoc_van, gioi_thieu, trang_thai) VALUES (?, ?, ?, ?, ?, ?, ?, 'hoat_dong')");
        $stmt->execute([$user_id, $fullName, $specialty_id, $experience_years, $consultation_fee, $education, $bio]);
        
        return ['success' => true, 'message' => 'Thêm bác sĩ thành công! Mật khẩu mặc định: 123456'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}
}

// Xóa bác sĩ (và tài khoản user liên quan)
if (!function_exists('deleteDoctor')) {
    function deleteDoctor($doctorId) {
    $pdo = getDBConnection();
    if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
    try {
            // Lấy nguoi_dung_id từ bảng bac_si
        $stmt = $pdo->prepare("SELECT nguoi_dung_id FROM bac_si WHERE id = ?");
            $stmt->execute([$doctorId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return ['success' => false, 'message' => 'Không tìm thấy bác sĩ'];

            $pdo->beginTransaction();
            // Xóa các cuộc hẹn của bác sĩ
            $pdo->prepare("DELETE FROM lich_hen WHERE bac_si_id = ?")->execute([$doctorId]);
            // Xóa hồ sơ bác sĩ
            $pdo->prepare("DELETE FROM bac_si WHERE id = ?")->execute([$doctorId]);
            // Xóa tài khoản user tương ứng
            $pdo->prepare("DELETE FROM nguoi_dung WHERE id = ?")->execute([(int)$row['nguoi_dung_id']]);
            $pdo->commit();
            return ['success' => true, 'message' => 'Đã xóa bác sĩ thành công'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
        return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// Duyệt bác sĩ (chuyển status từ pending sang active)
if (!function_exists('approveDoctor')) {
    function approveDoctor($doctorId) {
        $pdo = getDBConnection();
        if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            // Lấy user_id từ bảng bac_si
            $stmt = $pdo->prepare("SELECT nguoi_dung_id FROM bac_si WHERE id = ?");
            $stmt->execute([$doctorId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return ['success' => false, 'message' => 'Không tìm thấy bác sĩ'];

            $pdo->beginTransaction();
            // Cập nhật status trong bảng bac_si
            $pdo->prepare("UPDATE bac_si SET trang_thai = 'hoat_dong' WHERE id = ?")->execute([$doctorId]);
            // Cập nhật status trong bảng nguoi_dung
            $pdo->prepare("UPDATE nguoi_dung SET trang_thai = 'hoat_dong' WHERE id = ?")->execute([(int)$row['nguoi_dung_id']]);
            $pdo->commit();
            return ['success' => true, 'message' => 'Đã duyệt bác sĩ thành công'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// Hủy kích hoạt bác sĩ (chuyển status sang cho_duyet)
if (!function_exists('deactivateDoctor')) {
    function deactivateDoctor($doctorId) {
        $pdo = getDBConnection();
        if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            // Lấy user_id từ bảng bac_si
            $stmt = $pdo->prepare("SELECT nguoi_dung_id FROM bac_si WHERE id = ?");
            $stmt->execute([$doctorId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) return ['success' => false, 'message' => 'Không tìm thấy bác sĩ'];

            $pdo->beginTransaction();
            // Cập nhật status trong bảng bac_si
            $pdo->prepare("UPDATE bac_si SET trang_thai = 'khong_hoat_dong' WHERE id = ?")->execute([$doctorId]);
            // Cập nhật status trong bảng nguoi_dung
            $pdo->prepare("UPDATE nguoi_dung SET trang_thai = 'cho_duyet' WHERE id = ?")->execute([(int)$row['nguoi_dung_id']]);
            $pdo->commit();
            return ['success' => true, 'message' => 'Đã hủy kích hoạt bác sĩ'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// Xóa chuyên khoa (khi không còn bác sĩ sử dụng)
if (!function_exists('deleteSpecialty')) {
    function deleteSpecialty($specialtyId) {
    $pdo = getDBConnection();
    if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM doctors WHERE specialty_id = ?");
            $stmt->execute([$specialtyId]);
            if ((int)$stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Không thể xóa: còn bác sĩ thuộc chuyên khoa này'];
            }
            $pdo->prepare("DELETE FROM specialties WHERE id = ?")->execute([$specialtyId]);
            return ['success' => true, 'message' => 'Đã xóa chuyên khoa'];
        } catch (Exception $e) {
        return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// Cập nhật trạng thái lịch hẹn
if (!function_exists('updateAppointmentStatus')) {
    function updateAppointmentStatus($payload) {
    $pdo = getDBConnection();
    if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            $stmt = $pdo->prepare("UPDATE lich_hen SET trang_thai = ? WHERE id = ?");
            $stmt->execute([$payload['status'] ?? 'cho_xac_nhan', (int)($payload['appointment_id'] ?? 0)]);
            return ['success' => true, 'message' => 'Đã cập nhật trạng thái'];
        } catch (Exception $e) {
        return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// Xóa người dùng thường (không phải bác sĩ)
if (!function_exists('deleteUser')) {
    function deleteUser($userId) {
    $pdo = getDBConnection();
    if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            // Không xóa nếu là bác sĩ
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bac_si WHERE nguoi_dung_id = ?");
            $stmt->execute([(int)$userId]);
            if ((int)$stmt->fetchColumn() > 0) return ['success' => false, 'message' => 'Hãy xóa theo mục Bác sĩ'];
            $pdo->prepare("DELETE FROM lich_hen WHERE benh_nhan_id = ?")->execute([(int)$userId]);
            $pdo->prepare("DELETE FROM nguoi_dung WHERE id = ?")->execute([(int)$userId]);
            return ['success' => true, 'message' => 'Đã xóa người dùng'];
        } catch (Exception $e) {
        return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// Lấy thông tin chi tiết lịch hẹn để thanh toán
if (!function_exists('getAppointmentPaymentInfo')) {
    function getAppointmentPaymentInfo($appointmentId) {
        $pdo = getDBConnection();
        if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            $stmt = $pdo->prepare("
                SELECT lh.*, lh.ten_benh_nhan as patient_name, 
                       b.ho_ten as doctor_name, b.phi_kham as consultation_fee,
                       ck.ten as specialty_name,
                       nd.email as patient_email, nd.so_dien_thoai as patient_phone
                FROM lich_hen lh
                JOIN bac_si b ON lh.bac_si_id = b.id
                LEFT JOIN chuyen_khoa ck ON b.chuyen_khoa_id = ck.id
                LEFT JOIN nguoi_dung nd ON lh.benh_nhan_id = nd.id
                WHERE lh.id = ?
            ");
            $stmt->execute([$appointmentId]);
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$appointment) {
                return ['success' => false, 'message' => 'Không tìm thấy lịch hẹn'];
            }
            
            return ['success' => true, 'data' => $appointment];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// Cập nhật trạng thái thanh toán
if (!function_exists('updatePaymentStatus')) {
    function updatePaymentStatus($payload) {
        $pdo = getDBConnection();
        if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            $appointmentId = (int)($payload['appointment_id'] ?? 0);
            $paymentStatus = $payload['payment_status'] ?? 'da_thanh_toan';
            $paymentMethod = $payload['payment_method'] ?? 'tien_mat';
            $amount = (float)($payload['amount'] ?? 0);
            
            // Cập nhật trạng thái thanh toán
            $stmt = $pdo->prepare("UPDATE lich_hen SET trang_thai_thanh_toan = ?, tong_phi = ? WHERE id = ?");
            $stmt->execute([$paymentStatus, $amount, $appointmentId]);
            
            // Nếu đã thanh toán, cập nhật trạng thái lịch hẹn thành đã xác nhận (nếu đang chờ)
            if ($paymentStatus === 'da_thanh_toan') {
                $stmt = $pdo->prepare("UPDATE lich_hen SET trang_thai = 'da_xac_nhan' WHERE id = ? AND trang_thai = 'cho_xac_nhan'");
                $stmt->execute([$appointmentId]);
            }
            
            return ['success' => true, 'message' => 'Cập nhật thanh toán thành công'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// --- Thêm sau hàm addDoctor ---

if (!function_exists('updateDoctor')) {
    function updateDoctor($data) {
        $pdo = getDBConnection();
        if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            // Cập nhật bảng doctors
            $stmt = $pdo->prepare("UPDATE doctors SET specialty_id = ?, experience_years = ?, education = ?, consultation_fee = ?, bio = ?, hospital_affiliation = ?, special_interests = ? WHERE id = ?");
            $stmt->execute([
                $data['specialty_id'] ?? null,
                $data['experience_years'] ?? 0,
                $data['education'] ?? '',
                $data['consultation_fee'] ?? 0,
                $data['bio'] ?? '',
                $data['hospital_affiliation'] ?? '',
                $data['special_interests'] ?? '',
                $data['doctor_id'] ?? 0
            ]);
            // Nếu có thông tin user cần cập nhật
            if (!empty($data['full_name']) || !empty($data['email']) || !empty($data['phone'])) {
                $stmt2 = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = (SELECT user_id FROM doctors WHERE id = ?)");
                $stmt2->execute([
                    $data['full_name'] ?? '',
                    $data['email'] ?? '',
                    $data['phone'] ?? '',
                    $data['doctor_id'] ?? 0
                ]);
            }
            return ['success' => true, 'message' => 'Đã cập nhật bác sĩ'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('addSpecialty')) {
    function addSpecialty($data) {
        $pdo = getDBConnection();
        if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            $stmt = $pdo->prepare("INSERT INTO specialties (name, description, icon, color, sort_order) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['name'] ?? '',
                $data['description'] ?? '',
                $data['icon'] ?? '',
                $data['color'] ?? '#007bff',
                $data['sort_order'] ?? 0
            ]);
            return ['success' => true, 'message' => 'Thêm chuyên khoa thành công'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('updateSpecialty')) {
    function updateSpecialty($data) {
        $pdo = getDBConnection();
        if (!$pdo) return ['success' => false, 'message' => 'Lỗi kết nối database'];
        try {
            $stmt = $pdo->prepare("UPDATE specialties SET name = ?, description = ?, icon = ?, color = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([
                $data['name'] ?? '',
                $data['description'] ?? '',
                $data['icon'] ?? '',
                $data['color'] ?? '#007bff',
                $data['sort_order'] ?? 0,
                $data['specialty_id'] ?? 0
            ]);
            return ['success' => true, 'message' => 'Đã cập nhật chuyên khoa'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
}

// --- Kết thúc 3 hàm ---

// Xử lý các action AJAX sau khi đã định nghĩa hàm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $__ADMIN_POST = $_POST;
    ini_set('display_errors', 0);
    if (function_exists('ob_get_length') && ob_get_length()) { ob_clean(); }
    header('Content-Type: application/json');
    try {
        switch ($__ADMIN_POST['action']) {
            case 'add_doctor':
                echo json_encode(addDoctor($__ADMIN_POST));
                break;
            case 'update_doctor':
                echo json_encode(updateDoctor($__ADMIN_POST));
                break;
            case 'delete_doctor':
                echo json_encode(deleteDoctor((int)($__ADMIN_POST['doctor_id'] ?? 0)));
                break;
            case 'approve_doctor':
                echo json_encode(approveDoctor((int)($__ADMIN_POST['doctor_id'] ?? 0)));
                break;
            case 'deactivate_doctor':
                echo json_encode(deactivateDoctor((int)($__ADMIN_POST['doctor_id'] ?? 0)));
                break;
            case 'add_specialty':
                echo json_encode(addSpecialty($__ADMIN_POST));
                break;
            case 'update_specialty':
                echo json_encode(updateSpecialty($__ADMIN_POST));
                break;
            case 'delete_specialty':
                echo json_encode(deleteSpecialty((int)($__ADMIN_POST['specialty_id'] ?? 0)));
                break;
            case 'update_appointment_status':
                echo json_encode(updateAppointmentStatus($__ADMIN_POST));
                break;
            case 'delete_user':
                echo json_encode(deleteUser((int)($__ADMIN_POST['user_id'] ?? 0)));
                break;
            case 'get_payment_info':
                echo json_encode(getAppointmentPaymentInfo((int)($__ADMIN_POST['appointment_id'] ?? 0)));
                break;
            case 'update_payment':
                echo json_encode(updatePaymentStatus($__ADMIN_POST));
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        }
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
    }
    exit;
}


// Lấy dữ liệu cho dashboard (giữ nguyên logic từ code gốc)
// ... (Phần lấy dữ liệu $stats, $popular_services, $alerts, $statusCounts, $doctors, $specialties, $appointments, $users) ...
// (Đảm bảo $pdo đã được khởi tạo bằng getDBConnection())

// Kiểm tra kết nối database
if (!$pdo) {
    die('<div class="alert alert-danger" role="alert">
        <h4 class="alert-heading">Lỗi kết nối cơ sở dữ liệu!</h4>
        <p>Không thể kết nối đến cơ sở dữ liệu. Vui lòng kiểm tra:</p>
        <ul>
            <li>XAMPP đã được khởi động chưa?</li>
            <li>MySQL service đang chạy chưa?</li>
            <li>Cấu hình database trong db_config.php có đúng không?</li>
        </ul>
        <hr>
        <p class="mb-0">Liên hệ quản trị viên hệ thống để được hỗ trợ.</p>
    </div>');
}

// Thống kê tổng quan
$stats = [];
// Tổng số bệnh nhân / bác sĩ / lịch khám / chuyên khoa
$stmt = $pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'benh_nhan'");
$stats['patients'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM bac_si");
$stats['doctors'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM lich_hen");
$stats['appointments'] = (int)$stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM chuyen_khoa");
$stats['specialties'] = (int)$stmt->fetchColumn();

// Số liệu nhanh
$today = date('Y-m-d');
$monday = date('Y-m-d', strtotime('monday this week'));
$sunday = date('Y-m-d', strtotime('sunday this week'));

$stmt = $pdo->prepare("SELECT COUNT(*) FROM lich_hen WHERE ngay_hen = ?");
$stmt->execute([$today]);
$stats['appointments_today'] = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM lich_hen WHERE ngay_hen BETWEEN ? AND ?");
$stmt->execute([$monday, $sunday]);
$stats['appointments_week'] = (int)$stmt->fetchColumn();

try {
    // Doanh thu hôm nay - tính tất cả lịch hẹn (trừ đã hủy)
    $stmt = $pdo->prepare("SELECT 
                            COALESCE(SUM(CASE WHEN lh.tong_phi > 0 THEN lh.tong_phi ELSE b.phi_kham END), 0) AS revenue, 
                            COUNT(*) AS visits
                            FROM lich_hen lh 
                            JOIN bac_si b ON lh.bac_si_id = b.id
                            WHERE lh.trang_thai NOT IN ('da_huy', 'vang_mat') 
                            AND lh.ngay_hen = ?");
    $stmt->execute([$today]);
    $row = $stmt->fetch();
    $stats['revenue_today'] = (float)$row['revenue'];
    $stats['visits_today'] = (int)$row['visits'];

    // Doanh thu tuần này
    $stmt = $pdo->prepare("SELECT 
                            COALESCE(SUM(CASE WHEN lh.tong_phi > 0 THEN lh.tong_phi ELSE b.phi_kham END), 0) AS revenue, 
                            COUNT(*) AS visits
                            FROM lich_hen lh 
                            JOIN bac_si b ON lh.bac_si_id = b.id
                            WHERE lh.trang_thai NOT IN ('da_huy', 'vang_mat') 
                            AND lh.ngay_hen BETWEEN ? AND ?");
    $stmt->execute([$monday, $sunday]);
    $row = $stmt->fetch();
    $stats['revenue_week'] = (float)$row['revenue'];
    $stats['visits_week'] = (int)$row['visits'];
    
    // Tổng doanh thu - tất cả lịch hẹn không bị hủy
    $stmt = $pdo->query("SELECT COALESCE(SUM(CASE WHEN lh.tong_phi > 0 THEN lh.tong_phi ELSE b.phi_kham END), 0) AS total 
                         FROM lich_hen lh 
                         JOIN bac_si b ON lh.bac_si_id = b.id
                         WHERE lh.trang_thai NOT IN ('da_huy', 'vang_mat')");
    $stats['total_revenue'] = (float)$stmt->fetchColumn();
    
    // Tổng doanh thu tháng này
    $firstDayOfMonth = date('Y-m-01');
    $lastDayOfMonth = date('Y-m-t');
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN lh.tong_phi > 0 THEN lh.tong_phi ELSE b.phi_kham END), 0) AS total 
                           FROM lich_hen lh 
                           JOIN bac_si b ON lh.bac_si_id = b.id
                           WHERE lh.trang_thai NOT IN ('da_huy', 'vang_mat') 
                           AND lh.ngay_hen BETWEEN ? AND ?");
    $stmt->execute([$firstDayOfMonth, $lastDayOfMonth]);
    $stats['revenue_month'] = (float)$stmt->fetchColumn();
    
} catch (Exception $e) {
    $stats['revenue_today'] = $stats['revenue_week'] = 0;
    $stats['visits_today'] = $stats['visits_week'] = 0;
    $stats['total_revenue'] = $stats['revenue_month'] = 0;
}

// Thống kê đánh giá
$stats['total_reviews'] = 0;
$stats['avg_rating'] = 0;
$reviews = [];
try {
    // Tổng số đánh giá
    $stmt = $pdo->query("SELECT COUNT(*) FROM danh_gia WHERE trang_thai = 'hien_thi'");
    $stats['total_reviews'] = (int)$stmt->fetchColumn();
    
    // Điểm đánh giá trung bình
    $stmt = $pdo->query("SELECT AVG(diem) FROM danh_gia WHERE trang_thai = 'hien_thi'");
    $stats['avg_rating'] = round((float)$stmt->fetchColumn(), 1);
    
    // Lấy danh sách đánh giá gần đây với thông tin chi tiết
    $stmt = $pdo->query("SELECT dg.*, 
                                dg.diem as rating,
                                dg.noi_dung as comment,
                                dg.ngay_tao as created_at,
                                n.ho_ten as patient_name,
                                b.ho_ten as doctor_name,
                                c.ten as specialty_name,
                                lh.loai_tu_van,
                                lh.ngay_hen as appointment_date
                         FROM danh_gia dg
                         JOIN nguoi_dung n ON dg.nguoi_dung_id = n.id
                         JOIN bac_si b ON dg.bac_si_id = b.id
                         LEFT JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id
                         LEFT JOIN lich_hen lh ON dg.lich_hen_id = lh.id
                         WHERE dg.trang_thai = 'hien_thi'
                         ORDER BY dg.ngay_tao DESC
                         LIMIT 20");
    $reviews = $stmt->fetchAll();
} catch (Exception $e) {
    $reviews = [];
}

$popular_services = [];
try {
    $since = date('Y-m-d', strtotime('-30 days'));
    $stmt = $pdo->prepare("SELECT ck.ten as name, COUNT(*) AS total
                           FROM lich_hen lh
                           JOIN bac_si b ON lh.bac_si_id = b.id
                           JOIN chuyen_khoa ck ON b.chuyen_khoa_id = ck.id
                           WHERE lh.ngay_hen >= ?
                           GROUP BY ck.id, ck.ten
                           ORDER BY total DESC
                           LIMIT 5");
    $stmt->execute([$since]);
    $popular_services = $stmt->fetchAll();
} catch (Exception $e) {
    $popular_services = [];
}

$alerts = [];
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lich_hen WHERE trang_thai='da_huy' AND ngay_hen = ?");
    $stmt->execute([$today]);
    $cancelled_today = (int)$stmt->fetchColumn();
    if ($cancelled_today > 0) {
        $alerts[] = "Hôm nay có $cancelled_today lịch khám đã hủy.";
    }
} catch (Exception $e) { }

try {
    $since7 = date('Y-m-d', strtotime('-7 days'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM danh_gia WHERE ngay_tao >= ?");
    $stmt->execute([$since7]);
    $feedback_count = (int)$stmt->fetchColumn();
    if ($feedback_count > 0) {
        $alerts[] = "$feedback_count phản hồi mới trong 7 ngày qua.";
    }
} catch (Exception $e) { }

$statusCounts = [
    'cho_xac_nhan' => 0,
    'da_xac_nhan' => 0,
    'hoan_thanh' => 0,
    'da_huy' => 0,
];
try {
    $stmt = $pdo->query("SELECT trang_thai, COUNT(*) AS total FROM lich_hen GROUP BY trang_thai");
    foreach ($stmt->fetchAll() as $r) {
        $k = $r['trang_thai'];
        if (isset($statusCounts[$k])) $statusCounts[$k] = (int)$r['total'];
    }
} catch (Exception $e) { }

try {
    $stmt = $pdo->query("
        SELECT b.*, b.ho_ten as full_name, n.email, n.so_dien_thoai as phone, n.trang_thai as status, ck.ten as specialty_name 
        FROM bac_si b 
        JOIN nguoi_dung n ON b.nguoi_dung_id = n.id 
        LEFT JOIN chuyen_khoa ck ON b.chuyen_khoa_id = ck.id 
        ORDER BY n.trang_thai ASC, b.ngay_tao DESC
    ");
    $doctors = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching doctors: " . $e->getMessage());
    $doctors = [];
}

try {
    $stmt = $pdo->query("SELECT id, ten as name, mo_ta as description, icon, mau_sac as color, thu_tu as sort_order FROM chuyen_khoa ORDER BY thu_tu, ten");
    $specialties = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching specialties: " . $e->getMessage());
    $specialties = [];
}

try {
    $stmt = $pdo->query("
        SELECT lh.*, lh.ten_benh_nhan as patient_name, b.ho_ten as doctor_name, ck.ten as specialty_name,
               b.phi_kham as consultation_fee, lh.trang_thai_thanh_toan as payment_status
        FROM lich_hen lh
        JOIN bac_si b ON lh.bac_si_id = b.id
        LEFT JOIN chuyen_khoa ck ON b.chuyen_khoa_id = ck.id
        ORDER BY lh.ngay_hen DESC, lh.gio_hen DESC
        LIMIT 50
    ");
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching appointments: " . $e->getMessage());
    $appointments = [];
}

try {
    $stmt = $pdo->query("SELECT id, ten_dang_nhap as username, email, ho_ten as full_name, so_dien_thoai as phone, vai_tro as role, trang_thai as status, ngay_tao as created_at FROM nguoi_dung ORDER BY ngay_tao DESC LIMIT 50");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $users = [];
}
?>

<!-- Phần HTML dashboard giữ nguyên như code gốc -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Trị Viên - KhamCare</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- Fix Overlay Click Issues -->
    <link rel="stylesheet" href="fix-overlay-click.css">
    <!-- ... giữ nguyên phần style ... -->
    <style>
        body {
            background: #f4f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #334155;
        }
        
        /* Sidebar */
        .sidebar {
            background: #111827;
            min-height: 100vh;
            position: sticky;
            top: 0;
        }
        .sidebar .nav-link {
            color: #cbd5e1;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            border-left: 3px solid transparent;
            transition: background .15s, color .15s, border-color .15s;
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,.06);
            color: #ffffff;
        }
        .sidebar .nav-link.active {
            background: rgba(37,99,235,.18);
            color: #ffffff;
            border-left-color: #60a5fa;
        }

        /* Main content */
        .main-content {
            background: transparent;
        }
        .content-section h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        /* ========== DASHBOARD STYLES - NEW DESIGN ========== */
        
        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 1.5rem 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        
        .dashboard-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }
        
        .dashboard-subtitle {
            color: #64748b;
            font-size: 1rem;
        }
        
        .dashboard-date {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* Dashboard Stat Cards */
        .dashboard-stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .dashboard-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .dashboard-stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .dashboard-stat-card.patients .stat-icon {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb;
        }
        
        .dashboard-stat-card.doctors .stat-icon {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }
        
        .dashboard-stat-card.appointments .stat-icon {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }
        
        .dashboard-stat-card.specialties .stat-icon {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            color: #7c3aed;
        }
        
        .dashboard-stat-card .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }
        
        .dashboard-stat-card .stat-label {
            color: #64748b;
            font-weight: 500;
            margin-top: 0.3rem;
        }
        
        .dashboard-stat-card .stat-trend {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.3rem 0.6rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .dashboard-stat-card .stat-trend.up {
            background: #d1fae5;
            color: #059669;
        }
        
        .dashboard-stat-card .stat-trend.down {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .dashboard-stat-card .stat-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: #10b981;
            font-size: 1.2rem;
        }
        
        /* Dashboard Revenue Cards */
        .dashboard-revenue-card {
            border-radius: 20px;
            padding: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            min-height: 140px;
        }
        
        .dashboard-revenue-card:hover {
            transform: translateY(-5px);
        }
        
        .dashboard-revenue-card.total {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.3);
        }
        
        .dashboard-revenue-card.month {
            background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%);
            box-shadow: 0 15px 35px rgba(124, 58, 237, 0.3);
        }
        
        .dashboard-revenue-card .revenue-bg-icon {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 4rem;
            opacity: 0.2;
        }
        
        .dashboard-revenue-card .revenue-label {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        
        .dashboard-revenue-card .revenue-amount {
            font-size: 2rem;
            font-weight: 800;
        }
        
        .dashboard-revenue-card .revenue-amount .currency {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
        }
        
        .dashboard-revenue-card .revenue-sub {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }
        
        /* Quick Stat Cards */
        .quick-stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        
        .quick-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .quick-stat-card .quick-stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        
        .quick-stat-card.today .quick-stat-icon {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb;
        }
        
        .quick-stat-card.week .quick-stat-icon {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            color: #7c3aed;
        }
        
        .quick-stat-card.revenue .quick-stat-icon {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }
        
        .quick-stat-card.visits .quick-stat-icon {
            background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
            color: #db2777;
        }
        
        .quick-stat-card .quick-stat-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
        }
        
        .quick-stat-card .quick-stat-label {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.2rem;
        }
        
        /* Dashboard Rating Card */
        .dashboard-rating-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.2);
        }
        
        .dashboard-rating-card .rating-header {
            background: rgba(180, 83, 9, 0.1);
            padding: 1rem 1.5rem;
            font-weight: 700;
            color: #92400e;
        }
        
        .dashboard-rating-card .rating-body {
            padding: 1.5rem;
            text-align: center;
        }
        
        .dashboard-rating-card .rating-score {
            font-size: 3rem;
            font-weight: 800;
            color: #b45309;
            line-height: 1;
        }
        
        .dashboard-rating-card .rating-stars {
            margin: 0.8rem 0;
        }
        
        .dashboard-rating-card .rating-stars i {
            font-size: 1.3rem;
            color: #d1d5db;
            margin: 0 2px;
        }
        
        .dashboard-rating-card .rating-stars i.active {
            color: #f59e0b;
        }
        
        .dashboard-rating-card .rating-text {
            color: #92400e;
            font-weight: 500;
        }
        
        /* Dashboard Reviews Card */
        .dashboard-reviews-card {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.2);
        }
        
        .dashboard-reviews-card .reviews-header {
            background: rgba(37, 99, 235, 0.1);
            padding: 1rem 1.5rem;
            font-weight: 700;
            color: #1d4ed8;
        }
        
        .dashboard-reviews-card .reviews-body {
            padding: 1.5rem;
            text-align: center;
        }
        
        .dashboard-reviews-card .reviews-number {
            font-size: 3rem;
            font-weight: 800;
            color: #1d4ed8;
            line-height: 1;
        }
        
        .dashboard-reviews-card .reviews-text {
            color: #1e40af;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        /* Dashboard Week Revenue Card */
        .dashboard-week-revenue-card {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.2);
        }
        
        .dashboard-week-revenue-card .week-revenue-header {
            background: rgba(5, 150, 105, 0.1);
            padding: 1rem 1.5rem;
            font-weight: 700;
            color: #047857;
        }
        
        .dashboard-week-revenue-card .week-revenue-body {
            padding: 1.5rem;
            text-align: center;
        }
        
        .dashboard-week-revenue-card .week-revenue-amount {
            font-size: 2rem;
            font-weight: 800;
            color: #047857;
            line-height: 1;
        }
        
        .dashboard-week-revenue-card .week-revenue-currency {
            color: #059669;
            font-weight: 600;
            margin-top: 0.3rem;
        }

        /* Old Stat cards - keep for compatibility */
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 6px 24px rgba(15,23,42,.06);
            border: 1px solid #eef2f7;
        }
        .stat-card h3, .stat-card h4 { margin: 0; font-weight: 800; color: #0f172a; }
        .stat-card p { color: #64748b; }
        .stat-card .icon {
            font-size: 28px;
            color: #2563eb;
            background: rgba(37,99,235,.12);
            width: 48px; height: 48px; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
        }

        /* Tables and containers */
        .table-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 6px 24px rgba(15,23,42,.06);
            border: 1px solid #eef2f7;
        }
        .table-container h5 {
            font-weight: 700; color: #0f172a; margin-bottom: 12px;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            color: #475569;
            font-weight: 700;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* Status badges */
        .status-badge {
            padding: 6px 10px;
            border-radius: 999px;
            font-size: .85rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1d4ed8; }
        .status-completed { background: #dcfce7; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        /* Buttons fine-tune */
        .btn-primary {
            background: linear-gradient(90deg,#2563eb 0%,#38bdf8 100%);
            border: none;
        }
        .btn-primary:hover { opacity: .95; }

        /* Utilities */
        .badge.bg-warning { color: #7c2d12; background-color: #fef3c7 !important; }
        .badge.bg-info { color: #0c4a6e; background-color: #e0f2fe !important; }
        
        /* Fix for modal backdrop issues */
        body {
            pointer-events: auto !important;
        }
        .modal-backdrop {
            display: none !important;
        }
        body.modal-open {
            overflow: auto !important;
            padding-right: 0 !important;
        }

        /* Modal Add Doctor Styles */
        #addDoctorModal .modal-content {
            border: none;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }

        #addDoctorModal .modal-header {
            background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
            color: white;
            border: none;
            padding: 20px 25px;
        }

        #addDoctorModal .modal-title {
            font-weight: 700;
            font-size: 1.3rem;
        }

        #addDoctorModal .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        #addDoctorModal .modal-header .btn-close:hover {
            opacity: 1;
        }

        #addDoctorModal .modal-body {
            padding: 25px 30px;
            background: #f8fafc;
        }

        #addDoctorModal .form-label {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        #addDoctorModal .form-label i {
            color: #0ea5e9;
        }

        #addDoctorModal .form-control,
        #addDoctorModal .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        #addDoctorModal .form-control:focus,
        #addDoctorModal .form-select:focus {
            border-color: #0ea5e9;
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15);
            outline: none;
        }

        #addDoctorModal .form-control::placeholder {
            color: #94a3b8;
        }

        #addDoctorModal textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }

        #addDoctorModal .mb-3 {
            margin-bottom: 18px !important;
        }

        #addDoctorModal .row {
            margin-left: -10px;
            margin-right: -10px;
        }

        #addDoctorModal .row > [class*="col-"] {
            padding-left: 10px;
            padding-right: 10px;
        }

        /* File Upload Styles */
        #addDoctorModal .file-upload-wrapper {
            position: relative;
        }

        #addDoctorModal .file-upload-wrapper .form-control[type="file"] {
            padding: 20px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #addDoctorModal .file-upload-wrapper .form-control[type="file"]:hover {
            border-color: #0ea5e9;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(14, 165, 233, 0.02) 100%);
        }

        #addDoctorModal .file-upload-wrapper .form-control[type="file"]::file-selector-button {
            background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-right: 15px;
            transition: all 0.3s ease;
        }

        #addDoctorModal .file-upload-wrapper .form-control[type="file"]::file-selector-button:hover {
            background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
        }

        #addDoctorModal .file-preview-admin {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        #addDoctorModal .file-preview-admin img {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        #addDoctorModal .form-text {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 8px;
        }

        #addDoctorModal .form-text i {
            color: #0ea5e9;
        }

        /* Modal Footer */
        #addDoctorModal .modal-footer {
            background: white;
            border-top: 1px solid #e2e8f0;
            padding: 18px 25px;
            gap: 12px;
        }

        #addDoctorModal .modal-footer .btn {
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        #addDoctorModal .modal-footer .btn-secondary {
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            color: #475569;
        }

        #addDoctorModal .modal-footer .btn-secondary:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
        }

        #addDoctorModal .modal-footer .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.4);
        }

        #addDoctorModal .modal-footer .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.5);
        }

        /* Section dividers in form */
        #addDoctorModal .form-section-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 1rem;
            margin: 20px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        #addDoctorModal .form-section-title:first-child {
            margin-top: 0;
        }
        
        /* ========== REVENUE & RATING CARDS - ADMIN ========== */
        
        /* Revenue Card - Doanh thu */
        .revenue-card {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 20px;
            padding: 1.8rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.3);
            border: none;
            transition: all 0.3s ease;
        }
        
        .revenue-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(16, 185, 129, 0.4);
        }
        
        .revenue-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .revenue-card::after {
            content: '💰';
            position: absolute;
            bottom: 10px;
            right: 20px;
            font-size: 4rem;
            opacity: 0.15;
        }
        
        .revenue-card .revenue-amount {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.3rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .revenue-card .revenue-label {
            font-size: 1rem;
            opacity: 0.95;
            font-weight: 500;
        }
        
        .revenue-card .revenue-icon {
            font-size: 2.5rem;
            opacity: 0.6;
        }
        
        /* Revenue Month Card */
        .revenue-month-card {
            background: linear-gradient(135deg, #7c3aed 0%, #a78bfa 100%);
            border-radius: 20px;
            padding: 1.8rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(124, 58, 237, 0.3);
            border: none;
            transition: all 0.3s ease;
        }
        
        .revenue-month-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(124, 58, 237, 0.4);
        }
        
        .revenue-month-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -30%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .revenue-month-card::after {
            content: '📅';
            position: absolute;
            bottom: 10px;
            right: 20px;
            font-size: 4rem;
            opacity: 0.15;
        }
        
        /* Rating Card - Đánh giá */
        .admin-rating-card {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            border-radius: 20px;
            padding: 1.8rem;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(245, 158, 11, 0.3);
            border: none;
            transition: all 0.3s ease;
        }
        
        .admin-rating-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(245, 158, 11, 0.4);
        }
        
        .admin-rating-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(255,255,255,0.25) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .admin-rating-card .rating-stars {
            margin-bottom: 0.5rem;
        }
        
        .admin-rating-card .rating-stars i {
            font-size: 1.3rem;
            margin: 0 2px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .admin-rating-card .rating-score {
            font-size: 2.5rem;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-rating-card .rating-label {
            font-size: 0.95rem;
            opacity: 0.95;
            font-weight: 500;
        }
        
        /* Total Reviews Card */
        .admin-reviews-card {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            border-radius: 20px;
            padding: 1.8rem;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.3);
            border: none;
            transition: all 0.3s ease;
        }
        
        .admin-reviews-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(59, 130, 246, 0.4);
        }
        
        .admin-reviews-card::before {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .admin-reviews-card .reviews-count {
            font-size: 2.5rem;
            font-weight: 800;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-reviews-card .reviews-label {
            font-size: 0.95rem;
            opacity: 0.95;
            font-weight: 500;
        }
        
        /* Revenue Today Card */
        .revenue-today-card {
            background: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 100%);
            border-radius: 20px;
            padding: 1.8rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(14, 165, 233, 0.3);
            border: none;
            transition: all 0.3s ease;
        }
        
        .revenue-today-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(14, 165, 233, 0.4);
        }
        
        .revenue-today-card::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -20%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            pointer-events: none;
        }
        
        /* Revenue Week Card */
        .revenue-week-card {
            background: linear-gradient(135deg, #ec4899 0%, #f472b6 100%);
            border-radius: 20px;
            padding: 1.8rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(236, 72, 153, 0.3);
            border: none;
            transition: all 0.3s ease;
        }
        
        .revenue-week-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(236, 72, 153, 0.4);
        }
        
        .revenue-week-card::before {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            pointer-events: none;
        }
        
        /* Common styles for revenue/rating cards */
        .revenue-card .card-amount,
        .revenue-month-card .card-amount,
        .revenue-today-card .card-amount,
        .revenue-week-card .card-amount {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 0.3rem;
        }
        
        .revenue-card .card-label,
        .revenue-month-card .card-label,
        .revenue-today-card .card-label,
        .revenue-week-card .card-label {
            font-size: 0.95rem;
            opacity: 0.9;
        }
        
        .revenue-card .card-icon,
        .revenue-month-card .card-icon,
        .revenue-today-card .card-icon,
        .revenue-week-card .card-icon {
            font-size: 2.5rem;
            opacity: 0.5;
        }
        
        /* Reviews Section Styles */
        .reviews-summary-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            border: none;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.2);
        }
        
        .reviews-summary-card .big-rating {
            font-size: 4rem;
            font-weight: 800;
            color: #b45309;
            line-height: 1;
        }
        
        .reviews-summary-card .rating-stars i {
            font-size: 1.8rem;
            color: #f59e0b;
            margin: 0 3px;
        }
        
        .reviews-summary-card .rating-stars i.opacity-50 {
            color: #d1d5db;
        }
        
        .reviews-summary-card .total-text {
            color: #92400e;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        /* Review Item Card */
        .review-item-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .review-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #f59e0b;
        }
        
        .review-item-card .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .review-item-card .review-stars i {
            color: #fbbf24;
        }
        
        .review-item-card .review-stars i.text-muted {
            color: #d1d5db !important;
        }
        
        /* Revenue Section Cards */
        .revenue-section-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .revenue-section-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .revenue-section-card .section-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        
        .revenue-section-card .section-icon.green {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }
        
        .revenue-section-card .section-icon.purple {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            color: #7c3aed;
        }
        
        .revenue-section-card .section-icon.blue {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb;
        }
        
        .revenue-section-card .section-icon.pink {
            background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%);
            color: #db2777;
        }
        
        .revenue-section-card .amount {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
        }
        
        .revenue-section-card .label {
            color: #64748b;
            font-weight: 500;
        }
        
        /* ========== REVENUE PAGE STYLES ========== */
        
        /* Revenue Stat Cards */
        .revenue-stat-card {
            border-radius: 16px;
            padding: 1.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            min-height: 140px;
        }
        
        .revenue-stat-card:hover {
            transform: translateY(-5px);
        }
        
        .revenue-stat-card.green {
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }
        
        .revenue-stat-card.blue {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
        }
        
        .revenue-stat-card.purple {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.3);
        }
        
        .revenue-stat-card.orange {
            background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3);
        }
        
        .revenue-stat-card .revenue-stat-amount {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.2;
        }
        
        .revenue-stat-card .revenue-stat-currency {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }
        
        .revenue-stat-card .revenue-stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-top: 0.5rem;
        }
        
        .revenue-stat-card .revenue-stat-icon {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.3;
        }
        
        /* Payment Status Card */
        .payment-status-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        
        .payment-status-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .payment-status-header i {
            color: #3b82f6;
            font-size: 1.1rem;
        }
        
        .payment-status-body {
            padding: 1.5rem;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        
        .payment-stat {
            flex: 1;
            padding: 1rem;
            border-radius: 12px;
            margin: 0 0.5rem;
        }
        
        .payment-stat.paid {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }
        
        .payment-stat.unpaid {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        }
        
        .payment-stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }
        
        .payment-stat.paid .payment-stat-number {
            color: #059669;
        }
        
        .payment-stat.unpaid .payment-stat-number {
            color: #dc2626;
        }
        
        .payment-stat-label {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 0.5rem;
            font-weight: 500;
        }
        
        /* Consultation Type Card */
        .consultation-type-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        
        .consultation-type-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .consultation-type-header i {
            color: #10b981;
            font-size: 1.1rem;
        }
        
        .consultation-type-body {
            padding: 1.5rem;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        
        .consultation-stat {
            flex: 1;
            padding: 1rem;
            border-radius: 12px;
            margin: 0 0.5rem;
        }
        
        .consultation-stat.online {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        }
        
        .consultation-stat.offline {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
        }
        
        .consultation-stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }
        
        .consultation-stat.online .consultation-stat-number {
            color: #2563eb;
        }
        
        .consultation-stat.offline .consultation-stat-number {
            color: #7c3aed;
        }
        
        .consultation-stat-label {
            font-size: 0.9rem;
            color: #64748b;
            margin-top: 0.5rem;
            font-weight: 500;
        }
        
        .consultation-stat-label i {
            margin-right: 0.25rem;
        }
        
        .consultation-stat.online .consultation-stat-label i {
            color: #2563eb;
        }
        
        .consultation-stat.offline .consultation-stat-label i {
            color: #7c3aed;
        }
        
        .consultation-stat-revenue {
            font-size: 0.85rem;
            color: #059669;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        /* Recent Payments Card */
        .recent-payments-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        
        .recent-payments-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 700;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .recent-payments-header i {
            color: #f59e0b;
            font-size: 1.1rem;
        }
        
        .recent-payments-body {
            padding: 0;
        }
        
        .recent-payments-body .table {
            margin-bottom: 0;
        }
        
        .recent-payments-body .table thead th {
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            color: #475569;
            padding: 1rem;
            font-size: 0.9rem;
        }
        
        .recent-payments-body .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .recent-payments-body .table tbody tr:hover {
            background: #f8fafc;
        }
        
        /* Payment Status Badges */
        .payment-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .payment-badge.paid {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }
        
        .payment-badge.unpaid {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
        }
        
        .payment-badge.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #b45309;
        }
        
        /* Consultation Type Badges */
        .consultation-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .consultation-badge.online {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb;
        }
        
        .consultation-badge.offline {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            color: #7c3aed;
        }
        
        /* Amount Display */
        .amount-display {
            font-weight: 700;
            color: #059669;
        }
        
        .amount-display.large {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-4 text-center">
                        <img src="image/logo.png.png" alt="KhamCare Logo" style="width: 60px; height: 60px; border-radius: 12px; margin-bottom: 10px;">
                        <h4 class="text-white mb-0">
                            KhamCare Admin
                        </h4>
                        <small class="text-white-50">Quản trị hệ thống</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link<?php echo $current_section == 'dashboard' ? ' active' : ''; ?>" href="#dashboard" data-section="dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Tổng quan
                        </a>
                        <a class="nav-link<?php echo $current_section == 'doctors' ? ' active' : ''; ?>" href="#doctors" data-section="doctors">
                            <i class="fas fa-user-md me-2"></i>
                            Quản lý bác sĩ
                        </a>
                        <a class="nav-link<?php echo $current_section == 'specialties' ? ' active' : ''; ?>" href="#specialties" data-section="specialties">
                            <i class="fas fa-stethoscope me-2"></i>
                            Chuyên khoa
                        </a>
                        <a class="nav-link<?php echo $current_section == 'appointments' ? ' active' : ''; ?>" href="#appointments" data-section="appointments">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Lịch khám
                        </a>
                        <a class="nav-link<?php echo $current_section == 'users' ? ' active' : ''; ?>" href="#users" data-section="users">
                            <i class="fas fa-users me-2"></i>
                            Người dùng
                        </a>
                        <a class="nav-link<?php echo $current_section == 'reviews' ? ' active' : ''; ?>" href="#reviews" data-section="reviews">
                            <i class="fas fa-star me-2"></i>
                            Đánh giá
                            <?php if ($stats['total_reviews'] > 0): ?>
                                <span class="badge bg-warning text-dark ms-1"><?php echo $stats['total_reviews']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link<?php echo $current_section == 'revenue' ? ' active' : ''; ?>" href="#revenue" data-section="revenue">
                            <i class="fas fa-chart-line me-2"></i>
                            Doanh thu
                        </a>
                        <a class="nav-link" href="?action=logout" id="admin-logout-link">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            Đăng xuất
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    
                    <!-- Dashboard Section -->
                    <div id="dashboard-section" class="content-section">
                        <div class="dashboard-header mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="dashboard-title"><i class="fas fa-tachometer-alt me-2"></i>Tổng quan hệ thống</h2>
                                    <p class="dashboard-subtitle mb-0">Xin chào, <?php echo htmlspecialchars($admin_info['full_name']); ?>! 👋</p>
                                </div>
                                <div class="dashboard-date">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    <?php echo date('d/m/Y'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Main Statistics Cards - NEW DESIGN -->
                        <div class="row mb-4 g-3">
                            <div class="col-md-3">
                                <div class="dashboard-stat-card patients">
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $stats['patients']; ?></div>
                                        <div class="stat-label">Bệnh nhân</div>
                                    </div>
                                    <div class="stat-trend up">
                                        <i class="fas fa-arrow-up"></i> +12%
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="dashboard-stat-card doctors">
                                    <div class="stat-icon">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $stats['doctors']; ?></div>
                                        <div class="stat-label">Bác sĩ</div>
                                    </div>
                                    <div class="stat-trend up">
                                        <i class="fas fa-arrow-up"></i> +5%
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="dashboard-stat-card appointments">
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $stats['appointments']; ?></div>
                                        <div class="stat-label">Lịch khám</div>
                                    </div>
                                    <div class="stat-trend up">
                                        <i class="fas fa-arrow-up"></i> +18%
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="dashboard-stat-card specialties">
                                    <div class="stat-icon">
                                        <i class="fas fa-stethoscope"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $stats['specialties']; ?></div>
                                        <div class="stat-label">Chuyên khoa</div>
                                    </div>
                                    <div class="stat-badge">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue Cards - NEW DESIGN -->
                        <div class="row mb-4 g-3">
                            <div class="col-md-6">
                                <div class="dashboard-revenue-card total">
                                    <div class="revenue-bg-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="revenue-content">
                                        <div class="revenue-label">💰 Tổng doanh thu</div>
                                        <div class="revenue-amount"><?php echo number_format($stats['total_revenue'] ?? 0); ?> <span class="currency">VNĐ</span></div>
                                        <div class="revenue-sub">Tất cả lịch hẹn đã thanh toán</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="dashboard-revenue-card month">
                                    <div class="revenue-bg-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="revenue-content">
                                        <div class="revenue-label">📅 Doanh thu tháng <?php echo date('m/Y'); ?></div>
                                        <div class="revenue-amount"><?php echo number_format($stats['revenue_month'] ?? 0); ?> <span class="currency">VNĐ</span></div>
                                        <div class="revenue-sub">Tháng hiện tại</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats Row - NEW DESIGN -->
                        <div class="row mb-4 g-3">
                            <div class="col-md-3">
                                <div class="quick-stat-card today">
                                    <div class="quick-stat-icon">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                    <div class="quick-stat-info">
                                        <div class="quick-stat-number"><?php echo $stats['appointments_today']; ?></div>
                                        <div class="quick-stat-label">Lịch hẹn hôm nay</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="quick-stat-card week">
                                    <div class="quick-stat-icon">
                                        <i class="fas fa-calendar-week"></i>
                                    </div>
                                    <div class="quick-stat-info">
                                        <div class="quick-stat-number"><?php echo $stats['appointments_week']; ?></div>
                                        <div class="quick-stat-label">Lịch hẹn trong tuần</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="quick-stat-card revenue">
                                    <div class="quick-stat-icon">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <div class="quick-stat-info">
                                        <div class="quick-stat-number"><?php echo number_format($stats['revenue_today']); ?></div>
                                        <div class="quick-stat-label">Doanh thu hôm nay</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="quick-stat-card visits">
                                    <div class="quick-stat-icon">
                                        <i class="fas fa-procedures"></i>
                                    </div>
                                    <div class="quick-stat-info">
                                        <div class="quick-stat-number"><?php echo $stats['visits_week']; ?></div>
                                        <div class="quick-stat-label">Lượt khám trong tuần</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rating & Revenue Week - NEW DESIGN -->
                        <div class="row mb-4 g-3">
                            <div class="col-md-4">
                                <div class="dashboard-rating-card">
                                    <div class="rating-header">
                                        <span>⭐ Đánh giá trung bình</span>
                                    </div>
                                    <div class="rating-body">
                                        <div class="rating-score"><?php echo $stats['avg_rating'] ?? 0; ?></div>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= round($stats['avg_rating'] ?? 0) ? 'active' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="rating-text">trên 5 điểm</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="dashboard-reviews-card">
                                    <div class="reviews-header">
                                        <span>💬 Tổng đánh giá</span>
                                    </div>
                                    <div class="reviews-body">
                                        <div class="reviews-number"><?php echo $stats['total_reviews'] ?? 0; ?></div>
                                        <div class="reviews-text">đánh giá từ bệnh nhân</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="dashboard-week-revenue-card">
                                    <div class="week-revenue-header">
                                        <span>📊 Doanh thu tuần</span>
                                    </div>
                                    <div class="week-revenue-body">
                                        <div class="week-revenue-amount"><?php echo number_format($stats['revenue_week']); ?></div>
                                        <div class="week-revenue-currency">VNĐ</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Popular services last 30 days -->
                        <div class="table-container mb-4">
                            <h5><i class="fas fa-star me-2"></i>Dịch vụ/Chuyên khoa phổ biến (30 ngày)</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Chuyên khoa</th>
                                            <th>Số lượt hẹn</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($popular_services)) { foreach($popular_services as $svc): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($svc['name']); ?></td>
                                            <td><?php echo (int)$svc['total']; ?></td>
                                        </tr>
                                        <?php endforeach; } else { ?>
                                        <tr><td colspan="2" class="text-muted">Chưa có dữ liệu</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Alerts -->
                        <div class="table-container mb-4">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Thông báo</h5>
                            <ul>
                                <?php if (!empty($alerts)) { foreach($alerts as $al): ?>
                                    <li><?php echo htmlspecialchars($al); ?></li>
                                <?php endforeach; } else { ?>
                                    <li class="text-muted">Không có thông báo.</li>
                                <?php } ?>
                            </ul>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-lg-8">
                                <div class="table-container">
                                    <h5><i class="fas fa-chart-bar me-2"></i>Thống kê trạng thái lịch khám</h5>
                                    <canvas id="chartAppointments" height="160"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="table-container">
                                    <h5><i class="fas fa-chart-pie me-2"></i>Chuyên khoa phổ biến</h5>
                                    <canvas id="chartServices" height="160"></canvas>
                                </div>
                                <div class="table-container mt-4">
                                    <h5><i class="fas fa-newspaper me-2"></i>Tin tức & cập nhật</h5>
                                    <ul>
                                        <li>Hệ thống cập nhật tính năng đặt lịch nhanh.</li>
                                        <li>Đã tối ưu tốc độ tải dữ liệu lịch khám.</li>
                                        <li>Thêm thống kê doanh thu theo tuần.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Appointments -->
                        <div class="table-container">
                            <h5><i class="fas fa-clock me-2"></i>Lịch khám gần đây</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Bệnh nhân</th>
                                            <th>Bác sĩ</th>
                                            <th>Chuyên khoa</th>
                                            <th>Ngày khám</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach(array_slice($appointments, 0, 10) as $appointment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['doctor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($appointment['specialty_name'] ?? ''); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($appointment['ngay_hen'] . ' ' . $appointment['gio_hen'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $appointment['trang_thai']; ?>">
                                                    <?php 
                                                    $status_text = [
                                                        'cho_xac_nhan' => 'Chờ xác nhận',
                                                        'da_xac_nhan' => 'Đã xác nhận',
                                                        'hoan_thanh' => 'Hoàn thành',
                                                        'da_huy' => 'Đã hủy'
                                                    ];
                                                    echo $status_text[$appointment['trang_thai']] ?? $appointment['trang_thai'];
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Doctors Section -->
                    <div id="doctors-section" class="content-section" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-user-md me-2"></i>Quản lý bác sĩ</h2>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">
                                <i class="fas fa-plus me-2"></i>Thêm bác sĩ
                            </button>
                        </div>
                        
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Họ tên</th>
                                            <th>Email</th>
                                            <th>Số điện thoại</th>
                                            <th>Chuyên khoa</th>
                                            <th>Kinh nghiệm</th>
                                            <th>Phí khám</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($doctors as $doctor): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($doctor['full_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($doctor['email'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($doctor['phone'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($doctor['specialty_name'] ?? ''); ?></td>
                                            <td><?php echo isset($doctor['nam_kinh_nghiem']) ? $doctor['nam_kinh_nghiem'] : 0; ?> năm</td>
                                            <td><?php echo number_format(isset($doctor['phi_kham']) ? $doctor['phi_kham'] : 0); ?> VNĐ</td>
                                            <td>
                                                <?php 
                                                $status = isset($doctor['status']) ? $doctor['status'] : 'hoat_dong';
                                                if ($status === 'cho_duyet'): ?>
                                                    <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                                <?php elseif ($status === 'hoat_dong'): ?>
                                                    <span class="badge bg-success">Đã duyệt</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Vô hiệu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($status !== 'hoat_dong'): ?>
                                                <button class="btn btn-sm btn-success approve-doctor" data-id="<?php echo $doctor['id']; ?>" title="Duyệt bác sĩ">
                                                    <i class="fas fa-check"></i> Duyệt
                                                </button>
                                                <?php else: ?>
                                                <button class="btn btn-sm btn-outline-secondary deactivate-doctor" data-id="<?php echo $doctor['id']; ?>" title="Hủy kích hoạt">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-warning edit-doctor" data-id="<?php echo $doctor['id']; ?>" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-doctor" data-id="<?php echo $doctor['id']; ?>" title="Xóa">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Specialties Section -->
                    <div id="specialties-section" class="content-section" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-stethoscope me-2"></i>Quản lý chuyên khoa</h2>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSpecialtyModal">
                                <i class="fas fa-plus me-2"></i>Thêm chuyên khoa
                            </button>
                        </div>
                        
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tên chuyên khoa</th>
                                            <th>Mô tả</th>
                                            <th>Icon</th>
                                            <th>Màu sắc</th>
                                            <th>Thứ tự</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($specialties as $specialty): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($specialty['name']); ?></td>
                                            <td><?php echo htmlspecialchars($specialty['description']); ?></td>
                                            <td><i class="<?php echo htmlspecialchars(isset($specialty['icon']) ? $specialty['icon'] : 'fas fa-stethoscope'); ?>"></i></td>
                                            <td>
                                                <span class="badge" style="background-color: <?php echo isset($specialty['color']) ? $specialty['color'] : '#2ecc71'; ?>; color: white;">
                                                    <?php echo isset($specialty['color']) ? $specialty['color'] : '#2ecc71'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo isset($specialty['sort_order']) ? $specialty['sort_order'] : 0; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning edit-specialty" data-id="<?php echo $specialty['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger delete-specialty" data-id="<?php echo $specialty['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Appointments Section -->
                    <div id="appointments-section" class="content-section" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-calendar-alt me-2"></i>Quản lý lịch khám</h2>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary">Tổng: <?php echo count($appointments); ?> lịch hẹn</span>
                            </div>
                        </div>
                        
                        <!-- Bộ lọc -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label"><i class="fas fa-search me-1"></i>Tìm kiếm</label>
                                        <input type="text" class="form-control" id="appointmentSearch" placeholder="Tên bệnh nhân, bác sĩ...">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><i class="fas fa-filter me-1"></i>Trạng thái</label>
                                        <select class="form-select" id="appointmentStatusFilter">
                                            <option value="">Tất cả</option>
                                            <option value="pending">Chờ xác nhận</option>
                                            <option value="confirmed">Đã xác nhận</option>
                                            <option value="completed">Hoàn thành</option>
                                            <option value="cancelled">Đã hủy</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><i class="fas fa-stethoscope me-1"></i>Chuyên khoa</label>
                                        <select class="form-select" id="appointmentSpecialtyFilter">
                                            <option value="">Tất cả</option>
                                            <?php 
                                            $uniqueSpecialties = array_unique(array_column($appointments, 'specialty_name'));
                                            foreach($uniqueSpecialties as $spec): 
                                            ?>
                                                <option value="<?php echo htmlspecialchars($spec); ?>"><?php echo htmlspecialchars($spec); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><i class="fas fa-calendar me-1"></i>Từ ngày</label>
                                        <input type="date" class="form-control" id="appointmentDateFrom">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><i class="fas fa-calendar me-1"></i>Đến ngày</label>
                                        <input type="date" class="form-control" id="appointmentDateTo">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button class="btn btn-secondary w-100" onclick="resetAppointmentFilters()">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover" id="appointmentsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Bệnh nhân</th>
                                            <th>Bác sĩ</th>
                                            <th>Chuyên khoa</th>
                                            <th>Ngày khám</th>
                                            <th>Giờ</th>
                                            <th>Loại khám</th>
                                            <th>Trạng thái</th>
                                            <th>Phí khám</th>
                                            <th>Thanh toán</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($appointments as $appointment): ?>
                                        <?php 
                                            $paymentStatus = $appointment['payment_status'] ?? $appointment['trang_thai_thanh_toan'] ?? 'chua_thanh_toan';
                                            $fee = isset($appointment['tong_phi']) && $appointment['tong_phi'] > 0 
                                                   ? $appointment['tong_phi'] 
                                                   : ($appointment['consultation_fee'] ?? 0);
                                        ?>
                                        <tr data-status="<?php echo $appointment['trang_thai']; ?>" 
                                            data-specialty="<?php echo htmlspecialchars($appointment['specialty_name'] ?? ''); ?>"
                                            data-date="<?php echo $appointment['ngay_hen']; ?>"
                                            data-payment="<?php echo $paymentStatus; ?>"
                                            data-search="<?php echo strtolower(htmlspecialchars($appointment['patient_name'] . ' ' . $appointment['doctor_name'] . ' ' . ($appointment['specialty_name'] ?? ''))); ?>">
                                            <td><strong>#<?php echo $appointment['id']; ?></strong></td>
                                            <td>
                                                <i class="fas fa-user-injured me-1 text-primary"></i>
                                                <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user-md me-1 text-success"></i>
                                                <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($appointment['specialty_name'] ?? ''); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($appointment['ngay_hen'])); ?></td>
                                            <td><?php echo date('H:i', strtotime($appointment['gio_hen'])); ?></td>
                                            <td>
                                                <?php $loai_tu_van = $appointment['loai_tu_van'] ?? 'truc_tiep'; ?>
                                                <span class="badge bg-<?php echo $loai_tu_van === 'truc_tuyen' ? 'info' : 'warning'; ?>">
                                                    <i class="fas fa-<?php echo $loai_tu_van === 'truc_tuyen' ? 'video' : 'hospital'; ?> me-1"></i>
                                                    <?php echo $loai_tu_van === 'truc_tuyen' ? 'Online' : 'Tại phòng khám'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm status-select" data-id="<?php echo $appointment['id']; ?>" 
                                                        style="min-width: 140px;">
                                                    <option value="cho_xac_nhan" <?php echo $appointment['trang_thai'] === 'cho_xac_nhan' ? 'selected' : ''; ?>>⏳ Chờ xác nhận</option>
                                                    <option value="da_xac_nhan" <?php echo $appointment['trang_thai'] === 'da_xac_nhan' ? 'selected' : ''; ?>>✅ Đã xác nhận</option>
                                                    <option value="hoan_thanh" <?php echo $appointment['trang_thai'] === 'hoan_thanh' ? 'selected' : ''; ?>>✔️ Hoàn thành</option>
                                                    <option value="da_huy" <?php echo $appointment['trang_thai'] === 'da_huy' ? 'selected' : ''; ?>>❌ Đã hủy</option>
                                                </select>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    <?php echo number_format($fee); ?> ₫
                                                </strong>
                                            </td>
                                            <td>
                                                <?php if ($paymentStatus === 'da_thanh_toan'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Đã thanh toán
                                                    </span>
                                                <?php elseif ($paymentStatus === 'hoan_tien'): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-undo me-1"></i>Hoàn tiền
                                                    </span>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-warning btn-payment" 
                                                            data-id="<?php echo $appointment['id']; ?>"
                                                            data-patient="<?php echo htmlspecialchars($appointment['patient_name']); ?>"
                                                            data-doctor="<?php echo htmlspecialchars($appointment['doctor_name']); ?>"
                                                            data-specialty="<?php echo htmlspecialchars($appointment['specialty_name'] ?? ''); ?>"
                                                            data-date="<?php echo date('d/m/Y', strtotime($appointment['ngay_hen'])); ?>"
                                                            data-time="<?php echo date('H:i', strtotime($appointment['gio_hen'])); ?>"
                                                            data-fee="<?php echo $fee; ?>"
                                                            title="Thanh toán">
                                                        <i class="fas fa-money-bill-wave me-1"></i>Thanh toán
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-info view-appointment" data-id="<?php echo $appointment['id']; ?>" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Users Section -->
                    <div id="users-section" class="content-section" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-users me-2"></i>Quản lý người dùng</h2>
                        </div>
                        
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Họ tên</th>
                                            <th>Email</th>
                                            <th>Số điện thoại</th>
                                            <th>Vai trò</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày tạo</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                                            <td>
                                                <?php $role = $user['role'] ?? 'benh_nhan'; ?>
                                                <span class="badge bg-<?php echo $role === 'quan_tri' ? 'danger' : ($role === 'bac_si' ? 'primary' : 'success'); ?>">
                                                    <?php 
                                                    $role_text = [
                                                        'quan_tri' => 'Quản trị',
                                                        'bac_si' => 'Bác sĩ',
                                                        'benh_nhan' => 'Bệnh nhân'
                                                    ];
                                                    echo $role_text[$role] ?? $role;
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php $status = $user['status'] ?? 'hoat_dong'; ?>
                                                <span class="badge bg-<?php echo $status === 'hoat_dong' ? 'success' : 'secondary'; ?>">
                                                    <?php echo $status === 'hoat_dong' ? 'Hoạt động' : 'Không hoạt động'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <?php if(($user['role'] ?? '') !== 'quan_tri'): ?>
                                                <button class="btn btn-sm btn-danger delete-user" data-id="<?php echo $user['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Section -->
                    <div id="reviews-section" class="content-section" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-star me-2"></i>Quản lý đánh giá</h2>
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-warning text-dark fs-6">
                                    <i class="fas fa-star me-1"></i>
                                    Trung bình: <?php echo $stats['avg_rating']; ?>/5
                                </span>
                                <span class="badge bg-primary fs-6">
                                    Tổng: <?php echo $stats['total_reviews']; ?> đánh giá
                                </span>
                            </div>
                        </div>
                        
                        <!-- Thống kê đánh giá - NEW DESIGN -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="reviews-summary-card">
                                    <div class="big-rating"><?php echo $stats['avg_rating']; ?></div>
                                    <div class="rating-stars my-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= round($stats['avg_rating']) ? '' : 'opacity-50'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="total-text mb-0">⭐ Điểm đánh giá trung bình</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="admin-reviews-card h-100 d-flex flex-column justify-content-center">
                                    <div class="reviews-count"><?php echo $stats['total_reviews']; ?></div>
                                    <div class="reviews-label">💬 Tổng số đánh giá</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="revenue-card h-100 d-flex flex-column justify-content-center text-center">
                                    <div class="reviews-count" style="font-size: 2.5rem;"><?php echo $stats['doctors']; ?></div>
                                    <div class="reviews-label">👨‍⚕️ Bác sĩ được đánh giá</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Danh sách đánh giá -->
                        <div class="table-container">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Bệnh nhân</th>
                                            <th>Bác sĩ</th>
                                            <th>Chuyên khoa</th>
                                            <th>Loại tư vấn</th>
                                            <th>Điểm</th>
                                            <th>Nội dung</th>
                                            <th>Ngày đánh giá</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($reviews)): ?>
                                            <tr><td colspan="7" class="text-center text-muted">Chưa có đánh giá nào</td></tr>
                                        <?php else: ?>
                                            <?php foreach($reviews as $review): ?>
                                            <tr class="review-item-card" style="background: white;">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="reviewer-avatar me-2">
                                                            <?php echo strtoupper(substr($review['patient_name'] ?? 'B', 0, 1)); ?>
                                                        </div>
                                                        <strong><?php echo htmlspecialchars($review['patient_name'] ?? ''); ?></strong>
                                                    </div>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($review['doctor_name'] ?? ''); ?></strong></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($review['specialty_name'] ?? ''); ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $loai = $review['loai_tu_van'] ?? 'truc_tiep';
                                                    $icon = $loai === 'truc_tuyen' ? 'video' : 'hospital';
                                                    $text = $loai === 'truc_tuyen' ? 'Trực tuyến' : 'Trực tiếp';
                                                    $color = $loai === 'truc_tuyen' ? 'success' : 'primary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $color; ?>">
                                                        <i class="fas fa-<?php echo $icon; ?> me-1"></i><?php echo $text; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>" style="font-size: 0.9rem;"></i>
                                                        <?php endfor; ?>
                                                        <span class="ms-2 fw-bold"><?php echo $review['rating']; ?>/5</span>
                                                    </div>
                                                </td>
                                                <td style="max-width: 250px;">
                                                    <div class="text-truncate" title="<?php echo htmlspecialchars($review['comment'] ?? ''); ?>">
                                                        <?php echo htmlspecialchars($review['comment'] ?? 'Không có nội dung'); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Section -->
                    <div id="revenue-section" class="content-section" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-chart-line me-2"></i>Thống kê doanh thu</h2>
                        </div>
                        
                        <!-- Thống kê doanh thu - NEW DESIGN -->
                        <div class="row mb-4 g-3">
                            <div class="col-md-3">
                                <div class="revenue-stat-card green">
                                    <div class="revenue-stat-amount"><?php echo number_format($stats['total_revenue']); ?></div>
                                    <div class="revenue-stat-currency">VNĐ</div>
                                    <div class="revenue-stat-label">Tổng doanh thu</div>
                                    <div class="revenue-stat-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="revenue-stat-card blue">
                                    <div class="revenue-stat-amount"><?php echo number_format($stats['revenue_month']); ?></div>
                                    <div class="revenue-stat-currency">VNĐ</div>
                                    <div class="revenue-stat-label">Doanh thu tháng này</div>
                                    <div class="revenue-stat-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="revenue-stat-card purple">
                                    <div class="revenue-stat-amount"><?php echo number_format($stats['revenue_week']); ?></div>
                                    <div class="revenue-stat-currency">VNĐ</div>
                                    <div class="revenue-stat-label">Doanh thu tuần này</div>
                                    <div class="revenue-stat-icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="revenue-stat-card orange">
                                    <div class="revenue-stat-amount"><?php echo number_format($stats['revenue_today']); ?></div>
                                    <div class="revenue-stat-currency">VNĐ</div>
                                    <div class="revenue-stat-label">Doanh thu hôm nay</div>
                                    <div class="revenue-stat-icon">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thống kê thanh toán - NEW DESIGN -->
                        <div class="row mb-4 g-3">
                            <div class="col-md-6">
                                <div class="payment-status-card">
                                    <div class="payment-status-header">
                                        <i class="fas fa-credit-card"></i>
                                        <span>Trạng thái thanh toán</span>
                                    </div>
                                    <div class="payment-status-body">
                                        <?php
                                        $paid = 0; $unpaid = 0;
                                        try {
                                            $stmt = $pdo->query("SELECT trang_thai_thanh_toan, COUNT(*) as total FROM lich_hen GROUP BY trang_thai_thanh_toan");
                                            while ($row = $stmt->fetch()) {
                                                if ($row['trang_thai_thanh_toan'] === 'da_thanh_toan') $paid = $row['total'];
                                                else $unpaid += $row['total'];
                                            }
                                        } catch (Exception $e) {}
                                        ?>
                                        <div class="payment-stat paid">
                                            <div class="payment-stat-number"><?php echo $paid; ?></div>
                                            <div class="payment-stat-label">Đã thanh toán</div>
                                        </div>
                                        <div class="payment-stat unpaid">
                                            <div class="payment-stat-number"><?php echo $unpaid; ?></div>
                                            <div class="payment-stat-label">Chưa thanh toán</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="consultation-type-card">
                                    <div class="consultation-type-header">
                                        <i class="fas fa-stethoscope"></i>
                                        <span>Loại tư vấn</span>
                                    </div>
                                    <div class="consultation-type-body">
                                        <?php
                                        $online = 0; $offline = 0;
                                        try {
                                            $stmt = $pdo->query("SELECT loai_tu_van, COUNT(*) as total, SUM(tong_phi) as revenue FROM lich_hen WHERE trang_thai_thanh_toan = 'da_thanh_toan' GROUP BY loai_tu_van");
                                            while ($row = $stmt->fetch()) {
                                                if ($row['loai_tu_van'] === 'truc_tuyen') {
                                                    $online = ['count' => $row['total'], 'revenue' => $row['revenue']];
                                                } else {
                                                    $offline = ['count' => $row['total'], 'revenue' => $row['revenue']];
                                                }
                                            }
                                        } catch (Exception $e) {}
                                        ?>
                                        <div class="consultation-stat online">
                                            <div class="consultation-stat-number"><?php echo is_array($online) ? $online['count'] : 0; ?></div>
                                            <div class="consultation-stat-label">
                                                <i class="fas fa-video"></i> Trực tuyến
                                            </div>
                                            <div class="consultation-stat-revenue"><?php echo number_format(is_array($online) ? $online['revenue'] : 0); ?> VNĐ</div>
                                        </div>
                                        <div class="consultation-stat offline">
                                            <div class="consultation-stat-number"><?php echo is_array($offline) ? $offline['count'] : 0; ?></div>
                                            <div class="consultation-stat-label">
                                                <i class="fas fa-hospital"></i> Trực tiếp
                                            </div>
                                            <div class="consultation-stat-revenue"><?php echo number_format(is_array($offline) ? $offline['revenue'] : 0); ?> VNĐ</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Danh sách thanh toán gần đây - NEW DESIGN -->
                        <div class="recent-payments-card">
                            <div class="recent-payments-header">
                                <i class="fas fa-history"></i>
                                <span>Thanh toán gần đây</span>
                            </div>
                            <div class="recent-payments-body">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Mã</th>
                                                <th>Bệnh nhân</th>
                                                <th>Bác sĩ</th>
                                                <th>Loại</th>
                                                <th>Số tiền</th>
                                                <th>Trạng thái</th>
                                                <th>Ngày</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            try {
                                                $stmt = $pdo->query("SELECT lh.*, b.ho_ten as doctor_name 
                                                                     FROM lich_hen lh 
                                                                     JOIN bac_si b ON lh.bac_si_id = b.id 
                                                                     WHERE lh.trang_thai_thanh_toan = 'da_thanh_toan'
                                                                     ORDER BY lh.ngay_cap_nhat DESC LIMIT 10");
                                                $recent_payments = $stmt->fetchAll();
                                                foreach ($recent_payments as $payment):
                                            ?>
                                            <tr>
                                                <td><strong class="text-primary">#<?php echo $payment['id']; ?></strong></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="reviewer-avatar me-2" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                                            <?php echo strtoupper(substr($payment['ten_benh_nhan'] ?? 'B', 0, 1)); ?>
                                                        </div>
                                                        <span><?php echo htmlspecialchars($payment['ten_benh_nhan'] ?? ''); ?></span>
                                                    </div>
                                                </td>
                                                <td><strong>BS. <?php echo htmlspecialchars($payment['doctor_name'] ?? ''); ?></strong></td>
                                                <td>
                                                    <?php if ($payment['loai_tu_van'] === 'truc_tuyen'): ?>
                                                        <span class="consultation-badge online"><i class="fas fa-video"></i> Trực tuyến</span>
                                                    <?php else: ?>
                                                        <span class="consultation-badge offline"><i class="fas fa-hospital"></i> Trực tiếp</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="amount-display large"><?php echo number_format($payment['tong_phi']); ?> VNĐ</span></td>
                                                <td><span class="payment-badge paid"><i class="fas fa-check-circle"></i> Đã thanh toán</span></td>
                                                <td><?php echo date('d/m/Y', strtotime($payment['ngay_hen'])); ?></td>
                                            </tr>
                                            <?php 
                                                endforeach;
                                            } catch (Exception $e) {
                                                echo '<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Không có dữ liệu</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-money-bill-wave me-2"></i>Thanh toán lịch hẹn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="payment-info mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="card-title text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Thông tin lịch hẹn
                                </h6>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Mã lịch hẹn:</div>
                                    <div class="col-7 fw-bold" id="payment-appointment-id">#</div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Bệnh nhân:</div>
                                    <div class="col-7" id="payment-patient-name"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Bác sĩ:</div>
                                    <div class="col-7" id="payment-doctor-name"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Chuyên khoa:</div>
                                    <div class="col-7" id="payment-specialty"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Ngày khám:</div>
                                    <div class="col-7" id="payment-date"></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-5 text-muted">Giờ khám:</div>
                                    <div class="col-7" id="payment-time"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-amount mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center py-4">
                                <h6 class="mb-2">Số tiền thanh toán</h6>
                                <h2 class="mb-0 fw-bold" id="payment-amount">0 ₫</h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-method mb-3">
                        <label class="form-label fw-bold">
                            <i class="fas fa-credit-card me-2"></i>Phương thức thanh toán
                        </label>
                        <select class="form-select" id="payment-method">
                            <option value="tien_mat">💵 Tiền mặt</option>
                            <option value="chuyen_khoan">🏦 Chuyển khoản</option>
                            <option value="the">💳 Thẻ tín dụng/ghi nợ</option>
                            <option value="vi_dien_tu">📱 Ví điện tử (MoMo, ZaloPay...)</option>
                        </select>
                    </div>
                    
                    <input type="hidden" id="payment-appointment-id-hidden">
                    <input type="hidden" id="payment-fee-hidden">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Hủy
                    </button>
                    <button type="button" class="btn btn-success" id="confirmPaymentBtn">
                        <i class="fas fa-check-circle me-1"></i>Xác nhận thanh toán
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Doctor Modal -->
    <div class="modal fade" id="addDoctorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm bác sĩ mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addDoctorForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Họ tên *</label>
                                    <input type="text" class="form-control" name="full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tên đăng nhập *</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="text" class="form-control" name="phone">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Chuyên khoa *</label>
                                    <select class="form-select" name="specialty_id" required>
                                        <option value="">Chọn chuyên khoa</option>
                                        <?php foreach ($specialties as $specialty): ?>
                                            <option value="<?php echo $specialty['id']; ?>"><?php echo htmlspecialchars($specialty['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kinh nghiệm (năm)</label>
                                    <input type="number" class="form-control" name="experience_years" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phí khám (VNĐ)</label>
                                    <input type="number" class="form-control" name="consultation_fee" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Học vấn</label>
                            <textarea class="form-control" name="education" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Giới thiệu</label>
                            <textarea class="form-control" name="bio" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Bệnh viện công tác</label>
                                    <input type="text" class="form-control" name="hospital_affiliation">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Sở thích chuyên môn</label>
                                    <input type="text" class="form-control" name="special_interests">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-certificate me-2"></i>Hình ảnh chứng chỉ hành nghề
                            </label>
                            <div class="file-upload-wrapper">
                                <input type="file" class="form-control" id="license_file_admin" name="license_file" accept="image/*,.pdf">
                                <div class="file-preview-admin mt-2" id="filePreviewAdmin" style="display: none;">
                                    <img id="previewImageAdmin" src="" alt="Preview" style="max-width: 100%; max-height: 150px; border-radius: 8px; border: 2px solid #e5e7eb;">
                                    <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeFileAdmin()">
                                        <i class="fas fa-times"></i> Xóa
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Chấp nhận: JPG, PNG, PDF (Tối đa 5MB)
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Thêm bác sĩ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Specialty Modal -->
    <div class="modal fade" id="addSpecialtyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm chuyên khoa mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addSpecialtyForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên chuyên khoa *</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mô tả</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Icon (Font Awesome)</label>
                                    <input type="text" class="form-control" name="icon" placeholder="fas fa-heart">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Màu sắc</label>
                                    <input type="color" class="form-control" name="color" value="#007bff">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Thứ tự hiển thị</label>
                            <input type="number" class="form-control" name="sort_order" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Thêm chuyên khoa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // CRITICAL FIX: Remove any modal backdrop and restore page interactivity
            function fixOverlay() {
                // Remove all modal backdrops
                $('.modal-backdrop').remove();
                
                // Remove modal-open class from body and html
                $('body, html').removeClass('modal-open');
                
                // Reset body styles
                $('body').css({
                    'overflow': 'auto',
                    'overflow-x': 'hidden',
                    'padding-right': '0',
                    'pointer-events': 'auto',
                    'position': 'relative'
                });
                
                // Reset html styles
                $('html').css({
                    'overflow': 'auto',
                    'overflow-x': 'hidden',
                    'pointer-events': 'auto'
                });
                
                // Remove any inline styles that might block clicks
                $('body').removeAttr('style');
                
                // Ensure all interactive elements are clickable
                $('button, a, input, select, textarea, .btn, .nav-link, .clickable').css('pointer-events', 'auto');
                
                // Remove any stuck fixed position elements that might be overlays
                $('[style*="position: fixed"]').each(function() {
                    var $el = $(this);
                    var zIndex = parseInt($el.css('z-index')) || 0;
                    if (zIndex > 1000 && !$el.hasClass('modal') && !$el.closest('.modal').length) {
                        if ($el.hasClass('modal-backdrop') || $el.css('background-color').includes('rgba(0, 0, 0')) {
                            $el.remove();
                        }
                    }
                });
                
                console.log('✓ Overlay fix applied');
            }
            
            // Run fix immediately and multiple times
            fixOverlay();
            setTimeout(fixOverlay, 100);
            setTimeout(fixOverlay, 300);
            setTimeout(fixOverlay, 500);
            setTimeout(fixOverlay, 1000);
            setTimeout(fixOverlay, 2000);
            
            // Run fix when any modal is hidden
            $(document).on('hidden.bs.modal', function() {
                setTimeout(fixOverlay, 100);
                setTimeout(fixOverlay, 300);
            });
            
            // Run fix when clicking on body (in case overlay appears)
            $(document).on('click', 'body', function(e) {
                if ($(e.target).is('body') || $(e.target).hasClass('container-fluid')) {
                    fixOverlay();
                }
            });
            
            // Xóa bất kỳ modal backdrop nào còn sót lại và đảm bảo trang có thể tương tác
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({
                'overflow': '',
                'padding-right': '',
                'pointer-events': 'auto'
            });
            
            // Đảm bảo không có phần tử nào chặn click
            $('*').css('pointer-events', '');
            
            // Force remove any lingering overlays
            setTimeout(function() {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css({
                    'overflow': '',
                    'padding-right': ''
                });
            }, 100);
            
            // Navigation
            $('.nav-link').click(function(e) {
                // Nếu là nút đăng xuất thì không xử lý tab
                if ($(this).attr('id') === 'admin-logout-link') {
                    return; // cho phép chuyển trang
                }
                e.preventDefault();
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
                
                $('.content-section').hide();
                $('#' + $(this).data('section') + '-section').show();
            });
            
            // Add Doctor Form
            $('#addDoctorForm').submit(function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'add_doctor');
                
                $.ajax({
                    url: '', // Gửi về chính trang admin_khamcare.php
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#addDoctorModal').modal('hide');
                            // Xóa backdrop thủ công nếu còn
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('overflow', '');
                            location.reload(); // Tải lại trang để cập nhật danh sách
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Có lỗi xảy ra khi thêm bác sĩ: ' + error);
                        // Xóa backdrop nếu có lỗi
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('overflow', '');
                    }
                });
            });
            
            // Add Specialty Form
            $('#addSpecialtyForm').submit(function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: '', // Gửi về chính trang admin_khamcare.php
                    type: 'POST',
                    data: $(this).serialize() + '&action=add_specialty',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            $('#addSpecialtyModal').modal('hide');
                            // Xóa backdrop thủ công nếu còn
                            $('.modal-backdrop').remove();
                            $('body').removeClass('modal-open').css('overflow', '');
                            location.reload(); // Tải lại trang để cập nhật danh sách
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Có lỗi xảy ra khi thêm chuyên khoa: ' + error);
                        // Xóa backdrop nếu có lỗi
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open').css('overflow', '');
                    }
                });
            });
            
            // Approve Doctor (dùng event delegation)
            $(document).on('click', '.approve-doctor', function() {
                const btn = $(this);
                if (btn.data('busy')) return;
                if (!confirm('Bạn có chắc chắn muốn duyệt bác sĩ này?')) return;
                btn.data('busy', true).prop('disabled', true).addClass('disabled');
                const row = btn.closest('tr');
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: { action: 'approve_doctor', doctor_id: btn.data('id') },
                    dataType: 'json',
                    timeout: 15000,
                    success: function(response) {
                        if (response && response.success) {
                            toastr.success(response.message || 'Đã duyệt bác sĩ');
                            location.reload(); // Reload để cập nhật giao diện
                        } else {
                            toastr.error((response && response.message) ? response.message : 'Duyệt không thành công');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Có lỗi xảy ra khi duyệt bác sĩ';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) msg = r.message; } catch(e) {}
                        toastr.error(msg);
                    },
                    complete: function(){ btn.data('busy', false).prop('disabled', false).removeClass('disabled'); }
                });
            });
            
            // Deactivate Doctor (dùng event delegation)
            $(document).on('click', '.deactivate-doctor', function() {
                const btn = $(this);
                if (btn.data('busy')) return;
                if (!confirm('Bạn có chắc chắn muốn hủy kích hoạt bác sĩ này?')) return;
                btn.data('busy', true).prop('disabled', true).addClass('disabled');
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: { action: 'deactivate_doctor', doctor_id: btn.data('id') },
                    dataType: 'json',
                    timeout: 15000,
                    success: function(response) {
                        if (response && response.success) {
                            toastr.success(response.message || 'Đã hủy kích hoạt bác sĩ');
                            location.reload(); // Reload để cập nhật giao diện
                        } else {
                            toastr.error((response && response.message) ? response.message : 'Hủy kích hoạt không thành công');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Có lỗi xảy ra';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) msg = r.message; } catch(e) {}
                        toastr.error(msg);
                    },
                    complete: function(){ btn.data('busy', false).prop('disabled', false).removeClass('disabled'); }
                });
            });
            
            // Delete Doctor (dùng event delegation)
            $(document).on('click', '.delete-doctor', function() {
                const btn = $(this);
                if (btn.data('busy')) return;
                if (!confirm('Bạn có chắc chắn muốn xóa bác sĩ này?')) return;
                btn.data('busy', true).prop('disabled', true).addClass('disabled');
                const row = btn.closest('tr');
                    $.ajax({
                        url: '',
                        type: 'POST',
                    data: { action: 'delete_doctor', doctor_id: btn.data('id') },
                    dataType: 'json',
                    timeout: 15000,
                        success: function(response) {
                        if (response && response.success) {
                            toastr.success(response.message || 'Đã xóa bác sĩ');
                            row.fadeOut(200, function(){ $(this).remove(); });
                            } else {
                            toastr.error((response && response.message) ? response.message : 'Xóa không thành công');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Có lỗi xảy ra khi xóa bác sĩ';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) msg = r.message; } catch(e) {}
                        toastr.error(msg);
                    },
                    complete: function(){ btn.data('busy', false).prop('disabled', false).removeClass('disabled'); }
                });
            });
            
            // Delete Specialty
            $('.delete-specialty').click(function() {
                const btn = $(this);
                if (btn.data('busy')) return;
                if (!confirm('Bạn có chắc chắn muốn xóa chuyên khoa này?')) return;
                btn.data('busy', true).prop('disabled', true).addClass('disabled');
                const row = btn.closest('tr');
                    $.ajax({
                        url: '',
                        type: 'POST',
                    data: { action: 'delete_specialty', specialty_id: btn.data('id') },
                    dataType: 'json',
                    timeout: 15000,
                        success: function(response) {
                        if (response && response.success) {
                            toastr.success(response.message || 'Đã xóa chuyên khoa');
                            row.fadeOut(200, function(){ $(this).remove(); });
                            } else {
                            toastr.error((response && response.message) ? response.message : 'Xóa không thành công');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Có lỗi xảy ra khi xóa chuyên khoa';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) msg = r.message; } catch(e) {}
                        toastr.error(msg);
                    },
                    complete: function(){ btn.data('busy', false).prop('disabled', false).removeClass('disabled'); }
                });
            });
            
            // Update Appointment Status
            $('.status-select').change(function() {
                $.ajax({
                    url: '', // Gửi về chính trang admin_khamcare.php
                    type: 'POST',
                    data: { 
                        action: 'update_appointment_status', 
                        appointment_id: $(this).data('id'),
                        status: $(this).val()
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        toastr.error('Có lỗi xảy ra khi cập nhật trạng thái: ' + error);
                    }
                });
            });
            
            // Delete User
            $('.delete-user').click(function() {
                const btn = $(this);
                if (btn.data('busy')) return;
                if (!confirm('Bạn có chắc chắn muốn xóa người dùng này?')) return;
                btn.data('busy', true).prop('disabled', true).addClass('disabled');
                const row = btn.closest('tr');
                    $.ajax({
                        url: '',
                        type: 'POST',
                    data: { action: 'delete_user', user_id: btn.data('id') },
                    dataType: 'json',
                    timeout: 15000,
                        success: function(response) {
                        if (response && response.success) {
                            toastr.success(response.message || 'Đã xóa người dùng');
                            row.fadeOut(200, function(){ $(this).remove(); });
                            } else {
                            toastr.error((response && response.message) ? response.message : 'Xóa không thành công');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Có lỗi xảy ra khi xóa người dùng';
                        try { var r = JSON.parse(xhr.responseText); if (r && r.message) msg = r.message; } catch(e) {}
                        toastr.error(msg);
                    },
                    complete: function(){ btn.data('busy', false).prop('disabled', false).removeClass('disabled'); }
                });
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      // Charts data from PHP
      const apptStatus = <?php echo json_encode(array_values($statusCounts)); ?>; // [pending, confirmed, completed, cancelled]
      const serviceLabels = <?php echo json_encode(array_column($popular_services, 'name'), JSON_UNESCAPED_UNICODE); ?>;
      const serviceData = <?php echo json_encode(array_map('intval', array_column($popular_services, 'total'))); ?>;

      // Bar chart - appointment statuses
      const ctxBar = document.getElementById('chartAppointments').getContext('2d');
      if (ctxBar) {
        new Chart(ctxBar, {
          type: 'bar',
          data: {
            labels: ['Chờ xác nhận','Đã xác nhận','Hoàn thành','Đã hủy'],
            datasets: [{
              label: 'Số lượng',
              data: apptStatus,
              backgroundColor: ['#f59e0b','#3b82f6','#10b981','#ef4444']
            }]
          },
          options: { responsive: true, plugins: { legend: { display: false } } }
        });
      }

      // Doughnut chart - popular services
      const ctxPie = document.getElementById('chartServices').getContext('2d');
      if (ctxPie) {
        new Chart(ctxPie, {
          type: 'doughnut',
          data: {
            labels: serviceLabels,
            datasets: [{
              data: serviceData,
              backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6']
            }]
          },
          options: { responsive: true }
        });
      }

      // Appointment filtering functions
      function filterAppointments() {
        const searchTerm = document.getElementById('appointmentSearch')?.value.toLowerCase() || '';
        const statusFilter = document.getElementById('appointmentStatusFilter')?.value || '';
        const specialtyFilter = document.getElementById('appointmentSpecialtyFilter')?.value || '';
        const dateFrom = document.getElementById('appointmentDateFrom')?.value || '';
        const dateTo = document.getElementById('appointmentDateTo')?.value || '';
        
        const table = document.getElementById('appointmentsTable');
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
          const searchData = row.getAttribute('data-search') || '';
          const status = row.getAttribute('data-status') || '';
          const specialty = row.getAttribute('data-specialty') || '';
          const date = row.getAttribute('data-date') || '';
          
          let show = true;
          
          // Filter by search term
          if (searchTerm && !searchData.includes(searchTerm)) {
            show = false;
          }
          
          // Filter by status
          if (statusFilter && status !== statusFilter) {
            show = false;
          }
          
          // Filter by specialty
          if (specialtyFilter && specialty !== specialtyFilter) {
            show = false;
          }
          
          // Filter by date range
          if (dateFrom && date < dateFrom) {
            show = false;
          }
          if (dateTo && date > dateTo) {
            show = false;
          }
          
          row.style.display = show ? '' : 'none';
          if (show) visibleCount++;
        });
        
        console.log(`Filtered appointments: ${visibleCount} of ${rows.length} visible`);
      }
      
      function resetAppointmentFilters() {
        document.getElementById('appointmentSearch').value = '';
        document.getElementById('appointmentStatusFilter').value = '';
        document.getElementById('appointmentSpecialtyFilter').value = '';
        document.getElementById('appointmentDateFrom').value = '';
        document.getElementById('appointmentDateTo').value = '';
        filterAppointments();
      }
      
      // Add event listeners for appointment filters
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('appointmentSearch');
        const statusFilter = document.getElementById('appointmentStatusFilter');
        const specialtyFilter = document.getElementById('appointmentSpecialtyFilter');
        const dateFrom = document.getElementById('appointmentDateFrom');
        const dateTo = document.getElementById('appointmentDateTo');
        
        if (searchInput) searchInput.addEventListener('input', filterAppointments);
        if (statusFilter) statusFilter.addEventListener('change', filterAppointments);
        if (specialtyFilter) specialtyFilter.addEventListener('change', filterAppointments);
        if (dateFrom) dateFrom.addEventListener('change', filterAppointments);
        if (dateTo) dateTo.addEventListener('change', filterAppointments);
      });
    </script>
    
    <script>
        // File upload preview for admin add doctor form
        const licenseFileAdmin = document.getElementById('license_file_admin');
        const filePreviewAdmin = document.getElementById('filePreviewAdmin');
        const previewImageAdmin = document.getElementById('previewImageAdmin');
        
        if (licenseFileAdmin) {
            licenseFileAdmin.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        toastr.error('File quá lớn! Vui lòng chọn file nhỏ hơn 5MB.');
                        licenseFileAdmin.value = '';
                        return;
                    }
                    
                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                    if (!validTypes.includes(file.type)) {
                        toastr.error('Định dạng file không hợp lệ! Vui lòng chọn file JPG, PNG hoặc PDF.');
                        licenseFileAdmin.value = '';
                        return;
                    }
                    
                    // Show preview for images
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewImageAdmin.src = e.target.result;
                            previewImageAdmin.style.display = 'inline-block';
                            filePreviewAdmin.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    } else if (file.type === 'application/pdf') {
                        // Show PDF icon
                        previewImageAdmin.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"%3E%3Cpath fill="%23dc2626" d="M181.9 256.1c-5-16-4.9-46.9-2-46.9 8.4 0 7.6 36.9 2 46.9zm-1.7 47.2c-7.7 20.2-17.3 43.3-28.4 62.7 18.3-7 39-17.2 62.9-21.9-12.7-9.6-24.9-23.4-34.5-40.8zM86.1 428.1c0 .8 13.2-5.4 34.9-40.2-6.7 6.3-29.1 24.5-34.9 40.2zM248 160h136v328c0 13.3-10.7 24-24 24H24c-13.3 0-24-10.7-24-24V24C0 10.7 10.7 0 24 0h200v136c0 13.2 10.8 24 24 24z"/%3E%3C/svg%3E';
                        previewImageAdmin.style.display = 'inline-block';
                        previewImageAdmin.style.maxHeight = '60px';
                        filePreviewAdmin.style.display = 'block';
                    }
                }
            });
        }
        
        function removeFileAdmin() {
            if (licenseFileAdmin) licenseFileAdmin.value = '';
            if (previewImageAdmin) {
                previewImageAdmin.src = '';
                previewImageAdmin.style.display = 'none';
            }
            if (filePreviewAdmin) filePreviewAdmin.style.display = 'none';
        }
    </script>
</body>
</html>

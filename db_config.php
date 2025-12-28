<?php
/**
 * db_config.php - Cấu hình database tiếng Việt
 * Tương thích với khamcare_import.sql phiên bản tiếng Việt
 */

// Cấu hình database
if (!defined('DB_HOST'))    define('DB_HOST', 'localhost');
if (!defined('DB_PORT'))    define('DB_PORT', '3306');
if (!defined('DB_NAME'))    define('DB_NAME', 'khamcare_database');
if (!defined('DB_USER'))    define('DB_USER', 'root');
if (!defined('DB_PASS'))    define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

$GLOBALS['_khamcare_pdo'] = null;

/**
 * Phát hiện trình duyệt và thiết bị từ User-Agent
 */
function detectBrowserAndDevice($userAgent) {
    $browser = 'Unknown';
    $device = 'PC';
    
    // Phát hiện thiết bị di động
    if (preg_match('/iPhone/i', $userAgent)) {
        $device = 'iPhone';
    } elseif (preg_match('/iPad/i', $userAgent)) {
        $device = 'iPad';
    } elseif (preg_match('/Samsung|SM-[A-Z]/i', $userAgent)) {
        $device = 'Samsung';
    } elseif (preg_match('/Xiaomi|Redmi|POCO/i', $userAgent)) {
        $device = 'Xiaomi';
    } elseif (preg_match('/OPPO/i', $userAgent)) {
        $device = 'OPPO';
    } elseif (preg_match('/vivo/i', $userAgent)) {
        $device = 'Vivo';
    } elseif (preg_match('/Huawei|HUAWEI/i', $userAgent)) {
        $device = 'Huawei';
    } elseif (preg_match('/Realme/i', $userAgent)) {
        $device = 'Realme';
    } elseif (preg_match('/Android/i', $userAgent)) {
        $device = 'Android';
    } elseif (preg_match('/Mobile/i', $userAgent)) {
        $device = 'Mobile';
    } elseif (preg_match('/Macintosh/i', $userAgent)) {
        $device = 'Mac';
    } elseif (preg_match('/Windows/i', $userAgent)) {
        $device = 'Windows PC';
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $device = 'Linux PC';
    }
    
    // Phát hiện trình duyệt
    if (preg_match('/Edg/i', $userAgent)) {
        $browser = 'Edge';
    } elseif (preg_match('/OPR|Opera/i', $userAgent)) {
        $browser = 'Opera';
    } elseif (preg_match('/Chrome/i', $userAgent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Firefox/i', $userAgent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Safari/i', $userAgent)) {
        $browser = 'Safari';
    } elseif (preg_match('/MSIE|Trident/i', $userAgent)) {
        $browser = 'IE';
    }
    
    return $browser . ' / ' . $device;
}

/**
 * Kết nối database
 */
function getDBConnection() {
    if ($GLOBALS['_khamcare_pdo'] instanceof PDO) {
        return $GLOBALS['_khamcare_pdo'];
    }

    try {
        // Thử kết nối không có database trước để tạo nếu cần
        $dsnNoDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdoTemp = new PDO($dsnNoDb, DB_USER, DB_PASS, $options);
        
        // Kiểm tra database có tồn tại không
        $stmt = $pdoTemp->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
        if ($stmt->rowCount() == 0) {
            // Tạo database nếu chưa có
            $pdoTemp->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        
        // Kết nối với database
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $GLOBALS['_khamcare_pdo'] = $pdo;
        
        // Kiểm tra bảng nguoi_dung có tồn tại không
        $stmt = $pdo->query("SHOW TABLES LIKE 'nguoi_dung'");
        if ($stmt->rowCount() == 0) {
            // Bảng chưa có, cần import khamcare_import.sql
            error_log('[DB] Bảng nguoi_dung chưa tồn tại. Vui lòng import file khamcare_import.sql vào phpMyAdmin.');
        }
        
        return $pdo;
    } catch (PDOException $e) {
        error_log('[DB Error] ' . $e->getMessage());
        return false;
    }
}

/**
 * Đăng ký người dùng mới
 */
function registerUser($username, $email, $password, $full_name, $phone = null, $role = 'benh_nhan') {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Lỗi kết nối database.'];
    }

    try {
        // Kiểm tra tồn tại
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM nguoi_dung WHERE ten_dang_nhap = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Tên đăng nhập hoặc Email đã tồn tại.'];
        }

        // Chuyển đổi role sang tiếng Việt
        $vai_tro = 'benh_nhan';
        if ($role === 'doctor') $vai_tro = 'bac_si';
        if ($role === 'admin') $vai_tro = 'quan_tri';

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO nguoi_dung (ten_dang_nhap, email, mat_khau, ho_ten, so_dien_thoai, vai_tro) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $vai_tro]);
        $newUserId = (int)$pdo->lastInsertId();

        // Nếu là bác sĩ, tạo hồ sơ bác sĩ
        if ($vai_tro === 'bac_si') {
            $specId = 6; // Nội Tổng Quát mặc định
            $pdo->prepare("INSERT INTO bac_si (nguoi_dung_id, chuyen_khoa_id, nam_kinh_nghiem, phi_kham, trang_thai) VALUES (?, ?, 0, 300000, 'hoat_dong')")
                ->execute([$newUserId, $specId]);
        }

        return ['success' => true, 'message' => 'Đăng ký thành công!', 'user_id' => $newUserId];

    } catch (PDOException $e) {
        error_log("Register failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi đăng ký: ' . $e->getMessage()];
    }
}

/**
 * Đăng nhập người dùng
 */
function loginUser($username, $password) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Lỗi kết nối database.'];
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE ten_dang_nhap = ? AND trang_thai = 'hoat_dong'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mat_khau'])) {
            // Lấy thông tin trình duyệt và thiết bị
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $browserInfo = detectBrowserAndDevice($userAgent);
            
            // Ghi log đăng nhập thành công
            try {
                $logStmt = $pdo->prepare("INSERT INTO nhat_ky_dang_nhap (nguoi_dung_id, ten_dang_nhap, trang_thai, phuong_thuc, dia_chi_ip, trinh_duyet, ly_do_that_bai) VALUES (?, ?, 'thanh_cong', 'mat_khau', ?, ?, '')");
                $logStmt->execute([$user['id'], $user['ten_dang_nhap'], $_SERVER['REMOTE_ADDR'] ?? 'unknown', $browserInfo]);
            } catch (PDOException $e) {}

            // Chuyển đổi sang format cũ để tương thích
            $userData = [
                'id' => $user['id'],
                'username' => $user['ten_dang_nhap'],
                'email' => $user['email'],
                'full_name' => $user['ho_ten'],
                'phone' => $user['so_dien_thoai'],
                'role' => $user['vai_tro'] === 'quan_tri' ? 'admin' : ($user['vai_tro'] === 'bac_si' ? 'doctor' : 'patient'),
                'status' => $user['trang_thai'] === 'hoat_dong' ? 'active' : 'inactive'
            ];
            
            return ['success' => true, 'message' => 'Đăng nhập thành công!', 'user' => $userData];
        } else {
            // Lấy thông tin trình duyệt và thiết bị cho log thất bại
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $browserInfo = detectBrowserAndDevice($userAgent);
            
            // Ghi log đăng nhập thất bại
            $lyDoThatBai = $user ? 'Sai mật khẩu' : 'Tài khoản không tồn tại';
            try {
                $logStmt = $pdo->prepare("INSERT INTO nhat_ky_dang_nhap (nguoi_dung_id, ten_dang_nhap, trang_thai, phuong_thuc, dia_chi_ip, trinh_duyet, ly_do_that_bai) VALUES (?, ?, 'that_bai', 'mat_khau', ?, ?, ?)");
                $logStmt->execute([$user ? $user['id'] : null, $username, $_SERVER['REMOTE_ADDR'] ?? 'unknown', $browserInfo, $lyDoThatBai]);
            } catch (PDOException $e) {}
            
            return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'];
        }

    } catch (PDOException $e) {
        error_log("Login failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi đăng nhập: ' . $e->getMessage()];
    }
}

/**
 * Lấy thông tin user theo ID
 */
function getUserById($user_id) {
    $pdo = getDBConnection();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
        $stmt->execute([(int)$user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            return [
                'id' => $user['id'],
                'username' => $user['ten_dang_nhap'],
                'email' => $user['email'],
                'full_name' => $user['ho_ten'],
                'phone' => $user['so_dien_thoai'],
                'role' => $user['vai_tro'] === 'quan_tri' ? 'admin' : ($user['vai_tro'] === 'bac_si' ? 'doctor' : 'patient'),
                'status' => $user['trang_thai'] === 'hoat_dong' ? 'active' : 'inactive',
                'date_of_birth' => $user['ngay_sinh'],
                'gender' => $user['gioi_tinh'],
                'address' => $user['dia_chi']
            ];
        }
        return false;
    } catch (PDOException $e) {
        error_log('[getUserById] ' . $e->getMessage());
        return false;
    }
}

/**
 * Lấy danh sách chuyên khoa
 */
function getSpecialties() {
    $pdo = getDBConnection();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare("SELECT id, ten as name, mo_ta as description, icon, mau_sac as color FROM chuyen_khoa WHERE trang_thai = 'hoat_dong' ORDER BY thu_tu");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Get specialties failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Lấy danh sách bác sĩ
 */
function getDoctors($specialty_id = null, $keyword = '') {
    $pdo = getDBConnection();
    if (!$pdo) return [];

    try {
        $sql = "SELECT b.*, b.ho_ten as full_name, n.so_dien_thoai as phone, 
                       c.ten as specialty_name, c.icon, c.mau_sac as color,
                       b.phi_kham as consultation_fee, b.nam_kinh_nghiem as experience_years,
                       b.danh_gia as rating, b.tong_danh_gia as total_reviews
                FROM bac_si b 
                JOIN nguoi_dung n ON b.nguoi_dung_id = n.id 
                JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id 
                WHERE b.trang_thai = 'hoat_dong'";
        
        $params = [];
        
        if ($specialty_id) {
            $sql .= " AND b.chuyen_khoa_id = ?";
            $params[] = $specialty_id;
        }
        
        if ($keyword) {
            $sql .= " AND (b.ho_ten LIKE ? OR c.ten LIKE ?)";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        
        $sql .= " ORDER BY b.danh_gia DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Get doctors failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Đặt lịch khám
 */
function bookAppointment($patient_id, $doctor_id, $appointment_date, $appointment_time, $notes = '', $consultation_type = 'truc_tiep') {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['success' => false, 'message' => 'Lỗi kết nối database.'];
    }

    try {
        // Lấy thông tin bác sĩ
        $stmt = $pdo->prepare("SELECT phi_kham FROM bac_si WHERE id = ? AND trang_thai = 'hoat_dong'");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();
        
        if (!$doctor) {
            return ['success' => false, 'message' => 'Bác sĩ không tồn tại.'];
        }

        // Lấy tên bệnh nhân
        $stmt = $pdo->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = ?");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch();
        $patient_name = $patient ? $patient['ho_ten'] : 'Bệnh nhân';
        
        // Kiểm tra lịch trùng
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lich_hen WHERE bac_si_id = ? AND ngay_hen = ? AND gio_hen = ? AND trang_thai IN ('cho_xac_nhan', 'da_xac_nhan')");
        $stmt->execute([$doctor_id, $appointment_date, $appointment_time]);
        
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'Lịch khám này đã được đặt.'];
        }

        // Chuyển đổi loại tư vấn
        $loai_tu_van = $consultation_type === 'online' ? 'truc_tuyen' : 'truc_tiep';

        // Tạo lịch khám
        $stmt = $pdo->prepare("INSERT INTO lich_hen (benh_nhan_id, ten_benh_nhan, bac_si_id, ngay_hen, gio_hen, ghi_chu, loai_tu_van, tong_phi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patient_id, $patient_name, $doctor_id, $appointment_date, $appointment_time, $notes, $loai_tu_van, $doctor['phi_kham']]);

        return ['success' => true, 'message' => 'Đặt lịch khám thành công!', 'appointment_id' => $pdo->lastInsertId()];

    } catch (PDOException $e) {
        error_log("Book appointment failed: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi đặt lịch: ' . $e->getMessage()];
    }
}

/**
 * Lấy lịch khám của bác sĩ
 */
function getDoctorSchedule($doctor_id, $date = null) {
    $pdo = getDBConnection();
    if (!$pdo) return [];

    try {
        $sql = "SELECT gio_hen as appointment_time, trang_thai as status FROM lich_hen WHERE bac_si_id = ?";
        $params = [$doctor_id];
        
        if ($date) {
            $sql .= " AND ngay_hen = ?";
            $params[] = $date;
        }
        
        $sql .= " ORDER BY gio_hen";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Get doctor schedule failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Thực thi query
 */
function executeQuery($sql, $params = []) {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('[executeQuery] ' . $e->getMessage());
        return [];
    }
}


/**
 * Tạo các bảng cơ bản nếu chưa tồn tại
 */
function createBasicTables($pdo) {
    // Tạo bảng nguoi_dung
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `nguoi_dung` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ten_dang_nhap` varchar(50) NOT NULL,
            `email` varchar(100) NOT NULL,
            `mat_khau` varchar(255) NOT NULL,
            `ho_ten` varchar(100) NOT NULL,
            `so_dien_thoai` varchar(15) DEFAULT NULL,
            `vai_tro` enum('benh_nhan','bac_si','quan_tri') DEFAULT 'benh_nhan',
            `trang_thai` enum('hoat_dong','khong_hoat_dong','cho_duyet') DEFAULT 'hoat_dong',
            `anh_dai_dien` varchar(255) DEFAULT NULL,
            `ngay_sinh` date DEFAULT NULL,
            `gioi_tinh` enum('Nam','Nữ','Khác') DEFAULT NULL,
            `dia_chi` text DEFAULT NULL,
            `giay_phep_hanh_nghe` varchar(255) DEFAULT NULL,
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Tạo bảng chuyen_khoa
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `chuyen_khoa` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ten` varchar(100) NOT NULL,
            `mo_ta` text DEFAULT NULL,
            `icon` varchar(50) DEFAULT 'fas fa-stethoscope',
            `mau_sac` varchar(20) DEFAULT '#2ecc71',
            `thu_tu` int(11) DEFAULT 0,
            `trang_thai` enum('hoat_dong','khong_hoat_dong') DEFAULT 'hoat_dong',
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Tạo bảng bac_si
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `bac_si` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nguoi_dung_id` int(11) NOT NULL,
            `ho_ten` varchar(100) DEFAULT NULL,
            `chuyen_khoa_id` int(11) NOT NULL,
            `phi_kham` decimal(10,2) DEFAULT 300000.00,
            `nam_kinh_nghiem` int(11) DEFAULT 0,
            `danh_gia` decimal(3,2) DEFAULT 0.00,
            `tong_danh_gia` int(11) DEFAULT 0,
            `hoc_van` text DEFAULT NULL,
            `gioi_thieu` text DEFAULT NULL,
            `benh_vien` varchar(255) DEFAULT NULL,
            `hinh_anh` varchar(255) DEFAULT NULL,
            `trang_thai` enum('hoat_dong','khong_hoat_dong','ban') DEFAULT 'hoat_dong',
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Tạo bảng lich_hen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `lich_hen` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `benh_nhan_id` int(11) NOT NULL,
            `ten_benh_nhan` varchar(100) DEFAULT NULL,
            `bac_si_id` int(11) NOT NULL,
            `ngay_hen` date NOT NULL,
            `gio_hen` time NOT NULL,
            `thoi_luong` int(11) DEFAULT 30,
            `trang_thai` enum('cho_xac_nhan','da_xac_nhan','hoan_thanh','da_huy','vang_mat') DEFAULT 'cho_xac_nhan',
            `loai_tu_van` enum('truc_tuyen','truc_tiep') DEFAULT 'truc_tiep',
            `ghi_chu` text DEFAULT NULL,
            `tong_phi` decimal(10,2) DEFAULT 0.00,
            `trang_thai_thanh_toan` enum('chua_thanh_toan','da_thanh_toan','hoan_tien') DEFAULT 'chua_thanh_toan',
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ngay_cap_nhat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Tạo bảng nhat_ky_dang_nhap
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `nhat_ky_dang_nhap` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nguoi_dung_id` int(11) DEFAULT NULL,
            `ten_dang_nhap` varchar(50) DEFAULT NULL,
            `trang_thai` enum('thanh_cong','that_bai') DEFAULT 'thanh_cong',
            `dia_chi_ip` varchar(45) DEFAULT NULL,
            `thoi_gian` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Tạo bảng tu_van (consultations)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `tu_van` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `benh_nhan_id` int(11) NOT NULL,
            `bac_si_id` int(11) DEFAULT NULL,
            `lich_hen_id` int(11) DEFAULT NULL,
            `loai_tu_van` enum('chat','video','voice') DEFAULT 'chat',
            `thoi_gian_bat_dau` timestamp NULL DEFAULT NULL,
            `thoi_gian_ket_thuc` timestamp NULL DEFAULT NULL,
            `thoi_luong_phut` int(11) DEFAULT 0,
            `trang_thai` enum('cho','dang_hoat_dong','hoan_thanh','da_huy') DEFAULT 'dang_hoat_dong',
            `tom_tat` text DEFAULT NULL,
            `ghi_chu_bac_si` text DEFAULT NULL,
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ngay_cap_nhat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Tạo bảng tin_nhan (messages)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `tin_nhan` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `tu_van_id` int(11) NOT NULL,
            `nguoi_gui_id` int(11) NOT NULL,
            `noi_dung` text NOT NULL,
            `loai_tin_nhan` enum('van_ban','hinh_anh','file') DEFAULT 'van_ban',
            `da_doc` tinyint(1) DEFAULT 0,
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Tạo bảng ho_so_nguoi_dung (user_profiles)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `ho_so_nguoi_dung` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nguoi_dung_id` int(11) NOT NULL,
            `ho_va_ten` varchar(100) DEFAULT NULL,
            `so_dien_thoai` varchar(15) DEFAULT NULL,
            `ngay_sinh` date DEFAULT NULL,
            `gioi_tinh` enum('Nam','Nữ','Khác') DEFAULT NULL,
            `dia_chi` text DEFAULT NULL,
            `lien_he_khan_cap` varchar(255) DEFAULT NULL,
            `tien_su_benh` text DEFAULT NULL,
            `nhom_mau` varchar(10) DEFAULT NULL,
            `di_ung` text DEFAULT NULL,
            `ghi_chu` text DEFAULT NULL,
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ngay_cap_nhat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `nguoi_dung_id` (`nguoi_dung_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Xóa các VIEW tiếng Anh nếu tồn tại (không cần nữa vì đã chuyển sang tiếng Việt)
    $pdo->exec("DROP VIEW IF EXISTS `users`");
    $pdo->exec("DROP VIEW IF EXISTS `specialties`");
    $pdo->exec("DROP VIEW IF EXISTS `doctors`");
    $pdo->exec("DROP VIEW IF EXISTS `appointments`");
    $pdo->exec("DROP VIEW IF EXISTS `consultations`");
    $pdo->exec("DROP VIEW IF EXISTS `messages`");
    $pdo->exec("DROP VIEW IF EXISTS `reviews`");
    $pdo->exec("DROP VIEW IF EXISTS `user_profiles`");

    // Thêm dữ liệu mẫu nếu bảng trống
    $stmt = $pdo->query("SELECT COUNT(*) FROM chuyen_khoa");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO `chuyen_khoa` (`ten`, `mo_ta`, `icon`, `mau_sac`, `thu_tu`) VALUES
            ('Tim Mạch', 'Chuyên khoa tim mạch và huyết áp', 'fas fa-heartbeat', '#e74c3c', 1),
            ('Thần Kinh', 'Chuyên khoa thần kinh và não bộ', 'fas fa-brain', '#9b59b6', 2),
            ('Sản Phụ Khoa', 'Chuyên khoa sản phụ khoa', 'fas fa-venus', '#e91e63', 3),
            ('Da Liễu', 'Chuyên khoa da liễu và thẩm mỹ', 'fas fa-hand-holding-heart', '#f39c12', 4),
            ('Nhi Khoa', 'Chuyên khoa nhi', 'fas fa-child', '#3498db', 5),
            ('Nội Tổng Quát', 'Chuyên khoa nội tổng quát', 'fas fa-stethoscope', '#2ecc71', 6)
        ");
    }

    // Thêm admin mặc định nếu chưa có
    $stmt = $pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'quan_tri'");
    if ($stmt->fetchColumn() == 0) {
        $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("
            INSERT INTO `nguoi_dung` (`ten_dang_nhap`, `email`, `mat_khau`, `ho_ten`, `vai_tro`, `trang_thai`) VALUES
            ('admin', 'admin@khamcare.com', '$admin_pass', 'Quản trị viên', 'quan_tri', 'hoat_dong')
        ");
    }
}

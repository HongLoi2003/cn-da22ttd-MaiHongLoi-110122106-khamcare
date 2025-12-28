<?php
session_start();
require_once __DIR__ . '/db_config.php';

// đảm bảo đã login
if (empty($_SESSION['user_id'])) {
    header('Location: TaiKhoan.php');
    exit;
}
$user_id = (int) $_SESSION['user_id'];
$user = getUserById($user_id);
if (!$user) {
    echo "<div style='color:red'>Người dùng không tồn tại.</div>";
    exit;
}

// Lấy thông tin chi tiết từ user_profiles
$user_profile = [];
try {
    $pdo = getDBConnection();
    if ($pdo instanceof PDO) {
        // Kiểm tra bảng user_profiles có tồn tại không
        $stmt = $pdo->query("SHOW TABLES LIKE 'user_profiles'");
        $table_exists = $stmt->rowCount() > 0;
        
        if ($table_exists) {
            $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } else {
            // Tạo bảng user_profiles nếu chưa có
            $create_table_sql = "
            CREATE TABLE `user_profiles` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `ho_va_ten` varchar(100) DEFAULT NULL COMMENT 'Họ và tên',
              `so_dien_thoai` varchar(15) DEFAULT NULL COMMENT 'Số điện thoại',
              `ngay_sinh` date DEFAULT NULL COMMENT 'Ngày sinh',
              `gioi_tinh` enum('Nam','Nữ','Khác') DEFAULT NULL COMMENT 'Giới tính',
              `dia_chi` text DEFAULT NULL COMMENT 'Địa chỉ',
              `lien_he_khan_cap` varchar(255) DEFAULT NULL COMMENT 'Liên hệ khẩn cấp',
              `tien_su_benh` text DEFAULT NULL COMMENT 'Tiền sử bệnh',
              `nhom_mau` varchar(10) DEFAULT NULL COMMENT 'Nhóm máu',
              `di_ung` text DEFAULT NULL COMMENT 'Dị ứng',
              `ghi_chu` text DEFAULT NULL COMMENT 'Ghi chú thêm',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `user_id` (`user_id`),
              FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            $pdo->exec($create_table_sql);
            error_log('[update_profile] Created user_profiles table');
        }
    }
} catch (Exception $e) {
    error_log('[update_profile] Error getting user profile: ' . $e->getMessage());
}

$message = '';

// Kiểm tra xem có đến từ đặt lịch không
$from_booking = isset($_GET['step']) && $_GET['step'] === 'profile';
$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : null;
$appointment_info = null;

// Lấy thông tin lịch hẹn nếu có (sử dụng bảng tiếng Việt)
if ($from_booking && $appointment_id) {
    try {
        $pdo = getDBConnection();
        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare("SELECT lh.*, lh.id, lh.tong_phi as consultation_fee, 
                                          b.ho_ten as doctor_name, c.ten as specialty_name
                                   FROM lich_hen lh
                                   JOIN bac_si b ON lh.bac_si_id = b.id
                                   LEFT JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id
                                   WHERE lh.id = ? AND lh.benh_nhan_id = ?");
            $stmt->execute([$appointment_id, $user_id]);
            $appointment_info = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        error_log('[update_profile] ' . $e->getMessage());
    }
}

// lấy danh sách bác sĩ để chọn (sử dụng bảng tiếng Việt)
$doctorsList = [];
try {
    $pdo = getDBConnection();
    if ($pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT id, ho_ten as full_name FROM bac_si ORDER BY ho_ten");
        $doctorsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) { error_log('[update_profile] '.$e->getMessage()); }

// Xử lý upload avatar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_avatar') {
    header('Content-Type: application/json');
    
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Không có file được tải lên hoặc có lỗi xảy ra']);
        exit;
    }
    
    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)']);
        exit;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        echo json_encode(['success' => false, 'message' => 'Kích thước file không được vượt quá 5MB']);
        exit;
    }
    
    // Tạo thư mục uploads nếu chưa có
    $uploadDir = 'uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Tạo tên file unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        try {
            $pdo = getDBConnection();
            if ($pdo instanceof PDO) {
                // Lưu ảnh vào file JSON để tránh lỗi database
                $imageDataFile = 'doctor_images.json';
                $imageData = [];
                
                // Đọc dữ liệu cũ nếu có
                if (file_exists($imageDataFile)) {
                    $imageData = json_decode(file_get_contents($imageDataFile), true) ?: [];
                }
                
                // Cập nhật ảnh cho user hiện tại
                $imageData[$user_id] = $uploadPath;
                
                // Lưu lại file
                file_put_contents($imageDataFile, json_encode($imageData, JSON_PRETTY_PRINT));
                
                // Thử cập nhật database nếu có thể
                try {
                    // Kiểm tra xem user có phải là doctor không
                    $stmt = $pdo->prepare("SELECT d.id FROM doctors d WHERE d.user_id = ?");
                    $stmt->execute([$user_id]);
                    $doctor = $stmt->fetch();
                    
                    if ($doctor) {
                        // Thử cập nhật cột image trong bảng doctors
                        $stmt = $pdo->prepare("UPDATE doctors SET image = ? WHERE user_id = ?");
                        $stmt->execute([$uploadPath, $user_id]);
                    }
                } catch (Exception $e) {
                    // Không sao, đã lưu vào file JSON rồi
                    error_log("Could not update database, using JSON file: " . $e->getMessage());
                }
                
                echo json_encode(['success' => true, 'message' => 'Cập nhật ảnh đại diện thành công', 'image_path' => $uploadPath]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu file']);
    }
    exit;
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $ho_va_ten = trim($_POST['ho_va_ten'] ?? '');
    $so_dien_thoai = trim($_POST['so_dien_thoai'] ?? '');
    $ngay_sinh = $_POST['ngay_sinh'] ?? '';
    $gioi_tinh = $_POST['gioi_tinh'] ?? '';
    $dia_chi = trim($_POST['dia_chi'] ?? '');
    $lien_he_khan_cap = trim($_POST['lien_he_khan_cap'] ?? '');
    $tien_su_benh = trim($_POST['tien_su_benh'] ?? '');
    $nhom_mau = trim($_POST['nhom_mau'] ?? '');
    $di_ung = trim($_POST['di_ung'] ?? '');
    $ghi_chu = trim($_POST['ghi_chu'] ?? '');
    
    // Validate dữ liệu
    $errors = [];
    if (empty($ho_va_ten)) $errors[] = 'Họ và tên không được để trống';
    if (empty($so_dien_thoai)) $errors[] = 'Số điện thoại không được để trống';
    if (empty($dia_chi)) $errors[] = 'Địa chỉ không được để trống';
    if (empty($ngay_sinh)) $errors[] = 'Ngày sinh không được để trống';
    if (empty($gioi_tinh)) $errors[] = 'Giới tính không được để trống';
    
    // Validate số điện thoại
    if (!empty($so_dien_thoai) && !preg_match('/^[0-9+\-\s()]{10,15}$/', $so_dien_thoai)) {
        $errors[] = 'Số điện thoại không hợp lệ';
    }
    
    // Validate ngày sinh
    if (!empty($ngay_sinh)) {
        $birth_date = new DateTime($ngay_sinh);
        $today = new DateTime();
        $age = $today->diff($birth_date)->y;
        if ($age < 0 || $age > 120) {
            $errors[] = 'Ngày sinh không hợp lệ';
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            if ($pdo instanceof PDO) {
                $pdo->beginTransaction();
                
                // Cập nhật bảng nguoi_dung
                $stmt = $pdo->prepare("UPDATE nguoi_dung SET 
                    ho_ten = ?, so_dien_thoai = ?, ngay_sinh = ?, 
                    gioi_tinh = ?, dia_chi = ?, lien_he_khan_cap = ?, tien_su_benh = ?
                    WHERE id = ?");
                
                $stmt->execute([$ho_va_ten, $so_dien_thoai, $ngay_sinh, 
                               $gioi_tinh, $dia_chi, $lien_he_khan_cap, $tien_su_benh, $user_id]);
                
                // Cập nhật hoặc tạo mới bảng ho_so_nguoi_dung với đầy đủ các cột
                $stmt = $pdo->prepare("INSERT INTO ho_so_nguoi_dung 
                    (nguoi_dung_id, ho_va_ten, so_dien_thoai, ngay_sinh, gioi_tinh, dia_chi, lien_he_khan_cap, tien_su_benh, nhom_mau, di_ung, ghi_chu) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    ho_va_ten = VALUES(ho_va_ten),
                    so_dien_thoai = VALUES(so_dien_thoai),
                    ngay_sinh = VALUES(ngay_sinh),
                    gioi_tinh = VALUES(gioi_tinh),
                    dia_chi = VALUES(dia_chi),
                    lien_he_khan_cap = VALUES(lien_he_khan_cap),
                    tien_su_benh = VALUES(tien_su_benh),
                    nhom_mau = VALUES(nhom_mau),
                    di_ung = VALUES(di_ung),
                    ghi_chu = VALUES(ghi_chu)");
                
                $stmt->execute([$user_id, $ho_va_ten, $so_dien_thoai, $ngay_sinh, 
                               $gioi_tinh, $dia_chi, $lien_he_khan_cap, $tien_su_benh, 
                               $nhom_mau, $di_ung, $ghi_chu]);
                
                $pdo->commit();
                $message = 'Cập nhật hồ sơ thành công!';
                
                // Cập nhật session
                $_SESSION['profile_updated'] = true;
                
                // Reload user data
                $user = getUserById($user_id);
                $user_profile = [];
                try {
                    $stmt = $pdo->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                } catch (Exception $e) {
                    error_log('[update_profile] Error reloading user profile: ' . $e->getMessage());
                }
                
            } else {
                $message = 'Lỗi kết nối database';
            }
        } catch (Exception $e) {
            if (isset($pdo)) $pdo->rollBack();
            $message = 'Lỗi: ' . $e->getMessage();
            error_log('[update_profile] ' . $e->getMessage());
        }
    } else {
        $message = implode(', ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Cập nhật hồ sơ cá nhân</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <style>
        
        

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Modern Color Palette */
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #f8fafc;
            --accent: #10b981;
            --accent-light: #34d399;
            --accent-dark: #059669;
            --card-bg: #ffffff;
            --field-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --border-focus: #3b82f6;
            --success-bg: #ecfdf5;
            --success-text: #065f46;
            --error-bg: #fef2f2;
            --error-text: #dc2626;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: 
                radial-gradient(circle at 20% 80%, rgba(14, 165, 233, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.2) 0%, transparent 50%),
                linear-gradient(135deg, #0c4a6e 0%, #0369a1 50%, #0284c7 100%);
            min-height: 100vh;
            padding: 30px 20px;
            margin: 0;
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25px 25px, rgba(255, 255, 255, 0.08) 2px, transparent 2px),
                radial-gradient(circle at 75px 75px, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 100px 100px, 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        /* Main Container */
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0;
            box-sizing: border-box;
        }

        /* Card */
        .card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            box-shadow: 
                0 25px 70px rgba(0, 0, 0, 0.25),
                0 10px 30px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(20px);
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Top Header Bar - Inside Card */
        .top-header {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid #e2e8f0;
            padding: 25px 50px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .top-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #6366f1 50%, transparent);
            opacity: 0.3;
        }
        
        /* Logo Section */
        /* Logo Styles */
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #06b6d4;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .logo-section span {
            background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 50%, #10b981 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: logoTextShine 3s linear infinite;
        }

        @keyframes logoTextShine {
            0% {
                background-position: 0% center;
            }
            100% {
                background-position: 200% center;
            }
        }

        .logo-section:hover {
            transform: scale(1.02);
        }

        .logo-section:hover span {
            animation-duration: 1.5s;
        }

        .logo-section img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            border-radius: 18px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: logoFloat 3s ease-in-out infinite, logoPulse 2s ease-in-out infinite;
            background: transparent;
        }

        .logo-section:hover img {
            transform: scale(1.08) rotate(2deg);
            filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.25));
            animation-play-state: paused;
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes logoPulse {
            0%, 100% {
                filter: drop-shadow(0 4px 12px rgba(6, 182, 212, 0.3));
            }
            50% {
                filter: drop-shadow(0 6px 20px rgba(6, 182, 212, 0.5));
            }
        }
        
        .logo-text {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 28px;
            font-weight: 700;
            color: #06b6d4;
            letter-spacing: -0.5px;
        }
        
        /* Page Title */
        .page-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-title::before {
            content: "🏥";
            font-size: 30px;
        }
        
        @media (max-width: 992px) {
            .top-header {
                padding: 18px 30px;
            }
            
            .logo-text {
                font-size: 24px;
            }
            
            .page-title {
                font-size: 22px;
            }
            
            .page-title::before {
                font-size: 24px;
            }
        }
        
        @media (max-width: 768px) {
            .top-header {
                padding: 15px 20px;
            }
            
            .logo-section img {
                width: 45px;
                height: 45px;
            }
            
            .logo-text {
                font-size: 20px;
            }
            
            .page-title {
                position: static;
                transform: none;
                font-size: 18px;
            }
            
            .page-title::before {
                font-size: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .logo-text {
                display: none;
            }
            
            .page-title {
                font-size: 16px;
            }
        }

        /* Header */
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 30px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            display: none;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .card-header h2 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .card-header h2::before {
            content: "👤";
            font-size: 24px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        .card-header .subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 15px;
            margin-top: 8px;
            position: relative;
            z-index: 1;
        }

        /* Card Body */
        .card-body {
            padding: 40px 60px;
            max-width: 1600px;
            margin: 0 auto;
        }
        
        @media (min-width: 1600px) {
            .card-body {
                padding: 50px 100px;
                max-width: 1800px;
            }
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        @media (min-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }
        }
        
        @media (min-width: 1400px) {
            .form-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 40px;
            }
        }

        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            transition: width 0.4s ease;
        }
        
        .form-group:focus-within::after {
            width: 100%;
        }

        /* Labels */
        label {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-primary);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s ease;
        }

        label::before {
            content: attr(data-icon);
            font-size: 18px;
            opacity: 0.8;
            transition: transform 0.3s ease;
        }
        
        .form-group:hover label {
            color: #6366f1;
        }
        
        .form-group:hover label::before {
            transform: scale(1.2);
        }

        /* Input Styles */
        input[type="text"],
        input[type="tel"],
        input[type="date"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid var(--border-color);
            border-radius: 14px;
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            font-size: 15px;
            color: var(--text-primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        input[type="text"]:focus,
        input[type="tel"]:focus,
        input[type="date"]:focus,
        input[type="email"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #6366f1;
            background: #ffffff;
            box-shadow: 
                0 0 0 4px rgba(99, 102, 241, 0.1),
                0 4px 12px rgba(99, 102, 241, 0.15);
            transform: translateY(-2px);
        }
        
        input[type="text"]:hover,
        input[type="tel"]:hover,
        input[type="date"]:hover,
        input[type="email"]:hover,
        select:hover,
        textarea:hover {
            border-color: #a5b4fc;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
            appearance: none;
        }

        /* Button Styles */
        .form-actions {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 35px;
            padding-top: 35px;
            border-top: 2px solid #e5e7eb;
            position: relative;
        }
        
        .form-actions::before {
            content: '';
            position: absolute;
            top: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #6366f1 50%, transparent);
            opacity: 0.3;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 36px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            font-family: inherit;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            box-shadow: 
                0 8px 20px rgba(99, 102, 241, 0.3),
                0 3px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 12px 28px rgba(99, 102, 241, 0.4),
                0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: transparent;
            color: #6366f1;
            border: 2px solid #6366f1;
        }

        .btn-secondary:hover {
            background: #6366f1;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        /* Message Styles */
        .message {
            margin-bottom: 25px;
            padding: 16px 20px;
            border-radius: 12px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .message.success {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid var(--accent-light);
        }

        .message.success::before {
            content: "✅";
            font-size: 18px;
        }

        .message.error {
            background: var(--error-bg);
            color: var(--error-text);
            border: 1px solid #fecaca;
        }

        .message.error::before {
            content: "❌";
            font-size: 18px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .card-header {
                padding: 30px 20px 25px;
            }

            .card-header h2 {
                font-size: 26px;
            }

            .card-body {
                padding: 30px 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .card-header h2 {
                font-size: 22px;
                flex-direction: column;
                gap: 10px;
            }

            .card-body {
                padding: 20px 15px;
            }
        }

        /* Loading Animation */
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-primary:disabled::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 12px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Hover Effects */
        .form-group:hover label {
            color: var(--primary);
        }

        input[type="text"]:hover,
        input[type="tel"]:hover,
        input[type="date"]:hover,
        input[type="email"]:hover,
        select:hover,
        textarea:hover {
            border-color: var(--primary-light);
        }
        
        /* Booking Success Alert */
        .booking-success-alert {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: flex-start;
            gap: 20px;
        }
        
        .success-icon {
            font-size: 3rem;
            opacity: 0.9;
        }
        
        .success-content h3 {
            margin: 0 0 10px 0;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .appointment-details {
            margin-top: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
        }
        
        .appointment-details p {
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .appointment-details i {
            width: 20px;
            text-align: center;
        }
        
        .fee-amount {
            font-weight: 700;
            font-size: 1.1rem;
            color: #fef3c7;
        }
        
        .appointment-code {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 1.1rem;
            color: #fef3c7;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Next Step Alert */
        .next-step-alert {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }
        
        .next-step-alert h4 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .next-step-alert p {
            margin: 0;
            opacity: 0.95;
        }
        
        /* Payment Button */
        .payment-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 12px 25px;
            animation: pulse-payment 2s infinite;
        }
        
        .payment-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }
        
        @keyframes pulse-payment {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        /* Form validation styles */
        .form-group.error input,
        .form-group.error select,
        .form-group.error textarea {
            border-color: #dc2626;
            background-color: #fef2f2;
        }
        
        .form-group.success input,
        .form-group.success select,
        .form-group.success textarea {
            border-color: #10b981;
            background-color: #ecfdf5;
        }
        
        .field-error {
            color: #dc2626;
            font-size: 12px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .field-error::before {
            content: "⚠️";
            font-size: 14px;
        }
        
        /* Required field indicator */
        label[data-required="true"]::after {
            content: " *";
            color: #dc2626;
            font-weight: bold;
        }
        
        /* Profile completion indicator */
        .profile-completion {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #d1d5db;
        }
        
        .completion-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .completion-title {
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .completion-percentage {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent) 0%, var(--accent-light) 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        

        
        /* Payment Button States */
        .payment-btn-active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 16px 32px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        
        .payment-btn-active:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(16, 185, 129, 0.4);
        }
        
        .payment-btn-ready {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
            font-weight: 600;
            padding: 16px 32px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }
        
        .payment-btn-ready:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(16, 185, 129, 0.4);
        }
        
        .payment-btn-disabled {
            background: #9ca3af !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
            opacity: 0.6;
            font-weight: 500;
            padding: 16px 32px;
        }
        
        .payment-btn-disabled:hover {
            transform: none !important;
            box-shadow: none !important;
        }
        
        /* Alert Variants */
        .payment-ready-alert {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white;
        }
        
        .payment-disabled-alert {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: white;
        }
        

        
        /* Button Icon and Text Styling */
        .btn-icon {
            font-size: 1.2em;
            margin-right: 8px;
        }
        
        .btn-text {
            font-weight: inherit;
        }
        
        /* Payment Section */
        .payment-section {
            text-align: center;
            padding: 2rem 0;
        }
        
        .payment-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .no-booking-section {
            text-align: center;
            padding: 3rem 0;
        }
        
        .no-booking-section p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .booking-success-alert {
                flex-direction: column;
                text-align: center;
            }
            
            .success-icon {
                font-size: 2rem;
            }
            
            .appointment-details p {
                flex-direction: column;
                gap: 5px;
                text-align: left;
            }
            
            .payment-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .payment-actions .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- Top Header Bar -->
            <div class="top-header">
                <a href="TrangChu.php" class="logo-section" title="Quay về trang chủ">
                    <img src="image/logo.png.png" alt="Logo">
                    <span>KhamCare</span>
                </a>
                
                <div class="page-title">
                    Cập nhật hồ sơ bệnh án 
                </div>
            </div>
            
            <div class="card-header">
                <?php if ($from_booking): ?>
                    <h2>📋 Cập Nhật Hồ Sơ</h2>
                    <p class="subtitle">Vui lòng hoàn tất thông tin hồ sơ để tiến hành thanh toán</p>
                <?php else: ?>
                    <h2>👤 Cập Nhật Hồ Sơ Cá Nhân</h2>
                    <p class="subtitle">Quản lý và cập nhật thông tin cá nhân của bạn</p>
                <?php endif; ?>
            </div>

            <div class="card-body">
                <?php if ($from_booking && $appointment_info): ?>
                    <div class="booking-success-alert">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="success-content">
                            <h3>🎉 Đặt lịch thành công!</h3>
                            <p><strong>Mã lịch khám:</strong> <span class="appointment-code">APPT<?php echo str_pad($appointment_info['id'], 3, '0', STR_PAD_LEFT); ?></span></p>
                            <div class="appointment-details">
                                <p><i class="fas fa-user-md"></i> <strong>Bác sĩ:</strong> BS. <?php echo htmlspecialchars($appointment_info['doctor_name']); ?></p>
                                <p><i class="fas fa-stethoscope"></i> <strong>Chuyên khoa:</strong> <?php echo htmlspecialchars($appointment_info['specialty_name']); ?></p>
                                <p><i class="fas fa-calendar"></i> <strong>Ngày khám:</strong> <?php echo date('d/m/Y', strtotime($appointment_info['appointment_date'])); ?></p>
                                <p><i class="fas fa-clock"></i> <strong>Giờ khám:</strong> <?php echo substr($appointment_info['appointment_time'], 0, 5); ?></p>
                                <p><i class="fas fa-money-bill-wave"></i> <strong>Phí khám:</strong> <span class="fee-amount"><?php echo number_format($appointment_info['consultation_fee'], 0, ',', '.'); ?> VNĐ</span></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="message <?php echo (strpos($message,'thành công')!==false)?'success':'error' ?>">
                        <?php echo htmlspecialchars($message) ?>
                    </div>
                    
                    <?php 
                    // Nếu cập nhật thành công, hiển thị nút thanh toán
                    if (strpos($message, 'thành công') !== false && ($from_booking || isset($_SESSION['temp_appointment_id']))): 
                        $temp_appointment_id = $_SESSION['temp_appointment_id'] ?? $appointment_id;
                        $temp_consultation_fee = $_SESSION['temp_consultation_fee'] ?? 300000;
                        $temp_doctor_id = $_SESSION['temp_doctor_id'] ?? null;
                        
                        // Lấy thông tin bác sĩ nếu có
                        $doctor_name = '';
                        $specialty_name = '';
                        if ($temp_doctor_id) {
                            try {
                                $pdo = getDBConnection();
                                $stmt = $pdo->prepare("SELECT b.ho_ten, c.ten as specialty FROM bac_si b LEFT JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id WHERE b.id = ?");
                                $stmt->execute([$temp_doctor_id]);
                                $doc = $stmt->fetch(PDO::FETCH_ASSOC);
                                if ($doc) {
                                    $doctor_name = $doc['ho_ten'];
                                    $specialty_name = $doc['specialty'] ?? '';
                                }
                            } catch (Exception $e) {}
                        }
                    ?>
                        <div class="payment-section" style="margin-top: 20px; padding: 25px; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 16px; border: 2px solid #10b981;">
                            <h4 style="color: #065f46; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-check-circle"></i> Hồ sơ đã cập nhật thành công!
                            </h4>
                            <p style="color: #047857; margin-bottom: 20px;">Bây giờ bạn có thể tiến hành thanh toán để hoàn tất đặt lịch khám.</p>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <?php if ($temp_appointment_id): ?>
                                <a href="payment.php?appointment_id=<?php echo $temp_appointment_id; ?>&amount=<?php echo $temp_consultation_fee; ?>&doctor_name=<?php echo urlencode($doctor_name); ?>&specialty=<?php echo urlencode($specialty_name); ?>" 
                                   class="btn btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 14px 28px;">
                                    <i class="fas fa-credit-card"></i> Thanh toán <?php echo number_format($temp_consultation_fee, 0, ',', '.'); ?>đ
                                </a>
                                <?php endif; ?>
                                <a href="tk.php" class="btn btn-secondary" style="padding: 14px 28px;">
                                    <i class="fas fa-calendar-check"></i> Xem lịch hẹn của tôi
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Form cập nhật hồ sơ -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="ho_va_ten" data-icon="👤">Họ và tên *</label>
                            <input type="text" id="ho_va_ten" name="ho_va_ten" 
                                   value="<?php echo htmlspecialchars($user_profile['ho_va_ten'] ?? $user['full_name'] ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="so_dien_thoai" data-icon="📱">Số điện thoại *</label>
                            <input type="tel" id="so_dien_thoai" name="so_dien_thoai" 
                                   value="<?php echo htmlspecialchars($user_profile['so_dien_thoai'] ?? $user['phone'] ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="ngay_sinh" data-icon="🎂">Ngày sinh *</label>
                            <input type="date" id="ngay_sinh" name="ngay_sinh" 
                                   value="<?php echo htmlspecialchars($user_profile['ngay_sinh'] ?? $user['date_of_birth'] ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="gioi_tinh" data-icon="⚧">Giới tính *</label>
                            <select id="gioi_tinh" name="gioi_tinh" required>
                                <option value="">Chọn giới tính</option>
                                <?php 
                                $current_gender = $user_profile['gioi_tinh'] ?? $user['gender'] ?? '';
                                ?>
                                <option value="Nam" <?php echo $current_gender === 'Nam' ? 'selected' : ''; ?>>Nam</option>
                                <option value="Nữ" <?php echo $current_gender === 'Nữ' ? 'selected' : ''; ?>>Nữ</option>
                                <option value="Khác" <?php echo $current_gender === 'Khác' ? 'selected' : ''; ?>>Khác</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label for="dia_chi" data-icon="🏠">Địa chỉ *</label>
                            <input type="text" id="dia_chi" name="dia_chi" 
                                   value="<?php echo htmlspecialchars($user_profile['dia_chi'] ?? $user['address'] ?? ''); ?>" 
                                   required>
                        </div>

                        <div class="form-group full-width">
                            <label for="lien_he_khan_cap" data-icon="🚨">Liên hệ khẩn cấp</label>
                            <input type="text" id="lien_he_khan_cap" name="lien_he_khan_cap" 
                                   value="<?php echo htmlspecialchars($user_profile['lien_he_khan_cap'] ?? $user['emergency_contact'] ?? ''); ?>" 
                                   placeholder="Tên và số điện thoại người thân (VD: Nguyễn Văn A - 0901234567)">
                        </div>

                        <div class="form-group">
                            <label for="nhom_mau" data-icon="🩸">Nhóm máu</label>
                            <select id="nhom_mau" name="nhom_mau">
                                <option value="">Chọn nhóm máu</option>
                                <?php 
                                $current_blood = $user_profile['nhom_mau'] ?? '';
                                $blood_types = ['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                foreach ($blood_types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo $current_blood === $type ? 'selected' : ''; ?>><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="di_ung" data-icon="⚠️">Dị ứng</label>
                            <input type="text" id="di_ung" name="di_ung" 
                                   value="<?php echo htmlspecialchars($user_profile['di_ung'] ?? ''); ?>" 
                                   placeholder="VD: Penicillin, tôm cua...">
                        </div>

                        <div class="form-group full-width">
                            <label for="tien_su_benh" data-icon="📋">Tiền sử bệnh</label>
                            <textarea id="tien_su_benh" name="tien_su_benh" 
                                      placeholder="Mô tả các bệnh lý, thuốc đang sử dụng..."><?php echo htmlspecialchars($user_profile['tien_su_benh'] ?? $user['medical_history'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="ghi_chu" data-icon="📝">Ghi chú thêm</label>
                            <textarea id="ghi_chu" name="ghi_chu" 
                                      placeholder="Thông tin bổ sung khác..."><?php echo htmlspecialchars($user_profile['ghi_chu'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            💾 Cập nhật hồ sơ
                        </button>
                    </div>
                </form>

                <?php // Đã xóa phần thanh toán - chỉ hiển thị form cập nhật hồ sơ ?>
            </div>
        </div>
    </div>

    <script>
        // Form validation và progress tracking
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action=""]');
            const requiredFields = ['ho_va_ten', 'so_dien_thoai', 'dia_chi', 'ngay_sinh', 'gioi_tinh'];
            
            // Thêm progress bar nếu đang trong flow đặt lịch
            <?php if ($from_booking): ?>
            addProfileCompletionIndicator();
            <?php endif; ?>
            
            // Lấy thông tin appointment từ PHP
            <?php if ($appointment_info): ?>
            window.appointmentInfo = {
                id: <?php echo $appointment_info['id']; ?>,
                consultation_fee: <?php echo $appointment_info['consultation_fee']; ?>,
                doctor_name: '<?php echo addslashes($appointment_info['doctor_name']); ?>',
                specialty_name: '<?php echo addslashes($appointment_info['specialty_name']); ?>'
            };
            <?php endif; ?>
            
            // Real-time validation
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field) {
                    field.addEventListener('input', function() {
                        validateField(this);
                        updateProgress();
                        updatePaymentButton();
                    });
                    field.addEventListener('change', function() {
                        validateField(this);
                        updateProgress();
                        updatePaymentButton();
                    });
                }
            });
            
            // Form submit validation
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    
                    requiredFields.forEach(fieldName => {
                        const field = document.getElementById(fieldName);
                        if (field && !validateField(field)) {
                            isValid = false;
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        showMessage('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
                    } else {
                        // Hiển thị loading state
                        const submitBtn = form.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Đang cập nhật...</span>';
                        }
                    }
                });
            }
            
            // Initial validation
            updateProgress();
        });
        
        function validateField(field) {
            const formGroup = field.closest('.form-group');
            const value = field.value.trim();
            let isValid = true;
            let errorMessage = '';
            
            // Remove existing error
            const existingError = formGroup.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Check required fields
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = 'Trường này là bắt buộc';
            }
            
            // Specific validations
            if (value) {
                switch (field.name) {
                    case 'so_dien_thoai':
                        if (!/^[0-9+\-\s()]{10,15}$/.test(value)) {
                            isValid = false;
                            errorMessage = 'Số điện thoại không hợp lệ';
                        }
                        break;
                    case 'ngay_sinh':
                        const birthDate = new Date(value);
                        const today = new Date();
                        const age = today.getFullYear() - birthDate.getFullYear();
                        if (age < 0 || age > 120) {
                            isValid = false;
                            errorMessage = 'Ngày sinh không hợp lệ';
                        }
                        break;
                }
            }
            
            // Update UI
            formGroup.classList.remove('error', 'success');
            if (!isValid) {
                formGroup.classList.add('error');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'field-error';
                errorDiv.textContent = errorMessage;
                formGroup.appendChild(errorDiv);
            } else if (value) {
                formGroup.classList.add('success');
            }
            
            return isValid;
        }
        
        function addProfileCompletionIndicator() {
            const form = document.querySelector('form[action=""]');
            if (!form) return;
            
            const completionHTML = `
                <div class="profile-completion">
                    <div class="completion-header">
                        <div class="completion-title">
                            <span>📊</span>
                            <span>Tiến độ hoàn thành hồ sơ</span>
                        </div>
                        <div class="completion-percentage" id="completion-percentage">0%</div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
            `;
            
            form.insertAdjacentHTML('beforebegin', completionHTML);
        }
        
        function updateProgress() {
            const progressFill = document.getElementById('progress-fill');
            const progressPercentage = document.getElementById('completion-percentage');
            
            if (!progressFill || !progressPercentage) return;
            
            const requiredFields = ['ho_va_ten', 'so_dien_thoai', 'dia_chi', 'ngay_sinh', 'gioi_tinh'];
            let completedFields = 0;
            
            requiredFields.forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (field && field.value.trim()) {
                    completedFields++;
                }
            });
            
            const percentage = Math.round((completedFields / requiredFields.length) * 100);
            
            progressFill.style.width = percentage + '%';
            progressPercentage.textContent = percentage + '%';
            
            // Update color based on completion
            if (percentage === 100) {
                progressFill.style.background = 'linear-gradient(90deg, #10b981 0%, #059669 100%)';
                progressPercentage.style.color = '#059669';
            } else if (percentage >= 60) {
                progressFill.style.background = 'linear-gradient(90deg, #f59e0b 0%, #d97706 100%)';
                progressPercentage.style.color = '#d97706';
            } else {
                progressFill.style.background = 'linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%)';
                progressPercentage.style.color = '#1d4ed8';
            }
        }
        
        function showMessage(text, type) {
            // Remove existing messages
            const existingMessages = document.querySelectorAll('.message');
            existingMessages.forEach(msg => msg.remove());
            
            // Create new message
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            messageDiv.textContent = text;
            
            // Insert at top of card body
            const cardBody = document.querySelector('.card-body');
            cardBody.insertBefore(messageDiv, cardBody.firstChild);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
            

        }
        
        // Cập nhật trạng thái nút thanh toán real-time
        // Đã xóa function updatePaymentButton - không cần nút thanh toán
    </script>
</body>
</html>
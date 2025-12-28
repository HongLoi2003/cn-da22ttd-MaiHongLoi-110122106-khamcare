<?php
session_start();
require_once 'db_config.php';

// Khởi tạo kết nối database
$pdo = getDBConnection();
if (!$pdo) {
    die('Không thể kết nối database. Vui lòng kiểm tra cấu hình.');
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // login hoặc register

// Hiển thị thông báo đăng xuất
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success = 'Đã đăng xuất thành công!';
}

// Xử lý đăng ký
if (isset($_POST['action']) && $_POST['action'] === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $specialty = trim($_POST['specialty']);
    $phone = trim($_POST['phone']);
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($specialty) || empty($phone)) {
        $error = 'Vui lòng điền đầy đủ thông tin';
    } elseif (!isset($_FILES['license_file']) || $_FILES['license_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Vui lòng tải lên hình ảnh chứng chỉ hành nghề';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } else {
        // Validate file upload
        $file = $_FILES['license_file'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Định dạng file không hợp lệ. Chỉ chấp nhận JPG, PNG, PDF';
        } elseif ($file['size'] > $max_size) {
            $error = 'File quá lớn. Kích thước tối đa là 5MB';
        } else {
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
                $error = 'Có lỗi khi tải file lên. Vui lòng thử lại';
            }
        }
        
        if (empty($error)) {
        try {
            // Kiểm tra email đã tồn tại trong bảng nguoi_dung
            $check_email = $pdo->prepare("SELECT id FROM nguoi_dung WHERE email = ?");
            $check_email->execute([$email]);
            
            if ($check_email->fetch()) {
                $error = 'Email này đã được đăng ký';
            } else {
                // Tìm specialty_id từ tên chuyên khoa
                $specialty_stmt = $pdo->prepare("SELECT id FROM chuyen_khoa WHERE ten = ?");
                $specialty_stmt->execute([$specialty]);
                $specialty_row = $specialty_stmt->fetch();
                
                if (!$specialty_row) {
                    // Tạo chuyên khoa mới nếu chưa có
                    $create_specialty = $pdo->prepare("INSERT INTO chuyen_khoa (ten, mo_ta, icon, mau_sac, thu_tu) VALUES (?, ?, 'fas fa-stethoscope', '#2ecc71', 99)");
                    $create_specialty->execute([$specialty, "Chuyên khoa " . $specialty]);
                    $specialty_id = $pdo->lastInsertId();
                } else {
                    $specialty_id = $specialty_row['id'];
                }
                
                // Tạo user account - CHỜ DUYỆT (cho_duyet)
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_stmt = $pdo->prepare("INSERT INTO nguoi_dung (ten_dang_nhap, email, mat_khau, ho_ten, so_dien_thoai, vai_tro, trang_thai) VALUES (?, ?, ?, ?, ?, 'bac_si', 'cho_duyet')");
                $username = 'dr_' . strtolower(str_replace(' ', '', $name)) . '_' . time();
                $user_stmt->execute([$username, $email, $hashed_password, $name, $phone]);
                $user_id = $pdo->lastInsertId();
                
                // Thêm cột giay_phep_hanh_nghe vào bảng nguoi_dung nếu chưa có
                try {
                    $check_column = $pdo->query("SHOW COLUMNS FROM nguoi_dung LIKE 'giay_phep_hanh_nghe'");
                    if (!$check_column->fetch()) {
                        $pdo->exec("ALTER TABLE nguoi_dung ADD COLUMN giay_phep_hanh_nghe VARCHAR(255) DEFAULT NULL AFTER so_dien_thoai");
                    }
                } catch (PDOException $e) {
                    // Cột có thể đã tồn tại
                }
                
                // Cập nhật giay_phep_hanh_nghe
                $update_license = $pdo->prepare("UPDATE nguoi_dung SET giay_phep_hanh_nghe = ? WHERE id = ?");
                $update_license->execute([$license_path, $user_id]);
                
                // Tạo doctor record - CHỜ DUYỆT (cho_duyet)
                $doctor_stmt = $pdo->prepare("INSERT INTO bac_si (nguoi_dung_id, ho_ten, chuyen_khoa_id, phi_kham, nam_kinh_nghiem, trang_thai) VALUES (?, ?, ?, 300000, 0, 'khong_hoat_dong')");
                $doctor_stmt->execute([$user_id, $name, $specialty_id]);
                
                $success = 'Đăng ký thành công! Vui lòng chờ Admin duyệt tài khoản trước khi đăng nhập.';
                $mode = 'login';
            }
        } catch (PDOException $e) {
            error_log("Doctor registration error: " . $e->getMessage());
            $error = 'Có lỗi xảy ra khi đăng ký: ' . $e->getMessage();
        }
        }
    }
}

// Xử lý đăng nhập
if (isset($_POST['action']) && $_POST['action'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập email và mật khẩu';
    } else {
        try {
            // Tìm user với vai_tro bac_si
            $stmt = $pdo->prepare("SELECT n.*, b.id as doctor_id FROM nguoi_dung n 
                                  LEFT JOIN bac_si b ON n.id = b.nguoi_dung_id 
                                  WHERE n.email = ? AND n.vai_tro = 'bac_si'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['mat_khau'])) {
                if ($user['trang_thai'] === 'hoat_dong') {
                    $_SESSION['doctor_id'] = $user['doctor_id'];
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['doctor_name'] = $user['ho_ten'];
                    $_SESSION['doctor_email'] = $user['email'];
                    $_SESSION['user_role'] = 'doctor';
                    $_SESSION['role'] = 'doctor';
                    header('Location: doctor_dashboard.php');
                    exit();
                } elseif ($user['trang_thai'] === 'cho_duyet') {
                    $error = 'Tài khoản của bạn đang chờ Admin duyệt. Vui lòng đợi hoặc liên hệ quản trị viên.';
                } else {
                    $error = 'Tài khoản của bạn đã bị vô hiệu hóa. Vui lòng liên hệ quản trị viên.';
                }
            } else {
                $error = 'Email hoặc mật khẩu không đúng';
            }
        } catch (PDOException $e) {
            error_log("Doctor login error: " . $e->getMessage());
            $error = 'Có lỗi xảy ra khi đăng nhập';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'login' ? 'Đăng nhập' : 'Đăng ký'; ?> - Bác sĩ KhamCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="global-font-override.css">
    <link rel="stylesheet" href="square-icons-override.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <link rel="stylesheet" href="fix-overlay-click.css">
    <style>
        
        
        

        :root {
            /* Medical Color Palette */
            --primary: #0ea5e9;
            --primary-light: #38bdf8;
            --primary-dark: #0284c7;
            --secondary: #f8fafc;
            --accent: #10b981;
            --accent-light: #34d399;
            --accent-dark: #059669;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --dark: #1e293b;
            --light: #f1f5f9;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            /* Spacing & Layout */
            --radius-sm: 10px;
            --radius: 15px;
            --radius-lg: 20px;
            --radius-xl: 25px;
            --radius-2xl: 30px;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            
            /* Transitions */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            --gradient-medical: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-card: linear-gradient(145deg, rgba(255,255,255,0.95) 0%, rgba(240,249,255,0.9) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: var(--gradient-medical);
            background-attachment: fixed;
            min-height: 100vh;
            color: var(--gray-800);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            position: relative;
            overflow-x: hidden;
        }

        /* Medical pattern overlay - DISABLED to prevent click blocking */
        body::before {
            display: none !important;
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25px 25px, rgba(255, 255, 255, 0.1) 2px, transparent 2px),
                radial-gradient(circle at 75px 75px, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 100px 100px, 50px 50px;
            pointer-events: none !important;
            z-index: -9999 !important;
        }

        /* Floating medical icons - DISABLED to prevent click blocking */
        body::after {
            display: none !important;
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cg fill='rgba(255,255,255,0.03)'%3E%3Cpath d='M45 35h10v30h-10z'/%3E%3Cpath d='M35 45h30v10h-30z'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 200px 200px;
            animation: float 20s ease-in-out infinite;
            pointer-events: none !important;
            z-index: -9999 !important;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        .auth-card {
            background: var(--gradient-card);
            backdrop-filter: blur(20px);
            border-radius: 35px;
            box-shadow: var(--shadow-xl), 0 0 0 1px rgba(255,255,255,0.1);
            overflow: hidden;
            max-width: 650px;
            width: 100%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            position: relative;
            animation: slideUp 0.6s ease-out;
            display: flex;
            flex-direction: column;
        }

        .auth-card.register-mode {
            max-width: 750px;
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

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--gradient-primary);
            border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
            box-shadow: 0 2px 10px rgba(14, 165, 233, 0.5);
        }

        .auth-header {
            background: var(--gradient-primary);
            color: var(--white);
            padding: 35px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        .auth-header.compact {
            padding: 28px 35px;
        }

        .auth-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cg fill='rgba(255,255,255,0.1)'%3E%3Cpath d='M40 30h20v40h-20z'/%3E%3Cpath d='M30 40h40v20h-40z'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 80px 80px;
            opacity: 0.3;
            z-index: 1;
        }

        .auth-header > * {
            position: relative;
            z-index: 2;
        }

        .logo {
            font-size: 3rem;
            margin-bottom: 12px;
            animation: pulse 2s ease-in-out infinite;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }

        .logo.compact {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .auth-header h2 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 1.9rem;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            letter-spacing: -0.5px;
        }

        .auth-header h2.compact {
            font-size: 1.7rem;
            margin: 0 0 8px 0;
        }

        .auth-header p {
            font-size: 1.05rem;
            margin: 0;
            opacity: 0.95;
            font-weight: 500;
            letter-spacing: 0.3px;
        }

        .auth-body {
            padding: 35px 40px;
            background: rgba(255, 255, 255, 0.98);
            overflow-y: visible;
            overflow-x: hidden;
            color: #1f2937;
        }

        .auth-body.register-body {
            padding: 30px 35px;
        }



        .auth-body input,
        .auth-body select,
        .auth-body textarea {
            color: #1f2937 !important;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group.compact {
            margin-bottom: 18px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 18px;
        }

        .form-label {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            display: block;
            font-size: 1rem;
            letter-spacing: 0.2px;
        }

        .form-label i {
            color: var(--primary);
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: stretch;
        }

        .input-group-text {
            background: linear-gradient(135deg, var(--gray-50) 0%, #f8fafc 100%);
            border: 2px solid var(--gray-200);
            border-right: none;
            border-radius: 15px 0 0 15px;
            padding: 13px 15px;
            display: flex;
            align-items: center;
            color: #6b7280;
            transition: var(--transition);
            font-size: 1.05rem;
        }

        .input-group-text.compact {
            padding: 11px 13px;
            font-size: 1rem;
        }

        .form-control {
            border: 2px solid var(--gray-200);
            border-radius: 15px;
            padding: 13px 16px;
            font-size: 1rem;
            font-weight: 500;
            background: var(--white);
            color: #1f2937;
            transition: var(--transition);
            font-family: inherit;
            flex: 1;
        }

        .form-control.compact {
            padding: 11px 14px;
            font-size: 0.95rem;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 15px 15px 0;
        }

        .form-control:focus,
        .input-group .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(14, 165, 233, 0.15), 0 4px 12px rgba(14, 165, 233, 0.1);
            transform: translateY(-2px);
        }

        .form-control:focus + .input-group-text,
        .input-group:focus-within .input-group-text {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.08) 0%, rgba(14, 165, 233, 0.05) 100%);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #9ca3af;
            font-weight: 400;
            opacity: 0.7;
        }

        select.form-control {
            color: #1f2937;
            font-weight: 500;
        }

        select.form-control option {
            color: #1f2937;
            font-weight: 500;
        }

        .specialty-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 16px center;
            background-repeat: no-repeat;
            background-size: 18px;
            padding-right: 45px;
            cursor: pointer;
        }

        .specialty-select:hover {
            border-color: var(--primary);
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: 15px;
            padding: 15px 28px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            color: var(--white) !important;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 10px 25px -5px rgba(14, 165, 233, 0.4), 0 8px 10px -6px rgba(14, 165, 233, 0.3);
            position: relative;
            overflow: hidden;
            font-family: inherit;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary i {
            font-size: 1.1rem;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: var(--gradient-accent);
            transform: translateY(-3px);
            box-shadow: 0 15px 35px -5px rgba(14, 165, 233, 0.5), 0 10px 15px -6px rgba(14, 165, 233, 0.4);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 15px;
            border: none;
            padding: 16px 20px;
            margin-bottom: 22px;
            font-weight: 500;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-md);
        }

        .alert i {
            font-size: 1.2rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626 !important;
            border-left: 5px solid #ef4444;
            font-weight: 600;
        }

        .alert-success {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #16a34a !important;
            border-left: 5px solid #22c55e;
            font-weight: 600;
        }

        .auth-footer {
            text-align: center;
            padding: 30px 40px;
            background: linear-gradient(135deg, var(--gray-50) 0%, #f8fafc 100%);
            border-top: 2px solid var(--gray-200);
            flex-shrink: 0;
        }

        .auth-footer p {
            margin-bottom: 16px;
            color: #1f2937 !important;
            font-weight: 600;
            font-size: 1.05rem;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary) !important;
            background: transparent;
            border-radius: 15px;
            padding: 13px 28px;
            font-weight: 600;
            text-decoration: none !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
            font-size: 1.05rem;
            letter-spacing: 0.3px;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: var(--white) !important;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(14, 165, 233, 0.4);
        }

        .btn-outline-primary i {
            font-size: 1.05rem;
        }

        .back-link {
            margin-top: 24px;
        }

        .back-link a {
            color: var(--gray-500);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 1.05rem;
            padding: 8px 12px;
            border-radius: var(--radius);
        }

        .back-link a:hover {
            color: var(--primary);
            background: rgba(14, 165, 233, 0.08);
            transform: translateX(-3px);
        }

        .back-link a i {
            transition: var(--transition);
        }

        .back-link a:hover i {
            transform: translateX(-3px);
        }

        /* Bootstrap utility classes */
        .mt-3 {
            margin-top: 24px !important;
        }

        .mb-2 {
            margin-bottom: 12px !important;
        }

        .me-2 {
            margin-right: 8px !important;
        }

        .me-1 {
            margin-right: 4px !important;
        }

        .text-muted {
            color: #1f2937 !important;
            font-weight: 500;
        }

        .text-decoration-none {
            text-decoration: none !important;
        }

        a.text-muted.text-decoration-none {
            color: #1f2937 !important;
            font-weight: 600 !important;
        }

        /* Footer home link specific styles */
        .auth-footer .mt-3 {
            display: block !important;
            margin-top: 18px !important;
        }

        .auth-footer .mt-3 a {
            color: #1f2937 !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            transition: var(--transition);
            display: inline-flex !important;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            padding: 8px 12px;
            border-radius: 12px;
        }

        .auth-footer .mt-3 a:hover {
            color: var(--primary) !important;
            background: rgba(14, 165, 233, 0.08);
            transform: translateX(-3px);
        }

        .auth-footer .mt-3 a i {
            transition: var(--transition);
            color: #1f2937 !important;
        }

        .auth-footer .mt-3 a:hover i {
            transform: translateX(-3px);
            color: var(--primary) !important;
        }

        /* Ensure footer div is visible */
        .auth-footer div {
            display: block !important;
        }

        .auth-footer div a {
            display: inline-flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-container {
                padding: 15px;
            }

            .auth-card {
                max-width: 100%;
            }

            .auth-header {
                padding: 25px 20px;
            }

            .auth-header h2 {
                font-size: 1.4rem;
            }

            .logo {
                font-size: 2rem;
            }

            .auth-body {
                padding: 25px 20px;
            }

            .auth-body.register-body {
                padding: 20px 15px;
            }

            .form-control,
            .input-group .form-control {
                padding: 10px 12px;
                font-size: 0.9rem;
            }

            .btn-primary {
                padding: 12px 20px;
                font-size: 1rem;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-row .form-group {
                margin-bottom: 12px;
            }
        }

        @media (max-width: 480px) {
            .auth-header {
                padding: 25px 15px;
            }

            .auth-body {
                padding: 25px 15px;
            }

            .auth-footer {
                padding: 25px 15px;
            }
        }

        /* Loading Animation */
        .loading {
            animation: pulse 2s infinite;
        }

        /* Focus visible for accessibility */
        .btn-primary:focus-visible,
        .btn-outline-primary:focus-visible,
        .form-control:focus-visible {
            outline: 3px solid rgba(14, 165, 233, 0.5);
            outline-offset: 2px;
        }

        /* File Upload Styles */
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 10px;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 25px;
            border: 3px dashed var(--gray-300);
            border-radius: 20px;
            background: linear-gradient(135deg, var(--gray-50) 0%, #f8fafc 100%);
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
        }

        .file-upload-label:hover {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.08) 0%, rgba(14, 165, 233, 0.05) 100%);
            transform: translateY(-2px);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.05), 0 4px 12px rgba(14, 165, 233, 0.15);
        }

        .file-upload-label i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
            filter: drop-shadow(0 2px 4px rgba(14, 165, 233, 0.3));
        }

        .file-text {
            color: #4b5563;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.2px;
        }

        .file-preview {
            position: relative;
            margin-top: 18px;
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--gray-50) 0%, #f8fafc 100%);
            border-radius: 20px;
            border: 2px solid var(--gray-200);
            box-shadow: var(--shadow-md);
        }

        .btn-remove-file {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--danger);
            color: white;
            border: none;
            border-radius: 12px;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
            font-size: 1.1rem;
        }

        .btn-remove-file:hover {
            background: #dc2626;
            transform: scale(1.15);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.5);
        }

        .form-text {
            display: block;
            margin-top: 8px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .text-muted {
            color: #6b7280 !important;
            font-weight: 500;
        }

        .form-text i {
            color: var(--info);
        }

        small.form-text {
            color: #6b7280;
        }

        /* Fix Font Awesome Icons */
        i.fa, i.fas, i.far, i.fab, i.fal, i.fad,
        .fa, .fas, .far, .fab, .fal, .fad {
            font-family: "Font Awesome 6 Free", "Font Awesome 6 Brands", "Font Awesome 5 Free", "Font Awesome 5 Brands", "FontAwesome" !important;
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            font-style: normal !important;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        i.fas, .fas {
            font-weight: 900 !important;
        }

        i.far, .far {
            font-weight: 400 !important;
        }

        i.fab, .fab {
            font-family: "Font Awesome 6 Brands", "Font Awesome 5 Brands" !important;
            font-weight: 400 !important;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card <?php echo $mode === 'register' ? 'register-mode' : ''; ?>">
            <div class="auth-header <?php echo $mode === 'register' ? 'compact' : ''; ?>">
                <div class="logo <?php echo $mode === 'register' ? 'compact' : ''; ?>">
                    <img src="image/logo.png.png" alt="KhamCare Logo" style="width: 80px; height: 80px; border-radius: 16px; object-fit: contain;">
                </div>
                <h2 class="<?php echo $mode === 'register' ? 'compact' : ''; ?>"><?php echo $mode === 'login' ? 'Đăng nhập' : 'Đăng ký'; ?></h2>
                <p><?php echo $mode === 'login' ? 'Dành cho bác sĩ' : 'Tạo tài khoản bác sĩ mới'; ?></p>
            </div>
            
            <div class="auth-body <?php echo $mode === 'register' ? 'register-body' : ''; ?>">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($mode === 'login'): ?>
                    <!-- Form đăng nhập -->
                    <form method="POST">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" name="email" required 
                                       placeholder="Nhập email của bạn" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Mật khẩu</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" name="password" required 
                                       placeholder="Nhập mật khẩu">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Đăng nhập
                        </button>
                    </form>
                    
                <?php else: ?>
                    <!-- Form đăng ký -->
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="register">
                        
                        <!-- Hàng 1: Họ tên và Email -->
                        <div class="form-row">
                            <div class="form-group compact">
                                <label class="form-label">Họ và tên</label>
                                <div class="input-group">
                                    <span class="input-group-text compact">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control compact" name="name" required 
                                           placeholder="Nhập họ và tên" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group compact">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text compact">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control compact" name="email" required 
                                           placeholder="Nhập email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hàng 2: Số điện thoại và Chuyên khoa -->
                        <div class="form-row">
                            <div class="form-group compact">
                                <label class="form-label">Số điện thoại</label>
                                <div class="input-group">
                                    <span class="input-group-text compact">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="tel" class="form-control compact" name="phone" required 
                                           placeholder="Số điện thoại" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group compact">
                                <label class="form-label">Chuyên khoa</label>
                                <div class="input-group">
                                    <span class="input-group-text compact">
                                        <i class="fas fa-stethoscope"></i>
                                    </span>
                                    <select class="form-control specialty-select compact" name="specialty" required>
                                        <option value="">Chọn chuyên khoa</option>
                                        <option value="Nội Tổng Quát" <?php echo ($_POST['specialty'] ?? '') === 'Nội Tổng Quát' ? 'selected' : ''; ?>>Nội Tổng Quát</option>
                                        <option value="Tim Mạch" <?php echo ($_POST['specialty'] ?? '') === 'Tim Mạch' ? 'selected' : ''; ?>>Tim Mạch</option>
                                        <option value="Thần Kinh" <?php echo ($_POST['specialty'] ?? '') === 'Thần Kinh' ? 'selected' : ''; ?>>Thần Kinh</option>
                                        <option value="Da Liễu" <?php echo ($_POST['specialty'] ?? '') === 'Da Liễu' ? 'selected' : ''; ?>>Da Liễu</option>
                                        <option value="Sản Phụ Khoa" <?php echo ($_POST['specialty'] ?? '') === 'Sản Phụ Khoa' ? 'selected' : ''; ?>>Sản Phụ Khoa</option>
                                        <option value="Nhi Khoa" <?php echo ($_POST['specialty'] ?? '') === 'Nhi Khoa' ? 'selected' : ''; ?>>Nhi Khoa</option>
                                        <option value="Mắt" <?php echo ($_POST['specialty'] ?? '') === 'Mắt' ? 'selected' : ''; ?>>Mắt</option>
                                        <option value="Tai Mũi Họng" <?php echo ($_POST['specialty'] ?? '') === 'Tai Mũi Họng' ? 'selected' : ''; ?>>Tai Mũi Họng</option>
                                        
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hàng 3: Upload chứng chỉ (full width) -->
                        <div class="form-group compact">
                            <label class="form-label">
                                <i class="fas fa-certificate me-2"></i>
                                Hình ảnh chứng chỉ hành nghề
                            </label>
                            <div class="file-upload-wrapper">
                                <input type="file" class="form-control file-input" id="license_file" name="license_file" 
                                       accept="image/*,.pdf" required>
                                <label for="license_file" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>
                                    <span class="file-text">Chọn file hoặc kéo thả vào đây</span>
                                </label>
                                <div class="file-preview" id="filePreview" style="display: none;">
                                    <img id="previewImage" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px;">
                                    <button type="button" class="btn-remove-file" onclick="removeFile()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Chấp nhận: JPG, PNG, PDF (Tối đa 5MB)
                            </small>
                        </div>
                        
                        <!-- Hàng 4: Mật khẩu và Xác nhận -->
                        <div class="form-row">
                            <div class="form-group compact">
                                <label class="form-label">Mật khẩu</label>
                                <div class="input-group">
                                    <span class="input-group-text compact">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control compact" name="password" required 
                                           placeholder="Mật khẩu (≥6 ký tự)">
                                </div>
                            </div>
                            
                            <div class="form-group compact">
                                <label class="form-label">Xác nhận</label>
                                <div class="input-group">
                                    <span class="input-group-text compact">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control compact" name="confirm_password" required 
                                           placeholder="Nhập lại mật khẩu">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
                            <i class="fas fa-user-plus me-2"></i>
                            Đăng ký
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="auth-footer">
                <?php if ($mode === 'login'): ?>
                    <p class="mb-2">Chưa có tài khoản?</p>
                    <a href="?mode=register" class="btn-outline-primary">
                        <i class="fas fa-user-plus me-2"></i>
                        Đăng ký ngay
                    </a>
                <?php else: ?>
                    <p class="mb-2">Đã có tài khoản?</p>
                    <a href="?mode=login" class="btn-outline-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Đăng nhập
                    </a>
                <?php endif; ?>
                
                <div class="mt-3">
                    <a href="TrangChu.php" class="text-muted text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>
                        Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // File upload handling
        const fileInput = document.getElementById('license_file');
        const filePreview = document.getElementById('filePreview');
        const previewImage = document.getElementById('previewImage');
        const fileLabel = document.querySelector('.file-upload-label');
        
        if (fileInput) {
            // Handle file selection
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    handleFile(file);
                }
            });
            
            // Handle drag and drop
            fileLabel.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = 'var(--primary)';
                this.style.background = 'rgba(14, 165, 233, 0.1)';
            });
            
            fileLabel.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = 'var(--gray-300)';
                this.style.background = 'var(--gray-50)';
            });
            
            fileLabel.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.style.borderColor = 'var(--gray-300)';
                this.style.background = 'var(--gray-50)';
                
                const file = e.dataTransfer.files[0];
                if (file) {
                    fileInput.files = e.dataTransfer.files;
                    handleFile(file);
                }
            });
        }
        
        function handleFile(file) {
            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File quá lớn! Vui lòng chọn file nhỏ hơn 5MB.');
                fileInput.value = '';
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!validTypes.includes(file.type)) {
                alert('Định dạng file không hợp lệ! Vui lòng chọn file JPG, PNG hoặc PDF.');
                fileInput.value = '';
                return;
            }
            
            // Show preview for images
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                    filePreview.style.display = 'block';
                    fileLabel.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else if (file.type === 'application/pdf') {
                // Show PDF icon for PDF files
                previewImage.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"%3E%3Cpath fill="%23dc2626" d="M181.9 256.1c-5-16-4.9-46.9-2-46.9 8.4 0 7.6 36.9 2 46.9zm-1.7 47.2c-7.7 20.2-17.3 43.3-28.4 62.7 18.3-7 39-17.2 62.9-21.9-12.7-9.6-24.9-23.4-34.5-40.8zM86.1 428.1c0 .8 13.2-5.4 34.9-40.2-6.7 6.3-29.1 24.5-34.9 40.2zM248 160h136v328c0 13.3-10.7 24-24 24H24c-13.3 0-24-10.7-24-24V24C0 10.7 10.7 0 24 0h200v136c0 13.2 10.8 24 24 24zm-8 171.8c-20-12.2-33.3-29-42.7-53.8 4.5-18.5 11.6-46.6 6.2-64.2-4.7-29.4-42.4-26.5-47.8-6.8-5 18.3-.4 44.1 8.1 77-11.6 27.6-28.7 64.6-40.8 85.8-.1 0-.1.1-.2.1-27.1 13.9-73.6 44.5-54.5 68 5.6 6.9 16 10 21.5 10 17.9 0 35.7-18 61.1-61.8 25.8-8.5 54.1-19.1 79-23.2 21.7 11.8 47.1 19.5 64 19.5 29.2 0 31.2-32 19.7-43.4-13.9-13.6-54.3-9.7-73.6-7.2zM377 105L279 7c-4.5-4.5-10.6-7-17-7h-6v128h128v-6.1c0-6.3-2.5-12.4-7-16.9zm-74.1 255.3c4.1-2.7-2.5-11.9-42.8-9 37.1 15.8 42.8 9 42.8 9z"/%3E%3C/svg%3E';
                previewImage.style.display = 'block';
                previewImage.style.maxHeight = '80px';
                filePreview.style.display = 'block';
                fileLabel.style.display = 'none';
            }
        }
        
        function removeFile() {
            fileInput.value = '';
            previewImage.src = '';
            previewImage.style.display = 'none';
            filePreview.style.display = 'none';
            fileLabel.style.display = 'flex';
        }
    </script>
</body>
</html>
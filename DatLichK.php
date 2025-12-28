<?php

session_start();

require_once __DIR__ . '/db_config.php';
require_once 'doctor_data_helper.php';
require_once 'doctor_id_mapping.php';

$conn = null;
try {
    $conn = getDBConnection();
} catch (Throwable $e) {
    $conn = false;
}

if (!$conn || !($conn instanceof PDO)) {
    http_response_code(500);
    echo "<div style='color:red;text-align:center;margin-top:40px'>❌ Lỗi kết nối cơ sở dữ liệu. Vui lòng kiểm tra db_config.php.</div>";
    exit;
}

// Lấy id bác sĩ từ query string (nếu có)
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : null;

// Lấy chuyên khoa gợi ý từ trang tư vấn triệu chứng
$suggested_specialty = isset($_GET['suggested_specialty']) ? $_GET['suggested_specialty'] : null;
$from_consultation = isset($_GET['from_consultation']) ? true : false;

// Lấy thông tin triệu chứng từ session (nếu có)
$consultation_symptoms = $_SESSION['consultation_symptom_names'] ?? [];
$consultation_additional = $_SESSION['consultation_additional'] ?? '';

// Convert View ID (1001-1018) sang Database ID (1-18) nếu cần
if ($doctor_id) {
    $doctor_id = normalizeToDatabaseId($doctor_id);
}

// Lấy 1 bác sĩ nếu truyền doctor_id, ngược lại lấy danh sách bác sĩ
$doctor = null;
$doctors = [];
if ($doctor_id) {
    $stmt = $conn->prepare("SELECT b.*, b.ho_ten as full_name, c.ten AS specialty_name, c.id AS specialty_id 
                           FROM bac_si b 
                           LEFT JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id 
                           WHERE b.id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($doctor) {
        // Sử dụng phi_kham từ database
        $doctor['price'] = $doctor['phi_kham'] ?? 300000;
        $doctor['consultation_fee'] = $doctor['phi_kham'] ?? 300000;
    }
} 

// nếu không có $doctor lấy danh sách tất cả bác sĩ để select
if (!$doctor) {
    // Sử dụng helper để lấy 18 bác sĩ giống view_doctor.php
    $doctors = getTopDoctorsFromViewDoctor(18);
}

// Kiểm tra bệnh nhân đã đăng nhập
$patient_id = null;

// Ưu tiên sử dụng user_id từ session chính (khi đăng nhập qua TaiKhoan.php)
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'patient') {
    $patient_id = $_SESSION['user_id'];
} 
// Fallback: kiểm tra patient_id cũ (để tương thích)
elseif (isset($_SESSION['patient_id'])) {
    $patient_id = $_SESSION['patient_id'];
}

// Nếu vẫn chưa có patient_id, tạo user tạm thời
if (!$patient_id) {
    // Tạo user tạm thời cho demo
    $tempUserStmt = $conn->prepare("INSERT INTO nguoi_dung (ten_dang_nhap, email, mat_khau, ho_ten, so_dien_thoai, vai_tro) VALUES (?, ?, ?, ?, ?, 'benh_nhan')");
    $tempUsername = 'temp_' . time();
    $tempEmail = $tempUsername . '@temp.com';
    $tempUserStmt->execute([$tempUsername, $tempEmail, password_hash('temp123', PASSWORD_DEFAULT), 'Bệnh nhân tạm thời', '0123456789']);
    $patient_id = $conn->lastInsertId();
    $_SESSION['patient_id'] = $patient_id;
}

$message = "";
$bookingSuccess = false;
$createdAppointmentId = null;

// Tạo dữ liệu tuần (7 ngày)
$startDate = new DateTime('today');
$days = [];
for ($i = 0; $i < 7; $i++) {
    $dt = (clone $startDate)->modify("+{$i} day");
    $days[] = [
        'date' => $dt->format('Y-m-d'),
        'label' => $dt->format('d/m/Y')
    ];
}

// danh sách khung giờ
$timeSlots = [
    '07:00' => '7h - 8h Sáng',
    '08:00' => '8h - 9h Sáng',
    '09:00' => '9h - 10h Sáng',
    '10:00' => '10h - 11h Sáng',
    '13:00' => '13h - 14h Chiều',
    '14:00' => '14h - 15h Chiều',
    '15:00' => '15h - 16h Chiều',
    '16:00' => '16h - 17h Chiều',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedDoctor = intval($_POST['doctor_id'] ?? $doctor_id);
    // Convert View ID (1001-1018) sang Database ID (1-18) nếu cần
    $selectedDoctor = normalizeToDatabaseId($selectedDoctor);
    
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $consultation_type = in_array($_POST['consultation_type'] ?? 'offline', ['offline','online']) ? $_POST['consultation_type'] : 'offline';
    $notes = trim($_POST['notes'] ?? '');

    if (!$selectedDoctor || !$appointment_date || !$appointment_time) {
        $message = "<div class='alert alert-danger text-center'>❌ Vui lòng chọn đầy đủ bác sĩ, ngày và giờ khám.</div>";
    } else {
        // Kiểm tra bác sĩ có tồn tại trong database không
        $doctorCheck = $conn->prepare("SELECT id FROM bac_si WHERE id = ?");
        $doctorCheck->execute([$selectedDoctor]);
        if (!$doctorCheck->fetch()) {
            $message = "<div class='alert alert-danger text-center'>❌ Bác sĩ không tồn tại trong hệ thống (ID: $selectedDoctor). Vui lòng chạy SQL thêm bác sĩ.</div>";
        } else {
        // Kiểm tra patient_id có tồn tại không và lấy tên
        $patientCheck = $conn->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = ? AND vai_tro = 'benh_nhan'");
        $patientCheck->execute([$patient_id]);
        $patientData = $patientCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$patientData) {
            $message = "<div class='alert alert-danger text-center'>❌ Lỗi xác thực người dùng. Vui lòng thử lại.</div>";
        } else {
        $patient_name = $patientData['ho_ten'];
        
        // kiểm tra trùng
        $loai_tu_van = $consultation_type === 'online' ? 'truc_tuyen' : 'truc_tiep';
        $check = $conn->prepare("SELECT COUNT(*) FROM lich_hen WHERE bac_si_id = ? AND ngay_hen = ? AND gio_hen = ?");
        $check->execute([$selectedDoctor, $appointment_date, $appointment_time]);
        $count = (int)$check->fetchColumn();

        if ($count > 0) {
            $message = "<div class='alert alert-danger text-center'>❌ Bác sĩ đã có lịch vào khung giờ này. Vui lòng chọn giờ khác.</div>";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO lich_hen
                  (bac_si_id, benh_nhan_id, ten_benh_nhan, ngay_hen, gio_hen, loai_tu_van, ghi_chu, trang_thai)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'cho_xac_nhan')
            ");
            $ok = $stmt->execute([$selectedDoctor, $patient_id, $patient_name, $appointment_date, $appointment_time, $loai_tu_van, $notes]);

            if ($ok) {
                $createdAppointmentId = (int)$conn->lastInsertId();
                
                // Lấy thông tin bác sĩ để tính phí
                $doctorStmt = $conn->prepare("SELECT phi_kham as consultation_fee, ho_ten FROM bac_si WHERE id = ?");
                $doctorStmt->execute([$selectedDoctor]);
                $doctorInfo = $doctorStmt->fetch(PDO::FETCH_ASSOC);
                $consultationFee = $doctorInfo ? $doctorInfo['consultation_fee'] : 300000;
                $doctorName = $doctorInfo ? $doctorInfo['ho_ten'] : 'Bác sĩ';
                
                // Cập nhật tong_phi trong lich_hen
                $updateStmt = $conn->prepare("UPDATE lich_hen SET tong_phi = ? WHERE id = ?");
                $updateStmt->execute([$consultationFee, $createdAppointmentId]);
                
                // Lưu thông tin lịch hẹn vào session
                $_SESSION['temp_appointment_id'] = $createdAppointmentId;
                $_SESSION['temp_doctor_id'] = $selectedDoctor;
                $_SESSION['temp_consultation_fee'] = $consultationFee;
                
                // Đặt lịch thành công
                $bookingSuccess = true;
                $message = "<div class='alert alert-success text-center' style='border-radius: 15px; padding: 20px;'>
                    <h4 style='margin-bottom: 15px;'>✅ Đặt lịch khám thành công!</h4>
                    <p><strong>Mã lịch hẹn:</strong> #" . $createdAppointmentId . "</p>
                    <p><strong>Bác sĩ:</strong> " . htmlspecialchars($doctorName) . "</p>
                    <p><strong>Ngày khám:</strong> " . date('d/m/Y', strtotime($appointment_date)) . " - " . $appointment_time . "</p>
                    <hr style='margin: 15px 0;'>
                    <div style='background: #fff3cd; padding: 15px; border-radius: 10px; color: #856404;'>
                        <i class='fas fa-clock'></i> <strong>Trạng thái: CHỜ XÁC NHẬN</strong><br>
                        <small>Lịch hẹn của bạn đang chờ bác sĩ xác nhận. Bạn sẽ nhận được thông báo khi bác sĩ chấp nhận lịch hẹn.</small>
                    </div>
                </div>";
            } else {
                $message = "<div class='alert alert-danger text-center'>❌ Có lỗi xảy ra khi đặt lịch. Vui lòng thử lại.</div>";
            }
        }
        }
        }
    }
}

// Hàm lấy giá khám theo doctor_id
function getDoctorPrice($conn, $doctor_id) {
    try {
        $stmt = $conn->prepare("SELECT phi_kham FROM bac_si WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['phi_kham'] : 300000; // Giá mặc định nếu không tìm thấy
    } catch (PDOException $e) {
        // Trả về giá mặc định nếu có lỗi
        return 300000;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt Lịch Khám Bệnh - KhamCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css">
    <link rel="stylesheet" href="global-font-override.css">
    <link rel="stylesheet" href="square-icons-override.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        
        

        :root {
            /* Modern Color Palette */
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #3730a3;
            --secondary: #f8fafc;
            --accent: #06b6d4;
            --accent-light: #67e8f9;
            --success: #10b981;
            --success-light: #34d399;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            
            /* Text Colors */
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --text-light: #cbd5e1;
            
            /* Background Colors */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --bg-gradient: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #06b6d4 100%);
            
            /* Border Colors */
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --border-focus: #4f46e5;
            
            /* Shadows */
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            /* Border Radius */
            --radius-sm: 6px;
            --radius: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-2xl: 24px;
            --radius-full: 9999px;
            
            /* Transitions */
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Spacing */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space: 1rem;
            --space-md: 1.5rem;
            --space-lg: 2rem;
            --space-xl: 3rem;
            --space-2xl: 4rem;
        }

        /* Reset & Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-gradient);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(14, 116, 144, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(34, 211, 238, 0.3) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        /* Main Background Container */
        .main-bg {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: var(--space-lg) var(--space);
        }

        /* Booking Card */
        .booking-card {
            background: var(--bg-primary);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-2xl);
            max-width: 1200px;
            width: 100%;
            margin: 0 auto var(--space-lg);
            padding: var(--space-xl) 50px;
            position: relative;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .booking-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 50%, var(--success) 100%);
        }

        .booking-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, transparent 100%);
            pointer-events: none;
        }

        /* Header Section */
        .booking-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
            position: relative;
            z-index: 1;
        }

        .booking-header .btn-icon {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            border-radius: var(--radius-md);
            padding: var(--space-sm) var(--space-md);
            color: var(--primary);
            border: 1px solid var(--border);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .booking-header .btn-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(79, 70, 229, 0.1), transparent);
            transition: left 0.5s;
        }

        .booking-header .btn-icon:hover::before {
            left: 100%;
        }

        .booking-header .btn-icon:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary);
        }

        .booking-header .btn-icon i {
            font-size: 1rem;
        }

        /* Logo Link */
        /* Logo Styles */
        .logo-link {
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
            padding: 8px 16px;
            border-radius: var(--radius-md);
        }

        .logo-link span {
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

        .logo-link:hover {
            transform: scale(1.02);
        }

        .logo-link:hover span {
            animation-duration: 1.5s;
        }

        .logo-link img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            border-radius: 18px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: logoFloat 3s ease-in-out infinite, logoPulse 2s ease-in-out infinite;
        }

        .logo-link:hover img {
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

        /* Form Title */
        .form-title {
            margin: 0;
            flex: 1;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.025em;
        }

        .form-title i {
            margin-right: var(--space-sm);
            color: var(--primary);
        }

        /* Language Switcher */
        .lang-switcher {
            display: inline-flex;
            align-items: center;
            position: relative;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: var(--radius-md);
            padding: 2px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }

        .lang-switcher:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .lang-switcher::before {
            content: '🌐';
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.9rem;
            z-index: 2;
            pointer-events: none;
        }

        .lang-switcher select {
            font-family: inherit;
            padding: var(--space-sm) 30px var(--space-sm) 28px;
            border-radius: var(--radius);
            border: none;
            background: rgba(255, 255, 255, 0.95);
            color: var(--text-primary);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            appearance: none;
            min-width: 120px;
        }

        .lang-switcher select:hover {
            background: rgba(255, 255, 255, 1);
            color: var(--primary);
        }

        .lang-switcher select:focus {
            outline: none;
            background: rgba(255, 255, 255, 1);
            color: var(--primary);
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.2);
        }

        .lang-switcher::after {
            content: "▼";
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.6rem;
            pointer-events: none;
            z-index: 2;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-md);
            position: relative;
            z-index: 1;
        }

        /* Form Groups */
        .form-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: var(--space-xs);
            font-weight: 700;
            color: var(--text-primary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }

        .form-group label i {
            color: var(--primary);
            font-size: 0.95rem;
            width: 16px;
            text-align: center;
        }

        /* Form Inputs */
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: var(--space);
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            background: var(--bg-secondary);
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text-primary);
            transition: var(--transition);
            position: relative;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--border-focus);
            background: var(--bg-primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            transform: translateY(-1px);
        }

        .form-group input:hover,
        .form-group select:hover,
        .form-group textarea:hover {
            border-color: var(--primary);
            background: var(--bg-primary);
        }

        .form-group input[readonly] {
            background: var(--bg-tertiary);
            color: var(--text-muted);
            cursor: not-allowed;
        }

        /* Styling cho trường phí khám */
        #consultation_fee {
            background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%) !important;
            border: 2px solid #ef4444 !important;
            color: #dc2626 !important;
            font-weight: bold !important;
            font-size: 1.1rem !important;
            text-align: center !important;
        }

        #consultation_fee:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }

        /* Select Styling */
        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }

        /* Controls */
        .controls {
            display: flex;
            gap: var(--space);
            flex-wrap: wrap;
            margin-top: var(--space-md);
            justify-content: center;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space) var(--space-lg);
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            min-width: 160px;
            justify-content: center;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn i {
            font-size: 1.1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--success) 0%, var(--primary) 100%);
            color: white;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .btn-outline {
            background: var(--bg-primary);
            color: var(--primary);
            border: 2px solid var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        /* Payment Button */
        .btn[style*="background:#28a745"] {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-light) 100%) !important;
            color: white !important;
            box-shadow: var(--shadow) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            transition: var(--transition) !important;
        }

        .btn[style*="background:#28a745"]:hover {
            transform: translateY(-3px) !important;
            box-shadow: var(--shadow-lg) !important;
        }

        /* Specialty Choice Buttons */
        .specialty-choice-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-choice {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius);
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-choice:hover {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.05);
        }

        .btn-choice.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);
            color: var(--primary);
        }

        .btn-choice-known {
            border-color: var(--success);
        }

        .btn-choice-known:hover,
        .btn-choice-known.active {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(52, 211, 153, 0.1) 100%);
            border-color: var(--success);
            color: var(--success);
        }

        .btn-choice-consult {
            border-color: var(--warning);
        }

        .btn-choice-consult:hover,
        .btn-choice-consult.active {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(251, 191, 36, 0.1) 100%);
            border-color: var(--warning);
            color: #b45309;
        }

        .btn-choice i {
            font-size: 1rem;
        }

        /* Note */
        .note {
            margin-top: var(--space);
            color: var(--text-secondary);
            font-size: 0.85rem;
            line-height: 1.5;
            padding: var(--space) var(--space) var(--space) var(--space-xl);
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--info);
            position: relative;
        }

        .note::before {
            content: '💡';
            position: absolute;
            left: var(--space-sm);
            top: var(--space-sm);
            font-size: 1rem;
        }

        /* Alerts */
        .alert {
            padding: var(--space-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-md);
            font-size: 0.95rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            box-shadow: var(--shadow-sm);
            border: 1px solid transparent;
        }

        .alert::before {
            font-size: 1.2rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            color: #065f46;
            border-color: #a7f3d0;
        }

        .alert-success::before {
            content: '✅';
        }

        .alert-danger {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            border-color: #fca5a5;
        }

        .alert-danger::before {
            content: '❌';
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .booking-card {
            animation: fadeInUp 0.8s ease-out;
        }

        .booking-header {
            animation: slideInLeft 0.6s ease-out 0.2s both;
        }

        .form-group:nth-child(1) { animation: fadeInUp 0.6s ease-out 0.3s both; }
        .form-group:nth-child(2) { animation: fadeInUp 0.6s ease-out 0.4s both; }
        .form-group:nth-child(3) { animation: fadeInUp 0.6s ease-out 0.5s both; }
        .form-group:nth-child(4) { animation: fadeInUp 0.6s ease-out 0.6s both; }
        .form-group:nth-child(5) { animation: fadeInUp 0.6s ease-out 0.7s both; }
        .form-group:nth-child(6) { animation: fadeInUp 0.6s ease-out 0.8s both; }

        .controls {
            animation: slideInRight 0.6s ease-out 0.9s both;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .booking-card {
                padding: var(--space-lg) 40px;
            }
            
            .form-title {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 768px) {
            .main-bg {
                padding: var(--space) var(--space-sm);
            }
            
            .booking-card {
                padding: var(--space-md) 25px;
            }
            
            .booking-header {
                flex-direction: column;
                gap: var(--space-sm);
                text-align: center;
            }
            
            .booking-header .form-title {
                order: 2;
                font-size: 1.5rem;
            }
            
            .logo-link {
                order: 1;
            }
            
            .logo-text {
                font-size: 1.4rem;
            }
            
            .logo-img {
                width: 45px;
                height: 45px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: var(--space);
            }
            
            .controls {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .booking-card {
                padding: var(--space) 15px;
            }
            
            .form-title {
                font-size: 1.3rem;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: var(--space-sm);
                font-size: 0.9rem;
            }
        }

        /* Compact date/time controls on wide screens */
        @media (min-width: 769px) {
            #appointment_date {
                max-width: 280px;
            }
            
            #appointment_time {
                max-width: 350px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --text-primary: #f1f5f9;
                --text-secondary: #cbd5e1;
                --text-muted: #94a3b8;
                --bg-primary: #1e293b;
                --bg-secondary: #334155;
                --bg-tertiary: #475569;
                --border: #475569;
                --border-light: #334155;
            }
        }

        /* Print styles */
        @media print {
            .logo-link,
            .lang-switcher,
            .controls {
                display: none;
            }
            
            .booking-card {
                box-shadow: none;
                border: 1px solid #ccc;
            }
        }
        
        @media (max-width: 480px) {
            .logo-text {
                font-size: 1.3rem;
            }
            
            .logo-img {
                width: 40px;
                height: 40px;
            }
            
            body {
                background: white;
            }
        }

        /* Focus styles for accessibility */
        .btn:focus-visible,
        .form-group input:focus-visible,
        .form-group select:focus-visible,
        .form-group textarea:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Loading state */
        .btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn.loading::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 12px;
            animation: spin 1s linear infinite;
            margin-left: var(--space-sm);
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>
<body>
    <div class="main-bg">
        <div class="booking-card">

            <!-- Header with logo and language switcher -->
            <div class="booking-header">
                <a href="TrangChu.php" class="logo-link" aria-label="Về trang chủ KhamCare">
                    <img src="image\logo.png.png" alt="Logo">
                    <span>KhamCare</span>
                </a>

                <h2 class="form-title"><i class="fas fa-calendar-alt"></i> <span id="bookingTitle">Đặt Lịch Khám Bệnh</span></h2>

                <div style="display: flex; align-items: center; gap: 15px;">
                    <!-- Language Switcher -->
                    <div class="lang-switcher" title="Chọn ngôn ngữ / ជ្រើសរើសភាសា">
                        <select id="langSelect" aria-label="Chọn ngôn ngữ / ជ្រើសរើសភាសា" role="combobox">
                            <option value="vi" data-flag="🇻🇳">Tiếng Việt</option>
                            <option value="km" data-flag="🇰🇭">ភាសាខ្មែរ</option>
                        </select>
                    </div>
                </div>
            </div>

            <?php if ($message) echo $message; ?>

            <?php if ($from_consultation && $suggested_specialty): ?>
            <!-- Hiển thị gợi ý từ trang tư vấn triệu chứng -->
            <div class="consultation-suggestion" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 2px solid #10b981; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                <h4 style="color: #065f46; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-lightbulb" style="color: #10b981;"></i> Gợi ý từ phân tích triệu chứng
                </h4>
                <p style="color: #047857; margin-bottom: 10px;">Dựa trên triệu chứng của bạn, chúng tôi đề xuất:</p>
                <span style="display: inline-block; background: #10b981; color: white; padding: 8px 20px; border-radius: 20px; font-weight: 600; margin-bottom: 10px;">
                    🩺 Chuyên khoa: <?php echo htmlspecialchars($suggested_specialty); ?>
                </span>
                <?php if (!empty($consultation_symptoms)): ?>
                <p style="color: #047857; font-size: 0.9rem; margin-top: 10px;">
                    <strong>Triệu chứng đã chọn:</strong> <?php echo htmlspecialchars(implode(', ', $consultation_symptoms)); ?>
                </p>
                <?php endif; ?>
                <p style="color: #6b7280; font-size: 0.85rem; margin-top: 10px;">
                    <i class="fas fa-info-circle"></i> Vui lòng chọn bác sĩ thuộc chuyên khoa được gợi ý bên dưới
                </p>
            </div>
            <?php endif; ?>

            <form class="form-grid" method="post" action="">
                <div class="form-group">
                    <label for="doctor_id" id="labelSelectDoctor"><i class="fas fa-user-md"></i> Chọn Bác Sĩ:</label>
                    <select id="doctor_id" name="doctor_id" required>
                        <?php if ($doctor): ?>
                            <option value="<?= htmlspecialchars($doctor['id']) ?>">Dr. <?= htmlspecialchars($doctor['full_name']) ?> - <?= htmlspecialchars($doctor['specialty_name'] ?? '') ?></option>
                        <?php else: ?>
                            <option value="" id="optionSelectDoctor">-- Chọn bác sĩ --</option>
                            <?php 
                            if (!empty($doctors)) {
                                foreach ($doctors as $doc): 
                                    // Lấy ID từ doctor_row_id hoặc id
                                    $rawId = $doc['doctor_row_id'] ?? $doc['id']; 
                                    // Đảm bảo luôn là Database ID (1-18)
                                    $databaseId = normalizeToDatabaseId($rawId);
                                    // Map sang View ID để hiển thị
                                    $viewId = mapDatabaseIdToViewId($databaseId);
                                    $fullName = $doc['full_name'] ?? 'Unknown';
                                    // Ưu tiên lấy specialty_name, sau đó title, specialty
                                    $specialty = $doc['specialty_name'] ?? $doc['title'] ?? $doc['specialty'] ?? 'Chuyên khoa';
                                    $fee = $doc['consultation_fee'] ?? 300000;
                                    $rating = $doc['rating'] ?? 4.5;
                                    
                                    // Kiểm tra xem bác sĩ có thuộc chuyên khoa gợi ý không
                                    $isRecommended = $suggested_specialty && stripos($specialty, $suggested_specialty) !== false;
                            ?>
                                <option value="<?= htmlspecialchars($databaseId) ?>" 
                                        data-fee="<?= $fee ?>"
                                        data-specialty="<?= htmlspecialchars($specialty) ?>"
                                        data-rating="<?= $rating ?>"
                                        data-view-id="<?= $viewId ?>"
                                        data-recommended="<?= $isRecommended ? '1' : '0' ?>"
                                        <?= $isRecommended ? 'style="background-color: #ecfdf5; font-weight: bold;"' : '' ?>>
                                    <?= $isRecommended ? '⭐ [Gợi ý] ' : '' ?>Dr. <?= htmlspecialchars($fullName) ?> - <?= htmlspecialchars($specialty) ?> 
                                    (<?= number_format($fee, 0, ',', '.') ?>đ) 
                                    ⭐<?= number_format($rating, 1) ?>
                                </option>
                            <?php 
                                endforeach;
                            } else {
                                echo '<option value="" id="optionNoDoctors">Không có bác sĩ nào</option>';
                            }
                            ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="doctor_name" id="labelSelectedDoctor"><i class="fas fa-user-md"></i> Bác Sĩ Đã Chọn:</label>
                    <input type="text" id="doctor_name" name="doctor_name" value="<?= htmlspecialchars($doctor['full_name'] ?? '') ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="doctor_specialty" id="labelSpecialty"><i class="fas fa-stethoscope"></i> Chuyên Khoa:</label>
                    <input type="text" id="doctor_specialty" name="doctor_specialty" value="<?= htmlspecialchars($doctor['specialty_name'] ?? $doctor['title'] ?? '') ?>" readonly placeholder="Chọn bác sĩ để xem chuyên khoa" style="font-weight: 600; color: #6366f1;">
                    
                    <!-- Nút chọn hình thức đặt lịch -->
                    <div class="specialty-choice-buttons" style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="button" class="btn-choice btn-choice-known" onclick="showKnownDoctorMode()" id="btnKnownDoctor">
                            <i class="fas fa-user-md"></i> Đã biết bác sĩ
                        </button>
                        <button type="button" class="btn-choice btn-choice-consult" onclick="showConsultationMode()" id="btnNeedConsult">
                            <i class="fas fa-question-circle"></i> Cần tư vấn
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="consultation_fee" id="labelConsultationFee"><i class="fas fa-money-bill-wave"></i> Phí Khám:</label>
                    <input type="text" id="consultation_fee" name="consultation_fee" value="<?= $doctor ? number_format($doctor['price'] ?? 300000, 0, ',', '.') . 'đ' : '' ?>" readonly style="color: #e44d26; font-weight: bold;">
                </div>

                <div class="form-group">
                    <label for="doctor_rating" id="labelRating"><i class="fas fa-star"></i> Đánh Giá:</label>
                    <input type="text" id="doctor_rating" name="doctor_rating" readonly>
                </div>

                <div class="form-group">
                    <label for="appointment_date" id="labelAppointmentDate"><i class="fas fa-calendar-day"></i> Ngày Khám:</label>
                    <input type="date" id="appointment_date" name="appointment_date" required min="<?= date('Y-m-d') ?>">
                </div>

                <div class="form-group">
                    <label for="appointment_time" id="labelAppointmentTime"><i class="fas fa-clock"></i> Thời Gian:</label>
                    <select id="appointment_time" name="appointment_time" required>
                        <option value="" id="optionSelectTime">-- Chọn giờ khám --</option>
                        <?php foreach ($timeSlots as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="consultation_type" id="labelConsultationType"><i class="fas fa-notes-medical"></i> Hình Thức Khám:</label>
                    <select id="consultation_type" name="consultation_type" required>
                        <option value="offline" id="optionOffline">Khám trực tiếp tại phòng khám</option>
                        <option value="online" id="optionOnline">Tư vấn Online (Video call)</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="notes" id="labelNotes"><i class="fas fa-comment-medical"></i> Triệu Chứng / Ghi chú:</label>
                    <textarea id="notes" name="notes" rows="2" placeholder="Mô tả ngắn gọn về tình trạng của bạn hoặc ghi chú cho bác sĩ..."><?php 
                        // Tự động điền triệu chứng từ trang tư vấn
                        if ($from_consultation && !empty($consultation_symptoms)) {
                            echo "Triệu chứng: " . htmlspecialchars(implode(', ', $consultation_symptoms));
                            if (!empty($consultation_additional)) {
                                echo "\nMô tả thêm: " . htmlspecialchars($consultation_additional);
                            }
                        }
                    ?></textarea>
                </div>

                <div class="controls full-width">
                    <button type="submit" class="btn btn-primary" id="btnBookAppointment"><i class="fas fa-calendar-check"></i> Đặt Lịch Khám</button>
                </div>

                <div class="note full-width" id="bookingNote">Chú ý: Sau khi đặt lịch thành công, bạn có thể cập nhật hồ sơ cá nhân và thực hiện thanh toán.</div>

            </form>

            <?php if ($bookingSuccess && $createdAppointmentId): ?>
                <?php 
                // Lấy thông tin appointment với thông tin bác sĩ (sử dụng bảng tiếng Việt)
                $appointmentStmt = $conn->prepare("
                    SELECT lh.*, 
                           b.ho_ten as doctor_name, 
                           c.ten as specialty_name,
                           b.phi_kham as consultation_fee
                    FROM lich_hen lh
                    JOIN bac_si b ON lh.bac_si_id = b.id
                    LEFT JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id
                    WHERE lh.id = ?
                ");
                $appointmentStmt->execute([$createdAppointmentId]);
                $appointmentInfo = $appointmentStmt->fetch(PDO::FETCH_ASSOC);
                if ($appointmentInfo): 
                ?>
                <div style="margin-top:20px;text-align:center;display:flex;gap:15px;justify-content:center;flex-wrap:wrap">
                    <a href="update_profile.php?step=profile&appointment_id=<?= $createdAppointmentId ?>" class="btn btn-primary" id="btnUpdateProfile" style="display:inline-flex;align-items:center;gap:8px;padding:14px 24px;border-radius:12px;text-decoration:none;font-weight:700;background:linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);color:#fff;box-shadow:0 4px 12px rgba(99,102,241,0.3);transition:all 0.3s">
                        <i class="fas fa-user-edit"></i> Cập nhật hồ sơ
                    </a>
                    <a href="payment.php?appointment_id=<?= $createdAppointmentId ?>&order_id=APPT<?= $createdAppointmentId ?>&doctor_name=<?= urlencode($appointmentInfo['doctor_name']) ?>&specialty=<?= urlencode($appointmentInfo['specialty_name']) ?>&amount=<?= $appointmentInfo['consultation_fee'] ?>" class="btn btn-success" style="display:inline-flex;align-items:center;gap:8px;padding:14px 24px;border-radius:12px;text-decoration:none;font-weight:700;background:linear-gradient(135deg, #10b981 0%, #059669 100%);color:#fff;box-shadow:0 4px 12px rgba(16,185,129,0.3);transition:all 0.3s">
                        <i class="fas fa-credit-card"></i> <span id="btnPaymentText">Thanh Toán</span> <?= number_format($appointmentInfo['consultation_fee'], 0, ',', '.') ?>đ
                    </a>
                </div>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>

<script>
// Hàm xử lý khi bấm nút "Đã biết bác sĩ"
function showKnownDoctorMode() {
    // Đánh dấu nút active
    document.getElementById('btnKnownDoctor').classList.add('active');
    document.getElementById('btnNeedConsult').classList.remove('active');
    
    // Focus vào dropdown chọn bác sĩ
    var doctorSelect = document.getElementById('doctor_id');
    if (doctorSelect) {
        doctorSelect.focus();
        // Hiệu ứng highlight
        doctorSelect.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.3)';
        doctorSelect.style.borderColor = '#10b981';
        setTimeout(function() {
            doctorSelect.style.boxShadow = '';
            doctorSelect.style.borderColor = '';
        }, 2000);
    }
}

// Hàm xử lý khi bấm nút "Cần tư vấn"
function showConsultationMode() {
    // Đánh dấu nút active
    document.getElementById('btnNeedConsult').classList.add('active');
    document.getElementById('btnKnownDoctor').classList.remove('active');
    
    // Chuyển đến trang tư vấn triệu chứng
    window.location.href = 'symptom_consultation.php';
}

document.addEventListener('DOMContentLoaded', function(){
    // Cập nhật trường bác sĩ đã chọn khi chọn trong select
    var doctorSelect = document.getElementById('doctor_id');
    var doctorName = document.getElementById('doctor_name');
    var doctorSpecialty = document.getElementById('doctor_specialty');
    var doctorRating = document.getElementById('doctor_rating');
    var consultationFee = document.getElementById('consultation_fee');
    
    doctorSelect && doctorSelect.addEventListener('change', function(){
        var opt = doctorSelect.options[doctorSelect.selectedIndex];
        console.log('🔍 Bác sĩ được chọn:', opt);
        
        if (opt && opt.value) {
            // Cập nhật tên bác sĩ
            var fullText = opt.text;
            var doctorNameOnly = fullText.split(' - ')[0].replace(/^Dr\.\s*/, '');
            if (doctorName) {
                doctorName.value = doctorNameOnly;
                console.log('✅ Tên bác sĩ:', doctorNameOnly);
            }
            
            // Cập nhật chuyên khoa - ƯU TIÊN data-specialty
            var specialty = opt.getAttribute('data-specialty');
            console.log('🏥 Chuyên khoa từ data-specialty:', specialty);
            
            if (doctorSpecialty) {
                if (specialty && specialty.trim() !== '') {
                    // Sử dụng chuyên khoa từ data-specialty
                    doctorSpecialty.value = specialty;
                    doctorSpecialty.style.fontWeight = '600';
                    doctorSpecialty.style.color = '#6366f1';
                    console.log('✅ Chuyên khoa đã cập nhật:', specialty);
                } else {
                    // Fallback: lấy từ text của option
                    var parts = fullText.split(' - ');
                    if (parts.length > 1) {
                        var specialtyFromText = parts[1].split('(')[0].trim();
                        doctorSpecialty.value = specialtyFromText;
                        doctorSpecialty.style.fontWeight = '600';
                        doctorSpecialty.style.color = '#6366f1';
                        console.log('✅ Chuyên khoa từ text:', specialtyFromText);
                    } else {
                        doctorSpecialty.value = 'Chưa có thông tin';
                        doctorSpecialty.style.color = '#94a3b8';
                        console.log('⚠️ Không tìm thấy chuyên khoa');
                    }
                }
            }
            
            // Cập nhật đánh giá
            var rating = opt.getAttribute('data-rating');
            if (doctorRating && rating) {
                doctorRating.value = '⭐ ' + parseFloat(rating).toFixed(1) + '/5.0';
                console.log('✅ Đánh giá:', rating);
            }
            
            // Cập nhật phí khám
            var fee = opt.getAttribute('data-fee');
            console.log('💰 Phí khám từ data-fee:', fee);
            
            if (fee && consultationFee) {
                var feeNumber = parseInt(fee);
                console.log('💵 Số tiền:', feeNumber);
                
                if (feeNumber > 0) {
                    consultationFee.value = feeNumber.toLocaleString('vi-VN') + 'đ';
                    consultationFee.style.color = '#e44d26';
                    consultationFee.style.fontWeight = 'bold';
                    console.log('✅ Phí khám:', feeNumber.toLocaleString('vi-VN') + 'đ');
                } else {
                    consultationFee.value = 'Liên hệ để biết giá';
                    consultationFee.style.color = '#6b7280';
                    consultationFee.style.fontWeight = 'normal';
                    console.log('⚠️ Phí khám = 0');
                }
            } else {
                console.log('❌ Không có dữ liệu phí khám');
            }
        } else {
            // Reset tất cả các trường khi không chọn bác sĩ
            if (doctorName) doctorName.value = '';
            if (doctorSpecialty) {
                doctorSpecialty.value = '';
                doctorSpecialty.placeholder = 'Chọn bác sĩ để xem chuyên khoa';
            }
            if (doctorRating) doctorRating.value = '';
            if (consultationFee) consultationFee.value = '';
        }
    });
    
    // Trigger change event nếu đã có bác sĩ được chọn sẵn
    if (doctorSelect && doctorSelect.value) {
        doctorSelect.dispatchEvent(new Event('change'));
    }
});
</script>

<!-- Global Language System -->
<script>
console.log('🚀 DatLichK: Initializing...');

// Helper function to update label with icon
function updateLabelWithIcon(labelId, text, iconClass) {
    const label = document.getElementById(labelId);
    if (label && text) {
        label.innerHTML = `<i class="${iconClass}"></i> ${text}`;
        console.log(`✅ Updated ${labelId}: ${text}`);
    } else {
        console.warn(`⚠️ Could not update ${labelId}`);
    }
}

// Helper function to update button with icon
function updateButtonWithIcon(btnId, text, iconClass) {
    const btn = document.getElementById(btnId);
    if (btn && text) {
        btn.innerHTML = `<i class="${iconClass}"></i> ${text}`;
        console.log(`✅ Updated button ${btnId}: ${text}`);
    }
}

// Function to update all page elements
function updatePageLanguage(lang, dict) {
        console.log('🌐 DatLichK: Updating language to', lang, 'with dict:', dict);

        if (!dict) {
            console.error('❌ Dictionary not found!');
            return;
        }
        
        // Update page title
        if (dict.bookingPageTitle) {
            document.title = dict.bookingPageTitle;
        }
        
        // Update booking title
        const bookingTitle = document.getElementById('bookingTitle');
        if (bookingTitle && dict.bookingTitle) {
            bookingTitle.textContent = dict.bookingTitle;
        }
        
        // Update form labels with icons (only if dict has the property)
        if (dict.selectDoctor) updateLabelWithIcon('labelSelectDoctor', dict.selectDoctor, 'fas fa-user-md');
        if (dict.selectedDoctor) updateLabelWithIcon('labelSelectedDoctor', dict.selectedDoctor, 'fas fa-user-md');
        if (dict.specialty) updateLabelWithIcon('labelSpecialty', dict.specialty, 'fas fa-stethoscope');
        if (dict.consultationFee) updateLabelWithIcon('labelConsultationFee', dict.consultationFee, 'fas fa-money-bill-wave');
        if (dict.rating) updateLabelWithIcon('labelRating', dict.rating, 'fas fa-star');
        if (dict.appointmentDate) updateLabelWithIcon('labelAppointmentDate', dict.appointmentDate, 'fas fa-calendar-day');
        if (dict.appointmentTime) updateLabelWithIcon('labelAppointmentTime', dict.appointmentTime, 'fas fa-clock');
        if (dict.consultationType) updateLabelWithIcon('labelConsultationType', dict.consultationType, 'fas fa-notes-medical');
        if (dict.notes) updateLabelWithIcon('labelNotes', dict.notes, 'fas fa-comment-medical');
        
        // Update placeholders
        const optionSelectDoctor = document.getElementById('optionSelectDoctor');
        if (optionSelectDoctor && dict.selectDoctorPlaceholder) {
            optionSelectDoctor.textContent = dict.selectDoctorPlaceholder;
        }
        
        const optionNoDoctors = document.getElementById('optionNoDoctors');
        if (optionNoDoctors && dict.noDoctors) {
            optionNoDoctors.textContent = dict.noDoctors;
        }
        
        const inputSpecialty = document.getElementById('doctor_specialty');
        if (inputSpecialty && dict.specialtyPlaceholder) {
            inputSpecialty.placeholder = dict.specialtyPlaceholder;
        }
        
        const optionSelectTime = document.getElementById('optionSelectTime');
        if (optionSelectTime && dict.selectTimePlaceholder) {
            optionSelectTime.textContent = dict.selectTimePlaceholder;
        }
        
        const textareaNotes = document.getElementById('notes');
        if (textareaNotes && dict.notesPlaceholder) {
            textareaNotes.placeholder = dict.notesPlaceholder;
        }
        
        // Update consultation type options
        const optionOffline = document.getElementById('optionOffline');
        if (optionOffline && dict.offlineConsultation) {
            optionOffline.textContent = dict.offlineConsultation;
        }
        
        const optionOnline = document.getElementById('optionOnline');
        if (optionOnline && dict.onlineConsultationOption) {
            optionOnline.textContent = dict.onlineConsultationOption;
        }
        
        // Update buttons with icons
        if (dict.bookAppointmentBtn) {
            updateButtonWithIcon('btnBookAppointment', dict.bookAppointmentBtn, 'fas fa-calendar-check');
        }
        if (dict.updateProfile) {
            updateButtonWithIcon('btnUpdateProfile', dict.updateProfile, 'fas fa-user-edit');
        }
        
        // Update payment button text only (keep the price)
        const btnPaymentText = document.getElementById('btnPaymentText');
        if (btnPaymentText && dict.payment) {
            btnPaymentText.textContent = dict.payment;
        }
        
        // Update booking note
        const bookingNote = document.getElementById('bookingNote');
        if (bookingNote && dict.bookingNote) {
            bookingNote.textContent = dict.bookingNote;
        }
        
        // Update time slots
        const timeSelect = document.getElementById('appointment_time');
        if (timeSelect && dict.timeSlots) {
            // Save current selected value
            const currentValue = timeSelect.value;
            
            // Update all time slot options (skip first option which is placeholder)
            const options = timeSelect.querySelectorAll('option:not(:first-child)');
            options.forEach(option => {
                const timeValue = option.value;
                if (dict.timeSlots[timeValue]) {
                    option.textContent = dict.timeSlots[timeValue];
                }
            });
            
            // Restore selected value
            timeSelect.value = currentValue;
        }
        
        console.log('✅ DatLichK: Language updated successfully');
    }

// Listen for language changes and update page-specific elements
document.addEventListener('languageChanged', function(event) {
    const { lang, dict } = event.detail;
    console.log('🌐 languageChanged event received!', lang);
    updatePageLanguage(lang, dict);
});

// Force update on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DatLichK: Page loaded, forcing language update...');
    
    // Try multiple times to ensure GLOBAL_DICT is loaded
    let attempts = 0;
    const maxAttempts = 10;
    
    function tryUpdateLanguage() {
        attempts++;
        console.log(`🔄 Attempt ${attempts}/${maxAttempts} to load language...`);
        
        const currentLang = localStorage.getItem('kham_lang') || 'vi';
        console.log('📝 Current language from localStorage:', currentLang);
        
        // Get dictionary from global scope
        if (typeof GLOBAL_DICT !== 'undefined') {
            const dict = GLOBAL_DICT[currentLang];
            console.log('📚 Dictionary loaded:', dict ? 'Yes' : 'No');
            
            if (dict) {
                console.log('� Sampsle keys:', Object.keys(dict).slice(0, 10));
                console.log('🔍 selectDoctor:', dict.selectDoctor);
                console.log('🔍 selectedDoctor:', dict.selectedDoctor);
                console.log('🔍 specialty:', dict.specialty);
                console.log('🔍 consultationFee:', dict.consultationFee);
                updatePageLanguage(currentLang, dict);
            } else {
                console.error('❌ Dictionary is undefined for lang:', currentLang);
            }
        } else {
            console.warn(`⚠️ GLOBAL_DICT not found yet (attempt ${attempts}/${maxAttempts})`);
            
            // Try again if not max attempts
            if (attempts < maxAttempts) {
                setTimeout(tryUpdateLanguage, 100);
            } else {
                console.error('❌ Failed to load GLOBAL_DICT after', maxAttempts, 'attempts');
            }
        }
    }
    
    // Start trying
    tryUpdateLanguage();
});
</script>

<!-- Inline Language System - Simple & Direct -->
<script>
// Simple inline translations - no external dependencies
const LANG_DICT = {
    vi: {
        bookingTitle: "Đặt Lịch Khám Bệnh",
        selectDoctor: "Chọn Bác Sĩ:",
        selectDoctorPlaceholder: "-- Chọn bác sĩ --",
        selectedDoctor: "Bác Sĩ Đã Chọn:",
        specialty: "Chuyên Khoa:",
        specialtyPlaceholder: "Chọn bác sĩ để xem chuyên khoa",
        consultationFee: "Phí Khám:",
        rating: "Đánh Giá:",
        appointmentDate: "Ngày Khám:",
        appointmentTime: "Thời Gian:",
        selectTimePlaceholder: "-- Chọn giờ khám --",
        consultationType: "Hình Thức Khám:",
        offlineConsultation: "Khám trực tiếp tại phòng khám",
        onlineConsultation: "Tư vấn Online (Video call)",
        notes: "Triệu Chứng / Ghi chú:",
        notesPlaceholder: "Mô tả ngắn gọn về tình trạng của bạn hoặc ghi chú cho bác sĩ...",
        bookAppointmentBtn: "Đặt Lịch Khám",
        bookingNote: "Chú ý: Sau khi đặt lịch thành công, bạn có thể cập nhật hồ sơ cá nhân và thực hiện thanh toán.",
        timeSlots: {
            "07:00": "7h - 8h Sáng",
            "08:00": "8h - 9h Sáng",
            "09:00": "9h - 10h Sáng",
            "10:00": "10h - 11h Sáng",
            "13:00": "13h - 14h Chiều",
            "14:00": "14h - 15h Chiều",
            "15:00": "15h - 16h Chiều",
            "16:00": "16h - 17h Chiều"
        }
    },
    km: {
        bookingTitle: "កក់ពេលវេលាពិនិត្យជំងឺ",
        selectDoctor: "ជ្រើសរើសវេជ្ជបណ្ឌិត:",
        selectDoctorPlaceholder: "-- ជ្រើសរើសវេជ្ជបណ្ឌិត --",
        selectedDoctor: "វេជ្ជបណ្ឌិតដែលបានជ្រើសរើស:",
        specialty: "ជំនាញ:",
        specialtyPlaceholder: "ជ្រើសរើសវេជ្ជបណ្ឌិតដើម្បីមើលជំនាញ",
        consultationFee: "ថ្លៃពិនិត្យ:",
        rating: "ការវាយតម្លៃ:",
        appointmentDate: "កាលបរិច្ឆេទពិនិត្យ:",
        appointmentTime: "ពេលវេលា:",
        selectTimePlaceholder: "-- ជ្រើសរើសម៉ោងពិនិត្យ --",
        consultationType: "ប្រភេទការពិនិត្យ:",
        offlineConsultation: "ពិនិត្យដោយផ្ទាល់នៅគ្លីនិក",
        onlineConsultation: "ប្រឹក្សាអនឡាញ (ហៅវីដេអូ)",
        notes: "រោគសញ្ញា / កំណត់ចំណាំ:",
        notesPlaceholder: "ពិពណ៌នាសង្ខេបអំពីស្ថានភាពរបស់អ្នក ឬកំណត់ចំណាំសម្រាប់វេជ្ជបណ្ឌិត...",
        bookAppointmentBtn: "កក់ពេលវេលាពិនិត្យ",
        bookingNote: "ចំណាំ: បន្ទាប់ពីកក់ពេលវេលាបានជោគជ័យ អ្នកអាចកែប្រែព័ត៌មានផ្ទាល់ខ្លួន និងធ្វើការទូទាត់។",
        timeSlots: {
            "07:00": "7:00 - 8:00 ព្រឹក",
            "08:00": "8:00 - 9:00 ព្រឹក",
            "09:00": "9:00 - 10:00 ព្រឹក",
            "10:00": "10:00 - 11:00 ព្រឹក",
            "13:00": "13:00 - 14:00 រសៀល",
            "14:00": "14:00 - 15:00 រសៀល",
            "15:00": "15:00 - 16:00 រសៀល",
            "16:00": "16:00 - 17:00 រសៀល"
        }
    }
};

// Apply language to page
function applyLanguageDirect(lang) {
    console.log('🌐 Applying language:', lang);
    const dict = LANG_DICT[lang];
    if (!dict) return;

    // Update title
    document.title = dict.bookingTitle;
    const titleEl = document.getElementById('bookingTitle');
    if (titleEl) titleEl.textContent = dict.bookingTitle;

    // Update all labels with icons
    const labels = {
        'labelSelectDoctor': { text: dict.selectDoctor, icon: 'fas fa-user-md' },
        'labelSelectedDoctor': { text: dict.selectedDoctor, icon: 'fas fa-user-md' },
        'labelSpecialty': { text: dict.specialty, icon: 'fas fa-stethoscope' },
        'labelConsultationFee': { text: dict.consultationFee, icon: 'fas fa-money-bill-wave' },
        'labelRating': { text: dict.rating, icon: 'fas fa-star' },
        'labelAppointmentDate': { text: dict.appointmentDate, icon: 'fas fa-calendar-day' },
        'labelAppointmentTime': { text: dict.appointmentTime, icon: 'fas fa-clock' },
        'labelConsultationType': { text: dict.consultationType, icon: 'fas fa-notes-medical' },
        'labelNotes': { text: dict.notes, icon: 'fas fa-comment-medical' }
    };

    Object.keys(labels).forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.innerHTML = `<i class="${labels[id].icon}"></i> ${labels[id].text}`;
        }
    });

    // Update options
    const opts = {
        'optionSelectDoctor': dict.selectDoctorPlaceholder,
        'optionNoDoctors': dict.noDoctors || 'Không có bác sĩ nào',
        'optionSelectTime': dict.selectTimePlaceholder,
        'optionOffline': dict.offlineConsultation,
        'optionOnline': dict.onlineConsultation
    };

    Object.keys(opts).forEach(id => {
        const el = document.getElementById(id);
        if (el && opts[id]) el.textContent = opts[id];
    });

    // Update placeholders
    const ph = document.getElementById('doctor_specialty');
    if (ph) ph.placeholder = dict.specialtyPlaceholder;
    
    const notes = document.getElementById('notes');
    if (notes) notes.placeholder = dict.notesPlaceholder;

    // Update button
    const btn = document.getElementById('btnBookAppointment');
    if (btn) btn.innerHTML = `<i class="fas fa-calendar-check"></i> ${dict.bookAppointmentBtn}`;

    // Update note
    const note = document.getElementById('bookingNote');
    if (note) note.textContent = dict.bookingNote;

    // Update time slots
    const timeSelect = document.getElementById('appointment_time');
    if (timeSelect && dict.timeSlots) {
        const currentValue = timeSelect.value;
        const options = timeSelect.querySelectorAll('option:not(:first-child)');
        options.forEach(opt => {
            const timeValue = opt.value;
            if (dict.timeSlots[timeValue]) {
                opt.textContent = dict.timeSlots[timeValue];
            }
        });
        timeSelect.value = currentValue;
    }

    // Save to localStorage
    localStorage.setItem('kham_lang', lang);
    console.log('✅ Language applied successfully!');
}

// Setup language selector
const langSelect = document.getElementById('langSelect');
if (langSelect) {
    // Load saved language
    const savedLang = localStorage.getItem('kham_lang') || 'vi';
    langSelect.value = savedLang;
    
    // Apply on change
    langSelect.addEventListener('change', function() {
        applyLanguageDirect(this.value);
    });
    
    // Apply on page load
    if (savedLang === 'km') {
        setTimeout(() => applyLanguageDirect('km'), 100);
    }
}

console.log('✅ Inline language system loaded');
</script>

<!-- Chatbot AI Widget -->
<?php include 'chatbot_widget.php'; ?>

</body>
</html>
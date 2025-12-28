<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Lấy thông tin từ request
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $consultation_type = $_POST['type'] ?? 'voice'; // voice hoặc video
    $symptoms = $_POST['symptoms'] ?? '';
    
    if ($doctor_id <= 0) {
        throw new Exception('Doctor ID không hợp lệ');
    }
    
    // Validate consultation_type
    if (!in_array($consultation_type, ['voice', 'video', 'chat'])) {
        $consultation_type = 'voice';
    }
    
    // Bước 1: Tạo appointment tạm thời
    // Lấy tên bệnh nhân
    $stmt = $conn->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = ? AND vai_tro = 'benh_nhan'");
    $stmt->execute([$user_id]);
    $patient = $stmt->fetch();
    $patient_name = $patient ? $patient['ho_ten'] : 'Bệnh nhân';
    
    $appointment_date = date('Y-m-d');
    $appointment_time = date('H:i:s');
    
    $stmt = $conn->prepare("
        INSERT INTO lich_hen 
        (benh_nhan_id, ten_benh_nhan, bac_si_id, ngay_hen, gio_hen, loai_tu_van, trang_thai, ghi_chu, ngay_tao)
        VALUES (?, ?, ?, ?, ?, 'truc_tuyen', 'da_xac_nhan', ?, NOW())
    ");
    
    $notes = "Tư vấn trực tuyến - " . ($consultation_type === 'video' ? 'Video call' : 'Voice call');
    if ($symptoms) {
        $notes .= "\nTriệu chứng: " . $symptoms;
    }
    
    $stmt->execute([$user_id, $patient_name, $doctor_id, $appointment_date, $appointment_time, $notes]);
    $appointment_id = $conn->lastInsertId();
    
    if (!$appointment_id) {
        throw new Exception('Không thể tạo appointment');
    }
    
    // Bước 2: Tạo consultation - dùng bảng tu_van
    $loai_tu_van = $consultation_type; // chat, video, voice
    $stmt = $conn->prepare("
        INSERT INTO tu_van 
        (lich_hen_id, benh_nhan_id, bac_si_id, loai_tu_van, thoi_gian_bat_dau, trang_thai, ngay_tao)
        VALUES (?, ?, ?, ?, NOW(), 'dang_hoat_dong', NOW())
    ");
    
    $stmt->execute([$appointment_id, $user_id, $doctor_id, $loai_tu_van]);
    $consultation_id = $conn->lastInsertId();
    
    if (!$consultation_id) {
        throw new Exception('Không thể tạo consultation');
    }
    
    // Lấy thông tin bác sĩ
    $stmt = $conn->prepare("
        SELECT id, ho_ten as full_name, chuyen_khoa_id as specialty, nam_kinh_nghiem as experience_years, danh_gia as rating
        FROM bac_si
        WHERE id = ?
    ");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã tạo cuộc gọi thành công',
        'data' => [
            'consultation_id' => $consultation_id,
            'appointment_id' => $appointment_id,
            'consultation_type' => $consultation_type,
            'start_time' => time() * 1000, // milliseconds
            'doctor' => $doctor
        ]
    ]);
    
} catch (Exception $e) {
    error_log("[api_create_consultation] Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

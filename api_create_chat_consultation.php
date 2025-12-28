<?php
// Tắt tất cả output trước JSON
error_reporting(0);
ini_set('display_errors', 0);
if (ob_get_level()) ob_end_clean();

session_start();

// Set header trước khi có bất kỳ output nào
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$patient_id = $_SESSION['user_id'];

try {
    // Kết nối database trực tiếp
    $pdo = new PDO(
        'mysql:host=localhost;dbname=khamcare_database;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Lấy thông tin bệnh nhân
    $stmt = $pdo->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = ? LIMIT 1");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    $patient_name = $patient ? $patient['ho_ten'] : 'Bệnh nhân #' . $patient_id;
    
    // Tạo consultation - dùng bảng tu_van với đầy đủ thông tin
    // lich_hen_id, thoi_gian_ket_thuc, thoi_luong_phut để NULL vì:
    // - lich_hen_id: Chỉ có khi đặt lịch khám
    // - thoi_gian_ket_thuc: Chỉ có khi phiên kết thúc
    // - thoi_luong_phut: Tự động tính khi kết thúc
    $stmt = $pdo->prepare("
        INSERT INTO tu_van 
        (benh_nhan_id, loai_tu_van, thoi_gian_bat_dau, trang_thai, tom_tat, ghi_chu_bac_si, phan_hoi_benh_nhan, link_ghi_am, ngay_tao, ngay_cap_nhat)
        VALUES (?, 'chat', NOW(), 'dang_hoat_dong', ?, ?, ?, '', NOW(), NOW())
    ");
    
    $tomTat = 'Phiên tư vấn của ' . $patient_name;
    $ghiChuBacSi = 'Tư vấn trực tuyến qua chat';
    $phanHoiBenhNhan = 'Đang chờ phản hồi';
    
    $stmt->execute([$patient_id, $tomTat, $ghiChuBacSi, $phanHoiBenhNhan]);
    $consultation_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'consultation_id' => $consultation_id,
        'patient_id' => $patient_id,
        'consultation_type' => 'chat',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>

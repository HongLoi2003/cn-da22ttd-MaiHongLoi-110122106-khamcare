<?php
/**
 * API Test - Kiểm tra get_patient_profile
 */
session_start();
require_once 'db_config.php';

header('Content-Type: application/json; charset=utf-8');

// Lấy patient_id từ POST hoặc GET
$patientId = (int)($_POST['patient_id'] ?? $_GET['patient_id'] ?? 0);

if ($patientId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu patient_id', 'debug' => 'Truyền ?patient_id=X hoặc POST patient_id']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Không thể kết nối database');
    }
    
    // Query 1: Lấy thông tin từ nguoi_dung
    $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
    $stmt->execute([$patientId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        // Fallback: lấy từ lich_hen
        $stmt = $pdo->prepare("SELECT DISTINCT ten_benh_nhan as ho_ten, benh_nhan_id as id FROM lich_hen WHERE benh_nhan_id = ? LIMIT 1");
        $stmt->execute([$patientId]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$patient) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy bệnh nhân với ID: ' . $patientId]);
            exit;
        }
        
        $patient['email'] = '';
        $patient['so_dien_thoai'] = '';
        $patient['ngay_sinh'] = null;
        $patient['gioi_tinh'] = null;
        $patient['dia_chi'] = '';
        $patient['from_lich_hen'] = true;
    }
    
    // Query 2: Lấy profile (nếu có)
    $profile = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM ho_so_nguoi_dung WHERE nguoi_dung_id = ?");
        $stmt->execute([$patientId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Bảng không tồn tại
    }
    
    $patient['profile'] = $profile;
    
    // Query 3: Đếm lịch hẹn
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_visits FROM lich_hen WHERE benh_nhan_id = ?");
    $stmt->execute([$patientId]);
    $visitStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $patient,
        'visit_stats' => $visitStats,
        'appointments' => [],
        'medical_records' => [],
        'debug' => [
            'patient_id' => $patientId,
            'found_in' => isset($patient['from_lich_hen']) ? 'lich_hen' : 'nguoi_dung'
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

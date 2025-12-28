<?php
/**
 * API lấy chi tiết hồ sơ bệnh án
 */

session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('Không thể kết nối database');
    }
    
    $record_id = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;
    
    if ($record_id <= 0) {
        throw new Exception('ID hồ sơ không hợp lệ');
    }
    
    // Lấy thông tin hồ sơ
    $stmt = $pdo->prepare("
        SELECT mr.*, 
               a.appointment_date, a.appointment_time,
               d.id as doctor_id,
               u.full_name as doctor_name,
               s.name as specialty_name
        FROM medical_records mr
        JOIN appointments a ON mr.appointment_id = a.id
        JOIN doctors d ON mr.doctor_id = d.id
        JOIN users u ON d.user_id = u.id
        LEFT JOIN specialties s ON d.specialty_id = s.id
        WHERE mr.id = ?
        LIMIT 1
    ");
    $stmt->execute([$record_id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        throw new Exception('Không tìm thấy hồ sơ');
    }
    
    // Kiểm tra quyền truy cập
    $user_role = strtolower($_SESSION['role']);
    
    if ($user_role === 'patient') {
        // Bệnh nhân chỉ xem hồ sơ của mình
        if ($record['patient_id'] != $_SESSION['user_id']) {
            throw new Exception('Bạn không có quyền xem hồ sơ này');
        }
    } elseif ($user_role === 'doctor') {
        // Bác sĩ xem hồ sơ của mình tạo
        $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $doctor = $stmt->fetch();
        
        if (!$doctor || $record['doctor_id'] != $doctor['id']) {
            throw new Exception('Bạn không có quyền xem hồ sơ này');
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $record
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

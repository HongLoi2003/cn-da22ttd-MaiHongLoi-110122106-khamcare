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
    $consultation_id = intval($_POST['consultation_id'] ?? 0);
    $summary = $_POST['summary'] ?? '';
    $patient_feedback = $_POST['feedback'] ?? '';
    
    if ($consultation_id <= 0) {
        throw new Exception('Consultation ID không hợp lệ');
    }
    
    // Lấy thông tin consultation
    $stmt = $conn->prepare("
        SELECT c.*, a.user_id, a.doctor_id, c.start_time
        FROM consultations c
        JOIN appointments a ON c.appointment_id = a.id
        WHERE c.id = ? AND a.user_id = ?
    ");
    $stmt->execute([$consultation_id, $user_id]);
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consultation) {
        throw new Exception('Không tìm thấy consultation hoặc bạn không có quyền');
    }
    
    // Tính thời gian cuộc gọi (phút)
    $start_time = strtotime($consultation['start_time']);
    $end_time = time();
    $duration_minutes = round(($end_time - $start_time) / 60);
    
    // Cập nhật consultation
    $stmt = $conn->prepare("
        UPDATE consultations
        SET end_time = NOW(),
            duration_minutes = ?,
            status = 'completed',
            summary = ?,
            patient_feedback = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$duration_minutes, $summary, $patient_feedback, $consultation_id]);
    
    // Cập nhật appointment status
    $stmt = $conn->prepare("
        UPDATE appointments
        SET status = 'completed',
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$consultation['appointment_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã kết thúc cuộc gọi',
        'data' => [
            'consultation_id' => $consultation_id,
            'duration_minutes' => $duration_minutes,
            'duration_text' => sprintf('%02d:%02d:%02d', 
                floor($duration_minutes / 60), 
                $duration_minutes % 60, 
                0
            )
        ]
    ]);
    
} catch (Exception $e) {
    error_log("[api_end_consultation] Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

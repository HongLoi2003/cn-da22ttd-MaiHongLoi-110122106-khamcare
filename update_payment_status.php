<?php
/**
 * API cập nhật trạng thái thanh toán cho lịch hẹn
 */

header('Content-Type: application/json; charset=utf-8');

// Đọc dữ liệu JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

$appointment_id = (int)($input['appointment_id'] ?? 0);
$payment_status = $input['payment_status'] ?? 'da_thanh_toan';
$amount = (float)($input['amount'] ?? 0);

if ($appointment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu appointment_id']);
    exit;
}

require_once 'db_config.php';

try {
    $pdo = getDBConnection();
    
    if (!$pdo) {
        throw new Exception('Không thể kết nối database');
    }
    
    // Cập nhật trạng thái thanh toán
    $stmt = $pdo->prepare("UPDATE lich_hen SET trang_thai_thanh_toan = ?, tong_phi = ? WHERE id = ?");
    $stmt->execute([$payment_status, $amount, $appointment_id]);
    
    // Nếu đã thanh toán, cập nhật trạng thái lịch hẹn thành đã xác nhận (nếu đang chờ)
    if ($payment_status === 'da_thanh_toan') {
        $stmt = $pdo->prepare("UPDATE lich_hen SET trang_thai = 'da_xac_nhan' WHERE id = ? AND trang_thai = 'cho_xac_nhan'");
        $stmt->execute([$appointment_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật thanh toán thành công']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}

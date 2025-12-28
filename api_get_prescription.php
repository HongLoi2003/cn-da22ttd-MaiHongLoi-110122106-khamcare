<?php
/**
 * API Lấy Đơn Thuốc
 */
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $appointment_id = $_GET['appointment_id'] ?? null;
    
    if (!$appointment_id) {
        echo json_encode(['success' => false, 'message' => 'Missing appointment_id']);
        exit;
    }
    
    // Lấy thông tin đơn thuốc
    $stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $prescription = $stmt->fetch();
    
    if (!$prescription) {
        echo json_encode(['success' => true, 'prescription' => null, 'medicines' => []]);
        exit;
    }
    
    // Lấy danh sách thuốc
    $medicineStmt = $pdo->prepare("SELECT * FROM prescription_items WHERE prescription_id = ?");
    $medicineStmt->execute([$prescription['id']]);
    $medicines = $medicineStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'prescription' => $prescription,
        'medicines' => $medicines
    ]);
    
} catch (Exception $e) {
    error_log("Get prescription error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>

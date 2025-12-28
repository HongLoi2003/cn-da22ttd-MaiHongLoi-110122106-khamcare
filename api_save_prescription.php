<?php
/**
 * API Lưu Đơn Thuốc
 */
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập và role doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $appointment_id = $data['appointment_id'] ?? null;
    $patient_id = $data['patient_id'] ?? null;
    $diagnosis = $data['diagnosis'] ?? '';
    $notes = $data['notes'] ?? '';
    $medicines = $data['medicines'] ?? [];
    
    if (!$appointment_id || !$patient_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Kiểm tra xem đã có đơn thuốc chưa
    $checkStmt = $pdo->prepare("SELECT id FROM prescriptions WHERE appointment_id = ?");
    $checkStmt->execute([$appointment_id]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        // Update existing prescription
        $prescription_id = $existing['id'];
        $updateStmt = $pdo->prepare("UPDATE prescriptions SET diagnosis = ?, notes = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$diagnosis, $notes, $prescription_id]);
        
        // Xóa các thuốc cũ
        $deleteStmt = $pdo->prepare("DELETE FROM prescription_items WHERE prescription_id = ?");
        $deleteStmt->execute([$prescription_id]);
    } else {
        // Insert new prescription
        $insertStmt = $pdo->prepare("INSERT INTO prescriptions (appointment_id, patient_id, doctor_id, diagnosis, notes) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute([$appointment_id, $patient_id, $_SESSION['user_id'], $diagnosis, $notes]);
        $prescription_id = $pdo->lastInsertId();
    }
    
    // Insert medicines
    if (!empty($medicines)) {
        $medicineStmt = $pdo->prepare("INSERT INTO prescription_items (prescription_id, medicine_name, dosage, frequency, duration, quantity, instructions) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($medicines as $medicine) {
            $medicineStmt->execute([
                $prescription_id,
                $medicine['name'] ?? '',
                $medicine['dosage'] ?? '',
                $medicine['frequency'] ?? '',
                $medicine['duration'] ?? '',
                $medicine['quantity'] ?? 1,
                $medicine['instructions'] ?? ''
            ]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Đơn thuốc đã được lưu thành công',
        'prescription_id' => $prescription_id
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Save prescription error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>

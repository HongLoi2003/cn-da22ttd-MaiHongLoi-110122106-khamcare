<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Chưa đăng nhập'
    ]);
    exit;
}

// Lấy consultation_id từ GET
$consultation_id = isset($_GET['consultation_id']) ? intval($_GET['consultation_id']) : 0;

if ($consultation_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'consultation_id không hợp lệ'
    ]);
    exit;
}

try {
    // Kiểm tra consultation có thuộc về user này không
    $stmt = $pdo->prepare("
        SELECT id, patient_id, status 
        FROM consultations 
        WHERE id = ? AND patient_id = ?
    ");
    $stmt->execute([$consultation_id, $_SESSION['user_id']]);
    $consultation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$consultation) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy consultation hoặc bạn không có quyền truy cập'
        ]);
        exit;
    }
    
    // Lấy danh sách tin nhắn với sender_name
    $stmt = $pdo->prepare("
        SELECT 
            id,
            sender_id,
            sender_type,
            sender_name,
            content as message,
            message_type,
            is_read,
            created_at
        FROM messages
        WHERE consultation_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$consultation_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Đảm bảo sender_name không null
    foreach ($messages as &$msg) {
        if (empty($msg['sender_name'])) {
            $msg['sender_name'] = 'Unknown User';
        }
    }
    
    echo json_encode([
        'success' => true,
        'consultation_id' => $consultation_id,
        'status' => $consultation['status'],
        'message_count' => count($messages),
        'messages' => $messages
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi database: ' . $e->getMessage()
    ]);
}
?>

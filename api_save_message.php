<?php
// Tắt output trước JSON
error_reporting(0);
ini_set('display_errors', 0);
if (ob_get_level()) ob_end_clean();

session_start();

header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập'
    ]);
    exit;
}

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
    
    // Lấy thông tin từ request
    $consultation_id = intval($_POST['consultation_id'] ?? 0);
    $sender_id = intval($_SESSION['user_id']);
    $sender_type = trim($_POST['sender'] ?? 'patient');
    $message = trim($_POST['message'] ?? '');
    
    if ($consultation_id <= 0) {
        throw new Exception('Consultation ID không hợp lệ');
    }
    
    if (empty($message)) {
        throw new Exception('Nội dung tin nhắn không được rỗng');
    }
    
    // Lấy tên người gửi từ bảng nguoi_dung
    $stmt = $pdo->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = ? LIMIT 1");
    $stmt->execute([$sender_id]);
    $user = $stmt->fetch();
    $sender_name = $user ? $user['ho_ten'] : 'Người dùng #' . $sender_id;
    
    // Lưu tin nhắn vào bảng tin_nhan (bao gồm tên người gửi)
    $stmt = $pdo->prepare("
        INSERT INTO tin_nhan 
        (tu_van_id, nguoi_gui_id, ten_nguoi_gui, noi_dung, loai_tin_nhan, da_doc, ngay_tao)
        VALUES (?, ?, ?, ?, 'van_ban', 0, NOW())
    ");
    $stmt->execute([$consultation_id, $sender_id, $sender_name, $message]);
    $message_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'consultation_id' => $consultation_id,
        'sender_id' => $sender_id,
        'sender_name' => $sender_name
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'consultation_id' => $consultation_id ?? null,
            'sender_id' => $sender_id ?? null,
            'message_length' => strlen($message ?? '')
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

<?php
session_start();
require_once __DIR__ . '/db_config.php';
header('Content-Type: application/json');

$pdo = getDBConnection();
if (!$pdo || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

$consultation_id = intval($_POST['consultation_id'] ?? 0);
$sender_id = $_SESSION['user_id'] ?? null;
$sender_role = $_SESSION['role'] ?? 'patient';
$message = trim($_POST['message'] ?? '');

if (!$sender_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if (!$consultation_id) {
    echo json_encode(['success' => false, 'message' => 'Consultation ID is missing']);
    exit;
}

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Message is empty']);
    exit;
}

// Xử lý file đính kèm (nếu có)
$attachmentPath = null;
if (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $u = $_FILES['file'];
    $ext = pathinfo($u['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (!in_array(strtolower($ext), $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }
    $dir = __DIR__ . '/uploads/consultations/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $fname = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (move_uploaded_file($u['tmp_name'], $dir . $fname)) {
        $attachmentPath = 'uploads/consultations/' . $fname;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO consultation_messages (consultation_id, sender_id, sender_role, message, attachment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$consultation_id, $sender_id, $sender_role, $message ?: null, $attachmentPath]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'attachment' => $attachmentPath]);
} catch (PDOException $e) {
    error_log('[send_message] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

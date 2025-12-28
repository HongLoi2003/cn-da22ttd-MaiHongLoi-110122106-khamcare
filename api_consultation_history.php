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
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    $conn = getDBConnection();
    
    // Log request để debug
    error_log("[api_consultation_history] Action: $action, User ID: $user_id");
    
    switch ($action) {
        case 'save':
            // Lưu phiên tư vấn
            $session_id = $_POST['session_id'] ?? '';
            $language = $_POST['language'] ?? 'vi';
            $summary = $_POST['summary'] ?? '';
            $messages = $_POST['messages'] ?? '[]';
            
            if (empty($session_id) || empty($messages)) {
                throw new Exception('Thiếu thông tin phiên tư vấn');
            }
            
            // Lấy tên người dùng
            $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_name = $user ? $user['full_name'] : 'Người dùng';
            
            // Kiểm tra xem session_id đã tồn tại chưa (PDO)
            $stmt = $conn->prepare("SELECT id FROM consultation_history WHERE session_id = ? AND user_id = ?");
            $stmt->execute([$session_id, $user_id]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Cập nhật phiên hiện có
                $stmt = $conn->prepare("
                    UPDATE consultation_history 
                    SET messages = ?, summary = ?, language = ?, user_name = ?, updated_at = NOW()
                    WHERE session_id = ? AND user_id = ?
                ");
                $stmt->execute([$messages, $summary, $language, $user_name, $session_id, $user_id]);
            } else {
                // Tạo phiên mới
                $stmt = $conn->prepare("
                    INSERT INTO consultation_history (user_id, user_name, session_id, language, summary, messages)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $user_name, $session_id, $language, $summary, $messages]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã lưu lịch sử tư vấn'
            ]);
            break;
            
        case 'list':
            // Lấy danh sách phiên tư vấn (PDO)
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
            
            $stmt = $conn->prepare("
                SELECT id, session_id, user_name, language, summary, created_at, updated_at
                FROM consultation_history
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$user_id, $limit]);
            
            $sessions = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $sessions[] = [
                    'id' => $row['id'],
                    'session_id' => $row['session_id'],
                    'user_name' => $row['user_name'],
                    'language' => $row['language'],
                    'summary' => $row['summary'],
                    'created_at' => strtotime($row['created_at']) * 1000, // Convert to milliseconds
                    'updated_at' => strtotime($row['updated_at']) * 1000
                ];
            }
            
            echo json_encode([
                'success' => true,
                'sessions' => $sessions
            ]);
            break;
            
        case 'get':
            // Lấy chi tiết phiên tư vấn (PDO)
            $session_id = $_GET['session_id'] ?? '';
            
            if (empty($session_id)) {
                throw new Exception('Thiếu session_id');
            }
            
            $stmt = $conn->prepare("
                SELECT id, session_id, user_name, language, summary, messages, created_at
                FROM consultation_history
                WHERE session_id = ? AND user_id = ?
            ");
            $stmt->execute([$session_id, $user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                echo json_encode([
                    'success' => true,
                    'session' => [
                        'id' => $row['id'],
                        'session_id' => $row['session_id'],
                        'user_name' => $row['user_name'],
                        'language' => $row['language'],
                        'summary' => $row['summary'],
                        'messages' => json_decode($row['messages'], true),
                        'created_at' => strtotime($row['created_at']) * 1000
                    ]
                ]);
            } else {
                throw new Exception('Không tìm thấy phiên tư vấn');
            }
            break;
            
        case 'delete':
            // Xóa phiên tư vấn (PDO)
            $session_id = $_POST['session_id'] ?? '';
            
            if (empty($session_id)) {
                throw new Exception('Thiếu session_id');
            }
            
            $stmt = $conn->prepare("
                DELETE FROM consultation_history
                WHERE session_id = ? AND user_id = ?
            ");
            $stmt->execute([$session_id, $user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa lịch sử tư vấn'
            ]);
            break;
            
        default:
            throw new Exception('Action không hợp lệ');
    }
    
} catch (Exception $e) {
    error_log("[api_consultation_history] Error: " . $e->getMessage());
    error_log("[api_consultation_history] Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
}
?>

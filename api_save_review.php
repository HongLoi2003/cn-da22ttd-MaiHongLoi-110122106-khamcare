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
    // Kết nối database
    $pdo = new PDO(
        'mysql:host=localhost;dbname=khamcare_import;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    $user_id = $_SESSION['user_id'];
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $rating = floatval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    $service_quality = intval($_POST['service_quality'] ?? 0);
    $communication = intval($_POST['communication'] ?? 0);
    $professionalism = intval($_POST['professionalism'] ?? 0);
    $waiting_time = intval($_POST['waiting_time'] ?? 0);
    $would_recommend = intval($_POST['would_recommend'] ?? 1);
    $is_anonymous = intval($_POST['is_anonymous'] ?? 0);
    
    // Validate
    if ($doctor_id <= 0) {
        throw new Exception('Doctor ID không hợp lệ');
    }
    
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating phải từ 1 đến 5');
    }
    
    // Kiểm tra bác sĩ tồn tại
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE id = ?");
    $stmt->execute([$doctor_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Bác sĩ không tồn tại');
    }
    
    // Lấy họ tên bệnh nhân
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $patient_name = $user ? $user['full_name'] : 'Bệnh nhân';
    
    // Lưu review với patient_name
    $stmt = $pdo->prepare("
        INSERT INTO reviews 
        (user_id, patient_name, doctor_id, rating, comment, service_quality, communication, 
         professionalism, waiting_time, would_recommend, is_anonymous, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $patient_name,
        $doctor_id,
        $rating,
        $comment,
        $service_quality > 0 ? $service_quality : null,
        $communication > 0 ? $communication : null,
        $professionalism > 0 ? $professionalism : null,
        $waiting_time > 0 ? $waiting_time : null,
        $would_recommend,
        $is_anonymous
    ]);
    
    $review_id = $pdo->lastInsertId();
    
    // Cập nhật rating trung bình của bác sĩ
    $stmt = $pdo->prepare("
        UPDATE doctors 
        SET rating = (
            SELECT AVG(rating) 
            FROM reviews 
            WHERE doctor_id = ? AND status = 'approved'
        )
        WHERE id = ?
    ");
    $stmt->execute([$doctor_id, $doctor_id]);
    
    echo json_encode([
        'success' => true,
        'review_id' => $review_id,
        'message' => 'Cảm ơn bạn đã đánh giá!'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

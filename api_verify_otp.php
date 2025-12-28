<?php
/**
 * API Xác thực mã OTP
 * KhamCare - Hệ thống đặt lịch khám bệnh
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'db_config.php';
require_once 'otp_config.php';

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$otp = trim($input['otp'] ?? '');

// Validate
if (empty($email) || empty($otp)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ email và mã OTP']);
    exit;
}

// Xác thực OTP
$result = verifyOTPFromSession($email, $otp);

if ($result['success']) {
    // Đánh dấu email đã được xác thực
    $_SESSION['email_verified'] = $email;
    $_SESSION['email_verified_at'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Xác thực email thành công! Bạn có thể hoàn tất đăng ký.',
        'verified' => true
    ]);
} else {
    echo json_encode($result);
}
?>

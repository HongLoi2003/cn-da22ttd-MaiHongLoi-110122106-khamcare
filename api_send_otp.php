<?php
/**
 * API Gửi mã OTP qua Email
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
$fullName = trim($input['full_name'] ?? '');
$action = $input['action'] ?? 'send';

// Validate email
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập email']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Kiểm tra email đã tồn tại chưa
    $stmt = $pdo->prepare("SELECT id FROM nguoi_dung WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email này đã được đăng ký. Vui lòng sử dụng email khác hoặc đăng nhập.']);
        exit;
    }
    
    // Kiểm tra rate limit (tránh spam)
    if (isset($_SESSION['otp_last_sent'])) {
        $timeSinceLastSent = time() - $_SESSION['otp_last_sent'];
        if ($timeSinceLastSent < 60) { // 60 giây giữa các lần gửi
            $waitTime = 60 - $timeSinceLastSent;
            echo json_encode(['success' => false, 'message' => "Vui lòng đợi $waitTime giây trước khi gửi lại mã"]);
            exit;
        }
    }
    
    // Tạo mã OTP
    $otp = generateOTP();
    
    // Lưu OTP vào session
    saveOTPToSession($email, $otp);
    $_SESSION['otp_last_sent'] = time();
    $_SESSION['pending_registration'] = [
        'email' => $email,
        'full_name' => $fullName
    ];
    
    // Gửi email
    $result = sendOTPEmail($email, $otp, $fullName);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Mã OTP đã được gửi đến ' . maskEmail($email),
            'expires_in' => OTP_EXPIRY_MINUTES * 60
        ]);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("[api_send_otp] Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.']);
}

/**
 * Ẩn một phần email để bảo mật
 */
function maskEmail($email) {
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    
    $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 4, 2)) . substr($name, -2);
    
    return $maskedName . '@' . $domain;
}
?>

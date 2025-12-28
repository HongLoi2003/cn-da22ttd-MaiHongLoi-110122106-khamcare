<?php
/**
 * Cấu hình OTP - Gửi mã xác thực qua Email
 * KhamCare - Hệ thống đặt lịch khám bệnh
 */

// OTP Settings
define('OTP_LENGTH', 6);
define('OTP_EXPIRY_MINUTES', 5);
define('OTP_MAX_ATTEMPTS', 3);

// Email Configuration - Sử dụng PHPMailer
// Bạn cần cài đặt PHPMailer: composer require phpmailer/phpmailer
// Hoặc download từ: https://github.com/PHPMailer/PHPMailer

// Gmail SMTP Settings (Cần bật "App Password" trong Google Account)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'maihongloi060423@gmail.com');
define('SMTP_PASSWORD', 'kbgumnsgdkjznzkh');
define('SMTP_FROM_EMAIL', 'maihongloi060423@gmail.com');
define('SMTP_FROM_NAME', 'KhamCare');

/**
 * Tạo mã OTP ngẫu nhiên
 */
function generateOTP() {
    return str_pad(rand(0, pow(10, OTP_LENGTH) - 1), OTP_LENGTH, '0', STR_PAD_LEFT);
}

/**
 * Lưu OTP vào session (đơn giản, không cần database)
 */
function saveOTPToSession($email, $otp) {
    $_SESSION['otp_data'] = [
        'email' => $email,
        'otp' => $otp,
        'expires_at' => time() + (OTP_EXPIRY_MINUTES * 60),
        'attempts' => 0
    ];
    return true;
}

/**
 * Xác thực OTP từ session
 */
function verifyOTPFromSession($email, $otp) {
    if (!isset($_SESSION['otp_data'])) {
        return ['success' => false, 'message' => 'Chưa có mã OTP nào được gửi'];
    }
    
    $otpData = $_SESSION['otp_data'];
    
    // Kiểm tra email
    if ($otpData['email'] !== $email) {
        return ['success' => false, 'message' => 'Email không khớp'];
    }
    
    // Kiểm tra hết hạn
    if (time() > $otpData['expires_at']) {
        unset($_SESSION['otp_data']);
        return ['success' => false, 'message' => 'Mã OTP đã hết hạn. Vui lòng gửi lại mã mới.'];
    }
    
    // Kiểm tra số lần thử
    if ($otpData['attempts'] >= OTP_MAX_ATTEMPTS) {
        unset($_SESSION['otp_data']);
        return ['success' => false, 'message' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng gửi lại mã mới.'];
    }
    
    // Kiểm tra mã OTP
    if ($otpData['otp'] !== $otp) {
        $_SESSION['otp_data']['attempts']++;
        $remaining = OTP_MAX_ATTEMPTS - $_SESSION['otp_data']['attempts'];
        return ['success' => false, 'message' => "Mã OTP không đúng. Còn $remaining lần thử."];
    }
    
    // OTP hợp lệ - xóa khỏi session
    unset($_SESSION['otp_data']);
    return ['success' => true, 'message' => 'Xác thực thành công'];
}

/**
 * Gửi OTP qua Email sử dụng PHPMailer
 */
function sendOTPEmail($email, $otp, $fullName = '') {
    // Kiểm tra PHPMailer thủ công (không dùng composer)
    $phpmailerPath = __DIR__ . '/PHPMailer/src/PHPMailer.php';
    
    if (file_exists($phpmailerPath)) {
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        return sendWithPHPMailer($email, $otp, $fullName);
    }
    
    // Fallback: Sử dụng mail() function của PHP
    return sendWithPHPMail($email, $otp, $fullName);
}

/**
 * Gửi email bằng PHPMailer
 */
function sendWithPHPMailer($email, $otp, $fullName) {
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        
        // Tắt xác thực SSL cho localhost
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $fullName);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Ma xac thuc OTP - KhamCare';
        $mail->Body = getOTPEmailTemplate($otp, $fullName);
        $mail->AltBody = "Ma OTP cua ban la: $otp. Ma co hieu luc trong " . OTP_EXPIRY_MINUTES . " phut.";
        
        $mail->send();
        return ['success' => true, 'message' => 'Mã OTP đã được gửi đến email của bạn'];
        
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log("PHPMailer Exception: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi gửi email: ' . $mail->ErrorInfo];
    } catch (\Exception $e) {
        error_log("General Exception: " . $e->getMessage());
        return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

/**
 * Gửi email bằng mail() function (fallback)
 */
function sendWithPHPMail($email, $otp, $fullName) {
    $subject = '=?UTF-8?B?' . base64_encode('🔐 Mã xác thực OTP - KhamCare') . '?=';
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    
    $body = getOTPEmailTemplate($otp, $fullName);
    
    if (mail($email, $subject, $body, $headers)) {
        return ['success' => true, 'message' => 'Mã OTP đã được gửi đến email của bạn'];
    } else {
        return ['success' => false, 'message' => 'Không thể gửi email. Vui lòng thử lại sau.'];
    }
}

/**
 * Template email OTP
 */
function getOTPEmailTemplate($otp, $fullName = '') {
    $greeting = $fullName ? "Xin chào $fullName," : "Xin chào,";
    $expiry = OTP_EXPIRY_MINUTES;
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #0ea5e9, #10b981); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { color: white; margin: 0; font-size: 28px; }
            .content { background: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; }
            .otp-box { background: white; border: 2px dashed #0ea5e9; padding: 20px; text-align: center; margin: 20px 0; border-radius: 10px; }
            .otp-code { font-size: 36px; font-weight: bold; color: #0ea5e9; letter-spacing: 8px; }
            .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0; }
            .footer { text-align: center; padding: 20px; color: #64748b; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🏥 KhamCare</h1>
            </div>
            <div class='content'>
                <p>$greeting</p>
                <p>Bạn đã yêu cầu mã xác thực OTP để đăng ký tài khoản tại <strong>KhamCare</strong>.</p>
                
                <div class='otp-box'>
                    <p style='margin: 0 0 10px 0; color: #64748b;'>Mã xác thực của bạn là:</p>
                    <div class='otp-code'>$otp</div>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Lưu ý:</strong>
                    <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                        <li>Mã có hiệu lực trong <strong>$expiry phút</strong></li>
                        <li>Không chia sẻ mã này với bất kỳ ai</li>
                        <li>KhamCare không bao giờ yêu cầu mã OTP qua điện thoại</li>
                    </ul>
                </div>
                
                <p>Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.</p>
                <p>Trân trọng,<br><strong>Đội ngũ KhamCare</strong></p>
            </div>
            <div class='footer'>
                <p>© 2024 KhamCare - Hệ thống đặt lịch khám bệnh trực tuyến</p>
                <p>Email này được gửi tự động, vui lòng không trả lời.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>

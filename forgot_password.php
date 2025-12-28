<?php
/**
 * Trang Quên Mật Khẩu - KhamCare
 * Cho phép người dùng đặt lại mật khẩu qua email OTP
 */

session_start();
require_once 'db_config.php';
require_once 'otp_config.php';

$error_message = '';
$success_message = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Xử lý các bước
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Bước 1: Gửi OTP đến email
    if ($action === 'send_otp') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error_message = 'Vui lòng nhập email!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Email không hợp lệ!';
        } else {
            try {
                $pdo = getDBConnection();
                if (!$pdo) {
                    $error_message = 'Lỗi kết nối database!';
                } else {
                    $stmt = $pdo->prepare("SELECT id, ho_ten FROM nguoi_dung WHERE email = ? AND trang_thai = 'hoat_dong'");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    if (!$user) {
                        $error_message = 'Email này chưa được đăng ký trong hệ thống!';
                    } else {
                        // Rate limit
                        if (isset($_SESSION['reset_otp_last_sent'])) {
                            $timeSince = time() - $_SESSION['reset_otp_last_sent'];
                            if ($timeSince < 60) {
                                $error_message = 'Vui lòng đợi ' . (60 - $timeSince) . ' giây trước khi gửi lại!';
                            }
                        }
                        
                        if (empty($error_message)) {
                            $otp = generateOTP();
                            $_SESSION['reset_password_data'] = [
                                'email' => $email,
                                'user_id' => $user['id'],
                                'otp' => $otp,
                                'expires_at' => time() + (OTP_EXPIRY_MINUTES * 60),
                                'attempts' => 0
                            ];
                            $_SESSION['reset_otp_last_sent'] = time();

                            $result = sendResetPasswordEmail($email, $otp, $user['ho_ten']);
                            
                            if ($result['success']) {
                                $success_message = 'Mã OTP đã được gửi đến email của bạn!';
                                $step = 2;
                            } else {
                                // Vẫn chuyển sang bước 2 nhưng hiển thị cảnh báo
                                $success_message = 'Mã OTP: ' . $otp . ' (Lỗi gửi email: ' . $result['message'] . ')';
                                $step = 2;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $error_message = 'Lỗi hệ thống: ' . $e->getMessage();
            }
        }
    }
    
    // Bước 2: Xác thực OTP
    if ($action === 'verify_otp') {
        $otp = trim($_POST['otp'] ?? '');
        
        if (!isset($_SESSION['reset_password_data'])) {
            $error_message = 'Phiên làm việc đã hết hạn. Vui lòng thử lại!';
            $step = 1;
        } elseif (empty($otp)) {
            $error_message = 'Vui lòng nhập mã OTP!';
            $step = 2;
        } else {
            $data = $_SESSION['reset_password_data'];
            
            if (time() > $data['expires_at']) {
                $error_message = 'Mã OTP đã hết hạn. Vui lòng gửi lại!';
                unset($_SESSION['reset_password_data']);
                $step = 1;
            } elseif ($data['attempts'] >= OTP_MAX_ATTEMPTS) {
                $error_message = 'Bạn đã nhập sai quá nhiều lần. Vui lòng gửi lại mã!';
                unset($_SESSION['reset_password_data']);
                $step = 1;
            } elseif ($data['otp'] !== $otp) {
                $_SESSION['reset_password_data']['attempts']++;
                $remaining = OTP_MAX_ATTEMPTS - $_SESSION['reset_password_data']['attempts'];
                $error_message = "Mã OTP không đúng. Còn $remaining lần thử.";
                $step = 2;
            } else {
                $_SESSION['reset_password_data']['verified'] = true;
                $success_message = 'Xác thực thành công! Vui lòng đặt mật khẩu mới.';
                $step = 3;
            }
        }
    }
    
    // Bước 3: Đặt mật khẩu mới
    if ($action === 'reset_password') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (!isset($_SESSION['reset_password_data']) || !($_SESSION['reset_password_data']['verified'] ?? false)) {
            $error_message = 'Phiên làm việc không hợp lệ. Vui lòng thử lại!';
            $step = 1;
        } elseif (empty($password) || empty($confirm_password)) {
            $error_message = 'Vui lòng nhập đầy đủ mật khẩu!';
            $step = 3;
        } elseif (strlen($password) < 6) {
            $error_message = 'Mật khẩu phải có ít nhất 6 ký tự!';
            $step = 3;
        } elseif ($password !== $confirm_password) {
            $error_message = 'Mật khẩu xác nhận không khớp!';
            $step = 3;
        } else {
            $pdo = getDBConnection();
            $user_id = $_SESSION['reset_password_data']['user_id'];
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE nguoi_dung SET mat_khau = ? WHERE id = ?");
            if ($stmt->execute([$hashed, $user_id])) {
                unset($_SESSION['reset_password_data']);
                $success_message = 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay.';
                $step = 4;
            } else {
                $error_message = 'Có lỗi xảy ra. Vui lòng thử lại!';
                $step = 3;
            }
        }
    }
}

/**
 * Gửi email đặt lại mật khẩu
 */
function sendResetPasswordEmail($email, $otp, $fullName = '') {
    $phpmailerPath = __DIR__ . '/PHPMailer/src/PHPMailer.php';
    
    if (file_exists($phpmailerPath)) {
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
            
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($email, $fullName);
            $mail->isHTML(true);
            $mail->Subject = 'Dat lai mat khau - KhamCare';
            $mail->Body = getResetPasswordEmailTemplate($otp, $fullName);
            $mail->AltBody = "Ma OTP dat lai mat khau: $otp. Hieu luc trong " . OTP_EXPIRY_MINUTES . " phut.";
            
            $mail->send();
            return ['success' => true, 'message' => 'Email đã được gửi'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Lỗi gửi email: ' . $e->getMessage()];
        }
    }
    
    $subject = '=?UTF-8?B?' . base64_encode('🔐 Đặt lại mật khẩu - KhamCare') . '?=';
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: KhamCare <" . SMTP_FROM_EMAIL . ">\r\n";
    
    if (mail($email, $subject, getResetPasswordEmailTemplate($otp, $fullName), $headers)) {
        return ['success' => true, 'message' => 'Email đã được gửi'];
    }
    return ['success' => false, 'message' => 'Không thể gửi email'];
}

function getResetPasswordEmailTemplate($otp, $fullName = '') {
    $greeting = $fullName ? "Xin chào $fullName," : "Xin chào,";
    $expiry = OTP_EXPIRY_MINUTES;
    return "
    <!DOCTYPE html><html><head><meta charset='UTF-8'></head>
    <body style='font-family:Arial,sans-serif;line-height:1.6;color:#333;'>
    <div style='max-width:600px;margin:0 auto;padding:20px;'>
        <div style='background:linear-gradient(135deg,#ef4444,#f59e0b);padding:30px;text-align:center;border-radius:10px 10px 0 0;'>
            <h1 style='color:white;margin:0;'>🔐 Đặt lại mật khẩu</h1>
        </div>
        <div style='background:#f8fafc;padding:30px;border:1px solid #e2e8f0;'>
            <p>$greeting</p>
            <p>Bạn đã yêu cầu đặt lại mật khẩu tài khoản <strong>KhamCare</strong>.</p>
            <div style='background:white;border:2px dashed #ef4444;padding:20px;text-align:center;margin:20px 0;border-radius:10px;'>
                <p style='margin:0 0 10px 0;color:#64748b;'>Mã xác thực của bạn:</p>
                <div style='font-size:36px;font-weight:bold;color:#ef4444;letter-spacing:8px;'>$otp</div>
            </div>
            <div style='background:#fef3c7;border-left:4px solid #f59e0b;padding:15px;margin:20px 0;'>
                <strong>⚠️ Lưu ý:</strong>
                <ul><li>Mã có hiệu lực trong <strong>$expiry phút</strong></li>
                <li>Nếu bạn không yêu cầu, hãy bỏ qua email này</li></ul>
            </div>
            <p>Trân trọng,<br><strong>Đội ngũ KhamCare</strong></p>
        </div>
    </div></body></html>";
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quên mật khẩu - KhamCare</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="fix-icons-visibility.css">
  <link rel="stylesheet" href="mobile-responsive.css">
  <style>
    :root {
      /* Medical Color Palette - Giống TaiKhoan.php */
      --primary: #0ea5e9;
      --primary-light: #38bdf8;
      --primary-dark: #0284c7;
      --secondary: #f8fafc;
      --accent: #10b981;
      --accent-light: #34d399;
      --accent-dark: #059669;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --info: #06b6d4;
      --dark: #1e293b;
      --light: #f1f5f9;
      --white: #ffffff;
      --gray-50: #f9fafb;
      --gray-100: #f3f4f6;
      --gray-200: #e5e7eb;
      --gray-300: #d1d5db;
      --gray-400: #9ca3af;
      --gray-500: #6b7280;
      --gray-600: #4b5563;
      --gray-700: #374151;
      --gray-800: #1f2937;
      --gray-900: #111827;
      
      /* Spacing & Layout */
      --radius-sm: 8px;
      --radius: 12px;
      --radius-lg: 16px;
      --radius-xl: 20px;
      --radius-2xl: 24px;
      
      /* Shadows */
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
      --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
      --shadow-xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
      
      /* Transitions */
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
      
      /* Gradients */
      --gradient-primary: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      --gradient-accent: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
      --gradient-card: linear-gradient(145deg, rgba(255,255,255,0.95) 0%, rgba(240,249,255,0.8) 100%);
      --gradient-hero: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    }

    /* Global Styles */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      background: 
        radial-gradient(circle at 20% 80%, rgba(16, 185, 129, 0.15) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(14, 165, 233, 0.15) 0%, transparent 50%),
        linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      background-attachment: fixed;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      color: var(--gray-800);
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      position: relative;
      padding: 20px;
    }

    /* Medical pattern overlay */
    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: 
        radial-gradient(circle at 25px 25px, rgba(14, 165, 233, 0.03) 2px, transparent 2px),
        radial-gradient(circle at 75px 75px, rgba(16, 185, 129, 0.03) 1px, transparent 1px);
      background-size: 100px 100px, 50px 50px;
      pointer-events: none;
      z-index: -1;
    }

    /* Main Container */
    .main-container {
      display: flex;
      width: 900px;
      max-width: 95vw;
      background: var(--gradient-card);
      backdrop-filter: blur(20px);
      border-radius: var(--radius-2xl);
      overflow: hidden;
      box-shadow: var(--shadow-xl);
      border: 2px solid rgba(255, 255, 255, 0.3);
      position: relative;
    }

    .main-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--gradient-primary);
      border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    }

    /* Left Side - Welcome Panel */
    .left {
      flex: 1;
      background: var(--gradient-primary);
      color: var(--white);
      padding: 60px 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      position: relative;
      overflow: hidden;
      min-width: 350px;
    }

    .left::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="medical-cross" width="60" height="60" patternUnits="userSpaceOnUse"><g fill="rgba(255, 255, 255, 0.1)"><rect x="25" y="15" width="10" height="30"/><rect x="15" y="25" width="30" height="10"/></g></pattern></defs><rect width="100" height="100" fill="url(%23medical-cross)"/></svg>');
      background-size: 120px 120px;
      opacity: 0.3;
      z-index: 1;
    }

    .left > * {
      position: relative;
      z-index: 2;
    }

    .left h2 {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 20px;
      text-shadow: 0 2px 10px rgba(0,0,0,0.2);
      line-height: 1.2;
    }

    .left p {
      font-size: 1.1rem;
      margin-bottom: 20px;
      line-height: 1.6;
      opacity: 0.95;
      font-weight: 500;
    }

    /* Logo Styles */
    .logo-container {
      margin-bottom: 20px;
    }

    .logo-container img {
      width: 120px;
      height: 120px;
      object-fit: contain;
      border-radius: 24px;
      filter: drop-shadow(0 8px 20px rgba(0, 0, 0, 0.3));
      animation: float 3s ease-in-out infinite;
    }

    .logo-container h1 {
      font-size: 2.5rem;
      font-weight: 800;
      color: white;
      text-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      margin-top: 15px;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }

    /* Steps Indicator */
    .steps {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
    }

    .step-dot {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.3);
      transition: var(--transition);
    }

    .step-dot.active {
      background: white;
      transform: scale(1.3);
      box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
    }

    .step-dot.completed {
      background: var(--accent);
    }

    /* Right Side - Form Panel */
    .right {
      flex: 1;
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-width: 400px;
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
    }

    /* Form Title */
    h3 {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      text-align: center;
      color: var(--gray-800);
      margin-bottom: 10px;
      font-size: 1.8rem;
      font-weight: 800;
      position: relative;
    }

    h3::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background: var(--gradient-accent);
      border-radius: 2px;
    }

    .subtitle {
      text-align: center;
      color: var(--gray-500);
      margin: 20px 0 30px 0;
      font-size: 1rem;
      font-weight: 500;
    }

    /* Input Styles */
    input[type="text"], 
    input[type="email"], 
    input[type="password"] {
      width: 100%;
      padding: 18px 20px;
      margin: 12px 0;
      border: 2px solid rgba(14, 165, 233, 0.2);
      border-radius: var(--radius-lg);
      font-size: 1.1rem;
      font-weight: 500;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      color: var(--gray-800);
      transition: var(--transition);
      font-family: inherit;
    }

    input[type="text"]:focus, 
    input[type="email"]:focus, 
    input[type="password"]:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
      transform: translateY(-2px);
      background: var(--white);
    }

    input[type="text"]:hover, 
    input[type="email"]:hover, 
    input[type="password"]:hover {
      border-color: var(--primary-light);
      transform: translateY(-1px);
    }

    input::placeholder {
      color: var(--gray-500);
      font-weight: 400;
    }

    /* OTP Input Special Style */
    .otp-input {
      text-align: center;
      font-size: 1.8rem !important;
      letter-spacing: 12px;
      font-weight: 700 !important;
      padding: 20px !important;
    }

    /* Form Group */
    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--gray-700);
      font-size: 1rem;
    }

    .form-group label i {
      margin-right: 8px;
      color: var(--primary);
    }

    /* Button Styles */
    button.btn-main {
      width: 100%;
      background: var(--gradient-primary);
      color: var(--white);
      border: none;
      padding: 18px 24px;
      border-radius: var(--radius-lg);
      font-weight: 700;
      font-size: 1.1rem;
      cursor: pointer;
      transition: var(--transition);
      margin-top: 10px;
      box-shadow: var(--shadow-lg);
      position: relative;
      overflow: hidden;
      font-family: inherit;
    }

    button.btn-main::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: var(--transition);
    }

    button.btn-main:hover {
      background: var(--gradient-accent);
      transform: translateY(-3px);
      box-shadow: var(--shadow-xl);
    }

    button.btn-main:hover::before {
      left: 100%;
    }

    button.btn-main:active {
      transform: translateY(-1px);
    }

    button.btn-success {
      background: var(--gradient-accent);
    }

    button.btn-success:hover {
      background: var(--gradient-primary);
    }

    /* Alert Styles */
    .alert {
      padding: 16px 20px;
      border-radius: var(--radius-lg);
      margin-bottom: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert i {
      font-size: 1.2rem;
    }

    .alert-error {
      background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
      color: #dc2626;
      border: 2px solid #fecaca;
    }

    .alert-success {
      background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
      color: #059669;
      border: 2px solid #a7f3d0;
    }

    /* Timer */
    .timer {
      text-align: center;
      color: var(--gray-500);
      margin-top: 20px;
      font-size: 0.95rem;
      font-weight: 500;
    }

    .timer #countdown {
      font-weight: 700;
      color: var(--primary);
    }

    /* Back Link */
    .back-link {
      text-align: center;
      margin-top: 25px;
    }

    .back-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
      transition: var(--transition);
      padding: 8px 16px;
      border-radius: var(--radius);
    }

    .back-link a:hover {
      color: var(--accent);
      background: rgba(14, 165, 233, 0.1);
    }

    .back-link a i {
      margin-right: 6px;
    }

    /* Success Icon */
    .success-icon {
      text-align: center;
      font-size: 5rem;
      margin-bottom: 20px;
      animation: bounceIn 0.8s ease-out;
    }

    @keyframes bounceIn {
      0% { transform: scale(0); opacity: 0; }
      50% { transform: scale(1.2); }
      100% { transform: scale(1); opacity: 1; }
    }

    /* Resend Link */
    .resend-link {
      text-align: center;
      margin-top: 15px;
    }

    .resend-link a {
      color: var(--primary);
      text-decoration: none;
      font-weight: 600;
    }

    .resend-link a:hover {
      text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 900px) {
      .main-container {
        flex-direction: column;
        width: 100%;
        max-width: 500px;
      }

      .left {
        padding: 40px 30px;
        min-width: auto;
      }

      .left h2 {
        font-size: 1.6rem;
      }

      .left p {
        font-size: 1rem;
      }

      .logo-container img {
        width: 80px;
        height: 80px;
      }

      .logo-container h1 {
        font-size: 2rem;
      }

      .right {
        padding: 40px 30px;
        min-width: auto;
      }

      h3 {
        font-size: 1.5rem;
      }
    }

    @media (max-width: 480px) {
      body {
        padding: 15px;
      }

      .left {
        padding: 30px 20px;
      }

      .right {
        padding: 30px 20px;
      }

      h3 {
        font-size: 1.3rem;
      }

      input[type="text"], 
      input[type="email"], 
      input[type="password"] {
        padding: 14px 16px;
        font-size: 1rem;
      }

      .otp-input {
        font-size: 1.5rem !important;
        letter-spacing: 8px;
      }

      button.btn-main {
        padding: 14px 18px;
        font-size: 1rem;
      }
    }

    /* Loading Animation */
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }

    .loading {
      animation: pulse 2s infinite;
    }

    /* Scrollbar Styling */
    ::-webkit-scrollbar {
      width: 8px;
    }

    ::-webkit-scrollbar-track {
      background: var(--gray-100);
    }

    ::-webkit-scrollbar-thumb {
      background: var(--primary);
      border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
      background: var(--primary-dark);
    }
  </style>
</head>
<body>
  <div class="main-container">
    <!-- Left Side - Welcome Panel -->
    <div class="left">
      <div class="logo-container">
        <img src="image/logo.png.png" alt="KhamCare" onerror="this.style.display='none'">
        <h1>KhamCare</h1>
      </div>
      
      <h2>Khôi phục mật khẩu</h2>
      <p>Đừng lo lắng! Chúng tôi sẽ giúp bạn lấy lại quyền truy cập vào tài khoản của mình một cách an toàn.</p>
      
      <!-- Steps indicator -->
      <div class="steps">
        <div class="step-dot <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>"></div>
        <div class="step-dot <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>"></div>
        <div class="step-dot <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>"></div>
        <div class="step-dot <?php echo $step >= 4 ? 'active' : ''; ?>"></div>
      </div>
      
      <p style="font-size: 0.9rem; opacity: 0.8;">
        <?php
        $stepLabels = [
            1 => 'Bước 1: Nhập email',
            2 => 'Bước 2: Xác thực OTP',
            3 => 'Bước 3: Đặt mật khẩu mới',
            4 => 'Hoàn thành!'
        ];
        echo $stepLabels[$step] ?? '';
        ?>
      </p>
    </div>

    <!-- Right Side - Form Panel -->
    <div class="right">
      <?php if ($error_message): ?>
        <div class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>
      
      <?php if ($success_message && $step != 4): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>
      
      <?php if ($step == 1): ?>
      <!-- Bước 1: Nhập email -->
      <h3>Quên mật khẩu?</h3>
      <p class="subtitle">Nhập email đã đăng ký để nhận mã xác thực</p>
      
      <form method="POST">
        <input type="hidden" name="action" value="send_otp">
        <div class="form-group">
          <label><i class="fas fa-envelope"></i> Email của bạn</label>
          <input type="email" name="email" placeholder="example@email.com" required 
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <button type="submit" class="btn-main">
          <i class="fas fa-paper-plane"></i> Gửi mã xác thực
        </button>
      </form>
      
      <?php elseif ($step == 2): ?>
      <!-- Bước 2: Nhập OTP -->
      <h3>Xác thực OTP</h3>
      <p class="subtitle">Nhập mã 6 số đã gửi đến email của bạn</p>
      
      <form method="POST">
        <input type="hidden" name="action" value="verify_otp">
        <div class="form-group">
          <label><i class="fas fa-key"></i> Mã OTP</label>
          <input type="text" name="otp" class="otp-input" placeholder="000000" 
                 maxlength="6" pattern="[0-9]{6}" required autofocus>
        </div>
        <button type="submit" class="btn-main">
          <i class="fas fa-check"></i> Xác thực
        </button>
      </form>
      
      <div class="timer">
        Mã có hiệu lực trong <span id="countdown"><?php echo OTP_EXPIRY_MINUTES; ?>:00</span>
      </div>
      
      <div class="resend-link">
        <a href="forgot_password.php"><i class="fas fa-redo"></i> Gửi lại mã</a>
      </div>
      
      <?php elseif ($step == 3): ?>
      <!-- Bước 3: Đặt mật khẩu mới -->
      <h3>Đặt mật khẩu mới</h3>
      <p class="subtitle">Tạo mật khẩu mới cho tài khoản của bạn</p>
      
      <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <div class="form-group">
          <label><i class="fas fa-lock"></i> Mật khẩu mới</label>
          <input type="password" name="password" placeholder="Ít nhất 6 ký tự" required minlength="6">
        </div>
        <div class="form-group">
          <label><i class="fas fa-lock"></i> Xác nhận mật khẩu</label>
          <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
        </div>
        <button type="submit" class="btn-main btn-success">
          <i class="fas fa-save"></i> Đặt lại mật khẩu
        </button>
      </form>
      
      <?php elseif ($step == 4): ?>
      <!-- Bước 4: Thành công -->
      <div class="success-icon">✅</div>
      <h3>Thành công!</h3>
      <p class="subtitle"><?php echo htmlspecialchars($success_message); ?></p>
      
      <a href="TaiKhoan.php" class="btn-main" style="display: block; text-align: center; text-decoration: none;">
        <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
      </a>
      <?php endif; ?>
      
      <div class="back-link">
        <a href="TaiKhoan.php"><i class="fas fa-arrow-left"></i> Quay lại đăng nhập</a>
      </div>
    </div>
  </div>
  
  <script>
  // Countdown timer cho OTP
  <?php if ($step == 2 && isset($_SESSION['reset_password_data'])): ?>
  (function() {
    var expiresAt = <?php echo $_SESSION['reset_password_data']['expires_at'] ?? 0; ?>;
    var countdownEl = document.getElementById('countdown');
    
    function updateCountdown() {
      var now = Math.floor(Date.now() / 1000);
      var remaining = expiresAt - now;
      
      if (remaining <= 0) {
        countdownEl.textContent = 'Hết hạn';
        countdownEl.style.color = '#ef4444';
        return;
      }
      
      var minutes = Math.floor(remaining / 60);
      var seconds = remaining % 60;
      countdownEl.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
      
      setTimeout(updateCountdown, 1000);
    }
    
    updateCountdown();
  })();
  <?php endif; ?>
  
  // Auto-format OTP input
  var otpInput = document.querySelector('.otp-input');
  if (otpInput) {
    otpInput.addEventListener('input', function(e) {
      this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
    });
  }
  </script>
</body>
</html>

<?php
session_start();
require_once 'db_config.php';
require_once 'social_auth_config.php';

// Khởi tạo biến
$error_message = '';
$success_message = '';
$show_register_form = false;
$is_admin_flow = isset($_GET['redirect']) && $_GET['redirect'] === 'admin_khamcare.php';

// Xử lý tham số mode từ URL để chuyển đổi giữa đăng nhập và đăng ký
if (isset($_GET['mode'])) {
    if ($_GET['mode'] === 'register') {
        $show_register_form = true;
    } elseif ($_GET['mode'] === 'login') {
        $show_register_form = false;
    }
}

// Nhận thông báo lỗi chuyển hướng (ví dụ khi người dùng không phải bác sĩ truy cập vào dashboard bác sĩ)
if (isset($_GET['error']) && $_GET['error'] === 'not_doctor') {
    $error_message = 'Truy cập bị từ chối. Chỉ bác sĩ mới được phép vào trang này.';
}

// Nhận thông báo lỗi từ social login
if (isset($_SESSION['social_login_error'])) {
    $error_message = $_SESSION['social_login_error'];
    unset($_SESSION['social_login_error']);
}

// Tạo URL đăng nhập mạng xã hội
$facebookLoginUrl = getFacebookLoginUrl();
$googleLoginUrl = getGoogleLoginUrl();

// Xử lý đăng nhập
if ($_POST['action'] ?? '' == 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error_message = "Vui lòng nhập đầy đủ thông tin đăng nhập!";
    } else {
        $result = loginUser($username, $password);
        if ($result['success']) {
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['full_name'] = $result['user']['full_name'];
            $_SESSION['role'] = $result['user']['role'];
            
            // Chuyển hướng dựa trên vai trò và luồng vào
            if ($result['user']['role'] === 'admin') {
                header('Location: admin_khamcare.php');
                exit;
            }
            if ($result['user']['role'] === 'doctor') {
                header('Location: doctor_dashboard.php');
                exit;
            }
            // Nếu đang vào từ trang admin nhưng không phải admin
            if ($is_admin_flow) {
                $error_message = 'Tài khoản này không có quyền quản trị. Vui lòng đăng nhập bằng tài khoản admin.';
            } else {
                header('Location: TrangChu.php');
                exit;
            }
        } else {
            $error_message = $result['message'];
        }
    }
}

// Xử lý đăng ký
if ($_POST['action'] ?? '' == 'register') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email_verified = $_POST['email_verified'] ?? '0';
    
    // Xác định role khi đăng ký
    if ($is_admin_flow && isset($_POST['register_as_admin']) && $_POST['register_as_admin'] === '1') {
        $role = 'admin';
    } else {
        $role = 'patient'; // Chỉ cho phép đăng ký bệnh nhân
    }
    
    if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
        $error_message = "Vui lòng nhập đầy đủ thông tin đăng ký!";
        $show_register_form = true;
    } elseif ($email_verified !== '1' && !isset($_SESSION['email_verified'])) {
        $error_message = "Vui lòng xác thực email trước khi đăng ký!";
        $show_register_form = true;
    } elseif (isset($_SESSION['email_verified']) && $_SESSION['email_verified'] !== $email) {
        $error_message = "Email không khớp với email đã xác thực!";
        $show_register_form = true;
    } else {
        $result = registerUser($username, $email, $password, $full_name, $phone, $role);
        if ($result['success']) {
            // Xóa session OTP sau khi đăng ký thành công
            unset($_SESSION['email_verified']);
            unset($_SESSION['email_verified_at']);
            unset($_SESSION['otp_data']);
            unset($_SESSION['pending_registration']);
            
            $success_message = $result['message'];
            $show_register_form = false;
        } else {
            $error_message = $result['message'];
            $show_register_form = true;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập / Đăng ký / Tài Khoản - KhamCare</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="fix-icons-visibility.css">
  <link rel="stylesheet" href="mobile-responsive.css">
  <style>
    
    
    

    :root {
      /* Medical Color Palette */
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
      width: 1200px;
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
      padding: 80px 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      position: relative;
      overflow: hidden;
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
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 20px;
      text-shadow: 0 2px 10px rgba(0,0,0,0.2);
      line-height: 1.2;
    }

    .left p {
      font-size: 1.2rem;
      margin-bottom: 30px;
      line-height: 1.6;
      opacity: 0.95;
      font-weight: 500;
    }

    .left button {
      background: var(--white);
      color: var(--primary);
      border: none;
      padding: 16px 32px;
      border-radius: 25px;
      font-weight: 700;
      font-size: 1.1rem;
      cursor: pointer;
      transition: var(--transition);
      box-shadow: var(--shadow-lg);
      position: relative;
      overflow: hidden;
    }

    .left button::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(14, 165, 233, 0.1), transparent);
      transition: var(--transition);
    }

    .left button:hover {
      background: var(--gray-50);
      transform: translateY(-2px);
      box-shadow: var(--shadow-xl);
    }

    .left button:hover::before {
      left: 100%;
    }

    /* Right Side - Form Panel */
    .right {
      flex: 1;
      padding: 80px 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-width: 450px;
      background: rgba(255, 255, 255, 0.8);
      backdrop-filter: blur(10px);
    }

    /* Form Title */
    h3 {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      text-align: center;
      color: var(--gray-800);
      margin-bottom: 30px;
      font-size: 2rem;
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

    /* Checkbox Styling */
    .form-row {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 15px 0 8px 0;
      padding: 12px 16px;
      background: rgba(16, 185, 129, 0.05);
      border-radius: var(--radius-lg);
      border: 2px solid rgba(16, 185, 129, 0.2);
    }

    input[type="checkbox"] {
      width: 20px !important;
      height: 20px;
      margin: 0 !important;
      accent-color: var(--accent);
      cursor: pointer;
    }

    .form-row label {
      margin: 0;
      font-size: 1rem;
      color: var(--gray-700);
      font-weight: 600;
      cursor: pointer;
    }

    .form-row + small {
      color: var(--gray-600);
      font-size: 0.9rem;
      margin-left: 4px;
      font-weight: 500;
      line-height: 1.4;
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
      margin-top: 20px;
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

    /* Toggle Text */
    .toggle-text {
      text-align: center;
      margin-top: 25px;
      font-size: 1rem;
      color: var(--gray-600);
      font-weight: 500;
    }

    .toggle-text b {
      color: var(--primary);
      cursor: pointer;
      font-weight: 700;
      transition: var(--transition);
      padding: 4px 8px;
      border-radius: 6px;
    }

    .toggle-text b:hover {
      color: var(--accent);
      background: rgba(14, 165, 233, 0.1);
    }

    /* Toast Notification */
    .toast {
      position: fixed;
      top: 30px;
      right: 30px;
      min-width: 320px;
      max-width: 90vw;
      padding: 16px 20px;
      border-radius: var(--radius-lg);
      color: var(--white);
      box-shadow: var(--shadow-xl);
      z-index: 9999;
      display: none;
      font-size: 1rem;
      font-weight: 600;
      backdrop-filter: blur(20px);
      border: 2px solid rgba(255, 255, 255, 0.2);
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    .toast-success {
      background: var(--gradient-accent);
      border-color: var(--accent-light);
    }

    .toast-error {
      background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%);
      border-color: #fca5a5;
    }

    .toast::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: rgba(255, 255, 255, 0.3);
      border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
      .main-container {
        width: 90vw;
        flex-direction: column;
      }

      .left {
        padding: 60px 40px;
      }

      .left h2 {
        font-size: 2rem;
      }

      .left p {
        font-size: 1.1rem;
      }

      .right {
        padding: 60px 40px;
        min-width: auto;
      }

      h3 {
        font-size: 1.8rem;
      }
    }

    @media (max-width: 768px) {
      body {
        padding: 20px;
      }

      .main-container {
        width: 100%;
      }

      .left {
        padding: 40px 30px;
      }

      .left h2 {
        font-size: 1.8rem;
      }

      .left p {
        font-size: 1rem;
      }

      .right {
        padding: 40px 30px;
      }

      h3 {
        font-size: 1.6rem;
      }

      input[type="text"], 
      input[type="email"], 
      input[type="password"] {
        padding: 16px 18px;
        font-size: 1rem;
      }

      button.btn-main {
        padding: 16px 20px;
        font-size: 1rem;
      }

      .toast {
        top: 20px;
        right: 20px;
        min-width: 280px;
        padding: 14px 18px;
        font-size: 0.95rem;
      }
    }

    @media (max-width: 480px) {
      .left {
        padding: 30px 20px;
      }

      .right {
        padding: 30px 20px;
      }

      h3 {
        font-size: 1.4rem;
      }

      .left h2 {
        font-size: 1.6rem;
      }

      input[type="text"], 
      input[type="email"], 
      input[type="password"] {
        padding: 14px 16px;
      }

      button.btn-main {
        padding: 14px 18px;
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

    /* Social Login Buttons */
    .btn-social {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 14px 20px;
      border-radius: var(--radius-lg);
      font-weight: 600;
      font-size: 1rem;
      text-decoration: none;
      transition: var(--transition);
      border: 2px solid transparent;
      font-family: inherit;
    }

    .btn-facebook {
      background: #1877f2;
      color: white;
      border-color: #1877f2;
    }

    .btn-facebook:hover {
      background: #166fe5;
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .btn-google {
      background: white;
      color: var(--gray-700);
      border-color: var(--gray-300);
      box-shadow: var(--shadow-sm);
    }

    .btn-google:hover {
      background: var(--gray-50);
      border-color: var(--gray-400);
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    /* OTP Verification Buttons */
    .btn-verify-otp {
      position: absolute;
      right: 5px;
      top: 50%;
      transform: translateY(-50%);
      background: var(--gradient-accent);
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      font-family: inherit;
    }

    .btn-verify-otp:hover {
      background: var(--accent-dark);
      transform: translateY(-50%) scale(1.05);
    }

    .btn-verify-submit {
      background: var(--gradient-accent);
      color: white;
      border: none;
      padding: 14px 24px;
      border-radius: var(--radius-lg);
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: var(--transition);
      white-space: nowrap;
      font-family: inherit;
    }

    .btn-verify-submit:hover {
      background: var(--accent-dark);
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    /* OTP Input */
    #otpCode {
      font-size: 1.3rem;
      letter-spacing: 5px;
      text-align: center;
      font-weight: 700;
    }

    /* Form Section - Clean & Modern */
    .form-section {
      margin: 25px 0;
    }

    .form-section-title {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--gray-500);
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding-left: 2px;
    }

    /* Input Groups - Minimal & Clean */
    .input-group {
      margin-bottom: 16px;
    }

    .input-group label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--gray-700);
      margin-bottom: 6px;
      padding-left: 2px;
    }

    .input-group input {
      margin: 0 !important;
      border: 2px solid var(--gray-200) !important;
      transition: all 0.2s ease !important;
    }

    .input-group input:focus {
      border-color: var(--primary) !important;
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1) !important;
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

    /* Logo Animations */
    @keyframes float {
      0%, 100% {
        transform: translateY(0px);
      }
      50% {
        transform: translateY(-10px);
      }
    }

    @keyframes fadeInScale {
      0% {
        opacity: 0;
        transform: scale(0.5);
      }
      100% {
        opacity: 1;
        transform: scale(1);
      }
    }

    /* Logo hover effect */
    .logo-container:hover {
      transform: scale(1.08) translateY(-5px) rotate(2deg);
      filter: drop-shadow(0 15px 35px rgba(0, 0, 0, 0.4)) !important;
    }
  </style>
</head>
<body>
  <div id="toast" class="toast"></div>
  <div class="main-container">
    <!-- BÊN TRÁI -->
    <div class="left">
      <div style="
        margin-bottom: 30px; 
        animation: fadeInScale 0.8s ease-out;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
      ">
        <img src="image/logo.png.png" alt="KhamCare Logo" class="logo-container" style="
          width: 160px; 
          height: 160px; 
          object-fit: contain;
          border-radius: 30px;
          filter: drop-shadow(0 12px 30px rgba(0, 0, 0, 0.35));
          transition: all 0.3s ease;
          animation: float 3s ease-in-out infinite;
        ">
        <div style="
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
          font-size: 3rem;
          font-weight: 800;
          color: white;
          text-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
          letter-spacing: 2px;
        ">Khamcare</div>
      </div>
      <h2>Chào Mừng Đến KhamCare</h2>
      <p>Hệ thống đặt lịch khám bệnh trực tuyến hiện đại. Nếu bạn chưa có tài khoản, hãy tạo ngay để trải nghiệm dịch vụ y tế chất lượng cao!</p>
      <button id="btnShowRegister">
        ✨ Tạo Tài Khoản Bệnh Nhân
      </button>
      
      <div style="margin-top: 30px; padding: 20px; background: rgba(255,255,255,0.2); border-radius: 12px; backdrop-filter: blur(10px);">
        <p style="margin-bottom: 15px; font-size: 1rem; opacity: 0.9;">
          <strong>Dành cho Bác sĩ:</strong>
        </p>
        <a href="doctor_auth.php" style="display: inline-block; background: rgba(255,255,255,0.9); color: var(--primary); padding: 12px 24px; border-radius: 20px; text-decoration: none; font-weight: 600; transition: var(--transition); box-shadow: var(--shadow);">
          👨‍⚕️ Đăng ký/Đăng nhập Bác sĩ
        </a>
      </div>
    </div>

    <!-- BÊN PHẢI -->
    <div class="right">
      <h3 id="formTitle"><?php echo (isset($show_register_form) && $show_register_form) ? ($is_admin_flow ? 'Đăng ký Admin' : 'Đăng ký') : ($is_admin_flow ? 'Đăng nhập Admin' : 'Đăng nhập'); ?></h3>

      <!-- Thông báo được hiển thị bằng toast, không nằm trong form -->

      <form method="POST" id="loginForm" style="<?php echo (isset($show_register_form) && $show_register_form) ? 'display:none;' : 'display:block;'; ?>">
        <input type="hidden" name="action" value="login">
        <input type="text" id="username" name="username" placeholder="Tên đăng nhập" required>
        <input type="password" id="password" name="password" placeholder="Mật khẩu" required>
        
        <!-- Link Quên mật khẩu -->
        <div style="text-align: right; margin: 10px 0 5px 0;">
          <a href="forgot_password.php" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 500; transition: all 0.3s;">
            <i class="fas fa-key" style="margin-right: 5px;"></i>Quên mật khẩu?
          </a>
        </div>
        
        <button type="submit" class="btn-main">Đăng nhập</button>
        
        <!-- Đăng nhập bằng mạng xã hội -->
        <div style="margin: 25px 0; text-align: center; position: relative;">
          <div style="border-top: 2px solid var(--gray-200); position: absolute; width: 100%; top: 50%; z-index: 1;"></div>
          <span style="background: rgba(255,255,255,0.9); padding: 0 15px; position: relative; z-index: 2; color: var(--gray-600); font-weight: 600;">Hoặc đăng nhập với</span>
        </div>
        
        <div style="display: flex; gap: 12px; margin-top: 20px;">
          <a href="<?php echo htmlspecialchars($facebookLoginUrl); ?>" class="btn-social btn-facebook">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Facebook
          </a>
          <a href="<?php echo htmlspecialchars($googleLoginUrl); ?>" class="btn-social btn-google">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Google
          </a>
        </div>
      </form>

      <form method="POST" id="registerForm" style="<?php echo (isset($show_register_form) && $show_register_form) ? 'display:block;' : 'display:none;'; ?>">
        <input type="hidden" name="action" value="register">
        <?php if ($is_admin_flow): ?>
        <input type="hidden" name="register_as_admin" value="1">
        <?php endif; ?>
        
        <!-- Thông tin tài khoản -->
        <div class="form-section">
          <div class="form-section-title">Thông tin tài khoản</div>
          <div class="input-group">
            <label for="reg_username">Tên đăng nhập</label>
            <input type="text" id="reg_username" name="username" placeholder="" required>
          </div>
          <div class="input-group">
            <label for="reg_password">Mật khẩu</label>
            <input type="password" id="reg_password" name="password" placeholder="" required>
          </div>
        </div>
        
        <!-- Thông tin cá nhân -->
        <div class="form-section">
          <div class="form-section-title">Thông tin cá nhân</div>
          <div class="input-group">
            <label for="fullname">Họ và tên</label>
            <input type="text" id="fullname" name="full_name" placeholder="" required>
          </div>
          <div class="input-group">
            <label for="email">Email</label>
            <div style="position: relative;">
              <input type="email" id="email" name="email" placeholder="" required style="padding-right: 120px;">
              <button type="button" id="btnSendOTP" class="btn-verify-otp" onclick="sendOTP()">Gửi mã</button>
            </div>
          </div>
          
          <!-- OTP Input - Ẩn mặc định -->
          <div class="input-group" id="otpSection" style="display: none;">
            <label for="otpCode">Mã xác thực OTP</label>
            <div style="display: flex; gap: 10px;">
              <input type="text" id="otpCode" name="otp_code" placeholder="Nhập mã 6 số" maxlength="6" style="flex: 1; text-align: center; letter-spacing: 5px; font-size: 1.2rem; font-weight: bold;">
              <button type="button" class="btn-verify-submit" onclick="verifyOTP()">Xác thực</button>
            </div>
            <small id="otpTimer" style="color: var(--gray-600); margin-top: 8px; display: block;"></small>
            <small id="otpStatus" style="margin-top: 5px; display: block;"></small>
          </div>
          
          <!-- Hidden field để đánh dấu email đã xác thực -->
          <input type="hidden" id="emailVerified" name="email_verified" value="0">
          
          <div class="input-group">
            <label for="phone">Số điện thoại</label>
            <input type="text" id="phone" name="phone" placeholder="" required>
          </div>
        </div>
        
        
        
        <?php if (!$is_admin_flow): ?>
        <!-- Đã loại bỏ phần đăng ký bác sĩ - bác sĩ đăng ký riêng tại doctor_auth.php -->
        <div style="background: rgba(14, 165, 233, 0.1); padding: 12px 16px; border-radius: 8px; margin: 10px 0; border-left: 4px solid var(--primary);">
          <small style="color: var(--gray-700); font-weight: 500;">
            <strong>Dành cho bác sĩ:</strong> Vui lòng đăng ký tại 
            <a href="doctor_auth.php" style="color: var(--primary); text-decoration: none; font-weight: 600;">trang đăng ký bác sĩ</a>
          </small>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn-main" id="btnRegisterSubmit">Đăng ký</button>
        
        <!-- Đăng ký bằng mạng xã hội -->
        <div style="margin: 25px 0; text-align: center; position: relative;">
          <div style="border-top: 2px solid var(--gray-200); position: absolute; width: 100%; top: 50%; z-index: 1;"></div>
          <span style="background: rgba(255,255,255,0.9); padding: 0 15px; position: relative; z-index: 2; color: var(--gray-600); font-weight: 600;">Hoặc đăng ký với</span>
        </div>
        
        <div style="display: flex; gap: 12px;">
          <a href="<?php echo htmlspecialchars($facebookLoginUrl); ?>" class="btn-social btn-facebook">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
            Facebook
          </a>
          <a href="<?php echo htmlspecialchars($googleLoginUrl); ?>" class="btn-social btn-google">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
              <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
              <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
              <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Google
          </a>
        </div>
      </form>

      <div class="toggle-text" id="toggleText">
        <?php if (isset($show_register_form) && $show_register_form): ?>
          Đã có tài khoản? <b>Đăng nhập</b>
        <?php else: ?>
          Chưa có tài khoản? <b>Đăng ký</b>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    // ==================== OTP FUNCTIONS (Global) ====================
    let otpCountdown = null;
    let emailVerified = false;
    
    // Gửi mã OTP
    function sendOTP() {
      const email = document.getElementById('email').value.trim();
      const fullName = document.getElementById('fullname').value.trim();
      const btnSendOTP = document.getElementById('btnSendOTP');
      
      console.log('sendOTP called', { email, fullName });
      
      if (!email) {
        showToast('error', 'Vui lòng nhập email trước');
        document.getElementById('email').focus();
        return;
      }
      
      if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        showToast('error', 'Email không hợp lệ');
        return;
      }
      
      // Disable button
      btnSendOTP.disabled = true;
      btnSendOTP.textContent = 'Đang gửi...';
      
      console.log('Calling API...');
      
      fetch('api_send_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, full_name: fullName })
      })
      .then(response => {
        console.log('Response status:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('API Response:', data);
        if (data.success) {
          showToast('success', data.message);
          document.getElementById('otpSection').style.display = 'block';
          document.getElementById('otpCode').focus();
          startOTPTimer(data.expires_in || 300);
          
          // Disable email input
          document.getElementById('email').readOnly = true;
          btnSendOTP.textContent = 'Gửi lại';
        } else {
          showToast('error', data.message);
          btnSendOTP.disabled = false;
          btnSendOTP.textContent = 'Gửi mã';
        }
      })
      .catch(error => {
        console.error('Fetch Error:', error);
        showToast('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
        btnSendOTP.disabled = false;
        btnSendOTP.textContent = 'Gửi mã';
      });
    }
    
    // Xác thực OTP
    function verifyOTP() {
      const email = document.getElementById('email').value.trim();
      const otp = document.getElementById('otpCode').value.trim();
      const otpStatus = document.getElementById('otpStatus');
      
      if (!otp || otp.length !== 6) {
        showToast('error', 'Vui lòng nhập đủ 6 số');
        return;
      }
      
      fetch('api_verify_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, otp: otp })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast('success', data.message);
          emailVerified = true;
          document.getElementById('emailVerified').value = '1';
          
          // Hiển thị trạng thái xác thực thành công
          otpStatus.innerHTML = '<span style="color: var(--success); font-weight: 600;">✅ Email đã được xác thực</span>';
          document.getElementById('otpCode').disabled = true;
          document.getElementById('otpCode').style.background = '#d1fae5';
          
          // Dừng timer
          if (otpCountdown) {
            clearInterval(otpCountdown);
            document.getElementById('otpTimer').textContent = '';
          }
          
          // Ẩn nút gửi lại
          document.getElementById('btnSendOTP').style.display = 'none';
        } else {
          showToast('error', data.message);
          otpStatus.innerHTML = '<span style="color: var(--danger);">' + data.message + '</span>';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
      });
    }
    
    // Timer đếm ngược
    function startOTPTimer(seconds) {
      const timerEl = document.getElementById('otpTimer');
      const btnSendOTP = document.getElementById('btnSendOTP');
      let remaining = seconds;
      
      if (otpCountdown) clearInterval(otpCountdown);
      
      otpCountdown = setInterval(() => {
        remaining--;
        const mins = Math.floor(remaining / 60);
        const secs = remaining % 60;
        timerEl.textContent = 'Mã hết hạn sau: ' + mins + ':' + secs.toString().padStart(2, '0');
        
        if (remaining <= 0) {
          clearInterval(otpCountdown);
          timerEl.innerHTML = '<span style="color: var(--danger);">Mã đã hết hạn. Vui lòng gửi lại.</span>';
          btnSendOTP.disabled = false;
          btnSendOTP.textContent = 'Gửi lại';
          document.getElementById('email').readOnly = false;
        }
      }, 1000);
    }
    
    // Toast helper
    function showToast(type, text) {
      const el = document.getElementById('toast');
      el.className = 'toast ' + (type === 'success' ? 'toast-success' : 'toast-error');
      el.textContent = text;
      el.style.display = 'block';
      setTimeout(() => { el.style.display = 'none'; }, 3500);
    }

    // Đợi DOM load xong mới chạy JavaScript
    document.addEventListener('DOMContentLoaded', function() {
      
    let isLogin = <?php echo (isset($show_register_form) && $show_register_form) ? 'false' : 'true'; ?>;

    // Chuyển đổi giữa Đăng nhập và Đăng ký
    function toggleForm() {
      isLogin = !isLogin;
      const loginForm = document.getElementById("loginForm");
      const registerForm = document.getElementById("registerForm");
      const toggleText = document.getElementById("toggleText");
      const formTitle = document.getElementById("formTitle");
      
      // Không cần xóa vì dùng toast bên ngoài form
      
      if (isLogin) {
        loginForm.style.display = "block";
        registerForm.style.display = "none";
        formTitle.textContent = '<?php echo $is_admin_flow ? 'Đăng nhập Admin' : 'Đăng nhập'; ?>';
        toggleText.innerHTML = '<?php echo $is_admin_flow ? 'Chưa có tài khoản admin? <b>Đăng ký Admin</b>' : 'Chưa có tài khoản? <b>Đăng ký</b>'; ?>';
      } else {
        loginForm.style.display = "none";
        registerForm.style.display = "block";
        formTitle.textContent = '<?php echo $is_admin_flow ? 'Đăng ký Admin' : 'Đăng ký'; ?>';
        toggleText.innerHTML = '<?php echo $is_admin_flow ? 'Đã có tài khoản admin? <b>Đăng nhập Admin</b>' : 'Đã có tài khoản? <b>Đăng nhập</b>'; ?>';
      }
    }

    // Khởi tạo trạng thái form dựa trên PHP
    function initializeForm() {
      const loginForm = document.getElementById("loginForm");
      const registerForm = document.getElementById("registerForm");
      const toggleText = document.getElementById("toggleText");
      const formTitle = document.getElementById("formTitle");
      
      if (isLogin) {
        loginForm.style.display = "block";
        registerForm.style.display = "none";
        formTitle.textContent = '<?php echo $is_admin_flow ? 'Đăng nhập Admin' : 'Đăng nhập'; ?>';
        toggleText.innerHTML = 'Chưa có tài khoản? <b>Đăng ký</b>';
      } else {
        loginForm.style.display = "none";
        registerForm.style.display = "block";
        formTitle.textContent = '<?php echo $is_admin_flow ? 'Đăng ký Admin' : 'Đăng ký'; ?>';
        toggleText.innerHTML = 'Đã có tài khoản? <b>Đăng nhập</b>';
      }
    }

    // Khởi tạo khi trang load
    initializeForm();

    document.getElementById("toggleText").onclick = toggleForm;
    document.getElementById("btnShowRegister").onclick = function() {
      if (isLogin) toggleForm();
    };

    // Hiển thị toast theo thông điệp từ PHP
    <?php if (!empty($error_message)): ?>
      showToast('error', <?php echo json_encode($error_message, JSON_UNESCAPED_UNICODE); ?>);
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
      showToast('success', <?php echo json_encode($success_message, JSON_UNESCAPED_UNICODE); ?>);
      // Sau đăng ký thành công, focus vào ô username để đăng nhập
      setTimeout(function() { document.getElementById('username').focus(); }, 200);
    <?php endif; ?>

    // Xử lý form đăng nhập
    document.getElementById("loginForm").addEventListener("submit", function(e) {
      const username = document.getElementById("username").value.trim();
      const password = document.getElementById("password").value.trim();

      if (!username || !password) {
        e.preventDefault();
        alert("Vui lòng nhập đầy đủ thông tin đăng nhập!");
        return false;
      }
      
      if (username.length < 3) {
        e.preventDefault();
        alert("Tên đăng nhập phải có ít nhất 3 ký tự!");
        return false;
      }
      
      if (password.length < 6) {
        e.preventDefault();
        alert("Mật khẩu phải có ít nhất 6 ký tự!");
        return false;
      }
    });

    // Chặn form đăng ký nếu chưa xác thực OTP
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      if (!emailVerified && document.getElementById('emailVerified').value !== '1') {
        e.preventDefault();
        showToast('error', 'Vui lòng xác thực email trước khi đăng ký');
        return false;
      }
    });
    
    // Chỉ cho phép nhập số vào ô OTP
    document.getElementById('otpCode').addEventListener('input', function(e) {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
    
    }); // End DOMContentLoaded
  </script>
</body>
</html>

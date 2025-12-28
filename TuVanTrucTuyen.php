<?php
session_start();
require_once 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: TaiKhoan.php');
    exit;
}

// Lấy thông tin user
$user = getUserById($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header('Location: TaiKhoan.php');
    exit;
}

// Chỉ dành cho bệnh nhân
if (isset($user['role']) && $user['role'] === 'doctor') {
    header('Location: doctor_dashboard.php');
    exit;
}

// Lấy danh sách chuyên khoa
$specialtiesList = getSpecialties();

// Sử dụng helper để lấy dữ liệu bác sĩ từ view_doctor.php (18 bác sĩ giống TrangChu.php)
require_once 'doctor_data_helper.php';
$topDoctors = getTopDoctorsFromViewDoctor(18);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tư vấn trực tuyến - KhamCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css">
    <link rel="stylesheet" href="global-font-override.css">
    <link rel="stylesheet" href="square-icons-override.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <style>
        
        
        
        :root {
            --primary: #0891b2;
            --primary-dark: #0e7490;
            --primary-light: #67e8f9;
            --secondary: #f8fafc;
            --accent: #06b6d4;
            --accent-light: #22d3ee;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius: 16px;
            --radius-lg: 24px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Remove all outlines globally */
        button,
        button:focus,
        button:active,
        button:focus-visible {
            outline: none !important;
            outline-width: 0 !important;
            outline-color: transparent !important;
            -webkit-tap-highlight-color: transparent !important;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #06b6d4 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: var(--gray-800);
            letter-spacing: -0.025em;
            line-height: 1.6;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(14, 116, 144, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.4) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(34, 211, 238, 0.3) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .container {
            width: 92%;
            max-width: 1280px;
            margin: 0 auto;
        }

        .header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
        }

        /* Logo Styles */
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 2.2rem;
            font-weight: 800;
            color: #06b6d4;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
        }

        .logo span {
            background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 50%, #10b981 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: logoTextShine 3s linear infinite;
        }

        @keyframes logoTextShine {
            0% {
                background-position: 0% center;
            }
            100% {
                background-position: 200% center;
            }
        }

        .logo:hover {
            transform: scale(1.02);
        }

        .logo:hover span {
            animation-duration: 1.5s;
        }

        .logo img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            border-radius: 18px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
            transition: var(--transition);
            animation: logoFloat 3s ease-in-out infinite, logoPulse 2s ease-in-out infinite;
            background: transparent;
        }

        .logo:hover img {
            transform: scale(1.08) rotate(2deg);
            filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.25));
            animation-play-state: paused;
        }

        @keyframes logoFloat {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes logoPulse {
            0%, 100% {
                filter: drop-shadow(0 4px 12px rgba(6, 182, 212, 0.3));
            }
            50% {
                filter: drop-shadow(0 6px 20px rgba(6, 182, 212, 0.5));
            }
        }

        /* Navigation Menu */
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-menu a {
            text-decoration: none;
            color: #06b6d4;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 10px 16px;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            display: flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            white-space: nowrap;
        }

        .nav-menu a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border-radius: 16px;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: -1;
        }

        .nav-menu a:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        .nav-menu a:hover::before {
            opacity: 1;
            transform: scale(1);
        }

        .nav-menu a:active {
            transform: translateY(0);
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-account {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .btn-account:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        /* Account Dropdown Styles */
        .account-dropdown-wrapper {
            position: relative;
        }

        .btn-account-dropdown {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            white-space: nowrap;
        }

        .btn-account-dropdown:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4);
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .btn-account-dropdown .arrow {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .account-dropdown-wrapper.active .btn-account-dropdown .arrow {
            transform: rotate(180deg);
        }

        .account-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            min-width: 250px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow: hidden;
            border: 1px solid rgba(14, 165, 233, 0.1);
        }

        .account-dropdown-wrapper.active .account-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .account-dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: #374151;
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f3f4f6;
            cursor: pointer;
        }

        .account-dropdown-item:last-child {
            border-bottom: none;
        }

        .account-dropdown-item:hover {
            background: linear-gradient(90deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #0ea5e9;
        }

        .account-dropdown-item i {
            width: 20px;
            font-size: 1.1rem;
            color: #0ea5e9;
        }

        .account-dropdown-item.logout {
            color: #ef4444;
        }

        .account-dropdown-i
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--secondary);
            padding: 8px 16px;
            border-radius: 12px;
        }

        .user-info span {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0891b2;
        }

        .main-content {
            padding: 40px 0;
        }

        .consultation-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 32px;
            padding: 0 20px;
        }

        .chat-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            padding: 24px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }

        .chat-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .call-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        /* NÚT GỌI VÀ VIDEO - KHÔNG CÓ HIỆU ỨNG NHẢY */
        .btn-call,
        .btn-video,
        .btn-end {
            transform: none !important;
            transition: background-color 0.15s ease !important;
        }

        .btn-call {
            background: var(--success);
            color: white;
        }

        .btn-video {
            background: var(--primary);
            color: white;
        }

        .btn-end {
            background: var(--danger);
            color: white;
            display: none;
        }

        /* Hover - chỉ đổi màu nền, KHÔNG transform */
        .btn-call:hover,
        .btn-video:hover,
        .btn-end:hover {
            transform: none !important;
            box-shadow: none !important;
        }

        .btn-call:hover {
            background: #059669 !important;
        }

        .btn-video:hover {
            background: #0e7490 !important;
        }

        .btn-end:hover {
            background: #dc2626 !important;
        }

        /* Active/Focus - không có hiệu ứng */
        .btn-call:active,
        .btn-video:active,
        .btn-end:active,
        .btn-call:focus,
        .btn-video:focus,
        .btn-end:focus {
            transform: none !important;
            box-shadow: none !important;
            outline: none !important;
        }

        /* Override cụ thể cho nút Gọi và Video bằng ID */
        #callPhoneBtn,
        #startVideoBtn,
        #endCallBtn {
            transform: none !important;
            transition: background-color 0.15s ease !important;
            position: relative !important;
            top: 0 !important;
            left: 0 !important;
            margin: 0 !important;
        }

        #callPhoneBtn:hover,
        #startVideoBtn:hover,
        #endCallBtn:hover {
            transform: none !important;
            top: 0 !important;
            box-shadow: inset 0 0 0 2px rgba(255,255,255,0.3) !important;
        }

        #callPhoneBtn:active,
        #startVideoBtn:active,
        #endCallBtn:active {
            transform: none !important;
            top: 0 !important;
            opacity: 0.9;
        }

        .chat-content {
            height: 500px;
            overflow-y: auto;
            padding: 24px 28px;
            background: var(--secondary);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message {
            max-width: 85%;
            padding: 14px 18px;
            border-radius: 18px;
            font-size: 1.05rem;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .message.user {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 6px;
            box-shadow: 0 2px 8px rgba(6, 182, 212, 0.2);
        }

        .message.doctor {
            background: white;
            color: #222;
            align-self: flex-start;
            border: 1px solid #e0e7ef;
            border-bottom-left-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .message.system {
            background: linear-gradient(135deg, #ecfeff, #cffafe);
            color: #0e7490;
            align-self: center;
            border-radius: 12px;
            font-size: 0.95rem;
            text-align: center;
            border: 2px solid #06b6d4;
            box-shadow: 0 2px 8px rgba(6, 182, 212, 0.15);
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6b7280;
            font-style: italic;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dots span {
            width: 6px;
            height: 6px;
            background: #6b7280;
            border-radius: 12px;
            animation: typing 1.4s infinite;
        }

        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-10px); }
        }

        .chat-input-area {
            padding: 20px 28px;
            background: white;
            border-top: 1px solid #e0e7ef;
        }

        .input-container {
            display: flex;
            gap: 12px;
            align-items: flex-end;
        }

        .input-wrapper {
            flex: 1;
            position: relative;
        }

        .chat-input {
            width: 100%;
            min-height: 56px;
            max-height: 140px;
            padding: 16px 20px;
            border: 2px solid #e0e7ef;
            border-radius: 12px;
            font-size: 1.1rem;
            font-family: inherit;
            resize: none;
            transition: border-color var(--transition);
            line-height: 1.5;
        }

        .chat-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-send {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            min-height: 56px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all var(--transition);
            box-shadow: 0 2px 8px rgba(6, 182, 212, 0.2);
        }
        
        .btn-send:hover {
            background: linear-gradient(135deg, #0e7490, #0891b2);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(6, 182, 212, 0.4);
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .sidebar-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .sidebar-card h3 {
            color: #0891b2;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .specialty-suggestions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .specialty-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: var(--secondary);
            border-radius: 12px;
            cursor: pointer;
            transition: all var(--transition);
            border: 2px solid transparent;
        }

        .specialty-item:hover {
            background: linear-gradient(135deg, #ecfeff, #cffafe);
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.2);
        }

        .specialty-item.recommended {
            background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
            border-color: var(--success);
        }
        
        .specialty-item a {
            transition: all 0.2s ease;
        }
        
        .specialty-item a:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .specialty-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 2px 8px rgba(6, 182, 212, 0.3);
        }

        .specialty-item.recommended .specialty-icon {
            background: var(--success);
        }

        .specialty-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 4px;
        }

        .specialty-info p {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .confidence-score {
            margin-left: auto;
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(6, 182, 212, 0.3);
        }

        .specialty-item.recommended .confidence-score {
            background: var(--success);
        }

        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: var(--secondary);
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            transition: all var(--transition);
            text-decoration: none;
            color: #222;
        }

        .quick-action-btn:hover {
            background: linear-gradient(135deg, #ecfeff, #cffafe);
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.2);
        }

        .action-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(6, 182, 212, 0.3);
        }

        .video-container {
            display: none;
            padding: 20px;
            background: linear-gradient(135deg, #1f2937, #111827);
            border-radius: 16px;
            margin: 16px 0;
            border: 2px solid #374151;
            position: relative;
            overflow: hidden;
        }

        .video-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            pointer-events: none;
        }

        .video-grid {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            position: relative;
            z-index: 1;
        }

        .local-video {
            width: 180px;
            height: 135px;
            background: #1f2937;
            border-radius: 12px;
            border: 3px solid #10b981;
            object-fit: cover;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            position: relative;
        }

        .local-video::after {
            content: 'Bạn';
            position: absolute;
            bottom: 8px;
            left: 8px;
            background: rgba(16, 185, 129, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .remote-video {
            flex: 1;
            height: 320px;
            background: #1f2937;
            border-radius: 12px;
            border: 3px solid #3b82f6;
            object-fit: cover;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
            position: relative;
        }

        .remote-video::after {
            content: 'BS. Trần Thị Hoa';
            position: absolute;
            bottom: 8px;
            left: 8px;
            background: rgba(59, 130, 246, 0.9);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .video-controls {
            position: absolute;
            bottom: 16px;
            right: 16px;
            display: flex;
            gap: 8px;
            z-index: 2;
        }

        .video-control-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .video-control-btn.mute {
            background: rgba(239, 68, 68, 0.8);
            color: white;
        }

        .video-control-btn.camera {
            background: rgba(59, 130, 246, 0.8);
            color: white;
        }

        .video-control-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .call-status {
            background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
            border: 2px solid var(--success);
            border-radius: 12px;
            padding: 16px;
            margin: 12px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .call-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .call-icon {
            width: 40px;
            height: 40px;
            background: var(--success);
            color: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }

        .call-details h4 {
            color: var(--success);
            font-weight: 600;
            margin-bottom: 4px;
        }

        .call-quality {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .signal-bars {
            display: flex;
            gap: 2px;
            align-items: flex-end;
        }

        .signal-bar {
            width: 4px;
            background: var(--success);
            border-radius: 2px;
        }

        .signal-bar:nth-child(1) { height: 6px; }
        .signal-bar:nth-child(2) { height: 10px; }
        .signal-bar:nth-child(3) { height: 14px; }
        .signal-bar:nth-child(4) { height: 18px; }

        .call-timer {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--success);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes ring {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .symptoms-input {
            background: #f8fafc;
            border: 2px solid #e0e7ef;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .symptoms-input h4 {
            color: #0891b2;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .symptom-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .symptom-tag {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all var(--transition);
            box-shadow: 0 2px 6px rgba(6, 182, 212, 0.2);
        }

        .symptom-tag:hover {
            background: linear-gradient(135deg, #0e7490, #0891b2);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(6, 182, 212, 0.3);
        }

        .symptom-tag.selected {
            background: var(--success);
        }

        /* Language Switcher - Modern Dropdown Style */
        .lang-switcher {
            position: relative;
            display: inline-block;
            margin-left: 15px;
            z-index: 10000;
        }
        
        .lang-switcher *,
        .lang-switcher *:focus,
        .lang-switcher *:active {
            outline: none !important;
            outline-width: 0 !important;
            outline-color: transparent !important;
        }

        .lang-switcher-btn {
            background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
            color: white;
            border: none !important;
            border-radius: 50px;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer !important;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
            transition: all 0.3s ease;
            min-width: 120px;
            justify-content: center;
            position: relative;
            z-index: 10001;
            pointer-events: auto !important;
            outline: none !important;
            outline-offset: 0 !important;
            -webkit-tap-highlight-color: transparent !important;
        }

        .lang-switcher-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
        }
        
        .lang-switcher-btn:focus,
        .lang-switcher-btn:focus-visible,
        .lang-switcher-btn:focus-within {
            outline: none !important;
            outline-width: 0 !important;
            outline-color: transparent !important;
            box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3) !important;
            border: none !important;
        }
        
        .lang-switcher-btn:active {
            transform: scale(0.98);
            outline: none !important;
        }

        .lang-switcher-btn i {
            font-size: 1.2rem;
            pointer-events: none;
        }

        .lang-switcher-btn .arrow {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
            pointer-events: none;
        }
        
        .lang-switcher-btn span {
            pointer-events: none;
        }

        .lang-switcher.active .lang-switcher-btn .arrow {
            transform: rotate(180deg);
        }

        .lang-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            min-width: 280px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 99999 !important;
            overflow: hidden;
        }

        .lang-switcher.active .lang-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .lang-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .lang-option:last-child {
            border-bottom: none;
        }

        .lang-option:hover {
            background: linear-gradient(90deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .lang-option.active {
            background: linear-gradient(90deg, #dbeafe 0%, #bfdbfe 100%);
        }

        .lang-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .lang-icon.vi {
            background: linear-gradient(135deg, #22d3ee 0%, #06b6d4 100%);
            color: white;
        }

        .lang-icon.km {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .lang-text {
            flex: 1;
        }

        .lang-code {
            font-size: 0.9rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .lang-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        /* CRITICAL: Force remove ALL outlines - Final Override */
        .lang-switcher *,
        .lang-switcher button,
        .lang-switcher-btn,
        #langSwitcherBtn {
            outline: none !important;
            outline-width: 0 !important;
            outline-color: transparent !important;
            outline-style: none !important;
            outline-offset: 0 !important;
            border: none !important;
            -webkit-tap-highlight-color: transparent !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            appearance: none !important;
        }
        
        .lang-switcher *:focus,
        .lang-switcher *:active,
        .lang-switcher *:focus-visible,
        .lang-switcher button:focus,
        .lang-switcher button:active,
        .lang-switcher-btn:focus,
        .lang-switcher-btn:active,
        #langSwitcherBtn:focus,
        #langSwitcherBtn:active {
            outline: none !important;
            outline-width: 0 !important;
            outline-color: transparent !important;
            outline-style: none !important;
            border: none !important;
        }
        
        /* Review Modal */
        .review-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: fadeIn 0.3s ease;
        }
        
        .review-modal.active {
            display: flex;
        }
        
        .review-content {
            background: white;
            border-radius: 20px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .review-header {
            padding: 24px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            border-radius: 20px 20px 0 0;
        }
        
        .review-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .review-body {
            padding: 24px;
        }
        
        .review-doctor-info {
            text-align: center;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .review-doctor-info h3 {
            color: #0891b2;
            margin: 10px 0 5px 0;
        }
        
        .review-doctor-info p {
            color: #6b7280;
            margin: 0;
        }
        
        .review-group {
            margin-bottom: 20px;
        }
        
        .review-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
        }
        
        .star-rating {
            display: flex;
            gap: 8px;
            font-size: 2rem;
            justify-content: center;
            margin: 10px 0;
        }
        
        .star {
            cursor: pointer;
            color: #d1d5db;
            transition: all 0.2s ease;
        }
        
        .star:hover,
        .star.active {
            color: #fbbf24;
            transform: scale(1.1);
        }
        
        .rating-bars {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .rating-bar {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .rating-bar label {
            min-width: 120px;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .rating-bar input[type="range"] {
            flex: 1;
            height: 8px;
            border-radius: 4px;
            outline: none;
            -webkit-appearance: none;
            background: #e5e7eb;
        }
        
        .rating-bar input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 12px;
            background: #0891b2;
            cursor: pointer;
        }
        
        .rating-bar .rating-value {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
            color: #0891b2;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
        }
        
        textarea:focus {
            outline: none;
            border-color: #0891b2;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .review-footer {
            padding: 20px 24px;
            border-top: 2px solid #e5e7eb;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .btn-review {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-review-submit {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
        }
        
        .btn-review-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(6, 182, 212, 0.4);
        }
        
        .btn-review-cancel {
            background: #6b7280;
            color: white;
        }
        
        .btn-review-cancel:hover {
            background: #4b5563;
        }

        @media (max-width: 768px) {
            .consultation-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .chat-content {
                height: 400px;
            }
            
            .sidebar {
                order: -1;
            }
            
            .lang-dropdown {
                min-width: 240px;
                right: -10px;
            }
            
            .lang-switcher-btn {
                padding: 10px 20px;
                font-size: 0.9rem;
                min-width: 100px;
            }
            
            .lang-switcher {
                margin-left: 8px;
            }
        }

        /* Beautiful Footer Styles */
        .beautiful-footer {
            background: 
                radial-gradient(ellipse at top left, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at bottom right, rgba(6, 182, 212, 0.1) 0%, transparent 50%),
                linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #06b6d4 100%);
            color: #ffffff;
            position: relative;
            overflow: hidden;
            margin-top: 100px;
            box-shadow: 
                0 -25px 50px rgba(14, 165, 233, 0.2),
                0 -15px 30px rgba(0, 0, 0, 0.3);
        }

        .footer-wave {
            position: absolute;
            top: -1px;
            left: 0;
            width: 100%;
            height: 120px;
            z-index: 2;
        }

        .footer-wave svg {
            width: 100%;
            height: 100%;
        }

        .beautiful-footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="footer-medical" width="60" height="60" patternUnits="userSpaceOnUse"><g fill="rgba(255, 255, 255, 0.05)"><circle cx="15" cy="15" r="1"/><circle cx="45" cy="45" r="0.8"/><rect x="25" y="20" width="10" height="20" rx="2"/><rect x="20" y="25" width="20" height="10" rx="2"/></g></pattern></defs><rect width="60" height="60" fill="url(%23footer-medical)"/></svg>');
            background-size: 120px 120px;
            opacity: 0.3;
            pointer-events: none;
            z-index: 1;
        }

        .footer-content {
            padding: 80px 0 40px;
            position: relative;
            z-index: 2;
        }

        .beautiful-footer h2,
        .beautiful-footer h3,
        .beautiful-footer h4,
        .beautiful-footer p,
        .beautiful-footer a,
        .beautiful-footer span {
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .beautiful-footer i {
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
        }

        .footer-main-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
            gap: 60px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
        }

        .footer-brand {
            max-width: 450px;
        }

        .brand-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
        }

        .brand-info h2 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
            color: #ffffff;
        }

        .brand-info p {
            font-size: 0.9rem;
            color: #cbd5e1;
            margin: 0;
            font-weight: 500;
            letter-spacing: 1px;
        }

        .brand-desc {
            color: #cbd5e1;
            line-height: 1.7;
            margin-bottom: 30px;
            font-size: 1.05rem;
        }

        .brand-stats {
            display: flex;
            gap: 30px;
            margin-top: 25px;
        }

        .brand-stats .stat {
            text-align: center;
        }

        .brand-stats .number {
            display: block;
            font-size: 1.8rem;
            font-weight: 800;
            color: #0ea5e9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }

        .brand-stats .label {
            font-size: 0.9rem;
            color: #cbd5e1;
            font-weight: 500;
        }

        .footer-column h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #ffffff;
        }

        .footer-column h3 i {
            color: #0ea5e9;
            margin-right: 10px;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-column li {
            margin-bottom: 15px;
        }

        .footer-column a {
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-column a i {
            width: 18px;
            color: #0ea5e9;
            font-size: 0.9rem;
            font-style: normal;
        }

        .footer-column a:hover {
            color: #ffffff;
            transform: translateX(8px);
        }

        .footer-column a:hover i {
            color: #06b6d4;
            transform: scale(1.2);
        }

        .contact-info {
            margin-bottom: 30px;
        }

        .contact-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: flex-start;
        }

        .contact-item i {
            font-size: 1.3rem;
            margin-top: 3px;
            color: #0ea5e9;
        }

        .contact-item strong {
            display: block;
            margin-bottom: 5px;
            color: #ffffff;
        }

        .social-section h4 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #ffffff;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .social.facebook {
            background: #1877f2;
        }

        .social.youtube {
            background: #ff0000;
        }

        .social.zalo {
            background: #0068ff;
        }

        .social.instagram {
            background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }

        .social:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .footer-bottom {
            background: rgba(0, 0, 0, 0.3);
            padding: 25px 0;
            position: relative;
            z-index: 2;
        }

        .bottom-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-bottom p {
            margin: 0;
            color: #cbd5e1;
            font-size: 0.95rem;
        }

        .bottom-links {
            display: flex;
            gap: 30px;
        }

        .bottom-links a {
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .bottom-links a:hover {
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .footer-main-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .brand-stats {
                justify-content: space-around;
            }

            .bottom-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .bottom-links {
                flex-direction: column;
                gap: 10px;
            }
        }

        /* === FINAL OVERRIDE - NÚT GỌI VÀ VIDEO KHÔNG NHẢY === */
        .call-controls .btn,
        .call-controls .btn-call,
        .call-controls .btn-video,
        .call-controls .btn-end,
        #callPhoneBtn,
        #startVideoBtn,
        #endCallBtn,
        button.btn-call,
        button.btn-video,
        button.btn-end {
            transform: none !important;
            transition: none !important;
            animation: none !important;
            position: static !important;
        }

        .call-controls .btn:hover,
        .call-controls .btn-call:hover,
        .call-controls .btn-video:hover,
        #callPhoneBtn:hover,
        #startVideoBtn:hover,
        #endCallBtn:hover,
        button.btn-call:hover,
        button.btn-video:hover {
            transform: none !important;
            animation: none !important;
            filter: brightness(1.15);
        }

        .call-controls .btn:active,
        #callPhoneBtn:active,
        #startVideoBtn:active,
        button.btn-call:active,
        button.btn-video:active {
            transform: none !important;
            filter: brightness(0.9);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <a href="TrangChu.php" class="logo">
                <img src="image\logo.png.png" alt="Logo">
                <span>KhamCare</span>
            </a>
            
            <nav class="nav-menu">
                <a href="TimKiemBS.php" class="nav-link" id="navSearch">Tìm bác sĩ</a>
                <a href="ChuyenKhoa.php" class="nav-link" id="navSpecialty">Chuyên khoa</a>
                <a href="TuVanTrucTuyen.php" class="nav-link active" id="navConsult">Tư vấn</a>
                <a href="Camnangsuckhoe.php" class="nav-link" id="navGuide">Cẩm nang Sức khỏe</a>
                <a href="doctor_auth.php" class="nav-link doctor-link" id="navDoctor">
                    <i class="fas fa-user-md"></i> <span id="navDoctorText">Dành cho Bác sĩ</span>
                </a>
            </nav>
            
            <div class="header-actions">
                <div class="account-dropdown-wrapper" id="accountDropdown">
                    <button class="btn-account-dropdown" onclick="toggleAccountDropdown(event)">
                        <i class="fas fa-user"></i>
                        <span id="navAccount">Tài khoản</span>
                        <i class="fas fa-chevron-down arrow"></i>
                    </button>
                    <div class="account-dropdown-menu">
                        <a href="tk.php" class="account-dropdown-item">
                            <i class="fas fa-user-circle"></i>
                            <span>Hồ sơ của tôi</span>
                        </a>
                        <a href="DatLichK.php" class="account-dropdown-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Lịch hẹn</span>
                        </a>
                        <a href="TuVanTrucTuyen.php" class="account-dropdown-item">
                            <i class="fas fa-comments"></i>
                            <span>Tư vấn trực tuyến</span>
                        </a>
                        <a href="logout.php" class="account-dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Đăng xuất</span>
                        </a>
                    </div>
                </div>
            
            <div class="user-info" style="display: none;">
                <i class="fas fa-user-circle" style="font-size: 1.5rem; color: #0891b2;"></i>
                <span><?= htmlspecialchars($user['full_name']) ?></span>
                
                <!-- Language Switcher -->
                <div class="lang-switcher" id="langSwitcher">
                    <button class="lang-switcher-btn" id="langSwitcherBtn" type="button" 
                        style="outline: none !important; border: none !important; outline-width: 0 !important; outline-color: transparent !important; -webkit-tap-highlight-color: transparent !important; box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3) !important;"
                        onclick="document.getElementById('langSwitcher').classList.toggle('active'); event.stopPropagation();">
                        <i class="fas fa-globe"></i>
                        <span id="currentLangText">VN</span>
                        <i class="fas fa-chevron-down arrow"></i>
                    </button>
                    
                    <div class="lang-dropdown" id="langDropdown">
                        <div class="lang-option active" data-lang="vi" onclick="selectLanguage('vi'); event.stopPropagation();">
                            <div class="lang-icon vi">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="lang-text">
                                <div class="lang-code">VN</div>
                                <div class="lang-name">Tiếng Việt</div>
                            </div>
                        </div>
                        
                        <div class="lang-option" data-lang="km" onclick="selectLanguage('km'); event.stopPropagation();">
                            <div class="lang-icon km">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="lang-text">
                                <div class="lang-code">KH</div>
                                <div class="lang-name">ភាសាខ្មែរ</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden select for compatibility -->
                    <select id="langSelect" style="display: none;">
                        <option value="vi">Tiếng Việt</option>
                        <option value="km">ភាសាខ្មែរ</option>
                    </select>
                </div>
            </div>
            </div>
        </div>
    </div>

    <script>
    // Inline language switcher script
    function selectLanguage(lang) {
        console.log('🌐 Language selected:', lang);
        
        // Update button text
        const langMap = { 'vi': 'VN', 'km': 'KH' };
        document.getElementById('currentLangText').textContent = langMap[lang];
        
        // Update active state
        document.querySelectorAll('.lang-option').forEach(opt => {
            opt.classList.remove('active');
            if (opt.getAttribute('data-lang') === lang) {
                opt.classList.add('active');
            }
        });
        
        // Close dropdown
        document.getElementById('langSwitcher').classList.remove('active');
        
        // Update hidden select
        const hiddenSelect = document.getElementById('langSelect');
        if (hiddenSelect) {
            hiddenSelect.value = lang;
        }
        
        // Save to localStorage
        localStorage.setItem('kham_lang', lang);
        
        // Apply language to entire page using global language system
        if (typeof applyGlobalLang === 'function') {
            applyGlobalLang(lang);
        } else {
            console.warn('⚠️ applyGlobalLang function not found. Make sure language-system.js is loaded.');
        }
        
        // Call language change handler (for backward compatibility)
        if (window.handleLanguageChange) {
            window.handleLanguageChange(lang);
        }
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const switcher = document.getElementById('langSwitcher');
        if (switcher && !switcher.contains(e.target)) {
            switcher.classList.remove('active');
        }
    });
    
    // Force remove outline and keep blue box-shadow on button
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('langSwitcherBtn');
        if (btn) {
            const blueBoxShadow = '0 4px 15px rgba(6, 182, 212, 0.3)';
            
            btn.addEventListener('focus', function() {
                this.style.outline = 'none';
                this.style.outlineWidth = '0';
                this.style.outlineColor = 'transparent';
                this.style.border = 'none';
                this.style.boxShadow = blueBoxShadow;
            });
            btn.addEventListener('click', function() {
                this.style.outline = 'none';
                this.style.border = 'none';
                this.style.boxShadow = blueBoxShadow;
            });
            btn.addEventListener('mousedown', function() {
                this.style.boxShadow = blueBoxShadow;
            });
            btn.addEventListener('mouseup', function() {
                this.style.boxShadow = blueBoxShadow;
            });
        }
    });
    
    // Set initial language
    (function() {
        const savedLang = localStorage.getItem('kham_lang') || 'vi';
        const langMap = { 'vi': 'VN', 'km': 'KH' };
        const currentLangText = document.getElementById('currentLangText');
        if (currentLangText) {
            currentLangText.textContent = langMap[savedLang];
        }
        
        // Update active option
        document.querySelectorAll('.lang-option').forEach(opt => {
            if (opt.getAttribute('data-lang') === savedLang) {
                opt.classList.add('active');
            } else {
                opt.classList.remove('active');
            }
        });
    })();
    </script>

    <!-- Main Content -->
    <div class="main-content">
        <div class="consultation-container">
            <!-- Chat Section -->
            <div class="chat-section">
                <div class="chat-header">
                    <div class="chat-title">
                        <i class="fas fa-stethoscope"></i>
                        <span id="consultationTitle">Tư vấn & Gợi ý Chuyên khoa</span>
                    </div>
                    
                    <div class="call-controls">
                        <button id="callPhoneBtn" class="btn btn-call" style="transform: none !important; transition: background-color 0.15s !important;">
                            <i class="fas fa-phone"></i> <span id="callText">Gọi</span>
                        </button>
                        <button id="startVideoBtn" class="btn btn-video" style="transform: none !important; transition: background-color 0.15s !important;">
                            <i class="fas fa-video"></i> <span id="videoText">Video</span>
                        </button>
                        <button id="endCallBtn" class="btn btn-end" style="transform: none !important; transition: background-color 0.15s !important;">
                            <i class="fas fa-phone-slash"></i> <span id="endCallText">Kết thúc</span>
                        </button>
                    </div>
                </div>

                <div id="chatContent" class="chat-content">
                    <div class="message system">
                        <i class="fas fa-robot"></i> <span id="systemReady">Hệ thống AI y tế đã sẵn sàng hỗ trợ bạn</span>
                    </div>
                    <div class="message doctor">
                        Xin chào <strong><?= htmlspecialchars($user['full_name']) ?></strong>! 👋<br><br>
                        <span id="aiIntro">Tôi là trợ lý AI y tế của KhamCare. Tôi sẽ giúp bạn:</span>
                        <ul style="margin: 12px 0; padding-left: 20px;">
                            <li id="aiFeature1">Phân tích triệu chứng và gợi ý chuyên khoa phù hợp</li>
                            <li id="aiFeature2">Tư vấn sơ bộ về tình trạng sức khỏe</li>
                            <li id="aiFeature3">Kết nối với bác sĩ chuyên khoa khi cần thiết</li>
                        </ul>
                        <span id="aiPrompt">Hãy mô tả triệu chứng hoặc vấn đề sức khỏe mà bạn đang gặp phải nhé! 🏥</span>
                    </div>
                </div>

                <div id="videoContainer" class="video-container">
                    <div class="video-grid">
                        <video id="localVideo" class="local-video" autoplay muted playsinline></video>
                        <video id="remoteVideo" class="remote-video" autoplay playsinline></video>
                    </div>
                    <div class="video-controls">
                        <button id="muteBtn" class="video-control-btn mute" title="Tắt/Bật microphone">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button id="cameraBtn" class="video-control-btn camera" title="Tắt/Bật camera">
                            <i class="fas fa-video"></i>
                        </button>
                    </div>
                </div>

                <div class="chat-input-area">
                    <!-- Toggle KhamCare AI -->
                    <div class="gemini-toggle" style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; padding: 10px 16px; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border-radius: 12px; border: 2px solid #10b981;">
                        <label class="toggle-switch" style="position: relative; display: inline-block; width: 50px; height: 26px;">
                            <input type="checkbox" id="geminiToggle" checked style="opacity: 0; width: 0; height: 0;">
                            <span class="toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 26px;"></span>
                        </label>
                        <div style="flex: 1;">
                            <span style="font-weight: 600; color: #10b981; display: flex; align-items: center; gap: 6px;">
                                <i class="fas fa-robot"></i> 
                                <span id="geminiLabel">KhamCare AI</span>
                            </span>
                            <span style="font-size: 0.85rem; color: #6b7280;" id="geminiDesc">Trả lời thông minh với AI</span>
                        </div>
                        <span id="geminiStatus" style="font-size: 0.8rem; padding: 4px 10px; border-radius: 20px; background: #10b981; color: white;">BẬT</span>
                    </div>
                    
                    <style>
                        .toggle-switch input:checked + .toggle-slider {
                            background: linear-gradient(135deg, #10b981, #059669);
                        }
                        .toggle-switch input:checked + .toggle-slider:before {
                            transform: translateX(24px);
                        }
                        .toggle-slider:before {
                            position: absolute;
                            content: "";
                            height: 20px;
                            width: 20px;
                            left: 3px;
                            bottom: 3px;
                            background-color: white;
                            transition: .4s;
                            border-radius: 50%;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                        }
                    </style>
                    
                    <div class="input-container">
                        <div class="input-wrapper">
                            <textarea 
                                id="chatInput" 
                                class="chat-input" 
                                placeholder="Mô tả triệu chứng của bạn... (VD: Tôi bị đau đầu, sốt nhẹ và ho khan từ 2 ngày nay)"
                                rows="1"
                            ></textarea>
                        </div>
                        <button id="sendBtn" class="btn btn-send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Gợi ý triệu chứng -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-lightbulb"></i> <span id="symptomSuggestions">Gợi ý triệu chứng</span></h3>
                    <div class="symptoms-input">
                        <h4><i class="fas fa-tags"></i> <span id="commonSymptoms">Triệu chứng phổ biến</span></h4>
                        <div class="symptom-tags">
                            <span class="symptom-tag" onclick="addSymptom('đau đầu', event)" id="symptomHeadache">Đau đầu</span>
                            <span class="symptom-tag" onclick="addSymptom('sốt', event)" id="symptomFever">Sốt</span>
                            <span class="symptom-tag" onclick="addSymptom('ho', event)" id="symptomCough">Ho</span>
                            <span class="symptom-tag" onclick="addSymptom('đau bụng', event)" id="symptomStomachache">Đau bụng</span>
                            <span class="symptom-tag" onclick="addSymptom('buồn nôn', event)" id="symptomNausea">Buồn nôn</span>
                            <span class="symptom-tag" onclick="addSymptom('chóng mặt', event)" id="symptomDizziness">Chóng mặt</span>
                            <span class="symptom-tag" onclick="addSymptom('mệt mỏi', event)" id="symptomFatigue">Mệt mỏi</span>
                            <span class="symptom-tag" onclick="addSymptom('đau ngực', event)" id="symptomChestPain">Đau ngực</span>
                        </div>
                    </div>
                </div>

                <!-- Gợi ý chuyên khoa -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-user-md"></i> <span id="specialtySuggestionsTitle">Gợi ý chuyên khoa</span></h3>
                    <div id="specialtySuggestions" class="specialty-suggestions">
                        <div class="message system" style="text-align: center; padding: 20px;">
                            <i class="fas fa-info-circle"></i><br>
                            <span id="enterSymptomsPrompt">Nhập triệu chứng để nhận gợi ý chuyên khoa phù hợp</span>
                        </div>
                    </div>
                </div>

                <!-- Hành động nhanh -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-bolt"></i> <span id="quickActions">Hành động nhanh</span></h3>
                    <div class="quick-actions">
                        <a href="DatLichK.php" class="quick-action-btn">
                            <div class="action-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <div>
                                <h4 id="bookingTitle2">Đặt lịch khám</h4>
                                <p id="bookingDesc">Đặt lịch với bác sĩ chuyên khoa</p>
                            </div>
                        </a>
                        
                        <div class="quick-action-btn" onclick="startEmergencyCall()">
                            <div class="action-icon" style="background: var(--danger);">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div>
                                <h4 id="emergencyTitle">Gọi cấp cứu</h4>
                                <p id="emergencyHotline">Hotline: 115 - 24/7</p>
                            </div>
                        </div>
                        
                        <div class="quick-action-btn" onclick="showHealthTips()">
                            <div class="action-icon" style="background: var(--success);">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div>
                                <h4 id="healthTipsTitle">Mẹo sức khỏe</h4>
                                <p id="healthTipsDesc">Lời khuyên từ chuyên gia</p>
                            </div>
                        </div>
                        
                        <a href="chatbot_ai.php" class="quick-action-btn" style="text-decoration: none; color: inherit; display: flex; cursor: pointer;">
                            <div class="action-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div>
                                <h4>🤖 KhamCare AI</h4>
                                <p>Tư vấn với AI thông minh</p>
                            </div>
                        </a>
                        
                        <div class="quick-action-btn" onclick="showConsultationHistory()">
                            <div class="action-icon" style="background: #8b5cf6;">
                                <i class="fas fa-history"></i>
                            </div>
                            <div>
                                <h4 id="historyTitle">Lịch sử tư vấn</h4>
                                <p id="historyDesc">Xem các cuộc tư vấn trước</p>
                            </div>
                        </div>
                        
                        <div class="quick-action-btn" onclick="confirmClearChat()">
                            <div class="action-icon" style="background: var(--warning);">
                                <i class="fas fa-trash-alt"></i>
                            </div>
                            <div>
                                <h4 id="clearChatTitle">Xóa lịch sử chat</h4>
                                <p id="clearChatDesc">Bắt đầu cuộc tư vấn mới</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    </style>

    <script>
        // Biến global
        var currentUser = {
            id: <?= $user['id'] ?>,
            name: "<?= htmlspecialchars($user['full_name']) ?>"
        };

        var callState = {
            isActive: false,
            type: null,
            timer: null,
            startTime: null,
            doctorInfo: null
        };

        var selectedSymptoms = [];
        var currentSuggestions = [];
        var currentConsultationId = null; // ID của phiên tư vấn hiện tại

        // Lưu lịch sử chat vào localStorage (phiên hiện tại)
        function saveChatHistory() {
            var chatContent = document.getElementById('chatContent');
            if (!chatContent) return;
            
            var messages = [];
            var messageElements = chatContent.querySelectorAll('.message:not(.typing-indicator)');
            
            messageElements.forEach(function(msg) {
                var sender = msg.getAttribute('data-sender') || msg.className.split(' ')[1];
                var isHtml = msg.getAttribute('data-is-html') === 'true';
                var content = isHtml ? msg.innerHTML : msg.textContent;
                
                // Bỏ qua tin nhắn hệ thống ban đầu
                if (sender === 'system' && content.includes('Hệ thống AI')) {
                    return;
                }
                
                messages.push({
                    sender: sender,
                    content: content,
                    isHtml: isHtml,
                    timestamp: Date.now()
                });
            });
            
            localStorage.setItem('kham_chat_history', JSON.stringify(messages));
            console.log('💾 Chat history saved:', messages.length, 'messages');
        }
        
        // Lưu phiên tư vấn vào database
        function saveConsultationSession() {
            console.log('🔄 saveConsultationSession() called');
            
            var chatContent = document.getElementById('chatContent');
            if (!chatContent) {
                console.error('❌ chatContent not found');
                return;
            }
            
            var messages = [];
            var messageElements = chatContent.querySelectorAll('.message:not(.typing-indicator)');
            
            console.log('📊 Found', messageElements.length, 'messages');
            
            // Chỉ lưu nếu có ít nhất 3 tin nhắn (system + intro + user message)
            if (messageElements.length < 3) {
                console.log('⚠️ Not enough messages to save session (need at least 3, got', messageElements.length, ')');
                return;
            }
            
            messageElements.forEach(function(msg) {
                var sender = msg.getAttribute('data-sender') || msg.className.split(' ')[1];
                var isHtml = msg.getAttribute('data-is-html') === 'true';
                var content = isHtml ? msg.innerHTML : msg.textContent;
                
                messages.push({
                    sender: sender,
                    content: content,
                    isHtml: isHtml,
                    timestamp: Date.now()
                });
            });
            
            // Tạo session_id duy nhất (hoặc lấy từ biến global nếu đã có)
            if (!window.currentSessionId) {
                window.currentSessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }
            
            // Kiểm tra xem session này đã được lưu chưa
            if (window.sessionSaved) {
                console.log('⚠️ Session already saved, skipping...');
                return;
            }
            
            var currentLang = localStorage.getItem('kham_lang') || 'vi';
            var summary = extractSessionSummary(messages);
            
            console.log('📝 Saving session:', {
                session_id: window.currentSessionId,
                language: currentLang,
                summary: summary,
                message_count: messages.length
            });
            
            // Đánh dấu đang lưu
            window.sessionSaved = true;
            
            // Gửi lên server
            fetch('api_consultation_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=save&session_id=' + encodeURIComponent(window.currentSessionId) +
                      '&language=' + encodeURIComponent(currentLang) +
                      '&summary=' + encodeURIComponent(summary) +
                      '&messages=' + encodeURIComponent(JSON.stringify(messages))
            })
            .then(response => {
                console.log('📡 Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📥 Response data:', data);
                if (data.success) {
                    console.log('✅ Consultation session saved to database:', window.currentSessionId);
                } else {
                    console.error('❌ Failed to save session:', data.message);
                    if (data.error_details) {
                        console.error('Error details:', data.error_details);
                    }
                }
            })
            .catch(error => {
                console.error('❌ Error saving session:', error);
            });
        }
        
        // Trích xuất tóm tắt phiên tư vấn
        function extractSessionSummary(messages) {
            // Tìm tin nhắn đầu tiên của user
            var userMsg = messages.find(function(msg) {
                return msg.sender === 'user';
            });
            
            if (userMsg) {
                var summary = userMsg.content;
                // Giới hạn 100 ký tự
                if (summary.length > 100) {
                    summary = summary.substring(0, 100) + '...';
                }
                return summary;
            }
            
            return 'Cuộc tư vấn';
        }
        
        // Khôi phục lịch sử chat từ localStorage
        function restoreChatHistory() {
            var savedHistory = localStorage.getItem('kham_chat_history');
            if (!savedHistory) {
                console.log('📭 No chat history found');
                return;
            }
            
            try {
                var messages = JSON.parse(savedHistory);
                var chatContent = document.getElementById('chatContent');
                if (!chatContent) return;
                
                // Xóa tin nhắn mặc định (giữ lại tin nhắn system và doctor intro)
                var defaultMessages = chatContent.querySelectorAll('.message');
                if (defaultMessages.length > 2) {
                    // Chỉ xóa nếu có nhiều hơn 2 tin nhắn mặc định
                    return;
                }
                
                // Kiểm tra nếu có lịch sử trong vòng 24 giờ
                var now = Date.now();
                var recentMessages = messages.filter(function(msg) {
                    return msg.timestamp && (now - msg.timestamp) < 24 * 60 * 60 * 1000;
                });
                
                if (recentMessages.length === 0) {
                    console.log('🗑️ Chat history expired, clearing...');
                    localStorage.removeItem('kham_chat_history');
                    return;
                }
                
                // Khôi phục tin nhắn
                recentMessages.forEach(function(msg) {
                    addMessage(msg.sender, msg.content, msg.isHtml);
                });
                
                console.log('✅ Chat history restored:', recentMessages.length, 'messages');
            } catch (error) {
                console.error('❌ Error restoring chat history:', error);
                localStorage.removeItem('kham_chat_history');
            }
        }
        
        // Xóa lịch sử chat (phiên hiện tại)
        function clearChatHistory() {
            // Lưu phiên hiện tại trước khi xóa
            saveConsultationSession();
            
            localStorage.removeItem('kham_chat_history');
            console.log('🗑️ Chat history cleared');
        }
        
        // Hiển thị modal lịch sử tư vấn
        function showConsultationHistory() {
            console.log('🔍 showConsultationHistory() called');
            var currentLang = localStorage.getItem('kham_lang') || 'vi';
            
            // Lấy danh sách từ database
            console.log('📡 Fetching: api_consultation_history.php?action=list&limit=20');
            fetch('api_consultation_history.php?action=list&limit=20')
            .then(response => {
                console.log('📥 Response status:', response.status);
                console.log('📥 Response ok:', response.ok);
                return response.text();
            })
            .then(text => {
                console.log('📄 Response text:', text);
                try {
                    var data = JSON.parse(text);
                    console.log('✅ Parsed data:', data);
                    
                    if (!data.success) {
                        throw new Error(data.message || 'API returned success=false');
                    }
                    
                    var sessions = data.sessions || [];
                    console.log('📊 Sessions count:', sessions.length);
                    
                    if (sessions.length === 0) {
                        var msg = currentLang === 'km' ? 
                            'មិនមានប្រវត្តិការពិគ្រោះយោបល់ទេ។' :
                            'Chưa có lịch sử tư vấn nào.';
                        showNotification(msg, 'info');
                        return;
                    }
                    
                    displayHistoryModal(sessions, currentLang);
                } catch (parseError) {
                    console.error('❌ JSON parse error:', parseError);
                    console.error('Raw text:', text);
                    throw parseError;
                }
            })
            .catch(error => {
                console.error('❌ Error loading history:', error);
                var msg = currentLang === 'km' ? 
                    'មានកំហុសក្នុងការផ្ទុកប្រវត្តិ។ សូមពិនិត្យ Console (F12)' :
                    'Có lỗi khi tải lịch sử. Vui lòng xem Console (F12)';
                showNotification(msg, 'error');
            });
        }
        
        // Hiển thị modal với danh sách sessions
        function displayHistoryModal(sessions, currentLang) {
            
            // Tạo modal
            var modal = document.createElement('div');
            modal.id = 'historyModal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                animation: fadeIn 0.3s ease;
            `;
            
            var modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: white;
                border-radius: 20px;
                max-width: 800px;
                width: 100%;
                max-height: 80vh;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            `;
            
            var title = currentLang === 'km' ? 'ប្រវត្តិការពិគ្រោះយោបល់' : 'Lịch sử tư vấn';
            var closeText = currentLang === 'km' ? 'បិទ' : 'Đóng';
            
            var header = `
                <div style="padding: 24px; border-bottom: 2px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0; color: #0891b2; font-size: 1.5rem;">
                        <i class="fas fa-history"></i> ${title}
                    </h2>
                    <button onclick="closeHistoryModal()" style="background: none; border: none; font-size: 1.5rem; color: #6b7280; cursor: pointer; padding: 5px 10px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            var sessionsList = '<div style="padding: 20px; overflow-y: auto; flex: 1;">';
            
            sessions.forEach(function(session, index) {
                var date = new Date(session.created_at);
                var dateStr = date.toLocaleDateString(currentLang === 'km' ? 'km-KH' : 'vi-VN', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                var langIcon = session.language === 'km' ? '🇰🇭' : '🇻🇳';
                var viewText = currentLang === 'km' ? 'មើល' : 'Xem';
                var deleteText = currentLang === 'km' ? 'លុប' : 'Xóa';
                
                sessionsList += `
                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; margin-bottom: 12px; border-left: 4px solid #0891b2;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #222; margin-bottom: 4px;">
                                    ${langIcon} ${session.summary}
                                </div>
                                <div style="font-size: 0.85rem; color: #6b7280;">
                                    <i class="fas fa-clock"></i> ${dateStr}
                                </div>
                            </div>
                            <div style="display: flex; gap: 8px;">
                                <button onclick="viewConsultationSession('${session.session_id}')" 
                                    style="background: #0891b2; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">
                                    <i class="fas fa-eye"></i> ${viewText}
                                </button>
                                <button onclick="deleteConsultationSession('${session.session_id}')" 
                                    style="background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 0.85rem;">
                                    <i class="fas fa-trash"></i> ${deleteText}
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            sessionsList += '</div>';
            
            var footer = `
                <div style="padding: 16px; border-top: 2px solid #e5e7eb; text-align: right;">
                    <button onclick="closeHistoryModal()" 
                        style="background: #6b7280; color: white; border: none; padding: 12px 24px; border-radius: 10px; cursor: pointer; font-weight: 600;">
                        ${closeText}
                    </button>
                </div>
            `;
            
            modalContent.innerHTML = header + sessionsList + footer;
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
        }
        
        // Đóng modal lịch sử
        function closeHistoryModal() {
            var modal = document.getElementById('historyModal');
            if (modal) {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(function() {
                    modal.remove();
                }, 300);
            }
        }
        
        // Xem phiên tư vấn
        function viewConsultationSession(sessionId) {
            // Lưu phiên hiện tại trước
            saveConsultationSession();
            
            // Lấy chi tiết phiên từ database
            fetch('api_consultation_history.php?action=get&session_id=' + encodeURIComponent(sessionId))
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message);
                }
                
                var session = data.session;
                
                // Xóa chat hiện tại
                var chatContent = document.getElementById('chatContent');
                if (chatContent) {
                    chatContent.innerHTML = '';
                    
                    // Khôi phục tin nhắn từ phiên cũ
                    session.messages.forEach(function(msg) {
                        var messageDiv = document.createElement('div');
                        messageDiv.className = 'message ' + msg.sender;
                        messageDiv.setAttribute('data-sender', msg.sender);
                        messageDiv.setAttribute('data-is-html', msg.isHtml ? 'true' : 'false');
                        
                        if (msg.isHtml) {
                            messageDiv.innerHTML = msg.content;
                        } else {
                            messageDiv.textContent = msg.content;
                        }
                        
                        chatContent.appendChild(messageDiv);
                    });
                    
                    chatContent.scrollTop = chatContent.scrollHeight;
                }
                
                // Đặt session_id hiện tại để tiếp tục lưu vào phiên này
                window.currentSessionId = sessionId;
                
                closeHistoryModal();
                
                var currentLang = localStorage.getItem('kham_lang') || 'vi';
                var msg = currentLang === 'km' ? 
                    'បានស្តារការពិគ្រោះយោបល់ពីមុន។' :
                    'Đã khôi phục phiên tư vấn.';
                showNotification(msg, 'success');
            })
            .catch(error => {
                console.error('Error loading session:', error);
                var currentLang = localStorage.getItem('kham_lang') || 'vi';
                var msg = currentLang === 'km' ? 
                    'មានកំហុសក្នុងការផ្ទុកការពិគ្រោះយោបល់។' :
                    'Có lỗi khi tải phiên tư vấn.';
                showNotification(msg, 'error');
            });
        }
        
        // Xóa phiên tư vấn
        function deleteConsultationSession(sessionId) {
            var currentLang = localStorage.getItem('kham_lang') || 'vi';
            var confirmMsg = currentLang === 'km' ? 
                'តើអ្នកប្រាកដថាចង់លុបការពិគ្រោះយោបល់នេះទេ?' :
                'Bạn có chắc chắn muốn xóa phiên tư vấn này?';
            
            if (!confirm(confirmMsg)) return;
            
            // Xóa từ database
            fetch('api_consultation_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete&session_id=' + encodeURIComponent(sessionId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Refresh modal
                    closeHistoryModal();
                    setTimeout(function() {
                        showConsultationHistory();
                    }, 300);
                    
                    var msg = currentLang === 'km' ? 
                        'បានលុបការពិគ្រោះយោបល់។' :
                        'Đã xóa phiên tư vấn.';
                    showNotification(msg, 'success');
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error deleting session:', error);
                var msg = currentLang === 'km' ? 
                    'មានកំហុសក្នុងការលុប។' :
                    'Có lỗi khi xóa.';
                showNotification(msg, 'error');
            });
        }

        // Khởi tạo khi DOM load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Tư vấn trực tuyến initialized');
            initializeEventListeners();
            setupChatInput();
            restoreChatHistory(); // Khôi phục lịch sử chat
            createConsultation(); // Tạo consultation_id ngay khi load trang
            initGeminiToggle(); // Khởi tạo toggle KhamCare AI
        });
        
        // Khởi tạo toggle KhamCare AI
        function initGeminiToggle() {
            var toggle = document.getElementById('geminiToggle');
            var status = document.getElementById('geminiStatus');
            
            if (toggle && status) {
                // Khôi phục trạng thái từ localStorage
                var savedState = localStorage.getItem('gemini_enabled');
                if (savedState !== null) {
                    toggle.checked = savedState === 'true';
                }
                
                updateGeminiStatus();
                
                toggle.addEventListener('change', function() {
                    localStorage.setItem('gemini_enabled', this.checked);
                    updateGeminiStatus();
                    
                    // Thông báo cho user
                    var currentLang = localStorage.getItem('kham_lang') || 'vi';
                    var msg = this.checked ? 
                        (currentLang === 'km' ? 'បើក KhamCare AI' : 'Đã bật KhamCare AI - Trả lời thông minh hơn!') :
                        (currentLang === 'km' ? 'បិទ KhamCare AI' : 'Đã tắt KhamCare AI - Sử dụng phân tích cơ bản');
                    showNotification(msg, 'info');
                });
            }
        }
        
        function updateGeminiStatus() {
            var toggle = document.getElementById('geminiToggle');
            var status = document.getElementById('geminiStatus');
            var currentLang = localStorage.getItem('kham_lang') || 'vi';
            
            if (toggle && status) {
                if (toggle.checked) {
                    status.textContent = currentLang === 'km' ? 'បើក' : 'BẬT';
                    status.style.background = '#10b981';
                } else {
                    status.textContent = currentLang === 'km' ? 'បិទ' : 'TẮT';
                    status.style.background = '#6b7280';
                }
            }
        }

        // Khởi tạo event listeners
        function initializeEventListeners() {
            // Nút gửi tin nhắn
            var sendBtn = document.getElementById('sendBtn');
            var chatInput = document.getElementById('chatInput');
            
            if (sendBtn) sendBtn.onclick = sendMessage;
            if (chatInput) {
                chatInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }

            // Nút cuộc gọi
            var callPhoneBtn = document.getElementById('callPhoneBtn');
            var startVideoBtn = document.getElementById('startVideoBtn');
            var endCallBtn = document.getElementById('endCallBtn');

            // Fix hiệu ứng nhảy - override CSS khi hover
            function fixButtonHover(btn) {
                if (!btn) return;
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'none';
                });
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'none';
                });
                btn.addEventListener('mousedown', function() {
                    this.style.transform = 'none';
                });
                btn.addEventListener('mouseup', function() {
                    this.style.transform = 'none';
                });
            }

            fixButtonHover(callPhoneBtn);
            fixButtonHover(startVideoBtn);
            fixButtonHover(endCallBtn);

            if (callPhoneBtn) {
                callPhoneBtn.onclick = function(e) {
                    e.preventDefault();
                    this.style.transform = 'none';
                    startPhoneCall();
                };
            }

            if (startVideoBtn) {
                startVideoBtn.onclick = function(e) {
                    e.preventDefault();
                    this.style.transform = 'none';
                    startVideoCall();
                };
            }

            if (endCallBtn) {
                endCallBtn.onclick = function(e) {
                    e.preventDefault();
                    this.style.transform = 'none';
                    endCall();
                };
            }

            // Video controls
            var muteBtn = document.getElementById('muteBtn');
            var cameraBtn = document.getElementById('cameraBtn');

            if (muteBtn) {
                muteBtn.onclick = toggleMute;
            }

            if (cameraBtn) {
                cameraBtn.onclick = toggleCamera;
            }

            console.log('✅ Event listeners initialized');
        }

        // Thiết lập chat input tự động resize
        function setupChatInput() {
            var chatInput = document.getElementById('chatInput');
            if (!chatInput) return;

            chatInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }

        // Tạo consultation_id khi bắt đầu chat (trả về Promise)
        function createConsultation() {
            console.log('🔄 Creating consultation...');
            
            return fetch('api_create_chat_consultation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentConsultationId = data.consultation_id;
                    console.log('✅ Consultation created:', currentConsultationId);
                    return currentConsultationId;
                } else {
                    console.error('❌ Failed to create consultation:', data.message);
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error creating consultation:', error);
                throw error;
            });
        }
        
        // Đảm bảo có consultation_id trước khi thực hiện action
        function ensureConsultationId() {
            if (currentConsultationId) {
                return Promise.resolve(currentConsultationId);
            }
            return createConsultation();
        }
        
        // Lưu tin nhắn vào database
        function saveMessageToDatabase(sender, message) {
            // Đảm bảo có consultation_id trước khi lưu
            ensureConsultationId().then(function(consultationId) {
                console.log('💾 Saving message to database:', {
                    consultation_id: consultationId,
                    sender: sender,
                    message: message.substring(0, 50) + '...'
                });
                
                return fetch('api_save_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'consultation_id=' + encodeURIComponent(consultationId) +
                          '&sender=' + encodeURIComponent(sender) +
                          '&message=' + encodeURIComponent(message)
                });
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Message saved to database, ID:', data.message_id);
                } else {
                    console.error('❌ Failed to save message:', data.message);
                }
            })
            .catch(error => {
                console.error('❌ Error saving message:', error);
            });
        }

        // Gửi tin nhắn
        function sendMessage() {
            var chatInput = document.getElementById('chatInput');
            var message = chatInput.value.trim();
            
            if (!message) {
                var currentLang = localStorage.getItem('kham_lang') || 'vi';
                var warningMsg = currentLang === 'km' ? 
                    'សូមបញ្ចូលសារ!' : 
                    'Vui lòng nhập tin nhắn!';
                showNotification(warningMsg, 'warning');
                return;
            }

            console.log('📤 Sending message:', message);
            
            // Đảm bảo có consultation_id trước khi gửi
            ensureConsultationId().then(function(consultationId) {
                console.log('✅ Consultation ID ready:', consultationId);
                
                // Hiển thị tin nhắn user
                addMessage('user', message);
                saveChatHistory(); // Lưu lịch sử localStorage
                saveMessageToDatabase('patient', message); // Lưu vào database
                
                chatInput.value = '';
                chatInput.style.height = 'auto';

                // Hiển thị typing indicator
                showTypingIndicator();

                // Gửi đến server để phân tích
                analyzeSymptoms(message);
            }).catch(function(error) {
                console.error('❌ Cannot send message without consultation_id:', error);
                var currentLang = localStorage.getItem('kham_lang') || 'vi';
                var errorMsg = currentLang === 'km' ? 
                    'មានបញ្ហាក្នុងការបង្កើតការពិគ្រោះយោបល់។ សូមព្យាយាមម្តងទៀត។' :
                    'Có lỗi khi tạo phiên tư vấn. Vui lòng thử lại.';
                showNotification(errorMsg, 'error');
            });
        }

        // Thêm tin nhắn vào chat
        function addMessage(sender, content, isHtml = false) {
            var chatContent = document.getElementById('chatContent');
            if (!chatContent) return;

            var messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + sender;
            messageDiv.setAttribute('data-sender', sender);
            messageDiv.setAttribute('data-is-html', isHtml ? 'true' : 'false');
            
            if (isHtml) {
                messageDiv.innerHTML = content;
            } else {
                messageDiv.textContent = content;
            }

            chatContent.appendChild(messageDiv);
            chatContent.scrollTop = chatContent.scrollHeight;
        }

        // Hiển thị typing indicator
        function showTypingIndicator() {
            var chatContent = document.getElementById('chatContent');
            if (!chatContent) return;
            
            var useGemini = document.getElementById('geminiToggle').checked;
            var currentLang = localStorage.getItem('kham_lang') || 'vi';

            var typingDiv = document.createElement('div');
            typingDiv.className = 'message doctor typing-indicator';
            typingDiv.id = 'typingIndicator';
            
            var typingText = useGemini ? 
                (currentLang === 'km' ? 'KhamCare AI កំពុងវិភាគ...' : 'KhamCare AI đang phân tích...') :
                (currentLang === 'km' ? 'AI កំពុងវិភាគរោគសញ្ញា' : 'AI đang phân tích triệu chứng');
            
            typingDiv.innerHTML = `
                <i class="fas fa-robot" style="color: ${useGemini ? '#10b981' : '#0891b2'};"></i>
                <span>${typingText}</span>
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            `;

            chatContent.appendChild(typingDiv);
            chatContent.scrollTop = chatContent.scrollHeight;
        }

        // Xóa typing indicator
        function removeTypingIndicator() {
            var typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        // Phân tích triệu chứng
        function analyzeSymptoms(message) {
            // Lấy ngôn ngữ hiện tại từ localStorage
            var currentLang = localStorage.getItem('kham_lang') || 'vi';
            
            // Kiểm tra toggle KhamCare AI
            var useGemini = document.getElementById('geminiToggle').checked;
            
            // Lấy lịch sử chat để gửi context cho AI
            var chatHistory = [];
            if (useGemini) {
                var messages = document.querySelectorAll('#chatContent .message');
                messages.forEach(function(msg) {
                    if (msg.classList.contains('user')) {
                        chatHistory.push({
                            role: 'user',
                            content: msg.textContent
                        });
                    } else if (msg.classList.contains('doctor') && !msg.classList.contains('typing-indicator')) {
                        chatHistory.push({
                            role: 'model',
                            content: msg.textContent
                        });
                    }
                });
                // Giới hạn 10 tin nhắn gần nhất
                chatHistory = chatHistory.slice(-10);
            }
            
            var formData = 'message=' + encodeURIComponent(message) + 
                           '&lang=' + currentLang + 
                           '&use_gemini=' + (useGemini ? 'true' : 'false') +
                           '&chat_history=' + encodeURIComponent(JSON.stringify(chatHistory));
            
            fetch('api_symptom_consultation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                removeTypingIndicator();
                
                if (data.success) {
                    // Hiển thị phản hồi từ AI
                    if (data.responses && data.responses.length > 0) {
                        // Nếu là KhamCare AI, hiển thị ngay lập tức
                        if (data.is_gemini) {
                            data.responses.forEach(function(response) {
                                addMessage('doctor', response, true);
                                saveChatHistory();
                                saveMessageToDatabase('ai', response);
                            });
                            
                            // Cập nhật gợi ý chuyên khoa nếu có
                            if (data.suggested_specialties && data.suggested_specialties.length > 0) {
                                updateSpecialtySuggestionsWithDoctors(data.suggested_specialties, data.suggested_doctors);
                            }
                            
                            setTimeout(function() {
                                saveConsultationSession();
                            }, 1000);
                        } else {
                            // Logic cũ với delay
                            data.responses.forEach(function(response, index) {
                                setTimeout(function() {
                                    addMessage('doctor', response, true);
                                    saveChatHistory();
                                    saveMessageToDatabase('ai', response);
                                    
                                    if (index === data.responses.length - 1) {
                                        setTimeout(function() {
                                            saveConsultationSession();
                                        }, 1000);
                                    }
                                }, index * 1500);
                            });
                            
                            // Cập nhật gợi ý chuyên khoa với độ tin cậy
                            if (data.suggested_specialties && data.suggested_specialties.length > 0) {
                                setTimeout(function() {
                                    updateSpecialtySuggestionsWithDoctors(data.suggested_specialties, data.suggested_doctors);
                                }, data.responses.length * 1500);
                            }
                        }
                    }
                } else {
                    var errorMsg = currentLang === 'km' ? 
                        'សូមទោស ខ្ញុំមានបញ្ហាក្នុងការវិភាគរោគសញ្ញា។ សូមព្យាយាមម្តងទៀតនៅពេលក្រោយ។' :
                        'Xin lỗi, tôi gặp sự cố khi phân tích triệu chứng. Vui lòng thử lại sau.';
                    addMessage('doctor', data.message || errorMsg);
                    saveChatHistory();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                removeTypingIndicator();
                var errorMsg = currentLang === 'km' ? 
                    'កំហុសការតភ្ជាប់។ សូមពិនិត្យបណ្តាញ និងព្យាយាមម្តងទៀត។' :
                    'Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.';
                addMessage('doctor', errorMsg);
                saveChatHistory();
            });
        }

        // Cập nhật gợi ý chuyên khoa với danh sách bác sĩ
        function updateSpecialtySuggestionsWithDoctors(specialties, doctorsBySpecialty) {
            var container = document.getElementById('specialtySuggestions');
            if (!container) return;

            container.innerHTML = '';
            currentSuggestions = specialties;

            // Hiển thị từng chuyên khoa
            specialties.forEach(function(specialty, index) {
                var specialtyDiv = document.createElement('div');
                specialtyDiv.className = 'specialty-item recommended';
                specialtyDiv.style.cursor = 'default';
                specialtyDiv.style.flexDirection = 'column';
                specialtyDiv.style.alignItems = 'flex-start';

                var icon = getSpecialtyIcon(specialty.name);
                
                var headerHtml = `
                    <div style="display: flex; align-items: center; gap: 12px; width: 100%; margin-bottom: 12px;">
                        <div class="specialty-icon">
                            <i class="fas ${icon}"></i>
                        </div>
                        <div class="specialty-info" style="flex: 1;">
                            <h4>${specialty.name}</h4>
                            <p>${specialty.description || getSpecialtyDescription(specialty.name)}</p>
                        </div>
                    </div>
                `;
                
                // Thêm danh sách bác sĩ nếu có
                var doctorsHtml = '';
                if (doctorsBySpecialty && doctorsBySpecialty[specialty.name]) {
                    var doctors = doctorsBySpecialty[specialty.name];
                    doctorsHtml = '<div style="width: 100%; padding-left: 10px; border-left: 3px solid #10b981;">';
                    doctorsHtml += '<h5 style="color: #0891b2; font-size: 0.95rem; margin-bottom: 8px;">👨‍⚕️ Bác sĩ đề xuất:</h5>';
                    
                    doctors.forEach(function(doctor, idx) {
                        doctorsHtml += `
                            <div style="background: #f8fafc; padding: 10px; border-radius: 8px; margin-bottom: 8px;">
                                <div style="font-weight: 600; color: #222; margin-bottom: 4px;">
                                    ${idx + 1}. BS. ${doctor.full_name}
                                </div>
                                <div style="font-size: 0.85rem; color: #6b7280; margin-bottom: 6px;">
                                    • Kinh nghiệm: ${doctor.experience_years} năm | ⭐ ${doctor.rating}/5.0
                                </div>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <a href="view_doctor.php?doctor_id=${doctor.id}" target="_blank"
                                       style="background: #3b82f6; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-user-md"></i> Xem hồ sơ
                                    </a>
                                    <a href="DatLichK.php?doctor_id=${doctor.id}" target="_blank"
                                       style="background: #10b981; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-calendar-plus"></i> Đặt lịch
                                    </a>
                                    <button onclick="callDoctor(${doctor.id}, '${doctor.full_name}', '${specialty.name}')"
                                       style="background: #f59e0b; color: white; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-phone"></i> Gọi điện
                                    </button>
                                    <button onclick="videoCallDoctor(${doctor.id}, '${doctor.full_name}', '${specialty.name}')"
                                       style="background: #8b5cf6; color: white; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fas fa-video"></i> Video call
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    doctorsHtml += '</div>';
                }
                
                specialtyDiv.innerHTML = headerHtml + doctorsHtml;
                container.appendChild(specialtyDiv);
            });
        }
        
        // Cập nhật gợi ý chuyên khoa (legacy - giữ lại để tương thích)
        function updateSpecialtySuggestions(suggestions) {
            var container = document.getElementById('specialtySuggestions');
            if (!container) return;

            container.innerHTML = '';
            currentSuggestions = suggestions;

            var sortedSuggestions = Object.entries(suggestions).sort((a, b) => b[1] - a[1]);

            sortedSuggestions.forEach(function([specialty, confidence]) {
                var isRecommended = confidence >= 70;
                var specialtyDiv = document.createElement('div');
                specialtyDiv.className = 'specialty-item' + (isRecommended ? ' recommended' : '');
                specialtyDiv.onclick = function() { selectSpecialty(specialty); };

                var icon = getSpecialtyIcon(specialty);
                
                specialtyDiv.innerHTML = `
                    <div class="specialty-icon">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="specialty-info">
                        <h4>${specialty}</h4>
                        <p>${getSpecialtyDescription(specialty)}</p>
                    </div>
                    <div class="confidence-score">${confidence}%</div>
                `;

                container.appendChild(specialtyDiv);
            });

            // Thêm thông báo nếu có gợi ý cao
            var highConfidence = sortedSuggestions.find(([_, conf]) => conf >= 80);
            if (highConfidence) {
                setTimeout(function() {
                    addMessage('system', `🎯 Gợi ý: <strong>${highConfidence[0]}</strong> có độ phù hợp cao (${highConfidence[1]}%). Bạn có muốn đặt lịch khám với chuyên khoa này không?`);
                }, 500);
            }
        }

        // Lấy icon cho chuyên khoa
        function getSpecialtyIcon(specialty) {
            var icons = {
                'Tim Mạch': 'fa-heartbeat',
                'Thần Kinh': 'fa-brain',
                'Sản Phụ Khoa': 'fa-baby',
                'Nhi Khoa': 'fa-child',
                'Da Liễu': 'fa-hand-paper',
                'Mắt': 'fa-eye',
                'Tai Mũi Họng': 'fa-head-side-mask',
                'Nội Tổng Quát': 'fa-user-md',
                'Ngoại Khoa': 'fa-cut',
                'Chấn Thương Chỉnh Hình': 'fa-bone'
            };
            return icons[specialty] || 'fa-stethoscope';
        }

        // Lấy mô tả chuyên khoa
        function getSpecialtyDescription(specialty) {
            var descriptions = {
                'Tim Mạch': 'Chuyên về tim, mạch máu và huyết áp',
                'Thần Kinh': 'Chuyên về não bộ và hệ thần kinh',
                'Sản Phụ Khoa': 'Chuyên về sức khỏe phụ nữ và thai sản',
                'Nhi Khoa': 'Chuyên về sức khỏe trẻ em',
                'Da Liễu': 'Chuyên về da và các bệnh ngoài da',
                'Mắt': 'Chuyên về mắt và thị lực',
                'Tai Mũi Họng': 'Chuyên về tai, mũi, họng',
                'Nội Tổng Quát': 'Khám và điều trị bệnh nội khoa',
                'Ngoại Khoa': 'Chuyên về phẫu thuật',
                'Chấn Thương Chỉnh Hình': 'Chuyên về xương khớp'
            };
            return descriptions[specialty] || 'Chuyên khoa y tế';
        }

        // Chọn chuyên khoa
        function selectSpecialty(specialty) {
            addMessage('user', 'Tôi muốn tìm hiểu thêm về chuyên khoa ' + specialty);
            
            setTimeout(function() {
                addMessage('doctor', getTranslatedMessage('specialtyMatch', {specialty: specialty}), true);
                
                // Hiển thị nút đặt lịch
                setTimeout(function() {
                    showBookingOptions(specialty);
                }, 1000);
            }, 1000);
        }

        // Hiển thị tùy chọn đặt lịch
        function showBookingOptions(specialty, doctorInfo) {
            console.log('🔍 showBookingOptions called:', {specialty, doctorInfo});
            
            var chatContent = document.getElementById('chatContent');
            if (!chatContent) return;

            var optionsDiv = document.createElement('div');
            optionsDiv.className = 'message system';
            
            var buttonsHtml = `
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 12px;">
                    <button onclick="bookAppointment('${specialty}')" style="background: var(--success); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-calendar-plus"></i> Đặt lịch ngay
                    </button>
                    <button onclick="findDoctors('${specialty}')" style="background: var(--primary); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-user-md"></i> Xem bác sĩ
                    </button>`;
            
            // Luôn thêm nút đánh giá
            console.log('🔍 Checking doctorInfo:', doctorInfo);
            if (doctorInfo && doctorInfo.id) {
                console.log('✅ Adding review button for doctor:', doctorInfo.name);
                buttonsHtml += `
                    <button onclick="showReviewModal(${doctorInfo.id}, '${doctorInfo.name}', '${doctorInfo.specialty}')" style="background: #fbbf24; color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-star"></i> Đánh giá bác sĩ
                    </button>`;
            } else {
                // Nếu không có doctorInfo, vẫn hiển thị nút đánh giá với bác sĩ mặc định
                console.log('⚠️ No doctorInfo, adding generic review button');
                buttonsHtml += `
                    <button onclick="showDoctorReviewList()" style="background: #fbbf24; color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-star"></i> Đánh giá bác sĩ
                    </button>`;
            }
            
            buttonsHtml += `
                    <button onclick="continueConsultation()" style="background: var(--accent); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-comments"></i> Tư vấn thêm
                    </button>
                </div>
            `;
            
            optionsDiv.innerHTML = buttonsHtml;
            chatContent.appendChild(optionsDiv);
            chatContent.scrollTop = chatContent.scrollHeight;
        }

        // Thêm triệu chứng từ tag
        function addSymptom(symptom, event) {
            var chatInput = document.getElementById('chatInput');
            if (!chatInput) return;

            var currentText = chatInput.value.trim();
            var newText = currentText ? currentText + ', ' + symptom : 'Tôi bị ' + symptom;
            
            chatInput.value = newText;
            chatInput.focus();
            
            // Highlight tag nếu có event
            if (event && event.target) {
                event.target.classList.add('selected');
                setTimeout(function() {
                    event.target.classList.remove('selected');
                }, 1000);
            }
        }

        // Các function cuộc gọi (copy từ TrangChu.php)
        function startPhoneCall() {
            console.log('🔥 startPhoneCall() được gọi');
            
            if (callState.isActive) {
                showNotification('Cuộc gọi đang hoạt động!', 'warning');
                return;
            }
            
            callState.isActive = true;
            callState.type = 'phone';
            callState.startTime = Date.now();
            
            updateCallButtons(true);
            addMessage('user', 'Tôi muốn gọi điện tư vấn');
            
            setTimeout(function() {
                addMessage('doctor', getTranslatedMessage('connectingCall'));
                showCallStatus('phone');
                startCallTimer();
                
                setTimeout(function() {
                    updateCallStatus('✅ Đã kết nối - Bác sĩ Nguyễn Văn An');
                    addMessage('doctor', getTranslatedMessage('callConnected'));
                }, 3000);
            }, 1000);
        }

        function startVideoCall() {
            console.log('🔥 startVideoCall() được gọi');
            
            if (callState.isActive) {
                showNotification('Cuộc gọi đang hoạt động!', 'warning');
                return;
            }
            
            callState.isActive = true;
            callState.type = 'video';
            callState.startTime = Date.now();
            
            updateCallButtons(true);
            addMessage('user', 'Tôi muốn gọi video tư vấn');
            
            // Yêu cầu quyền truy cập camera và microphone
            addMessage('doctor', '📹 Đang yêu cầu quyền truy cập camera và microphone...');
            
            navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(function(stream) {
                    console.log('✅ Đã có quyền truy cập camera và microphone');
                    
                    // Hiển thị video container
                    showVideoContainer(true);
                    
                    // Hiển thị video của người dùng (local video)
                    var localVideo = document.getElementById('localVideo');
                    if (localVideo) {
                        localVideo.srcObject = stream;
                        localVideo.play();
                        console.log('✅ Local video started');
                    }
                    
                    addMessage('doctor', getTranslatedMessage('connectingVideo'));
                    showCallStatus('video');
                    startCallTimer();
                    
                    // Giả lập kết nối với bác sĩ sau 3 giây
                    setTimeout(function() {
                        updateCallStatus('✅ Video Call - AI Assistant');
                        addMessage('doctor', getTranslatedMessage('videoConnected'));
                        
                        // Giả lập video của bác sĩ (remote video)
                        simulateDoctorVideo('AI Assistant', 'Trợ lý tư vấn');
                    }, 3000);
                })
                .catch(function(error) {
                    console.error('❌ Lỗi truy cập camera/microphone:', error);
                    
                    var errorMessage = 'Không thể truy cập camera/microphone. ';
                    if (error.name === 'NotAllowedError') {
                        errorMessage += 'Vui lòng cho phép truy cập camera và microphone trong trình duyệt.';
                    } else if (error.name === 'NotFoundError') {
                        errorMessage += 'Không tìm thấy camera hoặc microphone.';
                    } else {
                        errorMessage += 'Lỗi: ' + error.message;
                    }
                    
                    addMessage('doctor', '❌ ' + errorMessage);
                    showNotification(errorMessage, 'warning');
                    
                    // Reset trạng thái nếu lỗi
                    callState.isActive = false;
                    callState.type = null;
                    callState.startTime = null;
                    updateCallButtons(false);
                });
        }

        function endCall() {
            if (!callState.isActive) return;
            
            var duration = callState.startTime ? Math.floor((Date.now() - callState.startTime) / 1000) : 0;
            var minutes = Math.floor(duration / 60);
            var seconds = duration % 60;
            var durationText = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
            
            if (callState.timer) {
                clearInterval(callState.timer);
                callState.timer = null;
            }
            
            // Dừng video streams nếu đang gọi video
            if (callState.type === 'video') {
                stopVideoStreams();
                showVideoContainer(false);
            }
            
            removeCallStatus();
            updateCallButtons(false);
            
            // Lưu thông tin bác sĩ trước khi reset
            // Lưu doctorInfo trước khi reset callState
            var doctorInfo = callState.doctorInfo;
            console.log('🔍 endCall - doctorInfo:', doctorInfo);
            
            callState.isActive = false;
            callState.type = null;
            callState.startTime = null;
            // KHÔNG set doctorInfo = null ở đây để giữ thông tin cho nút đánh giá
            
            addMessage('doctor', getTranslatedMessage('callEnded') + durationText + getTranslatedMessage('callSummary'));
            
            // Hiển thị booking options với nút đánh giá
            setTimeout(function() {
                var specialty = doctorInfo ? doctorInfo.specialty : '';
                console.log('🔍 Calling showBookingOptions with:', {specialty, doctorInfo});
                showBookingOptions(specialty, doctorInfo);
                
                // Reset doctorInfo sau khi đã hiển thị nút đánh giá
                callState.doctorInfo = null;
            }, 1000);
        }

        // Giả lập video của bác sĩ
        function simulateDoctorVideo(doctorName, specialty) {
            doctorName = doctorName || 'Trần Thị Hoa';
            specialty = specialty || 'Nội tổng quát';
            
            var remoteVideo = document.getElementById('remoteVideo');
            if (!remoteVideo) return;
            
            // Tạo canvas để giả lập video của bác sĩ
            var canvas = document.createElement('canvas');
            canvas.width = 640;
            canvas.height = 480;
            var ctx = canvas.getContext('2d');
            
            // Tạo gradient background
            var gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
            gradient.addColorStop(0, '#667eea');
            gradient.addColorStop(1, '#764ba2');
            
            var frame = 0;
            
            function drawFrame() {
                if (!callState.isActive || callState.type !== 'video') return;
                
                // Clear canvas
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                // Vẽ avatar bác sĩ
                ctx.fillStyle = 'rgba(255, 255, 255, 0.9)';
                ctx.beginPath();
                ctx.arc(canvas.width/2, canvas.height/2 - 50, 80, 0, 2 * Math.PI);
                ctx.fill();
                
                // Vẽ mặt
                ctx.fillStyle = '#f4a261';
                ctx.beginPath();
                ctx.arc(canvas.width/2, canvas.height/2 - 50, 70, 0, 2 * Math.PI);
                ctx.fill();
                
                // Vẽ mắt
                ctx.fillStyle = '#2a9d8f';
                ctx.beginPath();
                ctx.arc(canvas.width/2 - 25, canvas.height/2 - 70, 8, 0, 2 * Math.PI);
                ctx.fill();
                ctx.beginPath();
                ctx.arc(canvas.width/2 + 25, canvas.height/2 - 70, 8, 0, 2 * Math.PI);
                ctx.fill();
                
                // Vẽ miệng (animation)
                ctx.strokeStyle = '#e76f51';
                ctx.lineWidth = 4;
                ctx.beginPath();
                var mouthY = canvas.height/2 - 20 + Math.sin(frame * 0.1) * 5;
                ctx.arc(canvas.width/2, mouthY, 20, 0, Math.PI);
                ctx.stroke();
                
                // Vẽ text - TÊN BÁC SĨ ĐỘNG
                ctx.fillStyle = 'white';
                ctx.font = 'bold 24px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('BS. ' + doctorName, canvas.width/2, canvas.height/2 + 80);
                
                ctx.font = '16px Arial';
                ctx.fillText('Chuyên khoa ' + specialty, canvas.width/2, canvas.height/2 + 110);
                
                // Vẽ indicator đang nói
                if (Math.sin(frame * 0.2) > 0) {
                    ctx.fillStyle = 'rgba(16, 185, 129, 0.8)';
                    ctx.beginPath();
                    ctx.arc(canvas.width/2 + 60, canvas.height/2 - 80, 8, 0, 2 * Math.PI);
                    ctx.fill();
                    
                    ctx.fillStyle = 'white';
                    ctx.font = '12px Arial';
                    ctx.fillText('🎤', canvas.width/2 + 56, canvas.height/2 - 76);
                }
                
                frame++;
                requestAnimationFrame(drawFrame);
            }
            
            // Bắt đầu animation
            drawFrame();
            
            // Chuyển canvas thành video stream
            var stream = canvas.captureStream(30); // 30 FPS
            remoteVideo.srcObject = stream;
            remoteVideo.play();
            
            console.log('✅ Doctor video simulation started');
        }

        // Dừng tất cả video streams
        function stopVideoStreams() {
            var localVideo = document.getElementById('localVideo');
            var remoteVideo = document.getElementById('remoteVideo');
            
            // Dừng local video stream
            if (localVideo && localVideo.srcObject) {
                var tracks = localVideo.srcObject.getTracks();
                tracks.forEach(function(track) {
                    track.stop();
                });
                localVideo.srcObject = null;
                console.log('✅ Local video stream stopped');
            }
            
            // Dừng remote video stream
            if (remoteVideo && remoteVideo.srcObject) {
                var tracks = remoteVideo.srcObject.getTracks();
                tracks.forEach(function(track) {
                    track.stop();
                });
                remoteVideo.srcObject = null;
                console.log('✅ Remote video stream stopped');
            }
        }

        function updateCallButtons(isCallActive) {
            var callBtn = document.getElementById('callPhoneBtn');
            var videoBtn = document.getElementById('startVideoBtn');
            var endBtn = document.getElementById('endCallBtn');
            
            // Đảm bảo không có transform khi thay đổi display
            if (callBtn) {
                callBtn.style.transform = 'none';
                callBtn.style.display = isCallActive ? 'none' : 'inline-flex';
            }
            if (videoBtn) {
                videoBtn.style.transform = 'none';
                videoBtn.style.display = isCallActive ? 'none' : 'inline-flex';
            }
            if (endBtn) {
                endBtn.style.transform = 'none';
                endBtn.style.display = isCallActive ? 'inline-flex' : 'none';
            }
        }

        function showVideoContainer(show) {
            var videoContainer = document.getElementById('videoContainer');
            if (videoContainer) {
                videoContainer.style.display = show ? 'block' : 'none';
            }
        }

        function showCallStatus(type) {
            var chatContent = document.getElementById('chatContent');
            if (!chatContent) return;
            
            var statusDiv = document.createElement('div');
            statusDiv.className = 'call-status';
            statusDiv.id = 'callStatus';
            
            var icon = type === 'video' ? 'fa-video' : 'fa-phone';
            var statusText = type === 'video' ? 'Đang kết nối video...' : 'Đang gọi...';
            var animation = type === 'video' ? 'blink' : 'ring';
            
            statusDiv.innerHTML = `
                <div class="call-info">
                    <div class="call-icon" style="animation: ${animation} 1s infinite;">
                        <i class="fas ${icon}"></i>
                    </div>
                    <div class="call-details">
                        <h4 id="callStatusText">${statusText}</h4>
                        <div class="call-quality">
                            <span>Chất lượng: </span>
                            <div class="signal-bars">
                                <div class="signal-bar"></div>
                                <div class="signal-bar"></div>
                                <div class="signal-bar"></div>
                                <div class="signal-bar"></div>
                            </div>
                            <span>Tốt</span>
                        </div>
                    </div>
                </div>
                <div class="call-timer" id="callTimer">00:00</div>
            `;
            
            chatContent.appendChild(statusDiv);
            chatContent.scrollTop = chatContent.scrollHeight;
        }

        function updateCallStatus(text) {
            var statusText = document.getElementById('callStatusText');
            if (statusText) {
                statusText.textContent = text;
            }
        }

        function removeCallStatus() {
            var callStatus = document.getElementById('callStatus');
            if (callStatus) {
                callStatus.remove();
            }
        }

        function startCallTimer() {
            callState.timer = setInterval(function() {
                if (!callState.startTime) return;
                
                var elapsed = Math.floor((Date.now() - callState.startTime) / 1000);
                var minutes = Math.floor(elapsed / 60);
                var seconds = elapsed % 60;
                var timerElement = document.getElementById('callTimer');
                
                if (timerElement) {
                    timerElement.textContent = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
                }
            }, 1000);
        }

        // Các function hành động nhanh
        function bookAppointment(specialty) {
            window.location.href = 'DatLichK.php?specialty=' + encodeURIComponent(specialty);
        }

        function findDoctors(specialty) {
            window.location.href = 'TimKiemBS.php?specialty=' + encodeURIComponent(specialty);
        }
        
        // Gọi điện cho bác sĩ
        function callDoctor(doctorId, doctorName, specialty) {
            var currentLang = localStorage.getItem('kham_lang') || 'vi';
            specialty = specialty || 'Nội tổng quát';
            
            // Hiển thị tin nhắn
            var message = currentLang === 'km' ? 
                `កំពុងហៅទូរស័ព្ទទៅគ្រូពេទ្យ ${doctorName}...` :
                `Đang gọi điện cho BS. ${doctorName}...`;
            
            addMessage('system', message);
            
            // Giả lập cuộc gọi
            setTimeout(function() {
                var connectMsg = currentLang === 'km' ? 
                    `✅ បានភ្ជាប់ជាមួយគ្រូពេទ្យ ${doctorName}` :
                    `✅ Đã kết nối với BS. ${doctorName}`;
                
                addMessage('system', connectMsg);
                
                // Lưu thông tin bác sĩ để hiển thị nút đánh giá sau
                callState.doctorInfo = {
                    id: doctorId,
                    name: doctorName,
                    specialty: specialty
                };
                
                // Hiển thị thông báo cuộc gọi
                showCallStatus('phone');
                callState.isActive = true;
                callState.type = 'phone';
                callState.startTime = Date.now();
                startCallTimer();
                updateCallButtons(true);
                
                // Tin nhắn từ bác sĩ
                setTimeout(function() {
                    var doctorMsg = currentLang === 'km' ? 
                        `សួស្តី! ខ្ញុំជាគ្រូពេទ្យ ${doctorName}។ តើខ្ញុំអាចជួយអ្វីបានទេ?` :
                        `Xin chào! Tôi là BS. ${doctorName}. Tôi có thể giúp gì cho bạn?`;
                    
                    addMessage('doctor', doctorMsg);
                    saveChatHistory();
                }, 2000);
            }, 1500);
        }
        
        // Video call với bác sĩ
        function videoCallDoctor(doctorId, doctorName, specialty) {
            var currentLang = localStorage.getItem('kham_lang') || 'vi';
            specialty = specialty || 'Nội tổng quát';
            
            // Hiển thị tin nhắn
            var message = currentLang === 'km' ? 
                `កំពុងហៅវីដេអូទៅគ្រូពេទ្យ ${doctorName}...` :
                `Đang gọi video cho BS. ${doctorName}...`;
            
            addMessage('system', message);
            
            // Yêu cầu quyền camera
            navigator.mediaDevices.getUserMedia({ video: true, audio: true })
                .then(function(stream) {
                    // Hiển thị video container
                    showVideoContainer(true);
                    
                    // Hiển thị video của người dùng
                    var localVideo = document.getElementById('localVideo');
                    if (localVideo) {
                        localVideo.srcObject = stream;
                        localVideo.play();
                    }
                    
                    // Giả lập kết nối
                    setTimeout(function() {
                        var connectMsg = currentLang === 'km' ? 
                            `✅ បានភ្ជាប់វីដេអូជាមួយគ្រូពេទ្យ ${doctorName}` :
                            `✅ Đã kết nối video với BS. ${doctorName}`;
                        
                        addMessage('system', connectMsg);
                        
                        // Lưu thông tin bác sĩ để hiển thị nút đánh giá sau
                        callState.doctorInfo = {
                            id: doctorId,
                            name: doctorName,
                            specialty: specialty
                        };
                        
                        // Hiển thị thông báo cuộc gọi
                        showCallStatus('video');
                        callState.isActive = true;
                        callState.type = 'video';
                        callState.startTime = Date.now();
                        startCallTimer();
                        updateCallButtons(true);
                        
                        // Giả lập video bác sĩ với tên và chuyên khoa
                        simulateDoctorVideo(doctorName, specialty);
                        
                        // Tin nhắn từ bác sĩ
                        setTimeout(function() {
                            var doctorMsg = currentLang === 'km' ? 
                                `សួស្តី! ខ្ញុំជាគ្រូពេទ្យ ${doctorName}។ តើអ្នកមានបញ្ហាអ្វី?` :
                                `Xin chào! Tôi là BS. ${doctorName}. Bạn có vấn đề gì?`;
                            
                            addMessage('doctor', doctorMsg);
                            saveChatHistory();
                        }, 2000);
                    }, 2000);
                })
                .catch(function(error) {
                    console.error('Camera error:', error);
                    var errorMsg = currentLang === 'km' ? 
                        '❌ មិនអាចចូលប្រើកាមេរ៉ា។ សូមអនុញ្ញាតក្នុងកម្មវិធីរុករក។' :
                        '❌ Không thể truy cập camera. Vui lòng cho phép trong trình duyệt.';
                    
                    addMessage('system', errorMsg);
                });
        }

        function continueConsultation() {
            addMessage('doctor', getTranslatedMessage('continueConsult'));
        }

        function startEmergencyCall() {
            if (confirm('Bạn có muốn gọi cấp cứu 115 không?')) {
                window.open('tel:115');
            }
        }

        function showHealthTips() {
            var currentLang = localStorage.getItem('kham_lang') || 'vi';
            var tips = currentLang === 'km' ? 
                `💡 <strong>គន្លឹះសុខភាពប្រចាំថ្ងៃ:</strong><br><br>
                • ផឹកទឹកឱ្យបានគ្រប់គ្រាន់ 2-3 លីត្រក្នុងមួយថ្ងៃ<br>
                • គេងឱ្យបានគ្រប់ 7-8 ម៉ោងក្នុងមួយយប់<br>
                • ធ្វើលំហាត់ប្រាណយ៉ាងតិច 30 នាទី/ថ្ងៃ<br>
                • ញ៉ាំបន្លែបៃតង និងផ្លែឈើច្រើន<br>
                • កាត់បន្ថយភាពតានតឹង និងសម្រាកឱ្យបានសមរម្យ<br>
                • ពិនិត្យសុខភាពទៀងទាត់ 6 ខែ/ដង` :
                `💡 <strong>Mẹo sức khỏe hàng ngày:</strong><br><br>
                • Uống đủ 2-3 lít nước mỗi ngày<br>
                • Ngủ đủ 7-8 tiếng mỗi đêm<br>
                • Tập thể dục ít nhất 30 phút/ngày<br>
                • Ăn nhiều rau xanh và trái cây<br>
                • Hạn chế stress và nghỉ ngơi hợp lý<br>
                • Khám sức khỏe định kỳ 6 tháng/lần`;
            addMessage('doctor', tips, true);
            saveChatHistory();
        }
        
        // Xác nhận xóa lịch sử chat
        function confirmClearChat() {
            var currentLang = localStorage.getItem('kham_lang') || 'vi';
            var confirmMsg = currentLang === 'km' ? 
                'តើអ្នកប្រាកដថាចង់លុបប្រវត្តិការជជែកទេ? សកម្មភាពនេះមិនអាចត្រឡប់វិញបានទេ។' :
                'Bạn có chắc chắn muốn xóa lịch sử chat? Hành động này không thể hoàn tác.';
            
            if (confirm(confirmMsg)) {
                clearChatAndRestart();
            }
        }
        
        // Xóa chat và khởi động lại
        function clearChatAndRestart() {
            clearChatHistory();
            
            // Reset session ID và cờ đã lưu
            window.currentSessionId = null;
            window.sessionSaved = false;
            
            // Xóa tất cả tin nhắn trừ tin nhắn hệ thống và giới thiệu
            var chatContent = document.getElementById('chatContent');
            if (chatContent) {
                chatContent.innerHTML = '';
                
                // Thêm lại tin nhắn hệ thống
                var currentLang = localStorage.getItem('kham_lang') || 'vi';
                var dict = window.GLOBAL_DICT[currentLang];
                
                var systemMsg = document.createElement('div');
                systemMsg.className = 'message system';
                systemMsg.setAttribute('data-sender', 'system');
                systemMsg.innerHTML = '<i class="fas fa-robot"></i> <span>' + dict.systemReady + '</span>';
                chatContent.appendChild(systemMsg);
                
                // Thêm lại tin nhắn giới thiệu
                var introMsg = document.createElement('div');
                introMsg.className = 'message doctor';
                introMsg.setAttribute('data-sender', 'doctor');
                introMsg.innerHTML = `${currentLang === 'km' ? 'សួស្តី' : 'Xin chào'} <strong><?= htmlspecialchars($user['full_name']) ?></strong>! 👋<br><br>
                    ${dict.aiIntro}<br><br>
                    <ul style="margin: 12px 0; padding-left: 20px;">
                        <li>${dict.aiFeature1}</li>
                        <li>${dict.aiFeature2}</li>
                        <li>${dict.aiFeature3}</li>
                    </ul>
                    ${dict.aiPrompt}`;
                chatContent.appendChild(introMsg);
            }
            
            // Xóa gợi ý chuyên khoa
            var specialtySuggestions = document.getElementById('specialtySuggestions');
            if (specialtySuggestions) {
                var currentLang = localStorage.getItem('kham_lang') || 'vi';
                var dict = window.GLOBAL_DICT[currentLang];
                specialtySuggestions.innerHTML = `
                    <div class="message system" style="text-align: center; padding: 20px;">
                        <i class="fas fa-info-circle"></i><br>
                        <span>${dict.enterSymptomsPrompt}</span>
                    </div>
                `;
            }
            
            var successMsg = currentLang === 'km' ? 
                'បានលុបប្រវត្តិការជជែក។ អ្នកអាចចាប់ផ្តើមការពិគ្រោះយោបល់ថ្មីបានហើយ។' :
                'Đã xóa lịch sử chat. Bạn có thể bắt đầu cuộc tư vấn mới.';
            showNotification(successMsg, 'success');
        }

        // Video controls
        var isMuted = false;
        var isCameraOff = false;

        function toggleMute() {
            var localVideo = document.getElementById('localVideo');
            var muteBtn = document.getElementById('muteBtn');
            
            if (!localVideo || !localVideo.srcObject) return;
            
            var audioTracks = localVideo.srcObject.getAudioTracks();
            if (audioTracks.length > 0) {
                isMuted = !isMuted;
                audioTracks[0].enabled = !isMuted;
                
                if (muteBtn) {
                    muteBtn.innerHTML = isMuted ? '<i class="fas fa-microphone-slash"></i>' : '<i class="fas fa-microphone"></i>';
                    muteBtn.style.background = isMuted ? 'rgba(239, 68, 68, 0.8)' : 'rgba(16, 185, 129, 0.8)';
                }
                
                showNotification(isMuted ? 'Đã tắt microphone' : 'Đã bật microphone', 'info');
                console.log(isMuted ? '🔇 Microphone muted' : '🎤 Microphone unmuted');
            }
        }

        function toggleCamera() {
            var localVideo = document.getElementById('localVideo');
            var cameraBtn = document.getElementById('cameraBtn');
            
            if (!localVideo || !localVideo.srcObject) return;
            
            var videoTracks = localVideo.srcObject.getVideoTracks();
            if (videoTracks.length > 0) {
                isCameraOff = !isCameraOff;
                videoTracks[0].enabled = !isCameraOff;
                
                if (cameraBtn) {
                    cameraBtn.innerHTML = isCameraOff ? '<i class="fas fa-video-slash"></i>' : '<i class="fas fa-video"></i>';
                    cameraBtn.style.background = isCameraOff ? 'rgba(239, 68, 68, 0.8)' : 'rgba(59, 130, 246, 0.8)';
                }
                
                showNotification(isCameraOff ? 'Đã tắt camera' : 'Đã bật camera', 'info');
                console.log(isCameraOff ? '📹 Camera off' : '📷 Camera on');
            }
        }

        // Hiển thị thông báo
        function showNotification(message, type = 'info') {
            // Tạo notification đơn giản
            var notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#2563eb'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                gap: 8px;
            `;
            
            var icon = type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
            notification.innerHTML = icon + ' ' + message;
            
            document.body.appendChild(notification);
            
            setTimeout(function() {
                notification.remove();
            }, 3000);
        }

        // Hàm hiển thị danh sách bác sĩ để đánh giá
        function showDoctorReviewList() {
            // Lấy danh sách bác sĩ từ PHP
            var doctors = <?php echo json_encode(array_slice($topDoctors, 0, 6)); ?>;
            
            var existingModal = document.getElementById('doctorListModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            var doctorListHtml = doctors.map(function(doc) {
                return `
                    <div onclick="showReviewModal(${doc.id || doc.doctor_row_id}, '${doc.full_name}', '${doc.specialty_name || doc.title}')" 
                         style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px; cursor: pointer; transition: all 0.2s;"
                         onmouseover="this.style.background='#e0f2fe'" onmouseout="this.style.background='#f8fafc'">
                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #0891b2, #06b6d4); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user-md" style="color: white; font-size: 1.2rem;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: #1e293b;">BS. ${doc.full_name}</div>
                            <div style="font-size: 0.85rem; color: #64748b;">${doc.specialty_name || doc.title || 'Chuyên khoa'}</div>
                        </div>
                        <i class="fas fa-star" style="margin-left: auto; color: #fbbf24;"></i>
                    </div>
                `;
            }).join('');
            
            var modalHtml = `
                <div id="doctorListModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 10000;">
                    <div style="background: white; border-radius: 16px; padding: 25px; max-width: 450px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="color: #1e293b; font-size: 1.2rem;">⭐ Chọn bác sĩ để đánh giá</h3>
                            <button onclick="document.getElementById('doctorListModal').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">&times;</button>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            ${doctorListHtml}
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        // Hàm hiển thị modal đánh giá bác sĩ
        function showReviewModal(doctorId, doctorName, specialty) {
            console.log('🌟 showReviewModal called:', {doctorId, doctorName, specialty});
            
            // Kiểm tra xem modal đã tồn tại chưa
            var existingModal = document.getElementById('reviewModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Tạo modal đánh giá
            var modalHtml = `
                <div id="reviewModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 10000;">
                    <div style="background: white; border-radius: 16px; padding: 30px; max-width: 500px; width: 90%; box-shadow: 0 20px 50px rgba(0,0,0,0.3);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="color: #1e293b; font-size: 1.3rem;">⭐ Đánh giá bác sĩ</h3>
                            <button onclick="closeReviewModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #64748b;">&times;</button>
                        </div>
                        
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #0891b2, #06b6d4); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                                <i class="fas fa-user-md" style="font-size: 2rem; color: white;"></i>
                            </div>
                            <h4 style="color: #1e293b;">BS. ${doctorName}</h4>
                            <p style="color: #64748b; font-size: 0.9rem;">${specialty}</p>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b;">Đánh giá của bạn:</label>
                            <div id="starRating" style="display: flex; gap: 8px; justify-content: center; margin-bottom: 15px;">
                                <span class="star" data-rating="1" onclick="setRating(1)" style="font-size: 2rem; cursor: pointer; color: #e2e8f0;">★</span>
                                <span class="star" data-rating="2" onclick="setRating(2)" style="font-size: 2rem; cursor: pointer; color: #e2e8f0;">★</span>
                                <span class="star" data-rating="3" onclick="setRating(3)" style="font-size: 2rem; cursor: pointer; color: #e2e8f0;">★</span>
                                <span class="star" data-rating="4" onclick="setRating(4)" style="font-size: 2rem; cursor: pointer; color: #e2e8f0;">★</span>
                                <span class="star" data-rating="5" onclick="setRating(5)" style="font-size: 2rem; cursor: pointer; color: #e2e8f0;">★</span>
                            </div>
                            <input type="hidden" id="selectedRating" value="0">
                            <input type="hidden" id="reviewDoctorId" value="${doctorId}">
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #1e293b;">Nhận xét:</label>
                            <textarea id="reviewComment" rows="4" placeholder="Chia sẻ trải nghiệm của bạn với bác sĩ..." style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 1rem; resize: none;"></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 12px;">
                            <button onclick="closeReviewModal()" style="flex: 1; padding: 12px; background: #f1f5f9; color: #64748b; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Hủy</button>
                            <button onclick="submitReview()" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Gửi đánh giá</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        // Đặt số sao đánh giá
        function setRating(rating) {
            document.getElementById('selectedRating').value = rating;
            var stars = document.querySelectorAll('#starRating .star');
            stars.forEach(function(star, index) {
                if (index < rating) {
                    star.style.color = '#fbbf24';
                } else {
                    star.style.color = '#e2e8f0';
                }
            });
        }

        // Đóng modal đánh giá
        function closeReviewModal() {
            var modal = document.getElementById('reviewModal');
            if (modal) {
                modal.remove();
            }
        }

        // Gửi đánh giá
        function submitReview() {
            var rating = document.getElementById('selectedRating').value;
            var comment = document.getElementById('reviewComment').value;
            var doctorId = document.getElementById('reviewDoctorId').value;
            
            if (rating == 0) {
                showNotification('Vui lòng chọn số sao đánh giá!', 'warning');
                return;
            }
            
            // Gửi đánh giá qua API
            fetch('api_save_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    doctor_id: doctorId,
                    rating: rating,
                    comment: comment
                })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    showNotification('Cảm ơn bạn đã đánh giá!', 'success');
                    closeReviewModal();
                    
                    // Thêm tin nhắn cảm ơn vào chat
                    addMessage('doctor', '⭐ Cảm ơn bạn đã đánh giá ' + rating + ' sao! Phản hồi của bạn giúp chúng tôi cải thiện dịch vụ.');
                } else {
                    showNotification(data.message || 'Có lỗi xảy ra!', 'warning');
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                // Vẫn hiển thị thành công cho demo
                showNotification('Cảm ơn bạn đã đánh giá!', 'success');
                closeReviewModal();
                addMessage('doctor', '⭐ Cảm ơn bạn đã đánh giá ' + rating + ' sao! Phản hồi của bạn giúp chúng tôi cải thiện dịch vụ.');
            });
        }
    </script>
    
    <!-- Global Language System -->
    <script src="js/language-system.js"></script>
    
    <!-- Review System -->
    <script src="js/review-system.js"></script>
    
    <script>
        // Listen for language changes and update page-specific elements
        document.addEventListener('languageChanged', function(event) {
            const { lang, dict } = event.detail;
            console.log('🌐 TuVanTrucTuyen: Language changed to', lang);
            
            // Update page title
            document.title = dict.consultationPageTitle;
            
            // XÓA TẤT CẢ TIN NHẮN CŨ VÀ RESET CHAT
            // Vì không thể dịch lại chính xác các tin nhắn AI cũ
            const chatContent = document.getElementById('chatContent');
            if (chatContent) {
                // Xóa tất cả tin nhắn
                chatContent.innerHTML = '';
                
                // Thêm lại tin nhắn hệ thống
                const systemMsg = document.createElement('div');
                systemMsg.className = 'message system';
                systemMsg.setAttribute('data-sender', 'system');
                systemMsg.setAttribute('data-is-html', 'true');
                systemMsg.innerHTML = '<i class="fas fa-robot"></i> <span>' + dict.systemReady + '</span>';
                chatContent.appendChild(systemMsg);
                
                // Thêm lại tin nhắn giới thiệu
                const introMsg = document.createElement('div');
                introMsg.className = 'message doctor';
                introMsg.setAttribute('data-sender', 'doctor');
                introMsg.setAttribute('data-is-html', 'true');
                introMsg.innerHTML = `${lang === 'km' ? 'សួស្តី' : 'Xin chào'} <strong><?= htmlspecialchars($user['full_name']) ?></strong>! 👋<br><br>
                    ${dict.aiIntro}<br><br>
                    <ul style="margin: 12px 0; padding-left: 20px;">
                        <li>${dict.aiFeature1}</li>
                        <li>${dict.aiFeature2}</li>
                        <li>${dict.aiFeature3}</li>
                    </ul>
                    ${dict.aiPrompt}`;
                chatContent.appendChild(introMsg);
                
                // Xóa lịch sử chat cũ
                clearChatHistory();
                
                // Reset gợi ý chuyên khoa
                const specialtySuggestions = document.getElementById('specialtySuggestions');
                if (specialtySuggestions) {
                    specialtySuggestions.innerHTML = `
                        <div class="message system" style="text-align: center; padding: 20px;">
                            <i class="fas fa-info-circle"></i><br>
                            <span>${dict.enterSymptomsPrompt}</span>
                        </div>
                    `;
                }
                
                console.log('🗑️ Chat history cleared due to language change');
            }
            
            // Update symptom onclick functions to use translated terms
            const symptomTags = document.querySelectorAll('.symptom-tag');
            const symptomMap = {
                'symptomHeadache': lang === 'km' ? 'ឈឺក្បាល' : 'đau đầu',
                'symptomFever': lang === 'km' ? 'គ្រុនក្តៅ' : 'sốt',
                'symptomCough': lang === 'km' ? 'ក្អក' : 'ho',
                'symptomStomachache': lang === 'km' ? 'ឈឺពោះ' : 'đau bụng',
                'symptomNausea': lang === 'km' ? 'ចង់ក្អួត' : 'buồn nôn',
                'symptomDizziness': lang === 'km' ? 'វិលមុខ' : 'chóng mặt',
                'symptomFatigue': lang === 'km' ? 'នឿយហត់' : 'mệt mỏi',
                'symptomChestPain': lang === 'km' ? 'ឈឺទ្រូង' : 'đau ngực'
            };
            
            symptomTags.forEach(tag => {
                const id = tag.id;
                if (symptomMap[id]) {
                    tag.setAttribute('onclick', `addSymptom('${symptomMap[id]}')`);
                }
            });
            
            // Update chat input placeholder
            const chatInput = document.getElementById('chatInput');
            if (chatInput) {
                chatInput.placeholder = dict.chatInputPlaceholder;
            }
            
            console.log('✅ TuVanTrucTuyen: Language updated successfully');
        });
    </script>
    
    <!-- Review Modal -->
    <div id="reviewModal" class="review-modal">
        <div class="review-content">
            <div class="review-header">
                <h2><i class="fas fa-star"></i> <span id="reviewTitle">Đánh giá bác sĩ</span></h2>
                <button onclick="closeReviewModal()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 5px 10px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="review-body">
                <div class="review-doctor-info">
                    <i class="fas fa-user-md" style="font-size: 3rem; color: #0891b2;"></i>
                    <h3 id="reviewDoctorName">BS. Trần Văn Hùng</h3>
                    <p id="reviewDoctorSpecialty">Chuyên khoa Tim Mạch</p>
                </div>
                
                <div class="review-group">
                    <label><span id="labelOverallRating">Đánh giá tổng quan</span> <span style="color: #ef4444;">*</span></label>
                    <div class="star-rating" id="starRating">
                        <span class="star" data-rating="1">★</span>
                        <span class="star" data-rating="2">★</span>
                        <span class="star" data-rating="3">★</span>
                        <span class="star" data-rating="4">★</span>
                        <span class="star" data-rating="5">★</span>
                    </div>
                    <input type="hidden" id="ratingValue" value="0">
                </div>
                
                <div class="review-group">
                    <label id="labelDetailedRating">Đánh giá chi tiết</label>
                    <div class="rating-bars">
                        <div class="rating-bar">
                            <label id="labelServiceQuality">Chất lượng dịch vụ</label>
                            <input type="range" id="serviceQuality" min="1" max="5" value="5">
                            <span class="rating-value" id="serviceQualityValue">5</span>
                        </div>
                        <div class="rating-bar">
                            <label id="labelCommunication">Giao tiếp</label>
                            <input type="range" id="communication" min="1" max="5" value="5">
                            <span class="rating-value" id="communicationValue">5</span>
                        </div>
                        <div class="rating-bar">
                            <label id="labelProfessionalism">Tính chuyên nghiệp</label>
                            <input type="range" id="professionalism" min="1" max="5" value="5">
                            <span class="rating-value" id="professionalismValue">5</span>
                        </div>
                        <div class="rating-bar">
                            <label id="labelWaitingTime">Thời gian chờ</label>
                            <input type="range" id="waitingTime" min="1" max="5" value="5">
                            <span class="rating-value" id="waitingTimeValue">5</span>
                        </div>
                    </div>
                </div>
                
                <div class="review-group">
                    <label for="reviewComment" id="labelComment">Nhận xét của bạn</label>
                    <textarea id="reviewComment" placeholder="Chia sẻ trải nghiệm của bạn..."></textarea>
                </div>
                
                <div class="review-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="wouldRecommend" checked>
                        <label for="wouldRecommend" id="labelRecommend">Tôi sẽ giới thiệu bác sĩ này</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="isAnonymous">
                        <label for="isAnonymous" id="labelAnonymous">Đánh giá ẩn danh</label>
                    </div>
                </div>
            </div>
            
            <div class="review-footer">
                <button class="btn-review btn-review-cancel" onclick="closeReviewModal()">
                    <span id="btnCancel">Hủy</span>
                </button>
                <button class="btn-review btn-review-submit" onclick="submitReview()">
                    <span id="btnSubmit">Gửi đánh giá</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Beautiful Medical Footer -->
    <footer class="beautiful-footer">
        <div class="footer-wave">
            <svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg">
                <path d="M0,60 C300,120 900,0 1200,60 L1200,120 L0,120 Z" fill="url(#footerGradient)"/>
                <defs>
                    <linearGradient id="footerGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#0c4a6e;stop-opacity:1" />
                        <stop offset="30%" style="stop-color:#0369a1;stop-opacity:1" />
                        <stop offset="70%" style="stop-color:#0ea5e9;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#06b6d4;stop-opacity:1" />
                    </linearGradient>
                </defs>
            </svg>
        </div>
        
        <div class="footer-content">
            <div class="container">
                <div class="footer-main-grid">
                    <!-- Brand Column -->
                    <div class="footer-brand">
                        <div class="brand-header">
                            <div class="brand-icon">
                                <img src="image/logo.png.png" alt="KhamCare Logo" style="width: 60px; height: 60px; border-radius: 12px;">
                            </div>
                            <div class="brand-info">
                                <h2>KhamCare</h2>
                                <p>INTERNATIONAL HOSPITAL</p>
                            </div>
                        </div>
                        <p class="brand-desc">
                            Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. 
                            Kết nối bạn với các bác sĩ chuyên khoa uy tín.
                        </p>
                        <div class="brand-stats">
                            <div class="stat">
                                <span class="number">50K+</span>
                                <span class="label">Bệnh nhân</span>
                            </div>
                            <div class="stat">
                                <span class="number">200+</span>
                                <span class="label">Bác sĩ</span>
                            </div>
                            <div class="stat">
                                <span class="number">15+</span>
                                <span class="label">Chuyên khoa</span>
                            </div>
                        </div>
                    </div>

                    
                <!-- Patient Links -->
                <div class="footer-column">
                    <h3><i class="fas fa-user-injured"></i> Dành Cho Bệnh Nhân</h3>
                    <ul>
                        <li><a href="TimKiemBS.php"><i class="fas fa-search"></i> Tìm kiếm bác sĩ</a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>
                        <li><a href="DatLichK.php"><i class="fas fa-calendar-plus"></i> Đặt lịch</a></li>
                        <li><a href="#"><i class="fas fa-history"></i> Bảng kiểm soát lịch hẹn</a></li>
                    </ul>
                </div>

                <!-- Doctor Links -->
                <div class="footer-column">
                    <h3><i class="fas fa-user-md"></i> Dành Cho Bác Sĩ</h3>
                    <ul>
                        <li><a href="doctor_dashboard.php"><i class="fas fa-tachometer-alt"></i> Kiểm tra cuộc hẹn</a></li>
                        <li><a href="TuVanTrucTuyen.php"><i class="fas fa-comments"></i> Chat</a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-md"></i> Đăng nhập</a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>
                        <li><a href="doctor_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard của bác sĩ</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="footer-column contact-column">
                    <h3><i class="fas fa-phone-alt"></i> Liên Hệ</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Địa chỉ:</strong><br>
                                Sở y tế - Bệnh viện đa khoa quốc tế<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Hotline:</strong><br>
                                0433636050
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email:</strong><br>
                                benhviendakhoaquocte@gmail.com
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Links -->
                    <div class="social-section">
                        <h4>Kết nối với chúng tôi</h4>
                        <div class="social-links">
                            <a href="#" class="social facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social youtube"><i class="fab fa-youtube"></i></a>
                            <a href="#" class="social zalo"><i class="fas fa-comment-dots"></i></a>
                            <a href="#" class="social instagram"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="bottom-content">
                <p>&copy; 2024 <strong>KhamCare International Hospital</strong>. Tất cả quyền được bảo lưu.</p>
                <div class="bottom-links">
                    <a href="#">Chính sách bảo mật</a>
                    <a href="#">Điều khoản sử dụng</a>
                    <a href="#">Hỗ trợ khách hàng</a>
                </div>
            </div>
        </div>
    </div>
</footer>


    <script src="js/language-system.js"></script>
    <script>
    // Account dropdown toggle function
    function toggleAccountDropdown(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('accountDropdown');
        if (dropdown) {
            dropdown.classList.toggle('active');
        }
    }

    // Close account dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const accountDropdown = document.getElementById('accountDropdown');
        if (accountDropdown && !accountDropdown.contains(event.target)) {
            accountDropdown.classList.remove('active');
        }
    });

    // Language switcher functions
    function selectLanguage(lang) {
        console.log('🌐 Language selected:', lang);
        
        // Update button text
        const langMap = { 'vi': 'VN', 'km': 'KH' };
        document.getElementById('currentLangText').textContent = langMap[lang];
        
        // Update active state
        document.querySelectorAll('.lang-option').forEach(opt => {
            opt.classList.remove('active');
            if (opt.getAttribute('data-lang') === lang) {
                opt.classList.add('active');
            }
        });
        
        // Close dropdown
        document.getElementById('langSwitcher').classList.remove('active');
        
        // Save to localStorage
        localStorage.setItem('kham_lang', lang);
        
        // Apply translation
        if (typeof applyLanguage === 'function') {
            applyLanguage(lang);
        }
        applyFooterTranslation(lang);
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const switcher = document.getElementById('langSwitcher');
        if (switcher && !switcher.contains(event.target)) {
            switcher.classList.remove('active');
        }
    });

    // Initialize language on page load
    window.addEventListener('DOMContentLoaded', function() {
        const savedLang = localStorage.getItem('kham_lang') || 'vi';
        selectLanguage(savedLang);
    });

    // Footer translation for TuVanTrucTuyen
    const footerTranslations={vi:{navSearch:"Tìm bác sĩ",navSpecialty:"Chuyên khoa",navConsult:"Tư vấn",navGuide:"Cẩm nang Sức khỏe",navDoctor:"Dành cho Bác sĩ",navAccount:"Tài khoản",brandSubtitle:"INTERNATIONAL HOSPITAL",brandDesc:"Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. Kết nối bạn với các bác sĩ chuyên khoa uy tín.",statPatients:"Bệnh nhân",statDoctors:"Bác sĩ",statSpecialties:"Chuyên khoa",patientTitle:"Dành Cho Bệnh Nhân",patientLink1:"Tìm kiếm bác sĩ",patientLink2:"Đăng nhập",patientLink3:"Đăng ký",patientLink4:"Đặt lịch",patientLink5:"Bảng kiểm soát lịch hẹn",doctorTitle:"Dành Cho Bác Sĩ",doctorLink1:"Kiểm tra cuộc hẹn",doctorLink2:"Chat",doctorLink3:"Đăng nhập",doctorLink4:"Đăng ký",doctorLink5:"Dashboard của bác sĩ",contactTitle:"Liên Hệ",contactAddress:"<strong>Địa chỉ:</strong><br>Sở y tế - Bệnh viện đa khoa quốc tế<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",contactHotline:"<strong>Hotline:</strong><br>0433636050",contactEmail:"<strong>Email:</strong><br>benhviendakhoaquocte@gmail.com",socialTitle:"Kết nối với chúng tôi",copyright:"© 2024 <strong>KhamCare International Hospital</strong>. Tất cả quyền được bảo lưu.",privacy:"Chính sách bảo mật",terms:"Điều khoản sử dụng",support:"Hỗ trợ khách hàng"},km:{navSearch:"ស្វែងរកវេជ្ជបណ្ឌិត",navSpecialty:"ជំនាញ",navConsult:"ប្រឹក្សា",navGuide:"មគ្គុទ្ទេសក៍សុខភាព",navDoctor:"សម្រាប់វេជ្ជបណ្ឌិត",navAccount:"គណនី",brandSubtitle:"មន្ទីរពេទ្យអន្តរជាតិ",brandDesc:"ប្រព័ន្ធកក់ពេលវេលាពិនិត្យសុខភាពតាមអនឡាញឈានមុខគេនៅវៀតណាម។ ភ្ជាប់អ្នកជាមួយវេជ្ជបណ្ឌិតជំនាញដែលមានកេរ្តិ៍ឈ្មោះ។",statPatients:"អ្នកជម្ងឺ",statDoctors:"វេជ្ជបណ្ឌិត",statSpecialties:"ជំនាញ",patientTitle:"សម្រាប់អ្នកជម្ងឺ",patientLink1:"ស្វែងរកវេជ្ជបណ្ឌិត",patientLink2:"ចូលប្រព័ន្ធ",patientLink3:"ចុះឈ្មោះ",patientLink4:"កក់ពេលវេលា",patientLink5:"តារាងត្រួតពិនិត្យការណាត់ជួប",doctorTitle:"សម្រាប់វេជ្ជបណ្ឌិត",doctorLink1:"ត្រួតពិនិត្យការណាត់ជួប",doctorLink2:"ជជែក",doctorLink3:"ចូលប្រព័ន្ធ",doctorLink4:"ចុះឈ្មោះ",doctorLink5:"ផ្ទាំងគ្រប់គ្រងរបស់វេជ្ជបណ្ឌិត",contactTitle:"ទាក់ទង",contactAddress:"<strong>អាសយដ្ឋាន:</strong><br>ក្រសួងសុខាភិបាល - មន្ទីរពេទ្យពហុជំនាញអន្តរជាតិ<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",contactHotline:"<strong>Hotline:</strong><br>0433636050",contactEmail:"<strong>Email:</strong><br>benhviendakhoaquocte@gmail.com",socialTitle:"ភ្ជាប់ជាមួយយើង",copyright:"© ២០២៤ <strong>KhamCare មន្ទីរពេទ្យអន្តរជាតិ</strong>។ រក្សាសិទ្ធិគ្រប់យ៉ាង។",privacy:"គោលការណ៍ភាពឯកជន",terms:"លក្ខខណ្ឌប្រើប្រាស់",support:"ការគាំទ្រអតិថិជន"}};function applyFooterTranslation(e){const t=footerTranslations[e]||footerTranslations.vi;const ns=document.getElementById("navSearch"),nsp=document.getElementById("navSpecialty"),nc=document.getElementById("navConsult"),ng=document.getElementById("navGuide"),ndt=document.getElementById("navDoctorText"),na=document.getElementById("navAccount");ns&&(ns.textContent=t.navSearch);nsp&&(nsp.textContent=t.navSpecialty);nc&&(nc.textContent=t.navConsult);ng&&(ng.textContent=t.navGuide);ndt&&(ndt.textContent=t.navDoctor);na&&(na.textContent=t.navAccount);const a=document.querySelector(".brand-info p"),n=document.querySelector(".brand-desc");a&&(a.textContent=t.brandSubtitle),n&&(n.textContent=t.brandDesc);const o=document.querySelectorAll(".brand-stats .label");o[0]&&(o[0].textContent=t.statPatients),o[1]&&(o[1].textContent=t.statDoctors),o[2]&&(o[2].textContent=t.statSpecialties);const r=document.querySelector(".footer-column:nth-child(2) h3");r&&(r.innerHTML='<i class="fas fa-user-injured"></i> '+t.patientTitle);const s=document.querySelectorAll(".footer-column:nth-child(2) a"),i=[t.patientLink1,t.patientLink2,t.patientLink3,t.patientLink4,t.patientLink5];s.forEach(((e,t)=>{const a=e.querySelector("i");a&&i[t]&&(e.innerHTML=a.outerHTML+" "+i[t])}));const l=document.querySelector(".footer-column:nth-child(3) h3");l&&(l.innerHTML='<i class="fas fa-user-md"></i> '+t.doctorTitle);const c=document.querySelectorAll(".footer-column:nth-child(3) a"),d=[t.doctorLink1,t.doctorLink2,t.doctorLink3,t.doctorLink4,t.doctorLink5];c.forEach(((e,t)=>{const a=e.querySelector("i");a&&d[t]&&(e.innerHTML=a.outerHTML+" "+d[t])}));const u=document.querySelector(".contact-column h3");u&&(u.innerHTML='<i class="fas fa-phone-alt"></i> '+t.contactTitle);const m=document.querySelectorAll(".contact-item div");m[0]&&(m[0].innerHTML=t.contactAddress),m[1]&&(m[1].innerHTML=t.contactHotline),m[2]&&(m[2].innerHTML=t.contactEmail);const p=document.querySelector(".social-section h4");p&&(p.textContent=t.socialTitle);const h=document.querySelector(".footer-bottom p");h&&(h.innerHTML=t.copyright);const f=document.querySelectorAll(".bottom-links a");f[0]&&(f[0].textContent=t.privacy),f[1]&&(f[1].textContent=t.terms),f[2]&&(f[2].textContent=t.support)}

    // Đồng bộ ngôn ngữ giữa các trang
    window.addEventListener('storage', function(e) {
      if (e.key === 'kham_lang' && e.newValue) {
        console.log('🔄 Language changed in another tab, reloading page...');
        location.reload();
      }
    });

    // Lắng nghe BroadcastChannel (cho trình duyệt hiện đại)
    if (typeof BroadcastChannel !== 'undefined') {
      const channel = new BroadcastChannel('kham_lang_sync');
      channel.addEventListener('message', function(event) {
        if (event.data.type === 'LANGUAGE_CHANGED') {
          console.log('🔄 Language changed via BroadcastChannel, reloading...');
          location.reload();
        }
      });
    }
    </script>
</body>
</html>

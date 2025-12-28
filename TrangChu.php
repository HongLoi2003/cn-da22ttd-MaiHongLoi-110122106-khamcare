<?php
session_start();
require_once 'db_config.php';

// Kiểm tra đăng nhập (không bắt buộc cho trang chủ)
$user = null;
$isLoggedIn = false;

if (isset($_SESSION['user_id'])) {
    $user = getUserById($_SESSION['user_id']);
    if ($user) {
        $isLoggedIn = true;
        // Bác sĩ có thể xem trang chủ, không tự động redirect
        // Họ có thể vào dashboard từ menu
    }
}
$specialtiesList = getSpecialties();

// Sử dụng helper để lấy dữ liệu bác sĩ từ view_doctor.php
require_once 'doctor_data_helper.php';
$topDoctors = getTopDoctorsFromViewDoctor(18);

// Mapping ảnh cho các bác sĩ (hỗ trợ cả ID 1-18 và 1001-1018)
$imageMap = [
    // ID 1001-1018 (mock data)
    1001 => 'image/nguyenthilan.png.png',      // BS. Nguyễn Thị Lan - Tim Mạch
    1002 => 'image/tranvanhung.png.png',       // BS. Trần Văn Hùng - Thần Kinh
    1003 => 'image/lethimai.png.png',          // BS. Lê Thị Mai - Nhi Khoa
    1004 => 'image/leminhtuan.png.png',        // BS. Lê Minh Tuấn - Da Liễu
    1005 => 'image/tranthihuong.png.png',      // BS. Trần Thị Hương - Sản Phụ Khoa
    1006 => 'image/ngnuyenvanduc.png.webp',    // BS. Nguyễn Văn Đức - Tim Mạch
    1007 => 'image/dovanhung.png.jpg',         // BS. Đỗ Văn Hùng - Thần Kinh
    1008 => 'image/hoangminhhoang.png.png',    // BS. Hoàng Minh Hoàng - Nhi Khoa
    1009 => 'image/phamthilan.png.png',        // BS. Phạm Thị Linh - Da Liễu
    1010 => 'image/vuthimai.png.png',          // BS. Vũ Thị Mai - Tim Mạch
    1011 => 'image/nguyenducminh.png.png',     // BS. Nguyễn Đức Minh - Thần Kinh
    1012 => 'image/tranvannam.png.png',        // BS. Trần Văn Nam - Da Liễu
    1013 => 'image/phamthilan.png.png',        // BS. Phạm Thị Lan - Nhi Khoa
    1014 => 'image/lethihoa.png.png',          // BS. Lê Thị Hoa - Sản Phụ Khoa
    1015 => 'image/nguyenthihanh.png.png',     // BS. Nguyễn Thị Hạnh - Sản Phụ Khoa
    1016 => 'image/nguyenvanhoa.png.png',      // BS. Nguyễn Văn Hòa - Nội Tổng Quát
    1017 => 'image/tranthiphu.png.png',        // BS. Trần Thị Thu - Nội Tổng Quát
    1018 => 'image/leminhtam.png.png',         // BS. Lê Minh Tâm - Nội Tổng Quát
    // ID 1-18 (database)
    1 => 'image/nguyenthilan.png.png',
    2 => 'image/tranvanhung.png.png', 
    3 => 'image/tranthihuong.png.png',
    4 => 'image/leminhtuan.png.png',
    5 => 'image/lethimai.png.png',
    6 => 'image/nguyenvanhoa.png.png',
    7 => 'image/ngnuyenvanduc.png.webp',
    8 => 'image/vuthimai.png.png',
    9 => 'image/dovanhung.png.jpg',
    10 => 'image/nguyenducminh.png.png',
    11 => 'image/lethihoa.png.png',
    12 => 'image/nguyenthihanh.png.png',
    13 => 'image/phamthilan.png.png',
    14 => 'image/tranvannam.png.png',
    15 => 'image/hoangminhhoang.png.png',
    16 => 'image/phamthilan.png.png',
    17 => 'image/tranthiphu.png.png',
    18 => 'image/leminhtam.png.png'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>KhamCare - Đặt lịch khám bệnh dễ dàng</title>
    <link rel="stylesheet" href="style.css">
    <!-- Liên kết Font Awesome để dùng icon (nếu muốn) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="global-font-override.css">
    <link rel="stylesheet" href="square-icons-override.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <link rel="stylesheet" href="fix-emoji-icons.css">
    <link rel="stylesheet" href="doctor-card-modern.css">
    <link rel="stylesheet" href="doctor-card-compact.css">
    <link rel="stylesheet" href="doctor-team-section.css">
    <link rel="stylesheet" href="mobile-responsive.css">
    <style>
        /* Mobile Fix - Prevent horizontal scroll */
        * {
            box-sizing: border-box;
        }
        html, body {
            max-width: 100vw !important;
            overflow-x: hidden !important;
        }
        img, video, iframe {
            max-width: 100%;
            height: auto;
        }
        
        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: linear-gradient(135deg, #0ea5e9, #06b6d4);
            color: white;
            border: none;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 1001;
        }
        
        /* Force responsive on mobile */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block !important;
            }
            
            .main-nav {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255,255,255,0.98);
                z-index: 1000;
                padding: 80px 20px 20px;
                overflow-y: auto;
            }
            
            .main-nav.active {
                display: block !important;
            }
            
            .main-nav ul {
                flex-direction: column !important;
                gap: 5px !important;
            }
            
            .main-nav ul li {
                width: 100%;
            }
            
            .main-nav ul li a {
                display: block;
                padding: 15px 20px !important;
                background: #f8fafc;
                border-radius: 10px;
                margin-bottom: 5px;
                font-size: 1rem;
                color: #1e293b;
                text-decoration: none;
            }
            
            .main-nav ul li a:hover {
                background: #e0f2fe;
            }
            
            .btn-login, .btn-register {
                text-align: center !important;
            }
            
            .account-dropdown-wrapper {
                width: 100%;
            }
            
            .account-dropdown-wrapper .btn-account {
                width: 100%;
                justify-content: center;
            }
            
            .language-selector {
                width: 100%;
            }
            
            .language-selector .lang-toggle {
                width: 100%;
                justify-content: center;
            }
            
            .container,
            .hero-content,
            .specialties-grid,
            .doctors-grid,
            .comprehensive-grid,
            .footer-main-grid,
            .search-layout,
            .specialty-tabs,
            section,
            .section {
                max-width: 100% !important;
                width: 100% !important;
                padding-left: 15px !important;
                padding-right: 15px !important;
                overflow-x: hidden !important;
            }
            
            .header .container {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 10px 15px !important;
            }
            
            .hero-section {
                padding: 20px 0 !important;
            }
            
            .hero-content h1 {
                font-size: 1.5rem !important;
            }
            
            .hero-content p {
                font-size: 0.95rem !important;
            }
            
            .specialties-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 10px !important;
                flex-wrap: wrap !important;
            }
            
            .specialty-card {
                min-width: auto !important;
                width: 100% !important;
            }
            
            .doctors-grid {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }
            
            .doctor-card {
                max-width: 100% !important;
                width: 100% !important;
            }
            
            .footer-main-grid {
                grid-template-columns: 1fr !important;
                gap: 20px !important;
            }
            
            .comprehensive-grid {
                grid-template-columns: 1fr !important;
            }
            
            .comprehensive-card {
                min-width: auto !important;
            }
        }

        :root {
            /* Modern Color Palette */
            --primary: #6366f1;
            --primary-light: #8b5cf6;
            --primary-dark: #4f46e5;
            --secondary: #f8fafc;
            --accent: #06b6d4;
            --accent-light: #67e8f9;
            --accent-dark: #0891b2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
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
            --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            
            /* Transitions */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Medical-themed Gradients */
            --gradient-primary: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            --gradient-accent: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            --gradient-hero: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 50%, #7dd3fc 100%);
            --gradient-medical: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            --gradient-card: linear-gradient(145deg, rgba(255,255,255,0.95) 0%, rgba(240,249,255,0.8) 100%);
        }
        /* Global Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            font-size: 16px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: 
                radial-gradient(circle at 20% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            background-attachment: fixed;
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
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
        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
        }

        .header:hover {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            gap: 20px;
            flex-wrap: nowrap;
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            background: transparent;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: logoFloat 3s ease-in-out infinite, logoPulse 2s ease-in-out infinite;
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
        /* Navigation Styles */
        .main-nav ul {
            list-style: none;
            display: flex;
            gap: 12px;
            margin: 0;
            padding: 0;
            align-items: center;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .main-nav li {
            position: relative;
            flex-shrink: 0;
        }

        .main-nav a {
            text-decoration: none;
            color: #06b6d4;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 8px 12px;
            border-radius: var(--radius-lg);
            transition: var(--transition);
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            white-space: nowrap;
        }

        .main-nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%);
            border-radius: var(--radius-lg);
            opacity: 0;
            transform: scale(0.8);
            transition: var(--transition);
            z-index: -1;
        }

        .main-nav a:hover {
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }

        .main-nav a:hover::before {
            opacity: 1;
            transform: scale(1);
        }

        .main-nav a:active {
            transform: translateY(0);
        }

        /* Button Account Style */
        .btn-account {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%) !important;
            color: white !important;
            border: none;
            border-radius: 10px;
            padding: 8px 16px !important;
            font-size: 0.95rem !important;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
            white-space: nowrap;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-account:hover {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4) !important;
            color: white !important;
        }

        .btn-account::before {
            display: none !important;
        }

        .btn-account i.fa-user {
            font-size: 1rem;
        }

        .btn-account .arrow {
            font-size: 0.8rem;
            transition: transform 0.3s ease;
        }

        .account-dropdown-wrapper.active .btn-account .arrow {
            transform: rotate(180deg);
        }

        /* Login & Register Buttons */
        .btn-login, .btn-register {
            background: white !important;
            color: #06b6d4 !important;
            border: 2px solid #06b6d4;
            border-radius: 10px;
            padding: 8px 16px !important;
            font-size: 0.95rem !important;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            white-space: nowrap;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-login:hover, .btn-register:hover {
            background: #06b6d4 !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 182, 212, 0.3);
        }

        .btn-login::before, .btn-register::before {
            display: none !important;
        }

        .btn-register {
            background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 100%) !important;
            color: white !important;
            border: none;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%) !important;
            color: white !important;
        }

        /* Account Dropdown Menu */
        .account-dropdown-wrapper {
            position: relative;
        }

        .account-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 24px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            min-width: 300px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 9999;
            overflow: hidden;
            border: 2px solid #e0f2fe;
            padding: 20px;
        }

        .account-dropdown-wrapper.active .account-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .account-dropdown-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 20px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 16px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1.15rem;
            margin-bottom: 10px;
        }

        .account-dropdown-item:last-child {
            margin-bottom: 0;
        }

        .account-dropdown-item:hover {
            background: rgba(14, 165, 233, 0.08);
            color: #0ea5e9;
            transform: translateX(4px);
        }

        .account-dropdown-item i {
            font-size: 1.5rem;
            color: #0ea5e9;
            flex-shrink: 0;
            width: 30px;
            text-align: center;
        }

        .account-dropdown-item.logout {
            color: #ef4444;
        }

        .account-dropdown-item.logout:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .account-dropdown-item.logout i {
            color: #ef4444;
        }

        /* Language Selector Styles */
        .language-selector {
            position: relative;
        }

        .lang-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        }

        .lang-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
        }

        .lang-toggle i {
            font-size: 1.1rem;
        }

        .lang-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            min-width: 280px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow: hidden;
            border: 1px solid rgba(14, 165, 233, 0.1);
        }

        .lang-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .lang-option {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 1px solid #e5e7eb;
        }

        .lang-option:last-child {
            border-bottom: none;
        }

        .lang-option:hover {
            background: linear-gradient(90deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .lang-option.active {
            background: linear-gradient(135deg, #22d3ee 0%, #0ea5e9 100%);
            color: white;
        }

        .lang-option.active .lang-country,
        .lang-option.active .lang-name,
        .lang-option.active .lang-code {
            color: white;
        }

        .lang-country {
            font-weight: 700;
            font-size: 1rem;
            color: #374151;
            min-width: 35px;
        }

        .lang-name {
            flex: 1;
            font-weight: 600;
            font-size: 1.1rem;
            color: #374151;
            text-align: center;
        }

        .lang-code {
            font-weight: 700;
            font-size: 1rem;
            color: #0ea5e9;
            min-width: 35px;
            text-align: right;
        }

        .lang-option.active .lang-code {
            color: white;
        }
        /* Button Styles - Medical Theme */
        .btn, .btn-primary, .btn-search {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius-lg);
            padding: 14px 28px;
            font-size: 1.1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            box-shadow: 
                0 8px 16px rgba(14, 165, 233, 0.2),
                0 4px 8px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
            outline: none;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn::before, .btn-primary::before, .btn-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition-slow);
        }

        .btn:hover, .btn-primary:hover, .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 12px 24px rgba(14, 165, 233, 0.3),
                0 8px 16px rgba(0, 0, 0, 0.15);
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .btn:hover::before, .btn-primary:hover::before, .btn-search:hover::before {
            left: 100%;
        }

        .btn:active, .btn-primary:active, .btn-search:active {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }
        /* Hero Section - Medical Theme */
        .hero-section {
            background: 
                radial-gradient(ellipse at top, rgba(14, 165, 233, 0.15) 0%, transparent 60%),
                radial-gradient(ellipse at bottom left, rgba(16, 185, 129, 0.1) 0%, transparent 60%),
                linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #bae6fd 100%);
            position: relative;
            padding: 120px 0 80px 0;
            min-height: 70vh;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        /* Medical cross pattern */
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="medical-cross" width="60" height="60" patternUnits="userSpaceOnUse"><g fill="rgba(14, 165, 233, 0.08)"><rect x="25" y="15" width="10" height="30"/><rect x="15" y="25" width="30" height="10"/></g></pattern></defs><rect width="60" height="60" fill="url(%23medical-cross)"/></svg>');
            background-size: 120px 120px;
            opacity: 0.3;
            z-index: 1;
        }

        /* Floating medical icons */
        .hero-section::after {
            content: '';
            position: absolute;
            top: 10%;
            right: 10%;
            width: 200px;
            height: 200px;
            background: 
                radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
            border-radius: 12px;
            z-index: 1;
            animation: float 15s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        /* Medical decorative elements */
        .hero-section .medical-icons {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 1;
        }

        .hero-section .medical-icons::before {
            content: '🏥';
            position: absolute;
            top: 15%;
            left: 10%;
            font-size: 3rem;
            opacity: 0.1;
            animation: float 20s ease-in-out infinite;
        }

        .hero-section .medical-icons::after {
            content: '⚕️';
            position: absolute;
            top: 25%;
            right: 15%;
            font-size: 2.5rem;
            opacity: 0.1;
            animation: float 25s ease-in-out infinite reverse;
        }

        /* Additional medical symbols */
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="medical-pattern" width="100" height="100" patternUnits="userSpaceOnUse"><g fill="rgba(14, 165, 233, 0.05)"><circle cx="20" cy="20" r="2"/><circle cx="80" cy="80" r="1.5"/><circle cx="50" cy="10" r="1"/><circle cx="10" cy="60" r="1"/><circle cx="90" cy="40" r="1.5"/><rect x="45" y="35" width="10" height="30" rx="2"/><rect x="35" y="45" width="30" height="10" rx="2"/></g></pattern></defs><rect width="100" height="100" fill="url(%23medical-pattern)"/></svg>');
            background-size: 200px 200px;
            opacity: 0.3;
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            text-align: center;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 800;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 50%, #0ea5e9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 10px rgba(14, 165, 233, 0.2);
            animation: slideInUp 1s ease-out;
            position: relative;
        }

        .hero-content h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            right: 0;
            margin: 0 auto;
            width: 420px;
            height: 6px;
            background: linear-gradient(90deg, #0ea5e9, #10b981);
            border-radius: 3px;
            animation: slideInUp 1s ease-out 0.5s both;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero-bg {
            display: none;
        }
        /* Search Bar - Medical Theme */
        .search-bar {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-2xl);
            box-shadow: 
                0 20px 40px rgba(14, 165, 233, 0.1),
                0 8px 16px rgba(0, 0, 0, 0.1);
            padding: 20px 24px;
            gap: 16px;
            position: relative;
            max-width: 900px;
            width: 100%;
            margin: 40px auto 0;
            border: 2px solid rgba(14, 165, 233, 0.2);
            transition: var(--transition);
            animation: slideInUp 1s ease-out 0.3s both;
        }

        .search-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(16, 185, 129, 0.05) 100%);
            border-radius: var(--radius-2xl);
            z-index: -1;
        }

        .search-bar:hover {
            transform: translateY(-3px);
            box-shadow: 
                0 25px 50px rgba(14, 165, 233, 0.15),
                0 12px 24px rgba(0, 0, 0, 0.15);
            border-color: rgba(14, 165, 233, 0.4);
        }
        .search-bar i.fa-search {
            color: #0ea5e9;
            font-size: 1.5rem;
            opacity: 0.8;
            transition: var(--transition);
            filter: drop-shadow(0 2px 4px rgba(14, 165, 233, 0.2));
        }

        .search-bar:focus-within i.fa-search {
            color: #10b981;
            opacity: 1;
            transform: scale(1.1);
            filter: drop-shadow(0 4px 8px rgba(16, 185, 129, 0.3));
        }

        .search-bar input[type="text"] {
            border: none;
            outline: none;
            font-size: 1.2rem;
            flex: 1;
            padding: 16px 12px;
            background: transparent;
            color: var(--gray-800);
            font-family: inherit;
            font-weight: 500;
            transition: var(--transition);
        }

        .search-bar input[type="text"]::placeholder {
            color: var(--gray-500);
            font-weight: 400;
        }

        .search-bar select {
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 14px 18px;
            font-size: 1.1rem;
            background: var(--white);
            color: var(--gray-700);
            font-family: inherit;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            min-width: 140px;
        }

        .search-bar select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        .btn-search {
            margin-left: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
            padding: 16px 24px;
            border-radius: var(--radius-lg);
            white-space: nowrap;
        }

    .suggestions {
        position: absolute;
        top: 62px;
        left: 38px;
        right: 130px;
        background: #fff;
        border: 1.5px solid #e0e7ef;
        border-radius: 0 0 10px 10px;
        box-shadow: 0 4px 16px rgba(37,99,235,0.10);
        z-index: 10;
        display: none;
        max-height: 200px;
        overflow-y: auto;
        font-size: 1.05rem;
    }
    .suggestion-item {
        padding: 10px 16px;
        cursor: pointer;
        transition: background var(--transition);
        border-bottom: 1px solid #f1f5f9;
    }
    .suggestion-item:last-child {
        border-bottom: none;
    }
    .suggestion-item:hover {
        background: #e0e7ff;
    }
    .hero-bg {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 48%;
        z-index: 1;
        display: flex;
        align-items: flex-end;
        justify-content: flex-end;
        pointer-events: none;
    }
    .hero-bg img {
        width: 100%;
        max-width: 480px;
        opacity: 0.97;
        object-fit: contain;
        margin-bottom: -24px;
        filter: drop-shadow(0 8px 32px rgba(37,99,235,0.10));
    }
        /* Specialties Section - Enhanced to match Comprehensive Medical */
        .specialties-section {
            background: 
                radial-gradient(ellipse at top left, rgba(16, 185, 129, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse at bottom right, rgba(14, 165, 233, 0.06) 0%, transparent 60%),
                linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
            padding: 120px 0 100px 0;
            position: relative;
            margin-top: -40px;
            border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
            box-shadow: 
                0 25px 50px rgba(14, 165, 233, 0.1),
                0 15px 30px rgba(0, 0, 0, 0.08);
            z-index: 10;
            overflow: hidden;
        }

        /* Enhanced background patterns */
        .specialties-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="specialty-pattern" width="60" height="60" patternUnits="userSpaceOnUse"><g fill="rgba(16, 185, 129, 0.04)"><circle cx="15" cy="15" r="1.5"/><circle cx="45" cy="45" r="1"/><rect x="25" y="20" width="10" height="20" rx="2"/><rect x="20" y="25" width="20" height="10" rx="2"/></g></pattern></defs><rect width="60" height="60" fill="url(%23specialty-pattern)"/></svg>');
            background-size: 120px 120px;
            opacity: 0.5;
            pointer-events: none;
            z-index: 1;
        }

        /* Floating elements */
        .specialties-section::after {
            content: '';
            position: absolute;
            top: 20%;
            left: 5%;
            width: 120px;
            height: 120px;
            background: 
                radial-gradient(circle, rgba(14, 165, 233, 0.06) 0%, transparent 70%);
            border-radius: 12px;
            z-index: 1;
            animation: float-specialty 18s ease-in-out infinite;
        }

        @keyframes float-specialty {
            0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); }
            33% { transform: translateY(-12px) rotate(2deg) scale(1.05); }
            66% { transform: translateY(8px) rotate(-1deg) scale(0.95); }
        }

        .specialties-section h2 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: clamp(2.3rem, 4.5vw, 3.5rem);
            background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 80px;
            font-weight: 900;
            text-align: center;
            letter-spacing: -0.03em;
            position: relative;
            z-index: 2;
            line-height: 1.1;
            text-shadow: 0 4px 20px rgba(14, 165, 233, 0.15);
        }

        .specialties-section h2::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 5px;
            background: linear-gradient(90deg, #0ea5e9 0%, #10b981 50%, #06b6d4 100%);
            border-radius: 3px;
            box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
            animation: pulse-glow 3s ease-in-out infinite;
        }
        .specialties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            justify-items: center;
            align-items: start;
            gap: 32px;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Section đầu hiển thị 6 chuyên khoa trên 1 hàng */
        .specialties-section .specialties-grid {
            max-width: 1200px;
            display: flex;
            flex-wrap: nowrap;
            justify-content: center;
            gap: 16px;
        }
        
        /* Section thứ 2 hiển thị 6 items */
        .medical-specialties-section .specialties-grid {
            max-width: 1200px;
            grid-template-columns: repeat(auto-fit, minmax(160px, 180px));
        }

        .specialty-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 28px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--gray-800);
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            cursor: pointer;
            border: 2px solid #e0f2fe;
            overflow: visible;
            min-height: 180px;
            min-width: 160px;
            max-width: 200px;
            justify-content: center;
            flex: 0 0 auto;
        }

        .specialty-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);
            border-radius: 16px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }

        .specialty-item:hover::before {
            opacity: 1;
        }

        .specialty-item:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 12px 32px rgba(14, 165, 233, 0.2);
            border-color: #0ea5e9;
        }

        .specialty-item span {
            position: relative;
            z-index: 1;
        }
        .icon-box {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 2rem;
            color: white;
            box-shadow: 0 6px 16px rgba(14, 165, 233, 0.25);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .icon-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .specialty-item:hover .icon-box::before {
            opacity: 1;
        }

        .icon-box i,
        .icon-box span {
            display: inline-block;
            font-size: 2rem;
            color: white;
            line-height: 1;
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .icon-box span {
            font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif;
        }

        .specialty-item:hover .icon-box {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(14, 165, 233, 0.3);
        }
    
    /* CSS cho chuyên khoa được chọn */
    .specialty-item.selected,
    .specialty-item.active {
        background: #e0f2fe !important;
        border-color: #0ea5e9 !important;
        transform: translateY(-4px) scale(1.03);
        box-shadow: 0 8px 32px rgba(14, 165, 233, 0.2) !important;
    }
    
    .specialty-item.selected .specialty-icon,
    .specialty-item.active .specialty-icon,
    .specialty-item.selected .icon-box,
    .specialty-item.active .icon-box {
        border-color: #0ea5e9 !important;
        background: linear-gradient(135deg, #0ea5e9, #38bdf8) !important;
        animation: pulse 1.5s infinite;
    }
    
    .specialty-item.selected .specialty-icon i,
    .specialty-item.active .specialty-icon i,
    .specialty-item.selected .icon-box i,
    .specialty-item.active .icon-box i {
        color: #fff !important;
    }
    
    .specialty-item.selected .specialty-name,
    .specialty-item.active .specialty-name,
    .specialty-item.selected > span,
    .specialty-item.active > span {
        color: #0ea5e9 !important;
        font-weight: 700 !important;
    }
    
    .specialty-item.selected .specialty-dot {
        background: #0ea5e9 !important;
        animation: pulse 1.5s infinite;
    }
    
    .consultation-item {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 2px solid #0ea5e9;
        position: relative;
        overflow: hidden;
    }
    
    .consultation-item::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(14, 165, 233, 0.1), transparent);
        transform: rotate(45deg);
        animation: shimmer 3s infinite;
    }
    
    .consultation-item .icon-box {
        background: linear-gradient(135deg, #0ea5e9, #38bdf8);
        border-color: #0ea5e9;
        animation: pulse 2s infinite;
    }
    
    .consultation-item:hover {
        transform: translateY(-6px) scale(1.05);
        box-shadow: 0 12px 40px rgba(14, 165, 233, 0.2);
        border-color: #0284c7;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    /* Comprehensive Medical Section - Enhanced */
    .comprehensive-medical-section {
        background: 
            radial-gradient(ellipse at top, rgba(14, 165, 233, 0.08) 0%, transparent 60%),
            radial-gradient(ellipse at bottom right, rgba(16, 185, 129, 0.06) 0%, transparent 60%),
            linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
        padding: 120px 0 100px 0;
        position: relative;
        overflow: hidden;
    }

    /* Enhanced background patterns */
    .comprehensive-medical-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><defs><pattern id="medical-grid" width="80" height="80" patternUnits="userSpaceOnUse"><g fill="rgba(14, 165, 233, 0.04)"><circle cx="20" cy="20" r="1.5"/><circle cx="60" cy="60" r="1"/><rect x="35" y="25" width="10" height="30" rx="2"/><rect x="25" y="35" width="30" height="10" rx="2"/></g></pattern></defs><rect width="80" height="80" fill="url(%23medical-grid)"/></svg>');
        background-size: 160px 160px;
        opacity: 0.4;
        pointer-events: none;
        z-index: 1;
    }

    /* Floating medical elements */
    .comprehensive-medical-section::after {
        content: '';
        position: absolute;
        top: 15%;
        right: 8%;
        width: 150px;
        height: 150px;
        background: 
            radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
        border-radius: 12px;
        z-index: 1;
        animation: float-medical 20s ease-in-out infinite;
    }

    @keyframes float-medical {
        0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); }
        33% { transform: translateY(-15px) rotate(3deg) scale(1.05); }
        66% { transform: translateY(10px) rotate(-2deg) scale(0.95); }
    }

    .section-header {
        text-align: center;
        margin-bottom: 80px;
        position: relative;
        z-index: 2;
    }

    .section-header h2 {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        font-size: clamp(2.5rem, 5vw, 3.8rem);
        background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 900;
        margin-bottom: 25px;
        letter-spacing: -0.03em;
        line-height: 1.1;
        position: relative;
        text-shadow: 0 4px 20px rgba(14, 165, 233, 0.15);
    }

    .section-header h2::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 120px;
        height: 6px;
        background: linear-gradient(90deg, #0ea5e9 0%, #10b981 50%, #06b6d4 100%);
        border-radius: 3px;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .section-underline {
        width: 100px;
        height: 5px;
        background: linear-gradient(90deg, #0ea5e9, #10b981, #06b6d4);
        margin: 0 auto 25px;
        border-radius: 3px;
        box-shadow: 0 4px 15px rgba(14, 165, 233, 0.25);
        animation: pulse-glow 3s ease-in-out infinite;
    }

    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 4px 15px rgba(14, 165, 233, 0.25); }
        50% { box-shadow: 0 6px 25px rgba(14, 165, 233, 0.4); }
    }

    .section-description {
        font-size: 1.3rem;
        color: var(--gray-600);
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.7;
        font-weight: 500;
        text-align: center;
    }

    .comprehensive-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
        max-width: 1300px;
        margin: 0 auto;
        padding: 0 25px;
        position: relative;
        z-index: 2;
    }

    .comprehensive-item {
        background: white;
        border-radius: 12px;
        padding: 20px 18px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 2px solid #f1f5f9;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        min-height: 140px;
    }

    .comprehensive-item:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 24px rgba(14, 165, 233, 0.15);
        border-color: #0ea5e9;
    }

    .comprehensive-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.2);
        transition: all 0.3s ease;
        flex-shrink: 0;
    }

    .comprehensive-icon span {
        font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    .comprehensive-item:hover .comprehensive-icon {
        transform: scale(1.15) rotate(5deg);
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.35);
    }

    .comprehensive-content {
        flex: 1;
        position: relative;
        z-index: 2;
    }

    .comprehensive-content h4 {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--gray-800);
        margin-bottom: 6px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.3;
    }

    .comprehensive-content p {
        color: var(--gray-600);
        line-height: 1.5;
        margin-bottom: 10px;
        font-size: 0.9rem;
        font-weight: 400;
    }

    .comprehensive-stats {
        display: flex;
        gap: 25px;
        align-items: center;
        flex-wrap: wrap;
    }

    .comprehensive-stats span {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        color: var(--gray-500);
        font-weight: 600;
        padding: 6px 12px;
        background: rgba(14, 165, 233, 0.05);
        border-radius: var(--radius);
        border: 1px solid rgba(14, 165, 233, 0.1);
        transition: var(--transition);
    }

    .comprehensive-stats span:hover {
        background: rgba(14, 165, 233, 0.1);
        border-color: rgba(14, 165, 233, 0.2);
        transform: translateY(-1px);
    }

    .comprehensive-stats i {
        color: var(--primary);
        font-size: 0.9rem;
    }

    .comprehensive-cta {
        text-align: center;
        margin-top: 80px;
        position: relative;
        z-index: 2;
    }

    .btn-view-all {
        background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #10b981 100%);
        color: var(--white);
        border: none;
        border-radius: var(--radius-xl);
        padding: 20px 40px;
        font-size: 1.2rem;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 
            0 15px 30px rgba(14, 165, 233, 0.25),
            0 8px 16px rgba(0, 0, 0, 0.1);
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        position: relative;
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .btn-view-all::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: var(--transition-slow);
    }

    .btn-view-all:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 
            0 25px 50px rgba(14, 165, 233, 0.35),
            0 15px 30px rgba(0, 0, 0, 0.15);
        background: linear-gradient(135deg, #10b981 0%, #06b6d4 50%, #0ea5e9 100%);
    }

    .btn-view-all:hover::before {
        left: 100%;
    }

    .btn-view-all i {
        font-size: 1.1rem;
        transition: var(--transition);
    }

    .btn-view-all:hover i {
        transform: scale(1.2) rotate(5deg);
    }

    /* Enhanced Responsive Design */
    @media (max-width: 1024px) {
        .comprehensive-grid {
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }
    }

    @media (max-width: 768px) {
        .comprehensive-medical-section {
            padding: 80px 0 60px 0;
        }
        
        .section-header {
            margin-bottom: 60px;
        }
        
        .comprehensive-grid {
            grid-template-columns: 1fr;
            gap: 25px;
            padding: 0 20px;
        }
        
        .comprehensive-item {
            padding: 25px 20px;
            flex-direction: column;
            text-align: center;
            align-items: center;
            min-height: auto;
        }
        
        .comprehensive-icon {
            width: 70px;
            height: 70px;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .comprehensive-stats {
            justify-content: center;
            gap: 15px;
        }
        
        .btn-view-all {
            padding: 16px 32px;
            font-size: 1.1rem;
        }
    }

    @media (max-width: 480px) {
        .comprehensive-item {
            padding: 20px 15px;
        }
        
        .comprehensive-icon {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }
        
        .comprehensive-content h4 {
            font-size: 1.3rem;
        }
        
        .comprehensive-stats {
            flex-direction: column;
            gap: 10px;
        }
    }

    .promo-item {
        background: linear-gradient(120deg, var(--primary) 60%, #e0e7ff 100%);
        color: #fff;
        box-shadow: 0 8px 32px rgba(37,99,235,0.13);
        min-width: 240px;
        min-height: 170px;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        border: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: box-shadow var(--transition), transform var(--transition);
    }
    .promo-item img {
        width: 68px;
        height: 68px;
        border-radius: 12px;
        object-fit: cover;
        margin-bottom: 14px;
        border: 3px solid #fff;
        background: #fff;
        box-shadow: 0 2px 8px rgba(37,99,235,0.10);
    }
    .promo-text {
        text-align: center;
    }
    .promo-text span {
        font-size: 1.15rem;
        font-weight: 700;
        display: block;
        margin-bottom: 6px;
        letter-spacing: 0.01em;
    }
    .promo-text p {
        font-size: 1rem;
        margin: 0;
        color: #f1f5f9;
        font-weight: 400;
    }
    /* Hide old footer styles */
    .footer {
        display: none;
    }
    .footer-links {
        display: none;
    }
    .copyright {
        text-align: center;
        font-size: 1rem;
        color: #e0e7ff;
        font-weight: 400;
        letter-spacing: 0.01em;
    }
    .copyright a {
        color: #fff;
        margin-left: 10px;
        font-size: 1.2rem;
        vertical-align: middle;
        transition: color var(--transition);
    }
    .copyright a:hover {
        color: var(--accent);
    }
    /* Popup tài khoản */
    .popup {
        display: none;
        position: fixed;
        z-index: 1001;
        left: 0; top: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.18);
        align-items: center;
        justify-content: center;
    }
    .popup-content {
        background: #fff;
        border-radius: 18px;
        padding: 38px 34px 28px 34px;
        min-width: 340px;
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        border: 1.5px solid #e0e7ef;
    }
    .popup-content h3 {
        margin: 16px 0 22px 0;
        color: var(--primary);
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }
    .popup-content input[type="text"], .popup-content input[type="password"] {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid #e0e7ef;
        border-radius: 8px;
        margin-bottom: 12px;
        font-size: 1.08rem;
        background: #f1f5f9;
        color: #222;
        font-family: inherit;
        transition: border-color var(--transition);
    }
    .popup-content input[type="text"]:focus, .popup-content input[type="password"]:focus {
        border-color: var(--primary);
    }

    /* Dropdown nav - unified styles */
    .nav-dd {
        position: absolute;
        left: 0; right: 0; top: 6px;
        background: #fff;
        border: 1.5px solid #e0e7ef;
        border-radius: 14px;
        box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        padding: 16px 18px 18px 18px;
        z-index: 120;
        display: none;
    }
    .nav-dd .dd-title {
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 10px;
        letter-spacing: 0.01em;
    }
    .nav-dd .dd-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }
    .nav-dd .dd-card {
        background: #f8fafc;
        border: 1.5px solid #e0e7ef;
        border-radius: 12px;
        padding: 12px 14px;
        color: #222;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: transform var(--transition), box-shadow var(--transition), border-color var(--transition);
    }
    .nav-dd .dd-card:hover {
        transform: translateY(-2px);
        border-color: var(--primary);
        box-shadow: 0 8px 20px rgba(37,99,235,0.12);
        background: #ffffff;
    }
    .nav-dd .dd-card .dd-emoji {
        font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif;
        font-style: normal;
        flex-shrink: 0;
    }
    .nav-dd .dd-card .dd-text { display: flex; flex-direction: column; }
    .nav-dd .dd-card .dd-name { font-weight: 700; }
    .nav-dd .dd-card .dd-sub { font-size: 0.9
    @media (max-width: 1000px) { .nav-dd .dd-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 560px) { .nav-dd .dd-grid { grid-template-columns: 1fr; } }
    /* Popover tài khoản nhỏ cạnh nút Tài khoản */
    #accountPopover {
        display: none;
        position: fixed;
        z-index: 1100;
        width: 280px;
        background: #fff;
        border: 1.5px solid #e0e7ef;
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(0,0,0,0.15);
        overflow: hidden;
    }
    #accountPopover .ap-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 14px;
        background: #f8fafc;
        border-bottom: 1px solid #eaeef4;
        font-weight: 700;
        color: var(--primary);
    }
    #accountPopover .ap-close { cursor: pointer; color: #94a3b8; font-size: 20px; }
    #accountPopover .ap-body { padding: 14px; }
    #accountPopover .ap-row { margin-bottom: 8px; }
    #accountPopover input {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid #e0e7ef;
        border-radius: 8px;
        background: #f1f5f9;
        font-size: 1rem;
    }
    #accountPopover .ap-actions { padding: 12px 14px 16px 14px; }
    /* Chat CSS đã được chuyển sang TuVanTrucTuyen.php */
    /* Chat input và message styles đã được chuyển sang TuVanTrucTuyen.php */
    
    /* Specialty buttons, doctors list, quick suggestions CSS đã được chuyển sang TuVanTrucTuyen.php */
    
    /* Recommended doctor, animations, và booking tips CSS đã được chuyển sang TuVanTrucTuyen.php */
    
    .booking-suggestions {
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        margin: 12px 0;
    }
    
    .suggestion-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 8px;
        border-left: 4px solid;
        background: #f8fafc;
        transition: all 0.3s ease;
    }
    
    .suggestion-card.priority {
        border-left-color: #10b981;
        background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
    }
    
    .suggestion-card.timing {
        border-left-color: #f59e0b;
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
    }
    
    .suggestion-card.preparation {
        border-left-color: #3b82f6;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
    }
    
    .suggestion-icon {
        font-size: 18px;
        width: 24px;
        text-align: center;
    }
    
    .suggestion-content h5 {
        margin: 0 0 2px 0;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
    }
    
    .suggestion-content p {
        margin: 0;
        font-size: 11px;
        color: #6b7280;
        line-height: 1.3;
    }
    
    .quick-booking-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 12px;
    }
    
    .quick-book-btn, .compare-doctors-btn {
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.3s ease;
    }
    
    .quick-book-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    .quick-book-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    
    .compare-doctors-btn {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
    }
    
    .compare-doctors-btn:hover {
        background: #e5e7eb;
        border-color: #9ca3af;
    }
    
    .comparison-table {
        max-width: 100%;
        overflow-x: auto;
    }
    
    .comparison-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr 1fr;
        gap: 1px;
        background: #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        font-size: 11px;
    }
    
    .comparison-header {
        display: contents;
    }
    
    .comparison-header > div {
        background: #374151;
        color: white;
        padding: 8px 6px;
        font-weight: 600;
        text-align: center;
    }
    
    .comparison-row {
        display: contents;
    }
    
    .comparison-row.recommended-row > div {
        background: #f0fdf4;
        border-left: 3px solid #10b981;
    }
    
    .comparison-row > div {
        background: white;
        padding: 8px 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    
    .doctor-cell {
        justify-content: flex-start !important;
        gap: 6px;
        text-align: left !important;
    }
    
    .mini-avatar {
        width: 32px;
        height: 32px;
        border-radius: 12px;
        object-fit: cover;
        flex-shrink: 0;
    }
    
    .doctor-name {
        font-weight: 600;
        font-size: 11px;
        color: #374151;
    }
    
    .doctor-bio {
        font-size: 9px;
        color: #6b7280;
        margin-top: 1px;
    }
    
    .rating-cell {
        flex-direction: column;
        gap: 2px;
    }
    
    .stars {
        color: #fbbf24;
        font-size: 10px;
    }
    
    .reviews {
        font-size: 9px;
        color: #6b7280;
    }
    
    .fee-cell {
        font-weight: 600;
        color: #059669;
        font-size: 10px;
    }
    
    .schedule-cell {
        font-size: 9px;
        color: #374151;
        line-height: 1.2;
    }
    
    .mini-book-btn {
        background: #10b981;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 9px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s ease;
    }
    
    .mini-book-btn:hover {
        background: #059669;
    }
    
    .comparison-note {
        background: #eff6ff;
        padding: 8px 12px;
        border-radius: 6px;
        margin-top: 8px;
        border-left: 3px solid #3b82f6;
    }
    
    .comparison-note p {
        margin: 0;
        font-size: 10px;
        color: #1e40af;
    }
    
    .time-based-suggestion {
        background: #f8fafc;
        border-radius: 8px;
        padding: 12px;
        border-left: 4px solid #10b981;
        margin: 8px 0;
    }
    
    .time-tips {
        margin-top: 8px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    
    .time-tip {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 11px;
        color: #374151;
    }
    
    .tip-icon {
        font-size: 12px;
        width: 16px;
        text-align: center;
    }
    
    .call-btn, .video-btn, .end-call-btn {
        transition: all 0.3s ease;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .call-btn:hover {
        background: #059669 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .video-btn:hover {
        background: #2563eb !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .end-call-btn:hover {
        background: #dc2626 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .call-status {
        background: #f0fdf4;
        border: 1px solid #10b981;
        border-radius: 8px;
        padding: 12px;
        margin: 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: pulse-green 2s infinite;
    }
    
    .call-status.video-call {
        background: #eff6ff;
        border-color: #3b82f6;
        animation: pulse-blue 2s infinite;
    }
    
    @keyframes pulse-blue {
        0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
        50% { box-shadow: 0 0 0 8px rgba(59, 130, 246, 0); }
    }
    
    .call-timer {
        font-weight: 600;
        color: #059669;
        font-family: monospace;
    }
    
    .call-quality {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        color: #6b7280;
    }
    
    .signal-bars {
        display: flex;
        gap: 1px;
        align-items: end;
    }
    
    .signal-bar {
        width: 3px;
        background: #10b981;
        border-radius: 1px;
    }
    
    .signal-bar:nth-child(1) { height: 4px; }
    .signal-bar:nth-child(2) { height: 6px; }
    .signal-bar:nth-child(3) { height: 8px; }
    .signal-bar:nth-child(4) { height: 10px; }
    
    @keyframes ring {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(-10deg); }
        75% { transform: rotate(10deg); }
    }
    
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .call-notification {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border-radius: 12px;
        padding: 16px;
        margin: 8px 0;
        box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
        animation: slideIn 0.5s ease-out;
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
    
    @media (max-width: 400px) {
        .quick-suggestions-grid {
            grid-template-columns: 1fr;
        }
        
        .doctor-actions-chat {
            flex-direction: column;
        }
        
        .btn-book-doctor, .btn-view-profile {
            width: 100%;
            justify-content: center;
        }
    }
    @media (max-width: 1100px) {
        .hero-bg { display: none; }
        .container { width: 98%; }
        .specialties-grid { 
            gap: 20px; 
            justify-content: center;
        }
        .specialty-item, .promo-item { 
            min-width: 160px; 
            max-width: 200px;
            padding: 25px 15px; 
        }
    }
    @media (max-width: 700px) {
        .header .container, .footer-links { flex-direction: column; gap: 12px; }
        .hero-section { padding: 30px 0 20px 0; }
        .specialties-section { padding: 24px 0 16px 0; }
        .specialties-grid { 
            flex-direction: row; 
            justify-content: center; 
            align-items: center;
            gap: 15px;
        }
        #chatOverlay, #chatBox { width: 98vw !important; height: 60vh !important; right: 0; bottom: 0; }
        .popup-content { min-width: 90vw; }
    }
    @media (max-width: 500px) {
        .logo { font-size: 1.2rem; }
        .logo img { width: 36px; height: 36px; }
        .hero-content h1 { font-size: 1.3rem; }
        .specialties-section h2 { font-size: 1.1rem; }
        .specialty-item, .promo-item { min-width: 90vw; padding: 12px 4vw; }
        .popup-content { padding: 18px 6vw; }
    }

    /* CSS cho các phần mới */
    
    /* Medical Specialties Section - Matching main specialty section */
    .medical-specialties-section {
        background: var(--white);
        padding: 100px 0 80px 0;
        position: relative;
        margin-top: -40px;
        border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
        box-shadow: var(--shadow-xl);
        z-index: 10;
    }

    .medical-specialties-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-primary);
        border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
    }

    .medical-specialties-section h2 {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        font-size: clamp(2rem, 4vw, 3rem);
        color: var(--gray-800);
        margin-bottom: 60px;
        font-weight: 800;
        text-align: center;
        letter-spacing: -0.02em;
        position: relative;
    }

    .medical-specialties-section h2::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: var(--gradient-accent);
        border-radius: 2px;
    }
    
    .specialties-grid {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        margin: 40px auto;
        flex-wrap: wrap;
        max-width: 1200px;
        padding: 0 20px;
    }
    
    .specialty-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        position: relative;
        cursor: pointer;
        transition: transform 0.3s ease;
        background: #f0f5fa;
        border-radius: 12px;
        padding: 20px;
        min-width: 120px;
        min-height: 140px;
        justify-content: center;
    }
    
    .specialty-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.15);
    }
    
    /* Medical Specialty Icons - Matching main icon-box style */
    .specialty-icon {
        width: 80px;
        height: 80px;
        background: var(--gradient-primary);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 24px;
        font-size: 2.5rem;
        color: var(--white);
        box-shadow: var(--shadow-lg);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        border: 3px solid rgba(255, 255, 255, 0.9);
    }

    .specialty-icon::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
        transform: rotate(45deg);
        transition: var(--transition-slow);
        opacity: 0;
    }
    
    .specialty-icon i {
        font-size: 2rem;
        color: var(--white);
        z-index: 2;
        position: relative;
    }
    
    .specialty-name {
        font-size: 1.2rem;
        color: var(--gray-800);
        font-weight: 600;
        margin-bottom: 0;
        text-align: center;
        transition: var(--transition);
    }
    
    .specialty-item:hover .specialty-icon {
        transform: scale(1.1) rotate(5deg);
        box-shadow: var(--shadow-xl);
        background: var(--gradient-accent);
    }

    .specialty-item:hover .specialty-icon::before {
        opacity: 1;
        animation: shimmer 1.5s ease-in-out;
    }

    .specialty-item:hover .specialty-name {
        color: #0ea5e9;
    }
    
    /* Remove specialty-dot as it's not needed in the main design */
    .specialty-dot {
        display: none;
    }
    
    /* Pagination Indicator - Matching medical theme */
    .pagination-indicator {
        display: flex;
        justify-content: center;
        gap: 12px;
        margin-top: 40px;
    }
    
    .pagination-dot {
        width: 12px;
        height: 12px;
        border-radius: 12px;
        border: 2px solid #0ea5e9;
        background: transparent;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .pagination-dot::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: var(--gradient-primary);
        border-radius: 12px;
        transform: translate(-50%, -50%);
        transition: var(--transition);
    }
    
    .pagination-dot.active {
        background: var(--gradient-primary);
        border-color: #0ea5e9;
        transform: scale(1.3);
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
        animation: dotPulse 2s infinite;
    }

    .pagination-dot.active::before {
        width: 100%;
        height: 100%;
    }
    
    .pagination-dot:hover {
        background: var(--gradient-accent);
        border-color: #10b981;
        transform: scale(1.2);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    @keyframes dotPulse {
        0%, 100% { 
            transform: scale(1.3);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.4);
        }
        50% { 
            transform: scale(1.4);
            box-shadow: 0 6px 16px rgba(14, 165, 233, 0.6);
        }
    }
    
    /* Doctor Search Section - Light Gray Background */
    .doctor-search-section {
        background: #f8fafc;
        padding: 80px 0 80px 0;
        position: relative;
        margin-top: 0;
        z-index: 10;
    }

    /* Enhanced background patterns */
    .doctor-search-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><defs><pattern id="doctor-pattern" width="80" height="80" patternUnits="userSpaceOnUse"><g fill="rgba(14, 165, 233, 0.04)"><circle cx="20" cy="20" r="1.5"/><circle cx="60" cy="60" r="1"/><rect x="35" y="25" width="10" height="30" rx="2"/><rect x="25" y="35" width="30" height="10" rx="2"/></g></pattern></defs><rect width="80" height="80" fill="url(%23doctor-pattern)"/></svg>');
        background-size: 160px 160px;
        opacity: 0.4;
        pointer-events: none;
        z-index: 1;
    }

    /* Floating medical elements */
    .doctor-search-section::after {
        content: '';
        position: absolute;
        top: 25%;
        right: 10%;
        width: 140px;
        height: 140px;
        background: 
            radial-gradient(circle, rgba(16, 185, 129, 0.08) 0%, transparent 70%);
        border-radius: 12px;
        z-index: 1;
        animation: float-doctor 22s ease-in-out infinite;
    }

    @keyframes float-doctor {
        0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); }
        33% { transform: translateY(-18px) rotate(3deg) scale(1.05); }
        66% { transform: translateY(12px) rotate(-2deg) scale(0.95); }
    }
    
    .search-banner {
        text-align: left;
        margin-bottom: 40px;
        position: relative;
        z-index: 2;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }

    .search-banner h2 {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        font-size: 1.75rem;
        color: #1e293b;
        margin-bottom: 0.75rem;
        font-weight: 700;
        letter-spacing: -0.01em;
        position: relative;
        line-height: 1.3;
    }

    .search-banner h2::after {
        display: none;
    }
    
    .search-banner p {
        font-size: 0.95rem;
        color: #64748b;
        max-width: 100%;
        margin: 0 0 2rem;
        line-height: 1.6;
        font-weight: 400;
    }

    .btn-view-all-doctors {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        color: white;
        padding: 14px 32px;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: 600;
        text-decoration: none;
        box-shadow: 0 6px 20px rgba(14, 165, 233, 0.3);
        transition: all 0.3s ease;
    }

    .btn-view-all-doctors:hover {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
        color: white;
    }

    .btn-view-all-doctors i {
        font-size: 1.2rem;
    }
    
    .search-layout {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    /* Search Sidebar - Hidden */
    .search-sidebar {
        display: none;
    }
        margin-bottom: 40px;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    .search-sidebar h3 {
        margin-bottom: 25px;
        color: var(--gray-800);
        font-size: 1.4rem;
        font-weight: 700;
        text-align: center;
        position: relative;
    }

    .search-sidebar h3::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 3px;
        background: linear-gradient(90deg, #0ea5e9, #10b981);
        border-radius: 2px;
    }
    
    .specialty-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        justify-content: center;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .specialty-list li {
        margin-bottom: 0;
    }
    
    .specialty-list a {
        display: block;
        padding: 14px 24px;
        color: var(--gray-700);
        text-decoration: none;
        border-radius: var(--radius-lg);
        transition: var(--transition);
        border: 2px solid rgba(14, 165, 233, 0.2);
        white-space: nowrap;
        font-weight: 600;
        position: relative;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.8);
    }

    .specialty-list a::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(14, 165, 233, 0.1), transparent);
        transition: var(--transition-slow);
    }
    
    .specialty-list a:hover,
    .specialty-list a.active {
        background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        color: #fff;
        border-color: #0ea5e9;
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .specialty-list a:hover::before,
    .specialty-list a.active::before {
        left: 100%;
    }
    
    .search-results {
        flex: 1;
    }
    
    /* Specialty Tabs - Clean Design */
    .specialty-tabs {
        display: flex;
        gap: 12px;
        margin: 0 auto 40px;
        max-width: 1200px;
        padding: 0 20px;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
    }
    
    .specialty-tab {
        background: #f1f5f9;
        color: #64748b;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 24px;
        font-size: 0.95rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        position: relative;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    }
    
    .specialty-tab i,
    .specialty-tab .tab-icon {
        font-size: 1.1rem;
        color: #64748b;
        transition: all 0.3s ease;
    }
    
    .specialty-tab:hover {
        background: #e2e8f0;
        color: #475569;
        border-color: #cbd5e1;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .specialty-tab:hover i,
    .specialty-tab:hover .tab-icon {
        color: #475569;
        transform: scale(1.1);
    }
    
    .specialty-tab.active {
        background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        color: white;
        border-color: #0ea5e9;
        box-shadow: 0 4px 16px rgba(14, 165, 233, 0.25);
    }
    
    .specialty-tab.active i,
    .specialty-tab.active .tab-icon {
        color: white;
    }
    
    .specialty-tab.active:hover {
        background: linear-gradient(135deg, #0284c7 0%, #0891b2 100%);
        border-color: #0284c7;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(14, 165, 233, 0.3);
    }
    
    /* Doctors Grid - 3 columns layout */
    .doctors-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    @media (max-width: 1200px) {
        .doctors-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }
    
    @media (max-width: 768px) {
        .doctors-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }
    
    @media (max-width: 992px) {
        .doctors-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .doctors-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .doctors-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .doctor-card {
        background: white;
        backdrop-filter: blur(20px);
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        padding: 16px 14px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: var(--gray-800);
        transition: var(--transition);
        position: relative;
        cursor: pointer;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        min-height: 420px;
        justify-content: flex-start;
        width: 100%;
    }

    .doctor-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(14, 165, 233, 0.1), transparent);
        transition: var(--transition-slow);
    }

    .doctor-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        border-color: #0ea5e9;
    }

    .doctor-card:hover::before {
        left: 100%;
    }

    /* Áp dụng riêng cho các chuyên khoa nếu cần (tăng ưu tiên) */
    .doctor-card[data-specialty*="Nhi Khoa"],
    .doctor-card[data-specialty*="Da Liễu"],
    .doctor-card[data-specialty*="Sản Phụ Khoa"],
    .doctor-card[data-specialty*="Nội Tổng Quát"],
    .doctor-card[data-specialty*="Tim Mạch"] {
        width: 520px;
        max-width: calc(100% - 32px);
    }

    /* Doctor Avatar - Circular */
    .doctor-avatar {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        box-shadow: 0 4px 15px rgba(14, 165, 233, 0.15);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        border: 4px solid white;
    }

    .doctor-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        transition: var(--transition);
    }
    
    .doctor-avatar img.error {
        display: none;
    }
    
    .doctor-avatar .fallback {
        display: none;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #0ea5e9, #06b6d4);
        border-radius: 12px;
        color: white;
        font-size: 24px;
        font-weight: bold;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: 0;
        left: 0;
    }
    
    .doctor-avatar img.error + .fallback {
        display: flex;
    }

    .doctor-avatar::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.3), transparent);
        transform: rotate(45deg);
        transition: var(--transition-slow);
        opacity: 0;
    }

    .doctor-card:hover .doctor-avatar {
        transform: scale(1.1) rotate(5deg);
        box-shadow: var(--shadow-xl);
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .doctor-card:hover .doctor-avatar::before {
        opacity: 1;
        animation: shimmer 1.5s ease-in-out;
    }

    /* Doctor Info */
    .doctor-info {
        text-align: center;
        width: 100%;
        padding: 0 20px;
    }

    .doctor-info h4 {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 8px 0;
        transition: var(--transition);
        line-height: 1.3;
    }

    .doctor-card:hover .doctor-info h4 {
        color: #0ea5e9;
    }

    .doctor-info .specialty {
        font-size: 0.8rem;
        color: #64748b;
        margin: 0 0 6px 0;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .doctor-info .specialty i {
        color: #ef4444;
        font-size: 0.85rem;
    }
    
    .doctor-info .hospital {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .doctor-info .hospital i {
        color: #0ea5e9;
        font-size: 0.8rem;
    }
    
    .doctor-info .experience {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .doctor-info .experience i {
        color: #10b981;
        font-size: 0.8rem;
    }
    
    .doctor-info .rating {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        margin: 0 0 8px 0;
        flex-wrap: wrap;
    }
    
    .doctor-info .rating .stars {
        color: #fbbf24;
        font-size: 0.75rem;
    }
    
    .doctor-info .rating .score {
        font-size: 0.75rem;
        font-weight: 600;
        color: #1e293b;
    }
    
    .doctor-info .rating .reviews {
        font-size: 0.7rem;
        color: #64748b;
    }
    
    .doctor-info .price {
        font-size: 0.85rem;
        font-weight: 700;
        color: #10b981;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .doctor-info .price i {
        color: #10b981;
        font-size: 0.8rem;
    }
    
    .doctor-info .availability {
        background: #dcfce7;
        color: #15803d;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        margin: 0 0 12px 0;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .doctor-info .availability i {
        color: #15803d;
        font-size: 0.7rem;
    }
    
    /* No doctors message */
    .no-doctors-message {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
        font-size: 1.1rem;
        display: none;
    }
    
    .no-doctors-message i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 20px;
        display: block;
    }
    
    .no-doctors-message.show {
        display: block;
    }

    @media (max-width: 920px) {
        .specialty-tabs {
            gap: 8px;
            padding: 0 10px;
        }
        
        .specialty-tab {
            padding: 10px 16px;
            font-size: 0.9rem;
        }
        
        .specialty-tab i {
            font-size: 1rem;
        }
        
        .doctors-grid { 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .doctor-card { 
            padding: 25px 20px;
            min-height: 260px;
        }
        .doctor-avatar { 
            width: 100px; 
            height: 100px;
            margin-bottom: 15px;
        }
        .doctor-info h4 { 
            font-size: 1.15rem;
        }
        .doctor-info .specialty,
        .doctor-info .hospital,
        .doctor-info .experience {
            font-size: 0.85rem;
        }
        .doctor-info .price {
            font-size: 1rem;
        }
    }
    /* Doctor Actions - Matching specialty card style */
    .doctor-actions {
        display: flex;
        gap: 12px;
        margin-top: auto;
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
    }

    .doctor-actions a {
        text-decoration: none;
        padding: 12px 24px;
        font-size: 0.95rem;
        font-weight: 600;
        border-radius: 12px;
        transition: var(--transition);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        position: relative;
        z-index: 10;
        pointer-events: auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        overflow: hidden;
        border: 2px solid transparent;
        min-width: 120px;
        justify-content: center;
    }

    .doctor-actions a::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: var(--transition-slow);
    }

    .btn-profile {
        background: #0ea5e9;
        color: #fff;
        box-shadow: 0 2px 8px rgba(14, 165, 233, 0.2);
        border: none;
        flex: 1;
    }

    .btn-profile:hover {
        background: #0284c7;
        box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        transform: translateY(-2px);
    }

    .btn-profile:hover::before {
        left: 100%;
    }

    .btn-book {
        background: #10b981;
        color: #fff;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
        border: none;
        flex: 1;
    }

    .btn-book:hover {
        background: #059669;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        transform: translateY(-2px);
    }

    .btn-book:hover::before {
        left: 100%;
    }

    .btn-view-profile {
        background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
        color: #fff;
        box-shadow: var(--shadow-md);
    }

    .btn-view-profile:hover {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        box-shadow: var(--shadow-xl);
        transform: translateY(-2px);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .btn-view-profile:hover::before {
        left: 100%;
    }
    /* Facilities Section - Enhanced to match Comprehensive Medical */
    .facilities-section {
        background: 
            radial-gradient(ellipse at top left, rgba(16, 185, 129, 0.08) 0%, transparent 60%),
            radial-gradient(ellipse at bottom right, rgba(14, 165, 233, 0.06) 0%, transparent 60%),
            linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
        padding: 120px 0 100px 0;
        position: relative;
        margin-top: -40px;
        border-radius: var(--radius-2xl) var(--radius-2xl) 0 0;
        box-shadow: 
            0 25px 50px rgba(14, 165, 233, 0.1),
            0 15px 30px rgba(0, 0, 0, 0.08);
        z-index: 10;
        overflow: hidden;
    }

    /* Enhanced background patterns */
    .facilities-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: 
            url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="facility-pattern" width="100" height="100" patternUnits="userSpaceOnUse"><g fill="rgba(16, 185, 129, 0.04)"><circle cx="25" cy="25" r="1.5"/><circle cx="75" cy="75" r="1"/><rect x="45" y="30" width="10" height="40" rx="2"/><rect x="30" y="45" width="40" height="10" rx="2"/></g></pattern></defs><rect width="100" height="100" fill="url(%23facility-pattern)"/></svg>');
        background-size: 200px 200px;
        opacity: 0.4;
        pointer-events: none;
        z-index: 1;
    }

    /* Floating medical elements */
    .facilities-section::after {
        content: '';
        position: absolute;
        top: 30%;
        left: 8%;
        width: 160px;
        height: 160px;
        background: 
            radial-gradient(circle, rgba(14, 165, 233, 0.08) 0%, transparent 70%);
        border-radius: 12px;
        z-index: 1;
        animation: float-facility 25s ease-in-out infinite;
    }

    @keyframes float-facility {
        0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); }
        33% { transform: translateY(-20px) rotate(4deg) scale(1.05); }
        66% { transform: translateY(15px) rotate(-3deg) scale(0.95); }
    }
    
    .section-title {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        font-size: clamp(2.3rem, 4.5vw, 3.5rem);
        background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 25px;
        font-weight: 900;
        text-align: center;
        letter-spacing: -0.03em;
        position: relative;
        z-index: 2;
        line-height: 1.1;
        text-shadow: 0 4px 20px rgba(14, 165, 233, 0.15);
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: -12px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 5px;
        background: linear-gradient(90deg, #0ea5e9 0%, #10b981 50%, #06b6d4 100%);
        border-radius: 3px;
        box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
        animation: pulse-glow 3s ease-in-out infinite;
    }
    
    .section-subtitle {
        text-align: center;
        color: var(--gray-600);
        font-size: 1.3rem;
        max-width: 800px;
        margin: 0 auto 80px;
        line-height: 1.7;
        font-weight: 500;
        position: relative;
        z-index: 2;
    }
    
    .facilities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 35px;
        max-width: 1300px;
        margin: 0 auto;
        padding: 0 25px;
        justify-items: center;
        position: relative;
        z-index: 2;
    }
    
    .facility-item {
        background: 
            linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
        backdrop-filter: blur(25px);
        border-radius: var(--radius-2xl);
        box-shadow: 
            0 20px 40px rgba(14, 165, 233, 0.08),
            0 8px 16px rgba(0, 0, 0, 0.06),
            inset 0 1px 0 rgba(255, 255, 255, 0.8);
        padding: 35px 30px;
        text-decoration: none;
        color: var(--gray-800);
        transition: var(--transition);
        position: relative;
        cursor: pointer;
        border: 2px solid rgba(255, 255, 255, 0.6);
        overflow: hidden;
        text-align: center;
        max-width: 420px;
        width: 100%;
        min-height: 320px;
    }

    /* Enhanced shimmer effect */
    .facility-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            90deg, 
            transparent, 
            rgba(14, 165, 233, 0.1) 20%, 
            rgba(16, 185, 129, 0.1) 50%, 
            rgba(14, 165, 233, 0.1) 80%, 
            transparent
        );
        transition: var(--transition-slow);
        z-index: 1;
    }

    /* Medical cross decoration */
    .facility-item::after {
        content: '';
        position: absolute;
        top: 15px;
        right: 15px;
        width: 20px;
        height: 20px;
        background: 
            linear-gradient(90deg, rgba(14, 165, 233, 0.1) 40%, transparent 40%, transparent 60%, rgba(14, 165, 233, 0.1) 60%),
            linear-gradient(0deg, rgba(14, 165, 233, 0.1) 40%, transparent 40%, transparent 60%, rgba(14, 165, 233, 0.1) 60%);
        border-radius: 2px;
        opacity: 0;
        transition: var(--transition);
        z-index: 1;
    }

    .facility-item:hover {
        transform: translateY(-12px) scale(1.02);
        box-shadow: 
            0 30px 60px rgba(14, 165, 233, 0.15),
            0 15px 30px rgba(0, 0, 0, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 1);
        border-color: rgba(14, 165, 233, 0.3);
        background: 
            linear-gradient(145deg, rgba(255, 255, 255, 1) 0%, rgba(240, 249, 255, 0.98) 100%);
    }

    .facility-item:hover::before {
        left: 100%;
    }

    .facility-item:hover::after {
        opacity: 1;
        transform: rotate(45deg) scale(1.2);
    }
    
    .facility-image {
        margin-bottom: 28px;
        position: relative;
        overflow: hidden;
        border-radius: var(--radius-xl);
        box-shadow: 
            0 10px 20px rgba(14, 165, 233, 0.1),
            0 4px 8px rgba(0, 0, 0, 0.05);
        z-index: 2;
    }
    
    .facility-image img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        border-radius: var(--radius-xl);
        transition: var(--transition);
    }

    .facility-item:hover .facility-image {
        box-shadow: 
            0 15px 30px rgba(14, 165, 233, 0.2),
            0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .facility-item:hover .facility-image img {
        transform: scale(1.08);
    }
    
    .facility-item h3 {
        font-size: 1.3rem;
        color: var(--gray-800);
        margin: 0;
        line-height: 1.4;
        font-weight: 700;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        transition: var(--transition);
        letter-spacing: -0.01em;
        position: relative;
        z-index: 2;
    }

    .facility-item:hover h3 {
        color: #0ea5e9;
        transform: translateY(-2px);
    }


    
    /* Modern Medical Footer - Premium Design */
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

    /* Footer Wave */
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

    /* Medical Background Pattern */
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

    /* Floating Medical Icons */
    .footer-decoration {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        z-index: 1;
    }

    .floating-medical-icon {
        position: absolute;
        font-size: 3rem;
        opacity: 0.1;
        animation: float-medical 20s ease-in-out infinite;
    }

    .floating-medical-icon.icon-1 {
        top: 15%;
        left: 10%;
        animation-delay: 0s;
    }

    .floating-medical-icon.icon-2 {
        top: 25%;
        right: 15%;
        animation-delay: 5s;
    }

    .floating-medical-icon.icon-3 {
        bottom: 30%;
        left: 20%;
        animation-delay: 10s;
    }

    .floating-medical-icon.icon-4 {
        bottom: 20%;
        right: 25%;
        animation-delay: 15s;
    }

    @keyframes float-medical {
        0%, 100% { transform: translateY(0px) rotate(0deg) scale(1); opacity: 0.1; }
        25% { transform: translateY(-20px) rotate(5deg) scale(1.1); opacity: 0.2; }
        50% { transform: translateY(-10px) rotate(-3deg) scale(0.9); opacity: 0.15; }
        75% { transform: translateY(-15px) rotate(2deg) scale(1.05); opacity: 0.25; }
    }

    /* Main Footer Content */
    .footer-content {
        padding: 80px 0 40px;
        position: relative;
        z-index: 2;
    }

    /* Enhanced Text Readability */
    .beautiful-footer h2,
    .beautiful-footer h3,
    .beautiful-footer h4,
    .beautiful-footer p,
    .beautiful-footer a,
    .beautiful-footer span {
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    /* Enhanced Icon Visibility */
    .beautiful-footer i {
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
    }

    /* Brand Title Glow */
    .footer-brand h2 {
        text-shadow: 
            0 1px 3px rgba(0, 0, 0, 0.3),
            0 0 20px rgba(14, 165, 233, 0.3);
    }

    .footer-main-grid {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
        gap: 60px;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 30px;
    }

    /* Brand Section */
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
        box-shadow: 0 8px 20px rgba(220, 38, 38, 0.3);
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

    .brand-logo {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 30px;
    }

    .logo-circle {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #10b981 0%, #06b6d4 30%, #0ea5e9 70%, #3b82f6 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        box-shadow: 
            0 20px 40px rgba(16, 185, 129, 0.3),
            0 10px 20px rgba(0, 0, 0, 0.2),
            inset 0 2px 4px rgba(255, 255, 255, 0.2);
        position: relative;
        overflow: hidden;
        border: 3px solid rgba(255, 255, 255, 0.15);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .logo-circle:hover {
        transform: scale(1.1) rotate(10deg);
        box-shadow: 
            0 25px 50px rgba(16, 185, 129, 0.4),
            0 15px 30px rgba(0, 0, 0, 0.3);
    }

    .logo-circle::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: conic-gradient(from 0deg, transparent, rgba(255,255,255,0.2), transparent);
        animation: logo-spin 8s linear infinite;
    }

    @keyframes logo-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .brand-text h2 {
        margin: 0 0 8px 0;
        font-size: 2.8rem;
        font-weight: 900;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        background: linear-gradient(135deg, #ffffff 0%, #e2e8f0 50%, #cbd5e1 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        letter-spacing: -0.02em;
    }

    .brand-text p {
        margin: 0;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 3px;
        text-transform: uppercase;
        color: #94a3b8;
        opacity: 0.8;
    }

    .brand-description {
        margin-bottom: 35px;
        line-height: 1.8;
        font-size: 1.1rem;
        color: #e2e8f0;
        opacity: 0.9;
    }

    /* Footer Stats - Already defined in brand-stats above */

    /* Links Sections */
    .footer-column h3 {
        margin-bottom: 25px;
        font-size: 1.3rem;
        font-weight: 700;
        color: #ffffff;
        position: relative;
        padding-bottom: 12px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .footer-column h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background: linear-gradient(90deg, #0ea5e9, #06b6d4);
        border-radius: 2px;
    }

    .footer-column h3 i {
        color: #0ea5e9;
        font-size: 1.1rem;
    }

    .footer-column ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-column li {
        margin-bottom: 15px;
    }

    .contact-info {
        margin-bottom: 30px;
    }

    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        border-left: 3px solid #0ea5e9;
    }

    .contact-item i {
        color: #0ea5e9;
        font-size: 1.2rem;
        margin-top: 2px;
        min-width: 20px;
    }

    .contact-item div {
        flex: 1;
    }

    .contact-item strong {
        color: #ffffff;
        font-weight: 600;
    }

    .social-section {
        margin-top: 30px;
    }

    .social-section h4 {
        color: #ffffff;
        font-size: 1.1rem;
        margin-bottom: 15px;
        font-weight: 600;
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
        background: linear-gradient(135deg, #1877f2, #42a5f5);
    }

    .social.youtube {
        background: linear-gradient(135deg, #ff0000, #ff5722);
    }

    .social.zalo {
        background: linear-gradient(135deg, #0068ff, #0084ff);
    }

    .social.instagram {
        background: linear-gradient(135deg, #e4405f, #f77737);
    }

    .social:hover {
        transform: translateY(-3px) scale(1.1);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .footer-column a {
        color: #cbd5e1;
        text-decoration: none;
        font-size: 1rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 0;
        transition: all 0.3s ease;
        position: relative;
    }

    .footer-column a i,
    .footer-column a .footer-emoji {
        width: 18px;
        color: #0ea5e9;
        font-size: 0.9rem;
        font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif;
        font-style: normal;
    }

    .footer-column h3 .footer-emoji,
    .contact-item .footer-emoji {
        font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif;
        font-style: normal;
        margin-right: 8px;
        font-size: 1.1rem;
    }

    .footer-column a:hover {
        color: #ffffff;
        transform: translateX(8px);
    }

    .footer-column a:hover i,
    .footer-column a:hover .footer-emoji {
        color: #06b6d4;
        transform: scale(1.2);
    }

    /* Contact Section - Already defined above */

    .contact-items {
        margin-bottom: 35px;
    }

    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.3s ease;
    }

    .contact-item:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(220, 38, 38, 0.2);
        transform: translateY(-2px);
    }

    .contact-item i {
        width: 20px;
        font-size: 1.2rem;
        color: #0ea5e9;
        margin-top: 2px;
        text-align: center;
    }

    .contact-text {
        flex: 1;
    }

    .contact-text strong {
        display: block;
        color: #ffffff;
        font-weight: 600;
        margin-bottom: 4px;
        font-size: 0.95rem;
    }

    .contact-text span {
        color: #cbd5e1;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    /* Social Media */
    .social-media {
        margin-top: 30px;
    }

    .social-media h4 {
        margin-bottom: 15px;
        font-size: 1.1rem;
        color: #ffffff;
        font-weight: 600;
    }

    .social-links {
        display: flex;
        gap: 15px;
    }

    .social-link {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        font-size: 1.3rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        border: 2px solid transparent;
    }

    .social-link.facebook {
        background: linear-gradient(135deg, #1877f2, #42a5f5);
        color: white;
    }

    .social-link.youtube {
        background: linear-gradient(135deg, #ff0000, #ff5722);
        color: white;
    }

    .social-link.zalo {
        background: linear-gradient(135deg, #0068ff, #0084ff);
        color: white;
    }

    .social-link.instagram {
        background: linear-gradient(135deg, #e4405f, #f77737, #fcaf45);
        color: white;
    }

    .social-link:hover {
        transform: translateY(-5px) scale(1.1);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        border-color: rgba(255, 255, 255, 0.3);
    }

    .social-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.2);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .social-link:hover::before {
        opacity: 1;
    }

    /* Footer Bottom */
    .footer-bottom {
        padding: 25px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
    }

    .bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 30px;
    }

    .bottom-content p {
        margin: 0;
        color: #94a3b8;
        font-size: 0.95rem;
    }

    .bottom-content strong {
        color: #ffffff;
    }

    .bottom-links {
        display: flex;
        gap: 25px;
    }

    .bottom-links a {
        color: #cbd5e1;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .bottom-links a:hover {
        color: #0ea5e9;
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .footer-main-grid {
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }
        
        .footer-brand {
            grid-column: 1 / -1;
            max-width: none;
        }
        
        .brand-stats {
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .beautiful-footer {
            margin-top: 60px;
        }
        
        .footer-content {
            padding: 60px 0 30px;
        }
        
        .footer-main-grid {
            grid-template-columns: 1fr;
            gap: 40px;
            padding: 0 20px;
        }
        
        .brand-header {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .brand-icon {
            width: 60px;
            height: 60px;
            font-size: 1.6rem;
        }
        
        .brand-info h2 {
            font-size: 1.8rem;
        }
        
        .brand-stats {
            flex-direction: column;
            gap: 15px;
        }
        
        .bottom-content {
            flex-direction: column;
            gap: 15px;
            text-align: center;
            padding: 0 20px;
        }
        
        .bottom-links {
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .footer-main-grid {
            padding: 0 15px;
        }
        
        .contact-item {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }
        
        .social-links {
            justify-content: center;
        }
        
        .bottom-links {
            flex-direction: column;
            gap: 10px;
        }
    }
  /* ==========================
   🌐 Modern Language Switcher
   ========================== */
.lang-switcher-modern {
    position: relative;
    margin-left: 16px;
}

.lang-button {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 2px solid rgba(14, 165, 233, 0.2);
    border-radius: 25px;
    padding: 10px 16px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    color: #0ea5e9;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 4px 15px rgba(14, 165, 233, 0.1);
    min-width: 140px;
    justify-content: space-between;
}

.lang-button:hover {
    background: rgba(255, 255, 255, 1);
    border-color: rgba(14, 165, 233, 0.4);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(14, 165, 233, 0.2);
}

.lang-button i.fa-globe {
    color: #0ea5e9;
    font-size: 1rem;
}

.lang-button i.fa-chevron-down {
    color: #64748b;
    font-size: 0.8rem;
    transition: var(--transition);
}

.lang-button.active i.fa-chevron-down {
    transform: rotate(180deg);
}

.lang-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border: 2px solid #e0f2fe;
    border-radius: 24px;
    box-shadow: 0 15px 50px rgba(6, 182, 212, 0.2);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: var(--transition);
    z-index: 9999;
    overflow: hidden;
    min-width: 250px;
    padding: 14px;
}

.lang-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}



.lang-option {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    cursor: pointer;
    transition: var(--transition);
    border-radius: 18px;
    margin-bottom: 8px;
    border-bottom: none;
}

.lang-option:last-child {
    margin-bottom: 0;
}

.lang-option:hover {
    background: rgba(224, 242, 254, 0.6);
    transform: translateX(4px);
}

.lang-option:first-child {
    background: linear-gradient(135deg, #dbeafe 0%, #e0f2fe 100%);
}

.lang-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
}

.lang-icon i {
    font-size: 1.3rem;
    color: white;
}

.lang-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lang-code {
    font-size: 0.95rem;
    font-weight: 600;
    color: #94a3b8;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
}

.lang-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
}

/* Responsive Design */
@media (max-width: 768px) {
    .lang-switcher-modern {
        margin-left: 8px;
    }
    
    .lang-button {
        padding: 8px 12px;
        font-size: 0.8rem;
        min-width: 120px;
    }
    
    .lang-dropdown {
        left: -20px;
        right: -20px;
    }
}
    font-size: 0.7rem;
    pointer-events: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 2;
}

/* Animation icon khi hover */
.lang-switcher:hover::after {
    color: #2563eb;
    transform: translateY(-50%) rotate(180deg);
}

/* Style cho options */
.lang-switcher select option {
    padding: 12px 16px;
    background: white;
    color: #1e293b;
    font-weight: 500;
    border: none;
    font-size: 0.95rem;
}

.lang-switcher select option:hover {
    background: #f1f5f9;
    color: #2563eb;
}

/* Animation khi click */
.lang-switcher select:active {
    transform: scale(0.98);
}

/* Khmer font support */
.lang-switcher select option[value="km"] {
    font-family: 'Khmer OS', 'Noto Sans Khmer', 'Khmer UI', Arial, sans-serif;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 8px 12px;
    white-space: nowrap;
}

/* Vietnamese font support */
.lang-switcher select option[value="vi"] {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    font-weight: 600;
    font-size: 0.9rem;
    padding: 8px 12px;
    white-space: nowrap;
}

/* Responsive design */
@media (max-width: 768px) {
    .lang-switcher {
        margin-left: 8px;
        transform: scale(0.9);
    }
    
    .lang-switcher select {
        min-width: 140px;
        max-width: 160px;
        padding: 10px 40px 10px 35px;
        font-size: 0.8rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .lang-switcher::before {
        left: 10px;
        font-size: 1rem;
    }
    
    .lang-switcher::after {
        right: 12px;
    }
}

@media (max-width: 480px) {
    .lang-switcher {
        margin-left: 4px;
        transform: scale(0.85);
    }
    
    .lang-switcher select {
        min-width: 120px;
        max-width: 140px;
        padding: 8px 35px 8px 30px;
        font-size: 0.75rem;
    }
    
    .lang-switcher::before {
        left: 8px;
        font-size: 0.9rem;
    }
    
    .lang-switcher::after {
        right: 10px;
        font-size: 0.8rem;
    }
}

/* Hiệu ứng shimmer khi active */
.lang-switcher.active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

.lang-switcher.active select {
    color: #065f46;
    font-weight: 700;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .lang-switcher {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    }
    
    .lang-switcher select {
        background: rgba(30, 41, 59, 0.95);
        color: #e2e8f0;
    }
    
    .lang-switcher select:hover {
        background: rgba(30, 41, 59, 1);
        color: #60a5fa;
    }
    
    .lang-switcher select option {
        background: #1e293b;
        color: #e2e8f0;
    }
}

/* High contrast mode */
@media (prefers-contrast: high) {
    .lang-switcher {
        background: #000;
        border: 2px solid #fff;
    }
    
    .lang-switcher select {
        background: #fff;
        color: #000;
        border: 1px solid #000;
    }
    
    .lang-switcher::before,
    .lang-switcher::after {
        color: #000;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .lang-switcher,
    .lang-switcher select,
    .lang-switcher::after {
        transition: none;
    }
    
    .lang-switcher:hover {
        transform: none;
    }
    
    .lang-switcher:hover::after {
        transform: translateY(-50%);
    }
}

/* Focus visible for keyboard navigation */
.lang-switcher select:focus-visible {
    outline: 3px solid #2563eb;
    outline-offset: 2px;
}
/* Enhanced Account Modal Styles */
    .account-modal-overlay {
        position: fixed;
        inset: 0;
        background: 
            radial-gradient(circle at center, rgba(14, 165, 233, 0.15) 0%, transparent 70%),
            rgba(2, 6, 23, 0.6);
        backdrop-filter: blur(12px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .account-modal-overlay.modal-show {
        opacity: 1;
    }

    .account-modal-overlay.modal-hide {
        opacity: 0;
    }

    .account-modal {
        width: 480px;
        max-width: 94%;
        background: 
            linear-gradient(145deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 250, 252, 0.95) 100%);
        backdrop-filter: blur(25px);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 
            0 25px 50px rgba(14, 165, 233, 0.15),
            0 15px 30px rgba(0, 0, 0, 0.1),
            inset 0 1px 0 rgba(255, 255, 255, 0.8);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        position: relative;
        border: 2px solid rgba(255, 255, 255, 0.6);
        overflow: hidden;
        transform: scale(0.9) translateY(20px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .account-modal-overlay.modal-show .account-modal {
        transform: scale(1) translateY(0);
    }

    .account-modal-overlay.modal-hide .account-modal {
        transform: scale(0.9) translateY(20px);
    }

    /* Enhanced shimmer effect */
    .account-modal::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(
            90deg, 
            transparent, 
            rgba(16, 185, 129, 0.08) 20%, 
            rgba(14, 165, 233, 0.08) 50%, 
            rgba(16, 185, 129, 0.08) 80%, 
            transparent
        );
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1;
    }

    .account-modal:hover::before {
        left: 100%;
    }

    /* Medical cross decoration */
    .account-modal::after {
        content: '';
        position: absolute;
        top: 20px;
        right: 60px;
        width: 20px;
        height: 20px;
        background: 
            linear-gradient(90deg, rgba(16, 185, 129, 0.1) 40%, transparent 40%, transparent 60%, rgba(16, 185, 129, 0.1) 60%),
            linear-gradient(0deg, rgba(16, 185, 129, 0.1) 40%, transparent 40%, transparent 60%, rgba(16, 185, 129, 0.1) 60%);
        border-radius: 3px;
        opacity: 0.6;
        z-index: 1;
    }

    .account-modal .head {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 32px;
        position: relative;
        z-index: 2;
    }

    .account-modal .avatar {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 28px;
        color: #ffffff;
        box-shadow: 
            0 15px 30px rgba(14, 165, 233, 0.25),
            0 8px 16px rgba(0, 0, 0, 0.1),
            inset 0 2px 4px rgba(255, 255, 255, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        border: 3px solid rgba(255, 255, 255, 0.9);
    }

    .account-modal .avatar::before {
        content: '';
        position: absolute;
        top: -3px;
        left: -3px;
        right: -3px;
        bottom: -3px;
        background: linear-gradient(135deg, #0ea5e9, #10b981, #38bdf8);
        border-radius: 16px;
        z-index: -1;
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .account-modal .avatar:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 
            0 20px 40px rgba(14, 165, 233, 0.35),
            0 12px 24px rgba(0, 0, 0, 0.15),
            inset 0 3px 6px rgba(255, 255, 255, 0.4);
    }

    .account-modal .avatar:hover::before {
        opacity: 1;
        animation: rotate-glow 2s linear infinite;
    }

    @keyframes rotate-glow {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .account-modal h4 {
        margin: 0 0 8px 0;
        color: #0f172a;
        font-size: 24px;
        font-weight: 700;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        background: linear-gradient(135deg, #0284c7 0%, #0ea5e9 50%, #10b981 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        line-height: 1.2;
    }

    .account-modal .user-role {
        font-size: 16px;
        color: #6b7280;
        font-weight: 500;
        padding: 6px 12px;
        background: rgba(14, 165, 233, 0.1);
        border-radius: 8px;
        display: inline-block;
        margin-top: 4px;
    }

    .account-row {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 20px 0;
        position: relative;
        z-index: 2;
    }

    .account-row label {
        font-size: 14px;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .account-row .value {
        font-size: 16px;
        color: #111827;
        padding: 16px 18px;
        border-radius: 12px;
        background: 
            linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.8) 100%);
        border: 2px solid rgba(14, 165, 233, 0.1);
        font-weight: 500;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    }

    .account-row .value:hover {
        border-color: rgba(14, 165, 233, 0.3);
        background: 
            linear-gradient(145deg, rgba(255, 255, 255, 1) 0%, rgba(240, 249, 255, 0.9) 100%);
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }

    .account-actions {
        display: flex;
        gap: 16px;
        margin-top: 32px;
        position: relative;
        z-index: 2;
    }

    .account-actions .btn {
        flex: 1;
        padding: 16px 20px;
        border-radius: 12px;
        border: none;
        font-weight: 700;
        font-size: 16px;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }

    .account-actions .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .account-actions .btn:hover::before {
        left: 100%;
    }

    .btn-edit {
        background: linear-gradient(135deg, #0ea5e9 0%, #10b981 100%);
        color: #ffffff;
        box-shadow: 
            0 8px 16px rgba(14, 165, 233, 0.25),
            0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn-edit:hover {
        transform: translateY(-3px);
        box-shadow: 
            0 12px 24px rgba(14, 165, 233, 0.35),
            0 8px 16px rgba(0, 0, 0, 0.15);
        background: linear-gradient(135deg, #10b981 0%, #38bdf8 100%);
    }



    .account-modal .close-btn {
        position: absolute;
        right: 20px;
        top: 20px;
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid rgba(14, 165, 233, 0.2);
        border-radius: 12px;
        width: 40px;
        height: 40px;
        font-size: 20px;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    }

    .account-modal .close-btn:hover {
        background: #ef4444;
        color: #ffffff;
        border-color: #ef4444;
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }

    /* Responsive Design */
    @media (max-width: 640px) {
        .account-modal {
            padding: 24px;
            max-width: 100%;
            margin: 20px;
        }

        .account-modal .head {
            flex-direction: column;
            text-align: center;
            gap: 16px;
        }

        .account-modal .avatar {
            width: 70px;
            height: 70px;
            font-size: 24px;
        }

        .account-modal h4 {
            font-size: 20px;
        }

        .account-actions {
            flex-direction: column;
            gap: 12px;
        }

        .account-actions .btn {
            padding: 14px 18px;
            font-size: 15px;
        }
    }

    @media (max-width: 480px) {
        .account-modal {
            padding: 20px;
        }

        .account-modal h4 {
            font-size: 18px;
        }

        .account-row .value {
            padding: 12px 14px;
            font-size: 15px;
        }
    }

    /* Focus states for accessibility */
    .account-actions .btn:focus,
    .account-modal .close-btn:focus {
        outline: 3px solid rgba(14, 165, 233, 0.3);
        outline-offset: 2px;
    }

    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .account-modal {
            border: 3px solid #1f2937;
        }
        
        .account-row .value {
            border: 2px solid #4b5563;
        }
    }

    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        .account-modal-overlay,
        .account-modal,
        .account-modal::before,
        .account-modal .avatar,
        .account-actions .btn {
            animation: none !important;
            transition: none !important;
        }
    }

    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="TrangChu.php" class="logo">
                <img src="image/logo.png.png" alt="Logo">
                <span>KhamCare</span>
            </a>
            
            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a href="TimKiemBS.php" id="navDoctors">Tìm bác sĩ</a></li>
                    <li><a href="ChuyenKhoa.php" id="navSpecialties">Chuyên khoa</a></li>
                    <li><a href="TuVanTrucTuyen.php" id="navConsultation">Tư vấn</a></li>
                    <li><a href="Camnangsuckhoe.php" id="navGuides">Cẩm nang Sức khỏe</a></li>
                    <li><a href="doctor_auth.php" id="navDoctorAuth" style="color: #0ea5e9; font-weight: 600;">👨‍⚕️ Dành cho Bác sĩ</a></li>
                    
                    <?php if ($isLoggedIn): ?>
                        <!-- Hiển thị menu Tài khoản khi đã đăng nhập -->
                        <li>
                            <div class="account-dropdown-wrapper" id="accountDropdown">
                                <button class="btn-account" onclick="toggleAccountDropdown(event)" style="<?php echo ($_SESSION['role'] ?? '') === 'doctor' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : ''; ?>">
                                    <i class="fas fa-<?php echo ($_SESSION['role'] ?? '') === 'doctor' ? 'user-md' : 'user'; ?>"></i>
                                    <span><?php 
                                        $fullName = $user['full_name'] ?? '';
                                        $nameParts = explode(' ', $fullName);
                                        $lastName = end($nameParts);
                                        if (($_SESSION['role'] ?? '') === 'doctor') {
                                            echo 'BS. ' . htmlspecialchars($lastName);
                                        } else {
                                            echo htmlspecialchars($lastName ?: 'Tài khoản');
                                        }
                                    ?></span>
                                    <i class="fas fa-chevron-down arrow"></i>
                                </button>
                                <div class="account-dropdown-menu">
                                    <?php if (($_SESSION['role'] ?? '') === 'doctor'): ?>
                                        <!-- Menu cho Bác sĩ -->
                                        <div style="padding: 15px 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 12px 12px 0 0; margin: -20px -20px 15px -20px;">
                                            <div style="font-weight: 700; font-size: 1.1rem;">👨‍⚕️ <?php echo htmlspecialchars($user['full_name'] ?? 'Bác sĩ'); ?></div>
                                            <div style="font-size: 0.85rem; opacity: 0.9;">Tài khoản Bác sĩ</div>
                                        </div>
                                        <a href="doctor_dashboard.php" class="account-dropdown-item" style="background: #f0fdf4; border-left: 3px solid #10b981;">
                                            <i class="fas fa-columns" style="color: #10b981;"></i>
                                            <span>Dashboard Bác sĩ</span>
                                        </a>
                                        <a href="tk.php" class="account-dropdown-item">
                                            <i class="fas fa-user-circle"></i>
                                            <span>Hồ sơ của tôi</span>
                                        </a>
                                        <a href="logout.php" class="account-dropdown-item logout">
                                            <i class="fas fa-sign-out-alt"></i>
                                            <span>Đăng xuất</span>
                                        </a>
                                    <?php else: ?>
                                        <!-- Menu cho Bệnh nhân -->
                                        <a href="tk.php" class="account-dropdown-item">
                                            <i class="fas fa-user-circle"></i>
                                            <span id="menuMyProfile">Hồ sơ của tôi</span>
                                        </a>
                                        <a href="DatLichK.php" class="account-dropdown-item">
                                            <i class="fas fa-calendar-check"></i>
                                            <span id="menuAppointments">Lịch hẹn</span>
                                        </a>
                                        <a href="TuVanTrucTuyen.php" class="account-dropdown-item">
                                            <i class="fas fa-comments"></i>
                                            <span id="menuOnlineConsult">Tư vấn trực tuyến</span>
                                        </a>
                                        <a href="logout.php" class="account-dropdown-item logout">
                                            <i class="fas fa-sign-out-alt"></i>
                                            <span id="menuLogout">Đăng xuất</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php else: ?>
                        <!-- Hiển thị nút Đăng nhập/Đăng ký khi chưa đăng nhập -->
                        <li><a href="TaiKhoan.php?action=login" class="btn-login">Đăng nhập</a></li>
                        <li><a href="TaiKhoan.php?action=register" class="btn-register">Đăng ký</a></li>
                    <?php endif; ?>
                    <li>
                        <!-- Language Selector -->
                        <div class="language-selector">
                            <button class="lang-toggle" id="langToggle">
                                <i class="fas fa-globe"></i>
                                <span id="currentLang">VI</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="lang-dropdown" id="langDropdown">
                                <div class="lang-option active" data-lang="vi">
                                    <span class="lang-country">VN</span>
                                    <span class="lang-name">Tiếng Việt</span>
                                    <span class="lang-code">VI</span>
                                </div>
                                <div class="lang-option" data-lang="km">
                                    <span class="lang-country">KH</span>
                                    <span class="lang-name">ភាសាខ្មែរ</span>
                                    <span class="lang-code">KM</span>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>
            
            <style>
            .lang-switcher-modern {
                position: relative;
            }
            
            .lang-button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4) !important;
            }
            
            .lang-option:hover {
                background: #f0f9ff !important;
            }
            
            .lang-option:last-child {
                border-bottom: none !important;
            }
            
            /* Ensure dropdown is visible when shown */
            .lang-dropdown[style*="display: block"] {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            /* Clean dropdown styles */
            .lang-dropdown {
                border: 2px solid rgba(14, 165, 233, 0.2) !important;
            }
            
            .lang-option:hover {
                background: #f0f9ff !important;
            }
            </style>
            

        </div>
        

    </header>
    

    
    <script>
    // Mobile Menu Toggle
    function toggleMobileMenu() {
        const nav = document.getElementById('mainNav');
        const btn = document.getElementById('mobileMenuBtn');
        nav.classList.toggle('active');
        
        if (nav.classList.contains('active')) {
            btn.innerHTML = '<i class="fas fa-times"></i>';
            document.body.style.overflow = 'hidden';
        } else {
            btn.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.style.overflow = '';
        }
    }
    
    // Close mobile menu when clicking a link
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.main-nav a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                const nav = document.getElementById('mainNav');
                const btn = document.getElementById('mobileMenuBtn');
                if (nav.classList.contains('active')) {
                    nav.classList.remove('active');
                    btn.innerHTML = '<i class="fas fa-bars"></i>';
                    document.body.style.overflow = '';
                }
            });
        });
    });
    </script>
    
    <script>
    // Simple Language Switcher - Direct Implementation
    document.addEventListener('DOMContentLoaded', function() {
      
      // Complete Language translations for entire page
      const translations = {
        vi: {
          // Navigation
          navDoctors: "Tìm kiếm Bác sĩ",
          navSpecialties: "Chuyên khoa", 
          navConsultation: "Tư vấn trực tuyến",
          navGuides: "Cẩm nang Sức khỏe",
          navDoctorAuth: "👨‍⚕️ Dành cho Bác sĩ",
          account: "Tài khoản",
          
          // Page Title
          pageTitle: "KhamCare - Đặt lịch khám bệnh dễ dàng",
          
          // Hero Section
          heroTitle: "Đặt lịch khám bệnh dễ dàng. Chăm sóc sức khỏe toàn diện cho bạn",
          searchPlaceholder: "Tìm kiếm bác sĩ, chuyên khoa...",
          searchBtn: "Tìm kiếm",
          btnBookingText: "Đặt lịch khám ngay",
          optionToday: "Hôm nay",
          optionTomorrow: "Ngày mai",
          
          // Section Titles
          specialtiesTitle: "Chuyên khoa Nổi Bật",
          medicalSpecialtiesTitle: "Chuyên Khoa Y Tế Toàn Diện",
          medicalSpecialtiesDesc: "Hệ thống chăm sóc sức khỏe đa chuyên khoa với đội ngũ bác sĩ giàu kinh nghiệm",
          doctorTeamTitle: "Đội Ngũ Bác Sĩ Chuyên Khoa",
          doctorTeamDesc: "Khamcare quy tụ đội ngũ chuyên gia, bác sĩ, dược sĩ và điều dưỡng được đào tạo bài bản đến chuyên sâu tại Việt Nam và nhiều nước có y học phát triển.",
          facilitiesTitle: "Cơ sở vật chất",
          facilitiesSubtitle: "Sở hữu không gian khám chữa bệnh văn minh, sang trọng, hiện đại hỗ trợ hiệu quả cho việc chẩn đoán và điều trị",
          
          // Specialties
          consultationTitle: "Tư vấn trực tuyến 24/7",
          specialtyTimMach: "Tim Mạch",
          specialtyNhiKhoa: "Nhi Khoa",
          specialtyNoiTongQuat: "Nội Tổng Quát",
          specialtyChanThuong: "Chấn Thương Chỉnh Hình",
          specialtyDaLieu: "Da Liễu",
          specialtyMat: "Mắt",
          specialtyNgoaiKhoa: "Ngoại Khoa",
          specialtyTaiMuiHong: "Tai Mũi Họng",
          specialtyRangHamMat: "Răng Hàm Mặt",
          specialtyThanKinh: "Thần Kinh",
          specialtySanPhuKhoa: "Sản Phụ Khoa",
          specialtyTieuHoa: "Tiêu Hóa",
          specialtyHoHap: "Hô Hấp",
          specialtyNoiTiet: "Nội Tiết",
          
          // Specialty Descriptions
          specialtyChanThuongDesc: "Điều trị các bệnh lý về xương khớp, chấn thương thể thao",
          specialtyDaLieuDesc: "Chăm sóc và điều trị các bệnh lý về da, thẩm mỹ da",
          specialtyMatDesc: "Khám và điều trị các bệnh lý về mắt, phẫu thuật mắt",
          specialtyNgoaiKhoaDesc: "Phẫu thuật các bệnh lý ngoại khoa, can thiệp tối thiểu",
          specialtyTaiMuiHongDesc: "Điều trị các bệnh lý về tai, mũi, họng và đầu cổ",
          specialtyRangHamMatDesc: "Chăm sóc sức khỏe răng miệng, phẫu thuật hàm mặt",
          specialtyThanKinhDesc: "Điều trị các bệnh lý thần kinh, đột quỵ, động kinh",
          specialtySanPhuKhoaDesc: "Chăm sóc sức khỏe phụ nữ, thai sản, sinh nở",
          specialtyTieuHoaDesc: "Điều trị các bệnh lý về dạ dày, ruột, gan mật",
          specialtyHoHapDesc: "Điều trị các bệnh lý về phổi, đường hô hấp",
          specialtyNoiTietDesc: "Điều trị tiểu đường, tuyến giáp, rối loạn hormone",
          
          // Account Menu
          menuMyProfile: "Hồ sơ của tôi",
          menuAppointments: "Lịch hẹn",
          menuOnlineConsult: "Tư vấn trực tuyến",
          menuLogout: "Đăng xuất",
          btnLogin: "Đăng nhập",
          btnRegister: "Đăng ký",
          
          // Doctor Actions
          btnProfileText: "Xem hồ sơ",
          btnBookText: "Đặt lịch",
          reviewsText: "đánh giá",
          
          // Dropdown Content
          ddDoctorsTitle: "Bác sĩ nổi bật",
          ddSpecialtiesTitle: "Chuyên khoa",
          ddGuidesTitle: "Cẩm nang Sức khỏe",
          feeLabel: "Phí:",
          currencyLabel: "VNĐ",
          noDoctorsData: "Chưa có dữ liệu bác sĩ.",
          noSpecialtiesData: "Chưa có dữ liệu chuyên khoa.",
          
          // Guide Cards
          guideCardiac: "Dinh dưỡng tim mạch",
          guideNeuro: "Sức khỏe thần kinh",
          guideMaternal: "Sức khỏe sản phụ",
          
          // Doctor Counts
          doctorsCount: "Bác sĩ",
          doctorsCount1: "5+ Bác sĩ",
          doctorsCount2: "4+ Bác sĩ",
          doctorsCount3: "3+ Bác sĩ",
          doctorsCount4: "6+ Bác sĩ",
          doctorsCount5: "4+ Bác sĩ",
          doctorsCount6: "5+ Bác sĩ",
          doctorsCount7: "4+ Bác sĩ",
          doctorsCount8: "6+ Bác sĩ",
          doctorsCount9: "5+ Bác sĩ",
          doctorsCount10: "4+ Bác sĩ",
          doctorsCount11: "3+ Bác sĩ",
          viewAllSpecialties: "Xem Tất Cả Chuyên Khoa",
          
          // Footer Content
          footerBrandTitle: "KhamCare",
          footerBrandSubtitle: "INTERNATIONAL HOSPITAL",
          footerDescription: "Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. Kết nối bạn với các bác sĩ chuyên khoa uy tín.",
          footerPatientTitle: "Dành Cho Bệnh Nhân",
          footerDoctorTitle: "Dành Cho Bác Sĩ",
          footerContactTitle: "Liên Hệ",
          footerPatientLink1: "Tìm kiếm bác sĩ",
          footerPatientLink2: "Đăng nhập",
          footerPatientLink3: "Đăng ký",
          footerPatientLink4: "Đặt lịch",
          footerPatientLink5: "Bảng kiểm soát lịch hẹn",
          footerDoctorLink1: "Kiểm tra cuộc hẹn",
          footerDoctorLink2: "Chat",
          footerDoctorLink3: "Đăng nhập",
          footerDoctorLink4: "Đăng ký",
          footerDoctorLink5: "Dashboard của bác sĩ",
          footerContactAddress: "<strong>Địa chỉ:</strong><br>Sở y tế - Bệnh viện đa khoa quốc tế<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
          footerCopyright: "© 2024 KhamCare International Hospital. Tất cả quyền được bảo lưu.",
          footerPrivacy: "Chính sách bảo mật",
          footerTerms: "Điều khoản sử dụng",
          footerSupport: "Hỗ trợ khách hàng",
          footerSocialTitle: "Kết nối với chúng tôi"
        },
        km: {
          // Navigation
          navDoctors: "ស្វែងរកវេជ្ជបណ្ឌិត",
          navSpecialties: "ជំនាញ",
          navConsultation: "ពិគ្រោះយោបល់តាមអនឡាញ", 
          navGuides: "មគ្គុទេសក៍សុខភាព",
          navDoctorAuth: "👨‍⚕️ សម្រាប់វេជ្ជបណ្ឌិត",
          account: "គណនី",
          
          // Hero Section
          heroTitle: "ការកក់តាមអនឡាញបានងាយស្រួល។ ការថែទាំសុខភាពសម្រាប់អ្នក",
          searchPlaceholder: "ស្វែងរកវេជ្ជបណ្ឌិត ឬ ជំនាញ...",
          searchBtn: "ស្វែងរក",
          btnBookingText: "កក់ពេលវេលាពិនិត្យឥឡូវ",
          optionToday: "ថ្ងៃនេះ",
          optionTomorrow: "ថ្ងៃស្អែក",
          
          // Section Titles
          specialtiesTitle: "ជំនាញពេទ្យពេញនិយម",
          medicalSpecialtiesTitle: "ជំនាញពេទ្យគ្រប់ជាង",
          medicalSpecialtiesDesc: "ប្រព័ន្ធថែទាំសុខភាពពហុជំនាញជាមួយក្រុមគ្រូពេទ្យមានបទពិសោធន៍",
          doctorTeamTitle: "ក្រុមវេជ្ជបណ្ឌិតជំនាញ",
          doctorTeamDesc: "Khamcare រួមបញ្ចូលក្រុមអ្នកជំនាញ វេជ្ជបណ្ឌិត ឱសថការី និងអ្នកថែទាំដែលបានបណ្តុះបណ្តាលយ៉ាងល្អនៅក្នុងប្រទេសវៀតណាម និងប្រទេសដែលមានការអភិវឌ្ឍវិស័យសុខាភិបាល។",
          facilitiesTitle: "បរិក្ខារផ្សព្វផ្សាយ",
          facilitiesSubtitle: "មន្ទីរពេទ្យមានបរិយាកាសទំនើប និងសមរម្យ ដើម្បីជួយក្នុងការធ្វើរោគវិទ្យា និងការព្យាបាលយ៉ាងមានប្រសិទ្ធភាព",
          
          // Specialties
          consultationTitle: "ប្រឹក្សាអនឡាញ ២៤/៧",
          specialtyTimMach: "ជំងឺបេះដូង",
          specialtyNhiKhoa: "ពេទ្យកុមារ",
          specialtyNoiTongQuat: "ពេទ្យផ្ទៃក្នុងទូទៅ",
          specialtyChanThuong: "ជំងឺឆ្អឹងសន្លាក់",
          specialtyDaLieu: "ជំងឺស្បែក",
          specialtyMat: "ជំងឺភ្នែក",
          specialtyNgoaiKhoa: "ពេទ្យវះកាត់",
          specialtyTaiMuiHong: "ជំងឺត្រចៀក ច្រមុះ បំពង់ក",
          specialtyRangHamMat: "ពេទ្យធ្មេញ ថ្គាម មុខ",
          specialtyThanKinh: "ជំងឺប្រព័ន្ធសរសៃប្រសាទ",
          specialtySanPhuKhoa: "ពេទ្យស្រ្តី និងសម្រាល",
          specialtyTieuHoa: "ជំងឺរំលាយអាហារ",
          specialtyHoHap: "ជំងឺដង្ហើម",
          specialtyNoiTiet: "ជំងឺក្រពេញ",
          
          // Specialty Descriptions
          specialtyChanThuongDesc: "ព្យាបាលជំងឺឆ្អឹងសន្លាក់ និងរបួសកីឡា",
          specialtyDaLieuDesc: "ថែទាំ និងព្យាបាលជំងឺស្បែក សម្រស់ស្បែក",
          specialtyMatDesc: "ពិនិត្យ និងព្យាបាលជំងឺភ្នែក ការវះកាត់ភ្នែក",
          specialtyNgoaiKhoaDesc: "វះកាត់ជំងឺផ្ទៃក្រៅ ការធ្វើអន្តរាគមន៍តិចតួច",
          specialtyTaiMuiHongDesc: "ព្យាបាលជំងឺត្រចៀក ច្រមុះ បំពង់ក និងក្បាល ក",
          specialtyRangHamMatDesc: "ថែទាំសុខភាពធ្មេញ មាត់ វះកាត់ថ្គាម មុខ",
          specialtyThanKinhDesc: "ព្យាបាលជំងឺប្រព័ន្ធសរសៃប្រសាទ ដាច់សរសៃឈាមខួរក្បាល ជំងឺឆ្កួត",
          specialtySanPhuKhoaDesc: "ថែទាំសុខភាពស្រ្តី ការមានផ្ទៃពោះ ការសម្រាល",
          specialtyTieuHoaDesc: "ព្យាបាលជំងឺក្រពះ ពោះវៀន ថ្លើម ប្រមាត់",
          specialtyHoHapDesc: "ព្យាបាលជំងឺសួត ផ្លូវដង្ហើម",
          specialtyNoiTietDesc: "ព្យាបាលជំងឺទឹកនោមផ្អែម ក្រពេញទីរ៉ូអ៊ីត ភាពមិនស្រួលហរម៉ូន",
          
          // Account Menu
          menuMyProfile: "ប្រវត្តិរូបរបស់ខ្ញុំ",
          menuAppointments: "ការណាត់ជួប",
          menuOnlineConsult: "ពិគ្រោះយោបល់តាមអនឡាញ",
          menuLogout: "ចាកចេញ",
          btnLogin: "ចូលប្រព័ន្ធ",
          btnRegister: "ចុះឈ្មោះ",
          
          // Doctor Actions
          btnProfileText: "មើលប្រវត្តិរូប",
          btnBookText: "កក់ពេលវេលា",
          reviewsText: "ការវាយតម្លៃ",
          
          // Dropdown Content
          ddDoctorsTitle: "វេជ្ជបណ្ឌិតល្បី",
          ddSpecialtiesTitle: "ជំនាញ",
          ddGuidesTitle: "មគ្គុទេសក៍សុខភាព",
          feeLabel: "តម្លៃ:",
          currencyLabel: "រៀល",
          noDoctorsData: "មិនមានទិន្នន័យវេជ្ជបណ្ឌិត។",
          noSpecialtiesData: "មិនមានទិន្នន័យជំនាញ។",
          
          // Guide Cards
          guideCardiac: "អាហារូបត្ថម្ភបេះដូង",
          guideNeuro: "សុខភាពប្រព័ន្ធប្រសាទ",
          guideMaternal: "សុខភាពស្រ្តីមានផ្ទៃពោះ",
          
          // Doctor Counts
          doctorsCount: "គ្រូពេទ្យ",
          doctorsCount1: "៥+ គ្រូពេទ្យ",
          doctorsCount2: "៤+ គ្រូពេទ្យ",
          doctorsCount3: "៣+ គ្រូពេទ្យ",
          doctorsCount4: "៦+ គ្រូពេទ្យ",
          doctorsCount5: "៤+ គ្រូពេទ្យ",
          doctorsCount6: "៥+ គ្រូពេទ្យ",
          doctorsCount7: "៤+ គ្រូពេទ្យ",
          doctorsCount8: "៦+ គ្រូពេទ្យ",
          doctorsCount9: "៥+ គ្រូពេទ្យ",
          doctorsCount10: "៤+ គ្រូពេទ្យ",
          doctorsCount11: "៣+ គ្រូពេទ្យ",
          viewAllSpecialties: "មើលជំនាញពេទ្យទាំងអស់",
          
          // Footer Content
          footerBrandTitle: "KhamCare",
          footerBrandSubtitle: "មន្ទីរពេទ្យអន្តរជាតិ",
          footerDescription: "ប្រព័ន្ធកក់ពេលវេលាពិនិត្យសុខភាពតាមអនឡាញឈានមុខគេនៅវៀតណាម។ ភ្ជាប់អ្នកជាមួយវេជ្ជបណ្ឌិតជំនាញដែលមានកេរ្តិ៍ឈ្មោះ។",
          footerPatientTitle: "សម្រាប់អ្នកជម្ងឺ",
          footerDoctorTitle: "សម្រាប់វេជ្ជបណ្ឌិត",
          footerContactTitle: "ទាក់ទង",
          footerPatientLink1: "ស្វែងរកវេជ្ជបណ្ឌិត",
          footerPatientLink2: "ចូលប្រព័ន្ធ",
          footerPatientLink3: "ចុះឈ្មោះ",
          footerPatientLink4: "កក់ពេលវេលា",
          footerPatientLink5: "តារាងត្រួតពិនិត្យការណាត់ជួប",
          footerDoctorLink1: "ត្រួតពិនិត្យការណាត់ជួប",
          footerDoctorLink2: "ជជែក",
          footerDoctorLink3: "ចូលប្រព័ន្ធ",
          footerDoctorLink4: "ចុះឈ្មោះ",
          footerDoctorLink5: "ផ្ទាំងគ្រប់គ្រងរបស់វេជ្ជបណ្ឌិត",
          footerContactAddress: "<strong>អាសយដ្ឋាន:</strong><br>ក្រសួងសុខាភិបាល - មន្ទីរពេទ្យពហុជំនាញអន្តរជាតិ<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
          footerCopyright: "© ២០២៤ KhamCare មន្ទីរពេទ្យអន្តរជាតិ។ រក្សាសិទ្ធិគ្រប់យ៉ាង។",
          footerPrivacy: "គោលការណ៍ភាពឯកជន",
          footerTerms: "លក្ខខណ្ឌប្រើប្រាស់",
          footerSupport: "ការគាំទ្រអតិថិជន",
          footerSocialTitle: "ភ្ជាប់ជាមួយយើង"
        }
      };
      
      // Apply language function - Complete page translation
      function applyLanguage(lang) {
        const t = translations[lang];
        if (!t) return;
        
        console.log('🌐 Applying language:', lang);
        
        // Update navigation
        const navDoctors = document.getElementById('navDoctors');
        const navSpecialties = document.getElementById('navSpecialties');
        const navConsultation = document.getElementById('navConsultation');
        const navGuides = document.getElementById('navGuides');
        const navDoctorAuth = document.getElementById('navDoctorAuth');
        const btnAccount = document.getElementById('btnAccount');
        
        if (navDoctors) navDoctors.textContent = t.navDoctors;
        if (navSpecialties) navSpecialties.textContent = t.navSpecialties;
        if (navConsultation) navConsultation.textContent = t.navConsultation;
        if (navGuides) navGuides.textContent = t.navGuides;
        if (navDoctorAuth) navDoctorAuth.innerHTML = t.navDoctorAuth;
        if (btnAccount) btnAccount.innerHTML = '<i class="fas fa-user"></i> ' + t.account;
        
        // Update account dropdown menu
        const menuMyProfile = document.getElementById('menuMyProfile');
        const menuAppointments = document.getElementById('menuAppointments');
        const menuOnlineConsult = document.getElementById('menuOnlineConsult');
        const menuLogout = document.getElementById('menuLogout');
        
        if (menuMyProfile && t.menuMyProfile) menuMyProfile.textContent = t.menuMyProfile;
        if (menuAppointments && t.menuAppointments) menuAppointments.textContent = t.menuAppointments;
        if (menuOnlineConsult && t.menuOnlineConsult) menuOnlineConsult.textContent = t.menuOnlineConsult;
        if (menuLogout && t.menuLogout) menuLogout.textContent = t.menuLogout;
        
        // Update login/register buttons
        const btnLoginElements = document.querySelectorAll('.btn-login');
        const btnRegisterElements = document.querySelectorAll('.btn-register');
        
        btnLoginElements.forEach(btn => {
          if (btn && t.btnLogin) btn.textContent = t.btnLogin;
        });
        
        btnRegisterElements.forEach(btn => {
          if (btn && t.btnRegister) btn.textContent = t.btnRegister;
        });
        
        // Update hero section
        const heroTitle = document.getElementById('heroTitle');
        const searchInput = document.getElementById('searchInput');
        const btnSearch = document.getElementById('btnSearch');
        const optionToday = document.getElementById('optionToday');
        const optionTomorrow = document.getElementById('optionTomorrow');
        
        if (heroTitle) heroTitle.textContent = t.heroTitle;
        if (searchInput) searchInput.placeholder = t.searchPlaceholder;
        if (btnSearch) btnSearch.innerHTML = '<i class="fa fa-search"></i> ' + t.searchBtn;
        if (optionToday) optionToday.textContent = t.optionToday;
        if (optionTomorrow) optionTomorrow.textContent = t.optionTomorrow;
        
        // Update booking button
        const btnBooking = document.getElementById('btnBooking');
        if (btnBooking && t.btnBookingText) btnBooking.innerHTML = '<i class="fa fa-calendar-plus"></i> ' + t.btnBookingText;
        
        // Update section titles
        const specialtiesTitle = document.getElementById('specialtiesTitle');
        const medicalSpecialtiesTitle = document.getElementById('medicalSpecialtiesTitle');
        const medicalSpecialtiesDesc = document.getElementById('medicalSpecialtiesDesc');
        const doctorTeamTitle = document.getElementById('doctorTeamTitle');
        const doctorTeamDesc = document.getElementById('doctorTeamDesc');
        
        if (specialtiesTitle) specialtiesTitle.textContent = t.specialtiesTitle;
        if (medicalSpecialtiesTitle) medicalSpecialtiesTitle.textContent = t.medicalSpecialtiesTitle;
        if (medicalSpecialtiesDesc) medicalSpecialtiesDesc.textContent = t.medicalSpecialtiesDesc;
        if (doctorTeamTitle) doctorTeamTitle.textContent = t.doctorTeamTitle;
        if (doctorTeamDesc) doctorTeamDesc.textContent = t.doctorTeamDesc;
        
        // Update consultation title
        const consultationTitle = document.getElementById('consultationTitle');
        if (consultationTitle) consultationTitle.textContent = t.consultationTitle;
        
        // Update specialties
        const specialtyElements = [
          { id: 'specialty-tim-mach', key: 'specialtyTimMach' },
          { id: 'specialty-nhi-khoa', key: 'specialtyNhiKhoa' },
          { id: 'specialty-noi-tong-quat', key: 'specialtyNoiTongQuat' },
          { id: 'specialty-chan-thuong', key: 'specialtyChanThuong' },
          { id: 'specialty-da-lieu', key: 'specialtyDaLieu' },
          { id: 'specialty-mat', key: 'specialtyMat' },
          { id: 'specialty-ngoai-khoa', key: 'specialtyNgoaiKhoa' },
          { id: 'specialty-tai-mui-hong', key: 'specialtyTaiMuiHong' },
          { id: 'specialty-rang-ham-mat', key: 'specialtyRangHamMat' },
          { id: 'specialty-than-kinh', key: 'specialtyThanKinh' },
          { id: 'specialty-san-phu-khoa', key: 'specialtySanPhuKhoa' },
          { id: 'specialty-tieu-hoa', key: 'specialtyTieuHoa' },
          { id: 'specialty-ho-hap', key: 'specialtyHoHap' },
          { id: 'specialty-noi-tiet', key: 'specialtyNoiTiet' },
          // Comprehensive section (duplicate IDs with comp- prefix)
          { id: 'comp-specialty-than-kinh', key: 'specialtyThanKinh' },
          { id: 'comp-specialty-san-phu-khoa', key: 'specialtySanPhuKhoa' },
          { id: 'comp-specialty-da-lieu', key: 'specialtyDaLieu' }
        ];
        
        specialtyElements.forEach(item => {
          const element = document.getElementById(item.id);
          if (element && t[item.key]) {
            element.textContent = t[item.key];
          }
        });
        
        // Update specialty descriptions
        const specialtyDescElements = [
          { id: 'specialty-chan-thuong-desc', key: 'specialtyChanThuongDesc' },
          { id: 'specialty-da-lieu-desc', key: 'specialtyDaLieuDesc' },
          { id: 'specialty-mat-desc', key: 'specialtyMatDesc' },
          { id: 'specialty-ngoai-khoa-desc', key: 'specialtyNgoaiKhoaDesc' },
          { id: 'specialty-tai-mui-hong-desc', key: 'specialtyTaiMuiHongDesc' },
          { id: 'specialty-rang-ham-mat-desc', key: 'specialtyRangHamMatDesc' },
          { id: 'specialty-than-kinh-desc', key: 'specialtyThanKinhDesc' },
          { id: 'specialty-san-phu-khoa-desc', key: 'specialtySanPhuKhoaDesc' },
          { id: 'specialty-tieu-hoa-desc', key: 'specialtyTieuHoaDesc' },
          { id: 'specialty-ho-hap-desc', key: 'specialtyHoHapDesc' },
          { id: 'specialty-noi-tiet-desc', key: 'specialtyNoiTietDesc' },
          // Comprehensive section descriptions
          { id: 'comp-specialty-than-kinh-desc', key: 'specialtyThanKinhDesc' },
          { id: 'comp-specialty-san-phu-khoa-desc', key: 'specialtySanPhuKhoaDesc' },
          { id: 'comp-specialty-da-lieu-desc', key: 'specialtyDaLieuDesc' }
        ];
        
        specialtyDescElements.forEach(item => {
          const element = document.getElementById(item.id);
          if (element && t[item.key]) {
            element.textContent = t[item.key];
          }
        });
        
        // Update doctor counts in comprehensive section
        for (let i = 1; i <= 11; i++) {
          const doctorCount = document.getElementById(`doctors-count-${i}`);
          if (doctorCount && t[`doctorsCount${i}`]) {
            doctorCount.textContent = t[`doctorsCount${i}`];
          }
        }
        
        // Update dropdown titles
        const ddDoctorsTitle = document.getElementById('ddDoctorsTitle');
        const ddSpecialtiesTitle = document.getElementById('ddSpecialtiesTitle');
        const ddGuidesTitle = document.getElementById('ddGuidesTitle');
        
        if (ddDoctorsTitle) ddDoctorsTitle.textContent = t.ddDoctorsTitle;
        if (ddSpecialtiesTitle) ddSpecialtiesTitle.textContent = t.ddSpecialtiesTitle;
        if (ddGuidesTitle) ddGuidesTitle.textContent = t.ddGuidesTitle;
        
        // Update dropdown content
        const feeLabel = document.getElementById('feeLabel');
        const currencyLabel = document.getElementById('currencyLabel');
        const noDoctorsData = document.getElementById('noDoctorsData');
        const noSpecialtiesData = document.getElementById('noSpecialtiesData');
        
        if (feeLabel) feeLabel.textContent = t.feeLabel;
        if (currencyLabel) currencyLabel.textContent = t.currencyLabel;
        if (noDoctorsData) noDoctorsData.textContent = t.noDoctorsData;
        if (noSpecialtiesData) noSpecialtiesData.textContent = t.noSpecialtiesData;
        
        // Update guide cards
        const guideCardiac = document.getElementById('guideCardiac');
        const guideNeuro = document.getElementById('guideNeuro');
        const guideMaternal = document.getElementById('guideMaternal');
        
        if (guideCardiac) guideCardiac.textContent = t.guideCardiac;
        if (guideNeuro) guideNeuro.textContent = t.guideNeuro;
        if (guideMaternal) guideMaternal.textContent = t.guideMaternal;
        
        // Update doctor action buttons
        const btnProfileTexts = document.querySelectorAll('.btn-profile-text');
        const btnBookTexts = document.querySelectorAll('.btn-book-text');
        const reviewsTexts = document.querySelectorAll('.reviews-text');
        
        btnProfileTexts.forEach(btn => {
          if (btn) btn.textContent = t.btnProfileText;
        });
        
        btnBookTexts.forEach(btn => {
          if (btn) btn.textContent = t.btnBookText;
        });
        
        reviewsTexts.forEach(text => {
          if (text) text.textContent = t.reviewsText;
        });
        
        // Update facilities section
        const facilitiesTitle = document.querySelector('.facilities-section .section-title');
        const facilitiesSubtitle = document.querySelector('.facilities-section .section-subtitle');
        
        if (facilitiesTitle) facilitiesTitle.textContent = t.facilitiesTitle;
        if (facilitiesSubtitle) facilitiesSubtitle.textContent = t.facilitiesSubtitle;
        
        // Update view all button
        const viewAllSpecialties = document.getElementById('viewAllSpecialties');
        if (viewAllSpecialties) viewAllSpecialties.textContent = t.viewAllSpecialties;
        
        // Update footer content
        const footerBrandTitle = document.getElementById('footerBrandTitle');
        const footerBrandSubtitle = document.getElementById('footerBrandSubtitle');
        const footerDescription = document.getElementById('footerDescription');
        const footerPatientTitle = document.getElementById('footerPatientTitle');
        const footerDoctorTitle = document.getElementById('footerDoctorTitle');
        const footerContactTitle = document.getElementById('footerContactTitle');
        
        if (footerBrandTitle) footerBrandTitle.textContent = t.footerBrandTitle;
        if (footerBrandSubtitle) footerBrandSubtitle.textContent = t.footerBrandSubtitle;
        if (footerDescription) footerDescription.textContent = t.footerDescription;
        if (footerPatientTitle) footerPatientTitle.innerHTML = '<i class="fas fa-user-injured"></i> ' + t.footerPatientTitle;
        if (footerDoctorTitle) footerDoctorTitle.innerHTML = '<i class="fas fa-user-md"></i> ' + t.footerDoctorTitle;
        if (footerContactTitle) footerContactTitle.innerHTML = '<i class="fas fa-phone-alt"></i> ' + t.footerContactTitle;
        
        // Update footer links
        const footerLinks = [
          { id: 'footerPatientLink1', key: 'footerPatientLink1' },
          { id: 'footerPatientLink2', key: 'footerPatientLink2' },
          { id: 'footerPatientLink3', key: 'footerPatientLink3' },
          { id: 'footerPatientLink4', key: 'footerPatientLink4' },
          { id: 'footerPatientLink5', key: 'footerPatientLink5' },
          { id: 'footerDoctorLink1', key: 'footerDoctorLink1' },
          { id: 'footerDoctorLink2', key: 'footerDoctorLink2' },
          { id: 'footerDoctorLink3', key: 'footerDoctorLink3' },
          { id: 'footerDoctorLink4', key: 'footerDoctorLink4' },
          { id: 'footerDoctorLink5', key: 'footerDoctorLink5' }
        ];
        
        footerLinks.forEach(item => {
          const element = document.getElementById(item.id);
          if (element && t[item.key]) {
            element.textContent = t[item.key];
          }
        });
        
        // Update footer contact and copyright
        const footerContactAddress = document.getElementById('footerContactAddress');
        const footerCopyright = document.getElementById('footerCopyright');
        const footerPrivacy = document.getElementById('footerPrivacy');
        const footerTerms = document.getElementById('footerTerms');
        const footerSupport = document.getElementById('footerSupport');
        const footerSocialTitle = document.getElementById('footerSocialTitle');
        
        if (footerContactAddress) footerContactAddress.innerHTML = t.footerContactAddress;
        if (footerCopyright) footerCopyright.textContent = t.footerCopyright;
        if (footerPrivacy) footerPrivacy.textContent = t.footerPrivacy;
        if (footerTerms) footerTerms.textContent = t.footerTerms;
        if (footerSupport) footerSupport.textContent = t.footerSupport;
        if (footerSocialTitle) footerSocialTitle.textContent = t.footerSocialTitle;
        
        console.log('✅ Complete page + footer translation applied for:', lang);
      }
      
      // Account Dropdown Toggle - Global function
      window.toggleAccountDropdown = function(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('accountDropdown');
        if (dropdown) {
          dropdown.classList.toggle('active');
        }
      };

      // Close account dropdown when clicking outside
      document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('accountDropdown');
        if (dropdown && !dropdown.contains(event.target)) {
          dropdown.classList.remove('active');
        }
      });
      
      // Setup language switcher
      const langToggle = document.getElementById('langToggle');
      const langDropdown = document.getElementById('langDropdown');
      const langOptions = document.querySelectorAll('.lang-option');
      const currentLang = document.getElementById('currentLang');
      
      if (langToggle && langDropdown) {
        // Toggle dropdown
        langToggle.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          langDropdown.classList.toggle('show');
        });
        
        // Handle language selection
        langOptions.forEach(option => {
          option.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const selectedLang = this.getAttribute('data-lang');
            
            // Update active state
            langOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            // Update button text
            const langCode = this.querySelector('.lang-code').textContent;
            if (currentLang) {
              currentLang.textContent = langCode;
            }
            
            // Close dropdown
            langDropdown.classList.remove('show');
            
            // Save and apply language
            localStorage.setItem('kham_lang', selectedLang);
            applyLanguage(selectedLang);
            
            // Show notification
            const message = selectedLang === 'vi' ? '🇻🇳 Tiếng Việt' : '🇰🇭 ភាសាខ្មែរ';
            const notification = document.createElement('div');
            notification.style.cssText = `
              position: fixed; top: 80px; right: 20px; background: #10b981;
              color: white; padding: 8px 16px; border-radius: 20px; z-index: 10000;
              font-weight: 600; font-size: 0.9rem; transform: translateX(100%);
              transition: transform 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.style.transform = 'translateX(0)', 10);
            setTimeout(() => {
              notification.style.transform = 'translateX(100%)';
              setTimeout(() => notification.remove(), 300);
            }, 2000);
          });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
          if (!langToggle.contains(e.target) && !langDropdown.contains(e.target)) {
            langDropdown.classList.remove('show');
          }
        });
      }
      
      // Apply saved language on load
      const savedLang = localStorage.getItem('kham_lang') || 'vi';
      if (currentLang) {
        currentLang.textContent = savedLang === 'vi' ? 'VI' : 'KM';
      }
      // Update active state based on saved language
      const savedOption = document.querySelector(`.lang-option[data-lang="${savedLang}"]`);
      if (savedOption) {
        langOptions.forEach(opt => opt.classList.remove('active'));
        savedOption.classList.add('active');
      }
      applyLanguage(savedLang);
      
      // Keyboard shortcut (Ctrl+L)
      document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'l') {
          e.preventDefault();
          const current = localStorage.getItem('kham_lang') || 'vi';
          const newLang = current === 'vi' ? 'km' : 'vi';
          localStorage.setItem('kham_lang', newLang);
          if (currentLangText) {
            currentLangText.textContent = newLang === 'vi' ? 'VN' : 'KH';
          }
          applyLanguage(newLang);
        }
      });
    });
    </script>

    <section class="hero-section">
  <div class="container">
    <div class="hero-content">
      <h1 id="heroTitle">Đặt lịch khám bệnh dễ dàng. Chăm sóc sức khỏe toàn diện cho bạn</h1>
      <button id="btnBooking" class="btn-search" onclick="window.location.href='DatLichK.php'" style="font-size: 1.2rem; padding: 18px 40px; min-width: 300px; margin: 30px auto 0 auto; display: block;">
        <i class="fa fa-calendar-plus"></i> Đặt lịch khám ngay
      </button>
    </div>
  </div>

  
</section>


    
        
<script>
// Global dictionary for language translations
window.dict = {
    vi: {
      navDoctors: "Tìm kiếm Bác sĩ",
      navSpecialties: "Chuyên khoa",
      navConsultation: "Tư vấn trực tuyến",
      navGuides: "Cẩm nang Sức khỏe",
      navDoctorAuth: "👨‍⚕️ Dành cho Bác sĩ",
      account: "Tài khoản",
      consultationTitle: "Tư vấn trực tuyến 24/7",
      bookAppointment: "Đặt lịch khám",
      onlineConsultation: "Tư vấn trực tuyến",
      heroTitle: "Đặt lịch khám bệnh dễ dàng. Chăm sóc sức khỏe toàn diện cho bạn",    
      searchPlaceholder: "Tìm kiếm bác sĩ, chuyên khoa...",
      searchBtn: '<i class="fa fa-search"></i> Tìm kiếm',
      specialtiesTitle: "Chuyên khoa Nổi Bật",
      medicalSpecialtiesTitle: "Chuyên Khoa Y Tế Toàn Diện",
      doctorTeamTitle: "Đội Ngũ Bác Sĩ Chuyên Khoa",
      promoTitle: "Tư vấn trực tuyến 24/7",
      promoDesc: "Luôn sẵn sàng hỗ trợ bạn",
      // Specialty translations
      specialtyTimMach: "Tim Mạch",
      specialtyNhiKhoa: "Nhi Khoa", 
      specialtyNoiTongQuat: "Nội Tổng Quát",
      specialtyChanThuong: "Chấn Thương Chỉnh Hình",
      specialtyDaLieu: "Da Liễu",
      specialtyMat: "Mắt",
      specialtyNgoaiKhoa: "Ngoại Khoa",
      specialtyTaiMuiHong: "Tai Mũi Họng",
      specialtyRangHamMat: "Răng Hàm Mặt",
      specialtyThanKinh: "Thần Kinh",
      specialtySanPhuKhoa: "Sản Phụ Khoa",
      specialtyTieuHoa: "Tiêu Hóa",
      specialtyHoHap: "Hô Hấp",
      specialtyNoiTiet: "Nội Tiết",
      // Specialty descriptions
      specialtyChanThuongDesc: "Điều trị các bệnh lý về xương khớp, chấn thương thể thao",
      specialtyDaLieuDesc: "Chăm sóc và điều trị các bệnh lý về da, thẩm mỹ da",
      specialtyMatDesc: "Khám và điều trị các bệnh lý về mắt, phẫu thuật mắt",
      specialtyNgoaiKhoaDesc: "Phẫu thuật các bệnh lý ngoại khoa, can thiệp tối thiểu",
      specialtyTaiMuiHongDesc: "Điều trị các bệnh lý về tai, mũi, họng và đầu cổ",
      specialtyRangHamMatDesc: "Chăm sóc sức khỏe răng miệng, phẫu thuật hàm mặt",
      specialtyThanKinhDesc: "Điều trị các bệnh lý thần kinh, đột quỵ, động kinh",
      specialtySanPhuKhoaDesc: "Chăm sóc sức khỏe phụ nữ, thai sản, sinh nở",
      specialtyTieuHoaDesc: "Điều trị các bệnh lý về dạ dày, ruột, gan mật",
      specialtyHoHapDesc: "Điều trị các bệnh lý về phổi, đường hô hấp",
      specialtyNoiTietDesc: "Điều trị tiểu đường, tuyến giáp, rối loạn hormone",
      // Doctor counts
      doctorsCount1: "5+ Bác sĩ",
      doctorsCount2: "4+ Bác sĩ",
      doctorsCount3: "3+ Bác sĩ",
      doctorsCount4: "6+ Bác sĩ",
      doctorsCount5: "4+ Bác sĩ",
      doctorsCount6: "5+ Bác sĩ",
      doctorsCount7: "4+ Bác sĩ",
      doctorsCount8: "6+ Bác sĩ",
      doctorsCount9: "5+ Bác sĩ",
      doctorsCount10: "4+ Bác sĩ",
      doctorsCount11: "3+ Bác sĩ",
      // Other elements
      medicalSpecialtiesDesc: "Hệ thống chăm sóc sức khỏe đa chuyên khoa với đội ngũ bác sĩ giàu kinh nghiệm",
      viewAllSpecialties: "Xem Tất Cả Chuyên Khoa",
      facilitiesTitle: "Cơ sở vật chất",
      facilitiesSubtitle: "Sở hữu không gian khám chữa bệnh văn minh, sang trọng, hiện đại hỗ trợ hiệu quả cho việc chẩn đoán và điều trị",
      facilityItems: [
        'Hệ thống phòng mổ Hybrid hiện đại nhất Việt Nam',
        'Hệ thống phòng sinh hiện đại giúp mẹ bầu vượt cạn',
        'Nhà thuốc đạt tiêu chuẩn GPP với danh mục thuốc',
        'Hệ thống phòng sinh hiện đại giúp mẹ bầu vượt cạn',
        'Hệ thống phòng sinh hiện đại giúp mẹ bầu vượt cạn',
        'Hệ thống phòng sinh hiện đại giúp mẹ bầu vượt cạn'
      ],
      footerPatient: "Dành Cho Bệnh Nhân",
      footerDoctor: "Dành Cho Bác Sĩ",
      footerContact: "Liên Hệ",
      footerDesc: "Hệ thống web site đặt lịch khám bệnh trực tuyến",
      account_name_placeholder: "Tên hiển thị",
      account_username_placeholder: "Tên đăng nhập",
      account_email_placeholder: "Email",
      chat_placeholder: "Nhập tin nhắn...",
      chat_start_msg: "Xin chào! Tôi là trợ lý AI y tế. Hãy mô tả triệu chứng của bạn để tôi gợi ý chuyên khoa phù hợp nhé! 🏥",
      searchDateOptions: {
        today: "Hôm nay",
        tomorrow: "Ngày mai"
      },
      // Dropdown translations
      ddDoctorsTitle: "Bác sĩ nổi bật",
      ddSpecialtiesTitle: "Chuyên khoa", 
      ddGuidesTitle: "Cẩm nang Sức khỏe",
      feeLabel: "Phí:",
      currencyLabel: "VNĐ",
      noDoctorsData: "Chưa có dữ liệu bác sĩ.",
      noSpecialtiesData: "Chưa có dữ liệu chuyên khoa.",
      guideCardiac: "Dinh dưỡng tim mạch",
      guideNeuro: "Sức khỏe thần kinh", 
      guideMaternal: "Sức khỏe sản phụ",
      // Doctor actions
      btnProfileText: "Xem hồ sơ",
      btnBookText: "Đặt lịch",
      // Doctor team section
      doctorTeamDesc: "Khamcare quy tụ đội ngũ chuyên gia, bác sĩ, dược sĩ và điều dưỡng được đào tạo bài bản đến chuyên sâu tại Việt Nam và nhiều nước có y học phát triển.",
      // Search messages
      searchAlert: "Vui lòng nhập tên bác sĩ hoặc chuyên khoa!",
      noDoctorsFound: "Không tìm thấy bác sĩ nào cho chuyên khoa này.",
      reviewsText: "đánh giá"
    },
    km: {
      navDoctors: "ស្វែងរកវេជ្ជបណ្ឌិត",
      navSpecialties: "ជំនាញ",
      navConsultation: "ប្រឹក្សាអនឡាញ",
      navGuides: "មគ្គុទ្ទេសក៍សុខភាព",
      navDoctorAuth: "👨‍⚕️ សម្រាប់វេជ្ជបណ្ឌិត",
      navSpecialties: "ជំនាញ",
      navConsultation: "ពិគ្រោះយោបល់តាមអនឡាញ",
      navGuides: "មគ្គុទេសក៍សុខភាព",
      account: "គណនី",
      consultationTitle: "ប្រឹក្សាអនឡាញ ២៤/៧",
      bookAppointment: "កក់ពេលវេលាពិនិត្យ",
      onlineConsultation: "ពិគ្រោះយោបល់តាមអនឡាញ",
      heroTitle: "ការកក់តាមអនឡាញបានងាយស្រួល។ ការថែទាំសុខភាពសម្រាប់អ្នក",
      searchPlaceholder: "ស្វែងរកវេជ្ជបណ្ឌិត ឬ ជំនាញ...",
      searchBtn: '<i class="fa fa-search"></i> ស្វែងរក',
      specialtiesTitle: "ជំនាញពេទ្យពេញនិយម",
      medicalSpecialtiesTitle: "ជំនាញពេទ្យគ្រប់ជាង",
      doctorTeamTitle: "ក្រុមវេជ្ជបណ្ឌិតជំនាញ",
      promoTitle: "ប្រឹក្សាអនឡាញ ២៤/៧",
      promoDesc: "យើងរីករាយជួយអ្នក",
      // Specialty translations
      specialtyTimMach: "ជំងឺបេះដូង",
      specialtyNhiKhoa: "ពេទ្យកុមារ", 
      specialtyNoiTongQuat: "ពេទ្យផ្ទៃក្នុងទូទៅ",
      specialtyChanThuong: "ជំងឺឆ្អឹងសន្លាក់",
      specialtyDaLieu: "ជំងឺស្បែក",
      specialtyMat: "ជំងឺភ្នែក",
      specialtyNgoaiKhoa: "ពេទ្យវះកាត់",
      specialtyTaiMuiHong: "ជំងឺត្រចៀក ច្រមុះ បំពង់ក",
      specialtyRangHamMat: "ពេទ្យធ្មេញ ថ្គាម មុខ",
      specialtyThanKinh: "ជំងឺប្រព័ន្ធសរសៃប្រសាទ",
      specialtySanPhuKhoa: "ពេទ្យស្រ្តី និងសម្រាល",
      specialtyTieuHoa: "ជំងឺរំលាយអាហារ",
      specialtyHoHap: "ជំងឺដង្ហើម",
      specialtyNoiTiet: "ជំងឺក្រពេញ",
      // Specialty descriptions
      specialtyChanThuongDesc: "ព្យាបាលជំងឺឆ្អឹងសន្លាក់ និងរបួសកីឡា",
      specialtyDaLieuDesc: "ថែទាំ និងព្យាបាលជំងឺស្បែក សម្រស់ស្បែក",
      specialtyMatDesc: "ពិនិត្យ និងព្យាបាលជំងឺភ្នែក ការវះកាត់ភ្នែក",
      specialtyNgoaiKhoaDesc: "វះកាត់ជំងឺផ្ទៃក្រៅ ការធ្វើអន្តរាគមន៍តិចតួច",
      specialtyTaiMuiHongDesc: "ព្យាបាលជំងឺត្រចៀក ច្រមុះ បំពង់ក និងក្បាល ក",
      specialtyRangHamMatDesc: "ថែទាំសុខភាពធ្មេញ មាត់ វះកាត់ថ្គាម មុខ",
      specialtyThanKinhDesc: "ព្យាបាលជំងឺប្រព័ន្ធសរសៃប្រសាទ ដាច់សរសៃឈាមខួរក្បាល ជំងឺឆ្កួត",
      specialtySanPhuKhoaDesc: "ថែទាំសុខភាពស្រ្តី ការមានផ្ទៃពោះ ការសម្រាល",
      specialtyTieuHoaDesc: "ព្យាបាលជំងឺក្រពះ ពោះវៀន ថ្លើម ប្រមាត់",
      specialtyHoHapDesc: "ព្យាបាលជំងឺសួត ផ្លូវដង្ហើម",
      specialtyNoiTietDesc: "ព្យាបាលជំងឺទឹកនោមផ្អែម ក្រពេញទីរ៉ូអ៊ីត ភាពមិនស្រួលហរម៉ូន",
      // Doctor counts
      doctorsCount1: "៥+ គ្រូពេទ្យ",
      doctorsCount2: "៤+ គ្រូពេទ្យ",
      doctorsCount3: "៣+ គ្រូពេទ្យ",
      doctorsCount4: "៦+ គ្រូពេទ្យ",
      doctorsCount5: "៤+ គ្រូពេទ្យ",
      doctorsCount6: "៥+ គ្រូពេទ្យ",
      doctorsCount7: "៤+ គ្រូពេទ្យ",
      doctorsCount8: "៦+ គ្រូពេទ្យ",
      doctorsCount9: "៥+ គ្រូពេទ្យ",
      doctorsCount10: "៤+ គ្រូពេទ្យ",
      doctorsCount11: "៣+ គ្រូពេទ្យ",
      // Other elements
      medicalSpecialtiesDesc: "ប្រព័ន្ធថែទាំសុខភាពពហុជំនាញជាមួយក្រុមគ្រូពេទ្យមានបទពិសោធន៍",
      viewAllSpecialties: "មើលជំនាញពេទ្យទាំងអស់",
      facilitiesTitle: "បរិក្ខារ",
      facilitiesSubtitle: "មណ្ឌលពេទ្យមានបរិយាកាសទាន់សម័យ និងឧបករណ៍សមរម្យ ដើម្បីជួយក្នុងការធ្វើពិនិត្យ និងព្យាបាល",
      facilityItems: [
        'ប្រព័ន្ធបន្ទប់ប្រតិបត្តិការ Hybrid ទាន់សម័យបំផុតនៅវៀតណាម',
        'ប្រព័ន្ធបន្ទប់សម្រាលទាន់សម័យ ជួយឱ្យម្ដាយសម្រាលបានសុវត្ថិភាព',
        'មន្ទីរឱសថអនុវត្តតាមស្តង់ដារ GPP',
        'ប្រព័ន្ធបន្ទប់សម្រាលទាន់សម័យ ជួយឱ្យម្ដាយសម្រាលបានសុវត្ថិភាព',
        'បន្ទប់ពេទ្យ និងឧបករណ៍គាំទ្រទាន់សម័យ',
        'សេវាកម្មគាំទ្រផ្សេងៗដើម្បីការព្យាបាលប្រសើរឡើង'
      ],
      footerPatient: "សម្រាប់អ្នកជម្ងឺ",
      footerDoctor: "សម្រាប់វេជ្ជបណ្ឌិត",
      footerContact: "ទាក់ទង",
      footerDesc: "ប្រព័ន្ធកក់ពេលវេលាធ្វើតេស្តតាមអនឡាញ",
      account_name_placeholder: "ឈ្មោះបង្ហាញ",
      account_username_placeholder: "ឈ្មោះចូលប្រព័ន្ធ",
      account_email_placeholder: "អ៊ីម៉ែល",
      chat_placeholder: "បញ្ចូលសាររបស់អ្នក...",
      chat_start_msg: "សួស្តី! ខ្ញុំជាជំនួយការ AI វេជ្ជសាស្ត្រ។ សូមពិពណ៌នាអំពីរោគសញ្ញារបស់អ្នក ដើម្បីខ្ញុំណែនាំជំនាញសមរម្យ! 🏥",
      searchDateOptions: {
        today: "ថ្ងៃនេះ",
        tomorrow: "ថ្ងៃស្អែក" 
      },
      // Dropdown translations
      ddDoctorsTitle: "វេជ្ជបណ្ឌិតល្បី",
      ddSpecialtiesTitle: "ជំនាញ",
      ddGuidesTitle: "មគ្គុទេសក៍សុខភាព",
      feeLabel: "តម្លៃ:",
      currencyLabel: "រៀល",
      noDoctorsData: "មិនមានទិន្នន័យវេជ្ជបណ្ឌិត។",
      noSpecialtiesData: "មិនមានទិន្នន័យជំនាញ។",
      guideCardiac: "អាហារូបត្ថម្ភបេះដូង",
      guideNeuro: "សុខភាពប្រព័ន្ធប្រសាទ",
      guideMaternal: "សុខភាពស្រ្តីមានផ្ទៃពោះ",
      // Doctor actions
      btnProfileText: "មើលប្រវត្តិរូប",
      btnBookText: "កក់ពេលវេលា",
      // Doctor team section  
      doctorTeamDesc: "Khamcare រួមបញ្ចូលក្រុមអ្នកជំនាញ វេជ្ជបណ្ឌិត ឱសថការី និងអ្នកថែទាំដែលបានបណ្តុះបណ្តាលយ៉ាងល្អនៅក្នុងប្រទេសវៀតណាម និងប្រទេសដែលមានការអភិវឌ្ឍវិស័យសុខាភិបាល។",
      // Search messages
      searchAlert: "សូមបញ្ចូលឈ្មោះវេជ្ជបណ្ឌិត ឬជំនាញ!",
      noDoctorsFound: "រកមិនឃើញវេជ្ជបណ្ឌិតសម្រាប់ជំនាញនេះទេ។",
      reviewsText: "ការវាយតម្លៃ"
    }
  };

  // Footer Translation System
  const footerI18n = {
    vi: {
        brandTitle: "KhamCare",
        brandSubtitle: "INTERNATIONAL HOSPITAL",
        description: "Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. Kết nối bạn với các bác sĩ chuyên khoa uy tín.",
        patientTitle: "Dành Cho Bệnh Nhân",
        patientLinks: [
            "Tìm kiếm bác sĩ",
            "Đăng nhập",
            "Đăng ký",
            "Đặt lịch",
            "Bảng kiểm soát lịch hẹn"
        ],
        doctorTitle: "Dành Cho Bác Sĩ",
        doctorLinks: [
            "Kiểm tra cuộc hẹn",
            "Chat",
            "Đăng nhập",
            "Đăng ký",
            "Dashboard của bác sĩ"
        ],
        contactTitle: "Liên Hệ",
        contactAddress: "<strong>Địa chỉ:</strong><br>Sở y tế - Bệnh viện đa khoa quốc tế<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
        copyright: "© 2024 KhamCare International Hospital. Tất cả quyền được bảo lưu.",
        privacy: "Chính sách bảo mật",
        terms: "Điều khoản sử dụng",
        support: "Hỗ trợ khách hàng",
        statPatients: "Bệnh nhân",
        statDoctors: "Bác sĩ",
        statSpecialties: "Chuyên khoa",
        socialTitle: "Kết nối với chúng tôi"
    },
    km: {
        brandTitle: "KhamCare",
        brandSubtitle: "មន្ទីរពេទ្យអន្តរជាតិ",
        description: "ប្រព័ន្ធកក់ពេលវេលាពិនិត្យសុខភាពតាមអនឡាញឈានមុខគេនៅវៀតណាម។ ភ្ជាប់អ្នកជាមួយវេជ្ជបណ្ឌិតជំនាញដែលមានកេរ្តិ៍ឈ្មោះ។",
        patientTitle: "សម្រាប់អ្នកជម្ងឺ",
        patientLinks: [
            "ស្វែងរកវេជ្ជបណ្ឌិត",
            "ចូលប្រព័ន្ធ",
            "ចុះឈ្មោះ",
            "កក់ពេលវេលា",
            "តារាងត្រួតពិនិត្យការណាត់ជួប"
        ],
        doctorTitle: "សម្រាប់វេជ្ជបណ្ឌិត",
        doctorLinks: [
            "ត្រួតពិនិត្យការណាត់ជួប",
            "ជជែក",
            "ចូលប្រព័ន្ធ",
            "ចុះឈ្មោះ",
            "ផ្ទាំងគ្រប់គ្រងរបស់វេជ្ជបណ្ឌិត"
        ],
        contactTitle: "ទាក់ទង",
        contactAddress: "<strong>អាសយដ្ឋាន:</strong><br>ក្រសួងសុខាភិបាល - មន្ទីរពេទ្យពហុជំនាញអន្តរជាតិ<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
        copyright: "© ២០២៤ KhamCare មន្ទីរពេទ្យអន្តរជាតិ។ រក្សាសិទ្ធិគ្រប់យ៉ាង។",
        privacy: "គោលការណ៍ភាពឯកជន",
        terms: "លក្ខខណ្ឌប្រើប្រាស់",
        support: "ការគាំទ្រអតិថិជន",
        statPatients: "អ្នកជម្ងឺ",
        statDoctors: "វេជ្ជបណ្ឌិត",
        statSpecialties: "ជំនាញ",
        socialTitle: "ភ្ជាប់ជាមួយយើង"
    }
  };

  function applyFooterLang(lang) {
    lang = lang === 'km' ? 'km' : 'vi';
    const L = footerI18n[lang];
    
    console.log('Applying footer language:', lang, 'Doctor title:', L.doctorTitle);

    // Update brand info
    const brandTitle = document.getElementById('footerBrandTitle');
    const brandSubtitle = document.getElementById('footerBrandSubtitle');
    const description = document.getElementById('footerDescription');
    
    if (brandTitle) brandTitle.textContent = L.brandTitle;
    if (brandSubtitle) brandSubtitle.textContent = L.brandSubtitle;
    if (description) description.textContent = L.description;

    // Update patient section
    const patientTitle = document.getElementById('footerPatientTitle');
    if (patientTitle) {
        patientTitle.innerHTML = '<i class="fas fa-user-injured"></i> ' + L.patientTitle;
    }
    
    const patientLinks = ['footerPatientLink1', 'footerPatientLink2', 'footerPatientLink3', 'footerPatientLink4', 'footerPatientLink5'];
    patientLinks.forEach((linkId, idx) => {
        const link = document.getElementById(linkId);
        if (link && L.patientLinks[idx]) {
            link.textContent = L.patientLinks[idx];
        }
    });

    // Update doctor section
    const doctorTitle = document.getElementById('footerDoctorTitle');
    if (doctorTitle) {
        doctorTitle.innerHTML = '<i class="fas fa-user-md"></i> ' + L.doctorTitle;
    }
    
    const doctorLinks = ['footerDoctorLink1', 'footerDoctorLink2', 'footerDoctorLink3', 'footerDoctorLink4', 'footerDoctorLink5'];
    doctorLinks.forEach((linkId, idx) => {
        const link = document.getElementById(linkId);
        if (link && L.doctorLinks[idx]) {
            link.textContent = L.doctorLinks[idx];
        }
    });

    // Update contact section
    const contactTitle = document.getElementById('footerContactTitle');
    const contactAddress = document.getElementById('footerContactAddress');
    
    if (contactTitle) {
        contactTitle.innerHTML = '<i class="fas fa-phone-alt"></i> ' + L.contactTitle;
    }
    if (contactAddress) {
        contactAddress.innerHTML = L.contactAddress;
    }

    // Update footer bottom
    const copyright = document.getElementById('footerCopyright');
    const privacy = document.getElementById('footerPrivacy');
    const terms = document.getElementById('footerTerms');
    const support = document.getElementById('footerSupport');
    
    if (copyright) copyright.innerHTML = L.copyright;
    if (privacy) privacy.textContent = L.privacy;
    if (terms) terms.textContent = L.terms;
    if (support) support.textContent = L.support;

    // Update stats
    const statPatients = document.getElementById('footerStatPatients');
    const statDoctors = document.getElementById('footerStatDoctors');
    const statSpecialties = document.getElementById('footerStatSpecialties');
    
    if (statPatients) statPatients.textContent = L.statPatients;
    if (statDoctors) statDoctors.textContent = L.statDoctors;
    if (statSpecialties) statSpecialties.textContent = L.statSpecialties;

    // Update social title
    const socialTitle = document.getElementById('footerSocialTitle');
    if (socialTitle) socialTitle.textContent = L.socialTitle;
  }

  function applyLang(lang){
    lang = (lang === 'km') ? 'km' : 'vi';
    localStorage.setItem('kham_lang', lang);
    
    console.log('🔄 Applying language:', lang);
    const L = window.dict[lang];
    
    if (!L) {
      console.error('❌ Language data not found for:', lang);
      console.error('Available languages:', Object.keys(window.dict || {}));
      return;
    }
    
    console.log('✅ Language data loaded:', Object.keys(L).length, 'keys');

    // header / nav
    const navDoctors = document.getElementById('navDoctors');
    const navSpecialties = document.getElementById('navSpecialties');
    const navConsultation = document.getElementById('navConsultation');
    const navGuides = document.getElementById('navGuides');
    const btnAccount = document.getElementById('btnAccount');
    
    if (navDoctors) navDoctors.textContent = L.navDoctors;
    if (navSpecialties) navSpecialties.textContent = L.navSpecialties;
    if (navConsultation) navConsultation.textContent = L.navConsultation;
    if (navGuides) navGuides.textContent = L.navGuides;
    if (btnAccount) btnAccount.textContent = L.account;
    
    console.log('📝 Navigation updated:', {
      navDoctors: !!navDoctors,
      navSpecialties: !!navSpecialties,
      navConsultation: !!navConsultation,
      navGuides: !!navGuides,
      btnAccount: !!btnAccount
    });
    
    // consultation title
    const consultationTitle = document.getElementById('consultationTitle');
    if (consultationTitle) consultationTitle.textContent = L.consultationTitle;

    // hero
    const hero = document.querySelector('.hero-content h1');
    if (hero) hero.textContent = L.heroTitle;
    const search = document.getElementById('searchInput');
    if (search) search.placeholder = L.searchPlaceholder;
    const btnSearch = document.getElementById('btnSearch');
    if (btnSearch) btnSearch.innerHTML = L.searchBtn;

    // Main section titles
    const specialtiesTitle = document.getElementById('specialtiesTitle');
    if (specialtiesTitle) specialtiesTitle.textContent = L.specialtiesTitle;
    
    const medicalSpecialtiesTitle = document.getElementById('medicalSpecialtiesTitle');
    if (medicalSpecialtiesTitle) medicalSpecialtiesTitle.textContent = L.medicalSpecialtiesTitle;
    
    const medicalSpecialtiesDesc = document.getElementById('medicalSpecialtiesDesc');
    if (medicalSpecialtiesDesc) medicalSpecialtiesDesc.textContent = L.medicalSpecialtiesDesc;
    
    // Featured specialties (section 1) - 6 chuyên khoa nổi bật
    const specialtyTimMach = document.getElementById('specialty-tim-mach');
    if (specialtyTimMach) specialtyTimMach.textContent = L.specialtyTimMach;
    
    const specialtyThanKinhFeatured = document.getElementById('specialty-than-kinh');
    if (specialtyThanKinhFeatured) specialtyThanKinhFeatured.textContent = L.specialtyThanKinh;
    
    const specialtySanPhuKhoaFeatured = document.getElementById('specialty-san-phu-khoa');
    if (specialtySanPhuKhoaFeatured) specialtySanPhuKhoaFeatured.textContent = L.specialtySanPhuKhoa;
    
    const specialtyDaLieuFeatured = document.getElementById('specialty-da-lieu');
    if (specialtyDaLieuFeatured) specialtyDaLieuFeatured.textContent = L.specialtyDaLieu;
    
    const specialtyNhiKhoa = document.getElementById('specialty-nhi-khoa');
    if (specialtyNhiKhoa) specialtyNhiKhoa.textContent = L.specialtyNhiKhoa;
    
    const specialtyNoiTongQuat = document.getElementById('specialty-noi-tong-quat');
    if (specialtyNoiTongQuat) specialtyNoiTongQuat.textContent = L.specialtyNoiTongQuat;
    
    // Comprehensive medical specialties (section 2)
    const specialtyChanThuong = document.getElementById('specialty-chan-thuong');
    if (specialtyChanThuong) specialtyChanThuong.textContent = L.specialtyChanThuong;
    const specialtyChanThuongDesc = document.getElementById('specialty-chan-thuong-desc');
    if (specialtyChanThuongDesc) specialtyChanThuongDesc.textContent = L.specialtyChanThuongDesc;
    
    const specialtyDaLieu = document.getElementById('specialty-da-lieu');
    if (specialtyDaLieu) specialtyDaLieu.textContent = L.specialtyDaLieu;
    const specialtyDaLieuDesc = document.getElementById('specialty-da-lieu-desc');
    if (specialtyDaLieuDesc) specialtyDaLieuDesc.textContent = L.specialtyDaLieuDesc;
    // Comprehensive section - Da Liễu
    const compSpecialtyDaLieu = document.getElementById('comp-specialty-da-lieu');
    if (compSpecialtyDaLieu) compSpecialtyDaLieu.textContent = L.specialtyDaLieu;
    const compSpecialtyDaLieuDesc = document.getElementById('comp-specialty-da-lieu-desc');
    if (compSpecialtyDaLieuDesc) compSpecialtyDaLieuDesc.textContent = L.specialtyDaLieuDesc;
    
    const specialtyMat = document.getElementById('specialty-mat');
    if (specialtyMat) specialtyMat.textContent = L.specialtyMat;
    const specialtyMatDesc = document.getElementById('specialty-mat-desc');
    if (specialtyMatDesc) specialtyMatDesc.textContent = L.specialtyMatDesc;
    
    const specialtyNgoaiKhoa = document.getElementById('specialty-ngoai-khoa');
    if (specialtyNgoaiKhoa) specialtyNgoaiKhoa.textContent = L.specialtyNgoaiKhoa;
    const specialtyNgoaiKhoaDesc = document.getElementById('specialty-ngoai-khoa-desc');
    if (specialtyNgoaiKhoaDesc) specialtyNgoaiKhoaDesc.textContent = L.specialtyNgoaiKhoaDesc;
    
    const specialtyTaiMuiHong = document.getElementById('specialty-tai-mui-hong');
    if (specialtyTaiMuiHong) specialtyTaiMuiHong.textContent = L.specialtyTaiMuiHong;
    const specialtyTaiMuiHongDesc = document.getElementById('specialty-tai-mui-hong-desc');
    if (specialtyTaiMuiHongDesc) specialtyTaiMuiHongDesc.textContent = L.specialtyTaiMuiHongDesc;
    
    const specialtyRangHamMat = document.getElementById('specialty-rang-ham-mat');
    if (specialtyRangHamMat) specialtyRangHamMat.textContent = L.specialtyRangHamMat;
    const specialtyRangHamMatDesc = document.getElementById('specialty-rang-ham-mat-desc');
    if (specialtyRangHamMatDesc) specialtyRangHamMatDesc.textContent = L.specialtyRangHamMatDesc;
    
    const specialtyThanKinh = document.getElementById('specialty-than-kinh');
    if (specialtyThanKinh) specialtyThanKinh.textContent = L.specialtyThanKinh;
    const specialtyThanKinhDesc = document.getElementById('specialty-than-kinh-desc');
    if (specialtyThanKinhDesc) specialtyThanKinhDesc.textContent = L.specialtyThanKinhDesc;
    // Comprehensive section - Thần Kinh
    const compSpecialtyThanKinh = document.getElementById('comp-specialty-than-kinh');
    if (compSpecialtyThanKinh) compSpecialtyThanKinh.textContent = L.specialtyThanKinh;
    const compSpecialtyThanKinhDesc = document.getElementById('comp-specialty-than-kinh-desc');
    if (compSpecialtyThanKinhDesc) compSpecialtyThanKinhDesc.textContent = L.specialtyThanKinhDesc;
    
    const specialtySanPhuKhoa = document.getElementById('specialty-san-phu-khoa');
    if (specialtySanPhuKhoa) specialtySanPhuKhoa.textContent = L.specialtySanPhuKhoa;
    const specialtySanPhuKhoaDesc = document.getElementById('specialty-san-phu-khoa-desc');
    if (specialtySanPhuKhoaDesc) specialtySanPhuKhoaDesc.textContent = L.specialtySanPhuKhoaDesc;
    // Comprehensive section - Sản Phụ Khoa
    const compSpecialtySanPhuKhoa = document.getElementById('comp-specialty-san-phu-khoa');
    if (compSpecialtySanPhuKhoa) compSpecialtySanPhuKhoa.textContent = L.specialtySanPhuKhoa;
    const compSpecialtySanPhuKhoaDesc = document.getElementById('comp-specialty-san-phu-khoa-desc');
    if (compSpecialtySanPhuKhoaDesc) compSpecialtySanPhuKhoaDesc.textContent = L.specialtySanPhuKhoaDesc;
    
    const specialtyTieuHoa = document.getElementById('specialty-tieu-hoa');
    if (specialtyTieuHoa) specialtyTieuHoa.textContent = L.specialtyTieuHoa;
    const specialtyTieuHoaDesc = document.getElementById('specialty-tieu-hoa-desc');
    if (specialtyTieuHoaDesc) specialtyTieuHoaDesc.textContent = L.specialtyTieuHoaDesc;
    
    const specialtyHoHap = document.getElementById('specialty-ho-hap');
    if (specialtyHoHap) specialtyHoHap.textContent = L.specialtyHoHap;
    const specialtyHoHapDesc = document.getElementById('specialty-ho-hap-desc');
    if (specialtyHoHapDesc) specialtyHoHapDesc.textContent = L.specialtyHoHapDesc;
    
    const specialtyNoiTiet = document.getElementById('specialty-noi-tiet');
    if (specialtyNoiTiet) specialtyNoiTiet.textContent = L.specialtyNoiTiet;
    const specialtyNoiTietDesc = document.getElementById('specialty-noi-tiet-desc');
    if (specialtyNoiTietDesc) specialtyNoiTietDesc.textContent = L.specialtyNoiTietDesc;
    
    // Doctor counts
    for (let i = 1; i <= 11; i++) {
      const doctorCount = document.getElementById(`doctors-count-${i}`);
      if (doctorCount && L[`doctorsCount${i}`]) {
        doctorCount.textContent = L[`doctorsCount${i}`];
      }
    }
    
    // View all button
    const viewAllSpecialties = document.getElementById('viewAllSpecialties');
    if (viewAllSpecialties) viewAllSpecialties.textContent = L.viewAllSpecialties;
    
    // doctor team title
    const doctorTeamTitle = document.getElementById('doctorTeamTitle');
    if (doctorTeamTitle) doctorTeamTitle.textContent = L.doctorTeamTitle;
    const doctorTeamDesc = document.getElementById('doctorTeamDesc');
    if (doctorTeamDesc) doctorTeamDesc.textContent = L.doctorTeamDesc;
    
    const promoTitle = document.querySelector('.promo-text span');
    const promoDesc = document.querySelector('.promo-text p');
    if (promoTitle) promoTitle.textContent = L.promoTitle;
    if (promoDesc) promoDesc.textContent = L.promoDesc;

    // facilities
    const fTitle = document.querySelector('.facilities-section .section-title');
    const fSub = document.querySelector('.facilities-section .section-subtitle');
    if (fTitle) fTitle.textContent = L.facilitiesTitle;
    if (fSub) fSub.textContent = L.facilitiesSubtitle;
    document.querySelectorAll('.facilities-grid .facility-item h3').forEach((h3, i) => {
      if (L.facilityItems[i]) h3.textContent = L.facilityItems[i];
    });

    // footer - call the dedicated footer translation function
    if (typeof window.applyFooterLang === 'function') {
        window.applyFooterLang(lang);
    }

    // Update dropdown titles
    const ddDoctorsTitle = document.getElementById('ddDoctorsTitle');
    const ddSpecialtiesTitle = document.getElementById('ddSpecialtiesTitle');
    const ddGuidesTitle = document.getElementById('ddGuidesTitle');
    
    if (ddDoctorsTitle) ddDoctorsTitle.textContent = L.ddDoctorsTitle;
    if (ddSpecialtiesTitle) ddSpecialtiesTitle.textContent = L.ddSpecialtiesTitle;
    if (ddGuidesTitle) ddGuidesTitle.textContent = L.ddGuidesTitle;

    // Update dropdown content
    const feeLabel = document.getElementById('feeLabel');
    const currencyLabel = document.getElementById('currencyLabel');
    const noDoctorsData = document.getElementById('noDoctorsData');
    const noSpecialtiesData = document.getElementById('noSpecialtiesData');
    
    if (feeLabel) feeLabel.textContent = L.feeLabel;
    if (currencyLabel) currencyLabel.textContent = L.currencyLabel;
    if (noDoctorsData) noDoctorsData.textContent = L.noDoctorsData;
    if (noSpecialtiesData) noSpecialtiesData.textContent = L.noSpecialtiesData;

    // Update guide cards
    const guideCardiac = document.getElementById('guideCardiac');
    const guideNeuro = document.getElementById('guideNeuro');
    const guideMaternal = document.getElementById('guideMaternal');
    
    if (guideCardiac) guideCardiac.textContent = L.guideCardiac;
    if (guideNeuro) guideNeuro.textContent = L.guideNeuro;
    if (guideMaternal) guideMaternal.textContent = L.guideMaternal;

    // Update search date options
    const optionToday = document.getElementById('optionToday');
    const optionTomorrow = document.getElementById('optionTomorrow');
    
    if (optionToday) optionToday.textContent = L.searchDateOptions.today;
    if (optionTomorrow) optionTomorrow.textContent = L.searchDateOptions.tomorrow;

    // Update doctor team description
    const doctorTeamDesc = document.getElementById('doctorTeamDesc');
    if (doctorTeamDesc) doctorTeamDesc.textContent = L.doctorTeamDesc;

    // Update doctor action buttons
    const btnProfileTexts = document.querySelectorAll('.btn-profile-text');
    const btnBookTexts = document.querySelectorAll('.btn-book-text');
    
    btnProfileTexts.forEach(btn => {
        if (btn) btn.textContent = L.btnProfileText;
    });
    
    btnBookTexts.forEach(btn => {
        if (btn) btn.textContent = L.btnBookText;
    });

    // Update reviews text
    const reviewsTexts = document.querySelectorAll('.reviews-text');
    reviewsTexts.forEach(text => {
        if (text) text.textContent = L.reviewsText;
    });

    // account popover placeholders
    const nameEl = document.getElementById('name');
    const usernameEl = document.getElementById('username');
    const emailEl = document.getElementById('email');
    if (nameEl) nameEl.placeholder = L.account_name_placeholder;
    if (usernameEl) usernameEl.placeholder = L.account_username_placeholder;
    if (emailEl) emailEl.placeholder = L.account_email_placeholder;

    // chat
    const chatInput = document.getElementById('chatInput');
    if (chatInput) chatInput.placeholder = L.chat_placeholder;
    const firstMsg = document.querySelector('#chatContent .doctor-message');
    if (firstMsg) firstMsg.textContent = L.chat_start_msg;

    // ensure langSelect shows current
    const sel = document.getElementById('langSelect');
    if (sel) sel.value = lang;

    // re-render doctors (render uses __doctorsData or doctors)
    if (typeof render === 'function') {
      try { render(window.__doctorsData || doctors); } catch (e) { /* ignore */ }
    }

    // Update date selection options
    const dateSelect = document.getElementById('searchDate');
    if (dateSelect) {
        dateSelect.innerHTML = `
            <option value="today">${L.searchDateOptions.today}</option>
            <option value="tomorrow">${L.searchDateOptions.tomorrow}</option>
        `;
    }
  }

  // init on load
  // Modern Language Switcher JavaScript - Updated for new design
  function initModernLangSwitcher() {
    // This function is now handled by the main language switcher code above
    // Keeping for compatibility
    const langToggle = document.getElementById('langToggle');
    const langDropdown = document.getElementById('langDropdown');
    
    if (!langToggle || !langDropdown) {
      return;
    }
    
    // Set initial language display
    const savedLang = localStorage.getItem('kham_lang') || 'vi';
    const currentLang = document.getElementById('currentLang');
    if (currentLang) {
      currentLang.textContent = savedLang === 'vi' ? 'VI' : 'KM';
    }
    
    // Update active state
    const savedOption = document.querySelector(`.lang-option[data-lang="${savedLang}"]`);
    if (savedOption) {
      document.querySelectorAll('.lang-option').forEach(opt => opt.classList.remove('active'));
      savedOption.classList.add('active');
    }
  }

  // Simple Direct Footer Translation Function
  function translateFooterDirect(lang) {
    console.log('Direct footer translation for:', lang);
    
    // Simple translation data
    const footerTexts = {
      vi: {
        patient: 'Dành Cho Bệnh Nhân',
        doctor: 'Dành Cho Bác Sĩ',
        contact: 'Liên Hệ'
      },
      km: {
        patient: 'សម្រាប់អ្នកជម្ងឺ',
        doctor: 'សម្រាប់វេជ្ជបណ្ឌិត',
        contact: 'ទាក់ទង'
      }
    };
    
    const texts = footerTexts[lang] || footerTexts['vi'];
    
    // Update footer titles directly
    const patientTitle = document.getElementById('footerPatientTitle');
    const doctorTitle = document.getElementById('footerDoctorTitle');
    const contactTitle = document.getElementById('footerContactTitle');
    
    if (patientTitle) {
      patientTitle.innerHTML = '<i class="fas fa-user-injured"></i> ' + texts.patient;
      console.log('Updated patient title to:', texts.patient);
    }
    
    if (doctorTitle) {
      doctorTitle.innerHTML = '<i class="fas fa-user-md"></i> ' + texts.doctor;
      console.log('Updated doctor title to:', texts.doctor);
    }
    
    if (contactTitle) {
      contactTitle.innerHTML = '<i class="fas fa-phone-alt"></i> ' + texts.contact;
      console.log('Updated contact title to:', texts.contact);
    }
  }

  // Backup language change listener for footer
  window.addEventListener('storage', function(e) {
    if (e.key === 'kham_lang') {
      const newLang = e.newValue || 'vi';
      console.log('Storage change detected, translating footer to:', newLang);
      translateFooterDirect(newLang);
    }
  });

  // Debug: Add manual test buttons (temporary)
  window.testFooterTranslation = function() {
    console.log('Testing footer translation...');
    console.log('Current language:', localStorage.getItem('kham_lang'));
    translateFooterDirect('km'); // Force Khmer
    setTimeout(() => {
      translateFooterDirect('vi'); // Then Vietnamese
    }, 2000);
  };

  // Debug: Add keyboard shortcut for testing
  document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'f') {
      e.preventDefault();
      console.log('Manual footer translation test triggered');
      const currentLang = localStorage.getItem('kham_lang') || 'vi';
      const newLang = currentLang === 'vi' ? 'km' : 'vi';
      translateFooterDirect(newLang);
      localStorage.setItem('kham_lang', newLang);
    }
  });

  // Initialize language system when page loads
  document.addEventListener('DOMContentLoaded', function () {
    const savedLang = localStorage.getItem('kham_lang') || 'vi';
    
    console.log('🚀 Initializing language system with:', savedLang);

    // Apply saved language immediately
    if (typeof applyLang === 'function') {
      applyLang(savedLang);
    }
    
    // Apply footer translation
    setTimeout(() => {
        if (typeof translateFooterDirect === 'function') {
          translateFooterDirect(savedLang);
        }
    }, 100);

    // Lắng nghe sự thay đổi ngôn ngữ từ dropdown
    if (langSelect) {
        langSelect.value = savedLang; // Đặt giá trị mặc định
        
        // Thêm hiệu ứng visual khi thay đổi ngôn ngữ
        langSelect.addEventListener('change', function () {
            const selectedLang = this.value;
            
            // Thêm class active để hiệu ứng
            if (langSwitcher) {
                langSwitcher.classList.add('active');
                setTimeout(() => {
                    langSwitcher.classList.remove('active');
                }, 1000);
            }
            
            // Hiệu ứng loading nhẹ
            this.style.opacity = '0.7';
            this.disabled = true;
            
            setTimeout(() => {
                localStorage.setItem('kham_lang', selectedLang);
                applyLang(selectedLang);
                // Also call applyLanguage for complete translation
                if (typeof applyLanguage === 'function') {
                    applyLanguage(selectedLang);
                }
                this.style.opacity = '1';
                this.disabled = false;
                
                // Hiển thị thông báo nhẹ
                showLanguageNotification(selectedLang);
            }, 300);
        });
        
        // Thêm hiệu ứng hover cho select
        langSelect.addEventListener('mouseenter', function() {
            if (langSwitcher) {
                langSwitcher.style.transform = 'translateY(-2px) scale(1.05)';
            }
        });
        
        langSelect.addEventListener('mouseleave', function() {
            if (langSwitcher) {
                langSwitcher.style.transform = 'translateY(0) scale(1)';
            }
        });
    }
});

// Hàm hiển thị thông báo thay đổi ngôn ngữ
function showLanguageNotification(lang) {
    const messages = {
        'vi': '🇻🇳 Đã chuyển sang Tiếng Việt',
        'km': '🇰🇭 បានប្តូរទៅភាសាខ្មែរ'
    };
    
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        z-index: 10000;
        transform: translateX(100%);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
    `;
    
    notification.textContent = messages[lang] || messages['vi'];
    document.body.appendChild(notification);
    
    // Animation slide in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Animation slide out và remove
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 2500);
}
  // listener select change -> reload texts immediately
  const langSelect = document.getElementById('langSelect');
  if (langSelect) {
    langSelect.addEventListener('change', function(){
      applyLang(this.value);
    });
  }

  // allow other tabs to sync
  window.addEventListener('storage', function(e){
    if (e.key === 'kham_lang') applyLang(e.newValue || 'vi');
  });

// Make functions globally accessible
window.applyLang = applyLang;
window.showLanguageNotification = showLanguageNotification;
window.initModernLangSwitcher = initModernLangSwitcher;
</script>
    <!-- Dropdowns dưới nav -->
    <div id="navDropdownContainer" class="container" style="position: relative;">
      <div id="ddDoctors" class="nav-dd">
        <div class="dd-title" id="ddDoctorsTitle">Bác sĩ nổi bật</div>
        <div class="dd-grid">
          <?php if (!empty($topDoctors)) { foreach($topDoctors as $d) { ?>
            <a href="#" class="dd-card">
              <span class="dd-emoji" style="font-size: 1.5rem;">👨‍⚕️</span>
              <div class="dd-text">
                <span class="dd-name"><?php echo htmlspecialchars($d['full_name']); ?></span>
                <span class="dd-sub"><?php echo htmlspecialchars($d['specialty_name'] ?? ''); ?></span>
                <span class="dd-chip"><span id="feeLabel">Phí:</span> <?php echo number_format((float)($d['consultation_fee'] ?? 0)); ?> <span id="currencyLabel">VNĐ</span></span>
              </div>
            </a>
          <?php } } else { ?>
            <div style="color:#64748b;" id="noDoctorsData">Chưa có dữ liệu bác sĩ.</div>
          <?php } ?>
        </div>
      </div>
      <div id="ddSpecialties" class="nav-dd">
        <div class="dd-title" id="ddSpecialtiesTitle">Chuyên khoa</div>
        <div class="dd-grid">
          <?php if (!empty($specialtiesList)) { foreach($specialtiesList as $s) { ?>
            <a href="#" class="dd-card">
              <i class="<?php echo htmlspecialchars($s['icon'] ?? 'fas fa-stethoscope'); ?>" style="color:<?php echo htmlspecialchars($s['color'] ?? '#2563eb'); ?>"></i>
              <div class="dd-text">
                <span class="dd-name"><?php echo htmlspecialchars($s['name']); ?></span>
                <span class="dd-sub"><?php echo htmlspecialchars($s['description'] ?? ''); ?></span>
              </div>
            </a>
          <?php } } else { ?>
            <div style="color:#64748b;" id="noSpecialtiesData">Chưa có dữ liệu chuyên khoa.</div>
          <?php } ?>
        </div>
      </div>
      <div id="ddGuides" class="nav-dd">
        <div class="dd-title" id="ddGuidesTitle">Cẩm nang Sức khỏe</div>
        <div class="dd-grid">
          <a href="#" class="dd-card"><span class="dd-emoji" style="font-size: 1.5rem;">❤️</span><span class="dd-name" id="guideCardiac">Dinh dưỡng tim mạch</span></a>
          <a href="#" class="dd-card"><span class="dd-emoji" style="font-size: 1.5rem;">🧠</span><span class="dd-name" id="guideNeuro">Sức khỏe thần kinh</span></a>
          <a href="#" class="dd-card"><span class="dd-emoji" style="font-size: 1.5rem;">🤰</span><span class="dd-name" id="guideMaternal">Sức khỏe sản phụ</span></a>
        </div>
      </div>
    </div>

    <section class="specialties-section">
        <div class="container">
            <h2 id="specialtiesTitle">Chuyên khoa Nổi Bật</h2>
            <div class="specialties-grid">
                <div class="specialty-item featured-specialty" data-specialty="Tim Mạch" style="cursor: pointer;">
                    <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
                        <i class="fas fa-heartbeat" style="font-size: 32px; color: white;"></i>
                    </div>
                    <span id="specialty-tim-mach">Tim Mạch</span>
                </div>
                <div class="specialty-item featured-specialty" data-specialty="Thần Kinh" style="cursor: pointer;">
                    <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
                        <i class="fas fa-brain" style="font-size: 32px; color: white;"></i>
                    </div>
                    <span id="specialty-than-kinh">Thần Kinh</span>
                </div>
                <div class="specialty-item featured-specialty" data-specialty="Sản Phụ Khoa" style="cursor: pointer;">
                    <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
                        <i class="fas fa-baby" style="font-size: 32px; color: white;"></i>
                    </div>
                    <span id="specialty-san-phu-khoa">Sản Phụ Khoa</span>
                </div>
                <div class="specialty-item featured-specialty" data-specialty="Da Liễu" style="cursor: pointer;">
                    <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
                        <i class="fas fa-hand-sparkles" style="font-size: 32px; color: white;"></i>
                    </div>
                    <span id="specialty-da-lieu">Da Liễu</span>
                </div>
                <div class="specialty-item featured-specialty" data-specialty="Nhi Khoa" style="cursor: pointer;">
                    <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
                        <i class="fas fa-child" style="font-size: 32px; color: white;"></i>
                    </div>
                    <span id="specialty-nhi-khoa">Nhi Khoa</span>
                </div>
                <div class="specialty-item featured-specialty" data-specialty="Nội Tổng Quát" style="cursor: pointer;">
                    <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
                        <i class="fas fa-stethoscope" style="font-size: 32px; color: white;"></i>
                    </div>
                    <span id="specialty-noi-tong-quat">Nội Tổng Quát</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Script xử lý click chuyên khoa nổi bật -->
    <script>
    (function() {
        // Đợi DOM load xong
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFeaturedSpecialties);
        } else {
            initFeaturedSpecialties();
        }
        
        function initFeaturedSpecialties() {
            console.log('🔧 Initializing featured specialties click handler...');
            
            var featuredItems = document.querySelectorAll('.featured-specialty');
            console.log('Found', featuredItems.length, 'featured specialty items');
            
            featuredItems.forEach(function(item, index) {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var specialty = this.getAttribute('data-specialty');
                    console.log('🎯 Clicked featured specialty:', specialty);
                    
                    // Scroll đến phần bác sĩ
                    var doctorSection = document.querySelector('.doctor-team-section');
                    if (doctorSection) {
                        doctorSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        console.log('📜 Scrolling to doctor section...');
                    }
                    
                    // Filter bác sĩ sau khi scroll
                    setTimeout(function() {
                        filterDoctorsBySpecialtyName(specialty);
                    }, 400);
                });
                console.log('✅ Added click handler to:', item.getAttribute('data-specialty'));
            });
        }
        
        // Hàm filter bác sĩ
        window.filterDoctorsBySpecialtyName = function(specialty) {
            console.log('🔍 Filtering doctors by:', specialty);
            
            var tabs = document.querySelectorAll('.specialty-tab');
            var doctorCards = document.querySelectorAll('.doctor-card');
            var featuredItems = document.querySelectorAll('.featured-specialty');
            
            // Update active tab
            tabs.forEach(function(tab) {
                tab.classList.remove('active');
                if (tab.getAttribute('data-specialty') === specialty) {
                    tab.classList.add('active');
                    console.log('✅ Activated tab:', specialty);
                }
            });
            
            // Update selected featured specialty
            featuredItems.forEach(function(item) {
                item.classList.remove('selected');
                if (item.getAttribute('data-specialty') === specialty) {
                    item.classList.add('selected');
                }
            });
            
            // Filter doctor cards
            var visibleCount = 0;
            var specialtyCount = 0;
            var maxDoctors = 3;
            
            doctorCards.forEach(function(card) {
                var cardSpecialty = card.getAttribute('data-specialty');
                
                if (cardSpecialty === specialty) {
                    if (specialtyCount < maxDoctors) {
                        card.style.display = 'flex';
                        visibleCount++;
                        specialtyCount++;
                        console.log('✅ Showing doctor:', card.querySelector('h4')?.textContent);
                    } else {
                        card.style.display = 'none';
                    }
                } else {
                    card.style.display = 'none';
                }
            });
            
            console.log('📊 Total visible:', visibleCount, 'doctors');
        };
    })();
    </script>

    <!-- Chuyên Khoa Y Tế Toàn Diện -->
    <section class="comprehensive-medical-section">
        <div class="container">
            <div class="section-header">
                <h2 id="medicalSpecialtiesTitle">Chuyên Khoa Y Tế Toàn Diện</h2>
                <div class="section-underline"></div>
                <p class="section-description" id="medicalSpecialtiesDesc">Hệ thống chăm sóc sức khỏe đa chuyên khoa với đội ngũ bác sĩ giàu kinh nghiệm</p>
            </div>
            
            <div class="comprehensive-grid">
                <div class="comprehensive-item" data-specialty="Chấn Thương Chỉnh Hình" onclick="window.location.href='TimKiemBS.php?specialty=Chấn Thương Chỉnh Hình'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-bone" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="specialty-chan-thuong">Chấn Thương Chỉnh Hình</h4>
                        <p id="specialty-chan-thuong-desc">Điều trị các bệnh lý về xương khớp, chấn thương thể thao</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-1">5+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.8/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Da Liễu" onclick="window.location.href='TimKiemBS.php?specialty=Da Liễu'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-hand-sparkles" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="comp-specialty-da-lieu">Da Liễu</h4>
                        <p id="comp-specialty-da-lieu-desc">Chăm sóc và điều trị các bệnh lý về da, thẩm mỹ da</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-2">4+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.9/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Mắt" onclick="window.location.href='TimKiemBS.php?specialty=Mắt'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-eye" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="specialty-mat">Mắt</h4>
                        <p id="specialty-mat-desc">Khám và điều trị các bệnh lý về mắt, phẫu thuật mắt</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-3">3+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.7/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Ngoại Khoa" onclick="window.location.href='TimKiemBS.php?specialty=Ngoại Khoa'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-user-md" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="specialty-ngoai-khoa">Ngoại Khoa</h4>
                        <p id="specialty-ngoai-khoa-desc">Phẫu thuật các bệnh lý ngoại khoa, can thiệp tối thiểu</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-4">6+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.8/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Tai Mũi Họng" onclick="window.location.href='TimKiemBS.php?specialty=Tai Mũi Họng'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-head-side-mask" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="specialty-tai-mui-hong">Tai Mũi Họng</h4>
                        <p id="specialty-tai-mui-hong-desc">Điều trị các bệnh lý về tai, mũi, họng và đầu cổ</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-5">4+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.6/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Răng Hàm Mặt" onclick="window.location.href='TimKiemBS.php?specialty=Răng Hàm Mặt'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-tooth" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="specialty-rang-ham-mat">Răng Hàm Mặt</h4>
                        <p id="specialty-rang-ham-mat-desc">Chăm sóc sức khỏe răng miệng, phẫu thuật hàm mặt</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-6">5+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.9/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Thần Kinh" onclick="window.location.href='TimKiemBS.php?specialty=Thần Kinh'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-brain" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="comp-specialty-than-kinh">Thần Kinh</h4>
                        <p id="comp-specialty-than-kinh-desc">Điều trị các bệnh lý thần kinh, đột quỵ, động kinh</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-7">4+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.8/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Sản Phụ Khoa" onclick="window.location.href='TimKiemBS.php?specialty=Sản Phụ Khoa'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-baby" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="comp-specialty-san-phu-khoa">Sản Phụ Khoa</h4>
                        <p id="comp-specialty-san-phu-khoa-desc">Chăm sóc sức khỏe phụ nữ, thai sản, sinh nở</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-8">6+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.9/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Tiêu Hóa" onclick="window.location.href='TimKiemBS.php?specialty=Tiêu Hóa'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-stomach" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="specialty-tieu-hoa">Tiêu Hóa</h4>
                        <p id="specialty-tieu-hoa-desc">Điều trị các bệnh lý về dạ dày, ruột, gan mật</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-9">5+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.7/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Hô Hấp" onclick="window.location.href='TimKiemBS.php?specialty=Hô Hấp'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-lungs" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="specialty-ho-hap">Hô Hấp</h4>
                        <p id="specialty-ho-hap-desc">Điều trị các bệnh lý về phổi, đường hô hấp</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-10">4+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.6/5</span>
                        </div>
                    </div>
                </div>

                <div class="comprehensive-item" data-specialty="Nội Tiết" onclick="window.location.href='TimKiemBS.php?specialty=Nội Tiết'" style="cursor: pointer;">
                    <div class="comprehensive-icon">
                        <i class="fas fa-dna" style="font-size: 32px; color: white;"></i>
                    </div>
                    <div class="comprehensive-content">
                        <h4 id="specialty-noi-tiet">Nội Tiết</h4>
                        <p id="specialty-noi-tiet-desc">Điều trị tiểu đường, tuyến giáp, rối loạn hormone</p>
                        <div class="comprehensive-stats">
                            <span><i class="fas fa-user-md"></i> <span id="doctors-count-11">3+ Bác sĩ</span></span>
                            <span><i class="fas fa-star"></i> 4.8/5</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="comprehensive-cta">
                <button class="btn-view-all" onclick="window.location.href='ChuyenKhoa.php'">
                    <i class="fas fa-th-large"></i>
                    <span id="viewAllSpecialties">Xem Tất Cả Chuyên Khoa</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Phần tìm kiếm bác sĩ -->
    <section class="doctor-search-section">
        <div class="container">
            <div class="search-banner">
                <h2 id="doctorTeamTitle">Đội Ngũ Bác Sĩ Chuyên Khoa</h2>
                <p id="doctorTeamDesc">Khamcare quy tụ đội ngũ chuyên gia, bác sĩ, dược sĩ và điều dưỡng được đào tạo bài bản đến chuyên sâu tại Việt Nam và nhiều nước có y học phát triển.</p>
            </div>
            
            <!-- Specialty Tabs -->
            <div class="specialty-tabs">
                <button class="specialty-tab active" data-specialty="all">
                    <i class="fas fa-user-md"></i> <span id="filterAllDoctors">Tất Cả Bác Sĩ</span>
                </button>
                <button class="specialty-tab" data-specialty="Tim Mạch">
                    <i class="fas fa-heartbeat"></i> <span id="filterTimMach">Tim Mạch</span>
                </button>
                <button class="specialty-tab" data-specialty="Thần Kinh">
                    <i class="fas fa-brain"></i> <span id="filterThanKinh">Thần Kinh</span>
                </button>
                <button class="specialty-tab" data-specialty="Sản Phụ Khoa">
                    <i class="fas fa-baby"></i> <span id="filterSanPhuKhoa">Sản Phụ Khoa</span>
                </button>
                <button class="specialty-tab" data-specialty="Da Liễu">
                    <i class="fas fa-hand-sparkles"></i> <span id="filterDaLieu">Da Liễu</span>
                </button>
                <button class="specialty-tab" data-specialty="Nhi Khoa">
                    <i class="fas fa-child"></i> <span id="filterNhiKhoa">Nhi Khoa</span>
                </button>
                <button class="specialty-tab" data-specialty="Nội Tổng Quát">
                    <i class="fas fa-stethoscope"></i> <span id="filterNoiTongQuat">Nội Tổng Quát</span>
                </button>
            </div>
            
            <div class="search-layout">
            
            <div class="doctors-grid" id="doctorsGrid">
                <?php foreach ($topDoctors as $doctor): ?>
                    <div class="doctor-card" data-specialty="<?= htmlspecialchars($doctor['specialty_name'] ?? '') ?>">
                        <div class="doctor-avatar" onclick="window.open('view_doctor.php?doctor_id=<?= $doctor['id'] ?>', '_blank')" style="cursor: pointer;">
<?php
                            // Sử dụng imageMap cho ảnh bác sĩ
                            $doctorImage = $imageMap[$doctor['id']] ?? $doctor['image'] ?? 'image/default-doctor.svg';
                            
                            // Lấy chữ cái đầu của tên (cho fallback)
                            $fullName = $doctor['full_name'] ?? '';
                            $nameParts = explode(' ', $fullName);
                            $firstLetter = strtoupper(substr(end($nameParts), 0, 1)) ?: 'B';
                            if (strpos($fullName, 'Lê Thị Mai') !== false) {
                                $firstLetter = 'M';
                            }
                            
                            // Tạo màu gradient dựa trên ID (cho fallback)
                            $colors = [
                                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                                'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                                'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                                'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
                                'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
                                'linear-gradient(135deg, #ff8a80 0%, #ea4c89 100%)',
                                'linear-gradient(135deg, #8fd3f4 0%, #84fab0 100%)',
                                'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)',
                                'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)',
                                'linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%)',
                                'linear-gradient(135deg, #e0c3fc 0%, #9bb5ff 100%)',
                                'linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%)',
                                'linear-gradient(135deg, #74b9ff 0%, #0984e3 100%)',
                                'linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%)',
                                'linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%)'
                            ];
                            $colorIndex = ($doctor['id'] ?? 0) % count($colors);
                            $avatarColor = $colors[$colorIndex];
                            ?>
                            <img src="<?= htmlspecialchars($doctorImage) ?>?v=<?= time() ?>" 
                                alt="<?= htmlspecialchars($doctor['full_name']) ?>"
                                onload="console.log('✓ Loaded doctor image: <?= $doctorImage ?>');"
                                onerror="console.log('✗ Failed doctor image: <?= $doctorImage ?>'); this.classList.add('error'); this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                style="width: 100%; height: 100%; object-fit: cover;">
                            <div class="fallback" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: <?= $avatarColor ?>; color: white; font-size: 2rem; font-weight: bold; align-items: center; justify-content: center;">
                                <?= $firstLetter ?>
                            </div>
                        </div>
                        <div class="doctor-info">
                            <h4>BS. <?= htmlspecialchars(str_replace(['BS. ', 'Bs. ', 'BS.', 'Bs.'], '', $doctor['full_name'])) ?></h4>
                            <p class="specialty">
                                <i class="fas fa-stethoscope"></i>
                                <?= htmlspecialchars($doctor['specialty_name'] ?? '') ?>
                            </p>
                            <p class="hospital">
                                <img src="image/logo.png.png" alt="KhamCare" style="width: 16px; height: 16px; border-radius: 4px; vertical-align: middle;">
                                Bệnh viện KhamCare
                            </p>
                            <p class="experience">
                                <i class="fas fa-user-clock"></i>
                                <?php 
                                    // Lấy kinh nghiệm từ database hoặc random
                                    $experience = isset($doctor['experience_years']) ? $doctor['experience_years'] : rand(5, 15);
                                    echo $experience . ' năm kinh nghiệm';
                                ?>
                            </p>
                            <div class="rating">
                                <span class="stars">
                                    <?php 
                                        // Lấy rating từ database hoặc random
                                        $rating = isset($doctor['rating']) ? $doctor['rating'] : number_format(rand(45, 50) / 10, 1);
                                        $fullStars = floor($rating);
                                        for ($i = 0; $i < $fullStars; $i++) {
                                            echo '<i class="fas fa-star"></i>';
                                        }
                                        if ($rating - $fullStars >= 0.5) {
                                            echo '<i class="fas fa-star-half-alt"></i>';
                                        }
                                    ?>
                                </span>
                                <span class="score"><?= $rating ?>/5.0</span>
                                <span class="reviews">(<?= isset($doctor['total_reviews']) ? $doctor['total_reviews'] : rand(20, 200) ?> đánh giá)</span>
                            </div>
                            <p class="price">
                                <i class="fas fa-money-bill-wave"></i>
                                <?php 
                                    // Lấy giá từ database hoặc random
                                    if (isset($doctor['consultation_fee'])) {
                                        echo number_format($doctor['consultation_fee'], 0, ',', ',') . ' VND';
                                    } else {
                                        $prices = ['300,000', '350,000', '400,000', '450,000', '500,000'];
                                        echo $prices[array_rand($prices)] . ' VND';
                                    }
                                ?>
                            </p>
                            <span class="availability">
                                <i class="fas fa-calendar-check"></i>
                                Còn lịch trống hôm nay
                            </span>
                            <div class="doctor-actions">
                                <a href="view_doctor.php?doctor_id=<?= $doctor['id'] ?>" class="btn-profile">
                                    <i class="fas fa-user-md"></i> <span class="btn-profile-text">Xem hồ sơ</span>
                                </a>
                                <a href="DatLichK.php?doctor_id=<?= $doctor['id'] ?>" class="btn-book">
                                    <i class="fas fa-calendar-plus"></i> <span class="btn-book-text">Đặt lịch</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <script>
            // Specialty Tabs Filter
            document.addEventListener('DOMContentLoaded', function() {
                console.log('🚀 Filter script loaded!');
                
                const tabs = document.querySelectorAll('.specialty-tab');
                const doctorCards = document.querySelectorAll('.doctor-card');
                const doctorsGrid = document.getElementById('doctorsGrid');
                
                console.log('🔍 Found', tabs.length, 'filter tabs');
                console.log('🔍 Found', doctorCards.length, 'doctor cards');
                
                // Log all specialties
                doctorCards.forEach((card, index) => {
                    const specialty = card.getAttribute('data-specialty');
                    const doctorName = card.querySelector('h4')?.textContent || 'Unknown';
                    console.log(`📋 Card ${index + 1}: ${doctorName} - Specialty: "${specialty}"`);
                });
                
                // Log all filter buttons
                tabs.forEach((tab, index) => {
                    const specialty = tab.getAttribute('data-specialty');
                    console.log(`🎯 Tab ${index + 1}: "${specialty}"`);
                });
                
                tabs.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        console.log('👆 Tab clicked!');
                        
                        // Remove active class from all tabs
                        tabs.forEach(t => t.classList.remove('active'));
                        
                        // Add active class to clicked tab
                        this.classList.add('active');
                        
                        // Get selected specialty
                        const selectedSpecialty = this.getAttribute('data-specialty');
                        console.log('🎯 Selected specialty:', `"${selectedSpecialty}"`);
                        
                        let visibleCount = 0;
                        let specialtyCount = 0;
                        const maxDoctorsPerSpecialty = 3;
                        
                        // Filter doctor cards
                        doctorCards.forEach((card, index) => {
                            const cardSpecialty = card.getAttribute('data-specialty');
                            console.log(`  Checking card ${index + 1}: "${cardSpecialty}" === "${selectedSpecialty}"?`, cardSpecialty === selectedSpecialty);
                            
                            if (selectedSpecialty === 'all') {
                                card.style.display = 'flex';
                                visibleCount++;
                                console.log(`  ✅ Showing card ${index + 1} (all)`);
                            } else {
                                // So sánh chính xác tên chuyên khoa
                                if (cardSpecialty === selectedSpecialty) {
                                    // Chỉ hiện 3 bác sĩ đầu tiên của chuyên khoa
                                    if (specialtyCount < maxDoctorsPerSpecialty) {
                                        card.style.display = 'flex';
                                        visibleCount++;
                                        specialtyCount++;
                                        console.log(`  ✅ Showing card ${index + 1} (match ${specialtyCount}/3)`);
                                    } else {
                                        card.style.display = 'none';
                                        console.log(`  ❌ Hiding card ${index + 1} (limit reached)`);
                                    }
                                } else {
                                    card.style.display = 'none';
                                    console.log(`  ❌ Hiding card ${index + 1} (no match)`);
                                }
                            }
                        });
                        
                        console.log('✅ Total visible doctors:', visibleCount, '(Max 3 per specialty)');
                        
                        // Show/hide no doctors message
                        let noDocsMsg = doctorsGrid.querySelector('.no-doctors-message');
                        if (visibleCount === 0) {
                            if (!noDocsMsg) {
                                noDocsMsg = document.createElement('div');
                                noDocsMsg.className = 'no-doctors-message';
                                noDocsMsg.innerHTML = '<i class="fas fa-user-md"></i><p>Không tìm thấy bác sĩ nào cho chuyên khoa này.</p>';
                                doctorsGrid.appendChild(noDocsMsg);
                            }
                            noDocsMsg.classList.add('show');
                        } else {
                            if (noDocsMsg) {
                                noDocsMsg.classList.remove('show');
                            }
                        }
                    });
                });
            });
            
            // Hàm filter bác sĩ khi click vào chuyên khoa nổi bật
            window.filterDoctorsBySpecialty = function(specialty) {
                console.log('🎯 Filter by specialty:', specialty);
                
                // Lấy các elements cần thiết
                const tabs = document.querySelectorAll('.specialty-tab');
                const doctorCards = document.querySelectorAll('.doctor-card');
                const doctorsGrid = document.getElementById('doctorsGrid');
                const doctorSection = document.querySelector('.doctor-team-section');
                
                // Scroll đến phần bác sĩ
                if (doctorSection) {
                    doctorSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                // Cập nhật active state cho tabs
                tabs.forEach(tab => {
                    tab.classList.remove('active');
                    if (tab.getAttribute('data-specialty') === specialty) {
                        tab.classList.add('active');
                    }
                });
                
                // Filter doctor cards
                let visibleCount = 0;
                let specialtyCount = 0;
                const maxDoctorsPerSpecialty = 3;
                
                doctorCards.forEach((card) => {
                    const cardSpecialty = card.getAttribute('data-specialty');
                    
                    if (cardSpecialty === specialty) {
                        if (specialtyCount < maxDoctorsPerSpecialty) {
                            card.style.display = 'flex';
                            visibleCount++;
                            specialtyCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                console.log('✅ Visible doctors:', visibleCount);
                
                // Highlight specialty item đã chọn
                const specialtyItems = document.querySelectorAll('.specialty-item');
                specialtyItems.forEach(item => {
                    item.classList.remove('selected');
                    if (item.getAttribute('data-specialty') === specialty) {
                        item.classList.add('selected');
                    }
                });
                
                // Show message if no doctors found
                let noDocsMsg = doctorsGrid ? doctorsGrid.querySelector('.no-doctors-message') : null;
                if (visibleCount === 0 && doctorsGrid) {
                    if (!noDocsMsg) {
                        noDocsMsg = document.createElement('div');
                        noDocsMsg.className = 'no-doctors-message';
                        noDocsMsg.innerHTML = '<i class="fas fa-user-md"></i><p>Không tìm thấy bác sĩ nào cho chuyên khoa này.</p>';
                        doctorsGrid.appendChild(noDocsMsg);
                    }
                    noDocsMsg.classList.add('show');
                } else if (noDocsMsg) {
                    noDocsMsg.classList.remove('show');
                }
            };
            </script>
            
                    <script>
                         // Removed duplicate dictionary and function to avoid conflicts
                         function applySpecialtyLang(lang) {
                                lang = lang === 'km' ? 'km' : 'vi';
                                // This function is now handled by the main applyLang function

                                // Function content removed to avoid conflicts
                            }
                        </script>
                            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const savedLang = localStorage.getItem('kham_lang') || 'vi';
                applyLang(savedLang);

                const langSelect = document.getElementById('langSelect');
                if (langSelect) {
                    langSelect.value = savedLang; // Đặt giá trị mặc định
                    langSelect.addEventListener('change', function () {
                        const selectedLang = this.value;
                        localStorage.setItem('kham_lang', selectedLang);
                        applyLang(selectedLang);
                    });
                }
            });
</script>



                    <script>
                    (function(){
                      // Lấy dữ liệu bác sĩ từ PHP (đồng bộ với database)
                      <?php
                      // Mapping ảnh cho các bác sĩ
                      $imageMap = [
                          1001 => 'image/nguyenthilan.png.png',
                          1002 => 'image/tranvanhung.png.png', 
                          1003 => 'image/lethimai.png.png',
                          1004 => 'image/leminhtuan.png.png',
                          1005 => 'image/tranthihuong.png.png',
                          1006 => 'image/ngnuyenvanduc.png.webp',
                          1007 => 'image/dovanhung.png.jpg',
                          1008 => 'image/hoangminhhoang.png.png',
                          1009 => 'image/phamthilan.png.png',
                          1010 => 'image/vuthimai.png.png',
                          1011 => 'image/nguyenducminh.png.png',
                          1012 => 'image/tranvannam.png.png',
                          1013 => 'image/phamthilan.png.png',
                          1014 => 'image/lethihoa.png.png',
                          1015 => 'image/nguyenthihanh.png.png',
                          1016 => 'image/nguyenvanhoa.png.png',
                          1017 => 'image/tranthiphu.png.png',
                          1018 => 'image/leminhtam.png.png'
                      ];
                      
                      // Lấy dữ liệu từ database, nếu fail thì dùng array rỗng
                      $doctorsFromDB = [];
                      try {
                          $doctorsFromDB = array_map(function($doctor) use ($imageMap) {
                              return [
                                  'id' => $doctor['id'],
                                  'full_name' => $doctor['full_name'],
                                  'specialty' => $doctor['specialty_name'] ?? 'Tổng quát',
                                  'img' => $imageMap[$doctor['id']] ?? $doctor['image'] ?? 'image/default-doctor.png',
                                  'location' => $doctor['location'] ?? 'Hà Nội, Việt Nam',
                                  'availability' => $doctor['availability'] ?? 'Liên hệ để biết lịch khám',
                                  'fee' => $doctor['consultation_fee'] ? number_format($doctor['consultation_fee']) . ' VND' : 'Liên hệ',
                                  'rating' => $doctor['rating'] ?? 5,
                                  'reviews' => $doctor['total_reviews'] ?? 0,
                                  'bio' => $doctor['bio'] ?? 'Bác sĩ chuyên khoa với nhiều năm kinh nghiệm'
                              ];
                          }, getDoctors());
                      } catch (Exception $e) {
                          error_log("TrangChu.php - getDoctors() error: " . $e->getMessage());
                          $doctorsFromDB = [];
                      }
                      ?>
                      const doctorsFromDB = <?= json_encode($doctorsFromDB) ?>;

                      // Dữ liệu bác sĩ từ view_doctor.php - chỉ giữ lại 3 chuyên khoa chính
                      const sampleDoctors = [
                        // Tim Mạch (3 bác sĩ)
                        { id: 1010, full_name: "Vũ Thị Mai", specialty: "Tim Mạch", img: "image/vuthimai.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 8:00-17:00", fee: "500.000 VND", rating: 5, reviews: 42, bio: "Chuyên gia tim mạch, can thiệp tim mạch và điều trị bệnh lý tim bẩm sinh." },
                        { id: 1011, full_name: "Nguyễn Đức Minh", specialty: "Tim Mạch", img: "image/nguyenducminh.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 3-7: 9:00-16:00", fee: "450.000 VND", rating: 5, reviews: 38, bio: "Bác sĩ tim mạch, chẩn đoán và điều trị bệnh mạch vành, suy tim." },
                        { id: 1012, full_name: "Trần Văn Nam", specialty: "Tim Mạch", img: "image/tranvannam.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 8:30-17:30", fee: "480.000 VND", rating: 4, reviews: 32, bio: "Chuyên về can thiệp tim mạch, đặt stent và điều trị nhồi máu cơ tim." },

                        // Nhi Khoa (3 bác sĩ)
                        { id: 1013, full_name: "Phạm Thị Lan", specialty: "Nhi Khoa", img: "image/phamthilan.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 8:00-17:00", fee: "300.000 VND", rating: 5, reviews: 35, bio: "Chuyên gia nhi khoa, chăm sóc sức khỏe trẻ em từ sơ sinh đến 16 tuổi." },
                        { id: 1014, full_name: "Lê Thị Hoa", specialty: "Sản Phụ Khoa", img: "image/lethihoa.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 3-7: 9:00-16:00", fee: "320.000 VND", rating: 4, reviews: 28, bio: "Bác sĩ sản phụ khoa, chuyên về chăm sóc sức khỏe phụ nữ và thai sản." },
                        { id: 1015, full_name: "Nguyễn Thị Hạnh", specialty: "Nhi Khoa", img: "image/nguyenthihanh.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 7:30-16:30", fee: "280.000 VND", rating: 4, reviews: 24, bio: "Khám và điều trị bệnh lý nhi khoa, tư vấn dinh dưỡng cho trẻ." },

                        // Nội Tổng Quát (3 bác sĩ)
                        { id: 1016, full_name: "Nguyễn Văn Hòa", specialty: "Nội Tổng Quát", img: "image/nguyenvanhoa.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 8:00-17:00", fee: "300.000 VND", rating: 5, reviews: 40, bio: "Bác sĩ nội tổng quát, khám và điều trị các bệnh nội khoa thường gặp." },
                        { id: 1017, full_name: "Trần Thị Thu", specialty: "Nội Tổng Quát", img: "image/tranthiphu.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 3-7: 9:00-16:00", fee: "320.000 VND", rating: 4, reviews: 33, bio: "Chuyên gia nội khoa, điều trị tiểu đường, cao huyết áp và bệnh lý gan." },
                        { id: 1018, full_name: "Lê Minh Tâm", specialty: "Nội Tổng Quát", img: "image/leminhtam.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 8:30-17:30", fee: "310.000 VND", rating: 4, reviews: 29, bio: "Bác sĩ nội tổng quát, tư vấn và điều trị bệnh lý tiêu hóa, hô hấp." },

                        // Thần Kinh (3 bác sĩ)
                        { id: 1004, full_name: "Lê Minh Tuấn", specialty: "Thần Kinh", img: "image/leminhtuan.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 8:00-17:00", fee: "450.000 VND", rating: 5, reviews: 30, bio: "Chuyên gia thần kinh, điều trị đau đầu, chóng mặt và các bệnh lý thần kinh." },
                        { id: 1005, full_name: "Trần Thị Hương", specialty: "Thần Kinh", img: "image/tranthihuong.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 3-7: 9:00-16:00", fee: "420.000 VND", rating: 4, reviews: 22, bio: "Bác sĩ thần kinh, chuyên về phục hồi chức năng và điều trị bệnh lý não." },
                        { id: 1006, full_name: "Nguyễn Văn Đức", specialty: "Thần Kinh", img: "image/ngnuyenvanduc.png.webp", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 8:30-17:30", fee: "400.000 VND", rating: 4, reviews: 16, bio: "Khám và điều trị các bệnh thần kinh mạn tính, đột quỵ và Parkinson." },

                        // Sản Phụ Khoa (3 bác sĩ)
                        { id: 1001, full_name: "Nguyễn Thị Lan", specialty: "Sản Phụ Khoa", img: "image/nguyenthilan.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 8:00-17:00", fee: "400.000 VND", rating: 5, reviews: 24, bio: "Chuyên gia sản phụ khoa với 15 năm kinh nghiệm, chuyên về theo dõi thai kỳ và sinh đẻ." },
                        { id: 1002, full_name: "Trần Văn Hùng", specialty: "Sản Phụ Khoa", img: "image/tranvanhung.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 3-7: 9:00-16:00", fee: "380.000 VND", rating: 4, reviews: 18, bio: "Bác sĩ sản phụ khoa, chuyên về chăm sóc sản phụ và phẫu thuật nhỏ." },
                        { id: 1003, full_name: "Lê Thị Mai", specialty: "Sản Phụ Khoa", img: "image/lethimai.png.png", location: "Hà Nội, Việt Nam", availability: "Thứ 2-6: 7:30-16:30", fee: "350.000 VND", rating: 4, reviews: 12, bio: "Tư vấn thai kỳ, khám sản định kỳ và chăm sóc sức khỏe phụ nữ." }
                      ];

                      // Merge dữ liệu từ database và dữ liệu mẫu, loại bỏ trùng lặp
                      let doctors = [...doctorsFromDB];
                      
                      // Thêm dữ liệu mẫu chỉ khi không trùng tên
                      sampleDoctors.forEach(sampleDoc => {
                        const exists = doctors.some(doc => 
                          doc.full_name === sampleDoc.full_name && 
                          doc.specialty === sampleDoc.specialty
                        );
                        if (!exists) {
                          doctors.push(sampleDoc);
                        }
                      });
                      
                      // Loại bỏ trùng lặp hoàn toàn dựa trên tên và chuyên khoa
                      const uniqueDoctors = [];
                      const seen = new Set();
                      
                      doctors.forEach(doc => {
                        const key = `${doc.full_name}-${doc.specialty}`;
                        if (!seen.has(key)) {
                          seen.add(key);
                          uniqueDoctors.push(doc);
                        }
                      });
                      
                      doctors = uniqueDoctors;
                      
                      // Function để đảm bảo mỗi chuyên khoa có ĐÚNG 3 bác sĩ (không nhiều hơn)
                      function ensureExactlyThreeDoctors() {
                        const requiredSpecialties = ['Tim Mạch', 'Nhi Khoa', 'Nội Tổng Quát', 'Thần Kinh', 'Sản Phụ Khoa'];
                        const finalDoctors = [];
                        
                        requiredSpecialties.forEach(specialty => {
                          // Lấy đúng 3 bác sĩ đầu tiên của mỗi chuyên khoa
                          const doctorsInSpecialty = doctors.filter(d => d.specialty === specialty).slice(0, 3);
                          finalDoctors.push(...doctorsInSpecialty);
                          
                          console.log(`🏥 ${specialty}: ${doctorsInSpecialty.length} bác sĩ`);
                          doctorsInSpecialty.forEach(doc => {
                            console.log(`   - ${doc.full_name} (ID: ${doc.id})`);
                          });
                        });
                        
                        console.log(`📊 Tổng cộng: ${finalDoctors.length} bác sĩ (${finalDoctors.length/5} chuyên khoa x 3 bác sĩ)`);
                        return finalDoctors;
                      }
                      
                      // Đảm bảo mỗi chuyên khoa có ĐÚNG 3 bác sĩ
                      doctors = ensureExactlyThreeDoctors();

                      // i18n nhỏ
                      const i18n = {
                        vi: { view_profile: "Xem hồ sơ", book_now: "Đặt Ngay", search_placeholder: "Tìm kiếm bác sĩ, chuyên khoa..." },
                        km: { view_profile: "មើលប្រវត្តិ", book_now: "កក់ឥឡូវ", search_placeholder: "ស្វែងរកវេជ្ជបណ្ឌិត..." }
                      };
                      let currentLang = localStorage.getItem('kham_lang') || 'vi';
                      function __t(k){ return (i18n[currentLang] && i18n[currentLang][k]) || i18n.vi[k] || k; }
                      function applyTranslations(){
                        document.getElementById('searchInput').placeholder = __t('search_placeholder');
                      }
                      const langSelect = document.getElementById('langSelect');
                      if (langSelect) {
                        langSelect.value = currentLang;
                        langSelect.addEventListener('change', function(){ currentLang = this.value; localStorage.setItem('kham_lang', currentLang); applyTranslations(); render(window.__doctorsData || doctors.filter(d=>d.specialty.toLowerCase().includes('sản phụ khoa'))); });
                      }
                      applyTranslations();

                      // helpers
                      function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
                      function norm(s){ return (s||'').toLowerCase().trim(); }

                    // Sửa lại phần render doctor card trong hàm render():
                    function render(list){
                        const grid = document.getElementById('doctorsGrid');
                        if (!grid) {
                          console.log('❌ Grid element not found!');
                          return;
                        }
                        
                        window.__doctorsData = list;
                        
                        // Debug: log rendered doctors
                        console.log('🎨 Rendering', list.length, 'doctors');
                        console.log('📋 Doctors list:', list.map(d => `${d.full_name} (${d.specialty})`));
                        
                        if (list.length === 0) {
                          grid.innerHTML = '<div class="no-doctors">Không tìm thấy bác sĩ nào cho chuyên khoa này.</div>';
                          return;
                        }
                        
                        // Image mapping để hiển thị ảnh bác sĩ
                        const imageMap = {
                            1001: 'image/nguyenthilan.png.png',
                            1002: 'image/tranvanhung.png.png', 
                            1003: 'image/lethimai.png.png',
                            1004: 'image/leminhtuan.png.png',
                            1005: 'image/tranthihuong.png.png',
                            1006: 'image/ngnuyenvanduc.png.webp',
                            1007: 'image/dovanhung.png.jpg',
                            1008: 'image/hoangminhhoang.png.png',
                            1009: 'image/phamthilan.png.png',
                            1010: 'image/vuthimai.png.png',
                            1011: 'image/nguyenducminh.png.png',
                            1012: 'image/tranvannam.png.png',
                            1013: 'image/phamthilan.png.png',
                            1014: 'image/lethihoa.png.png',
                            1015: 'image/nguyenthihanh.png.png',
                            1016: 'image/nguyenvanhoa.png.png',
                            1017: 'image/tranthiphu.png.png',
                            1018: 'image/leminhtam.png.png'
                        };
                        
                        grid.innerHTML = list.map(d => {
                            // Lấy ảnh từ imageMap hoặc sử dụng chữ cái làm fallback
                            const doctorImage = imageMap[d.id] || d.img;
                            const fullName = d.full_name || '';
                            const nameParts = fullName.split(' ');
                            let firstLetter = nameParts[nameParts.length - 1]?.charAt(0).toUpperCase() || 'B';
                            
                            // Xử lý đặc biệt cho một số tên
                            if (fullName.includes('Lê Thị Mai')) {
                                firstLetter = 'M';
                            }
                            
                            // Tạo màu gradient dựa trên tên (cho fallback)
                            const colors = [
                                'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                                'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                                'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                                'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                                'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
                                'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)',
                                'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
                                'linear-gradient(135deg, #ff8a80 0%, #ea4c89 100%)',
                                'linear-gradient(135deg, #8fd3f4 0%, #84fab0 100%)',
                                'linear-gradient(135deg, #d299c2 0%, #fef9d7 100%)',
                                'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)',
                                'linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%)',
                                'linear-gradient(135deg, #e0c3fc 0%, #9bb5ff 100%)',
                                'linear-gradient(135deg, #ffeaa7 0%, #fab1a0 100%)',
                                'linear-gradient(135deg, #74b9ff 0%, #0984e3 100%)',
                                'linear-gradient(135deg, #fd79a8 0%, #fdcb6e 100%)',
                                'linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%)'
                            ];
                            const colorIndex = (d.id || 0) % colors.length;
                            const avatarColor = colors[colorIndex];
                            
                            return `
                            <div class="doctor-card" data-specialty="${escapeHtml(d.specialty)}" data-doctor-id="${d.id}">
                                <div class="doctor-avatar" style="position: relative; width: 80px; height: 80px; border-radius: 12px; overflow: hidden; margin: 0 auto 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2); border: 3px solid rgba(255,255,255,0.3);">
                                    ${doctorImage ? 
                                        `<img src="${escapeHtml(doctorImage)}?v=${Date.now()}" 
                                             alt="Bác sĩ ${escapeHtml(d.full_name)}" 
                                             style="width: 100%; height: 100%; object-fit: cover;"
                                             onload="console.log('✓ JS Loaded: ${doctorImage}');"
                                             onerror="console.log('✗ JS Error: ${doctorImage}'); this.style.display='none'; this.parentNode.innerHTML='<div style=\\'display: flex; width: 100%; height: 100%; background: ${avatarColor}; color: white; font-size: 2.2rem; font-weight: bold; align-items: center; justify-content: center;\\'>${firstLetter}</div>';">` :
                                        `<div style="display: flex; width: 100%; height: 100%; background: ${avatarColor}; color: white; font-size: 2.2rem; font-weight: bold; align-items: center; justify-content: center;">${firstLetter}</div>`
                                    }
                                </div>
                                <div class="doctor-info">
                                    <h4>BS. ${escapeHtml(d.full_name.replace(/^(BS\.|Bs\.|BS\s|Bs\s)/i, ''))}</h4>
                                    <p class="specialty">${escapeHtml(d.specialty)}</p>
                                    <div class="rating"><span class="stars">${'★'.repeat(d.rating || 5)}</span> <span class="review-count">${d.reviews || 0} <span class="reviews-text">đánh giá</span></span></div>
                                    <p class="location">${escapeHtml(d.location || '')}</p>
                                    <p class="availability">${escapeHtml(d.availability || '')}</p>
                                    <p class="price">${escapeHtml(d.fee || '')}</p>
                                    <div class="doctor-actions">
                                        <a class="btn-profile" href="view_doctor.php?doctor_id=${d.id}">Xem hồ sơ</a>
                                        <a class="btn-book" href="DatLichK.php?doctor_id=${d.id}">Đặt Ngay</a>               
                                    </div>
                                </div>
                            </div>
                            `;
                        }).join('');
                        
                        console.log('✅ Rendered successfully!');
                    }

                      // Debug: log doctors data
                      console.log('🔍 Doctors data loaded:', doctors);
                      console.log('🔍 Total doctors:', doctors.length);
                      
                      // Kiểm tra số lượng bác sĩ theo chuyên khoa
                      const specialtyCount = {};
                      doctors.forEach(d => {
                        specialtyCount[d.specialty] = (specialtyCount[d.specialty] || 0) + 1;
                      });
                      console.log('📊 Doctors per specialty:', specialtyCount);
                      
                      // Đảm bảo mỗi chuyên khoa có ít nhất 3 bác sĩ
                      const requiredSpecialties = ['Sản Phụ Khoa', 'Thần Kinh', 'Da Liễu', 'Tim Mạch', 'Nhi Khoa', 'Nội Tổng Quát'];
                      requiredSpecialties.forEach(specialty => {
                        const count = specialtyCount[specialty] || 0;
                        if (count < 3) {
                          console.warn(`⚠️ ${specialty} chỉ có ${count} bác sĩ (cần ít nhất 3)`);
                        } else {
                          console.log(`✅ ${specialty}: ${count} bác sĩ`);
                        }
                      });
                      
                      // initial render: default Sản Phụ Khoa
                      const defaultSpecialty = 'Sản Phụ Khoa';
                      console.log('🎯 Setting up default specialty:', defaultSpecialty);
                      console.log('📋 Available doctors for filtering:', doctors.map(d => `${d.full_name} (${d.specialty})`));
                      
                      const defaultDoctors = doctors.filter(d => norm(d.specialty) === norm(defaultSpecialty));
                      console.log('🎯 Default doctors found:', defaultDoctors.length);
                      render(defaultDoctors);
                      
                      // Set default active state - Tim Mạch
                      const defaultSpecialtyNew = 'Tim Mạch';
                      const defaultBtn = document.querySelector(`.specialty-item[data-specialty="${defaultSpecialtyNew}"]`);
                      if (defaultBtn) {
                        defaultBtn.classList.add('selected');
                        console.log('✅ Set default active specialty:', defaultSpecialtyNew);
                      } else {
                        console.warn('⚠️ Default button not found for:', defaultSpecialtyNew);
                      }
                      
                      // Hiển thị bác sĩ Tim Mạch mặc định
                      filterBy(defaultSpecialtyNew);

                      // filters
                      const topBtns = Array.from(document.querySelectorAll('.specialty-item'));
                      // const sideLinks = Array.from(document.querySelectorAll('.specialty-list a'));
                      
                      console.log('🔘 Found specialty buttons:', topBtns.length);
                      // console.log('🔗 Found side links:', sideLinks.length);
                      console.log('📋 Doctors data:', doctors.length, 'doctors loaded');
                      function filterBy(name){
                        console.log('🔍 Filtering by specialty:', name);
                        console.log('📋 Total doctors available:', doctors.length);
                        
                        if(!name){ 
                          console.log('📋 Showing all doctors');
                          render(doctors); 
                          return; 
                        }
                        
                        // Normalize specialty name for better matching
                        const normalizedName = norm(name);
                        console.log('🔍 Normalized search term:', normalizedName);
                        
                        const filtered = doctors.filter(d => {
                          const doctorSpecialty = norm(d.specialty || d.specialty_name || '');
                          const match = doctorSpecialty.includes(normalizedName) || normalizedName.includes(doctorSpecialty);
                          console.log(`🔍 Checking: ${d.full_name} (${d.specialty || d.specialty_name}) vs ${name} -> ${match ? '✅' : '❌'}`);
                          return match;
                        });
                        
                        console.log('📊 Filtered results:', filtered.length, 'doctors');
                        
                        if (filtered.length === 0) {
                          console.warn('⚠️ No doctors found for specialty:', name);
                        }
                        
                        render(filtered);
                        
                        const navElement = document.getElementById('doctorsNav');
                        if (navElement) {
                          navElement.style.display = filtered.length > 3 ? 'flex' : 'none';
                        }
                      }

                      topBtns.forEach(b=>{
                        b.addEventListener('click', function(e){
                          e.preventDefault();
                          e.stopPropagation();
                          
                          // Lấy tên chuyên khoa từ data-specialty hoặc text content
                          let name = this.getAttribute('data-specialty') || this.textContent.trim();
                          
                          // Xử lý trường hợp đặc biệt
                          if (name.includes('Tư vấn trực tuyến')) {
                            return; // Không xử lý cho nút tư vấn
                          }
                          
                          console.log('🖱️ Clicked specialty button:', name);
                          
                          // mark selected
                          topBtns.forEach(x=>x.classList.remove('active', 'selected'));
                          this.classList.add('selected');
                          
                          // Sidebar removed - no need to update sidebar links
                          
                          filterBy(name);
                        });
                      });
                      // Sidebar removed - no sidebar links to handle
                      // sideLinks.forEach(a=>{
                      //   a.addEventListener('click', function(e){
                      //     e.preventDefault();
                      //     const name = this.getAttribute('data-specialty') || this.textContent;
                      //     console.log('🖱️ Clicked sidebar link:', name);
                      //     filterBy(name);
                      //   });
                      // });
 // search suggestions
  const doctorsNames = doctors.slice(0,18).map(d=> d.full_name + ' - ' + d.specialty);
  const searchInput = document.getElementById("searchInput");
  const suggestions = document.getElementById("suggestions");
  function showSuggestions(){
    const value = (searchInput.value || '').trim().toLowerCase();
    let filtered = [];
    if (value) filtered = doctorsNames.filter(item => item.toLowerCase().includes(value));
    else filtered = doctorsNames.slice(0,6);
    if (!filtered.length){ suggestions.style.display='none'; return; }
    suggestions.innerHTML = filtered.map(item=>`<div class="suggestion-item">${escapeHtml(item)}</div>`).join('');
    suggestions.style.display = 'block';
  }
  if (searchInput) { searchInput.addEventListener('input', showSuggestions); searchInput.addEventListener('focus', showSuggestions); }
  if (suggestions) { suggestions.addEventListener('click', function(e){ if (e.target.classList.contains('suggestion-item')){ searchInput.value = e.target.textContent; suggestions.style.display='none'; searchDoctors(); } }); }
  document.addEventListener('click', function(e){ if (searchInput && suggestions && !searchInput.contains(e.target) && !suggestions.contains(e.target)) suggestions.style.display='none'; });

  const btnSearch = document.getElementById("btnSearch");
  // Function to search doctors
  function searchDoctors() {
    const keyword = (document.getElementById("searchInput").value || '').trim();
    if (!keyword) { 
      const currentLang = localStorage.getItem('kham_lang') || 'vi';
      const L = dict[currentLang];
      alert(L.searchAlert); 
      return; 
    }
    window.location.href = `TimKiemBS.php?keyword=${encodeURIComponent(keyword)}`;
  }

  if (btnSearch) btnSearch.onclick = searchDoctors;
                      // expose for debugging
                      window.__doctorsData = doctors;
                    })();
                    </script>
                    
                    <!-- FINAL Specialty Switch Fix -->
                    <script>
                    // Wait a bit to ensure all elements are loaded
                    setTimeout(function() {
                        console.log('🚀 FINAL specialty switch starting...');
                        
                        // Get elements
                        const buttons = document.querySelectorAll('.specialty-item');
                        const grid = document.getElementById('doctorsGrid');
                        
                        console.log('�  Found buttons:', buttons.length);
                        console.log('📋 Found grid:', grid ? 'YES' : 'NO');
                        
                        if (!grid) {
                            console.error('❌ Grid not found!');
                            return;
                        }
                        
                        // Doctors data
                        const allDoctors = [
                            { id: 1001, name: "Nguyễn Thị Lan", specialty: "Sản Phụ Khoa", img: "image/nguyenthilan.png.png", rating: 5, reviews: 24 },
                            { id: 1002, name: "Trần Văn Hùng", specialty: "Sản Phụ Khoa", img: "image/tranvanhung.png.png", rating: 4, reviews: 18 },
                            { id: 1003, name: "Lê Thị Mai", specialty: "Sản Phụ Khoa", img: "image/lethimai.png.png", rating: 4, reviews: 12 },
                            { id: 1004, name: "Lê Minh Tuấn", specialty: "Thần Kinh", img: "image/leminhtuan.png.png", rating: 5, reviews: 30 },
                            { id: 1005, name: "Trần Thị Hương", specialty: "Thần Kinh", img: "image/tranthihuong.png.png", rating: 4, reviews: 22 },
                            { id: 1006, name: "Nguyễn Văn Đức", specialty: "Thần Kinh", img: "image/ngnuyenvanduc.png.webp", rating: 4, reviews: 16 },
                            { id: 1007, name: "Đỗ Văn Hùng", specialty: "Da Liễu", img: "image/dovanhung.png.jpg", rating: 5, reviews: 28 },
                            { id: 1008, name: "Hoàng Minh Hoàng", specialty: "Da Liễu", img: "image/hoangminhhoang.png.png", rating: 5, reviews: 25 },
                            { id: 1009, name: "Phạm Thị Linh", specialty: "Da Liễu", img: "image/phamthilan.png.png", rating: 4, reviews: 20 },
                            { id: 1010, name: "Vũ Thị Mai", specialty: "Tim Mạch", img: "image/vuthimai.png.png", rating: 5, reviews: 42 },
                            { id: 1011, name: "Nguyễn Đức Minh", specialty: "Tim Mạch", img: "image/nguyenducminh.png.png", rating: 5, reviews: 38 },
                            { id: 1012, name: "Trần Văn Nam", specialty: "Tim Mạch", img: "image/tranvannam.png.png", rating: 4, reviews: 32 },
                            { id: 1013, name: "Phạm Thị Lan", specialty: "Nhi Khoa", img: "image/phamthilan.png.png", rating: 5, reviews: 35 },
                            { id: 1014, name: "Lê Thị Hoa", specialty: "Sản Phụ Khoa", img: "image/lethihoa.png.png", rating: 4, reviews: 28 },
                            { id: 1015, name: "Nguyễn Thị Hạnh", specialty: "Nhi Khoa", img: "image/nguyenthihanh.png.png", rating: 4, reviews: 24 },
                            { id: 1016, name: "Nguyễn Văn Hòa", specialty: "Nội Tổng Quát", img: "image/nguyenvanhoa.png.png", rating: 5, reviews: 40 },
                            { id: 1017, name: "Trần Thị Thu", specialty: "Nội Tổng Quát", img: "image/tranthiphu.png.png", rating: 4, reviews: 33 },
                            { id: 1018, name: "Lê Minh Tâm", specialty: "Nội Tổng Quát", img: "image/leminhtam.png.png", rating: 4, reviews: 29 }
                        ];
                        
                        console.log('📋 Loaded', allDoctors.length, 'doctors');
                        
                        // Simple filter function
                        function filterDoctors(specialtyName) {
                            console.log('🔍 Filtering for:', specialtyName);
                            
                            if (!specialtyName) return allDoctors;
                            
                            const filtered = allDoctors.filter(doctor => {
                                const match = doctor.specialty === specialtyName;
                                if (match) {
                                    console.log('✅ Match:', doctor.name, '-', doctor.specialty);
                                }
                                return match;
                            });
                            
                            console.log('📊 Found', filtered.length, 'doctors');
                            return filtered;
                        }
                        
                        // Simple render function
                        function renderDoctors(doctorsList) {
                            console.log('🎨 Rendering', doctorsList.length, 'doctors');
                            
                            if (doctorsList.length === 0) {
                                const currentLang = localStorage.getItem('kham_lang') || 'vi';
                                const L = dict[currentLang];
                                grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #6b7280;">${L.noDoctorsFound}</div>`;
                                return;
                            }
                            
                            const currentLang = localStorage.getItem('kham_lang') || 'vi';
                            const L = dict[currentLang];
                            
                            grid.innerHTML = doctorsList.map(doctor => {
                                const nameParts = doctor.name.split(' ');
                                let firstLetter = nameParts[nameParts.length - 1]?.charAt(0).toUpperCase() || 'B';
                                if (doctor.name.includes('Lê Thị Mai')) firstLetter = 'M';
                                
                                const colors = [
                                    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                    'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                                    'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                                    'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                                    'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                                    'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
                                ];
                                const avatarColor = colors[(doctor.id || 0) % colors.length];
                                
                                return 
                                    <div class="doctor-card">
                                        <div class="doctor-avatar" onclick="window.open('view_doctor.php?doctor_id=\${doctor.id}', '_blank')" style="cursor: pointer; position: relative; width: 80px; height: 80px; border-radius: 12px; overflow: hidden; margin: 0 auto 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                                            <img src="\${doctor.img}?v=\${Date.now()}" 
                                                 alt="\${doctor.name}" 
                                                 style="width: 100%; height: 100%; object-fit: cover;"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div style="display: none; width: 100%; height: 100%; background: \${avatarColor}; color: white; font-size: 2rem; font-weight: bold; align-items: center; justify-content: center;">
                                                \${firstLetter}
                                            </div>
                                        </div>
                                        <div class="doctor-info">
                                            <h4>BS. \${doctor.name}</h4>
                                            <p class="specialty">\${doctor.specialty}</p>
                                            <div class="rating">
                                                <span class="stars">\${'★'.repeat(doctor.rating || 5)}</span> 
                                                <span class="review-count">\${doctor.reviews || 0} \${L.reviewsText}</span>
                                            </div>
                                            <div class="doctor-actions">
                                                <a href="view_doctor.php?doctor_id=\${doctor.id}" class="btn-profile">\${L.btnProfileText}</a>
                                                <a href="DatLichK.php?doctor_id=\${doctor.id}" class="btn-book">\${L.btnBookText}</a>               
                                            </div>
                                        </div>
                                    </div>
                                \`;
                            }).join('');
                            
                            console.log('✅ Rendered successfully!');
                        }
                        
                        // Add click handlers
                        buttons.forEach((button, index) => {
                            const specialtyName = button.getAttribute('data-specialty');
                            
                            // Skip consultation button
                            if (!specialtyName || button.classList.contains('consultation-item')) {
                                console.log(\`⏭️ Skipping button \${index + 1}: \${specialtyName || 'consultation'}\`);
                                return;
                            }
                            
                            console.log(\`🔘 Setting up button \${index + 1}: \${specialtyName}\`);
                            
                            button.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                
                                console.log('👆 CLICKED:', specialtyName);
                                
                                // Remove active from all buttons
                                buttons.forEach(btn => {
                                    btn.classList.remove('selected', 'active');
                                });
                                
                                // Add active to clicked button
                                this.classList.add('selected');
                                
                                // Filter and show doctors
                                const filtered = filterDoctors(specialtyName);
                                renderDoctors(filtered);
                            });
                        });
                        
                        // Initialize with Tim Mạch (first available specialty)
                        const defaultButton = document.querySelector('.specialty-item[data-specialty="Tim Mạch"]');
                        if (defaultButton) {
                            console.log('🎯 Setting default: Tim Mạch');
                            defaultButton.classList.add('selected');
                            renderDoctors(filterDoctors('Tim Mạch'));
                        } else {
                            console.log('📋 No default button, showing all doctors');
                            renderDoctors(allDoctors);
                        }
                        
                        console.log('✅ Specialty switch ready!');
                    });
                    </script>
                </div>
            </div>
        </div>
    </section>

    <!-- Phần cơ sở vật chất -->
    <section class="facilities-section">
        <div class="container">
            <h2 class="section-title">Cơ sở vật chất</h2>
            <p class="section-subtitle">Sở hữu không gian khám chữa bệnh văn minh, sang trọng, hiện đại hỗ trợ hiệu quả cho việc chẩn đoán và điều trị</p>
            <div class="facilities-grid">
                <div class="facility-item">
                    <div class="facility-image">
                        <img src="image\phongmo.png" alt="Phòng mổ">
                    </div>
                    <h3>Hệ thống phòng mổ Hybrid hiện đại nhất Việt Nam</h3>
                </div>
                <div class="facility-item">
                    <div class="facility-image">
                        <img src="image\sangphukhoa.png" alt="Sản Phụ khoakhoa">
                    </div>
                    <h3>Hệ thống phòng sinh hiện đại giúp mẹ bầu vượt cạn</h3>
                </div>
                <div class="facility-item">
                    <div class="facility-image">
                        <img src="image\timmach.png"alt="Tim Mạch ">
                    </div>
                      <h3>Chuyên khoa Tim mạch với trang thiết bị chẩn đoán tiên tiến</h3>
                </div>
                <div class="facility-item">
                    <div class="facility-image">
                        <img src="image\thankinh.png" alt="Thần kinh ">
                    </div>
                    <h3>hoa Thần kinh chuyên sâu, đội ngũ bác sĩ giàu kinh nghiệm</h3>
                </div>
                <div class="facility-item">
                    <div class="facility-image">
                        <img src="image\nhikhoa.png" alt="Nhi khoa ">
                    </div>
                    <h3>Khoa Nhi đạt chuẩn, không gian thân thiện cho trẻ nhỏ</h3>
                </div>
                <div class="facility-item">
                    <div class="facility-image">
                        <img src="image\noitongquar.png" alt="Nội Tổng Quát ">
                    </div>
                    <h3>Khoa Nội tổng quát – chăm sóc sức khỏe toàn diện cho mọi người</h3>
                </div>
            </div>
        </div>
    </section>
<script>
(function(){
  const facilityI18n = {
    vi: {
      title: 'Cơ sở vật chất',
      subtitle: 'Sở hữu không gian khám chữa bệnh văn minh, sang trọng, hiện đại hỗ trợ hiệu quả cho việc chẩn đoán và điều trị',
      items: [
        'Hệ thống phòng mổ Hybrid hiện đại nhất Việt Nam',
        'Hệ thống phòng sinh tiên tiến giúp mẹ bầu vượt cạn an toàn',
        'Nhà thuốc đạt tiêu chuẩn GPP với danh mục thuốc đa dạng',
        'Khoa Tim mạch trang bị máy móc chẩn đoán hiện đại',
        'Khoa Nhi thân thiện, không gian vui chơi cho trẻ nhỏ',
        'Phòng khám Nội tổng quát với đội ngũ bác sĩ giàu kinh nghiệm'
      ]
    },
    km: {
      title: 'បរិក្ខារផ្សព្វផ្សាយ',
      subtitle: 'មន្ទីរពេទ្យមានបរិយាកាសទំនើប និងសមរម្យ ដើម្បីជួយក្នុងការធ្វើរោគវិទ្យា និងការព្យាបាលយ៉ាងមានប្រសិទ្ធភាព',
      items: [
        'ប្រព័ន្ធបន្ទប់វះកាត់ Hybrid ទំនើបបំផុតនៅវៀតណាម',
        'បន្ទប់សម្រាលទាន់សម័យ ជួយឱ្យម្ដាយឆ្លងកាត់ការសម្រាលយ៉ាងសុវត្ថិភាព',
        'ឱសថស្ថានមានស្តង់ដារ GPP និងបញ្ជីថ្នាំគ្រប់ប្រភេទ',
        'មន្ទីរពេទ្យជំនាញបេះដូង មានឧបករណ៍សវនកម្មទំនើប',
        'មន្ទីរពេទ្យកុមារ មានបរិយាកាសសប្បាយសម្រាប់កុមារ',
        'មន្ទីរពេទ្យទូទៅ មានវេជ្ជបណ្ឌិតមានបទពិសោធន៍ខ្ពស់'
      ]
    }
  };

  function applyFacilityLang(lang){
    lang = (lang === 'km') ? 'km' : 'vi';
    const L = facilityI18n[lang];

    const titleEl = document.querySelector('.facilities-section .section-title');
    if (titleEl) titleEl.textContent = L.title;

    const subEl = document.querySelector('.facilities-section .section-subtitle');
    if (subEl) subEl.textContent = L.subtitle;

    const itemEls = Array.from(document.querySelectorAll('.facilities-grid .facility-item h3'));
    itemEls.forEach((h3, idx) => {
      if (L.items[idx]) h3.textContent = L.items[idx];
    });
  }

  // apply on load
  document.addEventListener('DOMContentLoaded', function(){
    const saved = localStorage.getItem('kham_lang') || document.getElementById('langSelect')?.value || 'vi';
    applyFacilityLang(saved);
  });

  // listen select changes
  const langSelect = document.getElementById('langSelect');
  if (langSelect){
    langSelect.addEventListener('change', function(){
      localStorage.setItem('kham_lang', this.value);
      applyFacilityLang(this.value);
    });
  }

  // also observe storage (if language changed in another tab)
  window.addEventListener('storage', function(e){
    if (e.key === 'kham_lang') applyFacilityLang(e.newValue || 'vi');
  });
})();
</script>




 
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
                            <h2 id="footerBrandTitle">KhamCare</h2>
                            <p id="footerBrandSubtitle">INTERNATIONAL HOSPITAL</p>
                        </div>
                    </div>
                    <p class="brand-desc" id="footerDescription">
                        Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. 
                        Kết nối bạn với các bác sĩ chuyên khoa uy tín.
                    </p>
                    <div class="brand-stats">
                        <div class="stat">
                            <span class="number">50K+</span>
                            <span class="label" id="footerStatPatients">Bệnh nhân</span>
                        </div>
                        <div class="stat">
                            <span class="number">200+</span>
                            <span class="label" id="footerStatDoctors">Bác sĩ</span>
                        </div>
                        <div class="stat">
                            <span class="number">15+</span>
                            <span class="label" id="footerStatSpecialties">Chuyên khoa</span>
                        </div>
                    </div>
                </div>

                
                <!-- Patient Links -->
                <div class="footer-column">
                    <h3 id="footerPatientTitle"><i class="fas fa-user-injured"></i> Dành Cho Bệnh Nhân</h3>
                    <ul>
                        <li><a href="TimKiemBS.php"><i class="fas fa-search"></i> <span id="footerPatientLink1">Tìm kiếm bác sĩ</span></a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-sign-in-alt"></i> <span id="footerPatientLink2">Đăng nhập</span></a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-plus"></i> <span id="footerPatientLink3">Đăng ký</span></a></li>
                        <li><a href="DatLichK.php"><i class="fas fa-calendar-plus"></i> <span id="footerPatientLink4">Đặt lịch</span></a></li>
                        <li><a href="#"><i class="fas fa-history"></i> <span id="footerPatientLink5">Bảng kiểm soát lịch hẹn</span></a></li>
                    </ul>
                </div>

                <!-- Doctor Links -->
                <div class="footer-column">
                    <h3 id="footerDoctorTitle"><i class="fas fa-user-md"></i> Dành Cho Bác Sĩ</h3>
                    <ul>
                        <li><a href="doctor_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span id="footerDoctorLink1">Kiểm tra cuộc hẹn</span></a></li>
                        <li><a href="TuVanTrucTuyen.php"><i class="fas fa-comments"></i> <span id="footerDoctorLink2">Chat</span></a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-md"></i> <span id="footerDoctorLink3">Đăng nhập</span></a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-plus"></i> <span id="footerDoctorLink4">Đăng ký</span></a></li>
                        <li><a href="doctor_dashboard.php"><i class="fas fa-chart-line"></i> <span id="footerDoctorLink5">Dashboard của bác sĩ</span></a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="footer-column contact-column">
                    <h3 id="footerContactTitle"><i class="fas fa-phone-alt"></i> Liên Hệ</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div id="footerContactAddress">
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
                        <h4 id="footerSocialTitle">Kết nối với chúng tôi</h4>
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
                <p id="footerCopyright">&copy; 2024 <strong>KhamCare International Hospital</strong>. Tất cả quyền được bảo lưu.</p>
                <div class="bottom-links">
                    <a href="#" id="footerPrivacy">Chính sách bảo mật</a>
                    <a href="#" id="footerTerms">Điều khoản sử dụng</a>
                    <a href="#" id="footerSupport">Hỗ trợ khách hàng</a>
                </div>
            </div>
        </div>
    </div>
</footer>

    
    
     <script>
// Footer translation is now handle
    const footerI18n = {
        vi: {
            brandTitle: "KhamCare",
            brandSubtitle: "INTERNATIONAL HOSPITAL",
            description: "Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. Kết nối bạn với các bác sĩ chuyên khoa uy tín.",
            patientTitle: "Dành Cho Bệnh Nhân",
            patientLinks: [
                "Tìm kiếm bác sĩ",
                "Đăng nhập",
                "Đăng ký",
                "Đặt lịch",
                "Bảng kiểm soát lịch hẹn"
            ],
            doctorTitle: "Dành Cho Bác Sĩ",
            doctorLinks: [
                "Kiểm tra cuộc hẹn",
                "Chat",
                "Đăng nhập",
                "Đăng ký",
                "Dashboard của bác sĩ"
            ],
            contactTitle: "Liên Hệ",
            contactAddress: "<strong>Địa chỉ:</strong><br>Sở y tế - Bệnh viện đa khoa quốc tế<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
            copyright: "© 2024 KhamCare International Hospital. Tất cả quyền được bảo lưu.",
            privacy: "Chính sách bảo mật",
            terms: "Điều khoản sử dụng",
            support: "Hỗ trợ khách hàng",
            statPatients: "Bệnh nhân",
            statDoctors: "Bác sĩ",
            statSpecialties: "Chuyên khoa",
            socialTitle: "Kết nối với chúng tôi"
        },
        km: {
            brandTitle: "KhamCare",
            brandSubtitle: "មន្ទីរពេទ្យអន្តរជាតិ",
            description: "ប្រព័ន្ធកក់ពេលវេលាពិនិត្យសុខភាពតាមអនឡាញឈានមុខគេនៅវៀតណាម។ ភ្ជាប់អ្នកជាមួយវេជ្ជបណ្ឌិតជំនាញដែលមានកេរ្តិ៍ឈ្មោះ។",
            patientTitle: "សម្រាប់អ្នកជម្ងឺ",
            patientLinks: [
                "ស្វែងរកវេជ្ជបណ្ឌិត",
                "ចូលប្រព័ន្ធ",
                "ចុះឈ្មោះ",
                "កក់ពេលវេលា",
                "តារាងត្រួតពិនិត្យការណាត់ជួប"
            ],
            doctorTitle: "សម្រាប់វេជ្ជបណ្ឌិត",
            doctorLinks: [
                "ត្រួតពិនិត្យការណាត់ជួប",
                "ជជែក",
                "ចូលប្រព័ន្ធ",
                "ចុះឈ្មោះ",
                "ផ្ទាំងគ្រប់គ្រងរបស់វេជ្ជបណ្ឌិត"
            ],
            contactTitle: "ទាក់ទង",
            contactAddress: "<strong>អាសយដ្ឋាន:</strong><br>ក្រសួងសុខាភិបាល - មន្ទីរពេទ្យពហុជំនាញអន្តរជាតិ<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
            copyright: "© ២០២៤ KhamCare មន្ទីរពេទ្យអន្តរជាតិ។ រក្សាសិទ្ធិគ្រប់យ៉ាង។",
            privacy: "គោលការណ៍ភាពឯកជន",
            terms: "លក្ខខណ្ឌប្រើប្រាស់",
            support: "ការគាំទ្រអតិថិជន",
            statPatients: "អ្នកជម្ងឺ",
            statDoctors: "វេជ្ជបណ្ឌិត",
            statSpecialties: "ជំនាញ",
            socialTitle: "ភ្ជាប់ជាមួយយើង"
        }
    };

    // Gán vào window để có thể truy cập từ bên ngoài
    window.applyFooterLang = function(lang) {
        lang = lang === 'km' ? 'km' : 'vi'; // Mặc định là tiếng Việt nếu không phải tiếng Khmer
        const L = footerI18n[lang];
        
        console.log('Applying footer language:', lang, 'Doctor title:', L.doctorTitle);

        // Update brand info
        const brandTitle = document.getElementById('footerBrandTitle');
        const brandSubtitle = document.getElementById('footerBrandSubtitle');
        const description = document.getElementById('footerDescription');
        
        if (brandTitle) brandTitle.textContent = L.brandTitle;
        if (brandSubtitle) brandSubtitle.textContent = L.brandSubtitle;
        if (description) description.textContent = L.description;

        // Update patient section
        const patientTitle = document.getElementById('footerPatientTitle');
        if (patientTitle) {
            // Giữ nguyên icon, chỉ thay đổi text
            const icon = patientTitle.querySelector('i');
            patientTitle.innerHTML = '';
            if (icon) patientTitle.appendChild(icon);
            patientTitle.innerHTML += ' ' + L.patientTitle;
        }
        
        const patientLinks = ['footerPatientLink1', 'footerPatientLink2', 'footerPatientLink3', 'footerPatientLink4', 'footerPatientLink5'];
        patientLinks.forEach((linkId, idx) => {
            const link = document.getElementById(linkId);
            if (link && L.patientLinks[idx]) {
                link.textContent = L.patientLinks[idx];
            }
        });

        // Update doctor section
        const doctorTitle = document.getElementById('footerDoctorTitle');
        if (doctorTitle) {
            const icon = doctorTitle.querySelector('i');
            doctorTitle.innerHTML = '';
            if (icon) doctorTitle.appendChild(icon);
            doctorTitle.innerHTML += ' ' + L.doctorTitle;
        }
        
        const doctorLinks = ['footerDoctorLink1', 'footerDoctorLink2', 'footerDoctorLink3', 'footerDoctorLink4', 'footerDoctorLink5'];
        doctorLinks.forEach((linkId, idx) => {
            const link = document.getElementById(linkId);
            if (link && L.doctorLinks[idx]) {
                link.textContent = L.doctorLinks[idx];
            }
        });

        // Update contact section
        const contactTitle = document.getElementById('footerContactTitle');
        const contactAddress = document.getElementById('footerContactAddress');
        
        if (contactTitle) {
            const icon = contactTitle.querySelector('i');
            contactTitle.innerHTML = '';
            if (icon) contactTitle.appendChild(icon);
            contactTitle.innerHTML += ' ' + L.contactTitle;
        }
        if (contactAddress) {
            contactAddress.innerHTML = L.contactAddress;
        }

        // Update footer bottom
        const copyright = document.getElementById('footerCopyright');
        const privacy = document.getElementById('footerPrivacy');
        const terms = document.getElementById('footerTerms');
        const support = document.getElementById('footerSupport');
        
        if (copyright) copyright.innerHTML = L.copyright;
        if (privacy) privacy.textContent = L.privacy;
        if (terms) terms.textContent = L.terms;
        if (support) support.textContent = L.support;

        // Update stats
        const statPatients = document.getElementById('footerStatPatients');
        const statDoctors = document.getElementById('footerStatDoctors');
        const statSpecialties = document.getElementById('footerStatSpecialties');
        
        if (statPatients) statPatients.textContent = L.statPatients;
        if (statDoctors) statDoctors.textContent = L.statDoctors;
        if (statSpecialties) statSpecialties.textContent = L.statSpecialties;

        // Update social title
        const socialTitle = document.getElementById('footerSocialTitle');
        if (socialTitle) socialTitle.textContent = L.socialTitle;
    }

    // Footer translations are now handled by the main applyLang function
    // Initial application on page load
    document.addEventListener('DOMContentLoaded', function () {
        const savedLang = localStorage.getItem('kham_lang') || 'vi';
        window.applyFooterLang(savedLang);
    });
    
    // Listen for language changes
    const langSelectFooter = document.getElementById('langSelect');
    if (langSelectFooter) {
        langSelectFooter.addEventListener('change', function() {
            window.applyFooterLang(this.value);
        });
    }
})();
</script>

    <!-- Account Modal (inserted) -->
    <div id="accountModalOverlay" class="account-modal-overlay" aria-hidden="true" role="dialog" aria-label="Thông tin tài khoản">
      <div class="account-modal" role="document">
        <button class="close-btn" id="accountModalClose" aria-label="Đóng">&times;</button>
        <div class="head">
          <div class="avatar"><?= strtoupper(substr(htmlspecialchars($user['full_name'] ?? ''),0,1)) ?></div>
          <div>
            <h4><?= htmlspecialchars($user['full_name'] ?? 'Người dùng') ?></h4>
            <div class="user-role"><?= htmlspecialchars($user['role'] ?? 'Bệnh nhân') ?></div>
          </div>
        </div>

        <div class="account-row">
          <label><i class="fas fa-user"></i> Tên đăng nhập</label>
          <div class="value"><?= htmlspecialchars($user['username'] ?? '') ?></div>
        </div>

        <div class="account-row">
          <label><i class="fas fa-envelope"></i> Email</label>
          <div class="value"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        </div>

        <div class="account-row">
          <label><i class="fas fa-phone"></i> Số điện thoại</label>
          <div class="value"><?= htmlspecialchars($user['phone'] ?? $user['sdt'] ?? '') ?></div>
        </div>

        <div class="account-actions">
          <a href="update_profile.php" class="btn btn-edit">
            <i class="fas fa-user-edit"></i> Cập nhật hồ sơ
          </a>
        </div>
      </div>
    </div>

    <!-- Chat overlay đã được chuyển sang TuVanTrucTuyen.php -->
<script>
  // Thông tin user từ PHP
  const user = {
    name: "<?php echo htmlspecialchars($user['full_name']); ?>",
    username: "<?php echo htmlspecialchars($user['username']); ?>",
    email: "<?php echo htmlspecialchars($user['email']); ?>"
  };

  // Popover vị trí cạnh nút Tài khoản
  const btnAccount = document.getElementById("btnAccount");
  const pop = document.getElementById("accountPopover");
  const apClose = document.getElementById("apClose");

  function openAccountPopover() {
    const rect = btnAccount.getBoundingClientRect();
    const top = rect.top + window.scrollY; // cùng hàng với nút
    const left = rect.right + 12 + window.scrollX; // nằm ngay bên phải nút
    pop.style.top = top + 'px';
    pop.style.left = left + 'px';
    pop.style.display = 'block';
    document.getElementById("name").value = user.name || "";
    document.getElementById("username").value = user.username || "";
    document.getElementById("email").value = user.email || "";
  }

  function closeAccountPopover() { pop.style.display = 'none'; }

  // Account button redirect function
  function redirectToAccount() {
    console.log('🔄 Redirecting to tk.php...');
    window.location.href = 'tk.php';
  }
  
  // Make function global
  window.redirectToAccount = redirectToAccount;
  
  // Account button now redirects to tk.php via onclick attribute
  // btnAccount.addEventListener('click', function(e) {
  //   e.stopPropagation();
  //   if (pop.style.display === 'block') { closeAccountPopover(); } else { openAccountPopover(); }
  // });
  apClose.addEventListener('click', function(e) { e.stopPropagation(); closeAccountPopover(); });
  document.addEventListener('click', function(e) {
    if (!pop.contains(e.target) && e.target !== btnAccount && !btnAccount.contains(e.target)) closeAccountPopover();
  });

  // Dropdown nav show/hide helpers
  const ddDoctors = document.getElementById('ddDoctors');
  const ddSpecialties = document.getElementById('ddSpecialties');
  const ddGuides = document.getElementById('ddGuides');
  const navDoctors = document.getElementById('navDoctors');
  const navSpecialties = document.getElementById('navSpecialties');
  const navGuides = document.getElementById('navGuides');

  function hideAllDD(){ ddDoctors.style.display='none'; ddSpecialties.style.display='none'; ddGuides.style.display='none'; }
  // navDoctors giờ là link trực tiếp, không cần dropdown
  // navDoctors.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); hideAllDD(); ddDoctors.style.display='block'; });
  navSpecialties.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); hideAllDD(); ddSpecialties.style.display='block'; });
  navGuides.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); hideAllDD(); ddGuides.style.display='block'; });
  document.addEventListener('click', function(){ hideAllDD(); });



  // Không cần overlay; đã đóng khi click ra ngoài

const doctors = [
  "Đỗ Văn Hùng - Da Liễu",
  "Nguyễn Thị Lan - Sản Phụ Khoa",
  "Trần Văn Hùng - Sản Phụ Khoa",
  "Lê Thị Mai - Sản Phụ Khoa",
  "Lê Minh Tuấn - Thần Kinh",
  "Trần Thị Hương - Thần Kinh",
  "Nguyễn Văn Đức - Thần Kinh",
  "Hoàng Minh Hoang - Da Liễu",
  "Phạm Thị Lan - Nhi Khoa",
  "Lê Thị Hoa - Nha Khoa",
  "Phạm Văn Long - Nha Khoa",
  "Nguyễn Thị Hạnh - Nha Khoa",
  "Vũ Yhị Mai - Tim Mạch",
  "Nguyễn Đức Minh - Tim Mạch",
  "Trần Văn Nam - Tim Mạch",
  "Nguyễn Văn Hòa - Nội Tổng Quát",
  "Trần Thị Thu - Nội Tổng Quát",
  "Lê Minh Tâm - Nội Tổng Quát",
  // Gợi ý chuyên khoa
  "Chuyên khoa Tim Mạch",
  "Chuyên khoa Thần Kinh",
  "Chuyên khoa Sản Phụ Khoa",
  "Chuyên khoa Da Liễu",
  "Chuyên khoa Nhi Khoa",
  "Chuyên khoa Nội Tổng Quát"
];

const searchInput = document.getElementById("searchInput");
const suggestions = document.getElementById("suggestions");

// Hiện gợi ý khi nhập hoặc bấm vào ô tìm kiếm
searchInput.addEventListener("input", showSuggestions);
searchInput.addEventListener("focus", showSuggestions);

function showSuggestions() {
  const value = searchInput.value.trim().toLowerCase
  let filtered = [];
  if (value) {
    filtered = doctors.filter(item => item.toLowerCase().includes(value));
  } else {
    filtered = doctors.slice(0, 6); // Gợi ý mặc định
  }
  if (filtered.length === 0) {
    suggestions.style.display = "none";
    return;
  }
  suggestions.innerHTML = filtered.map(item => `<div class="suggestion-item">${item}</div>`).join("");
  suggestions.style.display = "block";
}

// Chọn gợi ý
suggestions.onclick = function(e) {
  if (e.target.classList.contains("suggestion-item")) {
    searchInput.value = e.target.textContent;
    suggestions.style.display = "none";
  }
};

// Ẩn gợi ý khi click ra ngoài
document.addEventListener("click", function(e) {
  if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
    suggestions.style.display = "none";
  }
});

// btnSearch onclick đã được gán ở trên

// Mở khung chat khi bấm vào nút tư vấn
function openChat() {
  // Chuyển hướng đến trang tư vấn trực tuyến chuyên dụng
  window.location.href = 'TuVanTrucTuyen.php';
  return;
}

// Debug Font Awesome loading
document.addEventListener('DOMContentLoaded', function() {
    console.log('Checking Font Awesome icons...');
    const icons = document.querySelectorAll('.icon-box i');
    icons.forEach((icon, index) => {
        console.log(`Icon ${index}:`, icon.className, 'Computed style:', window.getComputedStyle(icon).fontFamily);
    });
});

</script>

<!-- Specialty Filter Script -->
<script src="js/specialty-filter.js"></script>

<!-- Script filter chuyên khoa nổi bật -->
<script>
// Hàm filter bác sĩ khi click vào chuyên khoa nổi bật
function filterDoctorsBySpecialty(specialty) {
    console.log('🎯 Filter by specialty:', specialty);
    
    // Lấy các elements cần thiết
    const tabs = document.querySelectorAll('.specialty-tab');
    const doctorCards = document.querySelectorAll('.doctor-card');
    const doctorsGrid = document.getElementById('doctorsGrid');
    const doctorSection = document.querySelector('.doctor-team-section');
    
    // Scroll đến phần bác sĩ
    if (doctorSection) {
        doctorSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Cập nhật active state cho tabs
    tabs.forEach(tab => {
        tab.classList.remove('active');
        if (tab.getAttribute('data-specialty') === specialty) {
            tab.classList.add('active');
        }
    });
    
    // Filter doctor cards
    let visibleCount = 0;
    let specialtyCount = 0;
    const maxDoctorsPerSpecialty = 3;
    
    doctorCards.forEach((card) => {
        const cardSpecialty = card.getAttribute('data-specialty');
        
        if (cardSpecialty === specialty) {
            if (specialtyCount < maxDoctorsPerSpecialty) {
                card.style.display = 'flex';
                visibleCount++;
                specialtyCount++;
            } else {
                card.style.display = 'none';
            }
        } else {
            card.style.display = 'none';
        }
    });
    
    console.log('✅ Visible doctors:', visibleCount);
    
    // Highlight specialty item đã chọn
    const specialtyItems = document.querySelectorAll('.specialty-item');
    specialtyItems.forEach(item => {
        item.classList.remove('selected');
        if (item.getAttribute('data-specialty') === specialty) {
            item.classList.add('selected');
        }
    });
    
    // Show message if no doctors found
    if (visibleCount === 0 && doctorsGrid) {
        let noDocsMsg = doctorsGrid.querySelector('.no-doctors-message');
        if (!noDocsMsg) {
            noDocsMsg = document.createElement('div');
            noDocsMsg.className = 'no-doctors-message show';
            noDocsMsg.innerHTML = '<i class="fas fa-user-md"></i><p>Không tìm thấy bác sĩ nào cho chuyên khoa này.</p>';
            doctorsGrid.appendChild(noDocsMsg);
        } else {
            noDocsMsg.classList.add('show');
        }
    }
}

// Thêm event listener cho các chuyên khoa nổi bật
document.addEventListener('DOMContentLoaded', function() {
    const clickableSpecialties = document.querySelectorAll('.clickable-specialty');
    console.log('🔍 Found clickable specialties:', clickableSpecialties.length);
    
    clickableSpecialties.forEach(item => {
        item.addEventListener('click', function() {
            const specialty = this.getAttribute('data-specialty');
            console.log('👆 Clicked specialty:', specialty);
            filterDoctorsBySpecialty(specialty);
        });
    });
});
</script>

<!-- Chatbot AI Widget -->
<?php include 'chatbot_widget.php'; ?>

</body>
</html>
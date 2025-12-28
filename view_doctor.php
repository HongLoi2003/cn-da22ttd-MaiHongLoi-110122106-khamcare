<?php
// View Doctor với Base64 Embedded Images - Giải pháp ảnh thật ổn định
session_start();
require_once 'db_config.php';
require_once 'doctor_data_helper.php';
require_once 'doctor_base64_images.php';
require_once 'doctor_id_mapping.php';
// Image mapping for doctors (same as TrangChu.php)
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

// Get doctor ID from URL
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

if (!$doctor_id) {
    header('Location: TrangChu.php');
    exit;
}

$doc = getDoctorById($doctor_id);

if (!$doc) {
    echo "Không tìm thấy thông tin bác sĩ với ID: " . $doctor_id; 
    exit;
}

function e($s){ return htmlspecialchars($s ?? ''); }

// HYBRID IMAGE SYSTEM - Ảnh thật trước, SVG fallback sau
function generateDoctorSVG($doctorId, $name, $specialty) {
    $colors = [
        'Sản Phụ Khoa' => ['#ff6b9d', '#ffc3e0'],
        'Thần Kinh' => ['#4ecdc4', '#a8e6cf'],
        'Da Liễu' => ['#45b7d1', '#96ceb4'],
        'Tim Mạch' => ['#f39c12', '#f8c471'],
        'Nhi Khoa' => ['#9b59b6', '#d2b4de'],
        'Nội Tổng Quát' => ['#34495e', '#85929e'],
        'Mắt' => ['#e74c3c', '#f1948a'],
        'Tai Mũi Họng' => ['#2ecc71', '#82e5aa'],
        'Răng Hàm Mặt' => ['#3498db', '#85c1e9'],
        'Ngoại Khoa' => ['#e67e22', '#f8c471']
    ];
    
    $colorPair = $colors[$specialty] ?? ['#6c5ce7', '#a29bfe'];
    $color1 = $colorPair[0];
    $color2 = $colorPair[1];
    
    $nameParts = explode(' ', $name);
    $firstLetter = strtoupper(substr(end($nameParts), 0, 1));
    
    return '<svg width="100%" height="100%" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" style="border-radius: 20px;">
        <defs>
            <linearGradient id="grad' . $doctorId . '" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:' . $color1 . ';stop-opacity:1" />
                <stop offset="100%" style="stop-color:' . $color2 . ';stop-opacity:1" />
            </linearGradient>
        </defs>
        <rect width="200" height="200" rx="20" fill="url(#grad' . $doctorId . ')"/>
        <circle cx="100" cy="80" r="30" fill="rgba(255,255,255,0.2)"/>
        <text x="100" y="95" font-family="Arial, sans-serif" font-size="24" font-weight="bold" text-anchor="middle" fill="white">👨‍⚕️</text>
        <text x="100" y="140" font-family="Arial, sans-serif" font-size="36" font-weight="bold" text-anchor="middle" fill="white">' . $firstLetter . '</text>
    </svg>';
}


// ID MAPPING: Chuyển đổi ID từ TrangChu.php sang Base64 IDs
function mapToBase64Id($inputId) {
    // Mapping từ database ID sang Base64 ID (1001-1018)
    $idMapping = [
        // Database ID => Base64 ID
        1 => 1001,   // Nguyễn Thị Lan
        2 => 1002,   // Trần Văn Hùng  
        3 => 1003,   // Lê Thị Mai
        4 => 1004,   // Lê Minh Tuấn
        5 => 1005,   // Trần Thị Hương
        6 => 1006,   // Nguyễn Văn Đức
        7 => 1007,   // Đỗ Văn Hùng
        8 => 1008,   // Hoàng Minh Hoàng
        9 => 1009,   // Phạm Thị Lan
        10 => 1010,  // Vũ Thị Mai
        11 => 1011,  // Nguyễn Đức Minh
        12 => 1012,  // Trần Văn Nam
        13 => 1013,  // Phạm Thị Lan (duplicate)
        14 => 1014,  // Lê Thị Hoa
        15 => 1015,  // Nguyễn Thị Hạnh
        16 => 1016,  // Nguyễn Văn Hòa
        17 => 1017,  // Trần Thị Phú
        18 => 1018,  // Lê Minh Tâm
    ];
    
    // Nếu đã là Base64 ID (1001-1018) thì giữ nguyên
    if ($inputId >= 1001 && $inputId <= 1018) {
        return $inputId;
    }
    
    // Nếu là database ID thì map sang Base64 ID
    return $idMapping[$inputId] ?? $inputId;
}

// Apply mapping
$doctor_id = mapToBase64Id($doctor_id);

// Get doctor info
$doctorId = intval($doc['id'] ?? $doc['doctor_row_id'] ?? $doctor_id);
$doctorId = mapToBase64Id($doctorId);
$fullName = $doc['full_name'] ?? '';
$specialty = $doc['title'] ?? $doc['specialty'] ?? 'Chuyên khoa';

// BASE64 EMBEDDED IMAGES - Ảnh thật được nhúng trực tiếp
$hasRealImage = hasDoctorRealImage($doctorId);
$base64Image = getDoctorBase64Image($doctorId);

// Generate SVG fallback
$svgFallback = generateDoctorSVG($doctorId, $fullName, $specialty);

// Get first letter for fallback
$fullName = $doc['full_name'] ?? '';
$nameParts = explode(' ', $fullName);
$firstLetter = strtoupper(substr(end($nameParts), 0, 1)) ?: 'B';

// Colors for fallback
$colors = [
    'linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%)',
    'linear-gradient(135deg, #10b981 0%, #059669 100%)',
    'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%)',
    'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
    'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)'
];
$colorIndex = ($doctorId) % count($colors);
$avatarColor = $colors[$colorIndex];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Hồ sơ bác sĩ - <?php echo e($doc['full_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="global-font-override.css">
    <link rel="stylesheet" href="square-icons-override.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <link rel="stylesheet" href="doctor-card-modern.css">
    <style>
        
        

        :root {
            /* Modern Color Palette - Same as TrangChu.php */
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
                radial-gradient(circle at 20% 80%, rgba(14, 165, 233, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.2) 0%, transparent 50%),
                linear-gradient(135deg, #0c4a6e 0%, #0369a1 50%, #0284c7 100%);
            background-attachment: fixed;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            padding: 0;
            margin: 0;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 25px 25px, rgba(255, 255, 255, 0.08) 2px, transparent 2px),
                radial-gradient(circle at 75px 75px, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 100px 100px, 50px 50px;
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 100%;
            width: 100%;
            margin: 0;
            background: transparent;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        .header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            color: #1e293b;
            padding: 20px 50px;
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Logo Styles */
        .logo-link {
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
            padding: 8px 16px;
            border-radius: 12px;
        }

        .logo-link span {
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

        .logo-link:hover {
            transform: scale(1.02);
        }

        .logo-link:hover span {
            animation-duration: 1.5s;
        }

        .logo-link img {
            width: 65px;
            height: 65px;
            object-fit: contain;
            border-radius: 18px;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: logoFloat 3s ease-in-out infinite, logoPulse 2s ease-in-out infinite;
        }

        .logo-link:hover img {
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



        .header h1 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 2.5rem;
            font-weight: 900;
            margin: 0;
            flex: 1;
            text-align: center;
            background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.03em;
            line-height: 1.1;
            filter: drop-shadow(0 2px 8px rgba(6, 182, 212, 0.3));
            position: relative;
            padding: 8px 0;
        }

        .header h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 4px;
            background: linear-gradient(90deg, #0ea5e9, #10b981);
            border-radius: 2px;
            box-shadow: 0 2px 8px rgba(6, 182, 212, 0.4);
        }

        .doctor-profile {
            max-width: 1400px;
            margin: 0 auto;
            padding: 50px 80px;
            background: transparent;
        }

        .doctor-main {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            margin-bottom: 35px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 
                0 20px 50px rgba(0, 0, 0, 0.3),
                0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .doctor-avatar {
            width: 180px;
            height: 180px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.2);
            flex-shrink: 0;
            position: relative;
            border: 3px solid white;
        }

        .doctor-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .doctor-fallback {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            font-weight: 900;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .doctor-info {
            flex: 1;
        }

        .doctor-info h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 12px 0;
            color: #1e293b;
            line-height: 1.3;
        }

        .doctor-specialty {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }

        .doctor-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin: 40px 0;
        }

        .detail-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 
                0 15px 40px rgba(0, 0, 0, 0.25),
                0 8px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .detail-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #10b981 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .detail-card:hover {
            border-color: #6366f1;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15);
            transform: translateY(-5px);
        }
        
        .detail-card:hover::before {
            opacity: 1;
        }

        .detail-label {
            font-weight: 700;
            color: #0ea5e9;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 600;
            line-height: 1.5;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 40px;
        }

        /* Button Styles */
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-consultation {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }

        .btn-consultation:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.4);
        }

        .btn-call {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .btn-call:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }

        .debug-info {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
        }

        @media (max-width: 1200px) {
            .doctor-profile {
                padding: 40px 50px;
            }
            
            .doctor-details {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 18px 25px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .logo-text {
                font-size: 1.5rem;
            }
            
            .logo-img {
                width: 45px;
                height: 45px;
            }
            
            .doctor-profile {
                padding: 30px 25px;
            }
            
            .doctor-main {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 20px;
            }
            
            .doctor-avatar {
                width: 140px;
                height: 140px;
            }
            
            .doctor-info h1 {
                font-size: 1.5rem;
            }
            
            .doctor-details {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .actions {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 15px 20px;
            }
            
            .header h1 {
                font-size: 1.3rem;
            }
            
            .logo-text {
                font-size: 1.3rem;
            }
            
            .logo-img {
                width: 40px;
                height: 40px;
            }
            
            .doctor-profile {
                padding: 20px 15px;
            }
            
            .doctor-avatar {
                width: 120px;
                height: 120px;
            }
            
            .doctor-info h1 {
                font-size: 1.3rem;
            }
            
            .detail-card {
                padding: 18px;
            }
            
            .btn {
                padding: 14px 20px;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="TrangChu.php" class="logo-link">
                <img src="image\logo.png.png" alt="Logo">
                <span>KhamCare</span>
            </a>
            <h1>Hồ sơ Bác sĩ</h1>
        </div>
        
        <div class="doctor-profile">
            <?php if (isset($_GET['debug'])): ?>
            <div class="debug-info">
                <strong>🔍 BASE64 DEBUG INFO:</strong><br>
                Doctor ID: <?php echo $doctorId; ?><br>
                Has Real Image: <?php echo $hasRealImage ? 'YES ✅' : 'NO ❌'; ?><br>
                Base64 Length: <?php echo $base64Image ? strlen($base64Image) . ' chars' : 'N/A'; ?><br>
                Full Name: <?php echo e($fullName); ?><br>
                Specialty: <?php echo e($specialty); ?>
            </div>
            <?php endif; ?>
            
            <div class="doctor-main">
                <div class="doctor-avatar">
                    <?php if ($hasRealImage && $base64Image): ?>
                        <!-- Base64 Embedded Real Image - Ảnh thật được nhúng trực tiếp -->
                        <img src="<?php echo $base64Image; ?>" 
                             alt="BS. <?php echo e($fullName); ?>"
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 20px; display: block;"
                             onload="console.log('✅ Base64 embedded image loaded for Doctor <?php echo $doctorId; ?>');">
                        
                        <!-- Status indicator -->
                        <div style="position: absolute; top: 10px; right: 10px; background: rgba(16, 185, 129, 0.9); 
                                    color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">
                            Ảnh thật
                        </div>
                    <?php else: ?>
                        <!-- SVG Fallback nếu không có ảnh thật -->
                        <?php echo $svgFallback; ?>
                        <div style="position: absolute; top: 10px; right: 10px; background: rgba(156, 163, 175, 0.9); 
                                    color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.8rem; font-weight: 600;">
                            Avatar
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="doctor-info">
                    <h1>BS. <?php echo e($doc['full_name']); ?></h1>
                    <div class="doctor-specialty">
                        <i class="fas fa-stethoscope"></i>
                        <?php echo e($doc['title'] ?? $doc['specialty'] ?? 'Chuyên khoa'); ?>
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px; margin-top: 15px; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 8px; color: var(--gray-600); font-size: 1.1rem;">
                            <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i>
                            <?php echo e($doc['location'] ?? 'Hà Nội, Việt Nam'); ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; color: var(--gray-600); font-size: 1.1rem;">
                            <i class="fas fa-award" style="color: var(--success);"></i>
                            <?php 
                            $exp = isset($doc['experience_years']) && $doc['experience_years'] > 0 ? $doc['experience_years'] : rand(5, 15);
                            echo e($exp); 
                            ?> năm kinh nghiệm
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; color: var(--gray-600); font-size: 1.1rem;">
                            <i class="fas fa-star" style="color: var(--warning);"></i>
                            <?php 
                            $rating = isset($doc['rating']) && $doc['rating'] > 0 ? $doc['rating'] : number_format(rand(45, 50) / 10, 1);
                            $reviews = isset($doc['total_reviews']) && $doc['total_reviews'] > 0 ? $doc['total_reviews'] : rand(50, 200);
                            echo e($rating); 
                            ?>/5.0
                            (<?php echo e($reviews); ?> đánh giá)
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="doctor-details">
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-clock"></i>
                        Lịch khám
                    </div>
                    <div class="detail-value"><?php echo e($doc['availability'] ?? 'Thứ 2 - Thứ 6: 8:00 - 17:00'); ?></div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-graduation-cap"></i>
                        Kinh nghiệm
                    </div>
                    <div class="detail-value"><?php echo e($doc['experience'] ?? $doc['degree'] ?? ($exp . ' năm kinh nghiệm chuyên khoa')); ?></div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-money-bill-wave"></i>
                        Phí khám
                    </div>
                    <div class="detail-value" style="color: var(--success); font-weight: 800;">
                        <?php 
                        if (isset($doc['consultation_fee']) && $doc['consultation_fee'] > 0) {
                            if (is_numeric($doc['consultation_fee'])) {
                                echo number_format($doc['consultation_fee']) . ' VNĐ';
                            } else {
                                echo e($doc['consultation_fee']);
                            }
                        } else {
                            echo number_format(rand(300, 500) * 1000) . ' VNĐ';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-phone"></i>
                        Điện thoại
                    </div>
                    <div class="detail-value">
                        <?php 
                        $phone = !empty($doc['phone']) ? $doc['phone'] : '024-3456-' . rand(1000, 9999);
                        ?>
                        <a href="tel:<?php echo e($phone); ?>" style="color: var(--primary); text-decoration: none;">
                            <?php echo e($phone); ?>
                        </a>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-label">
                        <i class="fas fa-envelope"></i>
                        Email
                    </div>
                    <div class="detail-value">
                        <?php 
                        $email = !empty($doc['email']) ? $doc['email'] : strtolower(str_replace([' ', '.'], '', $doc['full_name'] ?? 'bacsi')) . '@khamcare.vn';
                        ?>
                        <a href="mailto:<?php echo e($email); ?>" style="color: var(--primary); text-decoration: none;">
                            <?php echo e($email); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($doc['bio'])): ?>
            <div class="detail-card" style="margin: 30px 0; grid-column: 1 / -1;">
                <div class="detail-label">
                    <i class="fas fa-user-md"></i>
                    Tiểu sử & Chuyên môn
                </div>
                <div class="detail-value" style="line-height: 1.8; margin-top: 15px; font-size: 1.1rem;">
                    <?php echo nl2br(e($doc['bio'])); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="actions">
                <?php 
                // Convert View ID sang Database ID cho DatLichK.php
                $currentViewId = intval($doc['doctor_row_id'] ?? $doc['id'] ?? $doctor_id);
                $databaseIdForBooking = mapViewIdToDatabaseId($currentViewId);
                ?>
                <a class="btn btn-primary" href="DatLichK.php?doctor_id=<?php echo $databaseIdForBooking; ?>">
                    <i class="fas fa-calendar-plus"></i>
                    Đặt lịch khám
                </a>
                <a class="btn btn-success" href="DatLichK.php?doctor_id=<?php echo $databaseIdForBooking; ?>">
                    <i class="fas fa-bolt"></i>
                    Đặt ngay
                </a>
                <a class="btn btn-consultation" href="TuVanTrucTuyen.php?doctor_id=<?php echo intval($doc['doctor_row_id'] ?? $doc['id']); ?>">
                    <i class="fas fa-video"></i>
                    Tư vấn trực tuyến
                </a>
                <a class="btn btn-call" href="tel:<?php echo e($doc['phone'] ?? '1900-1234'); ?>">
                    <i class="fas fa-phone"></i>
                    Gọi ngay
                </a>
            </div>
        </div>
    </div>
    
    <script>
        console.log('🏥 View Doctor with Base64 Embedded Images loaded');
        console.log('Doctor ID: <?php echo $doctorId; ?>');
        console.log('Has real image: <?php echo $hasRealImage ? "true" : "false"; ?>');
        console.log('Base64 image length: <?php echo $base64Image ? strlen($base64Image) : 0; ?> chars');
        
        // Performance monitoring
        window.addEventListener('load', function() {
            console.log('✅ Page fully loaded with embedded images');
            
            // Add entrance animations
            const cards = document.querySelectorAll('.detail-card');
            const buttons = document.querySelectorAll('.btn');
            
            // Animate cards on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.style.opacity = '1';
                            entry.target.style.transform = 'translateY(0)';
                        }, index * 100);
                    }
                });
            }, observerOptions);
            
            // Initially hide cards and buttons
            [...cards, ...buttons].forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                observer.observe(element);
            });
            
            // Add click tracking for buttons
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const action = this.textContent.trim();
                    console.log(`🔘 Button clicked: ${action}`);
                    
                    // Add ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.3);
                        border-radius: 12px;
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                    `;
                    
                    this.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 600);
                });
            });
        });
        
        // Add ripple animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            
            .btn {
                position: relative;
                overflow: hidden;
            }
        `;
        document.head.appendChild(style);
        
        // Phone number formatting and validation
        const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
        phoneLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const phone = this.getAttribute('href').replace('tel:', '');
                console.log(`📞 Calling: ${phone}`);
            });
        });
        
        // Email link tracking
        const emailLinks = document.querySelectorAll('a[href^="mailto:"]');
        emailLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const email = this.getAttribute('href').replace('mailto:', '');
                console.log(`📧 Emailing: ${email}`);
            });
        });
    </script>
    
    <!-- Chatbot AI Widget -->
    <?php include 'chatbot_widget.php'; ?>
</body>
</html>
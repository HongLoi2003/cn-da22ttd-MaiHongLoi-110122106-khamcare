<?php
session_start();

// Helpers
function json_response($data, $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function build_process_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $dir = rtrim(dirname($_SERVER['REQUEST_URI']), '/\\');
    return $scheme . '://' . $host . $dir . '/process_payment.php';
}

// CSRF token for form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Handle JSON API (raw POST JSON) or AJAX
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (strpos($contentType, 'application/json') !== false || isset($_POST['api']) && $_POST['api'] == '1')) {
    // Read input (JSON or form-data forwarded as API)
    $input = [];
    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? [];
    } else {
        $input = $_POST;
    }

    // Basic validation
    $amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 
             (isset($input['amount']) ? (float)$input['amount'] : 0.0);
    $order_id = trim($input['order_id'] ?? '');
    $currency = strtoupper(trim($input['currency'] ?? 'VND'));
    if ($amount <= 0 || $order_id === '') {
        json_response(['success' => false, 'message' => 'order_id và amount hợp lệ là bắt buộc'], 400);
    }

    $doctor_name = trim($_GET['doctor_name'] ?? '');
    $specialty = trim($_GET['specialty'] ?? '');

    // Build payload to process_payment.php
    $payload = [
        'order_id' => $order_id,
        'amount' => $amount,
        'currency' => $currency,
        'description' => trim($input['description'] ?? ''),
        'customer_name' => trim($input['customer_name'] ?? ''),
        'customer_email' => trim($input['customer_email'] ?? ''),
    ];

    // Forward to process_payment.php using server-side POST JSON
    $url = build_process_url();
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    $json = json_encode($payload);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json)
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        json_response(['success' => false, 'message' => 'Không thể liên hệ process_payment.php', 'error' => $err], 502);
    }

    // Try to pass-through JSON response if any
    $decoded = json_decode($resp, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        json_response($decoded, $httpCode ?: 200);
    } else {
        header('Content-Type: text/plain; charset=utf-8');
        http_response_code($httpCode ?: 200);
        echo $resp;
        exit;
    }
}
// Lấy thông tin từ query parameters hoặc POST data
$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 
                 (isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0);
$doctor_name = $_GET['doctor_name'] ?? $_POST['doctor_name'] ?? '';
$specialty = $_GET['specialty'] ?? $_POST['specialty'] ?? '';
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 
         (isset($_POST['amount']) ? (float)$_POST['amount'] : 0);

// Nếu có appointment_id, lấy thông tin từ database
if ($appointment_id && !$doctor_name) {
    require_once 'db_config.php';
    $appointmentInfo = getAppointmentWithDoctorInfo($appointment_id);
    if ($appointmentInfo) {
        $doctor_name = $appointmentInfo['doctor_name'];
        $specialty = $appointmentInfo['specialty_name'];
        $amount = $appointmentInfo['consultation_fee'];
    }
}

// Nếu vẫn chưa có amount, set mặc định
if ($amount <= 0) {
    $amount = 300000; // Giá mặc định
}

// Data for UI (sample banks)
$banks = [
    'VCB' => [
        'name' => 'VietComBank',
        'account' => '9365958612',
        'account_name' => 'Mai Hong Loi',
        'amount' => $amount, // Use passed amount instead of fixed value
        'status' => 'ok',
        'logo' => "image/NDTH-Vietcombank-3-min.png",
        'qr_image' => "image\VCB.png.png"  // QR code for VietComBank
    ],
    'MB' => [
        'name' => 'MBBank',
        'account' => '1234567890',
        'account_name' => 'Mai Hong loi',
        'amount' => $amount, // Use passed amount instead of fixed value
        'status' => 'ok',
        'logo' => "image\mbbank-logo-5.png",
        'qr_image' => "image\QR.PNG.png.png"    // QR code for MB Bank
    ],
    'SACOMBANK' => [
        'name' => 'Sacombank',
        'account' => '',
        'account_name' => '',
        'amount' => 100000,
        'status' => 'maintenance',
        'logo' => "image\images.png"
    ],
    'VIETBANK' => [
        'name' => 'Vietbank',
        'account' => '',
        'account_name' => '',
        'amount' => 100000,
        'status' => 'maintenance',
        'logo' => "image\images (1).png"
    ],
];

$defaultBankKey = 'VCB';
$orderSample = 'ORD' . time();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Thanh toán - Chuyển khoản</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        
        
        

        :root {
            /* Modern Color Palette */
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
                radial-gradient(circle at 20% 80%, rgba(14, 165, 233, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.2) 0%, transparent 50%),
                linear-gradient(135deg, #0c4a6e 0%, #0369a1 50%, #0284c7 100%);
            background-attachment: fixed;
            color: var(--gray-800);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            min-height: 100vh;
            padding: 24px;
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
                radial-gradient(circle at 25px 25px, rgba(255, 255, 255, 0.08) 2px, transparent 2px),
                radial-gradient(circle at 75px 75px, rgba(255, 255, 255, 0.05) 1px, transparent 1px);
            background-size: 100px 100px, 50px 50px;
            pointer-events: none;
            z-index: -1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }

        /* Header */
        h1 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color: white;
            margin: 0 0 32px;
            font-size: 2.5rem;
            font-weight: 800;
            text-align: center;
            position: relative;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: 2px;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.5);
        }

        /* Payment Details Card */
        .payment-details {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-xl);
            padding: 28px;
            margin-bottom: 32px;
            box-shadow: 
                0 20px 50px rgba(0, 0, 0, 0.3),
                0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: relative;
            overflow: hidden;
        }

        .payment-details::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0ea5e9, #06b6d4, #10b981);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        }

        .payment-details h3 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            color: #0369a1;
            margin: 0 0 16px;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .payment-details h3::before {
            content: '💳';
            font-size: 1.2em;
        }

        .payment-details > div {
            margin: 8px 0;
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--gray-700);
        }

        .payment-details strong {
            color: var(--gray-800);
            font-weight: 700;
        }

        /* Bank Cards */
        .cards {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .bank-card {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-lg);
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            min-width: 140px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: var(--transition);
            box-shadow: 
                0 8px 20px rgba(0, 0, 0, 0.2),
                0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .bank-card:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 12px 30px rgba(0, 0, 0, 0.25),
                0 6px 15px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 1);
        }

        .bank-card img {
            max-width: 100%;
            height: 50px;
            object-fit: contain;
            opacity: 0.9;
            transition: var(--transition);
        }

        .bank-card:hover img {
            opacity: 1;
            transform: scale(1.05);
        }

        .bank-card.active {
            border: 3px solid #10b981;
            box-shadow: 
                0 12px 30px rgba(16, 185, 129, 0.4),
                0 6px 15px rgba(0, 0, 0, 0.2);
            background: linear-gradient(145deg, rgba(16, 185, 129, 0.1) 0%, rgba(255,255,255,1) 100%);
            transform: translateY(-2px);
        }

        .bank-card .badge {
            position: absolute;
            right: 8px;
            top: 8px;
            background: var(--success);
            color: var(--white);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 600;
            box-shadow: var(--shadow);
        }

        .bank-card.maintenance {
            opacity: 0.5;
            cursor: not-allowed;
            background: var(--gray-100);
        }

        .bank-card.maintenance .badge {
            background: var(--danger);
        }

        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            margin-bottom: 32px;
        }

        /* Panel Styles */
        .panel {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 32px;
            border-radius: var(--radius-xl);
            box-shadow: 
                0 20px 50px rgba(0, 0, 0, 0.3),
                0 10px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: relative;
            overflow: hidden;
        }

        .panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        }

        /* QR Panel */
        .qr-panel strong {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 1.2rem;
            color: var(--gray-800);
            font-weight: 700;
        }

        .qr-wrap {
            text-align: center;
            margin-top: 24px;
        }

        .qr-wrap img {
            width: 240px;
            height: 240px;
            border: 8px solid var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            transition: var(--transition);
        }

        .qr-wrap img:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        /* Bank Details */
        .bank-details strong {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            font-size: 1.2rem;
            color: var(--gray-800);
            font-weight: 700;
        }

        .bank-details table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .bank-details tr {
            border-bottom: 1px solid rgba(14, 165, 233, 0.1);
            transition: var(--transition);
        }

        .bank-details tr:hover {
            background: rgba(14, 165, 233, 0.05);
        }

        .bank-details tr:last-child {
            border-bottom: none;
        }

        .bank-details td {
            padding: 16px 12px;
            vertical-align: middle;
            font-weight: 500;
        }

        .bank-details .label {
            color: var(--gray-600);
            width: 140px;
            font-weight: 600;
        }

        .amount {
            color: var(--danger);
            font-weight: 800;
            font-size: 1.1rem;
        }

        /* Buttons */
        .copy-btn {
            background: var(--gradient-primary);
            color: var(--white);
            padding: 8px 16px;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .copy-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
        }

        .copy-btn:hover {
            background: var(--gradient-accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .copy-btn:hover::before {
            left: 100%;
        }

        .copy-btn.small {
            padding: 6px 12px;
            font-size: 13px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            border: none;
            padding: 16px 32px;
            border-radius: var(--radius-lg);
            cursor: pointer;
            font-weight: 700;
            font-size: 1.1rem;
            transition: var(--transition);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            font-family: inherit;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: var(--gradient-accent);
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Form Styles */
        form .row {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-top: 16px;
        }

        form .row label {
            width: 140px;
            font-weight: 600;
            color: var(--gray-700);
        }

        input[type=text], 
        input[type=number], 
        input[type=email] {
            padding: 12px 16px;
            border: 2px solid rgba(14, 165, 233, 0.2);
            border-radius: var(--radius-lg);
            width: 100%;
            font-size: 1rem;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            color: var(--gray-800);
            transition: var(--transition);
            font-family: inherit;
        }

        input[type=text]:focus, 
        input[type=number]:focus, 
        input[type=email]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
            transform: translateY(-2px);
            background: var(--white);
        }

        input[type=text]:hover, 
        input[type=number]:hover, 
        input[type=email]:hover {
            border-color: var(--primary-light);
            transform: translateY(-1px);
        }

        input::placeholder {
            color: var(--gray-500);
            font-weight: 400;
        }

        /* Footer Note */
        .footer-note {
            font-size: 14px;
            color: var(--gray-600);
            margin-top: 16px;
            font-weight: 500;
            line-height: 1.5;
        }

        /* Toast Notification */
        #toast {
            position: fixed;
            right: 30px;
            top: 30px;
            min-width: 320px;
            max-width: 90vw;
            padding: 16px 20px;
            border-radius: var(--radius-lg);
            color: var(--white);
            font-weight: 600;
            display: none;
            z-index: 9999;
            box-shadow: var(--shadow-xl);
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

        #toast.success {
            background: var(--gradient-accent);
            border-color: var(--accent-light);
        }

        #toast.error {
            background: linear-gradient(135deg, var(--danger) 0%, #f87171 100%);
            border-color: #fca5a5;
        }

        #toast::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .hidden {
            display: none;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .container {
                padding: 0 20px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 24px;
            }

            h1 {
                font-size: 2rem;
            }

            .panel {
                padding: 24px;
            }

            .qr-wrap img {
                width: 200px;
                height: 200px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .cards {
                overflow-x: auto;
                justify-content: flex-start;
                padding-bottom: 8px;
            }

            .bank-card {
                min-width: 120px;
                height: 70px;
                padding: 16px;
            }

            .bank-card img {
                height: 40px;
            }

            h1 {
                font-size: 1.8rem;
                margin-bottom: 24px;
            }

            .payment-details {
                padding: 20px;
            }

            .panel {
                padding: 20px;
            }

            .qr-wrap img {
                width: 180px;
                height: 180px;
            }

            form .row {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }

            form .row label {
                width: auto;
            }

            #toast {
                right: 20px;
                top: 20px;
                min-width: 280px;
                padding: 14px 18px;
                font-size: 0.95rem;
            }
        }

        @media (max-width: 480px) {
            .payment-details h3 {
                font-size: 1.3rem;
            }

            .bank-details .label {
                width: 100px;
            }

            .bank-details td {
                padding: 12px 8px;
            }

            .qr-wrap img {
                width: 160px;
                height: 160px;
            }

            .btn-primary {
                padding: 14px 24px;
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
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
        
        /* Success Message Styles */
        .success-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }
        
        .success-animation {
            margin-bottom: 2rem;
        }
        
        .checkmark-circle {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            animation: scaleIn 0.6s ease-out;
        }
        
        .checkmark {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: white;
            position: relative;
            animation: checkmarkPulse 1s ease-out;
        }
        
        .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            font-weight: bold;
            color: #14b8a6;
            animation: checkmarkAppear 0.8s ease-out 0.3s both;
        }
        
        .success-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .success-details {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            max-width: 600px;
            width: 100%;
        }
        
        .appointment-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1rem 0;
            text-align: left;
        }
        
        .next-steps {
            margin-top: 1.5rem;
        }
        
        .next-steps h3 {
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .next-steps ul {
            list-style: none;
            padding: 0;
            text-align: left;
        }
        
        .next-steps li {
            padding: 0.5rem 0;
            font-size: 1.1rem;
        }
        
        .countdown {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .countdown p {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        #countdown {
            font-weight: bold;
            font-size: 1.5rem;
            color: #fef3c7;
        }
        
        .btn-home {
            display: inline-block;
            background: white;
            color: #10b981;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            color: #059669;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        
        @keyframes checkmarkPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        @keyframes checkmarkAppear {
            from { opacity: 0; transform: translate(-50%, -50%) scale(0); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .success-title {
                font-size: 2rem;
            }
            
            .success-details {
                padding: 1.5rem;
            }
            
            .checkmark-circle {
                width: 100px;
                height: 100px;
            }
            
            .checkmark {
                width: 50px;
                height: 50px;
            }
            
            .checkmark::after {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Thanh toán bằng <strong>Chuyển khoản</strong></h1>

    <!-- bank selection -->
    <div class="cards" id="bankList">
        <?php foreach($banks as $k => $b): ?>
            <div class="bank-card <?=($k===$defaultBankKey ? 'active' : '')?> <?=($b['status']!=='ok' ? 'maintenance' : '')?>" data-key="<?=htmlspecialchars($k)?>">
                <img src="<?=htmlspecialchars($b['logo'])?>" alt="<?=htmlspecialchars($b['name'])?>">
                <?php if($b['status']!=='ok'): ?>
                    <span class="badge" style="background:#e74c3c">Bảo trì</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="grid">
        <!-- left: QR -->
        <div class="panel qr-panel">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <strong> Chuyển khoản bằng mã QR</strong>
                <small class="footer-note">Mở app ngân hàng & quét QRCode</small>
            </div>
            <div class="qr-wrap">
                <img id="qrImage" src="image/QR.PNG.png.png" alt="QR code" style="width:220px;height:220px;border:8px solid #fff;box-shadow:0 4px 20px rgba(0,0,0,.06)">
                <div style="margin-top:12px">
                    <button id="downloadQr" class="btn-primary">Tải Xuống Mã QR</button>
                </div>
                <div class="footer-note">napas 247 | <span id="qrBankName"></span></div>
            </div>
        </div>

        <!-- right: bank details -->
        <div class="panel bank-details">
            <div style="margin-bottom:12px;display:flex;justify-content:space-between;align-items:center">
                <strong>Cách 2: Chuyển khoản thủ công theo thông tin</strong>
                <div style="font-size:14px;color:#666">Ngân hàng: <span id="bankTitle"></span></div>
            </div>

            <table>
                <tbody>
                    <tr>
                        <td class="label">Số TK</td>
                        <td id="accNumber">-</td>
                        <td style="width:120px"><button class="copy-btn small" data-copy-target="accNumber">Sao Chép</button></td>
                    </tr>
                    <tr>
                        <td class="label">Chủ TK</td>
                        <td id="accName">-</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class="label">Số tiền</td>
                        <td id="accAmount" class="amount">- đ</td>
                        <td><button class="copy-btn small" data-copy-target="accAmount">Sao Chép</button></td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top:14px">
                <form id="paymentForm" method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">
                    <input type="hidden" name="order_id" id="order_id" value="<?=htmlspecialchars($orderSample)?>">
                    <div class="row">
                        <label style="width:120px">Order ID</label>
                        <input type="text" name="order_id_text" id="order_id_text" value="<?=htmlspecialchars($orderSample)?>">
                    </div>
                    <div class="row">
                        <label style="width:120px">Số tiền (VND)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="<?= $amount ?>" readonly>
                    </div>
                    <div class="row" style="justify-content:flex-end">
                        <button type="submit" class="btn-primary">Tôi đã chuyển tiền</button>
                    </div>
                </form>
            </div>

            <div class="footer-note">Lưu ý: Hãy chuyển đúng số tiền và nội dung để tự động xác nhận.</div>
        </div>
    </div>

    <?php if ($doctor_name || $specialty || $amount > 0): ?>
    <div class="payment-details">
        <h3>Chi tiết thanh toán</h3>
        <?php if ($appointment_id): ?>
        <div><strong>Mã lịch khám:</strong> APPT<?= htmlspecialchars($appointment_id) ?></div>
        <?php endif; ?>
        <?php if ($doctor_name): ?>
        <div><strong>Bác sĩ:</strong> <?= htmlspecialchars($doctor_name) ?></div>
        <?php endif; ?>
        <?php if ($specialty): ?>
        <div><strong>Chuyên khoa:</strong> <?= htmlspecialchars($specialty) ?></div>
        <?php endif; ?>
        <div><strong>Phí khám:</strong> <span style="color: var(--danger); font-size: 1.2em;"><?= number_format($amount, 0, ',', '.') ?> đ</span></div>
        <div style="font-size: 0.9em; color: var(--text-secondary); margin-top: 8px;">
            <i class="fas fa-info-circle"></i> Vui lòng chuyển khoản đúng số tiền để hệ thống tự động xác nhận thanh toán
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- toast element -->
<div id="toast" role="status" aria-live="polite"></div>

<script>
(function(){
    var banks = <?=json_encode($banks, JSON_UNESCAPED_UNICODE)?>;
    var current = '<?=addslashes($defaultBankKey)?>';

    function formatVND(n){
        if (!n && n !== 0) return '-';
        return Number(n).toLocaleString('vi-VN');
    }

    function buildQrUrl(bank){
        return bank.qr_image || 'image/QR.PNG.png';
    }

    function render(bankKey){
        var bank = banks[bankKey];
        if (!bank) return;
        current = bankKey;
        document.querySelectorAll('.bank-card').forEach(function(el){
            el.classList.toggle('active', el.getAttribute('data-key')===bankKey);
        });
        document.getElementById('bankTitle').textContent = bank.name || '-';
        document.getElementById('accNumber').textContent = bank.account || '-';
        document.getElementById('accName').textContent = bank.account_name || '-';
        document.getElementById('accAmount').textContent = (bank.amount ? formatVND(bank.amount) + ' đ' : '-');
        document.getElementById('qrImage').src = buildQrUrl(bank);
        document.getElementById('qrBankName').textContent = bank.name || '';
        
        // Update amount input to match bank amount
        var amtInput = document.getElementById('amount');
        if (bank.amount) amtInput.value = bank.amount;
    }

    // toast helper
    function showToast(msg, success){
        var t = document.getElementById('toast');
        t.textContent = msg;
        t.className = success ? 'success' : 'error';
        t.style.display = 'block';
        clearTimeout(t._timer);
        t._timer = setTimeout(function(){ t.style.display = 'none'; }, 3500);
    }
    
    // Success message
    function showSuccessMessage(){
        var successHtml = `
            <div class="success-container">
                <div class="success-animation">
                    <div class="checkmark-circle">
                        <div class="checkmark"></div>
                    </div>
                </div>
                <h1 class="success-title">🎉 Đặt lịch và thanh toán thành công!</h1>
                <div class="success-details">
                    <p><strong>Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!</strong></p>
                    <div class="appointment-info">
                        ${document.querySelector('.payment-details') ? document.querySelector('.payment-details').innerHTML : ''}
                    </div>
                    <div class="next-steps">
                        <h3>📋 Bước tiếp theo:</h3>
                        <ul>
                            <li>✅ Lịch hẹn đã được gửi đến bác sĩ</li>
                            <li>📧 Bạn sẽ nhận được email xác nhận</li>
                            <li>📱 SMS nhắc nhở trước giờ khám</li>
                            <li>👨‍⚕️ Bác sĩ sẽ liên hệ xác nhận</li>
                        </ul>
                    </div>
                </div>
                <div class="countdown">
                    <p>Tự động chuyển về trang chủ sau <span id="countdown">5</span> giây...</p>
                    <a href="TrangChu.php" class="btn-home">🏠 Về trang chủ ngay</a>
                </div>
            </div>
        `;
        
        document.body.innerHTML = successHtml;
        
        // Countdown timer
        var count = 5;
        var countdownEl = document.getElementById('countdown');
        var timer = setInterval(function(){
            count--;
            if (countdownEl) countdownEl.textContent = count;
            if (count <= 0) {
                clearInterval(timer);
            }
        }, 1000);
    }

    // initialize
    render(current);

    // bank click
    document.getElementById('bankList').addEventListener('click', function(ev){
        var card = ev.target.closest('.bank-card');
        if(!card) return;
        var key = card.getAttribute('data-key');
        if(!key) return;
        render(key);
    });

    // copy buttons
    document.addEventListener('click', function(e){
        var btn = e.target.closest('.copy-btn');
        if (!btn) return;
        var targetId = btn.getAttribute('data-copy-target');
        var el = document.getElementById(targetId);
        if (!el) return;
        var text = el.textContent || el.value || '';
        navigator.clipboard.writeText(text.trim()).then(function(){
            var old = btn.textContent;
            btn.textContent = 'Đã sao chép';
            setTimeout(function(){ btn.textContent = old; }, 1500);
        }).catch(function(){
            alert('Không thể sao chép tự động. Hãy chọn và Ctrl+C.');
        });
    });

    // download QR
    document.getElementById('downloadQr').addEventListener('click', function(){
        var img = document.getElementById('qrImage');
        var link = document.createElement('a');
        link.href = img.src;
        link.download = current + '_qr.png';
        document.body.appendChild(link);
        link.click();
        link.remove();
    });

    // form submit: send AJAX to payment.php (JSON) and show toast on result
    var form = document.getElementById('paymentForm');
    form.addEventListener('submit', function(e){
        e.preventDefault(); // prevent normal form submit

        var submitBtn = form.querySelector('button[type=submit]');
        submitBtn.disabled = true;

        // prepare payload
        var payload = {
            order_id: document.getElementById('order_id_text').value || document.getElementById('order_id').value,
            amount: parseFloat(document.getElementById('amount').value || 0),
            currency: 'VND'
        };

        // Send AJAX request
        fetch(location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        }).then(function(resp){
            return resp.json().then(function(data){ 
                // Cập nhật trạng thái thanh toán trong database
                var appointmentId = <?= json_encode((int)$appointment_id) ?>;
                console.log('Appointment ID:', appointmentId);
                
                if (appointmentId && appointmentId > 0) {
                    return fetch('update_payment_status.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            appointment_id: appointmentId,
                            payment_status: 'da_thanh_toan',
                            amount: payload.amount
                        })
                    }).then(function(res) {
                        return res.json();
                    }).then(function(result) {
                        console.log('Cập nhật thanh toán:', result);
                    }).catch(function(err){
                        console.log('Lỗi cập nhật trạng thái:', err);
                    });
                }
            }).then(function() {
                // Hide the payment form
                document.querySelector('.container').style.display = 'none';
                
                // Show success message
                showSuccessMessage();
                
                // Redirect after 5 seconds
                setTimeout(function(){
                    window.location.href = 'TrangChu.php'; 
                }, 5000);
            });
        }).catch(function(err){
            showToast('Có lỗi xảy ra: ' + err.message, false);
            submitBtn.disabled = false;
        });
    });
})();
</script>
</body>
</html>

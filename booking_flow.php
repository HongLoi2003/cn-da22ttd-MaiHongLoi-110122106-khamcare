<?php
/**
 * Quy trình đặt lịch khám hoàn chỉnh
 * Bước 1: Đặt lịch khám → Bước 2: Cập nhật hồ sơ → Bước 3: Thanh toán → Bước 4: Thông báo thành công
 */

session_start();
require_once 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: TaiKhoan.php?redirect=booking_flow.php');
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    die('Lỗi kết nối database');
}

// Lấy thông tin user
$stmt = $pdo->prepare("SELECT id, ten_dang_nhap as username, email, ho_ten as full_name, so_dien_thoai as phone, 
                              ngay_sinh as date_of_birth, gioi_tinh as gender, dia_chi as address,
                              vai_tro as role, trang_thai as status
                       FROM nguoi_dung WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    die('Không tìm thấy thông tin người dùng');
}

// Xử lý các bước
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;

// Lấy thông tin bác sĩ nếu có
$doctor = null;
if ($doctor_id) {
    $stmt = $pdo->prepare("SELECT b.*, b.ho_ten as full_name, c.ten AS specialty_name 
                           FROM bac_si b 
                           JOIN nguoi_dung n ON b.nguoi_dung_id = n.id 
                           LEFT JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id 
                           WHERE b.id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();
}

// Xử lý POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'book_appointment':
                // Bước 1: Tạo lịch hẹn tạm thời
                // Lấy tên bệnh nhân
                $stmt = $pdo->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = ? AND vai_tro = 'benh_nhan'");
                $stmt->execute([$_SESSION['user_id']]);
                $patient = $stmt->fetch();
                $patient_name = $patient ? $patient['ho_ten'] : 'Bệnh nhân';
                
                $appointment_data = [
                    'patient_id' => $_SESSION['user_id'],
                    'patient_name' => $patient_name,
                    'doctor_id' => (int)$_POST['doctor_id'],
                    'appointment_date' => $_POST['appointment_date'],
                    'appointment_time' => $_POST['appointment_time'],
                    'consultation_type' => $_POST['consultation_type'],
                    'chief_complaint' => $_POST['chief_complaint'],
                    'symptoms' => $_POST['symptoms'],
                    'status' => 'cho_xac_nhan'
                ];
                
                try {
                    $loai_tu_van = $appointment_data['consultation_type'] === 'online' ? 'truc_tuyen' : 'truc_tiep';
                    $stmt = $pdo->prepare("INSERT INTO lich_hen 
                                           (benh_nhan_id, ten_benh_nhan, bac_si_id, ngay_hen, gio_hen, 
                                            loai_tu_van, ly_do_kham, ghi_chu, trang_thai) 
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $appointment_data['patient_id'],
                        $appointment_data['patient_name'],
                        $appointment_data['doctor_id'],
                        $appointment_data['appointment_date'],
                        $appointment_data['appointment_time'],
                        $loai_tu_van,
                        $appointment_data['chief_complaint'],
                        $appointment_data['symptoms'],
                        $appointment_data['status']
                    ]);
                    
                    $appointment_id = $pdo->lastInsertId();
                    $_SESSION['temp_appointment_id'] = $appointment_id;
                    
                    // Chuyển đến bước 2: Cập nhật hồ sơ
                    header("Location: booking_flow.php?step=2&doctor_id={$appointment_data['doctor_id']}");
                    exit;
                } catch (Exception $e) {
                    $error = "Lỗi khi tạo lịch hẹn: " . $e->getMessage();
                }
                break;
                
            case 'update_profile':
                // Bước 2: Cập nhật hồ sơ bệnh nhân
                try {
                    $stmt = $pdo->prepare("UPDATE nguoi_dung SET 
                                           ho_ten = ?, so_dien_thoai = ?, ngay_sinh = ?, 
                                           gioi_tinh = ?, dia_chi = ?, lien_he_khan_cap = ?, 
                                           tien_su_benh = ?
                                           WHERE id = ?");
                    $stmt->execute([
                        $_POST['full_name'],
                        $_POST['phone'],
                        $_POST['date_of_birth'],
                        $_POST['gender'],
                        $_POST['address'],
                        $_POST['emergency_contact'] ?? null,
                        $_POST['medical_history'] ?? null,
                        $_SESSION['user_id']
                    ]);
                    
                    // Chuyển đến bước 3: Thanh toán
                    header("Location: booking_flow.php?step=3&doctor_id={$doctor_id}");
                    exit;
                } catch (Exception $e) {
                    $error = "Lỗi khi cập nhật hồ sơ: " . $e->getMessage();
                }
                break;
                
            case 'process_payment':
                // Bước 3: Xử lý thanh toán
                $appointment_id = $_SESSION['temp_appointment_id'] ?? null;
                if (!$appointment_id) {
                    $error = "Không tìm thấy thông tin lịch hẹn";
                    break;
                }
                
                try {
                    // Cập nhật trạng thái lịch hẹn thành pending (đã thanh toán)
                    $stmt = $pdo->prepare("UPDATE appointments SET status = 'pending', payment_status = 'paid' WHERE id = ?");
                    $stmt->execute([$appointment_id]);
                    
                    // Tạo bản ghi thanh toán
                    $stmt = $pdo->prepare("INSERT INTO payments 
                                           (appointment_id, patient_id, amount, payment_method, status, transaction_id) 
                                           VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $appointment_id,
                        $_SESSION['user_id'],
                        $_POST['amount'],
                        $_POST['payment_method'],
                        'completed',
                        'TXN_' . time() . '_' . $appointment_id
                    ]);
                    
                    // Chuyển đến bước 4: Thông báo thành công
                    header("Location: booking_flow.php?step=4&appointment_id={$appointment_id}");
                    exit;
                } catch (Exception $e) {
                    $error = "Lỗi khi xử lý thanh toán: " . $e->getMessage();
                }
                break;
        }
    }
}

// Lấy thông tin lịch hẹn cho bước 4
$appointment = null;
if ($step == 4 && isset($_GET['appointment_id'])) {
    $stmt = $pdo->prepare("SELECT a.*, d.consultation_fee, u.full_name as doctor_name, s.name as specialty_name
                           FROM appointments a
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           LEFT JOIN specialties s ON d.specialty_id = s.id
                           WHERE a.id = ? AND a.patient_id = ?");
    $stmt->execute([$_GET['appointment_id'], $_SESSION['user_id']]);
    $appointment = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lịch khám - Bước <?php echo $step; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        
        .booking-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            margin: 2rem auto;
            max-width: 800px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e5e7eb;
            z-index: 1;
        }
        
        .step-indicator::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            height: 2px;
            background: var(--primary);
            z-index: 2;
            width: <?php echo (($step - 1) / 3) * 100; ?>%;
            transition: width 0.3s ease;
        }
        
        .step {
            background: white;
            border: 3px solid #e5e7eb;
            border-radius: 12px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: relative;
            z-index: 3;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .step.completed {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        }
        
        .doctor-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid var(--primary);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            border: none;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .success-animation {
            animation: bounceIn 0.8s ease-out;
        }
        
        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="booking-container p-4">
            <!-- Header -->
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Đặt lịch khám bệnh
                </h2>
                <p class="text-muted">Quy trình đặt lịch khám an toàn và tiện lợi</p>
            </div>
            
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                    <?php echo $step > 1 ? '<i class="fas fa-check"></i>' : '1'; ?>
                </div>
                <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">
                    <?php echo $step > 2 ? '<i class="fas fa-check"></i>' : '2'; ?>
                </div>
                <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">
                    <?php echo $step > 3 ? '<i class="fas fa-check"></i>' : '3'; ?>
                </div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                    <?php echo $step >= 4 ? '<i class="fas fa-check"></i>' : '4'; ?>
                </div>
            </div>
            
            <div class="text-center mb-4">
                <small class="text-muted">
                    <?php
                    $steps = [
                        1 => 'Đặt lịch khám',
                        2 => 'Cập nhật hồ sơ',
                        3 => 'Thanh toán',
                        4 => 'Hoàn thành'
                    ];
                    echo $steps[$step];
                    ?>
                </small>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Bước 1: Đặt lịch khám -->
            <?php if ($step == 1): ?>
                <div class="form-section">
                    <h4 class="mb-3">
                        <i class="fas fa-calendar-alt me-2 text-primary"></i>
                        Thông tin đặt lịch
                    </h4>
                    
                    <?php if ($doctor): ?>
                        <div class="doctor-card mb-4">
                            <div class="row align-items-center">
                                <div class="col-md-2 text-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <i class="fas fa-user-md fa-lg"></i>
                                    </div>
                                </div>
                                <div class="col-md-10">
                                    <h5 class="mb-1">BS. <?php echo htmlspecialchars($doctor['full_name']); ?></h5>
                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
                                    <p class="text-success fw-bold mb-0">
                                        <i class="fas fa-money-bill-wave me-1"></i>
                                        Phí khám: <?php echo number_format($doctor['consultation_fee'] ?? 200000, 0, ',', '.'); ?> VNĐ
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="book_appointment">
                        <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-calendar me-2"></i>Ngày khám
                                </label>
                                <input type="date" class="form-control" name="appointment_date" 
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-clock me-2"></i>Giờ khám
                                </label>
                                <select class="form-select" name="appointment_time" required>
                                    <option value="">Chọn giờ khám</option>
                                    <option value="08:00:00">08:00</option>
                                    <option value="09:00:00">09:00</option>
                                    <option value="10:00:00">10:00</option>
                                    <option value="11:00:00">11:00</option>
                                    <option value="14:00:00">14:00</option>
                                    <option value="15:00:00">15:00</option>
                                    <option value="16:00:00">16:00</option>
                                    <option value="17:00:00">17:00</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-laptop me-2"></i>Hình thức khám
                            </label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="consultation_type" 
                                               value="online" id="online" required>
                                        <label class="form-check-label" for="online">
                                            <i class="fas fa-video text-success me-2"></i>
                                            Tư vấn trực tuyến
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="consultation_type" 
                                               value="offline" id="offline" required>
                                        <label class="form-check-label" for="offline">
                                            <i class="fas fa-hospital text-primary me-2"></i>
                                            Khám tại phòng khám
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-stethoscope me-2"></i>Lý do khám
                            </label>
                            <input type="text" class="form-control" name="chief_complaint" 
                                   placeholder="Ví dụ: Đau đầu, khám tổng quát..." required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-notes-medical me-2"></i>Triệu chứng chi tiết
                            </label>
                            <textarea class="form-control" name="symptoms" rows="3" 
                                      placeholder="Mô tả chi tiết triệu chứng, thời gian xuất hiện..."></textarea>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-right me-2"></i>
                                Tiếp tục - Cập nhật hồ sơ
                            </button>
                        </div>
                    </form>
                </div>
            
            <!-- Bước 2: Cập nhật hồ sơ -->
            <?php elseif ($step == 2): ?>
                <div class="form-section">
                    <h4 class="mb-3">
                        <i class="fas fa-user-edit me-2 text-primary"></i>
                        Cập nhật hồ sơ bệnh nhân
                    </h4>
                    <p class="text-muted mb-4">Vui lòng cập nhật đầy đủ thông tin để bác sĩ có thể tư vấn tốt nhất</p>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user me-2"></i>Họ và tên
                                </label>
                                <input type="text" class="form-control" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-phone me-2"></i>Số điện thoại
                                </label>
                                <input type="tel" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-birthday-cake me-2"></i>Ngày sinh
                                </label>
                                <input type="date" class="form-control" name="date_of_birth" 
                                       value="<?php echo $user['date_of_birth'] ?? ''; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-venus-mars me-2"></i>Giới tính
                                </label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Chọn giới tính</option>
                                    <option value="male" <?php echo ($user['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Nam</option>
                                    <option value="female" <?php echo ($user['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Nữ</option>
                                    <option value="other" <?php echo ($user['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Khác</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ
                            </label>
                            <textarea class="form-control" name="address" rows="2" 
                                      placeholder="Địa chỉ chi tiết..."><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-phone-alt me-2"></i>Liên hệ khẩn cấp
                            </label>
                            <input type="text" class="form-control" name="emergency_contact" 
                                   placeholder="Tên và số điện thoại người thân..."
                                   value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-history me-2"></i>Tiền sử bệnh
                            </label>
                            <textarea class="form-control" name="medical_history" rows="2" 
                                      placeholder="Các bệnh đã mắc, phẫu thuật..."><?php echo htmlspecialchars($user['medical_history'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Dị ứng
                                </label>
                                <input type="text" class="form-control" name="allergies" 
                                       placeholder="Dị ứng thuốc, thực phẩm..."
                                       value="<?php echo htmlspecialchars($user['allergies'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-pills me-2"></i>Thuốc đang dùng
                                </label>
                                <input type="text" class="form-control" name="current_medications" 
                                       placeholder="Thuốc đang sử dụng..."
                                       value="<?php echo htmlspecialchars($user['current_medications'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="booking_flow.php?step=1&doctor_id=<?php echo $doctor_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-right me-2"></i>
                                Tiếp tục - Thanh toán
                            </button>
                        </div>
                    </form>
                </div>
            
            <!-- Bước 3: Thanh toán -->
            <?php elseif ($step == 3): ?>
                <div class="form-section">
                    <h4 class="mb-3">
                        <i class="fas fa-credit-card me-2 text-primary"></i>
                        Thanh toán
                    </h4>
                    
                    <?php if ($doctor): ?>
                        <div class="doctor-card mb-4">
                            <h6>Thông tin thanh toán</h6>
                            <div class="row">
                                <div class="col-md-8">
                                    <p class="mb-1"><strong>Bác sĩ:</strong> BS. <?php echo htmlspecialchars($doctor['full_name']); ?></p>
                                    <p class="mb-1"><strong>Chuyên khoa:</strong> <?php echo htmlspecialchars($doctor['specialty_name']); ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <h5 class="text-success mb-0">
                                        <?php echo number_format($doctor['consultation_fee'] ?? 200000, 0, ',', '.'); ?> VNĐ
                                    </h5>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="process_payment">
                        <input type="hidden" name="amount" value="<?php echo $doctor['consultation_fee'] ?? 200000; ?>">
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-wallet me-2"></i>Phương thức thanh toán
                            </label>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               value="momo" id="momo" required>
                                        <label class="form-check-label" for="momo">
                                            <i class="fas fa-mobile-alt text-danger me-2"></i>
                                            Ví MoMo
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               value="banking" id="banking" required>
                                        <label class="form-check-label" for="banking">
                                            <i class="fas fa-university text-primary me-2"></i>
                                            Chuyển khoản
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               value="cash" id="cash" required>
                                        <label class="form-check-label" for="cash">
                                            <i class="fas fa-money-bill text-success me-2"></i>
                                            Tiền mặt
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lưu ý:</strong> Sau khi thanh toán thành công, lịch hẹn sẽ được gửi đến bác sĩ để xác nhận.
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="booking_flow.php?step=2&doctor_id=<?php echo $doctor_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>
                                Thanh toán ngay
                            </button>
                        </div>
                    </form>
                </div>
            
            <!-- Bước 4: Thông báo thành công -->
            <?php elseif ($step == 4 && $appointment): ?>
                <div class="form-section text-center success-animation">
                    <div class="mb-4">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px;">
                            <i class="fas fa-check fa-2x"></i>
                        </div>
                    </div>
                    
                    <h3 class="text-success mb-3">Đặt lịch thành công!</h3>
                    <p class="text-muted mb-4">Cảm ơn bạn đã sử dụng dịch vụ. Thông tin lịch hẹn đã được gửi đến bác sĩ.</p>
                    
                    <div class="doctor-card mb-4">
                        <h6 class="mb-3">Thông tin lịch hẹn</h6>
                        <div class="row text-start">
                            <div class="col-md-6">
                                <p><strong>Mã lịch hẹn:</strong> #<?php echo str_pad($appointment['id'], 6, '0', STR_PAD_LEFT); ?></p>
                                <p><strong>Bác sĩ:</strong> BS. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                <p><strong>Chuyên khoa:</strong> <?php echo htmlspecialchars($appointment['specialty_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ngày khám:</strong> <?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?></p>
                                <p><strong>Giờ khám:</strong> <?php echo substr($appointment['appointment_time'], 0, 5); ?></p>
                                <p><strong>Hình thức:</strong> 
                                    <?php echo $appointment['consultation_type'] == 'online' ? 'Tư vấn trực tuyến' : 'Khám tại phòng khám'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-bell me-2"></i>
                        Bạn sẽ nhận được thông báo qua email và SMS khi bác sĩ xác nhận lịch hẹn.
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="TrangChu.php" class="btn btn-outline-primary">
                            <i class="fas fa-home me-2"></i>Về trang chủ
                        </a>
                        <a href="patient_dashboard.php" class="btn btn-primary">
                            <i class="fas fa-calendar-check me-2"></i>Xem lịch hẹn
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
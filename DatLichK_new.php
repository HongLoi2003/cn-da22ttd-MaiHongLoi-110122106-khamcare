<?php
/**
 * Luồng đặt lịch khám mới với 2 lựa chọn:
 * 1. Đã biết bác sĩ/chuyên khoa
 * 2. Chưa chắc - cần tư vấn theo triệu chứng
 */

session_start();
require_once __DIR__ . '/db_config.php';
require_once 'doctor_data_helper.php';
require_once 'doctor_id_mapping.php';

$conn = null;
try {
    $conn = getDBConnection();
} catch (Throwable $e) {
    $conn = false;
}

if (!$conn || !($conn instanceof PDO)) {
    http_response_code(500);
    echo "<div style='color:red;text-align:center;margin-top:40px'>❌ Lỗi kết nối cơ sở dữ liệu.</div>";
    exit;
}

// Lấy id bác sĩ từ query string (nếu có)
$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : null;
$mode = isset($_GET['mode']) ? $_GET['mode'] : null; // 'known' hoặc 'symptoms'
$suggested_specialty = isset($_GET['specialty_id']) ? intval($_GET['specialty_id']) : null;

// Convert View ID sang Database ID nếu cần
if ($doctor_id) {
    $doctor_id = normalizeToDatabaseId($doctor_id);
}

// Lấy thông tin bác sĩ nếu có
$doctor = null;
$doctors = [];
if ($doctor_id) {
    $stmt = $conn->prepare("SELECT d.*, u.full_name, s.name AS specialty_name, s.id AS specialty_id 
                           FROM doctors d 
                           JOIN users u ON d.user_id = u.id 
                           LEFT JOIN specialties s ON d.specialty_id = s.id 
                           WHERE d.id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($doctor) {
        $doctor['price'] = $doctor['consultation_fee'] ?? 300000;
    }
}

// Lấy danh sách bác sĩ
if (!$doctor) {
    $doctors = getTopDoctorsFromViewDoctor(18);
}

// Lấy danh sách chuyên khoa
$specialties = [];
$stmtSpec = $conn->query("SELECT id, name FROM specialties ORDER BY name");
$specialties = $stmtSpec->fetchAll(PDO::FETCH_ASSOC);

// Kiểm tra bệnh nhân đã đăng nhập
$patient_id = null;
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'patient') {
    $patient_id = $_SESSION['user_id'];
} elseif (isset($_SESSION['patient_id'])) {
    $patient_id = $_SESSION['patient_id'];
}

// Tạo user tạm nếu chưa đăng nhập
if (!$patient_id) {
    $tempUserStmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'patient')");
    $tempUsername = 'temp_' . time();
    $tempEmail = $tempUsername . '@temp.com';
    $tempUserStmt->execute([$tempUsername, $tempEmail, password_hash('temp123', PASSWORD_DEFAULT), 'Bệnh nhân tạm thời', '0123456789']);
    $patient_id = $conn->lastInsertId();
    $_SESSION['patient_id'] = $patient_id;
}

$message = "";
$bookingSuccess = false;
$createdAppointmentId = null;

// Danh sách triệu chứng phổ biến và mapping chuyên khoa
$symptomsList = [
    'headache' => ['name' => 'Đau đầu', 'specialties' => ['Thần kinh', 'Nội tổng quát']],
    'fever' => ['name' => 'Sốt', 'specialties' => ['Nội tổng quát', 'Nhiễm']],
    'cough' => ['name' => 'Ho kéo dài', 'specialties' => ['Hô hấp', 'Nội tổng quát']],
    'stomach_pain' => ['name' => 'Đau bụng', 'specialties' => ['Tiêu hóa', 'Nội tổng quát']],
    'breathing' => ['name' => 'Khó thở', 'specialties' => ['Hô hấp', 'Tim mạch']],
    'fatigue' => ['name' => 'Mệt mỏi', 'specialties' => ['Nội tổng quát', 'Tim mạch']],
    'chest_pain' => ['name' => 'Đau ngực', 'specialties' => ['Tim mạch', 'Hô hấp']],
    'joint_pain' => ['name' => 'Đau khớp', 'specialties' => ['Cơ xương khớp', 'Nội tổng quát']],
    'skin_issue' => ['name' => 'Vấn đề da', 'specialties' => ['Da liễu']],
    'eye_issue' => ['name' => 'Vấn đề mắt', 'specialties' => ['Mắt']],
    'ear_nose_throat' => ['name' => 'Tai mũi họng', 'specialties' => ['Tai mũi họng']],
    'mental_health' => ['name' => 'Sức khỏe tâm thần', 'specialties' => ['Tâm thần', 'Thần kinh']],
];

// Danh sách khung giờ
$timeSlots = [
    '07:00' => '7h - 8h Sáng',
    '08:00' => '8h - 9h Sáng',
    '09:00' => '9h - 10h Sáng',
    '10:00' => '10h - 11h Sáng',
    '13:00' => '13h - 14h Chiều',
    '14:00' => '14h - 15h Chiều',
    '15:00' => '15h - 16h Chiều',
    '16:00' => '16h - 17h Chiều',
];

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'analyze_symptoms') {
        // Phân tích triệu chứng và gợi ý chuyên khoa
        $selectedSymptoms = $_POST['symptoms'] ?? [];
        $otherSymptoms = trim($_POST['other_symptoms'] ?? '');
        
        // Tìm chuyên khoa phù hợp nhất
        $specialtyScores = [];
        foreach ($selectedSymptoms as $symptomKey) {
            if (isset($symptomsList[$symptomKey])) {
                foreach ($symptomsList[$symptomKey]['specialties'] as $spec) {
                    $specialtyScores[$spec] = ($specialtyScores[$spec] ?? 0) + 1;
                }
            }
        }
        
        // Sắp xếp theo điểm
        arsort($specialtyScores);
        $suggestedSpecialty = array_key_first($specialtyScores) ?? 'Nội tổng quát';
        
        // Lưu vào session
        $_SESSION['selected_symptoms'] = $selectedSymptoms;
        $_SESSION['other_symptoms'] = $otherSymptoms;
        $_SESSION['suggested_specialty'] = $suggestedSpecialty;
        
        // Redirect đến trang đặt lịch với chuyên khoa gợi ý
        header("Location: DatLichK_new.php?mode=symptoms&suggested=" . urlencode($suggestedSpecialty));
        exit;
    }
    
    if ($action === 'book_appointment') {
        $selectedDoctor = intval($_POST['doctor_id'] ?? $doctor_id);
        $appointment_date = trim($_POST['appointment_date'] ?? '');
        $appointment_time = trim($_POST['appointment_time'] ?? '');
        $consultation_type = in_array($_POST['consultation_type'] ?? 'offline', ['offline','online']) ? $_POST['consultation_type'] : 'offline';
        $notes = trim($_POST['notes'] ?? '');
        $symptoms_text = trim($_POST['symptoms_text'] ?? '');

        if (!$selectedDoctor || !$appointment_date || !$appointment_time) {
            $message = "<div class='alert alert-danger'>❌ Vui lòng chọn đầy đủ bác sĩ, ngày và giờ khám.</div>";
        } else {
            $patientCheck = $conn->prepare("SELECT full_name FROM users WHERE id = ? AND role = 'patient'");
            $patientCheck->execute([$patient_id]);
            $patientData = $patientCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$patientData) {
                $message = "<div class='alert alert-danger'>❌ Lỗi xác thực người dùng.</div>";
            } else {
                $patient_name = $patientData['full_name'];
                
                // Kiểm tra trùng lịch
                $check = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?");
                $check->execute([$selectedDoctor, $appointment_date, $appointment_time]);
                $count = (int)$check->fetchColumn();

                if ($count > 0) {
                    $message = "<div class='alert alert-danger'>❌ Bác sĩ đã có lịch vào khung giờ này.</div>";
                } else {
                    // Gộp triệu chứng vào notes
                    $fullNotes = $symptoms_text ? "Triệu chứng: " . $symptoms_text . "\n" . $notes : $notes;
                    
                    $stmt = $conn->prepare("
                        INSERT INTO appointments
                          (doctor_id, patient_id, patient_name, appointment_date, appointment_time, consultation_type, notes, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    $ok = $stmt->execute([$selectedDoctor, $patient_id, $patient_name, $appointment_date, $appointment_time, $consultation_type, $fullNotes]);

                    if ($ok) {
                        $createdAppointmentId = (int)$conn->lastInsertId();
                        
                        // Lấy phí khám
                        $doctorStmt = $conn->prepare("SELECT d.consultation_fee FROM doctors d WHERE d.id = ?");
                        $doctorStmt->execute([$selectedDoctor]);
                        $doctorInfo = $doctorStmt->fetch(PDO::FETCH_ASSOC);
                        $consultationFee = $doctorInfo ? $doctorInfo['consultation_fee'] : 300000;
                        
                        // Cập nhật total_fee
                        $updateStmt = $conn->prepare("UPDATE appointments SET total_fee = ? WHERE id = ?");
                        $updateStmt->execute([$consultationFee, $createdAppointmentId]);
                        
                        $_SESSION['temp_appointment_id'] = $createdAppointmentId;
                        $_SESSION['temp_doctor_id'] = $selectedDoctor;
                        $_SESSION['temp_consultation_fee'] = $consultationFee;
                        
                        $bookingSuccess = true;
                        $message = "<div class='alert alert-success'>✅ Đặt lịch thành công! Mã: <strong>#" . $createdAppointmentId . "</strong></div>";
                    } else {
                        $message = "<div class='alert alert-danger'>❌ Có lỗi xảy ra.</div>";
                    }
                }
            }
        }
    }
}

// Lấy suggested specialty từ URL
$suggestedSpecialtyName = isset($_GET['suggested']) ? $_GET['suggested'] : null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt Lịch Khám Bệnh - KhamCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="shared-styles.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        :root {
            --primary: #0891b2;
            --primary-light: #06b6d4;
            --primary-dark: #0e7490;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --border: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --radius: 12px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #06b6d4 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .card {
            background: var(--bg-primary);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .logo-link {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--primary);
            font-size: 1.8rem;
            font-weight: 700;
        }

        .logo-link img {
            width: 50px;
            height: 50px;
            border-radius: 12px;
        }

        .page-title {
            text-align: center;
            color: var(--text-primary);
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        /* Choice Cards */
        .choice-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .choice-card {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, #fff 100%);
            border: 2px solid var(--border);
            border-radius: var(--radius);
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .choice-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .choice-card.active {
            border-color: var(--primary);
            background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%);
        }

        .choice-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .choice-card h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .choice-card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Symptoms Section */
        .symptoms-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .symptoms-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .symptoms-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .symptom-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: var(--bg-secondary);
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .symptom-item:hover {
            border-color: var(--primary-light);
            background: #ecfeff;
        }

        .symptom-item.selected {
            border-color: var(--primary);
            background: #cffafe;
        }

        .symptom-item input {
            display: none;
        }

        .symptom-item .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .symptom-item.selected .checkmark {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* Booking Form */
        .booking-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .booking-section.active {
            display: block;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 15px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
        }

        .form-group input[readonly] {
            background: var(--bg-secondary);
            color: var(--text-secondary);
        }

        /* Suggestion Box */
        .suggestion-box {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border: 2px solid #10b981;
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
        }

        .suggestion-box h4 {
            color: #065f46;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .suggestion-box p {
            color: #047857;
        }

        .suggestion-box .specialty-tag {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 10px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(8, 145, 178, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
            color: white;
        }

        .btn-outline {
            background: white;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* Success Actions */
        .success-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .success-actions a {
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .choice-section {
                grid-template-columns: 1fr;
            }
            
            .symptoms-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .symptoms-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- Header -->
            <div class="header">
                <a href="TrangChu.php" class="logo-link">
                    <img src="image/logo.png.png" alt="Logo">
                    <span>KhamCare</span>
                </a>
                <h1 class="page-title"><i class="fas fa-calendar-alt"></i> Đặt Lịch Khám Bệnh</h1>
                <div></div>
            </div>

            <?php echo $message; ?>

            <?php if (!$bookingSuccess): ?>
            
            <!-- BƯỚC 1: Chọn hình thức đặt lịch -->
            <?php if (!$mode): ?>
            <div class="choice-section" id="choiceSection">
                <div class="choice-card" onclick="selectMode('known')" id="choiceKnown">
                    <div class="icon">🩺</div>
                    <h3>Tôi đã biết bác sĩ / chuyên khoa</h3>
                    <p>Chọn trực tiếp bác sĩ hoặc chuyên khoa bạn muốn khám</p>
                </div>
                <div class="choice-card" onclick="selectMode('symptoms')" id="choiceSymptoms">
                    <div class="icon">🤔</div>
                    <h3>Tôi chưa chắc - cần tư vấn</h3>
                    <p>Mô tả triệu chứng để được gợi ý chuyên khoa phù hợp</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- TRƯỜNG HỢP 2: Chọn triệu chứng -->
            <div class="symptoms-section <?php echo ($mode === 'symptoms' && !$suggestedSpecialtyName) ? 'active' : ''; ?>" id="symptomsSection">
                <h3 style="margin-bottom: 20px; color: var(--text-primary);">
                    <i class="fas fa-clipboard-list" style="color: var(--primary);"></i>
                    Bạn đang gặp triệu chứng nào? <small style="color: var(--text-secondary);">(có thể chọn nhiều)</small>
                </h3>
                
                <form method="POST" id="symptomsForm">
                    <input type="hidden" name="action" value="analyze_symptoms">
                    
                    <div class="symptoms-grid">
                        <?php foreach ($symptomsList as $key => $symptom): ?>
                        <label class="symptom-item" onclick="toggleSymptom(this)">
                            <input type="checkbox" name="symptoms[]" value="<?php echo $key; ?>">
                            <span class="checkmark"><i class="fas fa-check"></i></span>
                            <span><?php echo htmlspecialchars($symptom['name']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label><i class="fas fa-edit"></i> Triệu chứng khác (nếu có):</label>
                        <textarea name="other_symptoms" rows="3" placeholder="Mô tả chi tiết triệu chứng của bạn..."></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline" onclick="goBack()">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tiếp tục đặt lịch
                        </button>
                        <button type="button" class="btn btn-success" onclick="goToConsultation()">
                            <i class="fas fa-comments"></i> Tôi cần tư vấn thêm
                        </button>
                    </div>
                </form>
            </div>

            <!-- Gợi ý chuyên khoa sau khi phân tích triệu chứng -->
            <?php if ($suggestedSpecialtyName): ?>
            <div class="suggestion-box">
                <h4><i class="fas fa-lightbulb"></i> Gợi ý từ hệ thống</h4>
                <p>Dựa trên triệu chứng của bạn, chúng tôi đề xuất:</p>
                <span class="specialty-tag">🩺 Chuyên khoa: <?php echo htmlspecialchars($suggestedSpecialtyName); ?></span>
                <p style="margin-top: 10px; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> Bạn có thể chọn bác sĩ khác nếu muốn
                </p>
            </div>
            <?php endif; ?>

            <!-- FORM ĐẶT LỊCH CHÍNH -->
            <div class="booking-section <?php echo ($mode === 'known' || $suggestedSpecialtyName || $doctor_id) ? 'active' : ''; ?>" id="bookingSection">
                <form method="POST" class="form-grid" id="bookingForm">
                    <input type="hidden" name="action" value="book_appointment">
                    <?php if (isset($_SESSION['selected_symptoms'])): ?>
                    <input type="hidden" name="symptoms_text" value="<?php echo htmlspecialchars(implode(', ', array_map(function($s) use ($symptomsList) { return $symptomsList[$s]['name'] ?? $s; }, $_SESSION['selected_symptoms']))); ?>">
                    <?php endif; ?>
                    
                    <!-- Chọn Bác sĩ -->
                    <div class="form-group">
                        <label><i class="fas fa-user-md"></i> Chọn Bác Sĩ:</label>
                        <select id="doctor_id" name="doctor_id" required>
                            <?php if ($doctor): ?>
                                <option value="<?php echo htmlspecialchars($doctor['id']); ?>">
                                    Dr. <?php echo htmlspecialchars($doctor['full_name']); ?> - <?php echo htmlspecialchars($doctor['specialty_name'] ?? ''); ?>
                                </option>
                            <?php else: ?>
                                <option value="">-- Chọn bác sĩ --</option>
                                <?php foreach ($doctors as $doc): 
                                    $databaseId = $doc['doctor_row_id'] ?? $doc['id'];
                                    $fullName = $doc['full_name'] ?? 'Unknown';
                                    $specialty = $doc['specialty_name'] ?? $doc['title'] ?? 'Chuyên khoa';
                                    $fee = $doc['consultation_fee'] ?? 300000;
                                    $rating = $doc['rating'] ?? 4.5;
                                    
                                    // Highlight bác sĩ theo chuyên khoa gợi ý
                                    $isRecommended = $suggestedSpecialtyName && stripos($specialty, $suggestedSpecialtyName) !== false;
                                ?>
                                <option value="<?php echo htmlspecialchars($databaseId); ?>" 
                                        data-fee="<?php echo $fee; ?>"
                                        data-specialty="<?php echo htmlspecialchars($specialty); ?>"
                                        data-rating="<?php echo $rating; ?>"
                                        <?php echo $isRecommended ? 'style="background:#ecfdf5;font-weight:bold;"' : ''; ?>>
                                    <?php echo $isRecommended ? '⭐ ' : ''; ?>Dr. <?php echo htmlspecialchars($fullName); ?> - <?php echo htmlspecialchars($specialty); ?> 
                                    (<?php echo number_format($fee, 0, ',', '.'); ?>đ)
                                </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Bác sĩ đã chọn -->
                    <div class="form-group">
                        <label><i class="fas fa-user-md"></i> Bác Sĩ Đã Chọn:</label>
                        <input type="text" id="doctor_name" readonly placeholder="Chọn bác sĩ...">
                    </div>

                    <!-- Chuyên khoa -->
                    <div class="form-group">
                        <label><i class="fas fa-stethoscope"></i> Chuyên Khoa:</label>
                        <input type="text" id="doctor_specialty" readonly placeholder="Chọn bác sĩ để xem chuyên khoa" style="color: #6366f1; font-weight: 600;">
                    </div>

                    <!-- Phí khám -->
                    <div class="form-group">
                        <label><i class="fas fa-money-bill-wave"></i> Phí Khám:</label>
                        <input type="text" id="consultation_fee" readonly style="color: #ef4444; font-weight: bold;">
                    </div>

                    <!-- Đánh giá -->
                    <div class="form-group">
                        <label><i class="fas fa-star"></i> Đánh Giá:</label>
                        <input type="text" id="doctor_rating" readonly>
                    </div>

                    <!-- Ngày khám -->
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Ngày Khám:</label>
                        <input type="date" id="appointment_date" name="appointment_date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <!-- Giờ khám -->
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Thời Gian:</label>
                        <select id="appointment_time" name="appointment_time" required>
                            <option value="">-- Chọn giờ khám --</option>
                            <?php foreach ($timeSlots as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Hình thức khám -->
                    <div class="form-group">
                        <label><i class="fas fa-notes-medical"></i> Hình Thức Khám:</label>
                        <select id="consultation_type" name="consultation_type" required>
                            <option value="offline">Khám trực tiếp tại phòng khám</option>
                            <option value="online">Tư vấn Online (Video call)</option>
                        </select>
                    </div>

                    <!-- Ghi chú -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-comment-medical"></i> Ghi chú thêm:</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Mô tả thêm về tình trạng của bạn..."><?php echo isset($_SESSION['other_symptoms']) ? htmlspecialchars($_SESSION['other_symptoms']) : ''; ?></textarea>
                    </div>

                    <!-- Buttons -->
                    <div class="btn-group full-width">
                        <?php if (!$doctor_id): ?>
                        <button type="button" class="btn btn-outline" onclick="goBack()">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Đặt Lịch Khám
                        </button>
                    </div>
                </form>
            </div>

            <?php else: ?>
            <!-- Đặt lịch thành công -->
            <?php 
            $appointmentStmt = $conn->prepare("
                SELECT a.*, u.full_name as doctor_name, s.name as specialty_name, d.consultation_fee
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                LEFT JOIN specialties s ON d.specialty_id = s.id
                WHERE a.id = ?
            ");
            $appointmentStmt->execute([$createdAppointmentId]);
            $appointmentInfo = $appointmentStmt->fetch(PDO::FETCH_ASSOC);
            ?>
            
            <div style="text-align: center; padding: 30px 0;">
                <div style="font-size: 4rem; margin-bottom: 20px;">🎉</div>
                <h2 style="color: var(--success); margin-bottom: 15px;">Đặt lịch thành công!</h2>
                <p style="color: var(--text-secondary); margin-bottom: 20px;">
                    Mã lịch hẹn: <strong>#<?php echo $createdAppointmentId; ?></strong>
                </p>
                
                <?php if ($appointmentInfo): ?>
                <div style="background: var(--bg-secondary); border-radius: var(--radius); padding: 20px; max-width: 400px; margin: 0 auto 20px; text-align: left;">
                    <p><strong>Bác sĩ:</strong> Dr. <?php echo htmlspecialchars($appointmentInfo['doctor_name']); ?></p>
                    <p><strong>Chuyên khoa:</strong> <?php echo htmlspecialchars($appointmentInfo['specialty_name']); ?></p>
                    <p><strong>Ngày khám:</strong> <?php echo date('d/m/Y', strtotime($appointmentInfo['appointment_date'])); ?></p>
                    <p><strong>Giờ khám:</strong> <?php echo $appointmentInfo['appointment_time']; ?></p>
                    <p><strong>Phí khám:</strong> <span style="color: var(--danger); font-weight: bold;"><?php echo number_format($appointmentInfo['consultation_fee'], 0, ',', '.'); ?>đ</span></p>
                </div>
                <?php endif; ?>
                
                <div class="success-actions">
                    <a href="update_profile.php?step=profile&appointment_id=<?php echo $createdAppointmentId; ?>" class="btn btn-primary">
                        <i class="fas fa-user-edit"></i> Cập nhật hồ sơ
                    </a>
                    <?php if ($appointmentInfo): ?>
                    <a href="payment.php?appointment_id=<?php echo $createdAppointmentId; ?>&order_id=APPT<?php echo $createdAppointmentId; ?>&doctor_name=<?php echo urlencode($appointmentInfo['doctor_name']); ?>&specialty=<?php echo urlencode($appointmentInfo['specialty_name']); ?>&amount=<?php echo $appointmentInfo['consultation_fee']; ?>" class="btn btn-success">
                        <i class="fas fa-credit-card"></i> Thanh toán <?php echo number_format($appointmentInfo['consultation_fee'], 0, ',', '.'); ?>đ
                    </a>
                    <?php endif; ?>
                    <a href="TrangChu.php" class="btn btn-outline">
                        <i class="fas fa-home"></i> Về trang chủ
                    </a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

<script>
// Chọn mode đặt lịch
function selectMode(mode) {
    if (mode === 'known') {
        window.location.href = 'DatLichK_new.php?mode=known';
    } else if (mode === 'symptoms') {
        window.location.href = 'DatLichK_new.php?mode=symptoms';
    }
}

// Toggle triệu chứng
function toggleSymptom(element) {
    element.classList.toggle('selected');
    const checkbox = element.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
}

// Quay lại
function goBack() {
    window.location.href = 'DatLichK_new.php';
}

// Chuyển đến trang tư vấn chi tiết
function goToConsultation() {
    // Chuyển đến trang tư vấn triệu chứng chi tiết
    window.location.href = 'symptom_consultation.php';
}

// Cập nhật thông tin bác sĩ khi chọn
document.addEventListener('DOMContentLoaded', function() {
    const doctorSelect = document.getElementById('doctor_id');
    const doctorName = document.getElementById('doctor_name');
    const doctorSpecialty = document.getElementById('doctor_specialty');
    const doctorRating = document.getElementById('doctor_rating');
    const consultationFee = document.getElementById('consultation_fee');
    
    if (doctorSelect) {
        doctorSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            
            if (opt && opt.value) {
                // Tên bác sĩ
                const fullText = opt.text;
                const nameOnly = fullText.split(' - ')[0].replace(/^(⭐\s*)?Dr\.\s*/, '');
                if (doctorName) doctorName.value = nameOnly;
                
                // Chuyên khoa
                const specialty = opt.getAttribute('data-specialty');
                if (doctorSpecialty && specialty) {
                    doctorSpecialty.value = specialty;
                }
                
                // Đánh giá
                const rating = opt.getAttribute('data-rating');
                if (doctorRating && rating) {
                    doctorRating.value = '⭐ ' + parseFloat(rating).toFixed(1) + '/5.0';
                }
                
                // Phí khám
                const fee = opt.getAttribute('data-fee');
                if (consultationFee && fee) {
                    const feeNum = parseInt(fee);
                    consultationFee.value = feeNum.toLocaleString('vi-VN') + 'đ';
                }
            } else {
                // Reset
                if (doctorName) doctorName.value = '';
                if (doctorSpecialty) doctorSpecialty.value = '';
                if (doctorRating) doctorRating.value = '';
                if (consultationFee) consultationFee.value = '';
            }
        });
        
        // Trigger nếu đã có giá trị
        if (doctorSelect.value) {
            doctorSelect.dispatchEvent(new Event('change'));
        }
    }
});
</script>

</body>
</html>

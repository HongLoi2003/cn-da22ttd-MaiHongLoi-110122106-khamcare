<?php
/**
 * Dashboard dành cho bác sĩ - Phiên bản sạch
 */

session_start();
require_once 'db_config.php';

// Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: TaiKhoan.php?redirect=doctor_dashboard.php');
    exit;
}

// Chỉ cho phép role doctor
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'doctor') {
    // Hiển thị thông báo thay vì redirect (tránh đẩy user khác ra)
    ?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Truy cập bị từ chối - KhamCare</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                min-height: 100vh; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
            }
            .error-card {
                background: white;
                border-radius: 20px;
                padding: 40px;
                text-align: center;
                box-shadow: 0 20px 50px rgba(0,0,0,0.3);
                max-width: 500px;
            }
            .error-icon { font-size: 4rem; color: #ef4444; margin-bottom: 20px; }
            .btn-primary { background: linear-gradient(135deg, #0ea5e9, #06b6d4); border: none; }
            .btn-outline-secondary { border-color: #6b7280; color: #6b7280; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="error-icon">🔒</div>
            <h2 class="mb-3">Truy cập bị từ chối</h2>
            <p class="text-muted mb-4">
                Trang này chỉ dành cho bác sĩ.<br>
                Bạn đang đăng nhập với vai trò: <strong><?php echo htmlspecialchars($_SESSION['role'] ?? 'Không xác định'); ?></strong>
            </p>
            <div class="d-flex gap-3 justify-content-center">
                <a href="TrangChu.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home me-2"></i>Về trang chủ
                </a>
                <a href="logout.php" class="btn btn-primary">
                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất & Đăng nhập lại
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo 'Lỗi kết nối database';
    exit;
}

// Lấy thông tin bác sĩ từ user_id (sử dụng bảng tiếng Việt)
$stmt = $pdo->prepare("SELECT b.*, b.ho_ten as full_name, n.email, n.so_dien_thoai as phone, 
                              n.anh_dai_dien as avatar, c.ten AS specialty_name
                       FROM bac_si b
                       JOIN nguoi_dung n ON b.nguoi_dung_id = n.id
                       LEFT JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id
                       WHERE b.nguoi_dung_id = ? LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$doctor = $stmt->fetch();

if (!$doctor) {
    // Nếu không phải bác sĩ, chuyển về trang chủ
    header('Location: TrangChu.php');
    exit;
}

// Mapping tên bác sĩ với file ảnh có sẵn
$doctorImageMap = [
    'Trần Văn Hùng' => 'image/tranvanhung.png.png',
    'Đỗ Văn Hùng' => 'image/dovanhung.png.jpg',
    'Hoàng Minh Hoàng' => 'image/hoangminhhoang.png.png',
    'Lê Minh Tâm' => 'image/leminhtam.png.png',
    'Lê Minh Tuấn' => 'image/leminhtuan.png.png',
    'Lê Thị Hoa' => 'image/lethihoa.png.png',
    'Lê Thị Mai' => 'image/lethimai.png.png',
    'Nguyễn Văn Đức' => 'image/ngnuyenvanduc.png.webp',
    'Nguyễn Đức Minh' => 'image/nguyenducminh.png.png',
    'Nguyễn Thị Hạnh' => 'image/nguyenthihanh.png.png',
    'Nguyễn Thị Lan' => 'image/nguyenthilan.png.png',
    'Nguyễn Văn Hòa' => 'image/nguyenvanhoa.png.png',
    'Phạm Thị Lan' => 'image/phamthilan.png.png',
    'Phan Văn Long' => 'image/phanvanlong.png.png',
    'Trần Thị Hương' => 'image/tranthihuong.png.png',
    'Trần Thị Phú' => 'image/tranthiphu.png.png',
    'Trần Văn Nam' => 'image/tranvannam.png.png',
    'Vũ Thị Mai' => 'image/vuthimai.png.png',
];

// Hàm lấy URL avatar bác sĩ
function getDoctorAvatarUrl($avatar, $doctorName, $imageMap) {
    // Ưu tiên avatar từ database
    if (!empty($avatar) && file_exists($avatar)) {
        return $avatar;
    }
    
    // Tìm trong mapping theo tên
    foreach ($imageMap as $name => $imagePath) {
        if (stripos($doctorName, $name) !== false || stripos($name, $doctorName) !== false) {
            if (file_exists($imagePath)) {
                return $imagePath;
            }
        }
    }
    
    // Tìm file ảnh theo tên (không dấu)
    $nameNormalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', removeVietnameseAccents($doctorName)));
    $imageDir = 'image/';
    if (is_dir($imageDir)) {
        $files = scandir($imageDir);
        foreach ($files as $file) {
            $fileNormalized = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', pathinfo($file, PATHINFO_FILENAME)));
            if (strpos($fileNormalized, $nameNormalized) !== false || strpos($nameNormalized, $fileNormalized) !== false) {
                $fullPath = $imageDir . $file;
                if (is_file($fullPath) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    return $fullPath;
                }
            }
        }
    }
    
    // Trả về null nếu không có avatar
    return null;
}

// Hàm bỏ dấu tiếng Việt
function removeVietnameseAccents($str) {
    $accents = [
        'à','á','ạ','ả','ã','â','ầ','ấ','ậ','ẩ','ẫ','ă','ằ','ắ','ặ','ẳ','ẵ',
        'è','é','ẹ','ẻ','ẽ','ê','ề','ế','ệ','ể','ễ',
        'ì','í','ị','ỉ','ĩ',
        'ò','ó','ọ','ỏ','õ','ô','ồ','ố','ộ','ổ','ỗ','ơ','ờ','ớ','ợ','ở','ỡ',
        'ù','ú','ụ','ủ','ũ','ư','ừ','ứ','ự','ử','ữ',
        'ỳ','ý','ỵ','ỷ','ỹ',
        'đ',
        'À','Á','Ạ','Ả','Ã','Â','Ầ','Ấ','Ậ','Ẩ','Ẫ','Ă','Ằ','Ắ','Ặ','Ẳ','Ẵ',
        'È','É','Ẹ','Ẻ','Ẽ','Ê','Ề','Ế','Ệ','Ể','Ễ',
        'Ì','Í','Ị','Ỉ','Ĩ',
        'Ò','Ó','Ọ','Ỏ','Õ','Ô','Ồ','Ố','Ộ','Ổ','Ỗ','Ơ','Ờ','Ớ','Ợ','Ở','Ỡ',
        'Ù','Ú','Ụ','Ủ','Ũ','Ư','Ừ','Ứ','Ự','Ử','Ữ',
        'Ỳ','Ý','Ỵ','Ỷ','Ỹ',
        'Đ'
    ];
    $noAccents = [
        'a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a','a',
        'e','e','e','e','e','e','e','e','e','e','e',
        'i','i','i','i','i',
        'o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o','o',
        'u','u','u','u','u','u','u','u','u','u','u',
        'y','y','y','y','y',
        'd',
        'A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A','A',
        'E','E','E','E','E','E','E','E','E','E','E',
        'I','I','I','I','I',
        'O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O','O',
        'U','U','U','U','U','U','U','U','U','U','U',
        'Y','Y','Y','Y','Y',
        'D'
    ];
    return str_replace($accents, $noAccents, $str);
}

$doctorAvatarUrl = getDoctorAvatarUrl($doctor['avatar'], $doctor['full_name'], $doctorImageMap);

// Xử lý các AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    // Log để debug
    error_log("AJAX Action: " . $_POST['action'] . " | Patient ID: " . ($_POST['patient_id'] ?? 'N/A'));
    
    // Upload avatar
    if ($_POST['action'] === 'upload_avatar') {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file ảnh']);
            exit;
        }
        
        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WebP)']);
            exit;
        }
        
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File quá lớn. Tối đa 5MB']);
            exit;
        }
        
        // Tạo thư mục uploads nếu chưa có
        $uploadDir = 'uploads/avatars/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Tạo tên file unique
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'doctor_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            try {
                // Xóa avatar cũ nếu có
                if (!empty($doctor['avatar']) && file_exists($doctor['avatar'])) {
                    @unlink($doctor['avatar']);
                }
                
                // Cập nhật database
                $stmt = $pdo->prepare("UPDATE nguoi_dung SET anh_dai_dien = ? WHERE id = ?");
                $stmt->execute([$filepath, $_SESSION['user_id']]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Cập nhật ảnh đại diện thành công!',
                    'avatar_url' => $filepath
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật database: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể upload file']);
        }
        exit;
    }
    
    // Cập nhật trạng thái lịch hẹn
    if ($_POST['action'] === 'update_status') {
        $appointmentId = (int)($_POST['appointment_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $allowedStatuses = ['confirmed', 'cancelled', 'completed', 'in_progress'];

        if ($appointmentId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        try {
            // Chuyển đổi status sang tiếng Việt
            $statusMap = [
                'confirmed' => 'da_xac_nhan',
                'cancelled' => 'da_huy',
                'completed' => 'hoan_thanh',
                'in_progress' => 'da_xac_nhan'
            ];
            $trangThai = $statusMap[$newStatus] ?? 'cho_xac_nhan';
            
            $stmt = $pdo->prepare("UPDATE lich_hen SET trang_thai = ? WHERE id = ? AND bac_si_id = ?");
            $stmt->execute([$trangThai, $appointmentId, $doctor['id']]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy lịch hẹn hoặc bạn không có quyền']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    // Lấy thông tin bệnh nhân
    if ($_POST['action'] === 'get_patient_profile') {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        
        if ($patientId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID bệnh nhân không hợp lệ']);
            exit;
        }

        try {
            // Lấy thông tin bệnh nhân (bỏ điều kiện vai_tro và trang_thai để linh hoạt hơn)
            $stmt = $pdo->prepare("SELECT n.*, 
                                          DATEDIFF(CURDATE(), n.ngay_tao) as days_registered,
                                          TIMESTAMPDIFF(MONTH, n.ngay_tao, NOW()) as months_registered
                                   FROM nguoi_dung n 
                                   WHERE n.id = ?");
            $stmt->execute([$patientId]);
            $patient = $stmt->fetch();
            
            if (!$patient) {
                // Nếu không tìm thấy trong nguoi_dung, tạo thông tin cơ bản từ lich_hen
                $stmt = $pdo->prepare("SELECT DISTINCT ten_benh_nhan as ho_ten, benh_nhan_id as id 
                                       FROM lich_hen WHERE benh_nhan_id = ? LIMIT 1");
                $stmt->execute([$patientId]);
                $patient = $stmt->fetch();
                
                if (!$patient) {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin bệnh nhân']);
                    exit;
                }
                
                // Thêm các trường mặc định
                $patient['email'] = '';
                $patient['so_dien_thoai'] = '';
                $patient['ngay_sinh'] = null;
                $patient['gioi_tinh'] = null;
                $patient['dia_chi'] = '';
                $patient['days_registered'] = 0;
                $patient['months_registered'] = 0;
            }
            
            // Lấy thông tin y chẩn từ ho_so_nguoi_dung (nếu bảng tồn tại)
            try {
                $stmt = $pdo->prepare("SELECT * FROM ho_so_nguoi_dung WHERE nguoi_dung_id = ?");
                $stmt->execute([$patientId]);
                $profile = $stmt->fetch();
                
                // Merge thông tin profile vào patient data
                if ($profile) {
                    $patient['profile'] = $profile;
                } else {
                    $patient['profile'] = null;
                }
            } catch (Exception $e) {
                // Bảng không tồn tại hoặc lỗi khác - bỏ qua
                $patient['profile'] = null;
            }
            
            // Lấy lịch sử khám bệnh với bác sĩ hiện tại
            $doctorId = $doctor['id'] ?? 0;
            $appointments = [];
            $visitStats = ['total_visits' => 0, 'completed_visits' => 0];
            $medicalRecords = [];
            
            if ($doctorId > 0) {
                try {
                    $stmt = $pdo->prepare("SELECT lh.*, 
                                                  hs.chan_doan as diagnosis, hs.don_thuoc as prescription, 
                                                  hs.trieu_chung as symptoms,
                                                  hs.ngay_tai_kham as follow_up_date, hs.ghi_chu as notes
                                           FROM lich_hen lh
                                           LEFT JOIN ho_so_benh_an hs ON lh.id = hs.lich_hen_id
                                           WHERE lh.benh_nhan_id = ? AND lh.bac_si_id = ? 
                                           ORDER BY lh.ngay_hen DESC, lh.gio_hen DESC
                                           LIMIT 10");
                    $stmt->execute([$patientId, $doctorId]);
                    $appointments = $stmt->fetchAll();
                } catch (Exception $e) {
                    // Bỏ qua lỗi
                }
                
                // Đếm tổng số lần khám
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total_visits,
                                                  COUNT(CASE WHEN trang_thai = 'hoan_thanh' THEN 1 END) as completed_visits
                                           FROM lich_hen 
                                           WHERE benh_nhan_id = ? AND bac_si_id = ?");
                    $stmt->execute([$patientId, $doctorId]);
                    $visitStats = $stmt->fetch() ?: ['total_visits' => 0, 'completed_visits' => 0];
                } catch (Exception $e) {
                    // Bỏ qua lỗi
                }
            }
            
            // Lấy tất cả hồ sơ bệnh án của bệnh nhân
            try {
                $stmt = $pdo->prepare("SELECT hs.*, lh.ngay_hen as appointment_date, lh.gio_hen as appointment_time,
                                              b.id as doctor_id, b.ho_ten as doctor_name, c.ten as specialty_name
                                       FROM ho_so_benh_an hs
                                       JOIN lich_hen lh ON hs.lich_hen_id = lh.id
                                       JOIN bac_si b ON hs.bac_si_id = b.id
                                       LEFT JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id
                                       WHERE hs.benh_nhan_id = ?
                                       ORDER BY lh.ngay_hen DESC, lh.gio_hen DESC
                                       LIMIT 20");
                $stmt->execute([$patientId]);
                $medicalRecords = $stmt->fetchAll();
            } catch (Exception $e) {
                // Bỏ qua lỗi - có thể bảng chưa có dữ liệu
            }
            
            echo json_encode([
                'success' => true, 
                'data' => $patient,
                'appointments' => $appointments,
                'visit_stats' => $visitStats,
                'medical_records' => $medicalRecords
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Thống kê nhanh
$today = date('Y-m-d');
$stats = [
    'total_patients' => 0,
    'today_appointments' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0,
];

// Tổng số bệnh nhân
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT benh_nhan_id) FROM lich_hen WHERE bac_si_id = ?");
$stmt->execute([$doctor['id']]);
$stats['total_patients'] = (int)$stmt->fetchColumn();

// Lịch hẹn hôm nay
$stmt = $pdo->prepare("SELECT COUNT(*) FROM lich_hen WHERE bac_si_id = ? AND ngay_hen = ?");
$stmt->execute([$doctor['id'], $today]);
$stats['today_appointments'] = (int)$stmt->fetchColumn();

// Đếm theo trạng thái (chuyển đổi từ tiếng Việt sang key)
$stmt = $pdo->prepare("SELECT trang_thai, COUNT(*) AS total FROM lich_hen WHERE bac_si_id = ? GROUP BY trang_thai");
$stmt->execute([$doctor['id']]);
$statusMap = [
    'cho_xac_nhan' => 'pending',
    'da_xac_nhan' => 'confirmed', 
    'hoan_thanh' => 'completed',
    'da_huy' => 'cancelled'
];
foreach ($stmt->fetchAll() as $row) {
    $k = $statusMap[$row['trang_thai']] ?? $row['trang_thai'];
    if (isset($stats[$k])) $stats[$k] = (int)$row['total'];
}

// Đếm số lượng bệnh nhân
$stmt = $pdo->prepare("SELECT 
                        COUNT(CASE WHEN n.id IS NOT NULL THEN 1 END) as registered_patients,
                        COUNT(CASE WHEN n.id IS NULL THEN 1 END) as temp_patients
                       FROM lich_hen lh
                       LEFT JOIN nguoi_dung n ON lh.benh_nhan_id = n.id AND n.vai_tro = 'benh_nhan'
                       WHERE lh.bac_si_id = ?");
$stmt->execute([$doctor['id']]);
$patient_stats = $stmt->fetch();
$stats['registered_patients'] = (int)$patient_stats['registered_patients'];
$stats['temp_patients'] = (int)$patient_stats['temp_patients'];

// Lấy lịch hẹn sắp tới
$upcoming = [];
$stmt = $pdo->prepare("SELECT lh.*, 
                              lh.id as id,
                              lh.benh_nhan_id as patient_id,
                              lh.ngay_hen as appointment_date,
                              lh.gio_hen as appointment_time,
                              CASE lh.trang_thai 
                                  WHEN 'cho_xac_nhan' THEN 'pending'
                                  WHEN 'da_xac_nhan' THEN 'confirmed'
                                  WHEN 'hoan_thanh' THEN 'completed'
                                  WHEN 'da_huy' THEN 'cancelled'
                                  ELSE lh.trang_thai
                              END as status,
                              CASE lh.loai_tu_van
                                  WHEN 'truc_tuyen' THEN 'online'
                                  ELSE 'offline'
                              END as consultation_type,
                              lh.ghi_chu as notes,
                              n.id AS user_id,
                              n.ten_dang_nhap AS patient_username,
                              COALESCE(lh.ten_benh_nhan, n.ho_ten, 'Bệnh nhân') AS patient_name,
                              n.email AS patient_email,
                              n.so_dien_thoai AS patient_phone,
                              n.ngay_sinh AS patient_dob,
                              n.gioi_tinh AS patient_gender,
                              n.dia_chi AS patient_address,
                              'registered' AS account_type
                       FROM lich_hen lh
                       LEFT JOIN nguoi_dung n ON lh.benh_nhan_id = n.id AND n.vai_tro = 'benh_nhan'
                       WHERE lh.bac_si_id = ? 
                         AND DATE(lh.ngay_hen) >= ?
                         AND lh.trang_thai != 'da_huy'
                       ORDER BY lh.ngay_hen ASC, lh.gio_hen ASC
                       LIMIT 20");
$stmt->execute([$doctor['id'], $today]);
$upcoming = $stmt->fetchAll();

// Lấy tất cả lịch hẹn
$all_appointments = [];
$stmt = $pdo->prepare("SELECT lh.*, 
                              lh.id as id,
                              lh.benh_nhan_id as patient_id,
                              lh.ngay_hen as appointment_date,
                              lh.gio_hen as appointment_time,
                              CASE lh.trang_thai 
                                  WHEN 'cho_xac_nhan' THEN 'pending'
                                  WHEN 'da_xac_nhan' THEN 'confirmed'
                                  WHEN 'hoan_thanh' THEN 'completed'
                                  WHEN 'da_huy' THEN 'cancelled'
                                  ELSE lh.trang_thai
                              END as status,
                              CASE lh.loai_tu_van
                                  WHEN 'truc_tuyen' THEN 'online'
                                  ELSE 'offline'
                              END as consultation_type,
                              lh.ghi_chu as notes,
                              n.id AS user_id,
                              n.ten_dang_nhap AS patient_username,
                              COALESCE(lh.ten_benh_nhan, n.ho_ten, 'Bệnh nhân') AS patient_name,
                              n.email AS patient_email,
                              n.so_dien_thoai AS patient_phone,
                              n.ngay_sinh AS patient_dob,
                              n.gioi_tinh AS patient_gender,
                              n.dia_chi AS patient_address,
                              'registered' AS account_type
                       FROM lich_hen lh
                       LEFT JOIN nguoi_dung n ON lh.benh_nhan_id = n.id AND n.vai_tro = 'benh_nhan'
                       WHERE lh.bac_si_id = ?
                       ORDER BY lh.ngay_hen DESC, lh.gio_hen DESC");
$stmt->execute([$doctor['id']]);
$all_appointments = $stmt->fetchAll();

// Lấy lịch hẹn bị hủy (bởi bệnh nhân)
$cancelled_appointments = [];
$stmt = $pdo->prepare("SELECT lh.*, 
                              lh.id as id,
                              lh.benh_nhan_id as patient_id,
                              lh.ngay_hen as appointment_date,
                              lh.gio_hen as appointment_time,
                              'cancelled' as status,
                              CASE lh.loai_tu_van
                                  WHEN 'truc_tuyen' THEN 'online'
                                  ELSE 'offline'
                              END as consultation_type,
                              lh.ghi_chu as notes,
                              lh.ngay_cap_nhat as cancelled_at,
                              n.id AS user_id,
                              COALESCE(lh.ten_benh_nhan, n.ho_ten, 'Bệnh nhân') AS patient_name,
                              n.email AS patient_email,
                              n.so_dien_thoai AS patient_phone
                       FROM lich_hen lh
                       LEFT JOIN nguoi_dung n ON lh.benh_nhan_id = n.id AND n.vai_tro = 'benh_nhan'
                       WHERE lh.bac_si_id = ? AND lh.trang_thai = 'da_huy'
                       ORDER BY lh.ngay_cap_nhat DESC
                       LIMIT 20");
$stmt->execute([$doctor['id']]);
$cancelled_appointments = $stmt->fetchAll();
$stats['cancelled'] = count($cancelled_appointments);

// Lấy đánh giá của bác sĩ
$reviews = [];
$stmt = $pdo->prepare("SELECT dg.*, 
                              dg.diem as rating,
                              dg.noi_dung as comment,
                              dg.phan_hoi_bac_si as doctor_reply,
                              dg.ngay_tao as created_at,
                              n.ho_ten as patient_name,
                              lh.ngay_hen as appointment_date,
                              CASE lh.loai_tu_van
                                  WHEN 'truc_tuyen' THEN 'Trực tuyến'
                                  ELSE 'Trực tiếp'
                              END as consultation_type
                       FROM danh_gia dg
                       JOIN nguoi_dung n ON dg.nguoi_dung_id = n.id
                       LEFT JOIN lich_hen lh ON dg.lich_hen_id = lh.id
                       WHERE dg.bac_si_id = ? AND dg.trang_thai = 'hien_thi'
                       ORDER BY dg.ngay_tao DESC
                       LIMIT 20");
$stmt->execute([$doctor['id']]);
$reviews = $stmt->fetchAll();

// Tính điểm đánh giá trung bình
$avgRating = $doctor['danh_gia'] ?? 0;
$totalReviews = $doctor['tong_danh_gia'] ?? 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bác sĩ - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="global-font-override.css">
    <link rel="stylesheet" href="square-icons-override.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <link rel="stylesheet" href="fix-overlay-click.css">
    <link rel="stylesheet" href="doctor-card-modern.css">
    <style>
        
        
        :root {
            --primary: #4f46e5;
            --primary-light: #6366f1;
            --primary-dark: #3730a3;
            --secondary: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --light: #f8fafc;
            --dark: #1e293b;
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
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-700);
            line-height: 1.6;
        }
        
        .container-fluid {
            background: transparent;
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            min-height: 100vh;
            box-shadow: var(--shadow-xl);
            border-right: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .sidebar .doctor-info {
            padding: 2rem 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--gray-200);
            background: linear-gradient(135deg, var(--gray-50) 0%, #ffffff 100%);
        }
        
        .sidebar .nav-link {
            color: var(--gray-600);
            padding: 1rem 1.5rem;
            margin: 0.25rem 1rem;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .sidebar .nav-link:hover::before {
            left: 100%;
        }
        
        .sidebar .nav-link:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }
        
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: var(--shadow-lg);
            transform: translateX(4px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 12px;
        }
        
        /* Main Content */
        .main-content {
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px 0 0 20px;
            margin-left: -20px;
            box-shadow: var(--shadow-xl);
        }
        
        .page-header {
            background: linear-gradient(135deg, #ffffff 0%, var(--gray-50) 100%);
            padding: 2rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* KPI Cards */
        .kpi-card {
            background: linear-gradient(135deg, #ffffff 0%, var(--gray-50) 100%);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--gray-200);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .kpi-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .kpi-label {
            color: var(--gray-600);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Profile Card */
        .profile-card {
            background: linear-gradient(135deg, #ffffff 0%, var(--gray-50) 100%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }
        
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .doctor-avatar {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-lg);
            border: 4px solid white;
            overflow: hidden;
            position: relative;
        }
        
        .doctor-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .doctor-avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 16px;
            font-size: 3.5rem;
        }
        
        .doctor-avatar-small {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            font-size: 1.5rem;
            margin-bottom: 0;
        }
        
        /* Tables */
        .table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .table thead th {
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border: none;
            font-weight: 600;
            color: var(--gray-700);
            padding: 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
            border-color: var(--gray-200);
            vertical-align: middle;
        }
        
        .table tbody tr:hover {
            background-color: var(--gray-50);
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            box-shadow: var(--shadow);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
        }
        
        .btn-info {
            background: linear-gradient(135deg, var(--info) 0%, #2563eb 100%);
        }
        
        .btn-outline-info {
            border: 2px solid var(--info);
            color: var(--info);
            background: transparent;
        }
        
        .btn-outline-info:hover {
            background: var(--info);
            color: white;
        }
        
        /* Badges */
        .badge {
            border-radius: 6px;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
        }
        
        .badge.bg-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%) !important;
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%) !important;
        }
        
        .badge.bg-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
        }
        
        .badge.bg-info {
            background: linear-gradient(135deg, var(--info) 0%, #2563eb 100%) !important;
        }
        
        /* Detail Grid */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .detail-item {
            background: linear-gradient(135deg, #ffffff 0%, var(--gray-50) 100%);
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            box-shadow: var(--shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .detail-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .detail-label i {
            margin-right: 0.5rem;
            color: var(--primary);
        }
        
        .detail-value {
            color: var(--gray-600);
            font-weight: 500;
        }
        
        /* Modal */
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: var(--shadow-xl);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            border: none;
        }
        
        .modal-footer {
            border: none;
            padding: 1.5rem;
        }
        
        /* Form Controls */
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid var(--gray-200);
            padding: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                margin-left: 0;
                border-radius: 0;
            }
            
            .kpi-number {
                font-size: 2rem;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Loading Animation */
        .spinner-border {
            border-color: var(--primary);
            border-right-color: transparent;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-100);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        }
        
        /* ========== RATING & REVENUE STYLES ========== */
        
        /* Rating Card - Đánh giá */
        .rating-card {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(245, 158, 11, 0.2);
            border: none;
        }
        
        .rating-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .rating-card .rating-score {
            font-size: 4rem;
            font-weight: 800;
            color: #b45309;
            line-height: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .rating-card .rating-stars {
            margin: 1rem 0;
        }
        
        .rating-card .rating-stars i {
            font-size: 1.8rem;
            margin: 0 3px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }
        
        .rating-card .rating-count {
            font-size: 1.1rem;
            color: #92400e;
            font-weight: 600;
        }
        
        /* Cancelled Card - Lịch hủy */
        .cancelled-card {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(239, 68, 68, 0.2);
        }
        
        .cancelled-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .cancelled-card .cancelled-number {
            font-size: 3.5rem;
            font-weight: 800;
            color: #dc2626;
            line-height: 1;
        }
        
        .cancelled-card .cancelled-label {
            font-size: 1rem;
            color: #991b1b;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .cancelled-card .cancelled-link {
            color: #dc2626;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .cancelled-card .cancelled-link:hover {
            color: #991b1b;
            text-decoration: underline;
        }
        
        /* Recent Reviews Card */
        .recent-reviews-card {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.2);
        }
        
        .recent-reviews-card::before {
            content: '';
            position: absolute;
            bottom: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            pointer-events: none;
        }
        
        .recent-reviews-card .reviews-number {
            font-size: 3.5rem;
            font-weight: 800;
            color: #059669;
            line-height: 1;
        }
        
        .recent-reviews-card .reviews-label {
            font-size: 1rem;
            color: #047857;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        
        .recent-reviews-card .reviews-link {
            color: #059669;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .recent-reviews-card .reviews-link:hover {
            color: #047857;
            text-decoration: underline;
        }
        
        /* Reviews Tab Styles */
        .reviews-summary {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 24px;
            padding: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .reviews-summary::before {
            content: '★';
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 4rem;
            color: rgba(180, 83, 9, 0.1);
        }
        
        .reviews-summary .big-score {
            font-size: 5rem;
            font-weight: 800;
            color: #b45309;
            line-height: 1;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.1);
        }
        
        .reviews-summary .stars-row {
            margin: 1.5rem 0;
        }
        
        .reviews-summary .stars-row i {
            font-size: 2.2rem;
            margin: 0 5px;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,0.2));
        }
        
        .reviews-summary .total-reviews {
            font-size: 1.2rem;
            color: #92400e;
            font-weight: 600;
        }
        
        /* Review Item */
        .review-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .review-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        
        .review-item .reviewer-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .review-item .reviewer-info h6 {
            margin: 0;
            font-weight: 700;
            color: var(--gray-800);
        }
        
        .review-item .review-stars i {
            color: #fbbf24;
            font-size: 1rem;
        }
        
        .review-item .review-stars i.text-muted {
            color: var(--gray-300) !important;
        }
        
        .review-item .review-score {
            font-weight: 700;
            color: #b45309;
            font-size: 1.1rem;
        }
        
        .review-item .review-content {
            color: var(--gray-600);
            line-height: 1.6;
            margin: 1rem 0;
        }
        
        .review-item .doctor-reply {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-radius: 12px;
            padding: 1rem;
            border-left: 4px solid var(--primary);
            margin-top: 1rem;
        }
        
        .review-item .doctor-reply .reply-label {
            color: var(--primary);
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        /* Empty Reviews State */
        .empty-reviews {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-reviews .empty-icon {
            font-size: 5rem;
            color: var(--gray-300);
            margin-bottom: 1.5rem;
        }
        
        .empty-reviews h5 {
            color: var(--gray-500);
            font-weight: 600;
        }
        
        .empty-reviews p {
            color: var(--gray-400);
        }
        
        /* Patient Stats Cards */
        .patient-stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }
        
        .patient-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .patient-stat-card.registered {
            border-left: 4px solid #10b981;
        }
        
        .patient-stat-card.temporary {
            border-left: 4px solid #f59e0b;
        }
        
        .patient-stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
        }
        
        .patient-stat-card.registered .stat-number {
            color: #059669;
        }
        
        .patient-stat-card.temporary .stat-number {
            color: #d97706;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="doctor-info">
                        <div class="doctor-avatar mx-auto">
                            <?php if ($doctorAvatarUrl): ?>
                                <img src="<?php echo htmlspecialchars($doctorAvatarUrl); ?>" alt="Avatar">
                            <?php else: ?>
                                <i class="fas fa-user-md"></i>
                            <?php endif; ?>
                        </div>
                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($doctor['full_name']); ?></h6>
                        <small class="text-muted d-block"><?php echo htmlspecialchars($doctor['specialty_name'] ?? 'Bác sĩ'); ?></small>
                        <div class="badge bg-success mt-2">
                            <i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i>Đang hoạt động
                        </div>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#" onclick="showTab('overview', this)">
                            <i class="fas fa-chart-line me-2"></i>Tổng quan
                        </a>
                        <a class="nav-link" href="#" onclick="showTab('appointments', this)">
                            <i class="fas fa-calendar-alt me-2"></i>Lịch hẹn
                        </a>
                        <a class="nav-link" href="#" onclick="showTab('cancelled', this)">
                            <i class="fas fa-calendar-times me-2"></i>Lịch bị hủy
                            <?php if ($stats['cancelled'] > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo $stats['cancelled']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="#" onclick="showTab('reviews', this)">
                            <i class="fas fa-star me-2"></i>Đánh giá
                            <?php if ($totalReviews > 0): ?>
                                <span class="badge bg-warning text-dark ms-1"><?php echo $totalReviews; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="#" onclick="showTab('medical-records', this)">
                            <i class="fas fa-file-medical me-2"></i>Hồ sơ bệnh án
                        </a>
                        <a class="nav-link" href="#" onclick="showTab('profile', this)">
                            <i class="fas fa-user-circle me-2"></i>Hồ sơ
                        </a>
                        <a class="nav-link" href="#" onclick="showTab('schedule', this)">
                            <i class="fas fa-clock me-2"></i>Lịch làm việc
                        </a>
                        <hr>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="page-header">
                    <h1 class="page-title" id="page-title">Tổng Quan</h1>
                    <p class="text-muted mb-0">Chào mừng trở lại, BS. <?php echo htmlspecialchars($doctor['full_name']); ?></p>
                </div>
                
                <!-- Tab: Tổng quan -->
                <div class="tab-content active" id="overview-tab">
                    <!-- Thông tin ngày hiện tại -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="profile-card">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="mb-2">
                                            <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                            Hôm nay: <?php echo date('d/m/Y'); ?>
                                        </h5>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-clock me-2"></i>
                                            <?php echo date('H:i'); ?> - <?php echo date('l', strtotime('today')); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <?php if ($stats['pending'] > 0): ?>
                                            <div class="alert alert-warning mb-0 d-inline-block">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong><?php echo $stats['pending']; ?></strong> lịch hẹn chưa xác nhận
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-success mb-0 d-inline-block">
                                                <i class="fas fa-check-circle me-2"></i>
                                                Tất cả lịch hẹn đã được xử lý
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="kpi-card text-center">
                                <div class="kpi-number"><?php echo $stats['total_patients']; ?></div>
                                <div class="kpi-label">
                                    <i class="fas fa-users me-2"></i>Tổng bệnh nhân
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card text-center">
                                <div class="kpi-number"><?php echo $stats['today_appointments']; ?></div>
                                <div class="kpi-label">
                                    <i class="fas fa-calendar-day me-2"></i>Lịch hôm nay
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card text-center">
                                <div class="kpi-number text-warning"><?php echo $stats['pending']; ?></div>
                                <div class="kpi-label">
                                    <i class="fas fa-clock me-2"></i>Chưa xác nhận
                                </div>
                                <?php if ($stats['pending'] > 0): ?>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-calendar-day me-1"></i>
                                        Cần xử lý hôm nay
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card text-center">
                                <div class="kpi-number text-success"><?php echo $stats['completed']; ?></div>
                                <div class="kpi-label">
                                    <i class="fas fa-check-circle me-2"></i>Hoàn thành
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thống kê đánh giá và lịch hủy -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="rating-card">
                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= round($avgRating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="rating-score"><?php echo number_format($avgRating, 1); ?></div>
                                <div class="rating-count">
                                    <i class="fas fa-users me-1"></i><?php echo $totalReviews; ?> đánh giá
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="cancelled-card">
                                <div class="cancelled-number"><?php echo $stats['cancelled']; ?></div>
                                <div class="cancelled-label">
                                    <i class="fas fa-calendar-times me-2"></i>Lịch bị hủy
                                </div>
                                <?php if ($stats['cancelled'] > 0): ?>
                                    <div class="mt-2">
                                        <a href="#" onclick="showTab('cancelled', this)" class="cancelled-link">
                                            <i class="fas fa-eye me-1"></i>Xem chi tiết
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="recent-reviews-card">
                                <div class="reviews-number"><?php echo count($reviews); ?></div>
                                <div class="reviews-label">
                                    <i class="fas fa-comments me-2"></i>Đánh giá gần đây
                                </div>
                                <?php if (count($reviews) > 0): ?>
                                    <div class="mt-2">
                                        <a href="#" onclick="showTab('reviews', this)" class="reviews-link">
                                            <i class="fas fa-arrow-right me-1"></i>Xem tất cả
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thống kê loại bệnh nhân -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="patient-stat-card registered">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="mb-1 fw-bold">
                                            <i class="fas fa-user-check me-2 text-success"></i>
                                            Bệnh nhân đã đăng ký
                                        </h6>
                                        <p class="text-muted mb-0 small">Có tài khoản chính thức</p>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="stat-number"><?php echo $stats['registered_patients']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="patient-stat-card temporary">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h6 class="mb-1 fw-bold">
                                            <i class="fas fa-user-clock me-2 text-warning"></i>
                                            Bệnh nhân tạm thời
                                        </h6>
                                        <p class="text-muted mb-0">Chưa đăng ký tài khoản</p>
                                    </div>
                                    <div class="col-4 text-end">
                                        <div class="h3 text-warning mb-0"><?php echo $stats['temp_patients']; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-card">
                        <h5 class="mb-3">
                            <i class="fas fa-calendar-alt me-2"></i>Lịch hẹn sắp tới
                            <?php if (!empty($upcoming)): ?>
                                <span class="badge bg-primary ms-2"><?php echo count($upcoming); ?> lịch hẹn</span>
                            <?php endif; ?>
                        </h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Bệnh nhân</th>
                                        <th>Ngày & Giờ</th>
                                        <th>Triệu chứng</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($upcoming)): ?>
                                        <?php foreach ($upcoming as $a): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">
                                                            <?php echo htmlspecialchars($a['patient_name']); ?>
                                                            <?php if ($a['account_type'] === 'registered'): ?>
                                                                <span class="badge bg-success ms-2" style="font-size: 0.7rem;">
                                                                    <i class="fas fa-user-check"></i> Tài khoản chính thức
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning ms-2" style="font-size: 0.7rem;">
                                                                    <i class="fas fa-user-clock"></i> Bệnh nhân tạm thời
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-at me-1"></i><?php echo htmlspecialchars($a['patient_username']); ?>
                                                        </small>
                                                        <?php if (!empty($a['patient_phone'])): ?>
                                                            <small class="text-muted d-block">
                                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($a['patient_phone']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($a['patient_registered_at'])): ?>
                                                            <small class="text-success d-block">
                                                                <i class="fas fa-calendar-plus me-1"></i>
                                                                Đăng ký: <?php echo date('d/m/Y', strtotime($a['patient_registered_at'])); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($a['appointment_date'])); ?><br>
                                                <small class="text-primary fw-bold"><?php echo substr($a['appointment_time'], 0, 5); ?></small>
                                            </td>
                                            <td class="symptoms-cell">
                                                <?php if (!empty($a['chief_complaint'])): ?>
                                                    <div class="chief-complaint" title="Lý do khám: <?php echo htmlspecialchars($a['chief_complaint']); ?>">
                                                        <i class="fas fa-stethoscope me-1"></i>
                                                        <strong>Lý do khám:</strong> <?php echo htmlspecialchars(mb_substr($a['chief_complaint'], 0, 50) . (mb_strlen($a['chief_complaint']) > 50 ? '...' : '')); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="no-info">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Chưa có thông tin
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $a['status'] === 'pending' ? 'warning' : ($a['status'] === 'confirmed' ? 'success' : 'secondary'); ?>">
                                                    <?php echo ucfirst($a['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewPatientProfile(<?php echo $a['patient_id']; ?>, '<?php echo htmlspecialchars($a['patient_name'], ENT_QUOTES); ?>')" title="Xem hồ sơ bệnh nhân">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <?php if ($a['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $a['id']; ?>, 'confirmed')" title="Xác nhận lịch hẹn">
                                                            <i class="fas fa-check"></i> Xác nhận
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="updateStatus(<?php echo $a['id']; ?>, 'cancelled')" title="Hủy lịch hẹn">
                                                            <i class="fas fa-times"></i> Hủy
                                                        </button>
                                                    <?php elseif ($a['status'] === 'confirmed'): ?>
                                                        <?php if (($a['consultation_type'] ?? 'offline') === 'online'): ?>
                                                            <!-- Nút cho tư vấn trực tuyến -->
                                                            <button class="btn btn-sm btn-success" onclick="startVideoCall(<?php echo $a['id']; ?>, <?php echo $a['patient_id']; ?>)" title="Gọi video với bệnh nhân">
                                                                <i class="fas fa-video"></i> Gọi Video
                                                            </button>
                                                            <button class="btn btn-sm btn-info" onclick="startChatConsultation(<?php echo $a['id']; ?>)" title="Tư vấn qua chat">
                                                                <i class="fas fa-comments"></i> Chat
                                                            </button>
                                                        <?php else: ?>
                                                            <!-- Nút cho khám tại phòng khám -->
                                                            <button class="btn btn-sm btn-primary" onclick="startOfflineConsultation(<?php echo $a['id']; ?>)" title="Bắt đầu khám tại phòng khám">
                                                                <i class="fas fa-hospital"></i> Khám trực tiếp
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $a['id']; ?>, 'completed')" title="Hoàn thành khám bệnh">
                                                            <i class="fas fa-check-double"></i> Hoàn thành
                                                        </button>
                                                    <?php elseif ($a['status'] === 'completed'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle"></i> Đã hoàn thành
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center text-muted">Chưa có lịch hẹn</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Lịch hẹn -->
                <div class="tab-content" id="appointments-tab">
                    <div class="profile-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Tất cả lịch hẹn</h5>
                            <div class="d-flex gap-2">
                                <input type="text" class="form-control form-control-sm" id="search-input" placeholder="Tìm kiếm bệnh nhân..." onkeyup="searchAppointments()" style="width: 200px;">
                                <select class="form-select form-select-sm" id="status-filter" onchange="filterAppointments()">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="pending">Chờ xác nhận</option>
                                    <option value="confirmed">Đã xác nhận</option>
                                    <option value="completed">Hoàn thành</option>
                                    <option value="cancelled">Đã hủy</option>
                                </select>
                                <select class="form-select form-select-sm" id="account-type-filter" onchange="filterAppointments()">
                                    <option value="">Tất cả loại tài khoản</option>
                                    <option value="registered">Tài khoản chính thức</option>
                                    <option value="temp">Bệnh nhân tạm thời</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Thông tin bệnh nhân</th>
                                        <th>Ngày & Giờ</th>
                                        <th>Trạng thái</th>
                                        <th>Loại khám</th>
                                        <th>Triệu chứng</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody id="appointments-table-body">
                                    <?php if (!empty($all_appointments)): ?>
                                        <?php foreach ($all_appointments as $a): ?>
                                        <tr data-status="<?php echo $a['status']; ?>" data-account-type="<?php echo $a['account_type']; ?>" data-patient-name="<?php echo strtolower(htmlspecialchars($a['patient_name'])); ?>" data-patient-phone="<?php echo htmlspecialchars($a['patient_phone'] ?? ''); ?>" data-patient-email="<?php echo htmlspecialchars($a['patient_email'] ?? ''); ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                            <?php if (!empty($a['patient_gender'])): ?>
                                                                <i class="fas fa-<?php echo $a['patient_gender'] === 'male' ? 'mars' : 'venus'; ?>"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-user"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">
                                                            <?php echo htmlspecialchars($a['patient_name']); ?>
                                                            <?php if ($a['account_type'] === 'registered'): ?>
                                                                <span class="badge bg-primary ms-2" style="font-size: 0.7rem;">
                                                                    <i class="fas fa-shield-alt"></i> Đã xác thực
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning ms-2" style="font-size: 0.7rem;">
                                                                    <i class="fas fa-user-clock"></i> Bệnh nhân tạm thời
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-info fw-bold">
                                                            <i class="fas fa-user me-1"></i>@<?php echo htmlspecialchars($a['patient_username']); ?>
                                                        </small>
                                                        <?php if (!empty($a['patient_email'])): ?>
                                                            <small class="text-muted d-block">
                                                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($a['patient_email']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($a['patient_phone'])): ?>
                                                            <small class="text-muted d-block">
                                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($a['patient_phone']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($a['patient_dob'])): ?>
                                                            <small class="text-muted d-block">
                                                                <i class="fas fa-birthday-cake me-1"></i>
                                                                <?php 
                                                                $age = date('Y') - date('Y', strtotime($a['patient_dob']));
                                                                echo $age . ' tuổi';
                                                                ?>
                                                            </small>
                                                        <?php endif; ?>
                                                        <?php if (!empty($a['patient_registered_at'])): ?>
                                                            <small class="text-success d-block">
                                                                <i class="fas fa-calendar-check me-1"></i>
                                                                Tham gia: <?php echo date('d/m/Y', strtotime($a['patient_registered_at'])); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <div class="fw-bold"><?php echo date('d/m/Y', strtotime($a['appointment_date'])); ?></div>
                                                    <span class="badge bg-primary"><?php echo substr($a['appointment_time'], 0, 5); ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $a['status'] === 'pending' ? 'warning' : ($a['status'] === 'confirmed' ? 'success' : ($a['status'] === 'completed' ? 'info' : 'secondary')); ?>">
                                                    <?php 
                                                    $statusText = [
                                                        'pending' => 'Chờ xác nhận',
                                                        'confirmed' => 'Đã xác nhận', 
                                                        'completed' => 'Hoàn thành',
                                                        'cancelled' => 'Đã hủy'
                                                    ];
                                                    echo $statusText[$a['status']] ?? ucfirst($a['status']);
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo ($a['consultation_type'] ?? 'offline') === 'online' ? 'success' : 'primary'; ?>">
                                                    <i class="fas fa-<?php echo ($a['consultation_type'] ?? 'offline') === 'online' ? 'video' : 'hospital'; ?> me-1"></i>
                                                    <?php echo ($a['consultation_type'] ?? 'offline') === 'online' ? 'Trực tuyến' : 'Tại phòng khám'; ?>
                                                </span>
                                            </td>
                                            <td class="symptoms-cell">
                                                <?php if (!empty($a['chief_complaint'])): ?>
                                                    <div class="chief-complaint" title="Lý do khám: <?php echo htmlspecialchars($a['chief_complaint']); ?>">
                                                        <i class="fas fa-stethoscope me-1"></i>
                                                        <strong>Lý do khám:</strong> <?php echo htmlspecialchars(mb_substr($a['chief_complaint'], 0, 50) . (mb_strlen($a['chief_complaint']) > 50 ? '...' : '')); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="no-info">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Chưa có thông tin triệu chứng
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-info" onclick="viewPatientProfile(<?php echo $a['patient_id']; ?>, '<?php echo htmlspecialchars($a['patient_name'], ENT_QUOTES); ?>')" title="Xem hồ sơ bệnh nhân">
                                                        <i class="fas fa-user"></i>
                                                    </button>
                                                    <?php if ($a['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $a['id']; ?>, 'confirmed')" title="Xác nhận lịch hẹn">
                                                            <i class="fas fa-check"></i> Xác nhận
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="updateStatus(<?php echo $a['id']; ?>, 'cancelled')" title="Hủy lịch hẹn">
                                                            <i class="fas fa-times"></i> Hủy
                                                        </button>
                                                    <?php elseif ($a['status'] === 'confirmed'): ?>
                                                        <?php if (($a['consultation_type'] ?? 'offline') === 'online'): ?>
                                                            <!-- Nút cho tư vấn trực tuyến -->
                                                            <button class="btn btn-sm btn-success" onclick="startVideoCall(<?php echo $a['id']; ?>, <?php echo $a['patient_id']; ?>)" title="Gọi video với bệnh nhân">
                                                                <i class="fas fa-video"></i> Gọi Video
                                                            </button>
                                                            <button class="btn btn-sm btn-info" onclick="startChatConsultation(<?php echo $a['id']; ?>)" title="Tư vấn qua chat">
                                                                <i class="fas fa-comments"></i> Chat
                                                            </button>
                                                        <?php else: ?>
                                                            <!-- Nút cho khám tại phòng khám -->
                                                            <button class="btn btn-sm btn-primary" onclick="startOfflineConsultation(<?php echo $a['id']; ?>)" title="Bắt đầu khám tại phòng khám">
                                                                <i class="fas fa-hospital"></i> Khám trực tiếp
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $a['id']; ?>, 'completed')" title="Hoàn thành khám bệnh">
                                                            <i class="fas fa-check-double"></i> Hoàn thành
                                                        </button>
                                                    <?php elseif ($a['status'] === 'completed'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check-circle"></i> Đã hoàn thành
                                                        </span>
                                                    <?php elseif ($a['status'] === 'cancelled'): ?>
                                                        <span class="badge bg-secondary">
                                                            <i class="fas fa-ban"></i> Đã hủy
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center text-muted">Chưa có lịch hẹn nào</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Hồ sơ bệnh án -->
                <div class="tab-content" id="medical-records-tab">
                    <div class="profile-card">
                        <ul class="nav nav-tabs mb-4" id="medicalRecordsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="need-exam-tab" data-bs-toggle="tab" data-bs-target="#need-exam" type="button" role="tab">
                                    <i class="fas fa-clipboard-list me-2"></i>Hồ sơ cần khám
                                    <span class="badge bg-warning ms-2" id="need-exam-count">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="examined-tab" data-bs-toggle="tab" data-bs-target="#examined" type="button" role="tab">
                                    <i class="fas fa-check-circle me-2"></i>Hồ sơ đã khám
                                    <span class="badge bg-success ms-2" id="examined-count">0</span>
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="medicalRecordsTabContent">
                            <!-- Tab: Hồ sơ cần khám -->
                            <div class="tab-pane fade show active" id="need-exam" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Giờ hẹn</th>
                                                <th>Bệnh nhân</th>
                                                <th>Lý do khám</th>
                                                <th>Loại khám</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="need-exam-tbody">
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Đang tải...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Tab: Hồ sơ đã khám -->
                            <div class="tab-pane fade" id="examined" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Ngày khám</th>
                                                <th>Bệnh nhân</th>
                                                <th>Chẩn đoán</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="examined-tbody">
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Đang tải...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Hồ sơ -->
                <div class="tab-content" id="profile-tab">
                    <div class="profile-card">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="doctor-avatar doctor-avatar-large mx-auto">
                                    <?php if ($doctorAvatarUrl): ?>
                                        <img src="<?php echo htmlspecialchars($doctorAvatarUrl); ?>" alt="Avatar">
                                    <?php else: ?>
                                        <i class="fas fa-user-md"></i>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="openAvatarUpload()">
                                    <i class="fas fa-camera me-1"></i>Đổi ảnh
                                </button>
                                <input type="file" id="avatarInput" accept="image/*" style="display: none;" onchange="uploadAvatar(this)">
                            </div>
                            <div class="col-md-9">
                                <h3>BS. <?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                                <p class="text-primary mb-3">
                                    <i class="fas fa-stethoscope me-2"></i>
                                    <?php echo htmlspecialchars($doctor['specialty_name'] ?? 'Bác sĩ'); ?>
                                </p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($doctor['email']); ?></p>
                                        <p><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($doctor['phone']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><i class="fas fa-map-marker-alt me-2 text-muted"></i>Hà Nội, Việt Nam</p>
                                        <p><i class="fas fa-calendar me-2 text-muted"></i>Tham gia từ <?php echo date('m/Y'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-clock me-2"></i>Lịch khám
                            </div>
                            <div class="detail-value">Thứ 2 - Thứ 6: 8:00 - 17:00<br>Thứ 7: 8:00 - 12:00</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-graduation-cap me-2"></i>Kinh nghiệm
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($doctor['experience'] ?? 'Nhiều năm kinh nghiệm'); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-money-bill-wave me-2"></i>Phí khám
                            </div>
                            <div class="detail-value text-success fw-bold">
                                <?php echo number_format($doctor['consultation_fee'] ?? 200000, 0, ',', '.'); ?> VNĐ
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-hospital me-2"></i>Nơi làm việc
                            </div>
                            <div class="detail-value"><?php echo htmlspecialchars($doctor['workplace'] ?? 'Bệnh viện Đa khoa'); ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Thống kê hoạt động</h6>
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="h4 text-primary"><?php echo array_sum([$stats['pending'], $stats['confirmed'], $stats['completed'], $stats['cancelled']]); ?></div>
                                <small class="text-muted">Tổng lịch hẹn</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="h4 text-success"><?php echo $stats['completed']; ?></div>
                                <small class="text-muted">Đã hoàn thành</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="h4 text-info"><?php echo $stats['total_patients']; ?></div>
                                <small class="text-muted">Tổng bệnh nhân</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="h4 text-warning"><?php echo $stats['pending']; ?></div>
                                <small class="text-muted">Chờ xác nhận</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <a href="update_profile.php" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-2"></i>Cập nhật thông tin
                        </a>
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>In hồ sơ
                        </button>
                    </div>
                </div>
                
                <!-- Tab: Lịch làm việc -->
                <div class="tab-content" id="schedule-tab">
                    <div class="profile-card">
                        <h5 class="mb-3">Quản lý lịch làm việc</h5>
                        <p class="text-muted">Thiết lập lịch làm việc của bạn theo từng ngày trong tuần.</p>
                        
                        <div class="row g-3">
                            <?php 
                            $days = ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ Nhật'];
                            foreach ($days as $day): 
                            ?>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label"><?php echo $day; ?></div>
                                    <div class="detail-value">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" checked>
                                            <label class="form-check-label">Hoạt động</label>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <input type="time" class="form-control form-control-sm" value="08:00">
                                            </div>
                                            <div class="col-6">
                                                <input type="time" class="form-control form-control-sm" value="17:00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Lưu lịch làm việc
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tab: Lịch bị hủy -->
                <div class="tab-content" id="cancelled-tab">
                    <div class="profile-card">
                        <h5 class="mb-3">
                            <i class="fas fa-calendar-times me-2 text-danger"></i>Lịch hẹn bị hủy
                            <?php if (!empty($cancelled_appointments)): ?>
                                <span class="badge bg-danger ms-2"><?php echo count($cancelled_appointments); ?> lịch</span>
                            <?php endif; ?>
                        </h5>
                        
                        <?php if (empty($cancelled_appointments)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-check fa-4x text-success mb-3"></i>
                                <h5 class="text-muted">Không có lịch hẹn nào bị hủy</h5>
                                <p class="text-muted">Tất cả lịch hẹn đều được giữ nguyên.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Bệnh nhân</th>
                                            <th>Ngày hẹn</th>
                                            <th>Giờ hẹn</th>
                                            <th>Loại khám</th>
                                            <th>Ngày hủy</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cancelled_appointments as $ca): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($ca['patient_name']); ?></div>
                                                        <?php if ($ca['patient_phone']): ?>
                                                            <small class="text-muted"><?php echo htmlspecialchars($ca['patient_phone']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <i class="fas fa-calendar text-muted me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($ca['appointment_date'])); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-clock text-muted me-1"></i>
                                                <?php echo date('H:i', strtotime($ca['appointment_time'])); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $ca['consultation_type'] === 'online' ? 'info' : 'primary'; ?>">
                                                    <i class="fas fa-<?php echo $ca['consultation_type'] === 'online' ? 'video' : 'hospital'; ?> me-1"></i>
                                                    <?php echo $ca['consultation_type'] === 'online' ? 'Trực tuyến' : 'Trực tiếp'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-danger">
                                                    <i class="fas fa-times-circle me-1"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($ca['cancelled_at'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($ca['notes'] ?? 'Không có ghi chú'); ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: Đánh giá -->
                <div class="tab-content" id="reviews-tab">
                    <div class="profile-card">
                        <div class="row align-items-center mb-4">
                            <div class="col-md-4 text-center">
                                <div class="display-3 fw-bold text-warning"><?php echo number_format($avgRating, 1); ?></div>
                                <div class="mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= round($avgRating) ? 'text-warning' : 'text-muted'; ?>" style="font-size: 1.5rem;"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-muted mb-0"><?php echo $totalReviews; ?> đánh giá</p>
                            </div>
                            <div class="col-md-8">
                                <h5 class="mb-3">
                                    <i class="fas fa-star me-2 text-warning"></i>Đánh giá từ bệnh nhân
                                </h5>
                                <p class="text-muted">Đánh giá được thu thập sau mỗi lần khám bệnh (trực tiếp hoặc trực tuyến).</p>
                            </div>
                        </div>
                        
                        <?php if (empty($reviews)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-star fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có đánh giá nào</h5>
                                <p class="text-muted">Đánh giá sẽ xuất hiện sau khi bệnh nhân hoàn thành khám.</p>
                            </div>
                        <?php else: ?>
                            <div class="reviews-list">
                                <?php foreach ($reviews as $review): ?>
                                <div class="review-item mb-3 p-3 bg-light rounded">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                                                <?php echo strtoupper(substr($review['patient_name'] ?? 'B', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($review['patient_name'] ?? 'Bệnh nhân'); ?></div>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                                                    <?php if ($review['consultation_type']): ?>
                                                        <span class="badge bg-<?php echo $review['consultation_type'] === 'Trực tuyến' ? 'info' : 'success'; ?> ms-2">
                                                            <?php echo $review['consultation_type']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                            <div class="fw-bold text-warning"><?php echo $review['rating']; ?>/5</div>
                                        </div>
                                    </div>
                                    <p class="mb-2"><?php echo htmlspecialchars($review['comment'] ?? 'Không có nhận xét'); ?></p>
                                    <?php if ($review['doctor_reply']): ?>
                                        <div class="bg-white p-2 rounded border-start border-primary border-3">
                                            <small class="text-primary fw-bold"><i class="fas fa-reply me-1"></i>Phản hồi của bạn:</small>
                                            <p class="mb-0 small"><?php echo htmlspecialchars($review['doctor_reply']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal xem hồ sơ bệnh nhân -->
    <div class="modal fade" id="patientProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-circle me-2"></i>Hồ sơ bệnh nhân
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="patientProfileContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-primary" onclick="printPatientProfile()">
                        <i class="fas fa-print me-2"></i>In hồ sơ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal tạo hồ sơ bệnh án -->
    <div class="modal fade" id="medicalRecordModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-medical me-2"></i>Tạo Hồ Sơ Bệnh Án
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="medicalRecordForm">
                        <input type="hidden" id="mr_appointment_id" name="appointment_id">
                        <input type="hidden" id="mr_patient_id" name="patient_id">
                        
                        <!-- Phần 1: Thông tin bệnh nhân (Read-only) -->
                        <div class="profile-card mb-4">
                            <h6 class="mb-3"><i class="fas fa-user me-2"></i>Thông tin bệnh nhân</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Họ tên:</strong> <span id="mr_patient_name"></span></p>
                                    <p><strong>Giới tính:</strong> <span id="mr_patient_gender"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Số điện thoại:</strong> <span id="mr_patient_phone"></span></p>
                                    <p><strong>Lý do khám:</strong> <span id="mr_chief_complaint"></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Phần 2: Thông tin khám bệnh -->
                        <div class="profile-card mb-4">
                            <h6 class="mb-3"><i class="fas fa-stethoscope me-2"></i>Thông tin khám bệnh</h6>
                            <div class="mb-3">
                                <label class="form-label">Triệu chứng</label>
                                <textarea class="form-control" name="symptoms_reported" rows="3" placeholder="Mô tả triệu chứng bệnh nhân báo cáo..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kết quả khám</label>
                                <textarea class="form-control" name="examination_findings" rows="3" placeholder="Ghi nhận kết quả khám lâm sàng..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Chẩn đoán <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="diagnosis" rows="2" placeholder="Chẩn đoán bệnh..." required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phác đồ điều trị</label>
                                <textarea class="form-control" name="treatment_plan" rows="3" placeholder="Kế hoạch điều trị..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Phần 3: Chỉ số sinh tồn -->
                        <div class="profile-card mb-4">
                            <h6 class="mb-3"><i class="fas fa-heartbeat me-2"></i>Chỉ số sinh tồn</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Huyết áp (mmHg)</label>
                                    <input type="text" class="form-control" name="blood_pressure" placeholder="VD: 120/80">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Nhịp tim (bpm)</label>
                                    <input type="number" class="form-control" name="heart_rate" placeholder="VD: 75">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Nhiệt độ (°C)</label>
                                    <input type="number" step="0.1" class="form-control" name="temperature" placeholder="VD: 37.0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Cân nặng (kg)</label>
                                    <input type="number" step="0.1" class="form-control" name="weight" placeholder="VD: 65">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Chiều cao (cm)</label>
                                    <input type="number" class="form-control" name="height" placeholder="VD: 170">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">SpO2 (%)</label>
                                    <input type="number" class="form-control" name="spo2" placeholder="VD: 98">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Phần 4: Đơn thuốc -->
                        <div class="profile-card mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0"><i class="fas fa-pills me-2"></i>Đơn thuốc</h6>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addMedicine()">
                                    <i class="fas fa-plus me-1"></i>Thêm thuốc
                                </button>
                            </div>
                            <div id="medicinesList"></div>
                        </div>
                        
                        <!-- Phần 5: Thông tin bổ sung -->
                        <div class="profile-card mb-4">
                            <h6 class="mb-3"><i class="fas fa-info-circle me-2"></i>Thông tin bổ sung</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày tái khám</label>
                                    <input type="date" class="form-control" name="follow_up_date">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ghi chú</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="Ghi chú thêm..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Hủy
                    </button>
                    <button type="button" class="btn btn-primary" onclick="saveMedicalRecord()">
                        <i class="fas fa-save me-1"></i>Lưu hồ sơ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching
        function showTab(tabName, element) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            const targetTab = document.getElementById(tabName + '-tab');
            if (targetTab) {
                targetTab.classList.add('active');
            }
            
            // Update sidebar active state
            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            if (element) {
                element.classList.add('active');
            }
            
            // Update page title
            const titles = {
                'overview': 'Tổng Quan',
                'appointments': 'Quản lý lịch hẹn',
                'cancelled': 'Lịch hẹn bị hủy',
                'reviews': 'Đánh giá từ bệnh nhân',
                'medical-records': 'Hồ sơ bệnh án',
                'profile': 'Hồ sơ cá nhân',
                'schedule': 'Lịch làm việc'
            };
            document.getElementById('page-title').textContent = titles[tabName] || 'Dashboard bác sĩ';
            
            // Load medical records data when tab is opened
            if (tabName === 'medical-records') {
                loadMedicalRecordsData();
            }
        }
        
        // Update appointment status
        function updateStatus(appointmentId, status) {
            const statusText = {
                'confirmed': 'xác nhận',
                'cancelled': 'hủy',
                'completed': 'hoàn thành'
            };
            
            if (!confirm(`Bạn chắc chắn muốn ${statusText[status]} lịch hẹn này?`)) return;
            
            $.post('', {
                action: 'update_status',
                appointment_id: appointmentId,
                status: status
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Có lỗi xảy ra');
                }
            }, 'json').fail(function() {
                alert('Có lỗi xảy ra khi cập nhật');
            });
        }
        
        // View patient profile
        function viewPatientProfile(patientId, patientName) {
            $('#patientProfileModal').modal('show');
            $('#patientProfileContent').html(`
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            `);
            
            // Fetch patient data
            $.post('', {
                action: 'get_patient_profile',
                patient_id: patientId
            }, function(response) {
                if (response.success) {
                    const patient = response.data;
                    const visitStats = response.visit_stats || {};
                    
                    // Sử dụng tên trường tiếng Việt từ database
                    const fullName = patient.ho_ten || patient.full_name || patientName;
                    const email = patient.email || '';
                    const phone = patient.so_dien_thoai || patient.phone || '';
                    const dateOfBirth = patient.ngay_sinh || patient.date_of_birth || null;
                    const gender = patient.gioi_tinh || patient.gender || null;
                    const address = patient.dia_chi || patient.address || '';
                    const username = patient.ten_dang_nhap || patient.username || '';
                    const status = patient.trang_thai || patient.status || 'hoat_dong';
                    const createdAt = patient.ngay_tao || patient.created_at || null;
                    const updatedAt = patient.ngay_cap_nhat || patient.updated_at || null;
                    
                    const age = dateOfBirth ? new Date().getFullYear() - new Date(dateOfBirth).getFullYear() : 'N/A';
                    const genderIcon = gender === 'Nam' || gender === 'male' ? 'fa-mars text-primary' : (gender === 'Nữ' || gender === 'female' ? 'fa-venus text-danger' : 'fa-user text-secondary');
                    const genderText = gender === 'Nam' || gender === 'male' ? 'Nam' : (gender === 'Nữ' || gender === 'female' ? 'Nữ' : null);
                    const registeredDate = createdAt ? new Date(createdAt).toLocaleDateString('vi-VN') : 'N/A';
                    const daysRegistered = patient.days_registered || 0;
                    const monthsRegistered = patient.months_registered || 0;
                    const lastUpdated = updatedAt ? new Date(updatedAt).toLocaleDateString('vi-VN') : 'Chưa cập nhật';
                    const isActive = status === 'hoat_dong' || status === 'active';
                    
                    $('#patientProfileContent').html(`
                        <style>
                            .patient-profile-header {
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                padding: 30px;
                                border-radius: 15px;
                                color: white;
                                margin-bottom: 25px;
                                text-align: center;
                            }
                            .patient-avatar-large {
                                width: 120px;
                                height: 120px;
                                border-radius: 12px;
                                background: white;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin: 0 auto 20px;
                                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                                font-size: 3rem;
                            }
                            .patient-info-grid {
                                display: grid;
                                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                                gap: 20px;
                                margin-bottom: 25px;
                            }
                            .info-card {
                                background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
                                border-radius: 12px;
                                padding: 20px;
                                border-left: 4px solid #667eea;
                                box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                                transition: all 0.3s;
                            }
                            .info-card:hover {
                                transform: translateY(-3px);
                                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                            }
                            .info-card-icon {
                                font-size: 1.5rem;
                                color: #667eea;
                                margin-bottom: 10px;
                            }
                            .info-card-label {
                                font-size: 0.85rem;
                                color: #6c757d;
                                font-weight: 600;
                                margin-bottom: 8px;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            }
                            .info-card-value {
                                font-size: 1.1rem;
                                color: #212529;
                                font-weight: 500;
                            }
                            .stats-row {
                                display: grid;
                                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                                gap: 15px;
                                margin: 25px 0;
                            }
                            .stat-box {
                                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                color: white;
                                padding: 20px;
                                border-radius: 12px;
                                text-align: center;
                                box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                            }
                            .stat-number {
                                font-size: 2rem;
                                font-weight: 700;
                                margin-bottom: 5px;
                            }
                            .stat-label {
                                font-size: 0.85rem;
                                opacity: 0.9;
                            }
                        </style>
                        
                        <div class="patient-profile-header">
                            <div class="patient-avatar-large">
                                <i class="fas ${genderIcon.split(' ')[0]}" style="color: #667eea;"></i>
                            </div>
                            <h3 class="mb-2">${fullName}</h3>
                            <p class="mb-2" style="font-size: 1.1rem; opacity: 0.9;">${age !== 'N/A' ? age + ' tuổi' : ''}</p>
                            <div class="d-flex justify-content-center gap-2 flex-wrap">
                                ${username ? `<span class="badge bg-light text-dark">
                                    <i class="fas fa-user me-1"></i>@${username}
                                </span>` : ''}
                                ${createdAt ? `<span class="badge bg-light text-dark">
                                    <i class="fas fa-calendar-plus me-1"></i>Đăng ký: ${registeredDate}
                                </span>` : ''}
                            </div>
                        </div>
                        
                        <div class="stats-row">
                            <div class="stat-box">
                                <div class="stat-number">${visitStats.total_visits || 0}</div>
                                <div class="stat-label">Tổng lượt khám</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number">${visitStats.completed_visits || 0}</div>
                                <div class="stat-label">Đã hoàn thành</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-number">${monthsRegistered > 0 ? monthsRegistered : daysRegistered}</div>
                                <div class="stat-label">${monthsRegistered > 0 ? 'Tháng' : 'Ngày'} thành viên</div>
                            </div>
                        </div>
                        
                        <div class="patient-info-grid">
                            <div class="info-card">
                                <div class="info-card-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="info-card-label">📧 Email</div>
                                <div class="info-card-value">
                                    ${email || '<span class="text-muted">Chưa cập nhật</span>'}
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="info-card-label">📱 Điện thoại</div>
                                <div class="info-card-value">${phone || '<span class="text-muted">Chưa cập nhật</span>'}</div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-icon">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div class="info-card-label">🎂 Ngày sinh</div>
                                <div class="info-card-value">${dateOfBirth ? new Date(dateOfBirth).toLocaleDateString('vi-VN') : '<span class="text-muted">Chưa cập nhật</span>'}</div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-icon">
                                    <i class="fas ${genderIcon.split(' ')[0]}"></i>
                                </div>
                                <div class="info-card-label">⚧ Giới tính</div>
                                <div class="info-card-value">${genderText || '<span class="text-muted">Chưa cập nhật</span>'}</div>
                            </div>
                            
                            <div class="info-card" style="grid-column: 1 / -1;">
                                <div class="info-card-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="info-card-label">🏠 Địa chỉ</div>
                                <div class="info-card-value">${address || '<span class="text-muted">Chưa cập nhật</span>'}</div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-icon">
                                    <i class="fas fa-user-tag"></i>
                                </div>
                                <div class="info-card-label">Trạng thái tài khoản</div>
                                <div class="info-card-value">
                                    <span class="badge bg-${isActive ? 'success' : 'warning'}">
                                        <i class="fas fa-${isActive ? 'check-circle' : 'exclamation-triangle'} me-1"></i>
                                        ${isActive ? 'Hoạt động' : 'Tạm khóa'}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <div class="info-card-icon">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <div class="info-card-label">Cập nhật cuối</div>
                                <div class="info-card-value">${lastUpdated}</div>
                            </div>
                        </div>
                        
                        ${patient.profile ? `
                        <hr class="my-4">
                        
                        <h5 class="mb-3">
                            <i class="fas fa-notes-medical me-2 text-primary"></i>Thông Tin Y Chẩn
                        </h5>
                        
                        <div class="patient-info-grid">
                            ${patient.profile.nhom_mau ? `
                            <div class="info-card">
                                <div class="info-card-icon">
                                    <i class="fas fa-tint"></i>
                                </div>
                                <div class="info-card-label">🩸 Nhóm máu</div>
                                <div class="info-card-value">${patient.profile.nhom_mau}</div>
                            </div>
                            ` : ''}
                            
                            ${patient.profile.lien_he_khan_cap ? `
                            <div class="info-card">
                                <div class="info-card-icon">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="info-card-label">🚨 Liên hệ khẩn cấp</div>
                                <div class="info-card-value">${patient.profile.lien_he_khan_cap}</div>
                            </div>
                            ` : ''}
                            
                            ${patient.profile.tien_su_benh ? `
                            <div class="info-card" style="grid-column: 1 / -1;">
                                <div class="info-card-icon">
                                    <i class="fas fa-file-medical"></i>
                                </div>
                                <div class="info-card-label">📋 Tiền sử bệnh</div>
                                <div class="info-card-value">${patient.profile.tien_su_benh}</div>
                            </div>
                            ` : ''}
                            
                            ${patient.profile.di_ung ? `
                            <div class="info-card" style="grid-column: 1 / -1; border-left-color: #ef4444;">
                                <div class="info-card-icon" style="color: #ef4444;">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="info-card-label" style="color: #ef4444;">⚠️ Dị ứng</div>
                                <div class="info-card-value" style="color: #ef4444; font-weight: 600;">${patient.profile.di_ung}</div>
                            </div>
                            ` : ''}
                            
                            ${patient.profile.ghi_chu ? `
                            <div class="info-card" style="grid-column: 1 / -1;">
                                <div class="info-card-icon">
                                    <i class="fas fa-sticky-note"></i>
                                </div>
                                <div class="info-card-label">📝 Ghi chú</div>
                                <div class="info-card-value">${patient.profile.ghi_chu}</div>
                            </div>
                            ` : ''}
                        </div>
                        ` : `
                        <hr class="my-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Bệnh nhân chưa cập nhật thông tin y chẩn chi tiết.
                        </div>
                        `}
                        
                        ${response.medical_records && response.medical_records.length > 0 ? `
                        <hr class="my-4">
                        
                        <h5 class="mb-3">
                            <i class="fas fa-file-medical-alt me-2 text-primary"></i>Hồ Sơ Bệnh Án
                        </h5>
                        
                        <div class="accordion" id="medicalRecordsAccordion">
                            ${response.medical_records.map((record, index) => `
                                <div class="accordion-item mb-3" style="border: 1px solid #dee2e6; border-radius: 10px; overflow: hidden;">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button ${index !== 0 ? 'collapsed' : ''}" type="button" 
                                                data-bs-toggle="collapse" data-bs-target="#record${record.id}">
                                            <div class="w-100">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="fas fa-calendar-alt me-2"></i>
                                                        <strong>${new Date(record.appointment_date).toLocaleDateString('vi-VN')}</strong>
                                                        <span class="ms-2 text-muted">${record.appointment_time ? record.appointment_time.substring(0,5) : ''}</span>
                                                    </div>
                                                    <div>
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-user-md me-1"></i>BS. ${record.doctor_name}
                                                        </span>
                                                        ${record.specialty_name ? `<span class="badge bg-secondary ms-1">${record.specialty_name}</span>` : ''}
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="record${record.id}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" 
                                         data-bs-parent="#medicalRecordsAccordion">
                                        <div class="accordion-body">
                                            <div class="row g-3">
                                                ${record.diagnosis ? `
                                                <div class="col-12">
                                                    <div class="info-card" style="border-left-color: #10b981;">
                                                        <div class="info-card-icon" style="color: #10b981;">
                                                            <i class="fas fa-diagnoses"></i>
                                                        </div>
                                                        <div class="info-card-label">🩺 Chẩn Đoán</div>
                                                        <div class="info-card-value">${record.diagnosis}</div>
                                                    </div>
                                                </div>
                                                ` : ''}
                                                
                                                ${record.examination_findings ? `
                                                <div class="col-12">
                                                    <div class="info-card">
                                                        <div class="info-card-icon">
                                                            <i class="fas fa-stethoscope"></i>
                                                        </div>
                                                        <div class="info-card-label">🔍 Kết Quả Khám</div>
                                                        <div class="info-card-value">${record.examination_findings}</div>
                                                    </div>
                                                </div>
                                                ` : ''}
                                                
                                                ${record.prescription ? `
                                                <div class="col-12">
                                                    <div class="info-card" style="border-left-color: #3b82f6;">
                                                        <div class="info-card-icon" style="color: #3b82f6;">
                                                            <i class="fas fa-pills"></i>
                                                        </div>
                                                        <div class="info-card-label">💊 Đơn Thuốc</div>
                                                        <div class="info-card-value" style="white-space: pre-line;">${record.prescription}</div>
                                                    </div>
                                                </div>
                                                ` : ''}
                                                
                                                ${record.treatment_plan ? `
                                                <div class="col-12">
                                                    <div class="info-card">
                                                        <div class="info-card-icon">
                                                            <i class="fas fa-clipboard-list"></i>
                                                        </div>
                                                        <div class="info-card-label">📋 Kế Hoạch Điều Trị</div>
                                                        <div class="info-card-value">${record.treatment_plan}</div>
                                                    </div>
                                                </div>
                                                ` : ''}
                                                
                                                ${record.follow_up_date ? `
                                                <div class="col-md-6">
                                                    <div class="info-card" style="border-left-color: #f59e0b;">
                                                        <div class="info-card-icon" style="color: #f59e0b;">
                                                            <i class="fas fa-calendar-check"></i>
                                                        </div>
                                                        <div class="info-card-label">📅 Ngày Tái Khám</div>
                                                        <div class="info-card-value">${new Date(record.follow_up_date).toLocaleDateString('vi-VN')}</div>
                                                    </div>
                                                </div>
                                                ` : ''}
                                                
                                                ${record.notes ? `
                                                <div class="col-12">
                                                    <div class="info-card">
                                                        <div class="info-card-icon">
                                                            <i class="fas fa-comment-medical"></i>
                                                        </div>
                                                        <div class="info-card-label">📝 Ghi Chú Bác Sĩ</div>
                                                        <div class="info-card-value">${record.notes}</div>
                                                    </div>
                                                </div>
                                                ` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        ` : `
                        <hr class="my-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Chưa có hồ sơ bệnh án nào được ghi nhận.
                        </div>
                        `}
                    `);
                } else {
                    $('#patientProfileContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.message || 'Không thể tải thông tin bệnh nhân'}
                        </div>
                    `);
                }
            }, 'json').fail(function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                $('#patientProfileContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Có lỗi xảy ra khi tải thông tin bệnh nhân<br>
                        <small class="text-muted">Chi tiết: ${error || 'Unknown error'}</small>
                    </div>
                `);
            });
        }
        
        // Start consultation
        function startConsultation(appointmentId) {
            if (confirm('Bắt đầu buổi tư vấn với bệnh nhân?')) {
                window.location.href = `TuVanTrucTuyen.php?appointment_id=${appointmentId}&role=doctor`;
            }
        }
        
        // Filter appointments
        function filterAppointments() {
            const selectedStatus = document.getElementById('status-filter').value;
            const selectedAccountType = document.getElementById('account-type-filter').value;
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const rows = document.querySelectorAll('#appointments-table-body tr[data-status]');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const accountType = row.getAttribute('data-account-type');
                const patientName = row.getAttribute('data-patient-name');
                const patientPhone = row.getAttribute('data-patient-phone');
                const patientEmail = row.getAttribute('data-patient-email');
                
                const statusMatch = selectedStatus === '' || status === selectedStatus;
                const accountTypeMatch = selectedAccountType === '' || accountType === selectedAccountType;
                const searchMatch = searchTerm === '' || 
                                  patientName.includes(searchTerm) || 
                                  patientPhone.includes(searchTerm) || 
                                  patientEmail.includes(searchTerm);
                
                if (statusMatch && accountTypeMatch && searchMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Search appointments
        function searchAppointments() {
            filterAppointments();
        }
        
        // Print patient profile
        function printPatientProfile() {
            const content = document.getElementById('patientProfileContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Hồ sơ bệnh nhân</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            .detail-item { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; }
                            .detail-label { font-weight: 600; margin-bottom: 5px; }
                            @media print { .btn { display: none; } }
                        </style>
                    </head>
                    <body class="p-4">
                        <h3 class="mb-4">Hồ sơ bệnh nhân</h3>
                        ${content}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Start video call consultation
        function startVideoCall(appointmentId, patientId) {
            if (confirm('Bắt đầu cuộc gọi video với bệnh nhân? Hãy đảm bảo bệnh nhân đã sẵn sàng.')) {
                $.post('', {
                    action: 'update_status',
                    appointment_id: appointmentId,
                    status: 'in_progress'
                }, function(response) {
                    if (response.success) {
                        window.location.href = 'TuVanTrucTuyen.php?appointment_id=' + appointmentId + '&type=video&role=doctor';
                    } else {
                        alert('Có lỗi khi khởi tạo cuộc gọi');
                    }
                }, 'json').fail(function() {
                    alert('Có lỗi kết nối');
                });
            }
        }
        
        // Start chat consultation
        function startChatConsultation(appointmentId) {
            if (confirm('Bắt đầu tư vấn qua chat với bệnh nhân?')) {
                $.post('', {
                    action: 'update_status',
                    appointment_id: appointmentId,
                    status: 'in_progress'
                }, function(response) {
                    if (response.success) {
                        window.location.href = 'TuVanTrucTuyen.php?appointment_id=' + appointmentId + '&type=chat&role=doctor';
                    } else {
                        alert('Có lỗi khi mở phòng chat');
                    }
                }, 'json').fail(function() {
                    alert('Có lỗi kết nối');
                });
            }
        }
        
        // Start offline consultation
        function startOfflineConsultation(appointmentId) {
            // Tìm thông tin appointment từ cả 2 danh sách
            const upcomingAppointments = <?php echo json_encode($upcoming); ?>;
            const allAppointments = <?php echo json_encode($all_appointments); ?>;
            
            let appointment = upcomingAppointments.find(a => a.id == appointmentId);
            if (!appointment) {
                appointment = allAppointments.find(a => a.id == appointmentId);
            }
            
            if (!appointment) {
                alert('Không tìm thấy thông tin lịch hẹn');
                return;
            }
            
            // Mở modal form hồ sơ bệnh án trực tiếp
            openMedicalRecordForm(appointmentId, appointment.patient_id, appointment.patient_name);
        }
        
        // Start consultation (legacy)
        function startConsultation(appointmentId) {
            if (confirm('Bắt đầu buổi tư vấn với bệnh nhân?')) {
                window.location.href = 'TuVanTrucTuyen.php?appointment_id=' + appointmentId + '&role=doctor';
            }
        }
        
        // Load medical records data
        function loadMedicalRecordsData() {
            // Load appointments that need examination (confirmed status for today)
            loadNeedExamAppointments();
            
            // Load examined medical records
            loadExaminedRecords();
        }
        
        // Load appointments that need examination
        function loadNeedExamAppointments() {
            const tbody = $('#need-exam-tbody');
            tbody.html('<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary" role="status"></div></td></tr>');
            
            // Use existing appointments data filtered for today and confirmed status
            const today = new Date().toISOString().split('T')[0];
            const needExam = <?php echo json_encode($upcoming); ?>.filter(apt => {
                return apt.status === 'confirmed' && apt.appointment_date === today;
            });
            
            $('#need-exam-count').text(needExam.length);
            
            if (needExam.length === 0) {
                tbody.html('<tr><td colspan="5" class="text-center text-muted">Không có lịch hẹn cần khám hôm nay</td></tr>');
                return;
            }
            
            let html = '';
            needExam.forEach(apt => {
                const consultationType = (apt.consultation_type || 'offline') === 'online' ? 
                    '<span class="badge bg-success"><i class="fas fa-video me-1"></i>Trực tuyến</span>' : 
                    '<span class="badge bg-primary"><i class="fas fa-hospital me-1"></i>Tại phòng khám</span>';
                
                html += `
                    <tr>
                        <td>
                            <div class="text-center">
                                <div class="fw-bold">${apt.appointment_time ? apt.appointment_time.substring(0,5) : ''}</div>
                                <small class="text-muted">${new Date(apt.appointment_date).toLocaleDateString('vi-VN')}</small>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold">${apt.patient_name || 'N/A'}</div>
                            ${apt.patient_phone ? `<small class="text-muted d-block"><i class="fas fa-phone me-1"></i>${apt.patient_phone}</small>` : ''}
                        </td>
                        <td>${apt.chief_complaint || '<span class="text-muted">Chưa có thông tin</span>'}</td>
                        <td>${consultationType}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="openMedicalRecordForm(${apt.id}, ${apt.patient_id}, '${apt.patient_name.replace(/'/g, "\\'")}')">
                                <i class="fas fa-file-medical me-1"></i>Bắt đầu khám
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.html(html);
        }
        
        // Load examined medical records
        function loadExaminedRecords() {
            const tbody = $('#examined-tbody');
            tbody.html('<tr><td colspan="4" class="text-center"><div class="spinner-border text-primary" role="status"></div></td></tr>');
            
            // TODO: This will be loaded from medical_records table via AJAX
            // For now, show placeholder
            setTimeout(() => {
                tbody.html('<tr><td colspan="4" class="text-center text-muted">Chưa có hồ sơ bệnh án nào</td></tr>');
                $('#examined-count').text(0);
            }, 500);
        }
        
        // Open medical record form modal
        function openMedicalRecordForm(appointmentId, patientId, patientName) {
            console.log('Opening medical record form:', {appointmentId, patientId, patientName});
            
            try {
                // Reset form
                const form = document.getElementById('medicalRecordForm');
                if (!form) {
                    console.error('Form not found!');
                    alert('Lỗi: Không tìm thấy form. Vui lòng reload trang.');
                    return;
                }
                form.reset();
                
                const medicinesList = document.getElementById('medicinesList');
                if (medicinesList) {
                    medicinesList.innerHTML = '';
                }
                
                // Set hidden fields
                document.getElementById('mr_appointment_id').value = appointmentId;
                document.getElementById('mr_patient_id').value = patientId;
                
                // Set default patient info first
                $('#mr_patient_name').text(patientName || 'Đang tải...');
                $('#mr_patient_gender').text('Đang tải...');
                $('#mr_patient_phone').text('Đang tải...');
                $('#mr_chief_complaint').text('Đang tải...');
                
                // Show modal immediately
                const modal = new bootstrap.Modal(document.getElementById('medicalRecordModal'));
                modal.show();
                
                // Load patient info in background
                $.post('', {
                    action: 'get_patient_profile',
                    patient_id: patientId
                }, function(response) {
                    console.log('Patient profile response:', response);
                    if (response.success) {
                        const patient = response.data;
                        const appointments = response.appointments || [];
                        const currentAppointment = appointments.find(a => a.id == appointmentId);
                        
                        $('#mr_patient_name').text(patient.full_name || patientName);
                        $('#mr_patient_gender').text(patient.gender === 'male' ? 'Nam' : (patient.gender === 'female' ? 'Nữ' : 'Chưa rõ'));
                        $('#mr_patient_phone').text(patient.phone || 'Chưa có');
                        $('#mr_chief_complaint').text(currentAppointment ? (currentAppointment.chief_complaint || 'Chưa có') : 'Chưa có');
                    } else {
                        console.warn('Failed to load patient profile:', response.message);
                        $('#mr_patient_name').text(patientName);
                        $('#mr_patient_gender').text('Chưa rõ');
                        $('#mr_patient_phone').text('Chưa có');
                        $('#mr_chief_complaint').text('Chưa có');
                    }
                }, 'json').fail(function(xhr, status, error) {
                    console.error('AJAX error:', {xhr, status, error});
                    // Keep default values
                    $('#mr_patient_name').text(patientName);
                    $('#mr_patient_gender').text('Chưa rõ');
                    $('#mr_patient_phone').text('Chưa có');
                    $('#mr_chief_complaint').text('Chưa có');
                });
            } catch (error) {
                console.error('Error in openMedicalRecordForm:', error);
                alert('Lỗi: ' + error.message);
            }
        }
        
        // Add medicine to list
        let medicineCount = 0;
        function addMedicine() {
            medicineCount++;
            const html = `
                <div class="medicine-item border rounded p-3 mb-3" id="medicine-${medicineCount}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Thuốc #${medicineCount}</h6>
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeMedicine(${medicineCount})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">Tên thuốc <span class="text-danger">*</span></label>
                            <input type="text" class="form-control medicine-name" placeholder="VD: Paracetamol 500mg" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Liều lượng</label>
                            <input type="text" class="form-control medicine-dosage" placeholder="VD: 500mg">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="form-label">Số lượng</label>
                            <input type="number" class="form-control medicine-quantity" placeholder="VD: 30">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Tần suất</label>
                            <input type="text" class="form-control medicine-frequency" placeholder="VD: 3 lần/ngày">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Thời gian dùng</label>
                            <input type="text" class="form-control medicine-duration" placeholder="VD: 7 ngày">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Hướng dẫn</label>
                            <input type="text" class="form-control medicine-instructions" placeholder="VD: Sau bữa ăn">
                        </div>
                    </div>
                </div>
            `;
            $('#medicinesList').append(html);
        }
        
        // Remove medicine from list
        function removeMedicine(id) {
            $(`#medicine-${id}`).remove();
        }
        
        // Save medical record
        function saveMedicalRecord() {
            const form = document.getElementById('medicalRecordForm');
            
            // Validate required fields
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Collect form data
            const formData = new FormData(form);
            const data = {
                appointment_id: formData.get('appointment_id'),
                patient_id: formData.get('patient_id'),
                symptoms_reported: formData.get('symptoms_reported'),
                examination_findings: formData.get('examination_findings'),
                diagnosis: formData.get('diagnosis'),
                treatment_plan: formData.get('treatment_plan'),
                notes: formData.get('notes'),
                follow_up_date: formData.get('follow_up_date'),
                vital_signs: {
                    blood_pressure: formData.get('blood_pressure'),
                    heart_rate: formData.get('heart_rate'),
                    temperature: formData.get('temperature'),
                    weight: formData.get('weight'),
                    height: formData.get('height'),
                    spo2: formData.get('spo2')
                },
                medicines: []
            };
            
            // Collect medicines
            $('.medicine-item').each(function() {
                const medicine = {
                    medicine_name: $(this).find('.medicine-name').val(),
                    dosage: $(this).find('.medicine-dosage').val(),
                    quantity: $(this).find('.medicine-quantity').val(),
                    frequency: $(this).find('.medicine-frequency').val(),
                    duration: $(this).find('.medicine-duration').val(),
                    instructions: $(this).find('.medicine-instructions').val()
                };
                
                if (medicine.medicine_name) {
                    data.medicines.push(medicine);
                }
            });
            
            // Show loading
            const saveBtn = event.target;
            const originalText = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';
            
            // Send to API
            $.ajax({
                url: 'api_save_medical_record.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                    if (response.success) {
                        alert('✓ Lưu hồ sơ bệnh án thành công!');
                        $('#medicalRecordModal').modal('hide');
                        
                        // Reload data
                        if (typeof loadMedicalRecordsData === 'function') {
                            loadMedicalRecordsData();
                        }
                        location.reload(); // Reload page to update lists
                    } else {
                        alert('Lỗi: ' + (response.message || 'Không thể lưu hồ sơ'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('Lỗi kết nối: ' + error);
                },
                complete: function() {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            });
        }
        
        // Avatar upload functions
        function openAvatarUpload() {
            document.getElementById('avatarInput').click();
        }
        
        function uploadAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WebP)');
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File quá lớn. Tối đa 5MB');
                    return;
                }
                
                // Create FormData
                const formData = new FormData();
                formData.append('action', 'upload_avatar');
                formData.append('avatar', file);
                
                // Show loading
                const avatars = document.querySelectorAll('.doctor-avatar');
                avatars.forEach(avatar => {
                    avatar.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                });
                
                // Upload
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('✓ ' + response.message);
                            // Update all avatars on page
                            avatars.forEach(avatar => {
                                avatar.innerHTML = '<img src="' + response.avatar_url + '?t=' + Date.now() + '" alt="Avatar">';
                            });
                        } else {
                            alert('Lỗi: ' + response.message);
                            // Restore icon
                            avatars.forEach(avatar => {
                                avatar.innerHTML = '<i class="fas fa-user-md"></i>';
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Lỗi upload: ' + error);
                        // Restore icon
                        avatars.forEach(avatar => {
                            avatar.innerHTML = '<i class="fas fa-user-md"></i>';
                        });
                    }
                });
            }
        }
        
        // Initialize
        $(document).ready(function() {
            console.log('Doctor Dashboard loaded successfully');
            
            // FIX: Remove any modal backdrops that might be blocking the screen
            function removeOverlay() {
                // Remove modal backdrops
                $('.modal-backdrop').remove();
                
                // Remove modal-open class
                $('body').removeClass('modal-open');
                $('html').removeClass('modal-open');
                
                // Reset overflow
                $('body').css({
                    'overflow': 'auto',
                    'overflow-x': 'hidden',
                    'padding-right': '0'
                });
                $('html').css({
                    'overflow': 'auto',
                    'overflow-x': 'hidden'
                });
                
                // Enable pointer events on all elements
                $('body').css('pointer-events', 'auto');
                $('html').css('pointer-events', 'auto');
                $('*').css('pointer-events', ''); // Reset to default
                
                // Remove any inline styles that might block clicks
                $('body').removeAttr('style');
                
                // Ensure main content is clickable
                $('.main-content, .sidebar, .container-fluid').css('pointer-events', 'auto');
                
                // Remove any stuck overlays or fixed elements blocking clicks
                $('[style*="position: fixed"]').each(function() {
                    var $el = $(this);
                    if ($el.hasClass('modal-backdrop') || $el.css('z-index') > 1000) {
                        if (!$el.hasClass('modal') && !$el.closest('.modal').length) {
                            $el.remove();
                        }
                    }
                });
                
                console.log('✓ Overlay fix applied');
            }
            
            // Run fix multiple times to ensure it works
            removeOverlay(); // Run immediately
            setTimeout(removeOverlay, 100); // Run after 100ms
            setTimeout(removeOverlay, 500); // Run after 500ms
            setTimeout(removeOverlay, 1000); // Run after 1s
            setTimeout(removeOverlay, 2000); // Run after 2s
            
            // Also run when clicking anywhere (in case overlay appears later)
            $(document).on('click', function(e) {
                // If click doesn't reach any interactive element, try removing overlay
                if ($(e.target).is('body, html, .container-fluid')) {
                    removeOverlay();
                }
            });
            
            // Fix for Bootstrap modal issues
            $(document).on('hidden.bs.modal', function() {
                setTimeout(removeOverlay, 100);
            });
        });
    </script>
</body>
</html>
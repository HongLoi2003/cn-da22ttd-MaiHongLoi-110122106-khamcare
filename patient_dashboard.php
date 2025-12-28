<?php
/**
 * Dashboard cho bệnh nhân
 */

session_start();
require_once 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: TaiKhoan.php?redirect=patient_dashboard.php');
    exit;
}

// Chỉ cho phép role patient
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'patient') {
    header('Location: TaiKhoan.php?redirect=patient_dashboard.php&error=not_patient');
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo 'Lỗi kết nối database';
    exit;
}

// Lấy thông tin bệnh nhân
$stmt = $pdo->prepare("SELECT id, ten_dang_nhap as username, email, ho_ten as full_name, 
                              so_dien_thoai as phone, ngay_sinh as date_of_birth, gioi_tinh as gender,
                              dia_chi as address, vai_tro as role, trang_thai as status, ngay_tao as created_at
                       FROM nguoi_dung WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if (!$patient) {
    echo 'Không tìm thấy thông tin bệnh nhân.';
    exit;
}

// Thống kê nhanh
$today = date('Y-m-d');
$stats = [
    'total_appointments' => 0,
    'upcoming' => 0,
    'completed' => 0,
    'pending' => 0,
    'cancelled' => 0
];

// Tổng số lịch hẹn
$stmt = $pdo->prepare("SELECT COUNT(*) FROM lich_hen WHERE benh_nhan_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats['total_appointments'] = (int)$stmt->fetchColumn();

// Lịch hẹn sắp tới
$stmt = $pdo->prepare("SELECT COUNT(*) FROM lich_hen WHERE benh_nhan_id = ? AND ngay_hen >= ?");
$stmt->execute([$_SESSION['user_id'], $today]);
$stats['upcoming'] = (int)$stmt->fetchColumn();

// Đếm theo trạng thái
$stmt = $pdo->prepare("SELECT trang_thai as status, COUNT(*) AS total FROM lich_hen WHERE benh_nhan_id = ? GROUP BY trang_thai");
$stmt->execute([$_SESSION['user_id']]);
foreach ($stmt->fetchAll() as $row) {
    $k = $row['status'];
    if (isset($stats[$k])) $stats[$k] = (int)$row['total'];
}

// Lấy lịch hẹn sắp tới
$upcoming = [];
$stmt = $pdo->prepare("SELECT a.*, 
                              u.full_name as doctor_name,
                              s.name as specialty_name,
                              d.consultation_fee
                       FROM appointments a
                       JOIN doctors d ON a.doctor_id = d.id
                       JOIN users u ON d.user_id = u.id
                       LEFT JOIN specialties s ON d.specialty_id = s.id
                       WHERE a.patient_id = ? 
                         AND DATE(a.appointment_date) >= ?
                       ORDER BY a.appointment_date ASC, a.appointment_time ASC
                       LIMIT 10");
$stmt->execute([$_SESSION['user_id'], $today]);
$upcoming = $stmt->fetchAll();

// Lấy tất cả lịch hẹn
$all_appointments = [];
$stmt = $pdo->prepare("SELECT a.*, 
                              u.full_name as doctor_name,
                              s.name as specialty_name,
                              d.consultation_fee,
                              p.amount as paid_amount,
                              p.payment_method,
                              p.status as payment_status
                       FROM appointments a
                       JOIN doctors d ON a.doctor_id = d.id
                       JOIN users u ON d.user_id = u.id
                       LEFT JOIN specialties s ON d.specialty_id = s.id
                       LEFT JOIN payments p ON a.id = p.appointment_id
                       WHERE a.patient_id = ?
                       ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$stmt->execute([$_SESSION['user_id']]);
$all_appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bệnh nhân - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/doctor-dashboard.css">
    <style>
        .patient-avatar {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .appointment-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .status-pending { border-left-color: #f59e0b; }
        .status-confirmed { border-left-color: #10b981; }
        .status-completed { border-left-color: #3b82f6; }
        .status-cancelled { border-left-color: #ef4444; }
        .status-draft { border-left-color: #6b7280; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="doctor-info">
                        <div class="doctor-avatar patient-avatar mx-auto">
                            <i class="fas fa-user"></i>
                        </div>
                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($patient['full_name'] ?: $patient['username']); ?></h6>
                        <small class="text-muted d-block">Bệnh nhân</small>
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
                        <a class="nav-link" href="#" onclick="showTab('profile', this)">
                            <i class="fas fa-user-circle me-2"></i>Hồ sơ
                        </a>
                        <hr>
                        <a class="nav-link" href="TimKiemBS.php">
                            <i class="fas fa-search me-2"></i>Tìm bác sĩ
                        </a>
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="page-header">
                    <h1 class="page-title" id="page-title">Dashboard bệnh nhân</h1>
                    <p class="text-muted mb-0">Chào mừng trở lại, <?php echo htmlspecialchars($patient['full_name'] ?: $patient['username']); ?></p>
                </div>
                
                <!-- Tab: Tổng quan -->
                <div class="tab-content active" id="overview-tab">
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="kpi-card text-center">
                                <div class="kpi-number text-primary"><?php echo $stats['total_appointments']; ?></div>
                                <div class="kpi-label">
                                    <i class="fas fa-calendar me-2"></i>Tổng lịch hẹn
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card text-center">
                                <div class="kpi-number text-success"><?php echo $stats['upcoming']; ?></div>
                                <div class="kpi-label">
                                    <i class="fas fa-calendar-plus me-2"></i>Sắp tới
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card text-center">
                                <div class="kpi-number text-warning"><?php echo $stats['pending']; ?></div>
                                <div class="kpi-label">
                                    <i class="fas fa-clock me-2"></i>Chờ xác nhận
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card text-center">
                                <div class="kpi-number text-info"><?php echo $stats['completed']; ?></div>
                                <div class="kpi-label">
                                    <i class="fas fa-check-circle me-2"></i>Hoàn thành
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Lịch hẹn sắp tới</h5>
                            <a href="TimKiemBS.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Đặt lịch mới
                            </a>
                        </div>
                        
                        <?php if (!empty($upcoming)): ?>
                            <?php foreach ($upcoming as $a): ?>
                                <div class="appointment-card status-<?php echo $a['status']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h6 class="mb-1">
                                                <i class="fas fa-user-md me-2 text-primary"></i>
                                                BS. <?php echo htmlspecialchars($a['doctor_name']); ?>
                                            </h6>
                                            <p class="text-muted mb-1">
                                                <i class="fas fa-stethoscope me-2"></i>
                                                <?php echo htmlspecialchars($a['specialty_name']); ?>
                                            </p>
                                            <p class="mb-1">
                                                <i class="fas fa-calendar me-2"></i>
                                                <?php echo date('d/m/Y', strtotime($a['appointment_date'])); ?> - 
                                                <?php echo substr($a['appointment_time'], 0, 5); ?>
                                            </p>
                                            <p class="mb-0">
                                                <i class="fas fa-<?php echo $a['consultation_type'] == 'online' ? 'video' : 'hospital'; ?> me-2"></i>
                                                <?php echo $a['consultation_type'] == 'online' ? 'Tư vấn trực tuyến' : 'Khám tại phòng khám'; ?>
                                            </p>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <span class="badge bg-<?php 
                                                echo $a['status'] === 'pending' ? 'warning' : 
                                                    ($a['status'] === 'confirmed' ? 'success' : 
                                                    ($a['status'] === 'completed' ? 'info' : 'secondary')); 
                                            ?> mb-2">
                                                <?php 
                                                $statusText = [
                                                    'pending' => 'Chờ xác nhận',
                                                    'confirmed' => 'Đã xác nhận',
                                                    'completed' => 'Hoàn thành',
                                                    'cancelled' => 'Đã hủy',
                                                    'draft' => 'Nháp'
                                                ];
                                                echo $statusText[$a['status']] ?? ucfirst($a['status']);
                                                ?>
                                            </span>
                                            <div class="text-success fw-bold">
                                                <?php echo number_format($a['consultation_fee'], 0, ',', '.'); ?> VNĐ
                                            </div>
                                            <?php if ($a['status'] === 'confirmed' && $a['consultation_type'] === 'online'): ?>
                                                <div class="mt-2">
                                                    <a href="TuVanTrucTuyen.php?appointment_id=<?php echo $a['id']; ?>&role=patient" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-video me-1"></i>Tham gia
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Chưa có lịch hẹn nào</h6>
                                <a href="TimKiemBS.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-plus me-2"></i>Đặt lịch khám ngay
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tab: Lịch hẹn -->
                <div class="tab-content" id="appointments-tab">
                    <div class="profile-card">
                        <h5 class="mb-3">Tất cả lịch hẹn</h5>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Mã LH</th>
                                        <th>Bác sĩ</th>
                                        <th>Ngày & Giờ</th>
                                        <th>Loại khám</th>
                                        <th>Trạng thái</th>
                                        <th>Thanh toán</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($all_appointments)): ?>
                                        <?php foreach ($all_appointments as $a): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad($a['id'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold">BS. <?php echo htmlspecialchars($a['doctor_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($a['specialty_name']); ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?php echo date('d/m/Y', strtotime($a['appointment_date'])); ?></div>
                                                <small class="text-primary fw-bold"><?php echo substr($a['appointment_time'], 0, 5); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $a['consultation_type'] === 'online' ? 'success' : 'primary'; ?>">
                                                    <i class="fas fa-<?php echo $a['consultation_type'] === 'online' ? 'video' : 'hospital'; ?> me-1"></i>
                                                    <?php echo $a['consultation_type'] === 'online' ? 'Trực tuyến' : 'Tại phòng khám'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $a['status'] === 'pending' ? 'warning' : 
                                                        ($a['status'] === 'confirmed' ? 'success' : 
                                                        ($a['status'] === 'completed' ? 'info' : 
                                                        ($a['status'] === 'draft' ? 'secondary' : 'danger'))); 
                                                ?>">
                                                    <?php echo $statusText[$a['status']] ?? ucfirst($a['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($a['payment_status'] === 'completed'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Đã thanh toán
                                                    </span>
                                                    <div class="small text-muted">
                                                        <?php echo number_format($a['paid_amount'], 0, ',', '.'); ?> VNĐ
                                                    </div>
                                                <?php elseif ($a['status'] === 'draft'): ?>
                                                    <a href="booking_flow.php?step=3&doctor_id=<?php echo $a['doctor_id']; ?>" 
                                                       class="btn btn-sm btn-warning">
                                                        <i class="fas fa-credit-card me-1"></i>Thanh toán
                                                    </a>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Chưa thanh toán</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($a['status'] === 'confirmed' && $a['consultation_type'] === 'online'): ?>
                                                    <a href="TuVanTrucTuyen.php?appointment_id=<?php echo $a['id']; ?>&role=patient" 
                                                       class="btn btn-sm btn-success">
                                                        <i class="fas fa-video me-1"></i>Tham gia
                                                    </a>
                                                <?php elseif ($a['status'] === 'draft'): ?>
                                                    <a href="booking_flow.php?step=2&doctor_id=<?php echo $a['doctor_id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-edit me-1"></i>Hoàn thành
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-info" disabled>
                                                        <i class="fas fa-eye me-1"></i>Xem
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                Chưa có lịch hẹn nào
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Tab: Hồ sơ -->
                <div class="tab-content" id="profile-tab">
                    <div class="profile-card">
                        <h5 class="mb-3">Thông tin cá nhân</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-user me-2"></i>Họ và tên
                                    </div>
                                    <div class="detail-value"><?php echo htmlspecialchars($patient['full_name'] ?: 'Chưa cập nhật'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </div>
                                    <div class="detail-value"><?php echo htmlspecialchars($patient['email']); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-phone me-2"></i>Số điện thoại
                                    </div>
                                    <div class="detail-value"><?php echo htmlspecialchars($patient['phone'] ?: 'Chưa cập nhật'); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-birthday-cake me-2"></i>Ngày sinh
                                    </div>
                                    <div class="detail-value">
                                        <?php 
                                        if ($patient['date_of_birth']) {
                                            echo date('d/m/Y', strtotime($patient['date_of_birth']));
                                        } else {
                                            echo 'Chưa cập nhật';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="update_profile.php" class="btn btn-primary">
                                <i class="fas fa-edit me-2"></i>Cập nhật thông tin
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/doctor-dashboard.js"></script>
</body>
</html>
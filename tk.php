<?php

session_start();
require_once 'db_config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401);
        echo '<div class="modal-wrap"><div class="modal"><h3>Chưa đăng nhập</h3><p>Vui lòng <a href="TaiKhoan.php">đăng nhập</a> để xem thông tin tài khoản.</p></div></div>';
        exit;
    }
    header('Location: TaiKhoan.php');
    exit;
}

// Lấy thông tin user từ database
$user = getUserById($_SESSION['user_id']);
if (!$user) {
    session_destroy();
    header('Location: TaiKhoan.php');
    exit;
}

// Xử lý hủy lịch hẹn
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $pdo = getDBConnection();
    
    if ($pdo) {
        // Kiểm tra lịch hẹn thuộc về user này và chưa bị hủy
        $stmt = $pdo->prepare("SELECT * FROM lich_hen WHERE id = ? AND benh_nhan_id = ? AND trang_thai IN ('cho_xac_nhan', 'da_xac_nhan')");
        $stmt->execute([$appointment_id, $_SESSION['user_id']]);
        $appointment = $stmt->fetch();
        
        if ($appointment) {
            $updateStmt = $pdo->prepare("UPDATE lich_hen SET trang_thai = 'da_huy', ngay_cap_nhat = NOW() WHERE id = ?");
            if ($updateStmt->execute([$appointment_id])) {
                $message = 'Đã hủy lịch hẹn thành công!';
                $messageType = 'success';
            } else {
                $message = 'Có lỗi xảy ra khi hủy lịch hẹn.';
                $messageType = 'error';
            }
        } else {
            $message = 'Không tìm thấy lịch hẹn hoặc lịch hẹn không thể hủy.';
            $messageType = 'error';
        }
    }
}

// Lấy danh sách lịch hẹn của user
$appointments = [];
$pdo = getDBConnection();
if ($pdo) {
    $stmt = $pdo->prepare("
        SELECT lh.*, b.ho_ten as ten_bac_si, c.ten as ten_chuyen_khoa
        FROM lich_hen lh
        LEFT JOIN bac_si b ON lh.bac_si_id = b.id
        LEFT JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id
        WHERE lh.benh_nhan_id = ?
        ORDER BY lh.ngay_hen DESC, lh.gio_hen DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $appointments = $stmt->fetchAll();
}

// Hàm format trạng thái
function formatStatus($status) {
    $statusMap = [
        'cho_xac_nhan' => ['text' => 'Chờ xác nhận', 'class' => 'status-pending'],
        'da_xac_nhan' => ['text' => 'Đã xác nhận', 'class' => 'status-confirmed'],
        'hoan_thanh' => ['text' => 'Hoàn thành', 'class' => 'status-completed'],
        'da_huy' => ['text' => 'Đã hủy', 'class' => 'status-cancelled'],
        'vang_mat' => ['text' => 'Vắng mặt', 'class' => 'status-absent']
    ];
    return $statusMap[$status] ?? ['text' => $status, 'class' => 'status-default'];
}

// Hàm format loại tư vấn
function formatConsultationType($type) {
    return $type === 'truc_tuyen' ? 'Trực tuyến' : 'Trực tiếp';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin tài khoản - KhamCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="global-font-override.css">
    <link rel="stylesheet" href="square-icons-override.css">
    <link rel="stylesheet" href="fix-icons-visibility.css">
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-light: #38bdf8;
            --primary-dark: #0284c7;
            --accent: #10b981;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --white: #ffffff;
            --radius: 12px;
            --radius-lg: 16px;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--gray-800);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .account-card {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 30px;
            margin-bottom: 20px;
            position: relative;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--gray-100);
            border: none;
            border-radius: 10px;
            width: 36px;
            height: 36px;
            font-size: 18px;
            color: var(--gray-600);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .close-btn:hover {
            background: var(--danger);
            color: var(--white);
        }

        .header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .avatar {
            width: 70px;
            height: 70px;
            border-radius: var(--radius);
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            color: var(--white);
            box-shadow: var(--shadow);
        }

        .user-info h1 {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }

        .user-info p {
            font-size: 14px;
            color: var(--gray-600);
            background: rgba(14, 165, 233, 0.1);
            padding: 4px 10px;
            border-radius: 6px;
            display: inline-block;
        }

        .detail-row {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 15px;
        }

        .detail-row label {
            font-size: 13px;
            color: var(--gray-600);
            font-weight: 600;
            text-transform: uppercase;
        }

        .detail-row .value {
            padding: 12px 14px;
            background: var(--gray-50);
            border-radius: var(--radius);
            font-size: 15px;
            color: var(--gray-800);
            border: 1px solid var(--gray-200);
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            flex: 1;
            padding: 12px 16px;
            border-radius: var(--radius);
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: var(--white);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
        }

        /* Appointments Section */
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary);
        }

        .appointments-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .appointment-card {
            background: var(--gray-50);
            border-radius: var(--radius);
            padding: 16px;
            border: 1px solid var(--gray-200);
            transition: var(--transition);
        }

        .appointment-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow);
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .doctor-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .specialty {
            font-size: 13px;
            color: var(--gray-500);
            margin-top: 2px;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-absent {
            background: var(--gray-200);
            color: var(--gray-600);
        }

        .appointment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--gray-600);
        }

        .detail-item i {
            color: var(--primary);
            width: 16px;
        }

        .appointment-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-cancel {
            padding: 6px 14px;
            background: var(--white);
            border: 1px solid var(--danger);
            color: var(--danger);
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-cancel:hover {
            background: var(--danger);
            color: var(--white);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gray-300);
            margin-bottom: 15px;
        }

        .empty-state p {
            margin-bottom: 15px;
        }

        .btn-book {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--primary);
            color: var(--white);
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-book:hover {
            background: var(--primary-dark);
        }

        /* Alert Messages */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: var(--white);
            border-radius: var(--radius-lg);
            padding: 24px;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .modal h3 {
            margin-bottom: 10px;
            color: var(--gray-800);
        }

        .modal p {
            color: var(--gray-600);
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-actions button {
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-modal-cancel {
            background: var(--gray-200);
            border: none;
            color: var(--gray-700);
        }

        .btn-modal-confirm {
            background: var(--danger);
            border: none;
            color: var(--white);
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--gray-200);
            padding-bottom: 0;
        }

        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-500);
            cursor: pointer;
            position: relative;
            transition: var(--transition);
        }

        .tab-btn.active {
            color: var(--primary);
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary);
        }

        .tab-badge {
            background: var(--danger);
            color: white;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .cancelled-card {
            background: #fef2f2;
            border: 1px solid #fecaca;
        }

        .cancelled-card .status-badge {
            background: #fee2e2;
            color: #991b1b;
        }

        .cancelled-info {
            background: #fff5f5;
            border-left: 3px solid var(--danger);
            padding: 10px 15px;
            margin-top: 10px;
            border-radius: 0 8px 8px 0;
            font-size: 13px;
            color: #991b1b;
        }

        @media (max-width: 640px) {
            .header { flex-direction: column; text-align: center; }
            .actions { flex-direction: column; }
            .appointment-details { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Thông tin tài khoản -->
        <div class="account-card">
            <a href="TrangChu.php" class="close-btn" aria-label="Về trang chủ">&times;</a>
            
            <div class="header">
                <div class="avatar">
                    <?= strtoupper(substr($user['full_name'] ?? '', 0, 1)) ?>
                </div>
                <div class="user-info">
                    <h1><?= htmlspecialchars($user['full_name'] ?? 'Người dùng') ?></h1>
                    <p><?= htmlspecialchars($user['role'] === 'patient' ? 'Bệnh nhân' : $user['role']) ?></p>
                </div>
            </div>

            <div class="detail-row">
                <label>Tên đăng nhập</label>
                <div class="value"><?= htmlspecialchars($user['username'] ?? '') ?></div>
            </div>

            <div class="detail-row">
                <label>Email</label>
                <div class="value"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            </div>

            <div class="detail-row">
                <label>Số điện thoại</label>
                <div class="value"><?= htmlspecialchars($user['phone'] ?? 'Chưa cập nhật') ?></div>
            </div>

            <div class="actions">
                <a href="update_profile.php" class="btn btn-primary">
                    <i class="fas fa-user-edit"></i> Cập nhật hồ sơ
                </a>
                <form method="post" action="logout.php" style="flex:1;margin:0">
                    <button type="submit" class="btn btn-danger" style="width:100%">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </button>
                </form>
            </div>
        </div>

        <!-- Lịch hẹn đã đặt -->
        <div class="account-card">
            <h2 class="section-title">
                <i class="fas fa-calendar-check"></i> Lịch hẹn của tôi
            </h2>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php 
            // Đếm số lịch đã hủy
            $cancelledCount = count(array_filter($appointments, function($apt) {
                return $apt['trang_thai'] === 'da_huy';
            }));
            ?>

            <!-- Tabs -->
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('upcoming')">Sắp tới</button>
                <button class="tab-btn" onclick="showTab('cancelled')">
                    Đã hủy
                    <?php if ($cancelledCount > 0): ?>
                        <span class="tab-badge"><?= $cancelledCount ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn" onclick="showTab('history')">Lịch sử</button>
            </div>

            <!-- Tab: Sắp tới -->
            <div id="tab-upcoming" class="tab-content active">
                <?php 
                $upcomingAppointments = array_filter($appointments, function($apt) {
                    return in_array($apt['trang_thai'], ['cho_xac_nhan', 'da_xac_nhan']) 
                           && $apt['ngay_hen'] >= date('Y-m-d');
                });
                ?>
                
                <?php if (empty($upcomingAppointments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>Bạn chưa có lịch hẹn nào sắp tới</p>
                        <a href="DatLichK.php" class="btn-book">
                            <i class="fas fa-plus"></i> Đặt lịch khám
                        </a>
                    </div>
                <?php else: ?>
                    <div class="appointments-list">
                        <?php foreach ($upcomingAppointments as $apt): 
                            $status = formatStatus($apt['trang_thai']);
                            $isPending = $apt['trang_thai'] === 'cho_xac_nhan';
                            $isConfirmed = $apt['trang_thai'] === 'da_xac_nhan';
                        ?>
                            <div class="appointment-card" style="<?= $isPending ? 'border-left: 4px solid #f59e0b;' : ($isConfirmed ? 'border-left: 4px solid #10b981;' : '') ?>">
                                <div class="appointment-header">
                                    <div>
                                        <div class="doctor-name">BS. <?= htmlspecialchars($apt['ten_bac_si'] ?? 'Chưa xác định') ?></div>
                                        <div class="specialty"><?= htmlspecialchars($apt['ten_chuyen_khoa'] ?? '') ?></div>
                                    </div>
                                    <span class="status-badge <?= $status['class'] ?>"><?= $status['text'] ?></span>
                                </div>
                                
                                <?php if ($isPending): ?>
                                <div style="background: #fef3c7; padding: 10px 15px; border-radius: 8px; margin-bottom: 12px; font-size: 0.9rem; color: #92400e;">
                                    <i class="fas fa-hourglass-half"></i> 
                                    <strong>Đang chờ bác sĩ xác nhận.</strong> Bạn sẽ được thông báo khi bác sĩ chấp nhận lịch hẹn.
                                </div>
                                <?php elseif ($isConfirmed): ?>
                                <div style="background: #d1fae5; padding: 10px 15px; border-radius: 8px; margin-bottom: 12px; font-size: 0.9rem; color: #065f46;">
                                    <i class="fas fa-check-circle"></i> 
                                    <strong>Bác sĩ đã xác nhận lịch hẹn.</strong> Vui lòng đến đúng giờ.
                                </div>
                                <?php endif; ?>
                                
                                <div class="appointment-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y', strtotime($apt['ngay_hen'])) ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <?= date('H:i', strtotime($apt['gio_hen'])) ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-<?= $apt['loai_tu_van'] === 'truc_tuyen' ? 'video' : 'hospital' ?>"></i>
                                        <?= formatConsultationType($apt['loai_tu_van']) ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-money-bill"></i>
                                        <?= number_format($apt['tong_phi'] ?? 0, 0, ',', '.') ?> VNĐ
                                    </div>
                                </div>
                                <?php if ($apt['ghi_chu']): ?>
                                    <div class="detail-item" style="margin-bottom: 12px;">
                                        <i class="fas fa-sticky-note"></i>
                                        <?= htmlspecialchars($apt['ghi_chu']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="appointment-actions">
                                    <button class="btn-cancel" onclick="confirmCancel(<?= $apt['id'] ?>)">
                                        <i class="fas fa-times"></i> Hủy lịch
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Lịch đã hủy -->
            <div id="tab-cancelled" class="tab-content">
                <?php 
                $cancelledAppointments = array_filter($appointments, function($apt) {
                    return $apt['trang_thai'] === 'da_huy';
                });
                ?>
                
                <?php if (empty($cancelledAppointments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-check" style="color: var(--success);"></i>
                        <p>Bạn chưa hủy lịch hẹn nào</p>
                        <small style="color: var(--gray-500);">Tất cả lịch hẹn đều được giữ nguyên</small>
                    </div>
                <?php else: ?>
                    <div class="appointments-list">
                        <?php foreach ($cancelledAppointments as $apt): ?>
                            <div class="appointment-card cancelled-card">
                                <div class="appointment-header">
                                    <div>
                                        <div class="doctor-name">BS. <?= htmlspecialchars($apt['ten_bac_si'] ?? 'Chưa xác định') ?></div>
                                        <div class="specialty"><?= htmlspecialchars($apt['ten_chuyen_khoa'] ?? '') ?></div>
                                    </div>
                                    <span class="status-badge status-cancelled">
                                        <i class="fas fa-times-circle"></i> Đã hủy
                                    </span>
                                </div>
                                <div class="appointment-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y', strtotime($apt['ngay_hen'])) ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <?= date('H:i', strtotime($apt['gio_hen'])) ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-<?= $apt['loai_tu_van'] === 'truc_tuyen' ? 'video' : 'hospital' ?>"></i>
                                        <?= formatConsultationType($apt['loai_tu_van']) ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-money-bill"></i>
                                        <?= number_format($apt['tong_phi'] ?? 0, 0, ',', '.') ?> VNĐ
                                    </div>
                                </div>
                                <div class="cancelled-info">
                                    <i class="fas fa-info-circle"></i>
                                    Lịch hẹn này đã bị hủy
                                    <?php if ($apt['ngay_cap_nhat']): ?>
                                        vào ngày <?= date('d/m/Y H:i', strtotime($apt['ngay_cap_nhat'])) ?>
                                    <?php endif; ?>
                                </div>
                                <?php if ($apt['ghi_chu']): ?>
                                    <div class="detail-item" style="margin-top: 10px;">
                                        <i class="fas fa-sticky-note"></i>
                                        <?= htmlspecialchars($apt['ghi_chu']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tab: Lịch sử -->
            <div id="tab-history" class="tab-content">
                <?php 
                $historyAppointments = array_filter($appointments, function($apt) {
                    return in_array($apt['trang_thai'], ['hoan_thanh', 'vang_mat']) 
                           || ($apt['ngay_hen'] < date('Y-m-d') && $apt['trang_thai'] !== 'da_huy');
                });
                ?>
                
                <?php if (empty($historyAppointments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>Chưa có lịch sử khám bệnh</p>
                    </div>
                <?php else: ?>
                    <div class="appointments-list">
                        <?php foreach ($historyAppointments as $apt): 
                            $status = formatStatus($apt['trang_thai']);
                        ?>
                            <div class="appointment-card">
                                <div class="appointment-header">
                                    <div>
                                        <div class="doctor-name">BS. <?= htmlspecialchars($apt['ten_bac_si'] ?? 'Chưa xác định') ?></div>
                                        <div class="specialty"><?= htmlspecialchars($apt['ten_chuyen_khoa'] ?? '') ?></div>
                                    </div>
                                    <span class="status-badge <?= $status['class'] ?>"><?= $status['text'] ?></span>
                                </div>
                                <div class="appointment-details">
                                    <div class="detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('d/m/Y', strtotime($apt['ngay_hen'])) ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-clock"></i>
                                        <?= date('H:i', strtotime($apt['gio_hen'])) ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-<?= $apt['loai_tu_van'] === 'truc_tuyen' ? 'video' : 'hospital' ?>"></i>
                                        <?= formatConsultationType($apt['loai_tu_van']) ?>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-money-bill"></i>
                                        <?= number_format($apt['tong_phi'] ?? 0, 0, ',', '.') ?> VNĐ
                                    </div>
                                </div>
                                <?php if ($apt['ghi_chu']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-sticky-note"></i>
                                        <?= htmlspecialchars($apt['ghi_chu']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Nút đặt lịch mới -->
            <div style="text-align: center; margin-top: 20px;">
                <a href="DatLichK.php" class="btn-book">
                    <i class="fas fa-plus"></i> Đặt lịch khám mới
                </a>
            </div>
        </div>
    </div>

    <!-- Modal xác nhận hủy -->
    <div class="modal-overlay" id="cancelModal">
        <div class="modal">
            <h3><i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i> Xác nhận hủy lịch</h3>
            <p>Bạn có chắc chắn muốn hủy lịch hẹn này không?</p>
            <form method="POST" id="cancelForm">
                <input type="hidden" name="cancel_appointment" value="1">
                <input type="hidden" name="appointment_id" id="cancelAppointmentId">
                <div class="modal-actions">
                    <button type="button" class="btn-modal-cancel" onclick="closeModal()">Không</button>
                    <button type="submit" class="btn-modal-confirm">Hủy lịch</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            event.target.classList.add('active');
        }

        function confirmCancel(appointmentId) {
            document.getElementById('cancelAppointmentId').value = appointmentId;
            document.getElementById('cancelModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('cancelModal').classList.remove('active');
        }

        // Close modal when clicking outside
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

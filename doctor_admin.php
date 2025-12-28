<?php
session_start();
require_once 'db_config.php';

// Kiểm tra quyền admin (tạm thời bỏ qua để test)
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
//     header('Location: admin_khamcare.php');
//     exit();
// }

$message = '';

// Xử lý kích hoạt/vô hiệu hóa bác sĩ
if (isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'activate') {
            $stmt = $pdo->prepare("UPDATE nguoi_dung SET trang_thai = 'hoat_dong' WHERE id = ? AND vai_tro = 'bac_si'");
            $stmt->execute([$user_id]);
            
            $stmt2 = $pdo->prepare("UPDATE bac_si SET trang_thai = 'hoat_dong' WHERE nguoi_dung_id = ?");
            $stmt2->execute([$user_id]);
            
            $message = "Đã kích hoạt tài khoản bác sĩ thành công!";
        } elseif ($action === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE nguoi_dung SET trang_thai = 'khong_hoat_dong' WHERE id = ? AND vai_tro = 'bac_si'");
            $stmt->execute([$user_id]);
            
            $stmt2 = $pdo->prepare("UPDATE bac_si SET trang_thai = 'khong_hoat_dong' WHERE nguoi_dung_id = ?");
            $stmt2->execute([$user_id]);
            
            $message = "Đã vô hiệu hóa tài khoản bác sĩ!";
        }
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách bác sĩ
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.phone, u.license_number, u.status as user_status, u.created_at,
               d.id as doctor_id, d.consultation_fee, d.experience_years, d.status as doctor_status,
               s.name as specialty_name
        FROM users u
        LEFT JOIN doctors d ON u.id = d.user_id
        LEFT JOIN specialties s ON d.specialty_id = s.id
        WHERE u.role = 'doctor'
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $doctors = $stmt->fetchAll();
} catch (PDOException $e) {
    $doctors = [];
    $message = "Lỗi lấy danh sách bác sĩ: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Bác sĩ - KhamCare Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-pending { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                    <h2><i class="fas fa-user-md me-2"></i>Quản lý Bác sĩ</h2>
                    <div>
                        <a href="doctor_auth.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-sign-in-alt me-1"></i>Trang đăng nhập bác sĩ
                        </a>
                        <a href="admin_khamcare.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Về Admin
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Danh sách Bác sĩ (<?php echo count($doctors); ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($doctors)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-md fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Chưa có bác sĩ nào đăng ký</p>
                                <a href="doctor_auth.php?mode=register" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Đăng ký bác sĩ mới
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Họ tên</th>
                                            <th>Email</th>
                                            <th>Chuyên khoa</th>
                                            <th>Số chứng chỉ</th>
                                            <th>Phí khám</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày đăng ký</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <tr>
                                                <td><?php echo $doctor['id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($doctor['full_name']); ?></strong>
                                                    <?php if ($doctor['phone']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($doctor['phone']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                                <td>
                                                    <?php if ($doctor['specialty_name']): ?>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($doctor['specialty_name']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Chưa có</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($doctor['license_number']): ?>
                                                        <code><?php echo htmlspecialchars($doctor['license_number']); ?></code>
                                                    <?php else: ?>
                                                        <span class="text-muted">Chưa có</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($doctor['consultation_fee']): ?>
                                                        <?php echo number_format($doctor['consultation_fee']); ?>đ
                                                    <?php else: ?>
                                                        <span class="text-muted">Chưa có</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $doctor['user_status']; ?>">
                                                        <?php 
                                                        switch($doctor['user_status']) {
                                                            case 'active': echo 'Hoạt động'; break;
                                                            case 'inactive': echo 'Chưa kích hoạt'; break;
                                                            default: echo ucfirst($doctor['user_status']); break;
                                                        }
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo date('d/m/Y H:i', strtotime($doctor['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $doctor['id']; ?>">
                                                        <?php if ($doctor['user_status'] === 'active'): ?>
                                                            <button type="submit" name="action" value="deactivate" 
                                                                    class="btn btn-sm btn-outline-warning"
                                                                    onclick="return confirm('Vô hiệu hóa tài khoản này?')">
                                                                <i class="fas fa-pause"></i> Vô hiệu hóa
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="submit" name="action" value="activate" 
                                                                    class="btn btn-sm btn-outline-success"
                                                                    onclick="return confirm('Kích hoạt tài khoản này?')">
                                                                <i class="fas fa-check"></i> Kích hoạt
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Thống kê</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $stats = [
                                    'total' => count($doctors),
                                    'active' => count(array_filter($doctors, fn($d) => $d['user_status'] === 'active')),
                                    'inactive' => count(array_filter($doctors, fn($d) => $d['user_status'] === 'inactive'))
                                ];
                                ?>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 class="text-primary"><?php echo $stats['total']; ?></h4>
                                        <small>Tổng số</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-success"><?php echo $stats['active']; ?></h4>
                                        <small>Hoạt động</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="text-warning"><?php echo $stats['inactive']; ?></h4>
                                        <small>Chờ kích hoạt</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Hướng dẫn</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li><i class="fas fa-check text-success me-2"></i>Bác sĩ đăng ký tại doctor_auth.php</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Tự động kích hoạt sau khi đăng ký</li>
                                    <li><i class="fas fa-check text-success me-2"></i>Có thể đăng nhập ngay không cần phê duyệt</li>
                                    <li><i class="fas fa-cog text-primary me-2"></i>Admin có thể quản lý tại đây</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
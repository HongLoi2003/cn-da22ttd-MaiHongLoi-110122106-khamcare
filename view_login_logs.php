<?php
/**
 * Trang xem Login Logs
 * Hiển thị toàn bộ hoạt động đăng nhập trong hệ thống
 */

session_start();
require_once 'db_config.php';

// Kiểm tra quyền admin (chỉ admin mới xem được logs)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: TaiKhoan.php');
    exit;
}

$pdo = getDBConnection();

// Lấy tham số lọc
$filter_status = $_GET['status'] ?? 'all';
$filter_role = $_GET['role'] ?? 'all';
$limit = $_GET['limit'] ?? 50;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];

if ($filter_status !== 'all') {
    $where[] = "login_status = ?";
    $params[] = $filter_status;
}

if ($filter_role !== 'all') {
    $where[] = "role = ?";
    $params[] = $filter_role;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Đếm tổng số records
$countSql = "SELECT COUNT(*) FROM login_logs $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Lấy dữ liệu
$sql = "SELECT * FROM login_logs $whereClause ORDER BY login_time DESC LIMIT ? OFFSET ?";
$params[] = (int)$limit;
$params[] = (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Logs - KhamCare</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters select, .filters input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .filters button {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .filters button:hover {
            background: #764ba2;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 14px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-success {
            color: #10b981;
            font-weight: bold;
        }

        .status-failed {
            color: #ef4444;
            font-weight: bold;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .role-admin {
            background: #fbbf24;
            color: #78350f;
        }

        .role-doctor {
            background: #60a5fa;
            color: #1e3a8a;
        }

        .role-patient {
            background: #34d399;
            color: #065f46;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 12px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .pagination a:hover {
            background: #764ba2;
        }

        .pagination .active {
            background: #764ba2;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_khamcare.php" class="back-link">← Quay lại Admin</a>
        
        <h1>📊 Login Logs - Lịch Sử Đăng Nhập</h1>

        <!-- Statistics -->
        <div class="stats">
            <?php
            $statsStmt = $pdo->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN login_status = 'success' THEN 1 ELSE 0 END) as success_count,
                SUM(CASE WHEN login_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                COUNT(DISTINCT user_id) as unique_users
                FROM login_logs");
            $stats = $statsStmt->fetch();
            ?>
            <div class="stat-card">
                <h3>Tổng Đăng Nhập</h3>
                <div class="number"><?= number_format($stats['total']) ?></div>
            </div>
            <div class="stat-card">
                <h3>Thành Công</h3>
                <div class="number"><?= number_format($stats['success_count']) ?></div>
            </div>
            <div class="stat-card">
                <h3>Thất Bại</h3>
                <div class="number"><?= number_format($stats['failed_count']) ?></div>
            </div>
            <div class="stat-card">
                <h3>Người Dùng</h3>
                <div class="number"><?= number_format($stats['unique_users']) ?></div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <select name="status">
                <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                <option value="success" <?= $filter_status === 'success' ? 'selected' : '' ?>>Thành công</option>
                <option value="failed" <?= $filter_status === 'failed' ? 'selected' : '' ?>>Thất bại</option>
            </select>

            <select name="role">
                <option value="all" <?= $filter_role === 'all' ? 'selected' : '' ?>>Tất cả vai trò</option>
                <option value="admin" <?= $filter_role === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="doctor" <?= $filter_role === 'doctor' ? 'selected' : '' ?>>Bác sĩ</option>
                <option value="patient" <?= $filter_role === 'patient' ? 'selected' : '' ?>>Bệnh nhân</option>
            </select>

            <select name="limit">
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 records</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 records</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 records</option>
            </select>

            <button type="submit">Lọc</button>
        </form>

        <!-- Table -->
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Thời Gian</th>
                        <th>Tên Đăng Nhập</th>
                        <th>Họ Tên</th>
                        <th>Vai Trò</th>
                        <th>Trạng Thái</th>
                        <th>IP Address</th>
                        <th>Lý Do Thất Bại</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['login_time'])) ?></td>
                        <td><?= htmlspecialchars($log['username']) ?></td>
                        <td><?= htmlspecialchars($log['full_name'] ?? '-') ?></td>
                        <td>
                            <?php if ($log['role']): ?>
                                <span class="role-badge role-<?= $log['role'] ?>">
                                    <?= strtoupper($log['role']) ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="status-<?= $log['login_status'] ?>">
                            <?= $log['login_status'] === 'success' ? '✓ Thành công' : '✗ Thất bại' ?>
                        </td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td><?= htmlspecialchars($log['failure_reason'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&status=<?= $filter_status ?>&role=<?= $filter_role ?>&limit=<?= $limit ?>">← Trước</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a href="?page=<?= $i ?>&status=<?= $filter_status ?>&role=<?= $filter_role ?>&limit=<?= $limit ?>" 
                   class="<?= $i === (int)$page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>&status=<?= $filter_status ?>&role=<?= $filter_role ?>&limit=<?= $limit ?>">Sau →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 20px; color: #666;">
            Hiển thị <?= count($logs) ?> / <?= number_format($totalRecords) ?> records
        </p>
    </div>
</body>
</html>

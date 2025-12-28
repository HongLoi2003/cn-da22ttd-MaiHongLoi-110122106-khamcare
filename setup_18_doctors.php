<?php
/**
 * Đồng bộ 18 bác sĩ với ID 1001-1018
 * Email: ten@khamcare.com
 * Mật khẩu: 123456789
 */

require_once 'db_config.php';

echo "<h2>Đồng bộ 18 Bác Sĩ</h2>";

$pdo = getDBConnection();
if (!$pdo) {
    die("❌ Lỗi kết nối database");
}

// Kiểm tra cấu trúc bảng bac_si
echo "<h3>Kiểm tra cấu trúc bảng bac_si:</h3>";
$stmt = $pdo->query("DESCRIBE bac_si");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Các cột: " . implode(', ', $columns) . "<br><br>";

// Hash mật khẩu 123456789
$password = password_hash('123456789', PASSWORD_DEFAULT);
echo "✅ Mật khẩu hash: " . substr($password, 0, 30) . "...<br><br>";

// Danh sách 18 bác sĩ với đầy đủ thông tin
$specialtyNames = [1 => 'Tim Mạch', 2 => 'Thần Kinh', 3 => 'Nhi Khoa', 4 => 'Da Liễu', 5 => 'Sản Phụ Khoa', 6 => 'Nội Tổng Quát'];

$doctors = [
    ['id' => 1001, 'name' => 'BS. Nguyễn Thị Lan', 'username' => 'nguyenthilan', 'specialty' => 1, 'fee' => 350000, 'exp' => 8, 'img' => 'nguyenthilan.png'],
    ['id' => 1002, 'name' => 'BS. Trần Văn Hùng', 'username' => 'tranvanhung', 'specialty' => 2, 'fee' => 400000, 'exp' => 12, 'img' => 'tranvanhung.png'],
    ['id' => 1003, 'name' => 'BS. Lê Thị Mai', 'username' => 'lethimai', 'specialty' => 3, 'fee' => 320000, 'exp' => 6, 'img' => 'lethimai.png'],
    ['id' => 1004, 'name' => 'BS. Lê Minh Tuấn', 'username' => 'leminhtuan', 'specialty' => 4, 'fee' => 380000, 'exp' => 10, 'img' => 'leminhtuan.png'],
    ['id' => 1005, 'name' => 'BS. Trần Thị Hương', 'username' => 'tranthihuong', 'specialty' => 5, 'fee' => 450000, 'exp' => 15, 'img' => 'tranthihuong.png'],
    ['id' => 1006, 'name' => 'BS. Nguyễn Văn Đức', 'username' => 'nguyenvanduc', 'specialty' => 1, 'fee' => 330000, 'exp' => 7, 'img' => 'nguyenvanduc.png'],
    ['id' => 1007, 'name' => 'BS. Đỗ Văn Hùng', 'username' => 'dovanhung', 'specialty' => 2, 'fee' => 390000, 'exp' => 9, 'img' => 'dovanhung.png'],
    ['id' => 1008, 'name' => 'BS. Hoàng Minh Hoàng', 'username' => 'hoangminhhoang', 'specialty' => 3, 'fee' => 310000, 'exp' => 5, 'img' => 'hoangminhhoang.png'],
    ['id' => 1009, 'name' => 'BS. Phạm Thị Linh', 'username' => 'phamthilinh', 'specialty' => 4, 'fee' => 370000, 'exp' => 8, 'img' => 'phamthilinh.png'],
    ['id' => 1010, 'name' => 'BS. Vũ Thị Mai', 'username' => 'vuthimai', 'specialty' => 1, 'fee' => 420000, 'exp' => 11, 'img' => 'vuthimai.png'],
    ['id' => 1011, 'name' => 'BS. Nguyễn Đức Minh', 'username' => 'nguyenducminh', 'specialty' => 2, 'fee' => 380000, 'exp' => 9, 'img' => 'nguyenducminh.png'],
    ['id' => 1012, 'name' => 'BS. Trần Văn Nam', 'username' => 'tranvannam', 'specialty' => 4, 'fee' => 450000, 'exp' => 12, 'img' => 'tranvannam.png'],
    ['id' => 1013, 'name' => 'BS. Phạm Thị Lan', 'username' => 'phamthilan', 'specialty' => 3, 'fee' => 340000, 'exp' => 7, 'img' => 'phamthilan.png'],
    ['id' => 1014, 'name' => 'BS. Lê Thị Hoa', 'username' => 'lethihoa', 'specialty' => 5, 'fee' => 320000, 'exp' => 6, 'img' => 'lethihoa.png.png'],
    ['id' => 1015, 'name' => 'BS. Nguyễn Thị Hạnh', 'username' => 'nguyenthihanh', 'specialty' => 5, 'fee' => 360000, 'exp' => 8, 'img' => 'nguyenthihanh.png'],
    ['id' => 1016, 'name' => 'BS. Nguyễn Văn Hòa', 'username' => 'nguyenvanhoa', 'specialty' => 6, 'fee' => 300000, 'exp' => 10, 'img' => 'nguyenvanhoa.png'],
    ['id' => 1017, 'name' => 'BS. Trần Thị Thu', 'username' => 'tranthithu', 'specialty' => 6, 'fee' => 380000, 'exp' => 9, 'img' => 'tranthithu.png'],
    ['id' => 1018, 'name' => 'BS. Lê Minh Tâm', 'username' => 'leminhtam', 'specialty' => 6, 'fee' => 370000, 'exp' => 8, 'img' => 'leminhtam.png'],
];

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Xóa dữ liệu cũ
    echo "<h3>Bước 1: Xóa dữ liệu cũ</h3>";
    $pdo->exec("DELETE FROM bac_si WHERE id >= 1001 AND id <= 1018");
    $pdo->exec("DELETE FROM nguoi_dung WHERE id >= 1001 AND id <= 1018");
    echo "✅ Đã xóa dữ liệu cũ<br><br>";
    
    // Tạo tài khoản và bác sĩ mới
    echo "<h3>Bước 2: Tạo 18 bác sĩ mới</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Tên</th><th>Chuyên khoa</th></tr>";
    
    foreach ($doctors as $doc) {
        $email = $doc['username'] . '@khamcare.com';
        $phone = '090100' . str_pad($doc['id'] - 1000, 4, '0', STR_PAD_LEFT);
        
        // Tạo tài khoản trong nguoi_dung
        $stmt = $pdo->prepare("INSERT INTO nguoi_dung (id, ten_dang_nhap, mat_khau, email, ho_ten, so_dien_thoai, vai_tro, trang_thai, ngay_tao) VALUES (?, ?, ?, ?, ?, ?, 'bac_si', 'hoat_dong', NOW())");
        $stmt->execute([$doc['id'], $doc['username'], $password, $email, $doc['name'], $phone]);
        
        // Tạo bác sĩ trong bac_si với đầy đủ thông tin
        $hocVan = 'Bác sĩ chuyên khoa ' . $specialtyNames[$doc['specialty']];
        $gioiThieu = 'Bác sĩ ' . $doc['name'] . ' với ' . $doc['exp'] . ' năm kinh nghiệm trong lĩnh vực ' . $specialtyNames[$doc['specialty']];
        $benhVien = 'Bệnh viện KhamCare';
        $hinhAnh = 'image/' . $doc['img'];
        
        $stmt = $pdo->prepare("INSERT INTO bac_si (id, nguoi_dung_id, ho_ten, chuyen_khoa_id, phi_kham, nam_kinh_nghiem, hoc_van, gioi_thieu, benh_vien, hinh_anh, trang_thai, ngay_tao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'hoat_dong', NOW())");
        $stmt->execute([$doc['id'], $doc['id'], $doc['name'], $doc['specialty'], $doc['fee'], $doc['exp'], $hocVan, $gioiThieu, $benhVien, $hinhAnh]);
        
        echo "<tr><td>{$doc['id']}</td><td>{$doc['username']}</td><td>{$email}</td><td>{$doc['name']}</td><td>{$doc['specialty']}</td></tr>";
    }
    
    echo "</table><br>";
    
    // Cập nhật lịch hẹn cũ
    echo "<h3>Bước 3: Cập nhật lịch hẹn</h3>";
    
    // Mapping từ ID cũ sang ID mới
    $idMapping = [
        2 => 1002,  // BS. Trần Văn Hùng
        3 => 1005,  // BS. Trần Thị Hương
        4 => 1004,  // BS. Lê Minh Tuấn
        5 => 1003,  // BS. Lê Thị Mai
        6 => 1016,  // BS. Nguyễn Văn Hòa
        7 => 1006,  // BS. Nguyễn Văn Đức
        8 => 1010,  // BS. Vũ Thị Mai
        9 => 1007,  // BS. Đỗ Văn Hùng
        10 => 1011, // BS. Nguyễn Đức Minh
        11 => 1014, // BS. Lê Thị Hoa
        12 => 1015, // BS. Nguyễn Thị Hạnh
        13 => 1009, // BS. Phạm Thị Linh
        14 => 1012, // BS. Trần Văn Nam
    ];
    
    foreach ($idMapping as $oldId => $newId) {
        $stmt = $pdo->prepare("UPDATE lich_hen SET bac_si_id = ? WHERE bac_si_id = ?");
        $stmt->execute([$newId, $oldId]);
        $count = $stmt->rowCount();
        if ($count > 0) {
            echo "✅ Cập nhật $count lịch hẹn từ bac_si_id $oldId → $newId<br>";
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<br><h3>✅ Hoàn tất!</h3>";
    echo "<p><strong>Đăng nhập bác sĩ:</strong></p>";
    echo "<ul>";
    echo "<li>Username: <code>nguyenthilan</code> (hoặc bất kỳ username nào ở trên)</li>";
    echo "<li>Mật khẩu: <code>123456789</code></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "❌ Lỗi: " . $e->getMessage();
}
?>

<?php
/**
 * Script cập nhật ảnh đại diện cho bác sĩ
 */
require_once 'db_config.php';

$pdo = getDBConnection();
if (!$pdo) {
    die('Không thể kết nối database');
}

// Mapping tên bác sĩ với file ảnh
$doctorImages = [
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

echo "<h2>Cập nhật ảnh đại diện bác sĩ</h2>";
echo "<pre>";

$updated = 0;
$errors = 0;

foreach ($doctorImages as $name => $imagePath) {
    // Kiểm tra file tồn tại
    if (!file_exists($imagePath)) {
        echo "❌ File không tồn tại: $imagePath\n";
        $errors++;
        continue;
    }
    
    try {
        // Tìm bác sĩ theo tên
        $stmt = $pdo->prepare("SELECT id, ho_ten FROM nguoi_dung WHERE ho_ten LIKE ? AND vai_tro = 'bac_si'");
        $stmt->execute(['%' . $name . '%']);
        $user = $stmt->fetch();
        
        if ($user) {
            // Cập nhật ảnh
            $updateStmt = $pdo->prepare("UPDATE nguoi_dung SET anh_dai_dien = ? WHERE id = ?");
            $updateStmt->execute([$imagePath, $user['id']]);
            echo "✓ Đã cập nhật ảnh cho: {$user['ho_ten']} -> $imagePath\n";
            $updated++;
        } else {
            echo "⚠ Không tìm thấy bác sĩ: $name\n";
        }
    } catch (Exception $e) {
        echo "❌ Lỗi cập nhật $name: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n";
echo "=================================\n";
echo "Tổng kết:\n";
echo "- Đã cập nhật: $updated bác sĩ\n";
echo "- Lỗi: $errors\n";
echo "</pre>";

// Hiển thị danh sách bác sĩ hiện tại
echo "<h3>Danh sách bác sĩ hiện tại:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Họ tên</th><th>Ảnh đại diện</th><th>Preview</th></tr>";

$stmt = $pdo->query("SELECT id, ho_ten, anh_dai_dien FROM nguoi_dung WHERE vai_tro = 'bac_si' ORDER BY id");
while ($row = $stmt->fetch()) {
    $avatar = $row['anh_dai_dien'] ?: 'Chưa có';
    $preview = $row['anh_dai_dien'] && file_exists($row['anh_dai_dien']) 
        ? "<img src='{$row['anh_dai_dien']}' width='50' height='50' style='object-fit: cover; border-radius: 8px;'>" 
        : '❌';
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['ho_ten']}</td>";
    echo "<td>{$avatar}</td>";
    echo "<td>{$preview}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='doctor_dashboard.php'>← Quay lại Dashboard</a></p>";
?>

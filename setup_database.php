<?php
/**
 * Tự động tạo database và các bảng
 * Truy cập: http://localhost/setup_database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'khamcare_database';

echo "<h2>🔧 Cài đặt Database KhamCare</h2>";

try {
    // Kết nối MySQL
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Kết nối MySQL thành công</p>";

    // Tạo database
    $pdo->exec("DROP DATABASE IF EXISTS `$dbname`");
    $pdo->exec("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    echo "<p>✅ Tạo database '$dbname' thành công</p>";

    // Tạo bảng nguoi_dung
    $pdo->exec("
        CREATE TABLE `nguoi_dung` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ten_dang_nhap` varchar(50) NOT NULL,
            `email` varchar(100) NOT NULL,
            `mat_khau` varchar(255) NOT NULL,
            `ho_ten` varchar(100) NOT NULL,
            `so_dien_thoai` varchar(15) DEFAULT NULL,
            `vai_tro` enum('benh_nhan','bac_si','quan_tri') DEFAULT 'benh_nhan',
            `trang_thai` enum('hoat_dong','khong_hoat_dong','cho_duyet') DEFAULT 'hoat_dong',
            `anh_dai_dien` varchar(255) DEFAULT NULL,
            `ngay_sinh` date DEFAULT NULL,
            `gioi_tinh` enum('Nam','Nữ','Khác') DEFAULT NULL,
            `dia_chi` text DEFAULT NULL,
            `lien_he_khan_cap` varchar(255) DEFAULT NULL,
            `tien_su_benh` text DEFAULT NULL,
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ Tạo bảng 'nguoi_dung'</p>";

    // Tạo bảng chuyen_khoa
    $pdo->exec("
        CREATE TABLE `chuyen_khoa` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ten` varchar(100) NOT NULL,
            `mo_ta` text DEFAULT NULL,
            `icon` varchar(50) DEFAULT 'fas fa-stethoscope',
            `mau_sac` varchar(20) DEFAULT '#2ecc71',
            `thu_tu` int(11) DEFAULT 0,
            `trang_thai` enum('hoat_dong','khong_hoat_dong') DEFAULT 'hoat_dong',
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ Tạo bảng 'chuyen_khoa'</p>";

    // Tạo bảng bac_si
    $pdo->exec("
        CREATE TABLE `bac_si` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nguoi_dung_id` int(11) NOT NULL,
            `ho_ten` varchar(100) DEFAULT NULL,
            `chuyen_khoa_id` int(11) NOT NULL,
            `phi_kham` decimal(10,2) DEFAULT 300000.00,
            `nam_kinh_nghiem` int(11) DEFAULT 0,
            `danh_gia` decimal(3,2) DEFAULT 0.00,
            `tong_danh_gia` int(11) DEFAULT 0,
            `hoc_van` text DEFAULT NULL,
            `gioi_thieu` text DEFAULT NULL,
            `benh_vien` varchar(255) DEFAULT NULL,
            `hinh_anh` varchar(255) DEFAULT NULL,
            `trang_thai` enum('hoat_dong','khong_hoat_dong','ban') DEFAULT 'hoat_dong',
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ Tạo bảng 'bac_si'</p>";

    // Tạo bảng lich_hen
    $pdo->exec("
        CREATE TABLE `lich_hen` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `benh_nhan_id` int(11) NOT NULL,
            `ten_benh_nhan` varchar(100) DEFAULT NULL,
            `bac_si_id` int(11) NOT NULL,
            `ngay_hen` date NOT NULL,
            `gio_hen` time NOT NULL,
            `thoi_luong` int(11) DEFAULT 30,
            `trang_thai` enum('cho_xac_nhan','da_xac_nhan','hoan_thanh','da_huy','vang_mat') DEFAULT 'cho_xac_nhan',
            `loai_tu_van` enum('truc_tuyen','truc_tiep') DEFAULT 'truc_tiep',
            `loai_lich_hen` enum('dinh_ky','tai_kham','khan_cap','tu_van') DEFAULT 'dinh_ky',
            `ly_do_kham` text DEFAULT NULL,
            `ghi_chu` text DEFAULT NULL,
            `tong_phi` decimal(10,2) DEFAULT 0.00,
            `trang_thai_thanh_toan` enum('chua_thanh_toan','da_thanh_toan','hoan_tien') DEFAULT 'chua_thanh_toan',
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ngay_cap_nhat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ Tạo bảng 'lich_hen'</p>";

    // Tạo bảng ho_so_nguoi_dung
    $pdo->exec("
        CREATE TABLE `ho_so_nguoi_dung` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nguoi_dung_id` int(11) NOT NULL,
            `ho_va_ten` varchar(100) DEFAULT NULL,
            `so_dien_thoai` varchar(15) DEFAULT NULL,
            `ngay_sinh` date DEFAULT NULL,
            `gioi_tinh` enum('Nam','Nữ','Khác') DEFAULT NULL,
            `dia_chi` text DEFAULT NULL,
            `lien_he_khan_cap` varchar(255) DEFAULT NULL,
            `tien_su_benh` text DEFAULT NULL,
            `nhom_mau` varchar(10) DEFAULT NULL,
            `di_ung` text DEFAULT NULL,
            `ghi_chu` text DEFAULT NULL,
            `ngay_tao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ngay_cap_nhat` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `nguoi_dung_id` (`nguoi_dung_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ Tạo bảng 'ho_so_nguoi_dung'</p>";

    // Tạo bảng nhat_ky_dang_nhap
    $pdo->exec("
        CREATE TABLE `nhat_ky_dang_nhap` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nguoi_dung_id` int(11) DEFAULT NULL,
            `ten_dang_nhap` varchar(50) DEFAULT NULL,
            `trang_thai` enum('thanh_cong','that_bai') DEFAULT 'thanh_cong',
            `phuong_thuc` enum('mat_khau','facebook','google','otp') DEFAULT 'mat_khau',
            `dia_chi_ip` varchar(45) DEFAULT NULL,
            `trinh_duyet` text DEFAULT NULL,
            `ly_do_that_bai` varchar(255) DEFAULT NULL,
            `thoi_gian` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p>✅ Tạo bảng 'nhat_ky_dang_nhap'</p>";

    // Thêm dữ liệu mẫu - Admin
    $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT INTO `nguoi_dung` (`ten_dang_nhap`, `email`, `mat_khau`, `ho_ten`, `vai_tro`, `trang_thai`) VALUES
        ('admin', 'admin@khamcare.com', '$admin_pass', 'Quản trị viên', 'quan_tri', 'hoat_dong')
    ");
    echo "<p>✅ Tạo tài khoản Admin</p>";

    // Thêm chuyên khoa
    $pdo->exec("
        INSERT INTO `chuyen_khoa` (`ten`, `mo_ta`, `icon`, `mau_sac`, `thu_tu`, `trang_thai`) VALUES
        ('Tim Mạch', 'Chuyên khoa tim mạch và huyết áp', 'fas fa-heartbeat', '#e74c3c', 1, 'hoat_dong'),
        ('Thần Kinh', 'Chuyên khoa thần kinh và não bộ', 'fas fa-brain', '#9b59b6', 2, 'hoat_dong'),
        ('Sản Phụ Khoa', 'Chuyên khoa sản phụ khoa', 'fas fa-venus', '#e91e63', 3, 'hoat_dong'),
        ('Da Liễu', 'Chuyên khoa da liễu và thẩm mỹ', 'fas fa-hand-holding-heart', '#f39c12', 4, 'hoat_dong'),
        ('Nhi Khoa', 'Chuyên khoa nhi', 'fas fa-child', '#3498db', 5, 'hoat_dong'),
        ('Nội Tổng Quát', 'Chuyên khoa nội tổng quát', 'fas fa-stethoscope', '#2ecc71', 6, 'hoat_dong')
    ");
    echo "<p>✅ Thêm 6 chuyên khoa</p>";

    // Thêm bác sĩ mẫu
    $pdo->exec("
        INSERT INTO `bac_si` (`nguoi_dung_id`, `ho_ten`, `chuyen_khoa_id`, `phi_kham`, `nam_kinh_nghiem`, `danh_gia`, `tong_danh_gia`, `hoc_van`, `gioi_thieu`, `trang_thai`) VALUES
        (1, 'BS. Nguyễn Thị Lan', 1, 350000, 8, 4.8, 156, 'Bác sĩ CK I Tim Mạch', 'Chuyên điều trị bệnh tim mạch.', 'hoat_dong'),
        (1, 'BS. Trần Văn Hùng', 2, 400000, 12, 4.9, 203, 'Tiến sĩ Thần Kinh', 'Chuyên điều trị đau đầu, đột quỵ.', 'hoat_dong'),
        (1, 'BS. Trần Thị Hương', 3, 450000, 15, 4.8, 178, 'Bác sĩ CK II Sản Phụ Khoa', 'Chuyên khám thai.', 'hoat_dong'),
        (1, 'BS. Lê Minh Tuấn', 4, 380000, 10, 4.6, 134, 'Bác sĩ CK I Da Liễu', 'Chuyên điều trị mụn, nám.', 'hoat_dong'),
        (1, 'BS. Lê Thị Mai', 5, 320000, 6, 4.7, 89, 'Bác sĩ Nhi Khoa', 'Chuyên khám trẻ em.', 'hoat_dong'),
        (1, 'BS. Nguyễn Văn Hòa', 6, 300000, 10, 4.6, 123, 'Bác sĩ Nội Tổng Quát', 'Chuyên khám nội khoa.', 'hoat_dong')
    ");
    echo "<p>✅ Thêm 6 bác sĩ mẫu</p>";

    echo "<hr>";
    echo "<h3 style='color:green'>🎉 Cài đặt hoàn tất!</h3>";
    echo "<p><strong>Tài khoản Admin:</strong></p>";
    echo "<ul>";
    echo "<li>Tên đăng nhập: <code>admin</code></li>";
    echo "<li>Mật khẩu: <code>admin123</code></li>";
    echo "</ul>";
    echo "<p><a href='index.php'>👉 Quay về trang chủ</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>

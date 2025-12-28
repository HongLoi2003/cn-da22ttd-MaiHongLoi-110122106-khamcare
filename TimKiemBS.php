<?php
/**
 * Tìm kiếm Bác sĩ - Lấy 18 bác sĩ từ view_doctor.php
 * Gợi ý thông minh với dữ liệu chính xác
 */

session_start();
require_once 'db_config.php';
require_once 'doctor_data_helper.php';

// Mapping ảnh cho các bác sĩ theo ID (giống TrangChu.php)
$imageMapById = [
    1001 => 'image/nguyenthilan.png.png',
    1002 => 'image/tranvanhung.png.png', 
    1003 => 'image/lethimai.png.png',
    1004 => 'image/leminhtuan.png.png',
    1005 => 'image/tranthihuong.png.png',
    1006 => 'image/ngnuyenvanduc.png.webp',
    1007 => 'image/dovanhung.png.jpg',
    1008 => 'image/hoangminhhoang.png.png',
    1009 => 'image/phamthilan.png.png',
    1010 => 'image/vuthimai.png.png',
    1011 => 'image/nguyenducminh.png.png',
    1012 => 'image/tranvannam.png.png',
    1013 => 'image/phamthilan.png.png',
    1014 => 'image/lethihoa.png.png',
    1015 => 'image/nguyenthihanh.png.png',
    1016 => 'image/nguyenvanhoa.png.png',
    1017 => 'image/tranthiphu.png.png',
    1018 => 'image/leminhtam.png.png'
];

// Mapping ảnh theo tên bác sĩ (để hỗ trợ khi ID không khớp)
$imageMapByName = [
    'Nguyễn Thị Lan' => 'image/nguyenthilan.png.png',
    'Trần Văn Hùng' => 'image/tranvanhung.png.png',
    'Lê Thị Mai' => 'image/lethimai.png.png',
    'Lê Minh Tuấn' => 'image/leminhtuan.png.png',
    'Trần Thị Hương' => 'image/tranthihuong.png.png',
    'Nguyễn Văn Đức' => 'image/ngnuyenvanduc.png.webp',
    'Đỗ Văn Hùng' => 'image/dovanhung.png.jpg',
    'Hoàng Minh Hoàng' => 'image/hoangminhhoang.png.png',
    'Phạm Thị Lan' => 'image/phamthilan.png.png',
    'Phạm Thị Linh' => 'image/phamthilan.png.png',
    'Vũ Thị Mai' => 'image/vuthimai.png.png',
    'Nguyễn Đức Minh' => 'image/nguyenducminh.png.png',
    'Trần Văn Nam' => 'image/tranvannam.png.png',
    'Lê Thị Hoa' => 'image/lethihoa.png.png',
    'Nguyễn Thị Hạnh' => 'image/nguyenthihanh.png.png',
    'Nguyễn Văn Hòa' => 'image/nguyenvanhoa.png.png',
    'Trần Thị Thu' => 'image/tranthiphu.png.png',
    'Lê Minh Tâm' => 'image/leminhtam.png.png'
];

// Hàm lấy ảnh bác sĩ
function getDoctorImage($doctor, $imageMapById, $imageMapByName) {
    // Thử theo ID trước
    if (isset($doctor['id']) && isset($imageMapById[$doctor['id']])) {
        return $imageMapById[$doctor['id']];
    }
    
    // Thử theo tên
    $fullName = $doctor['full_name'] ?? '';
    // Loại bỏ prefix BS.
    $cleanName = str_replace(['BS. ', 'Bs. ', 'BS.', 'Bs.'], '', $fullName);
    $cleanName = trim($cleanName);
    
    if (isset($imageMapByName[$cleanName])) {
        return $imageMapByName[$cleanName];
    }
    
    // Thử tìm theo tên gần đúng
    foreach ($imageMapByName as $name => $image) {
        if (stripos($cleanName, $name) !== false || stripos($name, $cleanName) !== false) {
            return $image;
        }
    }
    
    // Thử theo trường image trong data
    if (!empty($doctor['image'])) {
        return $doctor['image'];
    }
    
    return 'image/default-doctor.svg';
}

// Lấy tham số tìm kiếm từ URL
$search_name = $_GET['name'] ?? '';
$search_specialty = $_GET['specialty'] ?? '';
$search_keyword = $_GET['keyword'] ?? '';

$pdo = getDBConnection();
if (!$pdo) {
    die('Lỗi kết nối database');
}

// Tìm kiếm bác sĩ sử dụng helper
$doctors = [];

// Nếu có tham số tìm kiếm, thực hiện tìm kiếm
if (!empty($search_name) || !empty($search_specialty) || !empty($search_keyword)) {
    try {
        $sql = "SELECT d.*, u.full_name, u.phone, u.email, s.name as specialty_name, s.icon, s.color 
                FROM doctors d 
                JOIN users u ON d.user_id = u.id 
                JOIN specialties s ON d.specialty_id = s.id 
                WHERE d.status = 'active' AND u.status = 'active'";
        
        $params = [];
        
        if (!empty($search_name)) {
            $sql .= " AND u.full_name LIKE ?";
            $params[] = "%$search_name%";
        }
        
        if (!empty($search_specialty)) {
            $sql .= " AND s.name LIKE ?";
            $params[] = "%$search_specialty%";
        }
        
        if (!empty($search_keyword)) {
            // Tách từ khóa thành tên và chuyên khoa nếu có dấu "-"
            if (strpos($search_keyword, ' - ') !== false) {
                $parts = explode(' - ', $search_keyword, 2);
                $name_part = trim($parts[0]);
                $specialty_part = trim($parts[1]);
                $sql .= " AND (u.full_name LIKE ? AND s.name LIKE ?)";
                $params[] = "%$name_part%";
                $params[] = "%$specialty_part%";
            } else {
                $sql .= " AND (u.full_name LIKE ? OR s.name LIKE ?)";
                $params[] = "%$search_keyword%";
                $params[] = "%$search_keyword%";
            }
        }
        
        $sql .= " ORDER BY d.rating DESC, d.total_reviews DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $doctors = $stmt->fetchAll();
        
        // Nếu database trống, sử dụng fallback data và lọc
        if (empty($doctors)) {
            $allDoctors = getTopDoctorsFromViewDoctor();
            
            // Lọc theo các tiêu chí tìm kiếm
            $doctors = array_filter($allDoctors, function($doctor) use ($search_name, $search_specialty, $search_keyword) {
                $match = true;
                
                if (!empty($search_name)) {
                    $match = $match && (stripos($doctor['full_name'], $search_name) !== false);
                }
                
                if (!empty($search_specialty)) {
                    $match = $match && (stripos($doctor['specialty_name'], $search_specialty) !== false);
                }
                
                if (!empty($search_keyword)) {
                    if (strpos($search_keyword, ' - ') !== false) {
                        $parts = explode(' - ', $search_keyword, 2);
                        $name_part = trim($parts[0]);
                        $specialty_part = trim($parts[1]);
                        $match = $match && (stripos($doctor['full_name'], $name_part) !== false) && (stripos($doctor['specialty_name'], $specialty_part) !== false);
                    } else {
                        $match = $match && ((stripos($doctor['full_name'], $search_keyword) !== false) || (stripos($doctor['specialty_name'], $search_keyword) !== false));
                    }
                }
                
                return $match;
            });
        }
        
    } catch (PDOException $e) {
        error_log("Search doctors error: " . $e->getMessage());
        // Nếu có lỗi database, sử dụng fallback data
        $doctors = getTopDoctorsFromViewDoctor();
    }
} else {
    // Nếu không có tham số tìm kiếm, hiển thị gợi ý 18 bác sĩ hàng đầu
    $doctors = getTopDoctorsFromViewDoctor(18);
}

// Lấy danh sách chuyên khoa cho dropdown
$specialties = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM specialties WHERE status = 'active' ORDER BY name");
    $stmt->execute();
    $specialties = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Get specialties error: " . $e->getMessage());
}

// Fallback data cho chuyên khoa nếu database trống
if (empty($specialties)) {
    $specialties = [
        ['id' => 1, 'name' => 'Nội khoa', 'icon' => 'fa-stethoscope', 'color' => '#0ea5e9'],
        ['id' => 2, 'name' => 'Ngoại khoa', 'icon' => 'fa-user-md', 'color' => '#10b981'],
        ['id' => 3, 'name' => 'Sản phụ khoa', 'icon' => 'fa-baby', 'color' => '#f472b6'],
        ['id' => 4, 'name' => 'Nhi khoa', 'icon' => 'fa-child', 'color' => '#fbbf24'],
        ['id' => 5, 'name' => 'Da liễu', 'icon' => 'fa-hand-sparkles', 'color' => '#a78bfa'],
        ['id' => 6, 'name' => 'Tim mạch', 'icon' => 'fa-heartbeat', 'color' => '#ef4444'],
        ['id' => 7, 'name' => 'Thần kinh', 'icon' => 'fa-brain', 'color' => '#6366f1'],
        ['id' => 8, 'name' => 'Tai mũi họng', 'icon' => 'fa-head-side-mask', 'color' => '#14b8a6'],
        ['id' => 9, 'name' => 'Mắt', 'icon' => 'fa-eye', 'color' => '#3b82f6'],
        ['id' => 10, 'name' => 'Răng hàm mặt', 'icon' => 'fa-tooth', 'color' => '#f97316'],
        ['id' => 11, 'name' => 'Xương khớp', 'icon' => 'fa-bone', 'color' => '#84cc16'],
        ['id' => 12, 'name' => 'Tiêu hóa', 'icon' => 'fa-stomach', 'color' => '#22c55e'],
        ['id' => 13, 'name' => 'Hô hấp', 'icon' => 'fa-lungs', 'color' => '#06b6d4'],
        ['id' => 14, 'name' => 'Tiết niệu', 'icon' => 'fa-kidneys', 'color' => '#8b5cf6'],
        ['id' => 15, 'name' => 'Ung bướu', 'icon' => 'fa-ribbon', 'color' => '#ec4899']
    ];
}

// Lấy 18 bác sĩ để làm gợi ý từ helper - LUÔN sử dụng dữ liệu từ helper để đảm bảo đủ 18 bác sĩ
$allDoctors = getTopDoctorsFromViewDoctor(18);
$suggestion_doctors = array_map(function($doctor) {
    return [
        'id' => $doctor['id'],
        'full_name' => $doctor['full_name'],
        'specialty_name' => $doctor['specialty_name']
    ];
}, $allDoctors);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title data-lang="searchPageTitle">Tìm kiếm Bác sĩ - KhamCare</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="shared-styles.css">
  <link rel="stylesheet" href="global-font-override.css">
  <link rel="stylesheet" href="square-icons-override.css">
  <link rel="stylesheet" href="fix-icons-visibility.css">
  <!-- Bỏ doctor-card-modern.css để tránh conflict với CSS inline -->
  <style>
    
    
    

    :root {
      --primary: #0ea5e9;
      --primary-light: #38bdf8;
      --primary-dark: #0284c7;
      --secondary: #f8fafc;
      --accent: #10b981;
      --accent-light: #34d399;
      --success: #22c55e;
      --warning: #f59e0b;
      --danger: #ef4444;
      --dark: #1e293b;
      --light: #f1f5f9;
      --white: #ffffff;
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
      --radius: 12px;
      --radius-lg: 16px;
      --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
      --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1);
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #06b6d4 100%);
      background-attachment: fixed;
      color: var(--gray-800);
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      min-height: 100vh;
      position: relative;
    }

    body::before {
      content: '';
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        radial-gradient(circle at 20% 80%, rgba(14, 116, 144, 0.4) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.4) 0%, transparent 50%),
        radial-gradient(circle at 40% 40%, rgba(34, 211, 238, 0.3) 0%, transparent 50%);
      pointer-events: none;
      z-index: -1;
    }

    /* Header */
    .header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--gray-200);
      padding: 1rem 0;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: var(--shadow);
    }

    .header .container {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    /* Logo Styles */
    .logo {
      display: flex;
      align-items: center;
      gap: 15px;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      color: #06b6d4;
      text-decoration: none;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
    }

    .logo span {
      background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 50%, #10b981 100%);
      background-size: 200% auto;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      animation: logoTextShine 3s linear infinite;
    }

    @keyframes logoTextShine {
      0% {
        background-position: 0% center;
      }
      100% {
        background-position: 200% center;
      }
    }

    .logo:hover {
      transform: scale(1.02);
    }

    .logo:hover span {
      animation-duration: 1.5s;
    }

    .logo img {
      width: 65px;
      height: 65px;
      object-fit: contain;
      border-radius: 18px;
      background: transparent;
      filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      animation: logoFloat 3s ease-in-out infinite, logoPulse 2s ease-in-out infinite;
    }

    .logo:hover img {
      transform: scale(1.08) rotate(2deg);
      filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.25));
      animation-play-state: paused;
    }

    @keyframes logoFloat {
      0%, 100% {
        transform: translateY(0px);
      }
      50% {
        transform: translateY(-8px);
      }
    }

    @keyframes logoPulse {
      0%, 100% {
        filter: drop-shadow(0 4px 12px rgba(6, 182, 212, 0.3));
      }
      50% {
        filter: drop-shadow(0 6px 20px rgba(6, 182, 212, 0.5));
      }
    }

    /* Navigation Menu */
    .nav-menu {
      display: flex;
      align-items: center;
      gap: 2rem;
    }

    .nav-menu a {
      text-decoration: none;
      color: #06b6d4;
      font-weight: 600;
      font-size: 1.1rem;
      padding: 10px 16px;
      border-radius: var(--radius-lg);
      transition: var(--transition);
      position: relative;
      display: flex;
      align-items: center;
      gap: 6px;
      background: transparent;
      white-space: nowrap;
    }

    .nav-menu a::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
      border-radius: var(--radius-lg);
      opacity: 0;
      transform: scale(0.8);
      transition: var(--transition);
      z-index: -1;
    }

    .nav-menu a:hover {
      color: var(--white);
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .nav-menu a:hover::before {
      opacity: 1;
      transform: scale(1);
    }

    .nav-menu a:active {
      transform: translateY(0);
    }

    /* Header Actions */
    .header-actions {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .btn-account {
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 0.6rem 1.2rem;
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      white-space: nowrap;
    }

    .btn-account:hover {
      background: var(--primary-dark);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    /* Account Dropdown Styles */
    .account-dropdown-wrapper {
      position: relative;
    }

    .btn-account-dropdown {
      background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
      color: white;
      border: none;
      border-radius: 12px;
      padding: 12px 20px;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
      white-space: nowrap;
    }

    .btn-account-dropdown:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(14, 165, 233, 0.4);
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .btn-account-dropdown .arrow {
      font-size: 0.8rem;
      transition: transform 0.3s ease;
    }

    .account-dropdown-wrapper.active .btn-account-dropdown .arrow {
      transform: rotate(180deg);
    }

    .account-dropdown-menu {
      position: absolute;
      top: calc(100% + 10px);
      right: 0;
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      min-width: 250px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
      overflow: hidden;
      border: 1px solid rgba(14, 165, 233, 0.1);
    }

    .account-dropdown-wrapper.active .account-dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .account-dropdown-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 20px;
      color: #374151;
      text-decoration: none;
      transition: all 0.3s ease;
      border-bottom: 1px solid #f3f4f6;
      cursor: pointer;
    }

    .account-dropdown-item:last-child {
      border-bottom: none;
    }

    .account-dropdown-item:hover {
      background: linear-gradient(90deg, #f0f9ff 0%, #e0f2fe 100%);
      color: #0ea5e9;
    }

    .account-dropdown-item i {
      width: 20px;
      font-size: 1.1rem;
      color: #0ea5e9;
    }

    .account-dropdown-item.logout {
      color: #ef4444;
    }

    .account-dropdown-item.logout:hover {
      background: linear-gradient(90deg, #fef2f2 0%, #fee2e2 100%);
    }

    .account-dropdown-item.logout i {
      color: #ef4444;
    }



    .back-btn {
      background: var(--primary);
      color: var(--white);
      border: none;
      border-radius: var(--radius);
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .back-btn:hover {
      background: var(--primary-dark);
      color: var(--white);
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    /* Search Section */
    .search-section {
      background: var(--white);
      padding: 2rem 0;
      margin-bottom: 2rem;
      box-shadow: var(--shadow);
    }

    .search-form {
      background: var(--gray-50);
      border-radius: var(--radius-lg);
      padding: 2rem;
      border: 2px solid var(--gray-200);
    }

    .search-title {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 2.3rem;
      font-weight: 900;
      background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 1.5rem;
      text-align: center;
      letter-spacing: -0.03em;
      line-height: 1.1;
    }

    .form-control {
      border: 2px solid var(--gray-200);
      border-radius: var(--radius);
      padding: 0.75rem 1rem;
      font-size: 1rem;
      transition: var(--transition);
      background: var(--white);
    }

    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
      outline: none;
    }

    .btn-search {
      background: var(--primary);
      border: none;
      color: var(--white);
      border-radius: var(--radius);
      padding: 0.75rem 2rem;
      font-weight: 600;
      transition: var(--transition);
      width: 100%;
    }

    .btn-search:hover {
      background: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    /* Results Section */
    .results-section {
      padding: 0 0 3rem 0;
    }

    .results-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .results-title {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      letter-spacing: -0.025em;
    }

    .results-count {
      color: var(--gray-600);
      font-weight: 500;
    }

    /* Doctor Cards */
    .doctor-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 2rem;
      margin-bottom: 1.5rem;
      box-shadow: var(--shadow);
      border: 2px solid var(--gray-100);
      transition: var(--transition);
    }

    .doctor-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-lg);
      border-color: var(--primary-light);
    }

    .doctor-header {
      display: flex;
      align-items: flex-start;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .doctor-avatar {
      width: 80px;
      height: 80px;
      border-radius: 12px;
      object-fit: cover;
      border: 3px solid var(--primary-light);
      background: var(--gray-100);
    }

    .doctor-info h3 {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--gray-800);
      margin-bottom: 0.5rem;
    }

    .doctor-specialty {
      display: inline-block;
      background: var(--primary);
      color: var(--white);
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .doctor-stats {
      display: flex;
      gap: 1.5rem;
      margin-bottom: 1rem;
    }

    .stat-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--gray-600);
      font-size: 0.875rem;
    }

    .stat-item i {
      color: var(--primary);
    }

    .doctor-actions {
      display: flex;
      gap: 1rem;
      align-items: center;
    }

    .btn-book {
      background: var(--accent);
      color: var(--white);
      border: none;
      border-radius: var(--radius);
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: var(--transition);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-book:hover {
      background: var(--accent-dark);
      color: var(--white);
      transform: translateY(-2px);
      box-shadow: var(--shadow);
    }

    .btn-view {
      background: transparent;
      color: var(--primary);
      border: 2px solid var(--primary);
      border-radius: var(--radius);
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: var(--transition);
      text-decoration: none;
    }

    .btn-view:hover {
      background: var(--primary);
      color: var(--white);
    }

    /* No Results */
    .no-results {
      text-align: center;
      padding: 4rem 2rem;
      background: var(--white);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
    }

    .no-results i {
      font-size: 4rem;
      color: var(--gray-400);
      margin-bottom: 1rem;
    }

    .no-results h3 {
      font-size: 1.5rem;
      color: var(--gray-600);
      margin-bottom: 0.5rem;
    }

    .no-results p {
      color: var(--gray-500);
    }

    /* Doctor Suggestions */
    .suggestions-section {
      background: var(--white);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .suggestions-header {
      text-align: center;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 2px solid var(--gray-100);
    }

    .suggestions-header i {
      font-size: 3rem;
      color: var(--accent);
      margin-bottom: 1rem;
      display: block;
    }

    .suggestions-header h3 {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
      letter-spacing: -0.025em;
    }

    .suggestions-header p {
      color: var(--gray-600);
      font-size: 1.1rem;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Doctors Grid - 3 columns layout */
    .doctors-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      margin-top: 2rem;
      padding: 0 20px;
    }

    @media (max-width: 1200px) {
      .doctors-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
      }
    }

    @media (max-width: 768px) {
      .doctors-grid {
        grid-template-columns: 1fr;
        gap: 16px;
        padding: 0 10px;
      }
    }

    /* Doctor Card Styles - giống TrangChu.php */
    .doctor-card {
      background: white;
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 28px 24px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(14, 165, 233, 0.1);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .doctor-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(14, 165, 233, 0.08), transparent);
      transition: left 0.6s ease;
    }

    .doctor-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 16px 40px rgba(14, 165, 233, 0.15);
      border-color: rgba(14, 165, 233, 0.3);
    }

    .doctor-card:hover::before {
      left: 100%;
    }

    /* Doctor Avatar - Copy từ TrangChu.php */
    .doctor-avatar {
      width: 120px;
      height: 120px;
      background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      box-shadow: 0 4px 15px rgba(14, 165, 233, 0.15);
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      border: 4px solid white;
    }

    .doctor-avatar img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      transition: all 0.3s ease;
    }

    .doctor-avatar img.error {
      display: none;
    }

    .doctor-avatar .fallback {
      display: none;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #0ea5e9, #06b6d4);
      border-radius: 50%;
      color: white;
      font-size: 2rem;
      font-weight: bold;
      align-items: center;
      justify-content: center;
      position: absolute;
      top: 0;
      left: 0;
    }

    .doctor-avatar img.error + .fallback {
      display: flex;
    }

    .doctor-card:hover .doctor-avatar {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 12px 32px rgba(14, 165, 233, 0.25);
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .doctor-info {
      width: 100%;
    }

    .doctor-info h4 {
      font-size: 1.1rem;
      font-weight: 700;
      color: #1e293b;
      margin: 0 0 10px 0;
      transition: color 0.3s ease;
    }

    .doctor-info h4 a {
      color: inherit;
      text-decoration: none;
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .doctor-info h4 a:hover {
      color: #0ea5e9;
      text-decoration: underline;
    }

    .doctor-card:hover .doctor-info h4 {
      color: #0ea5e9;
    }

    .doctor-info .specialty {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 10px;
    }

    .doctor-info .hospital,
    .doctor-info .experience {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      color: #64748b;
      font-size: 0.85rem;
      margin: 6px 0;
    }

    .doctor-info .hospital i,
    .doctor-info .experience i {
      color: #0ea5e9;
      width: 16px;
    }

    .doctor-info .rating {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin: 10px 0;
    }

    .doctor-info .rating .stars {
      display: flex;
      gap: 2px;
    }

    .doctor-info .rating .stars i {
      color: #fbbf24;
      font-size: 0.9rem;
    }

    .doctor-info .rating .score {
      font-weight: 700;
      color: #1e293b;
      font-size: 0.9rem;
    }

    .doctor-info .rating .reviews {
      color: #64748b;
      font-size: 0.8rem;
    }

    .doctor-info .price {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #f0f9ff;
      color: #0ea5e9;
      padding: 8px 16px;
      border-radius: 10px;
      font-size: 1rem;
      font-weight: 700;
      margin: 10px 0;
    }

    .doctor-info .price i {
      color: #10b981;
    }

    .doctor-info .availability {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #d1fae5;
      color: #059669;
      padding: 6px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin: 8px 0;
    }

    .doctor-actions {
      display: flex;
      gap: 10px;
      width: 100%;
      margin-top: 16px;
    }

    .btn-profile {
      flex: 1;
      padding: 11px 14px;
      background: #f1f5f9;
      color: #475569;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-profile:hover {
      background: #e2e8f0;
      color: #1e293b;
      transform: translateY(-2px);
    }

    .btn-book {
      flex: 1;
      padding: 11px 14px;
      background: linear-gradient(135deg, #0ea5e9, #0284c7);
      color: white;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
    }

    .btn-book:hover {
      background: linear-gradient(135deg, #0284c7, #0369a1);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(14, 165, 233, 0.4);
    }

    /* Suggestions Section Styles */
    .suggestions-section {
      background: var(--white);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg);
      padding: 3rem 2rem;
      margin: 2rem auto;
      max-width: 1400px;
    }

    .suggestions-header {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .suggestions-header i {
      font-size: 3.5rem;
      background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 1rem;
      display: block;
    }

    .suggestions-header h3 {
      font-size: 2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #0c4a6e 0%, #0ea5e9 50%, #10b981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.75rem;
    }

    .suggestions-header p {
      color: var(--gray-600);
      font-size: 1.1rem;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Suggestions */
    .suggestions-section {
      background: var(--white);
      padding: 2rem 0;
      margin-bottom: 2rem;
      border-top: 1px solid var(--gray-200);
    }

    .suggestions-title {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 1.8rem;
      font-weight: 800;
      background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 1.5rem;
      text-align: center;
      letter-spacing: -0.025em;
    }

    .suggestions-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1rem;
    }

    .suggestion-item {
      background: var(--gray-50);
      border: 2px solid var(--gray-200);
      border-radius: var(--radius);
      padding: 1rem;
      text-decoration: none;
      color: var(--gray-700);
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .suggestion-item:hover {
      background: var(--primary);
      color: var(--white);
      border-color: var(--primary);
      transform: translateY(-2px);
      box-shadow: var(--shadow);
    }

    .suggestion-item i {
      color: var(--primary);
      font-size: 1.25rem;
    }

    .suggestion-item:hover i {
      color: var(--white);
    }

    .suggestion-content {
      flex: 1;
    }

    .suggestion-name {
      font-weight: 600;
      margin-bottom: 0.25rem;
    }

    .suggestion-specialty {
      font-size: 0.875rem;
      opacity: 0.8;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .doctor-header {
        flex-direction: column;
        text-align: center;
      }

      .doctor-stats {
        justify-content: center;
        flex-wrap: wrap;
      }

      .doctor-actions {
        flex-direction: column;
      }

      .btn-book, .btn-view {
        width: 100%;
        justify-content: center;
      }

      .suggestions-grid {
        grid-template-columns: 1fr;
      }
    }
    .container-main {
      max-width: 1100px;
      margin: 40px auto;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 16px rgba(0,123,255,0.08);
      display: flex;
      gap: 0;
      min-height: 600px;
      overflow: hidden;
    }
    .left-panel {
      flex: 1.2;
      padding: 36px 32px 32px 32px;
      border-right: 1px solid #e3e3e3;
      background: #fff;
    }
    .right-panel {
      flex: 1;
      padding: 36px 32px 32px 32px;
      background: #f7fbff;
      min-width: 340px;
    }
    .section-title {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 18px;
      color: #007bff;
    }
    .form-group {
      display: flex;
      gap: 18px;
      margin-bottom: 14px;
    }
    .form-group input, .form-group textarea {
      flex: 1;
      padding: 10px 14px;
      border: 1.5px solid #b3d7ff;
      border-radius: 8px;
      font-size: 15px;
      background: #f7fbff;
      transition: border 0.2s;
    }
    .form-group input:focus, .form-group textarea:focus {
      border: 1.5px solid #007bff;
      background: #fff;
      outline: none;
    }
    .form-label {
      font-size: 15px;
      font-weight: 500;
      margin-bottom: 5px;
      color: #444;
      display: block;
    }
    .info-note {
      font-size: 13px;
      color: #888;
      margin-bottom: 18px;
    }
    .confirm-btn {
      background: #28c76f;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 12px 32px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.2s;
      margin-top: 18px;
      box-shadow: 0 2px 8px rgba(40,199,111,0.08);
      display: block;
    }
    .confirm-btn:hover {
      background: #20b263;
    }
    .summary-title {
      font-size: 18px;
      font-weight: 700;
      color: #007bff;
      margin-bottom: 18px;
    }
    .doctor-summary {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      margin-bottom: 18px;
    }
    .doctor-summary img {
      width: 64px;
      height: 64px;
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid #b3d7ff;
      background: #eaf6fb;
    }
    .doctor-info-summary {
      flex: 1;
    }
    .doctor-info-summary .doctor-name {
      font-size: 17px;
      font-weight: 600;
      color: #007bff;
      margin-bottom: 2px;
    }
    .doctor-info-summary .doctor-specialty {
      font-size: 15px;
      color: #28c76f;
      margin-bottom: 2px;
    }
    .doctor-info-summary .doctor-address {
      font-size: 14px;
      color: #666;
      margin-bottom: 2px;
    }
    .doctor-info-summary .doctor-rating {
      font-size: 14px;
      color: #f7b731;
      margin-bottom: 2px;
    }
    .summary-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    .summary-table td {
      font-size: 15px;
      padding: 5px 0;
      color: #444;
    }
    .summary-table .label {
      color: #888;
      width: 120px;
    }
    .summary-table .value {
      font-weight: 500;
    }
    .summary-total {
      font-size: 17px;
      font-weight: bold;
      color: #28c76f;
      text-align: right;
      margin-top: 10px;
    }
    .search-box {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
    }
    .search-box input {
      flex: 1;
      padding: 13px 16px;
      border: 1.5px solid #b3d7ff;
      border-radius: 10px;
      background: #f7fbff;
      font-size: 16px;
      transition: border 0.2s;
    }
    .search-box input:focus {
      border: 1.5px solid #007bff;
      outline: none;
      background: #fff;
    }
    .search-btn {
      background: #007bff;
      border: none;
      color: white;
      padding: 0 22px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 20px;
      font-weight: bold;
      transition: background 0.2s;
      box-shadow: 0 2px 8px rgba(0,123,255,0.08);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .search-btn:hover {
      background: #0059c9;
    }
    .top-bar {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      margin-bottom: 18px;
      gap: 10px;
    }
    .back-btn {
      background: #007bff;
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 10px 24px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.2s;
      box-shadow: 0 2px 8px rgba(0,123,255,0.08);
    }
    .back-btn:hover {
      background: #0059c9;
    }
    .slots {
      margin-top: 8px;
      margin-bottom: 8px;
    }
    .time {
      display: inline-block;
      border: 1.5px solid #007bff;
      border-radius: 7px;
      padding: 6px 16px;
      margin: 7px 7px 0 0;
      font-size: 15px;
      color: #007bff;
      cursor: pointer;
      background: #fff;
      transition: background 0.2s, color 0.2s, border 0.2s;
      font-weight: 500;
    }
    .time.active, .time:active {
      background: #007bff;
      color: #fff;
      border: 1.5px solid #007bff;
    }
    .time[style*="opacity:0.5"] {
      background: #e3e3e3 !important;
      color: #aaa !important;
      border: 1.5px dashed #b3d7ff !important;
      cursor: not-allowed !important;
    }
    @media (max-width: 900px) {
      .container-main { flex-direction: column; }
      .left-panel, .right-panel { border-right: none; min-width: unset; }
    }

    /* Beautiful Footer Styles */
    .beautiful-footer {
      background: 
        radial-gradient(ellipse at top left, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
        radial-gradient(ellipse at bottom right, rgba(6, 182, 212, 0.1) 0%, transparent 50%),
        linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #06b6d4 100%);
      color: #ffffff;
      position: relative;
      overflow: hidden;
      margin-top: 100px;
      box-shadow: 
        0 -25px 50px rgba(14, 165, 233, 0.2),
        0 -15px 30px rgba(0, 0, 0, 0.3);
    }

    .footer-wave {
      position: absolute;
      top: -1px;
      left: 0;
      width: 100%;
      height: 120px;
      z-index: 2;
    }

    .footer-wave svg {
      width: 100%;
      height: 100%;
    }

    .beautiful-footer::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: 
        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="footer-medical" width="60" height="60" patternUnits="userSpaceOnUse"><g fill="rgba(255, 255, 255, 0.05)"><circle cx="15" cy="15" r="1"/><circle cx="45" cy="45" r="0.8"/><rect x="25" y="20" width="10" height="20" rx="2"/><rect x="20" y="25" width="20" height="10" rx="2"/></g></pattern></defs><rect width="60" height="60" fill="url(%23footer-medical)"/></svg>');
      background-size: 120px 120px;
      opacity: 0.3;
      pointer-events: none;
      z-index: 1;
    }

    .footer-content {
      padding: 80px 0 40px;
      position: relative;
      z-index: 2;
    }

    .beautiful-footer h2,
    .beautiful-footer h3,
    .beautiful-footer h4,
    .beautiful-footer p,
    .beautiful-footer a,
    .beautiful-footer span {
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    }

    .beautiful-footer i {
      text-shadow: 0 1px 3px rgba(0, 0, 0, 0.4);
      filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
    }

    .footer-main-grid {
      display: grid;
      grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
      gap: 60px;
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 30px;
    }

    .footer-brand {
      max-width: 450px;
    }

    .brand-header {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 20px;
    }

    .brand-icon {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      color: white;
      box-shadow: 0 8px 20px rgba(14, 165, 233, 0.3);
    }

    .brand-info h2 {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 2rem;
      font-weight: 800;
      margin: 0;
      color: #ffffff;
    }

    .brand-info p {
      font-size: 0.9rem;
      color: #cbd5e1;
      margin: 0;
      font-weight: 500;
      letter-spacing: 1px;
    }

    .brand-desc {
      color: #cbd5e1;
      line-height: 1.7;
      margin-bottom: 30px;
      font-size: 1.05rem;
    }

    .brand-stats {
      display: flex;
      gap: 30px;
      margin-top: 25px;
    }

    .brand-stats .stat {
      text-align: center;
    }

    .brand-stats .number {
      display: block;
      font-size: 1.8rem;
      font-weight: 800;
      color: #0ea5e9;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
    }

    .brand-stats .label {
      font-size: 0.9rem;
      color: #cbd5e1;
      font-weight: 500;
    }

    .footer-column h3 {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 25px;
      color: #ffffff;
    }

    .footer-column h3 i {
      color: #0ea5e9;
      margin-right: 10px;
    }

    .footer-column ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .footer-column li {
      margin-bottom: 15px;
    }

    .footer-column a {
      color: #cbd5e1;
      text-decoration: none;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .footer-column a i {
      width: 18px;
      color: #0ea5e9;
      font-size: 0.9rem;
      font-style: normal;
    }

    .footer-column a:hover {
      color: #ffffff;
      transform: translateX(8px);
    }

    .footer-column a:hover i {
      color: #06b6d4;
      transform: scale(1.2);
    }

    .contact-info {
      margin-bottom: 30px;
    }

    .contact-item {
      display: flex;
      gap: 15px;
      margin-bottom: 20px;
      align-items: flex-start;
    }

    .contact-item i {
      font-size: 1.3rem;
      margin-top: 3px;
      color: #0ea5e9;
    }

    .contact-item strong {
      display: block;
      margin-bottom: 5px;
      color: #ffffff;
    }

    .social-section h4 {
      font-size: 1.1rem;
      margin-bottom: 15px;
      color: #ffffff;
    }

    .social-links {
      display: flex;
      gap: 15px;
    }

    .social {
      width: 45px;
      height: 45px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 1.2rem;
    }

    .social.facebook {
      background: #1877f2;
    }

    .social.youtube {
      background: #ff0000;
    }

    .social.zalo {
      background: #0068ff;
    }

    .social.instagram {
      background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
    }

    .social:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
    }

    .footer-bottom {
      background: rgba(0, 0, 0, 0.3);
      padding: 25px 0;
      position: relative;
      z-index: 2;
    }

    .bottom-content {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .footer-bottom p {
      margin: 0;
      color: #cbd5e1;
      font-size: 0.95rem;
    }

    .bottom-links {
      display: flex;
      gap: 30px;
    }

    .bottom-links a {
      color: #cbd5e1;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 0.95rem;
    }

    .bottom-links a:hover {
      color: #ffffff;
    }

    @media (max-width: 768px) {
      .footer-main-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }

      .brand-stats {
        justify-content: space-around;
      }

      .bottom-content {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }

      .bottom-links {
        flex-direction: column;
        gap: 10px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <div class="header">
    <div class="container">
      <a href="TrangChu.php" class="logo">
        <img src="image/logo.png.png" alt="Logo">
        <span>KhamCare</span>
      </a>
      
      <nav class="nav-menu">
        <a href="TimKiemBS.php" class="nav-link active" id="navSearch">Tìm bác sĩ</a>
        <a href="ChuyenKhoa.php" class="nav-link" id="navSpecialty">Chuyên khoa</a>
        <a href="TuVanTrucTuyen.php" class="nav-link" id="navConsult">Tư vấn</a>
        <a href="Camnangsuckhoe.php" class="nav-link" id="navGuide">Cẩm nang Sức khỏe</a>
        <a href="doctor_auth.php" class="nav-link doctor-link" id="navDoctor">
          <i class="fas fa-user-md"></i> <span id="navDoctorText">Dành cho Bác sĩ</span>
        </a>
      </nav>
      
      <div class="header-actions">
        <div class="account-dropdown-wrapper" id="accountDropdown">
          <button class="btn-account-dropdown" onclick="toggleAccountDropdown(event)">
            <i class="fas fa-user"></i>
            <span id="navAccount">Tài khoản</span>
            <i class="fas fa-chevron-down arrow"></i>
          </button>
          <div class="account-dropdown-menu">
            <a href="tk.php" class="account-dropdown-item">
              <i class="fas fa-user-circle"></i>
              <span>Hồ sơ của tôi</span>
            </a>
            <a href="DatLichK.php" class="account-dropdown-item">
              <i class="fas fa-calendar-check"></i>
              <span>Lịch hẹn</span>
            </a>
            <a href="TuVanTrucTuyen.php" class="account-dropdown-item">
              <i class="fas fa-comments"></i>
              <span>Tư vấn trực tuyến</span>
            </a>
            <a href="logout.php" class="account-dropdown-item logout">
              <i class="fas fa-sign-out-alt"></i>
              <span>Đăng xuất</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Search Section -->
  <div class="search-section">
    <div class="container">
      <form method="GET" action="TimKiemBS.php" class="search-form">
        <h2 class="search-title">
          <i class="fas fa-search me-2"></i>
          <span data-lang="searchDoctorsTitle">Tìm kiếm Bác sĩ</span>
        </h2>
        
        <div class="row g-3">
          <div class="col-md-5">
            <label class="form-label fw-semibold" data-lang="doctorNameLabel">Tên bác sĩ</label>
            <input type="text" class="form-control" name="name" 
                   data-lang-placeholder="doctorNamePlaceholder"
                   placeholder="Nhập tên bác sĩ..." 
                   value="<?php echo htmlspecialchars($search_name); ?>">
          </div>
          
          <div class="col-md-5">
            <label class="form-label fw-semibold" data-lang="specialtyLabel">Chuyên khoa</label>
            <select class="form-control" name="specialty">
              <option value="" data-lang="allSpecialties">Tất cả chuyên khoa</option>
              <?php foreach ($specialties as $specialty): ?>
                <option value="<?php echo htmlspecialchars($specialty['name']); ?>" 
                        <?php echo $search_specialty === $specialty['name'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($specialty['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn-search">
              <i class="fas fa-search me-2"></i>
              <span data-lang="searchBtn">Tìm kiếm</span>
            </button>
          </div>
        </div>
        
        <?php if (!empty($search_keyword)): ?>
          <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($search_keyword); ?>">
        <?php endif; ?>
      </form>
    </div>
  </div>
  <!-- Results Section -->
  <div class="results-section">
    <div class="container">
      <div class="results-header">
        <h2 class="results-title">
          <?php if (!empty($search_name) || !empty($search_specialty) || !empty($search_keyword)): ?>
            <span data-lang="searchResults">Kết quả tìm kiếm</span>
            <?php if (!empty($search_keyword)): ?>
              <small class="text-muted d-block" style="font-size: 1rem; font-weight: 400;">
                <span data-lang="keywordLabel">Từ khóa:</span> "<?php echo htmlspecialchars($search_keyword); ?>"
              </small>
            <?php endif; ?>
          <?php else: ?>
            <span data-lang="topDoctorsSuggestion">Gợi ý 18 Bác sĩ Hàng đầu</span>
          <?php endif; ?>
        </h2>
        <div class="results-count">
          <i class="fas fa-search me-1"></i>
          <span data-lang="foundLabel">Tìm thấy</span> <strong><?php echo count($doctors); ?></strong> <span data-lang="doctorsLabel">bác sĩ</span>
        </div>
      </div>

      <?php if (!empty($search_name) || !empty($search_specialty) || !empty($search_keyword)): ?>
        <div class="alert alert-info mb-4">
          <i class="fas fa-info-circle me-2"></i>
          <strong data-lang="searchLabel">Tìm kiếm:</strong>
          <?php if (!empty($search_name)): ?>
            <span data-lang="nameLabel">Tên:</span> "<?php echo htmlspecialchars($search_name); ?>"
          <?php endif; ?>
          <?php if (!empty($search_specialty)): ?>
            <?php echo !empty($search_name) ? ' | ' : ''; ?><span data-lang="specialtyLabel">Chuyên khoa:</span> "<?php echo htmlspecialchars($search_specialty); ?>"
          <?php endif; ?>
          <?php if (!empty($search_keyword)): ?>
            <?php echo (!empty($search_name) || !empty($search_specialty)) ? ' | ' : ''; ?><span data-lang="keywordLabel">Từ khóa:</span> "<?php echo htmlspecialchars($search_keyword); ?>"
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if (empty($doctors)): ?>
        <!-- Doctor Suggestions when no results -->
        <?php 
        // Lấy 18 bác sĩ gợi ý từ helper (đã require ở đầu file)
        $suggestedDoctors = getTopDoctorsFromViewDoctor(18);
        ?>
        <!-- DEBUG: Số bác sĩ: <?php echo count($suggestedDoctors); ?> -->
        <div class="suggestions-section">
          <div class="suggestions-header">
            <i class="fas fa-lightbulb"></i>
            <h3 data-lang="topDoctorsSuggestion">Gợi ý 18 Bác sĩ Hàng đầu</h3>
            <p data-lang="noResultsMessage">Không tìm thấy kết quả phù hợp? Hãy xem các bác sĩ được đánh giá cao dưới đây</p>
          </div>
          
          <!-- Suggested Doctors Grid -->
          <div class="doctors-grid">
            <?php foreach ($suggestedDoctors as $doctor): ?>
              <?php
              // Sử dụng hàm getDoctorImage để lấy ảnh bác sĩ
              $doctorImage = getDoctorImage($doctor, $imageMapById, $imageMapByName);
              $fullName = $doctor['full_name'] ?? '';
              $nameParts = explode(' ', $fullName);
              $firstLetter = strtoupper(substr(end($nameParts), 0, 1)) ?: 'B';
              
              $colors = [
                  'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                  'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
                  'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
                  'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
                  'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
                  'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
              ];
              $colorIndex = ($doctor['id'] ?? 0) % count($colors);
              $avatarColor = $colors[$colorIndex];
              ?>
              <div class="doctor-card" data-specialty="<?php echo htmlspecialchars($doctor['specialty_name'] ?? ''); ?>">
                <div class="doctor-avatar" onclick="window.open('view_doctor.php?doctor_id=<?php echo $doctor['id']; ?>', '_blank')" style="cursor: pointer;">
                  <img src="<?php echo htmlspecialchars($doctorImage); ?>?v=<?php echo time(); ?>" 
                       alt="<?php echo htmlspecialchars($doctor['full_name']); ?>"
                       onload="console.log('✓ Loaded: <?php echo $doctorImage; ?>');"
                       onerror="console.log('✗ Failed: <?php echo $doctorImage; ?>'); this.classList.add('error'); this.style.display='none'; this.nextElementSibling.style.display='flex';"
                       style="width: 100%; height: 100%; object-fit: cover;">
                  <div class="fallback" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: <?php echo $avatarColor; ?>; color: white; font-size: 2rem; font-weight: bold; align-items: center; justify-content: center;">
                    <?php echo $firstLetter; ?>
                  </div>
                </div>
                <div class="doctor-info">
                  <h4><a href="view_doctor.php?doctor_id=<?php echo $doctor['id']; ?>" style="color: inherit; text-decoration: none;">BS. <?php echo htmlspecialchars(str_replace(['BS. ', 'Bs. ', 'BS.', 'Bs.'], '', $doctor['full_name'])); ?></a></h4>
                  <p class="specialty">
                    <i class="fas fa-stethoscope"></i>
                    <?php echo htmlspecialchars($doctor['specialty_name'] ?? $doctor['specialty'] ?? ''); ?>
                  </p>
                  <p class="hospital">
                    <img src="image/logo.png.png" alt="KhamCare" style="width: 16px; height: 16px; border-radius: 4px; vertical-align: middle;">
                    Bệnh viện KhamCare
                  </p>
                  <p class="experience">
                    <i class="fas fa-user-clock"></i>
                    <?php 
                    $experience = isset($doctor['experience_years']) ? $doctor['experience_years'] : rand(5, 15);
                    echo $experience . ' năm kinh nghiệm';
                    ?>
                  </p>
                  <div class="rating">
                    <span class="stars">
                      <?php 
                      $rating = isset($doctor['rating']) ? $doctor['rating'] : number_format(rand(45, 50) / 10, 1);
                      $fullStars = floor($rating);
                      for ($i = 0; $i < $fullStars; $i++) {
                          echo '<i class="fas fa-star"></i>';
                      }
                      if ($rating - $fullStars >= 0.5) {
                          echo '<i class="fas fa-star-half-alt"></i>';
                      }
                      ?>
                    </span>
                    <span class="score"><?php echo $rating; ?>/5.0</span>
                    <span class="reviews">(<?php echo isset($doctor['total_reviews']) ? $doctor['total_reviews'] : rand(20, 200); ?> đánh giá)</span>
                  </div>
                  <p class="price">
                    <i class="fas fa-money-bill-wave"></i>
                    <?php 
                    if (isset($doctor['consultation_fee'])) {
                        echo number_format($doctor['consultation_fee'], 0, ',', ',') . ' VND';
                    } else {
                        $prices = ['300,000', '350,000', '400,000', '450,000', '500,000'];
                        echo $prices[array_rand($prices)] . ' VND';
                    }
                    ?>
                  </p>
                  <span class="availability">
                    <i class="fas fa-calendar-check"></i>
                    Còn lịch trống hôm nay
                  </span>
                  <div class="doctor-actions">
                    <a href="view_doctor.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn-profile">
                      <i class="fas fa-user-md"></i> <span>Xem hồ sơ</span>
                    </a>
                    <a href="DatLichK.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn-book">
                      <i class="fas fa-calendar-plus"></i> <span>Đặt lịch</span>
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <!-- Doctor Cards - Kết quả tìm kiếm -->
        <div class="doctors-grid">
        <?php foreach ($doctors as $doctor): ?>
          <?php
          // Sử dụng hàm getDoctorImage để lấy ảnh bác sĩ
          $doctorImage = getDoctorImage($doctor, $imageMapById, $imageMapByName);
          
          // Lấy chữ cái đầu của tên (cho fallback)
          $fullName = $doctor['full_name'] ?? '';
          $nameParts = explode(' ', $fullName);
          $firstLetter = strtoupper(substr(end($nameParts), 0, 1)) ?: 'B';
          
          $colors = [
              'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
              'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
              'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
              'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
              'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
              'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
          ];
          $colorIndex = ($doctor['id'] ?? 0) % count($colors);
          $avatarColor = $colors[$colorIndex];
          ?>
          <div class="doctor-card" data-specialty="<?php echo htmlspecialchars($doctor['specialty_name'] ?? ''); ?>">
            <div class="doctor-avatar" onclick="window.open('view_doctor.php?doctor_id=<?php echo $doctor['id']; ?>', '_blank')" style="cursor: pointer;">
              <img src="<?php echo htmlspecialchars($doctorImage); ?>?v=<?php echo time(); ?>" 
                   alt="<?php echo htmlspecialchars($doctor['full_name']); ?>"
                   onload="console.log('✓ Loaded: <?php echo $doctorImage; ?>');"
                   onerror="console.log('✗ Failed: <?php echo $doctorImage; ?>'); this.classList.add('error'); this.style.display='none'; this.nextElementSibling.style.display='flex';"
                   style="width: 100%; height: 100%; object-fit: cover;">
              <div class="fallback" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: <?php echo $avatarColor; ?>; color: white; font-size: 2rem; font-weight: bold; align-items: center; justify-content: center;">
                <?php echo $firstLetter; ?>
              </div>
            </div>
            <div class="doctor-info">
              <h4><a href="view_doctor.php?doctor_id=<?php echo $doctor['id']; ?>" style="color: inherit; text-decoration: none;">BS. <?php echo htmlspecialchars(str_replace(['BS. ', 'Bs. ', 'BS.', 'Bs.'], '', $doctor['full_name'])); ?></a></h4>
              <p class="specialty">
                <i class="fas fa-stethoscope"></i>
                <?php echo htmlspecialchars($doctor['specialty_name'] ?? ''); ?>
              </p>
              <p class="hospital">
                <img src="image/logo.png.png" alt="KhamCare" style="width: 16px; height: 16px; border-radius: 4px; vertical-align: middle;">
                Bệnh viện KhamCare
              </p>
              <p class="experience">
                <i class="fas fa-user-clock"></i>
                <?php 
                $experience = isset($doctor['experience_years']) ? $doctor['experience_years'] : rand(5, 15);
                echo $experience . ' năm kinh nghiệm';
                ?>
              </p>
              <div class="rating">
                <span class="stars">
                  <?php 
                  $rating = isset($doctor['rating']) ? $doctor['rating'] : number_format(rand(45, 50) / 10, 1);
                  $fullStars = floor($rating);
                  for ($i = 0; $i < $fullStars; $i++) {
                      echo '<i class="fas fa-star"></i>';
                  }
                  if ($rating - $fullStars >= 0.5) {
                      echo '<i class="fas fa-star-half-alt"></i>';
                  }
                  ?>
                </span>
                <span class="score"><?php echo $rating; ?>/5.0</span>
                <span class="reviews">(<?php echo isset($doctor['total_reviews']) ? $doctor['total_reviews'] : rand(20, 200); ?> đánh giá)</span>
              </div>
              <p class="price">
                <i class="fas fa-money-bill-wave"></i>
                <?php 
                if (isset($doctor['consultation_fee'])) {
                    echo number_format($doctor['consultation_fee'], 0, ',', ',') . ' VND';
                } else {
                    $prices = ['300,000', '350,000', '400,000', '450,000', '500,000'];
                    echo $prices[array_rand($prices)] . ' VND';
                }
                ?>
              </p>
              <span class="availability">
                <i class="fas fa-calendar-check"></i>
                Còn lịch trống hôm nay
              </span>
              <div class="doctor-actions">
                <a href="view_doctor.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn-profile">
                  <i class="fas fa-user-md"></i> <span>Xem hồ sơ</span>
                </a>
                <a href="DatLichK.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn-book">
                  <i class="fas fa-calendar-plus"></i> <span>Đặt lịch</span>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Suggestions Section -->
  <?php if (empty($search_name) && empty($search_specialty) && empty($search_keyword)): ?>
  <div class="suggestions-section">
    <div class="container">
      <h3 class="suggestions-title">
        <i class="fas fa-lightbulb me-2"></i>
        <span data-lang="featuredDoctorsTitle">Gợi ý Bác sĩ nổi bật</span>
      </h3>
      
      <div class="suggestions-grid">
        <?php foreach ($suggestion_doctors as $suggestion): ?>
          <a href="view_doctor.php?doctor_id=<?php echo $suggestion['id']; ?>" 
             class="suggestion-item">
            <i class="fas fa-user-md"></i>
            <div class="suggestion-content">
              <div class="suggestion-name"><?php echo htmlspecialchars($suggestion['full_name']); ?></div>
              <div class="suggestion-specialty"><?php echo htmlspecialchars($suggestion['specialty_name']); ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/language-system.js"></script>
  <script>
    // Initialize language system
    document.addEventListener('DOMContentLoaded', function() {
      initLanguageSystem();
      initCustomLanguageSwitcher();
    });

    // Custom Language Switcher
    function initCustomLanguageSwitcher() {
      const switcher = document.getElementById('langSwitcher');
      const btn = document.getElementById('langSwitcherBtn');
      const dropdown = document.getElementById('langDropdown');
      const options = document.querySelectorAll('.lang-option');
      const currentLangText = document.getElementById('currentLangText');
      const hiddenSelect = document.getElementById('langSelect');
      
      // Get saved language
      const savedLang = localStorage.getItem('kham_lang') || 'vi';
      updateCurrentLanguage(savedLang);
      
      // Toggle dropdown
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        switcher.classList.toggle('active');
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if (!switcher.contains(e.target)) {
          switcher.classList.remove('active');
        }
      });
      
      // Handle language selection
      options.forEach(option => {
        option.addEventListener('click', function() {
          const selectedLang = this.getAttribute('data-lang');
          
          // Update active state
          options.forEach(opt => opt.classList.remove('active'));
          this.classList.add('active');
          
          // Update current language display
          updateCurrentLanguage(selectedLang);
          
          // Update hidden select and trigger change
          hiddenSelect.value = selectedLang;
          hiddenSelect.dispatchEvent(new Event('change'));
          
          // Close dropdown
          switcher.classList.remove('active');
          
          // Apply language
          applyGlobalLang(selectedLang);
        });
      });
      
      function updateCurrentLanguage(lang) {
        const langMap = {
          'vi': 'VN',
          'km': 'KH'
        };
        currentLangText.textContent = langMap[lang] || 'VN';
        
        // Update active option
        options.forEach(opt => {
          if (opt.getAttribute('data-lang') === lang) {
            opt.classList.add('active');
          } else {
            opt.classList.remove('active');
          }
        });
      }
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
          behavior: 'smooth'
        });
      });
    });

    // Add loading state to buttons
    document.querySelectorAll('.btn-book, .btn-view').forEach(btn => {
      btn.addEventListener('click', function() {
        if (this.classList.contains('btn-book')) {
          const originalText = this.innerHTML;
          this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
          this.disabled = true;
          
          // Re-enable after 2 seconds (for demo)
          setTimeout(() => {
            this.innerHTML = originalText;
            this.disabled = false;
          }, 2000);
        }
      });
    });

    // Auto-focus search input
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.querySelector('input[name="name"]');
      if (searchInput && !searchInput.value) {
        searchInput.focus();
      }
      
      // Auto submit khi chọn chuyên khoa
      const specialtySelect = document.querySelector('select[name="specialty"]');
      if (specialtySelect) {
        specialtySelect.addEventListener('change', function() {
          // Tự động submit form khi chọn chuyên khoa
          this.closest('form').submit();
        });
      }
    });
  </script>

  <!-- Beautiful Medical Footer -->
  <footer class="beautiful-footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg">
            <path d="M0,60 C300,120 900,0 1200,60 L1200,120 L0,120 Z" fill="url(#footerGradient)"/>
            <defs>
                <linearGradient id="footerGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" style="stop-color:#0c4a6e;stop-opacity:1" />
                    <stop offset="30%" style="stop-color:#0369a1;stop-opacity:1" />
                    <stop offset="70%" style="stop-color:#0ea5e9;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#06b6d4;stop-opacity:1" />
                </linearGradient>
            </defs>
        </svg>
    </div>
    
    <div class="footer-content">
        <div class="container">
            <div class="footer-main-grid">
                <!-- Brand Column -->
                <div class="footer-brand">
                    <div class="brand-header">
                        <div class="brand-icon">
                            <img src="image/logo.png.png" alt="KhamCare Logo" style="width: 60px; height: 60px; border-radius: 12px;">
                        </div>
                        <div class="brand-info">
                            <h2>KhamCare</h2>
                            <p>INTERNATIONAL HOSPITAL</p>
                        </div>
                    </div>
                    <p class="brand-desc">
                        Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. 
                        Kết nối bạn với các bác sĩ chuyên khoa uy tín.
                    </p>
                    <div class="brand-stats">
                        <div class="stat">
                            <span class="number">50K+</span>
                            <span class="label">Bệnh nhân</span>
                        </div>
                        <div class="stat">
                            <span class="number">200+</span>
                            <span class="label">Bác sĩ</span>
                        </div>
                        <div class="stat">
                            <span class="number">15+</span>
                            <span class="label">Chuyên khoa</span>
                        </div>
                    </div>
                </div>

                <!-- Patient Links -->
                <div class="footer-column">
                    <h3><i class="fas fa-user-injured"></i> Dành Cho Bệnh Nhân</h3>
                    <ul>
                        <li><a href="TimKiemBS.php"><i class="fas fa-search"></i> Tìm kiếm bác sĩ</a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>
                        <li><a href="DatLichK.php"><i class="fas fa-calendar-plus"></i> Đặt lịch</a></li>
                        <li><a href="#"><i class="fas fa-history"></i> Bảng kiểm soát lịch hẹn</a></li>
                    </ul>
                </div>

                <!-- Doctor Links -->
                <div class="footer-column">
                    <h3><i class="fas fa-user-md"></i> Dành Cho Bác Sĩ</h3>
                    <ul>
                        <li><a href="doctor_dashboard.php"><i class="fas fa-tachometer-alt"></i> Kiểm tra cuộc hẹn</a></li>
                        <li><a href="TuVanTrucTuyen.php"><i class="fas fa-comments"></i> Chat</a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-md"></i> Đăng nhập</a></li>
                        <li><a href="TaiKhoan.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>
                        <li><a href="doctor_dashboard.php"><i class="fas fa-chart-line"></i> Dashboard của bác sĩ</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="footer-column contact-column">
                    <h3><i class="fas fa-phone-alt"></i> Liên Hệ</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Địa chỉ:</strong><br>
                                Sở y tế - Bệnh viện đa khoa quốc tế<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Hotline:</strong><br>
                                0433636050
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email:</strong><br>
                                benhviendakhoaquocte@gmail.com
                            </div>
                        </div>
                    </div>
                    
                    <!-- Social Links -->
                    <div class="social-section">
                        <h4>Kết nối với chúng tôi</h4>
                        <div class="social-links">
                            <a href="#" class="social facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="social youtube"><i class="fab fa-youtube"></i></a>
                            <a href="#" class="social zalo"><i class="fas fa-comment-dots"></i></a>
                            <a href="#" class="social instagram"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="bottom-content">
                <p>&copy; 2024 <strong>KhamCare International Hospital</strong>. Tất cả quyền được bảo lưu.</p>
                <div class="bottom-links">
                    <a href="#">Chính sách bảo mật</a>
                    <a href="#">Điều khoản sử dụng</a>
                    <a href="#">Hỗ trợ khách hàng</a>
                </div>
            </div>
        </div>
    </div>
  </footer>

  <script>
    // Menu and Footer translation
    const footerTranslations = {
      vi: {
        // Menu items
        navSearch: "Tìm bác sĩ",
        navSpecialty: "Chuyên khoa",
        navConsult: "Tư vấn",
        navGuide: "Cẩm nang Sức khỏe",
        navDoctor: "Dành cho Bác sĩ",
        navAccount: "Tài khoản",
        // Footer
        brandSubtitle: "INTERNATIONAL HOSPITAL",
        brandDesc: "Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. Kết nối bạn với các bác sĩ chuyên khoa uy tín.",
        statPatients: "Bệnh nhân",
        statDoctors: "Bác sĩ",
        statSpecialties: "Chuyên khoa",
        patientTitle: "Dành Cho Bệnh Nhân",
        patientLink1: "Tìm kiếm bác sĩ",
        patientLink2: "Đăng nhập",
        patientLink3: "Đăng ký",
        patientLink4: "Đặt lịch",
        patientLink5: "Bảng kiểm soát lịch hẹn",
        doctorTitle: "Dành Cho Bác Sĩ",
        doctorLink1: "Kiểm tra cuộc hẹn",
        doctorLink2: "Chat",
        doctorLink3: "Đăng nhập",
        doctorLink4: "Đăng ký",
        doctorLink5: "Dashboard của bác sĩ",
        contactTitle: "Liên Hệ",
        contactAddress: "<strong>Địa chỉ:</strong><br>Sở y tế - Bệnh viện đa khoa quốc tế<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
        contactHotline: "<strong>Hotline:</strong><br>0433636050",
        contactEmail: "<strong>Email:</strong><br>benhviendakhoaquocte@gmail.com",
        socialTitle: "Kết nối với chúng tôi",
        copyright: "© 2024 <strong>KhamCare International Hospital</strong>. Tất cả quyền được bảo lưu.",
        privacy: "Chính sách bảo mật",
        terms: "Điều khoản sử dụng",
        support: "Hỗ trợ khách hàng"
      },
      km: {
        // Menu items
        navSearch: "ស្វែងរកវេជ្ជបណ្ឌិត",
        navSpecialty: "ជំនាញ",
        navConsult: "ប្រឹក្សា",
        navGuide: "មគ្គុទ្ទេសក៍សុខភាព",
        navDoctor: "សម្រាប់វេជ្ជបណ្ឌិត",
        navAccount: "គណនី",
        // Footer
        brandSubtitle: "មន្ទីរពេទ្យអន្តរជាតិ",
        brandDesc: "ប្រព័ន្ធកក់ពេលវេលាពិនិត្យសុខភាពតាមអនឡាញឈានមុខគេនៅវៀតណាម។ ភ្ជាប់អ្នកជាមួយវេជ្ជបណ្ឌិតជំនាញដែលមានកេរ្តិ៍ឈ្មោះ។",
        statPatients: "អ្នកជម្ងឺ",
        statDoctors: "វេជ្ជបណ្ឌិត",
        statSpecialties: "ជំនាញ",
        patientTitle: "សម្រាប់អ្នកជម្ងឺ",
        patientLink1: "ស្វែងរកវេជ្ជបណ្ឌិត",
        patientLink2: "ចូលប្រព័ន្ធ",
        patientLink3: "ចុះឈ្មោះ",
        patientLink4: "កក់ពេលវេលា",
        patientLink5: "តារាងត្រួតពិនិត្យការណាត់ជួប",
        doctorTitle: "សម្រាប់វេជ្ជបណ្ឌិត",
        doctorLink1: "ត្រួតពិនិត្យការណាត់ជួប",
        doctorLink2: "ជជែក",
        doctorLink3: "ចូលប្រព័ន្ធ",
        doctorLink4: "ចុះឈ្មោះ",
        doctorLink5: "ផ្ទាំងគ្រប់គ្រងរបស់វេជ្ជបណ្ឌិត",
        contactTitle: "ទាក់ទង",
        contactAddress: "<strong>អាសយដ្ឋាន:</strong><br>ក្រសួងសុខាភិបាល - មន្ទីរពេទ្យពហុជំនាញអន្តរជាតិ<br>458 Minh Khai, Q. Hai Bà Trưng, TP.HCM",
        contactHotline: "<strong>Hotline:</strong><br>0433636050",
        contactEmail: "<strong>Email:</strong><br>benhviendakhoaquocte@gmail.com",
        socialTitle: "ភ្ជាប់ជាមួយយើង",
        copyright: "© ២០២៤ <strong>KhamCare មន្ទីរពេទ្យអន្តរជាតិ</strong>។ រក្សាសិទ្ធិគ្រប់យ៉ាង។",
        privacy: "គោលការណ៍ភាពឯកជន",
        terms: "លក្ខខណ្ឌប្រើប្រាស់",
        support: "ការគាំទ្រអតិថិជន"
      }
    };

    // Apply footer translation
    function applyFooterTranslation(lang) {
      const t = footerTranslations[lang] || footerTranslations.vi;
      
      // Update menu items
      const navSearch = document.getElementById('navSearch');
      const navSpecialty = document.getElementById('navSpecialty');
      const navConsult = document.getElementById('navConsult');
      const navGuide = document.getElementById('navGuide');
      const navDoctorText = document.getElementById('navDoctorText');
      const navAccount = document.getElementById('navAccount');
      
      if (navSearch) navSearch.textContent = t.navSearch;
      if (navSpecialty) navSpecialty.textContent = t.navSpecialty;
      if (navConsult) navConsult.textContent = t.navConsult;
      if (navGuide) navGuide.textContent = t.navGuide;
      if (navDoctorText) navDoctorText.textContent = t.navDoctor;
      if (navAccount) navAccount.textContent = t.navAccount;
      
      // Update brand info
      const brandSubtitle = document.querySelector('.brand-info p');
      const brandDesc = document.querySelector('.brand-desc');
      if (brandSubtitle) brandSubtitle.textContent = t.brandSubtitle;
      if (brandDesc) brandDesc.textContent = t.brandDesc;
      
      // Update stats
      const statLabels = document.querySelectorAll('.brand-stats .label');
      if (statLabels[0]) statLabels[0].textContent = t.statPatients;
      if (statLabels[1]) statLabels[1].textContent = t.statDoctors;
      if (statLabels[2]) statLabels[2].textContent = t.statSpecialties;
      
      // Update patient section
      const patientTitle = document.querySelector('.footer-column:nth-child(2) h3');
      if (patientTitle) patientTitle.innerHTML = '<i class="fas fa-user-injured"></i> ' + t.patientTitle;
      
      const patientLinks = document.querySelectorAll('.footer-column:nth-child(2) a');
      const patientTexts = [t.patientLink1, t.patientLink2, t.patientLink3, t.patientLink4, t.patientLink5];
      patientLinks.forEach((link, i) => {
        const icon = link.querySelector('i');
        if (icon && patientTexts[i]) {
          link.innerHTML = icon.outerHTML + ' ' + patientTexts[i];
        }
      });
      
      // Update doctor section
      const doctorTitle = document.querySelector('.footer-column:nth-child(3) h3');
      if (doctorTitle) doctorTitle.innerHTML = '<i class="fas fa-user-md"></i> ' + t.doctorTitle;
      
      const doctorLinks = document.querySelectorAll('.footer-column:nth-child(3) a');
      const doctorTexts = [t.doctorLink1, t.doctorLink2, t.doctorLink3, t.doctorLink4, t.doctorLink5];
      doctorLinks.forEach((link, i) => {
        const icon = link.querySelector('i');
        if (icon && doctorTexts[i]) {
          link.innerHTML = icon.outerHTML + ' ' + doctorTexts[i];
        }
      });
      
      // Update contact section
      const contactTitle = document.querySelector('.contact-column h3');
      if (contactTitle) contactTitle.innerHTML = '<i class="fas fa-phone-alt"></i> ' + t.contactTitle;
      
      const contactItems = document.querySelectorAll('.contact-item div');
      if (contactItems[0]) contactItems[0].innerHTML = t.contactAddress;
      if (contactItems[1]) contactItems[1].innerHTML = t.contactHotline;
      if (contactItems[2]) contactItems[2].innerHTML = t.contactEmail;
      
      // Update social title
      const socialTitle = document.querySelector('.social-section h4');
      if (socialTitle) socialTitle.textContent = t.socialTitle;
      
      // Update footer bottom
      const copyright = document.querySelector('.footer-bottom p');
      if (copyright) copyright.innerHTML = t.copyright;
      
      const bottomLinks = document.querySelectorAll('.bottom-links a');
      if (bottomLinks[0]) bottomLinks[0].textContent = t.privacy;
      if (bottomLinks[1]) bottomLinks[1].textContent = t.terms;
      if (bottomLinks[2]) bottomLinks[2].textContent = t.support;
    }

    // Listen for language changes
    document.addEventListener('DOMContentLoaded', function() {
      const savedLang = localStorage.getItem('kham_lang') || 'vi';
      applyFooterTranslation(savedLang);
      
      // Listen for language select changes
      const langSelect = document.getElementById('langSelect');
      if (langSelect) {
        langSelect.addEventListener('change', function() {
          applyFooterTranslation(this.value);
        });
      }
    });

    // Language switcher functions
    function toggleLanguageDropdown(event) {
      event.stopPropagation();
      const switcher = document.getElementById('langSwitcher');
      switcher.classList.toggle('active');
    }

    function changeLanguage(lang) {
      localStorage.setItem('kham_lang', lang);
      
      // Update button text
      const currentLangText = document.getElementById('currentLangText');
      if (currentLangText) {
        currentLangText.textContent = lang === 'vi' ? 'VN' : 'KH';
      }
      
      // Update active state
      document.querySelectorAll('.lang-option').forEach(opt => {
        opt.classList.remove('active');
        if (opt.getAttribute('data-lang') === lang) {
          opt.classList.add('active');
        }
      });
      
      // Close dropdown
      document.getElementById('langSwitcher').classList.remove('active');
      
      // Apply translation
      if (typeof applyLanguage === 'function') {
        applyLanguage(lang);
      }
      applyFooterTranslation(lang);
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const switcher = document.getElementById('langSwitcher');
      if (switcher && !switcher.contains(event.target)) {
        switcher.classList.remove('active');
      }
    });

    // Initialize language on page load
    window.addEventListener('DOMContentLoaded', function() {
      const savedLang = localStorage.getItem('kham_lang') || 'vi';
      changeLanguage(savedLang);
    });

    // Đồng bộ ngôn ngữ giữa các trang
    window.addEventListener('storage', function(e) {
      if (e.key === 'kham_lang' && e.newValue) {
        console.log('🔄 Language changed in another tab, reloading page...');
        location.reload();
      }
    });

    // Lắng nghe BroadcastChannel (cho trình duyệt hiện đại)
    if (typeof BroadcastChannel !== 'undefined') {
      const channel = new BroadcastChannel('kham_lang_sync');
      channel.addEventListener('message', function(event) {
        if (event.data.type === 'LANGUAGE_CHANGED') {
          console.log('🔄 Language changed via BroadcastChannel, reloading...');
          location.reload();
        }
      });
    }

    // Account Dropdown Toggle
    function toggleAccountDropdown(event) {
      event.stopPropagation();
      const dropdown = document.getElementById('accountDropdown');
      dropdown.classList.toggle('active');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const dropdown = document.getElementById('accountDropdown');
      if (dropdown && !dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
      }
    });
  </script>
  <script src="js/language-system.js"></script>
  
  <!-- Chatbot AI Widget -->
  <?php include 'chatbot_widget.php'; ?>
</body>
</html>

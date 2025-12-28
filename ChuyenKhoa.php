<?php
/**
 * Trang Chuy�n Khoa - Hi?n th? danh s�ch b�c si theo chuy�n khoa
 */

session_start();
require_once 'db_config.php';
require_once 'doctor_data_helper.php';

// L?y tham s? chuy�n khoa t? URL
$specialty_name = $_GET['specialty'] ?? '';

$pdo = getDBConnection();
if (!$pdo) {
    die('L?i k?t n?i database');
}

// L?y th�ng tin chuy�n khoa
$specialty_info = null;
$doctors = [];

if (!empty($specialty_name)) {
    try {
        // L?y th�ng tin chuy�n khoa
        $stmt = $pdo->prepare("SELECT * FROM specialties WHERE name = ? AND status = 'active'");
        $stmt->execute([$specialty_name]);
        $specialty_info = $stmt->fetch();
        
        // L?y danh s�ch b�c si theo chuy�n khoa
        $sql = "SELECT d.*, u.full_name, u.phone, u.email, s.name as specialty_name, s.icon, s.color 
                FROM doctors d 
                JOIN users u ON d.user_id = u.id 
                JOIN specialties s ON d.specialty_id = s.id 
                WHERE d.status = 'active' AND u.status = 'active' AND s.name = ?
                ORDER BY d.rating DESC, d.total_reviews DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$specialty_name]);
        $doctors = $stmt->fetchAll();
        
        // N?u database tr?ng, s? d?ng fallback data
        if (empty($doctors)) {
            $allDoctors = getTopDoctorsFromViewDoctor();
            $doctors = array_filter($allDoctors, function($doctor) use ($specialty_name) {
                return stripos($doctor['specialty_name'], $specialty_name) !== false;
            });
        }
        
    } catch (PDOException $e) {
        error_log("Get specialty doctors error: " . $e->getMessage());
        // Fallback
        $allDoctors = getTopDoctorsFromViewDoctor();
        $doctors = array_filter($allDoctors, function($doctor) use ($specialty_name) {
            return stripos($doctor['specialty_name'], $specialty_name) !== false;
        });
    }
}

// Mapping icon cho c�c chuy�n khoa
$specialty_icons = [
    'Tim M?ch' => 'fa-heartbeat',
    'Th?n Kinh' => 'fa-brain',
    'S?n Ph? Khoa' => 'fa-female',
    'Da Li?u' => 'fa-hand-paper',
    'Nhi Khoa' => 'fa-baby',
    'N?i T?ng Qu�t' => 'fa-user-md',
    'Co Xuong Kh?p' => 'fa-bone',
    'Ti�u H�a' => 'fa-stomach',
    'Tai Mui H?ng' => 'fa-head-side-mask',
    'C?t S?ng' => 'fa-spine',
    'Y H?c C? Truy?n' => 'fa-leaf',
    'Si�u �m Thai' => 'fa-baby-carriage',
    'Cham C?u' => 'fa-hand-holding-medical',
    'Ch?nh Thuong Ch?nh H�nh' => 'fa-bone',
    'M?t' => 'fa-eye',
    'Ngo?i Khoa' => 'fa-user-md',
    'Rang H�m M?t' => 'fa-tooth',
    'H� H?p' => 'fa-lungs',
    'N?i Ti?t' => 'fa-dna'
];

$icon_class = $specialty_icons[$specialty_name] ?? 'fa-stethoscope';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($specialty_name) ?> - KhamCare</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="shared-styles.css">
  <link rel="stylesheet" href="global-font-override.css">
  <link rel="stylesheet" href="square-icons-override.css">
  <link rel="stylesheet" href="fix-icons-visibility.css">
  <link rel="stylesheet" href="doctor-card-modern.css">
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
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', 'Noto Sans Khmer', Arial, sans-serif;
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

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }

    .specialty-header-content {
      animation: fadeInUp 0.8s ease-out;
    }

    .specialty-card {
      animation: fadeInUp 0.6s ease-out backwards;
    }

    .specialty-card:nth-child(1) { animation-delay: 0.1s; }
    .specialty-card:nth-child(2) { animation-delay: 0.2s; }
    .specialty-card:nth-child(3) { animation-delay: 0.3s; }
    .specialty-card:nth-child(4) { animation-delay: 0.4s; }
    .specialty-card:nth-child(5) { animation-delay: 0.5s; }
    .specialty-card:nth-child(6) { animation-delay: 0.6s; }
    .specialty-card:nth-child(7) { animation-delay: 0.7s; }
    .specialty-card:nth-child(8) { animation-delay: 0.8s; }

    .doctor-card {
      animation: fadeInUp 0.6s ease-out backwards;
    }

    .doctor-card:nth-child(1) { animation-delay: 0.1s; }
    .doctor-card:nth-child(2) { animation-delay: 0.2s; }
    .doctor-card:nth-child(3) { animation-delay: 0.3s; }
    .doctor-card:nth-child(4) { animation-delay: 0.4s; }
    .doctor-card:nth-child(5) { animation-delay: 0.5s; }
    .doctor-card:nth-child(6) { animation-delay: 0.6s; }

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

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    /* Logo */
    .logo {
      display: flex;
      align-items: center;
      gap: 15px;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
      font-size: 2.2rem;
      font-weight: 800;
      color: #06b6d4;
      text-decoration: none;
      transition: var(--transition);
    }

    .logo span {
      background: linear-gradient(135deg, #06b6d4 0%, #0ea5e9 50%, #10b981 100%);
      background-size: 200% auto;
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .logo img {
      width: 65px;
      height: 65px;
      object-fit: contain;
      border-radius: 18px;
      background: transparent;
      filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.15));
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

    /* Featured Specialties Section - 6 chuyên khoa nổi bật */
    .featured-specialties-section {
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
      padding: 3rem 0;
      position: relative;
    }

    .featured-specialties-grid {
      display: flex;
      flex-wrap: nowrap;
      justify-content: center;
      gap: 16px;
      max-width: 1200px;
      margin: 0 auto;
    }

    .featured-specialties-section .specialty-item {
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      padding: 28px 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-decoration: none;
      color: var(--gray-800);
      font-weight: 600;
      font-size: 1.1rem;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      border: 2px solid #e0f2fe;
      min-height: 180px;
      min-width: 160px;
      max-width: 200px;
      justify-content: center;
      flex: 0 0 auto;
    }

    .featured-specialties-section .specialty-item:hover {
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 12px 32px rgba(14, 165, 233, 0.2);
      border-color: #0ea5e9;
    }

    .featured-specialties-section .icon-box {
      width: 70px;
      height: 70px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
      box-shadow: 0 6px 16px rgba(14, 165, 233, 0.25);
      transition: all 0.3s ease;
    }

    .featured-specialties-section .specialty-item:hover .icon-box {
      transform: scale(1.1);
      box-shadow: 0 8px 20px rgba(14, 165, 233, 0.35);
    }

    @media (max-width: 992px) {
      .featured-specialties-grid {
        flex-wrap: wrap;
        justify-content: center;
      }
    }

    /* Other Specialties Section */
    .other-specialties-section {
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
      padding: 4rem 0;
      position: relative;
      overflow: hidden;
    }

    .other-specialties-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: 
        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="medical" width="60" height="60" patternUnits="userSpaceOnUse"><g fill="rgba(14, 165, 233, 0.04)"><rect x="25" y="15" width="10" height="30"/><rect x="15" y="25" width="30" height="10"/></g></pattern></defs><rect width="60" height="60" fill="url(%23medical)"/></svg>');
      background-size: 120px 120px;
      opacity: 0.3;
    }

    .section-description {
      text-align: center;
      color: var(--gray-600);
      font-size: 1.2rem;
      margin-bottom: 3rem;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }

    .specialties-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      position: relative;
      z-index: 2;
    }

    .specialty-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      border-radius: var(--radius-lg);
      padding: 2.5rem 2rem;
      text-align: center;
      text-decoration: none;
      color: var(--gray-800);
      border: 2px solid rgba(255, 255, 255, 0.6);
      box-shadow: 
        0 10px 30px rgba(14, 165, 233, 0.08),
        0 4px 12px rgba(0, 0, 0, 0.05);
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }

    .specialty-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(14, 165, 233, 0.1), transparent);
      transition: var(--transition);
    }

    .specialty-card:hover {
      transform: translateY(-8px);
      box-shadow: 
        0 20px 40px rgba(14, 165, 233, 0.15),
        0 8px 20px rgba(0, 0, 0, 0.1);
      border-color: rgba(14, 165, 233, 0.3);
    }

    .specialty-card:hover::before {
      left: 100%;
    }

    .specialty-card .specialty-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #10b981 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 2rem;
      color: var(--white);
      box-shadow: 0 10px 25px rgba(14, 165, 233, 0.25);
      transition: var(--transition);
    }

    .specialty-card:hover .specialty-icon {
      transform: scale(1.1) rotate(5deg);
      box-shadow: 0 15px 35px rgba(14, 165, 233, 0.35);
    }

    .specialty-card h4 {
      font-family: 'Poppins', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--gray-800);
      margin-bottom: 0.75rem;
    }

    .specialty-card p {
      color: var(--gray-600);
      font-size: 1rem;
      line-height: 1.5;
    }

    .specialty-card:hover h4 {
      color: var(--primary);
    }

    /* Specialty Header */
    .specialty-header {
      background: linear-gradient(135deg, rgba(14, 165, 233, 0.95) 0%, rgba(6, 182, 212, 0.9) 100%);
      padding: 4rem 0 3rem 0;
      margin-bottom: 0;
      position: relative;
      overflow: hidden;
    }

    .specialty-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: 
        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="medical" width="60" height="60" patternUnits="userSpaceOnUse"><g fill="rgba(255, 255, 255, 0.1)"><rect x="25" y="15" width="10" height="30"/><rect x="15" y="25" width="30" height="10"/></g></pattern></defs><rect width="60" height="60" fill="url(%23medical)"/></svg>');
      background-size: 120px 120px;
      opacity: 0.3;
    }

    .specialty-header-content {
      position: relative;
      z-index: 2;
      text-align: center;
    }

    .specialty-icon-large {
      width: 120px;
      height: 120px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 2rem;
      border: 3px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .specialty-icon-large i {
      font-size: 4rem;
      color: var(--white);
    }

    .specialty-header h1 {
      font-family: 'Poppins', sans-serif;
      font-size: 3.5rem;
      font-weight: 800;
      color: var(--white);
      margin-bottom: 1rem;
      text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .specialty-header p {
      font-size: 1.3rem;
      color: rgba(255, 255, 255, 0.95);
      max-width: 600px;
      margin: 0 auto;
    }

    /* Doctors Section */
    .doctors-section {
      padding: 4rem 0;
      background: var(--white);
    }

    .section-title {
      font-family: 'Poppins', sans-serif;
      font-size: 2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 30%, #0ea5e9 70%, #10b981 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 2rem;
      text-align: center;
    }

    .doctors-count {
      text-align: center;
      color: var(--gray-600);
      font-size: 1.1rem;
      margin-bottom: 3rem;
    }

    .doctors-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 2rem;
    }

    /* Doctor Card */
    .doctor-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 2rem;
      box-shadow: var(--shadow);
      border: 2px solid var(--gray-100);
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }

    .doctor-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 4px;
      height: 0;
      background: linear-gradient(180deg, #0ea5e9, #10b981);
      transition: var(--transition);
    }

    .doctor-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-lg);
      border-color: var(--primary-light);
    }

    .doctor-card:hover::before {
      height: 100%;
    }

    .doctor-header {
      display: flex;
      align-items: flex-start;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .doctor-avatar {
      width: 90px;
      height: 90px;
      border-radius: 12px;
      object-fit: cover;
      border: 4px solid var(--primary-light);
      background: var(--gray-100);
      flex-shrink: 0;
    }

    .doctor-info h3 {
      font-family: 'Poppins', sans-serif;
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--gray-800);
      margin-bottom: 0.5rem;
    }

    .doctor-specialty {
      display: inline-block;
      background: var(--primary);
      color: var(--white);
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .doctor-stats {
      display: flex;
      gap: 1.5rem;
      margin-bottom: 1rem;
      flex-wrap: wrap;
    }

    .stat-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--gray-600);
      font-size: 0.95rem;
    }

    .stat-item i {
      color: var(--primary);
    }

    .doctor-bio {
      color: var(--gray-700);
      line-height: 1.6;
      margin-bottom: 1.5rem;
      font-size: 0.95rem;
    }

    .doctor-actions {
      display: flex;
      gap: 1rem;
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
      flex: 1;
      justify-content: center;
    }

    .btn-book:hover {
      background: #059669;
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
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      flex: 1;
      justify-content: center;
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

    /* Stats Badge */
    .stats-badge {
      display: inline-flex;
      align-items: center;
      gap: 1rem;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      padding: 1rem 2rem;
      border-radius: 50px;
      margin-top: 2rem;
      border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .stats-badge .stat {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--white);
      font-weight: 600;
    }

    .stats-badge .stat i {
      font-size: 1.2rem;
    }

    .stats-badge .divider {
      width: 2px;
      height: 30px;
      background: rgba(255, 255, 255, 0.3);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .header .container {
        flex-wrap: wrap;
      }

      .nav-menu {
        order: 3;
        width: 100%;
        margin-top: 1rem;
        gap: 1rem;
        overflow-x: auto;
      }

      .nav-link {
        white-space: nowrap;
        font-size: 0.9rem;
      }

      .header-actions {
        gap: 0.5rem;
      }

      .btn-account, .btn-language {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
      }

      .specialty-header h1 {
        font-size: 2rem;
      }

      .specialty-icon-large {
        width: 90px;
        height: 90px;
      }

      .specialty-icon-large i {
        font-size: 3rem;
      }

      .specialties-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }

      .stats-badge {
        flex-direction: column;
        gap: 0.5rem;
        padding: 1rem 1.5rem;
      }

      .stats-badge .divider {
        width: 100%;
        height: 2px;
      }
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
      font-family: 'Poppins', sans-serif;
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
      font-family: 'Poppins', sans-serif;
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

    .footer-column a i,
    .footer-column a .footer-emoji {
      width: 18px;
      color: #0ea5e9;
      font-size: 0.9rem;
      font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif;
      font-style: normal;
    }

    .footer-column h3 .footer-emoji,
    .contact-item .footer-emoji {
      font-family: "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji", sans-serif;
      font-style: normal;
      margin-right: 8px;
      font-size: 1.1rem;
    }

    .footer-column a:hover {
      color: #ffffff;
      transform: translateX(8px);
    }

    .footer-column a:hover i,
    .footer-column a:hover .footer-emoji {
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
  <header class="header">
    <div class="container">
      <a href="TrangChu.php" class="logo">
        <img src="image/logo.png.png" alt="KhamCare Logo">
        <span>KhamCare</span>
      </a>
      
      <nav class="nav-menu">
        <a href="TimKiemBS.php" class="nav-link" id="navSearch" data-translate="navSearch">Tìm bác sĩ</a>
        <a href="ChuyenKhoa.php" class="nav-link active" id="navSpecialty" data-translate="navSpecialty">Chuyên khoa</a>
        <a href="TuVanTrucTuyen.php" class="nav-link" id="navConsult" data-translate="navConsult">Tư vấn</a>
        <a href="Camnangsuckhoe.php" class="nav-link" id="navGuide" data-translate="navGuide">Cẩm nang Sức khỏe</a>
        <a href="doctor_auth.php" class="nav-link doctor-link" id="navDoctor">
          <i class="fas fa-user-md"></i> <span id="navDoctorText" data-translate="navDoctor">Dành Cho Bác Sĩ</span>
        </a>
      </nav>
      
      <div class="header-actions">
        <div class="account-dropdown-wrapper" id="accountDropdown">
          <button class="btn-account-dropdown" onclick="toggleAccountDropdown(event)">
            <i class="fas fa-user"></i>
            <span id="navAccount" data-translate="navAccount">Tài khoản</span>
            <i class="fas fa-chevron-down arrow"></i>
          </button>
          <div class="account-dropdown-menu">
            <a href="tk.php" class="account-dropdown-item">
              <i class="fas fa-user-circle"></i>
              <span data-translate="myProfile">Hồ sơ của tôi</span>
            </a>
            <a href="DatLichK.php" class="account-dropdown-item">
              <i class="fas fa-calendar-check"></i>
              <span data-translate="myAppointments">Lịch hẹn</span>
            </a>
            <a href="TuVanTrucTuyen.php" class="account-dropdown-item">
              <i class="fas fa-comments"></i>
              <span data-translate="onlineConsult">Tư vấn trực tuyến</span>
            </a>
            <a href="logout.php" class="account-dropdown-item logout">
              <i class="fas fa-sign-out-alt"></i>
              <span data-translate="logout">Đăng xuất</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Specialty Header -->
  <section class="specialty-header">
    <div class="container">
      <div class="specialty-header-content">
        <div class="specialty-icon-large">
          <i class="fas <?= $icon_class ?>"></i>
        </div>
        <h1 id="specialtyTitle"><?= htmlspecialchars($specialty_name) ?></h1>
        <p id="specialtyDesc" data-translate="specialtyTeamDesc">Đội ngũ bác sĩ chuyên khoa <?= htmlspecialchars($specialty_name) ?> giàu kinh nghiệm</p>
        
        <?php if (!empty($doctors)): ?>
        <div class="stats-badge">
          <div class="stat">
            <i class="fas fa-user-md"></i>
            <span id="statDoctorsCount"><?= count($doctors) ?> <span data-translate="statDoctors">Bác sĩ</span></span>
          </div>
          <div class="divider"></div>
          <div class="stat">
            <i class="fas fa-star"></i>
            <span data-translate="statHighRating">Đánh giá cao</span>
          </div>
          <div class="divider"></div>
          <div class="stat">
            <i class="fas fa-certificate"></i>
            <span data-translate="statExpertise">Chuyên môn cao</span>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Featured Specialties Section - 6 chuyên khoa nổi bật -->
  <section class="featured-specialties-section">
    <div class="container">
      <h2 class="section-title" data-translate="featuredSpecialtiesTitle">Chuyên Khoa Nổi Bật</h2>
      <div class="featured-specialties-grid">
        <div class="specialty-item" onclick="window.location.href='ChuyenKhoa.php?specialty=Tim Mạch'">
          <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
            <i class="fas fa-heartbeat" style="font-size: 32px; color: white;"></i>
          </div>
          <span data-translate="specialtyTimMach" data-original="Tim Mạch">Tim Mạch</span>
        </div>
        <div class="specialty-item" onclick="window.location.href='ChuyenKhoa.php?specialty=Thần Kinh'">
          <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
            <i class="fas fa-brain" style="font-size: 32px; color: white;"></i>
          </div>
          <span data-translate="specialtyThanKinh" data-original="Thần Kinh">Thần Kinh</span>
        </div>
        <div class="specialty-item" onclick="window.location.href='ChuyenKhoa.php?specialty=Sản Phụ Khoa'">
          <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
            <i class="fas fa-baby" style="font-size: 32px; color: white;"></i>
          </div>
          <span data-translate="specialtySanPhuKhoa" data-original="Sản Phụ Khoa">Sản Phụ Khoa</span>
        </div>
        <div class="specialty-item" onclick="window.location.href='ChuyenKhoa.php?specialty=Da Liễu'">
          <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
            <i class="fas fa-hand-sparkles" style="font-size: 32px; color: white;"></i>
          </div>
          <span data-translate="specialtyDaLieu" data-original="Da Liễu">Da Liễu</span>
        </div>
        <div class="specialty-item" onclick="window.location.href='ChuyenKhoa.php?specialty=Nhi Khoa'">
          <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
            <i class="fas fa-child" style="font-size: 32px; color: white;"></i>
          </div>
          <span data-translate="specialtyNhiKhoa" data-original="Nhi Khoa">Nhi Khoa</span>
        </div>
        <div class="specialty-item" onclick="window.location.href='ChuyenKhoa.php?specialty=Nội Tổng Quát'">
          <div class="icon-box" style="background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);">
            <i class="fas fa-stethoscope" style="font-size: 32px; color: white;"></i>
          </div>
          <span data-translate="specialtyNoiTongQuat" data-original="Nội Tổng Quát">Nội Tổng Quát</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Other Specialties Section -->
  <section class="other-specialties-section">
    <div class="container">
      <h2 class="section-title" data-translate="otherSpecialtiesTitle">Chuyên Khoa Khác</h2>
      <p class="section-description" data-translate="otherSpecialtiesDesc">Khám phá các chuyên khoa y tế khác của chúng tôi</p>
      
      <div class="specialties-grid">
        <a href="ChuyenKhoa.php?specialty=Xuong Khop" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-bone"></i></div>
          <h4 data-translate="specialtyXuongKhop" data-original="Xương Khớp">Xương Khớp</h4>
          <p data-translate="descXuongKhop">Điều trị các bệnh lý về xương, khớp, chấn thương thể thao</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=Da Lieu" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-hand-paper"></i></div>
          <h4 data-translate="specialtyDaLieu2" data-original="Da Liễu">Da Liễu</h4>
          <p data-translate="descDaLieu">Chăm sóc và điều trị các bệnh lý về da, thẩm mỹ da</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=Mat" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-eye"></i></div>
          <h4 data-translate="specialtyMat" data-original="Mắt">Mắt</h4>
          <p data-translate="descMat">Khám và điều trị các bệnh lý về mắt, phẫu thuật mắt</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=Ngoai Khoa" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-user-md"></i></div>
          <h4 data-translate="specialtyNgoaiKhoa" data-original="Ngoại Khoa">Ngoại Khoa</h4>
          <p data-translate="descNgoaiKhoa">Phẫu thuật các bệnh lý ngoại khoa, can thiệp tối thiểu</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=Tai Mui Hong" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-head-side-mask"></i></div>
          <h4 data-translate="specialtyTaiMuiHong" data-original="Tai Mũi Họng">Tai Mũi Họng</h4>
          <p data-translate="descTaiMuiHong">Điều trị các bệnh lý về tai, mũi, họng và đầu cổ</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=Rang Ham Mat" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-tooth"></i></div>
          <h4 data-translate="specialtyRangHamMat" data-original="Răng Hàm Mặt">Răng Hàm Mặt</h4>
          <p data-translate="descRangHamMat">Chăm sóc sức khỏe răng miệng, phẫu thuật hàm mặt</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=Than Kinh" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-brain"></i></div>
          <h4 data-translate="specialtyThanKinh2" data-original="Thần Kinh">Thần Kinh</h4>
          <p data-translate="descThanKinh">Điều trị các bệnh lý thần kinh, đột quỵ, động kinh</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=San Phu Khoa" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-female"></i></div>
          <h4 data-translate="specialtySanPhuKhoa2" data-original="Sản Phụ Khoa">Sản Phụ Khoa</h4>
          <p data-translate="descSanPhuKhoa">Chăm sóc sức khỏe phụ nữ, thai sản, sinh nở</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=Tieu Hoa" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-stomach"></i></div>
          <h4 data-translate="specialtyTieuHoa" data-original="Tiêu Hóa">Tiêu Hóa</h4>
          <p data-translate="descTieuHoa">Điều trị các bệnh lý về dạ dày, ruột, gan mật</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=Ho Hap" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-lungs"></i></div>
          <h4 data-translate="specialtyHoHap" data-original="Hô Hấp">Hô Hấp</h4>
          <p data-translate="descHoHap">Điều trị các bệnh lý về phổi, đường hô hấp</p>
        </a>

        <a href="ChuyenKhoa.php?specialty=Noi Tiet" class="specialty-card">
          <div class="specialty-icon"><i class="fas fa-dna"></i></div>
          <h4 data-translate="specialtyNoiTiet" data-original="Nội Tiết">Nội Tiết</h4>
          <p data-translate="descNoiTiet">Điều trị tiểu đường, tuyến giáp, rối loạn hormone</p>
        </a>
      </div>
    </div>
  </section>

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
    // Complete Translation Dictionary for ChuyenKhoa page
    const chuyenKhoaTranslations = {
      vi: {
        // Navigation
        navSearch: "Tìm bác sĩ",
        navSpecialty: "Chuyên khoa",
        navConsult: "Tư vấn",
        navGuide: "Cẩm nang Sức khỏe",
        navDoctor: "Dành cho Bác sĩ",
        navAccount: "Tài khoản",
        
        // Account Dropdown
        myProfile: "Hồ sơ của tôi",
        myAppointments: "Lịch hẹn",
        onlineConsult: "Tư vấn trực tuyến",
        logout: "Đăng xuất",
        
        // Specialty Header
        specialtyTeamDesc: "Đội ngũ bác sĩ chuyên khoa giàu kinh nghiệm",
        statDoctors: "Bác sĩ",
        statHighRating: "Đánh giá cao",
        statExpertise: "Chuyên môn cao",
        
        // Featured Specialties
        featuredSpecialtiesTitle: "Chuyên Khoa Nổi Bật",
        specialtyTimMach: "Tim Mạch",
        specialtyThanKinh: "Thần Kinh",
        specialtySanPhuKhoa: "Sản Phụ Khoa",
        specialtyDaLieu: "Da Liễu",
        specialtyNhiKhoa: "Nhi Khoa",
        specialtyNoiTongQuat: "Nội Tổng Quát",
        
        // Other Specialties
        otherSpecialtiesTitle: "Chuyên Khoa Khác",
        otherSpecialtiesDesc: "Khám phá các chuyên khoa y tế khác của chúng tôi",
        specialtyXuongKhop: "Xương Khớp",
        specialtyDaLieu2: "Da Liễu",
        specialtyMat: "Mắt",
        specialtyNgoaiKhoa: "Ngoại Khoa",
        specialtyTaiMuiHong: "Tai Mũi Họng",
        specialtyRangHamMat: "Răng Hàm Mặt",
        specialtyThanKinh2: "Thần Kinh",
        specialtySanPhuKhoa2: "Sản Phụ Khoa",
        specialtyTieuHoa: "Tiêu Hóa",
        specialtyHoHap: "Hô Hấp",
        specialtyNoiTiet: "Nội Tiết",
        
        // Specialty Descriptions
        descXuongKhop: "Điều trị các bệnh lý về xương, khớp, chấn thương thể thao",
        descDaLieu: "Chăm sóc và điều trị các bệnh lý về da, thẩm mỹ da",
        descMat: "Khám và điều trị các bệnh lý về mắt, phẫu thuật mắt",
        descNgoaiKhoa: "Phẫu thuật các bệnh lý ngoại khoa, can thiệp tối thiểu",
        descTaiMuiHong: "Điều trị các bệnh lý về tai, mũi, họng và đầu cổ",
        descRangHamMat: "Chăm sóc sức khỏe răng miệng, phẫu thuật hàm mặt",
        descThanKinh: "Điều trị các bệnh lý thần kinh, đột quỵ, động kinh",
        descSanPhuKhoa: "Chăm sóc sức khỏe phụ nữ, thai sản, sinh nở",
        descTieuHoa: "Điều trị các bệnh lý về dạ dày, ruột, gan mật",
        descHoHap: "Điều trị các bệnh lý về phổi, đường hô hấp",
        descNoiTiet: "Điều trị tiểu đường, tuyến giáp, rối loạn hormone",
        
        // Footer
        brandSubtitle: "INTERNATIONAL HOSPITAL",
        brandDesc: "Hệ thống đặt lịch khám bệnh trực tuyến hàng đầu Việt Nam. Kết nối bạn với các bác sĩ chuyên khoa uy tín.",
        statPatients: "Bệnh nhân",
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
        // Navigation
        navSearch: "ស្វែងរកវេជ្ជបណ្ឌិត",
        navSpecialty: "ជំនាញ",
        navConsult: "ប្រឹក្សា",
        navGuide: "មគ្គុទ្ទេសក៍សុខភាព",
        navDoctor: "សម្រាប់វេជ្ជបណ្ឌិត",
        navAccount: "គណនី",
        
        // Account Dropdown
        myProfile: "ប្រវត្តិរូបរបស់ខ្ញុំ",
        myAppointments: "ការណាត់ជួប",
        onlineConsult: "ប្រឹក្សាតាមអនឡាញ",
        logout: "ចាកចេញ",
        
        // Specialty Header
        specialtyTeamDesc: "ក្រុមវេជ្ជបណ្ឌិតជំនាញមានបទពិសោធន៍ច្រើន",
        statDoctors: "វេជ្ជបណ្ឌិត",
        statHighRating: "វាយតម្លៃខ្ពស់",
        statExpertise: "ជំនាញខ្ពស់",
        
        // Featured Specialties
        featuredSpecialtiesTitle: "ជំនាញពេទ្យពេញនិយម",
        specialtyTimMach: "ជំងឺបេះដូង",
        specialtyThanKinh: "ជំងឺប្រព័ន្ធសរសៃប្រសាទ",
        specialtySanPhuKhoa: "ពេទ្យស្រ្តី និងសម្រាល",
        specialtyDaLieu: "ជំងឺស្បែក",
        specialtyNhiKhoa: "ពេទ្យកុមារ",
        specialtyNoiTongQuat: "ពេទ្យផ្ទៃក្នុងទូទៅ",
        
        // Other Specialties
        otherSpecialtiesTitle: "ជំនាញពេទ្យផ្សេងទៀត",
        otherSpecialtiesDesc: "ស្វែងយល់ពីជំនាញពេទ្យផ្សេងទៀតរបស់យើង",
        specialtyXuongKhop: "ជំងឺឆ្អឹង និងសន្លាក់",
        specialtyDaLieu2: "ជំងឺស្បែក",
        specialtyMat: "ភ្នែក",
        specialtyNgoaiKhoa: "ជំនាញវះកាត់",
        specialtyTaiMuiHong: "ត្រចៀក ច្រមុះ បំពង់ក",
        specialtyRangHamMat: "ធ្មេញ និងមាត់",
        specialtyThanKinh2: "ជំងឺប្រព័ន្ធសរសៃប្រសាទ",
        specialtySanPhuKhoa2: "ពេទ្យស្រ្តី និងសម្រាល",
        specialtyTieuHoa: "ប្រព័ន្ធរំលាយអាហារ",
        specialtyHoHap: "ប្រព័ន្ធដង្ហើម",
        specialtyNoiTiet: "ជំងឺក្រពេញ",
        
        // Specialty Descriptions
        descXuongKhop: "ព្យាបាលជំងឺឆ្អឹង សន្លាក់ របួសកីឡា",
        descDaLieu: "ថែទាំ និងព្យាបាលជំងឺស្បែក សម្រស់ស្បែក",
        descMat: "ពិនិត្យ និងព្យាបាលជំងឺភ្នែក ការវះកាត់ភ្នែក",
        descNgoaiKhoa: "វះកាត់ជំងឺផ្នែកខាងក្រៅ ការធ្វើអន្តរាគមន៍តិចបំផុត",
        descTaiMuiHong: "ព្យាបាលជំងឺត្រចៀក ច្រមុះ បំពង់ក និងក",
        descRangHamMat: "ថែទាំសុខភាពធ្មេញមាត់ វះកាត់ថ្គាមមាត់",
        descThanKinh: "ព្យាបាលជំងឺសរសៃប្រសាទ ដាច់សរសៃឈាមខួរក្បាល ជំងឺឆ្កួត",
        descSanPhuKhoa: "ថែទាំសុខភាពស្ត្រី ការមានផ្ទៃពោះ ការសម្រាល",
        descTieuHoa: "ព្យាបាលជំងឺក្រពះ ពោះវៀន ថ្លើម",
        descHoHap: "ព្យាបាលជំងឺសួត ផ្លូវដង្ហើម",
        descNoiTiet: "ព្យាបាលជំងឺទឹកនោមផ្អែម ក្រពេញទីរ៉ូអ៊ីត ភាពមិនស្រួលហរម៉ូន",
        
        // Footer
        brandSubtitle: "មន្ទីរពេទ្យអន្តរជាតិ",
        brandDesc: "ប្រព័ន្ធកក់ពេលវេលាពិនិត្យសុខភាពតាមអនឡាញឈានមុខគេនៅវៀតណាម។ ភ្ជាប់អ្នកជាមួយវេជ្ជបណ្ឌិតជំនាញដែលមានកេរ្តិ៍ឈ្មោះ។",
        statPatients: "អ្នកជម្ងឺ",
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
        contactEmail: "<strong>អ៊ីម៉ែល:</strong><br>benhviendakhoaquocte@gmail.com",
        socialTitle: "ភ្ជាប់ជាមួយយើង",
        copyright: "© ២០២៤ <strong>KhamCare មន្ទីរពេទ្យអន្តរជាតិ</strong>។ រក្សាសិទ្ធិគ្រប់យ៉ាង។",
        privacy: "គោលការណ៍ភាពឯកជន",
        terms: "លក្ខខណ្ឌប្រើប្រាស់",
        support: "ការគាំទ្រអតិថិជន"
      }
    };
    
    // Alias for backward compatibility
    const footerTranslations = chuyenKhoaTranslations;


    // Main translation function for ChuyenKhoa page
    function applyChuyenKhoaTranslation(lang) {
      const t = chuyenKhoaTranslations[lang] || chuyenKhoaTranslations.vi;
      
      // Update all elements with data-translate attribute
      document.querySelectorAll('[data-translate]').forEach(el => {
        const key = el.getAttribute('data-translate');
        if (t[key]) {
          el.textContent = t[key];
        }
      });
      
      // Update footer (more complex elements)
      applyFooterTranslation(lang);
    }
    
    function applyFooterTranslation(lang) {
      const t = chuyenKhoaTranslations[lang] || chuyenKhoaTranslations.vi;
      
      // Update brand info
      const brandSubtitle = document.querySelector('.brand-info p');
      const brandDesc = document.querySelector('.brand-desc');
      if (brandSubtitle) brandSubtitle.textContent = t.brandSubtitle;
      if (brandDesc) brandDesc.textContent = t.brandDesc;
      
      // Update stats labels
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
        if (icon && patientTexts[i]) link.innerHTML = icon.outerHTML + ' ' + patientTexts[i];
      });
      
      // Update doctor section
      const doctorTitle = document.querySelector('.footer-column:nth-child(3) h3');
      if (doctorTitle) doctorTitle.innerHTML = '<i class="fas fa-user-md"></i> ' + t.doctorTitle;
      const doctorLinks = document.querySelectorAll('.footer-column:nth-child(3) a');
      const doctorTexts = [t.doctorLink1, t.doctorLink2, t.doctorLink3, t.doctorLink4, t.doctorLink5];
      doctorLinks.forEach((link, i) => {
        const icon = link.querySelector('i');
        if (icon && doctorTexts[i]) link.innerHTML = icon.outerHTML + ' ' + doctorTexts[i];
      });
      
      // Update contact section
      const contactTitle = document.querySelector('.contact-column h3');
      if (contactTitle) contactTitle.innerHTML = '<i class="fas fa-phone-alt"></i> ' + t.contactTitle;
      const contactItems = document.querySelectorAll('.contact-item div');
      if (contactItems[0]) contactItems[0].innerHTML = t.contactAddress;
      if (contactItems[1]) contactItems[1].innerHTML = t.contactHotline;
      if (contactItems[2]) contactItems[2].innerHTML = t.contactEmail;
      
      // Update social section
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

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
      const savedLang = localStorage.getItem('kham_lang') || 'vi';
      applyChuyenKhoaTranslation(savedLang);
      
      // Listen for language selector changes
      const langSelect = document.getElementById('langSelect');
      if (langSelect) {
        langSelect.addEventListener('change', function() {
          applyChuyenKhoaTranslation(this.value);
        });
      }
      
      // Listen for lang-option clicks
      document.querySelectorAll('.lang-option').forEach(option => {
        option.addEventListener('click', function() {
          const lang = this.getAttribute('data-lang');
          if (lang) {
            localStorage.setItem('kham_lang', lang);
            applyChuyenKhoaTranslation(lang);
            
            // Broadcast to other tabs
            if (typeof BroadcastChannel !== 'undefined') {
              const channel = new BroadcastChannel('kham_lang_sync');
              channel.postMessage({ type: 'LANGUAGE_CHANGED', lang: lang });
            }
          }
        });
      });
    });

    // Sync language between tabs via storage event
    window.addEventListener('storage', function(e) {
      if (e.key === 'kham_lang' && e.newValue) {
        console.log('Language changed in another tab, applying...');
        applyChuyenKhoaTranslation(e.newValue);
      }
    });

    // Listen for BroadcastChannel (modern browsers)
    if (typeof BroadcastChannel !== 'undefined') {
      const channel = new BroadcastChannel('kham_lang_sync');
      channel.addEventListener('message', function(event) {
        if (event.data.type === 'LANGUAGE_CHANGED') {
          console.log('Language changed via BroadcastChannel, applying...');
          applyChuyenKhoaTranslation(event.data.lang);
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

  <!-- Chatbot AI Widget -->
  <?php include 'chatbot_widget.php'; ?>

</body>
</html>

<?php
/**
 * Doctor Data Helper - Provides doctor data for the application
 */

function getTopDoctorsFromViewDoctor($limit = 18) {
    $connection = getDBConnection();
    
    // If no database connection, use mock data
    if (!$connection || (isset($GLOBALS['_connection_type']) && $GLOBALS['_connection_type'] === 'mock')) {
        error_log('[Doctor Helper] Using mock data - ' . ($connection ? 'connection type is mock' : 'no connection'));
        // Return mock doctor data
        // Dữ liệu 18 bác sĩ - Thứ tự hiển thị trong TrangChu.php
        // Mỗi chuyên khoa có 3 bác sĩ theo thứ tự xuất hiện trên trang chủ
        // Mock data với ID 1-18 khớp với database
        $mockDoctors = [
            // TIM MẠCH - 3 bác sĩ
            [
                'id' => 1,
                'full_name' => 'BS. Nguyễn Thị Lan',
                'specialty_name' => 'Tim Mạch',
                'rating' => 4.8,
                'total_reviews' => 156,
                'consultation_fee' => 350000,
                'experience_years' => 8,
                'status' => 'active',
                'display_order' => 1,
                'image' => 'image/nguyenthilan.png.png'
            ],
            [
                'id' => 6,
                'full_name' => 'BS. Nguyễn Văn Đức',
                'specialty_name' => 'Tim Mạch',
                'rating' => 4.5,
                'total_reviews' => 92,
                'consultation_fee' => 330000,
                'experience_years' => 7,
                'status' => 'active',
                'display_order' => 2,
                'image' => 'image/ngnuyenvanduc.png.webp'
            ],
            [
                'id' => 10,
                'full_name' => 'BS. Vũ Thị Mai',
                'specialty_name' => 'Tim Mạch',
                'rating' => 4.9,
                'total_reviews' => 189,
                'consultation_fee' => 420000,
                'experience_years' => 11,
                'status' => 'active',
                'display_order' => 3,
                'image' => 'image/vuthimai.png.png'
            ],
            
            // THẦN KINH - 3 bác sĩ
            [
                'id' => 2,
                'full_name' => 'BS. Trần Văn Hùng',
                'specialty_name' => 'Thần Kinh',
                'rating' => 4.9,
                'total_reviews' => 203,
                'consultation_fee' => 400000,
                'experience_years' => 12,
                'status' => 'active',
                'display_order' => 1,
                'image' => 'image/tranvanhung.png.png'
            ],
            [
                'id' => 7,
                'full_name' => 'BS. Đỗ Văn Hùng',
                'specialty_name' => 'Thần Kinh',
                'rating' => 4.7,
                'total_reviews' => 145,
                'consultation_fee' => 390000,
                'experience_years' => 9,
                'status' => 'active',
                'display_order' => 2,
                'image' => 'image/dovanhung.png.jpg'
            ],
            [
                'id' => 11,
                'full_name' => 'BS. Nguyễn Đức Minh',
                'specialty_name' => 'Thần Kinh',
                'rating' => 4.7,
                'total_reviews' => 156,
                'consultation_fee' => 380000,
                'experience_years' => 9,
                'status' => 'active',
                'display_order' => 3,
                'image' => 'image/nguyenducminh.png.png'
            ],
            
            // SẢN PHỤ KHOA - 3 bác sĩ
            [
                'id' => 3,
                'full_name' => 'BS. Trần Thị Hương',
                'specialty_name' => 'Sản Phụ Khoa',
                'rating' => 4.8,
                'total_reviews' => 178,
                'consultation_fee' => 450000,
                'experience_years' => 15,
                'status' => 'active',
                'display_order' => 1,
                'image' => 'image/tranthihuong.png.png'
            ],
            [
                'id' => 14,
                'full_name' => 'BS. Lê Thị Hoa',
                'specialty_name' => 'Sản Phụ Khoa',
                'rating' => 4.5,
                'total_reviews' => 87,
                'consultation_fee' => 320000,
                'experience_years' => 6,
                'status' => 'active',
                'display_order' => 2,
                'image' => 'image/lethihoa.png.png'
            ],
            [
                'id' => 15,
                'full_name' => 'BS. Nguyễn Thị Hạnh',
                'specialty_name' => 'Sản Phụ Khoa',
                'rating' => 4.7,
                'total_reviews' => 145,
                'consultation_fee' => 360000,
                'experience_years' => 8,
                'status' => 'active',
                'display_order' => 3,
                'image' => 'image/nguyenthihanh.png.png'
            ],
            
            // DA LIỄU - 3 bác sĩ
            [
                'id' => 4,
                'full_name' => 'BS. Lê Minh Tuấn',
                'specialty_name' => 'Da Liễu',
                'rating' => 4.6,
                'total_reviews' => 134,
                'consultation_fee' => 380000,
                'experience_years' => 10,
                'status' => 'active',
                'display_order' => 1,
                'image' => 'image/leminhtuan.png.png'
            ],
            [
                'id' => 9,
                'full_name' => 'BS. Phạm Thị Linh',
                'specialty_name' => 'Da Liễu',
                'rating' => 4.8,
                'total_reviews' => 112,
                'consultation_fee' => 370000,
                'experience_years' => 8,
                'status' => 'active',
                'display_order' => 2,
                'image' => 'image/phamthilan.png.png'
            ],
            [
                'id' => 12,
                'full_name' => 'BS. Trần Văn Nam',
                'specialty_name' => 'Da Liễu',
                'rating' => 4.6,
                'total_reviews' => 98,
                'consultation_fee' => 450000,
                'experience_years' => 12,
                'status' => 'active',
                'display_order' => 3,
                'image' => 'image/tranvannam.png.png'
            ],
            
            // NHI KHOA - 3 bác sĩ
            [
                'id' => 5,
                'full_name' => 'BS. Lê Thị Mai',
                'specialty_name' => 'Nhi Khoa',
                'rating' => 4.7,
                'total_reviews' => 89,
                'consultation_fee' => 320000,
                'experience_years' => 6,
                'status' => 'active',
                'display_order' => 1,
                'image' => 'image/lethimai.png.png'
            ],
            [
                'id' => 8,
                'full_name' => 'BS. Hoàng Minh Hoàng',
                'specialty_name' => 'Nhi Khoa',
                'rating' => 4.6,
                'total_reviews' => 67,
                'consultation_fee' => 310000,
                'experience_years' => 5,
                'status' => 'active',
                'display_order' => 2,
                'image' => 'image/hoangminhhoang.png.png'
            ],
            [
                'id' => 13,
                'full_name' => 'BS. Phạm Thị Lan',
                'specialty_name' => 'Nhi Khoa',
                'rating' => 4.8,
                'total_reviews' => 134,
                'consultation_fee' => 340000,
                'experience_years' => 7,
                'status' => 'active',
                'display_order' => 3,
                'image' => 'image/phamthilan.png.png'
            ],
            
            // NỘI TỔNG QUÁT - 3 bác sĩ
            [
                'id' => 16,
                'full_name' => 'BS. Nguyễn Văn Hòa',
                'specialty_name' => 'Nội Tổng Quát',
                'rating' => 4.6,
                'total_reviews' => 123,
                'consultation_fee' => 300000,
                'experience_years' => 10,
                'status' => 'active',
                'display_order' => 1,
                'image' => 'image/nguyenvanhoa.png.png'
            ],
            [
                'id' => 17,
                'full_name' => 'BS. Trần Thị Thu',
                'specialty_name' => 'Nội Tổng Quát',
                'rating' => 4.8,
                'total_reviews' => 167,
                'consultation_fee' => 380000,
                'experience_years' => 9,
                'status' => 'active',
                'display_order' => 2,
                'image' => 'image/tranthiphu.png.png'
            ],
            [
                'id' => 18,
                'full_name' => 'BS. Lê Minh Tâm',
                'specialty_name' => 'Nội Tổng Quát',
                'rating' => 4.7,
                'total_reviews' => 134,
                'consultation_fee' => 370000,
                'experience_years' => 8,
                'status' => 'active',
                'display_order' => 3,
                'image' => 'image/leminhtam.png.png'
            ]
        ];
        
        // Return only the requested number of doctors
        $result = array_slice($mockDoctors, 0, $limit);
        error_log('[Doctor Helper] Returning ' . count($result) . ' mock doctors');
        return $result;
    }
    
    // If we have a real database connection, query it
    try {
        $sql = "SELECT b.*, b.ho_ten as full_name, c.ten as specialty_name,
                       b.phi_kham as consultation_fee, b.nam_kinh_nghiem as experience_years,
                       b.danh_gia as rating, b.tong_danh_gia as total_reviews
                FROM bac_si b 
                JOIN nguoi_dung n ON b.nguoi_dung_id = n.id 
                JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id 
                WHERE b.trang_thai = 'hoat_dong' 
                ORDER BY b.danh_gia DESC, b.tong_danh_gia DESC 
                LIMIT ?";
        
        $result = executeQuery($sql, [$limit]);
        
        // Check if result is valid
        if (!is_array($result)) {
            error_log('[Doctor Helper] Database query returned invalid data type, falling back to mock data');
            $GLOBALS['_connection_type'] = 'mock';
            return getTopDoctorsFromViewDoctor($limit);
        }
        
        error_log('[Doctor Helper] Retrieved ' . count($result) . ' doctors from database');
        
        // If database returned empty, fallback to mock data
        if (empty($result)) {
            error_log('[Doctor Helper] Database returned empty result, falling back to mock data');
            $GLOBALS['_connection_type'] = 'mock';
            return getTopDoctorsFromViewDoctor($limit);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log('[Doctor Helper] Database query failed: ' . $e->getMessage() . ' - Falling back to mock data');
        $GLOBALS['_connection_type'] = 'mock';
        return getTopDoctorsFromViewDoctor($limit);
    }
}

function getDoctorsBySpecialty($specialty_id, $limit = 10) {
    $connection = getDBConnection();
    
    if ($GLOBALS['_connection_type'] === 'mock') {
        // Filter mock data by specialty
        $allDoctors = getTopDoctorsFromViewDoctor(20);
        $filtered = array_filter($allDoctors, function($doctor) use ($specialty_id) {
            $specialtyMap = [
                1 => 'Tim Mạch',
                2 => 'Thần Kinh', 
                3 => 'Nhi Khoa',
                4 => 'Da Liễu',
                5 => 'Sản Phụ Khoa'
            ];
            return isset($specialtyMap[$specialty_id]) && $doctor['specialty_name'] === $specialtyMap[$specialty_id];
        });
        
        return array_slice($filtered, 0, $limit);
    }
    
    try {
        $sql = "SELECT b.*, b.ho_ten as full_name, c.ten as specialty_name,
                       b.phi_kham as consultation_fee, b.nam_kinh_nghiem as experience_years,
                       b.danh_gia as rating, b.tong_danh_gia as total_reviews
                FROM bac_si b 
                JOIN nguoi_dung n ON b.nguoi_dung_id = n.id 
                JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id 
                WHERE b.trang_thai = 'hoat_dong' AND b.chuyen_khoa_id = ?
                ORDER BY b.danh_gia DESC 
                LIMIT ?";
        
        return executeQuery($sql, [$specialty_id, $limit]);
    } catch (Exception $e) {
        error_log("Error getting doctors by specialty: " . $e->getMessage());
        return [];
    }
}

if (!function_exists('getDoctorById')) {
    function getDoctorById($doctor_id) {
        $connection = getDBConnection();
        
        if (!$connection) {
            return null;
        }
        
        if (isset($GLOBALS['_connection_type']) && $GLOBALS['_connection_type'] === 'mock') {
            $allDoctors = getTopDoctorsFromViewDoctor(20);
            foreach ($allDoctors as $doctor) {
                if ($doctor['id'] == $doctor_id) {
                    // Add fields expected by view_doctor.php
                    $doctor['title'] = $doctor['specialty_name'];
                    $doctor['specialty'] = $doctor['specialty_name'];
                    $doctor['location'] = 'Hà Nội, Việt Nam';
                    $doctor['availability'] = 'Thứ 2 - Thứ 6: 8:00 - 17:00';
                    $doctor['experience'] = $doctor['experience_years'] . ' năm kinh nghiệm';
                    $doctor['degree'] = 'Bác sĩ chuyên khoa ' . $doctor['specialty_name'];
                    $doctor['doctor_row_id'] = $doctor['id'];
                    $doctor['phone'] = '024-3456-789' . substr($doctor['id'], -1);
                    $doctor['email'] = strtolower(str_replace([' ', '.'], '', $doctor['full_name'])) . '@hospital.vn';
                    $doctor['bio'] = 'Bác sĩ có ' . $doctor['experience_years'] . ' năm kinh nghiệm trong lĩnh vực ' . $doctor['specialty_name'] . '. Tốt nghiệp Đại học Y Hà Nội với thành tích xuất sắc.';
                    return $doctor;
                }
            }
            return null;
        }
        
        try {
            // Thử dùng bảng tiếng Việt trước
            $sql = "SELECT b.*, b.ho_ten as full_name, n.so_dien_thoai as phone, n.email, 
                           c.ten as specialty_name, c.icon, c.mau_sac as color,
                           b.phi_kham as consultation_fee, b.nam_kinh_nghiem as experience_years,
                           b.danh_gia as rating, b.tong_danh_gia as total_reviews
                    FROM bac_si b 
                    JOIN nguoi_dung n ON b.nguoi_dung_id = n.id 
                    JOIN chuyen_khoa c ON b.chuyen_khoa_id = c.id 
                    WHERE b.id = ? AND b.trang_thai = 'hoat_dong'";
            
            $result = executeQuery($sql, [$doctor_id]);
            if ($result && count($result) > 0) {
                $doctor = $result[0];
                // Add fields expected by view_doctor.php
                $doctor['title'] = $doctor['specialty_name'];
                $doctor['specialty'] = $doctor['specialty_name'];
                $doctor['doctor_row_id'] = $doctor['id'];
                return $doctor;
            }
            return null;
        } catch (Exception $e) {
            error_log("Error getting doctor by ID: " . $e->getMessage());
            return null;
        }
    }
}
?>
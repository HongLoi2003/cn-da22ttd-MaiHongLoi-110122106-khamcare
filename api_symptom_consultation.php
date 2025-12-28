<?php
session_start();
require_once 'db_config.php';
require_once 'gemini_config.php';

header('Content-Type: application/json');

// Kiểm tra user đã login
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để sử dụng tính năng này'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Xử lý request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $useGemini = isset($_POST['use_gemini']) && $_POST['use_gemini'] === 'true';
    $chatHistory = json_decode($_POST['chat_history'] ?? '[]', true);
    
    if (empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập triệu chứng'
        ]);
        exit;
    }
    
    try {
        // Lấy ngôn ngữ từ localStorage (được gửi qua POST hoặc mặc định là 'vi')
        $lang = $_POST['lang'] ?? 'vi';
        
        // Nếu bật chế độ Gemini AI
        if ($useGemini) {
            $geminiResult = callGeminiAPI($message, $chatHistory);
            
            if ($geminiResult['success']) {
                $aiResponse = $geminiResult['response'];
                
                // Phát hiện chuyên khoa từ phản hồi AI
                $suggestedSpecialty = detectSpecialtyFromText($aiResponse . ' ' . $message);
                
                // Lấy bác sĩ theo chuyên khoa
                $doctorsBySpecialty = getDoctorsBySpecialtyData();
                $doctors = isset($doctorsBySpecialty[$suggestedSpecialty]) ? $doctorsBySpecialty[$suggestedSpecialty] : [];
                
                // LƯU TRIỆU CHỨNG VÀO DATABASE (Gemini mode)
                try {
                    require_once 'symptom_analyzer.php';
                    $analyzer = new SymptomAnalyzer();
                    
                    $analysisResult = [
                        'user_input' => $message,
                        'main_symptom' => $message,
                        'severity' => 'trung bình',
                        'ai_analysis' => $aiResponse,
                        'suggested_specialties' => $suggestedSpecialty ? [['name' => $suggestedSpecialty]] : [],
                        'symptom_data' => null
                    ];
                    
                    $analyzer->saveUserSymptom($user_id, $message, $analysisResult);
                    error_log('[Gemini] Saved symptom for user_id: ' . $user_id);
                    
                    // LƯU VÀO BẢNG lich_su_tu_van
                    $pdo = getDBConnection();
                    if ($pdo) {
                        // Lấy tên người dùng
                        $stmt = $pdo->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = ? LIMIT 1");
                        $stmt->execute([$user_id]);
                        $userInfo = $stmt->fetch();
                        $tenNguoiDung = $userInfo ? $userInfo['ho_ten'] : 'Người dùng #' . $user_id;
                        
                        // Lưu tin nhắn người dùng
                        $stmt = $pdo->prepare("INSERT INTO lich_su_tu_van (nguoi_dung_id, ten_nguoi_gui, tu_van_id, noi_dung, loai, ngay_tao) VALUES (?, ?, 0, ?, 'nguoi_dung', NOW())");
                        $stmt->execute([$user_id, $tenNguoiDung, $message]);
                        
                        // Lưu phản hồi AI
                        $stmt = $pdo->prepare("INSERT INTO lich_su_tu_van (nguoi_dung_id, ten_nguoi_gui, tu_van_id, noi_dung, loai, ngay_tao) VALUES (?, ?, 0, ?, 'ai', NOW())");
                        $stmt->execute([$user_id, 'Gemini AI', $aiResponse]);
                        error_log('[Gemini] Saved to lich_su_tu_van for user_id: ' . $user_id);
                    }
                } catch (Exception $saveError) {
                    error_log('[Gemini] Error saving: ' . $saveError->getMessage());
                }
                
                echo json_encode([
                    'success' => true,
                    'responses' => [$aiResponse],
                    'is_gemini' => true,
                    'suggested_specialties' => $suggestedSpecialty ? [
                        ['id' => 1, 'name' => $suggestedSpecialty, 'description' => '']
                    ] : [],
                    'suggested_doctors' => $suggestedSpecialty ? [
                        $suggestedSpecialty => $doctors
                    ] : []
                ]);
                exit;
            } else {
                // Fallback nếu Gemini lỗi - tiếp tục với logic cũ
                error_log('[Gemini Error] ' . $geminiResult['error']);
            }
        }
        
        // Translations cho 6 chuyên khoa
        $translations = [
            'vi' => [
                'thank_you' => 'Cảm ơn bạn đã chia sẻ. Tôi hiểu bạn đang gặp triệu chứng:',
                'based_on' => 'Dựa trên triệu chứng của bạn, tôi đề xuất chuyên khoa',
                'suggest_doctors' => 'Tôi đề xuất bạn nên khám với các bác sĩ sau:',
                'specialty_label' => 'Chuyên khoa',
                'doctors_suggest' => 'Bác sĩ đề xuất:',
                'experience' => 'Kinh nghiệm',
                'years' => 'năm',
                'rating' => 'Đánh giá',
                'fee' => 'Phí khám',
                'view_profile' => 'Xem hồ sơ',
                'book_now' => 'Đặt lịch ngay',
                'book_appointment' => 'Đặt lịch',
                'symptoms' => [
                    'child' => 'triệu chứng ở trẻ em',
                    'heart' => 'đau ngực/tim',
                    'head' => 'đau đầu',
                    'skin' => 'vấn đề về da',
                    'gynecology' => 'vấn đề phụ khoa',
                    'general' => 'triệu chứng chung'
                ],
                'specialties' => [
                    'Tim Mạch' => 'Tim Mạch',
                    'Thần Kinh' => 'Thần Kinh',
                    'Nhi Khoa' => 'Nhi Khoa',
                    'Da Liễu' => 'Da Liễu',
                    'Sản Phụ Khoa' => 'Sản Phụ Khoa',
                    'Nội Tổng Quát' => 'Nội Tổng Quát'
                ],
                'specialty_descriptions' => [
                    'Tim Mạch' => 'Khám và điều trị bệnh tim mạch',
                    'Thần Kinh' => 'Khám và điều trị bệnh thần kinh',
                    'Nhi Khoa' => 'Khám và chăm sóc sức khỏe trẻ em',
                    'Da Liễu' => 'Khám và điều trị bệnh da',
                    'Sản Phụ Khoa' => 'Khám và chăm sóc sức khỏe phụ nữ',
                    'Nội Tổng Quát' => 'Khám và điều trị bệnh nội khoa'
                ]
            ],
            'km' => [
                'thank_you' => 'សូមអរគុណសម្រាប់ការចែករំលែករបស់អ្នក។ ខ្ញុំយល់ថាអ្នកកំពុងមានរោគសញ្ញា:',
                'based_on' => 'ផ្អែកលើរោគសញ្ញារបស់អ្នក ខ្ញុំសូមណែនាំផ្នែក',
                'suggest_doctors' => 'ខ្ញុំសូមណែនាំឱ្យអ្នកពិនិត្យជាមួយវេជ្ជបណ្ឌិតទាំងនេះ:',
                'specialty_label' => 'ផ្នែកឯកទេស',
                'doctors_suggest' => 'វេជ្ជបណ្ឌិតណែនាំ:',
                'experience' => 'បទពិសោធន៍',
                'years' => 'ឆ្នាំ',
                'rating' => 'ការវាយតម្លៃ',
                'fee' => 'តម្លៃពិនិត្យ',
                'view_profile' => 'មើលប្រវត្តិរូប',
                'book_now' => 'ធ្វើការណាត់ជួប',
                'book_appointment' => 'ធ្វើការណាត់ជួប',
                'symptoms' => [
                    'child' => 'រោគសញ្ញានៅក្មេង',
                    'heart' => 'ឈឺទ្រូង/បេះដូង',
                    'head' => 'ឈឺក្បាល',
                    'skin' => 'បញ្ហាស្បែក',
                    'gynecology' => 'បញ្ហាស្ត្រី',
                    'general' => 'រោគសញ្ញាទូទៅ'
                ],
                'specialties' => [
                    'Tim Mạch' => 'ផ្នែកបេះដូង',
                    'Thần Kinh' => 'ផ្នែកសរសៃប្រសាទ',
                    'Nhi Khoa' => 'ផ្នែកកុមារ',
                    'Da Liễu' => 'ផ្នែកស្បែក',
                    'Sản Phụ Khoa' => 'ផ្នែកស្ត្រី',
                    'Nội Tổng Quát' => 'ផ្នែកទូទៅ'
                ],
                'specialty_descriptions' => [
                    'Tim Mạch' => 'ពិនិត្យនិងព្យាបាលជំងឺបេះដូង',
                    'Thần Kinh' => 'ពិនិត្យនិងព្យាបាលជំងឺសរសៃប្រសាទ',
                    'Nhi Khoa' => 'ពិនិត្យនិងព្យាបាលសុខភាពកុមារ',
                    'Da Liễu' => 'ពិនិត្យនិងព្យាបាលជំងឺស្បែក',
                    'Sản Phụ Khoa' => 'ពិនិត្យនិងព្យាបាលសុខភាពស្ត្រី',
                    'Nội Tổng Quát' => 'ពិនិត្យនិងព្យាបាលជំងឺទូទៅ'
                ]
            ]
        ];
        
        $t = $translations[$lang] ?? $translations['vi'];
        
        // Dữ liệu 18 bác sĩ cố định từ TrangChu.php - CHÍNH XÁC 100%
        $doctorsBySpecialty = [
            'Tim Mạch' => [
                ['id' => 1001, 'full_name' => 'BS. Nguyễn Thị Lan', 'experience_years' => 8, 'rating' => 4.8, 'total_reviews' => 156, 'consultation_fee' => 350000],
                ['id' => 1006, 'full_name' => 'BS. Nguyễn Văn Đức', 'experience_years' => 7, 'rating' => 4.5, 'total_reviews' => 92, 'consultation_fee' => 330000],
                ['id' => 1010, 'full_name' => 'BS. Vũ Thị Mai', 'experience_years' => 11, 'rating' => 4.9, 'total_reviews' => 189, 'consultation_fee' => 420000]
            ],
            'Thần Kinh' => [
                ['id' => 1002, 'full_name' => 'BS. Trần Văn Hùng', 'experience_years' => 12, 'rating' => 4.9, 'total_reviews' => 203, 'consultation_fee' => 400000],
                ['id' => 1007, 'full_name' => 'BS. Đỗ Văn Hùng', 'experience_years' => 9, 'rating' => 4.7, 'total_reviews' => 145, 'consultation_fee' => 390000],
                ['id' => 1011, 'full_name' => 'BS. Nguyễn Đức Minh', 'experience_years' => 9, 'rating' => 4.7, 'total_reviews' => 156, 'consultation_fee' => 380000]
            ],
            'Nhi Khoa' => [
                ['id' => 1003, 'full_name' => 'BS. Lê Thị Mai', 'experience_years' => 6, 'rating' => 4.7, 'total_reviews' => 89, 'consultation_fee' => 320000],
                ['id' => 1008, 'full_name' => 'BS. Hoàng Minh Hoàng', 'experience_years' => 5, 'rating' => 4.6, 'total_reviews' => 67, 'consultation_fee' => 310000],
                ['id' => 1013, 'full_name' => 'BS. Phạm Thị Lan', 'experience_years' => 7, 'rating' => 4.8, 'total_reviews' => 134, 'consultation_fee' => 340000]
            ],
            'Da Liễu' => [
                ['id' => 1004, 'full_name' => 'BS. Lê Minh Tuấn', 'experience_years' => 10, 'rating' => 4.6, 'total_reviews' => 134, 'consultation_fee' => 380000],
                ['id' => 1009, 'full_name' => 'BS. Phạm Thị Linh', 'experience_years' => 8, 'rating' => 4.8, 'total_reviews' => 112, 'consultation_fee' => 370000],
                ['id' => 1012, 'full_name' => 'BS. Trần Văn Nam', 'experience_years' => 12, 'rating' => 4.6, 'total_reviews' => 98, 'consultation_fee' => 450000]
            ],
            'Sản Phụ Khoa' => [
                ['id' => 1005, 'full_name' => 'BS. Trần Thị Hương', 'experience_years' => 15, 'rating' => 4.8, 'total_reviews' => 178, 'consultation_fee' => 450000],
                ['id' => 1014, 'full_name' => 'BS. Lê Văn Thành', 'experience_years' => 6, 'rating' => 4.5, 'total_reviews' => 87, 'consultation_fee' => 320000],
                ['id' => 1015, 'full_name' => 'BS. Nguyễn Thị Hạnh', 'experience_years' => 8, 'rating' => 4.7, 'total_reviews' => 145, 'consultation_fee' => 360000]
            ],
            'Nội Tổng Quát' => [
                ['id' => 1016, 'full_name' => 'BS. Nguyễn Văn Hòa', 'experience_years' => 10, 'rating' => 4.6, 'total_reviews' => 123, 'consultation_fee' => 300000],
                ['id' => 1017, 'full_name' => 'BS. Trần Thị Thu', 'experience_years' => 9, 'rating' => 4.8, 'total_reviews' => 167, 'consultation_fee' => 380000],
                ['id' => 1018, 'full_name' => 'BS. Lê Minh Tâm', 'experience_years' => 8, 'rating' => 4.7, 'total_reviews' => 134, 'consultation_fee' => 370000]
            ]
        ];
        
        // Phân tích triệu chứng đơn giản - ƯU TIÊN từ cao đến thấp
        $message_lower = mb_strtolower($message, 'UTF-8');
        
        $suggestedSpecialty = null;
        $mainSymptom = '';
        
        // Mapping từ khóa -> chuyên khoa (ưu tiên từ trên xuống)
        // Hỗ trợ cả tiếng Việt và tiếng Khmer
        
        // 1. NHI KHOA - Ưu tiên cao nhất (từ khóa về trẻ em)
        if (strpos($message_lower, 'con tôi') !== false || 
            strpos($message_lower, 'con bị') !== false || 
            strpos($message_lower, 'trẻ') !== false || 
            strpos($message_lower, 'bé') !== false ||
            strpos($message_lower, 'em bé') !== false ||
            strpos($message_lower, 'កូន') !== false ||  // con (Khmer)
            strpos($message_lower, 'កុមារ') !== false || // trẻ em (Khmer)
            strpos($message_lower, 'ក្មេង') !== false) { // trẻ (Khmer)
            $suggestedSpecialty = 'Nhi Khoa';
            $mainSymptom = $t['symptoms']['child'];
        }
        // 2. TIM MẠCH
        elseif (strpos($message_lower, 'tim') !== false || 
                strpos($message_lower, 'ngực') !== false || 
                strpos($message_lower, 'khó thở') !== false ||
                strpos($message_lower, 'đau ngực') !== false ||
                strpos($message_lower, 'បេះដូង') !== false || // tim (Khmer)
                strpos($message_lower, 'ទ្រូង') !== false ||   // ngực (Khmer)
                strpos($message_lower, 'ដង្ហើម') !== false) {  // thở (Khmer)
            $suggestedSpecialty = 'Tim Mạch';
            $mainSymptom = $t['symptoms']['heart'];
        }
        // 3. THẦN KINH
        elseif (strpos($message_lower, 'đầu') !== false || 
                strpos($message_lower, 'chóng mặt') !== false ||
                strpos($message_lower, 'hoa mắt') !== false ||
                strpos($message_lower, 'ក្បាល') !== false ||  // đầu (Khmer)
                strpos($message_lower, 'វិលមុខ') !== false) { // chóng mặt (Khmer)
            $suggestedSpecialty = 'Thần Kinh';
            $mainSymptom = $t['symptoms']['head'];
        }
        // 4. DA LIỄU
        elseif (strpos($message_lower, 'da') !== false || 
                strpos($message_lower, 'ngứa') !== false || 
                strpos($message_lower, 'mẩn') !== false ||
                strpos($message_lower, 'nổi') !== false ||
                strpos($message_lower, 'ស្បែក') !== false ||  // da (Khmer)
                strpos($message_lower, 'រមាស់') !== false) {  // ngứa (Khmer)
            $suggestedSpecialty = 'Da Liễu';
            $mainSymptom = $t['symptoms']['skin'];
        }
        // 5. SẢN PHỤ KHOA
        elseif (strpos($message_lower, 'thai') !== false || 
                strpos($message_lower, 'kinh') !== false || 
                strpos($message_lower, 'mang thai') !== false ||
                (strpos($message_lower, 'bụng') !== false && strpos($message_lower, 'dưới') !== false) ||
                strpos($message_lower, 'ផ្ទៃពោះ') !== false || // thai (Khmer)
                strpos($message_lower, 'រដូវ') !== false) {      // kinh nguyệt (Khmer)
            $suggestedSpecialty = 'Sản Phụ Khoa';
            $mainSymptom = $t['symptoms']['gynecology'];
        }
        // 6. NỘI TỔNG QUÁT - Mặc định cho các triệu chứng chung
        else {
            $suggestedSpecialty = 'Nội Tổng Quát';
            $mainSymptom = $t['symptoms']['general'];
        }
        
        // Tạo responses với đa ngôn ngữ
        $responses = [];
        $responses[] = $t['thank_you'] . " <strong>{$mainSymptom}</strong>";
        $responses[] = $t['based_on'] . " <strong>" . $t['specialties'][$suggestedSpecialty] . "</strong>.";
        
        // Lấy 3 bác sĩ
        $doctors = $doctorsBySpecialty[$suggestedSpecialty];
        
        // Tạo response về bác sĩ
        $doctorResponse = $t['suggest_doctors'] . "\n\n";
        $doctorResponse .= "<strong>🏥 " . $t['specialty_label'] . " " . $t['specialties'][$suggestedSpecialty] . ":</strong>\n";
        
        foreach ($doctors as $index => $doctor) {
            $doctorResponse .= ($index + 1) . ". <strong>{$doctor['full_name']}</strong>\n";
            $doctorResponse .= "   • " . $t['experience'] . ": {$doctor['experience_years']} " . $t['years'] . "\n";
            $doctorResponse .= "   • " . $t['rating'] . ": ⭐ {$doctor['rating']}/5.0\n";
            $doctorResponse .= "   • " . $t['fee'] . ": " . number_format($doctor['consultation_fee'], 0, ',', '.') . " VNĐ\n";
            $doctorResponse .= "   • <a href='view_doctor.php?doctor_id={$doctor['id']}' target='_blank' style='color: #3b82f6;'>" . $t['view_profile'] . "</a> | ";
            $doctorResponse .= "<a href='DatLichK.php?doctor_id={$doctor['id']}' target='_blank' style='color: #10b981;'>" . $t['book_now'] . "</a>\n\n";
        }
        
        $responses[] = $doctorResponse;
        
        // LƯU TRIỆU CHỨNG VÀO DATABASE
        try {
            require_once 'symptom_analyzer.php';
            $analyzer = new SymptomAnalyzer();
            
            // Tạo analysis result để lưu
            $analysisResult = [
                'user_input' => $message,
                'main_symptom' => $mainSymptom,
                'severity' => 'trung bình', // Mặc định
                'ai_analysis' => implode(' ', $responses),
                'suggested_specialties' => [
                    ['name' => $suggestedSpecialty]
                ],
                'symptom_data' => null // Sẽ tự động tạo "Khác" nếu cần
            ];
            
            // Lưu vào database
            $saved = $analyzer->saveUserSymptom($user_id, $message, $analysisResult);
            
            if ($saved) {
                error_log('[api_symptom_consultation] Successfully saved symptom for user_id: ' . $user_id);
            } else {
                error_log('[api_symptom_consultation] Failed to save symptom for user_id: ' . $user_id);
            }
            
        } catch (Exception $saveError) {
            error_log('[api_symptom_consultation] Error saving symptom: ' . $saveError->getMessage());
            // Không báo lỗi cho user, chỉ log
        }
        
        // Trả về kết quả
        echo json_encode([
            'success' => true,
            'responses' => $responses,
            'suggested_specialties' => [
                ['id' => 1, 'name' => $suggestedSpecialty, 'description' => '']
            ],
            'suggested_doctors' => [
                $suggestedSpecialty => $doctors
            ]
        ]);
        
    } catch (Exception $e) {
        error_log('[api_symptom_consultation_FIXED] Error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

/**
 * Phát hiện chuyên khoa từ văn bản
 */
function detectSpecialtyFromText($text) {
    $text = mb_strtolower($text, 'UTF-8');
    
    $specialtyKeywords = [
        'Tim Mạch' => ['tim', 'ngực', 'huyết áp', 'nhịp tim', 'đau ngực', 'khó thở', 'tim đập', 'huyết áp cao', 'huyết áp thấp'],
        'Thần Kinh' => ['đau đầu', 'chóng mặt', 'tê', 'mất ngủ', 'stress', 'căng thẳng', 'thần kinh', 'não', 'hoa mắt', 'đau nửa đầu'],
        'Nhi Khoa' => ['trẻ em', 'bé', 'con', 'sơ sinh', 'trẻ nhỏ', 'em bé', 'con tôi', 'con bị'],
        'Da Liễu' => ['da', 'mụn', 'ngứa', 'nổi mẩn', 'dị ứng', 'phát ban', 'nấm', 'chàm', 'vảy nến'],
        'Sản Phụ Khoa' => ['kinh nguyệt', 'thai', 'phụ nữ', 'sinh', 'tử cung', 'buồng trứng', 'mang thai', 'rối loạn kinh'],
        'Nội Tổng Quát' => ['sốt', 'ho', 'cảm', 'đau bụng', 'tiêu hóa', 'dạ dày', 'mệt mỏi', 'cảm cúm', 'viêm họng']
    ];
    
    foreach ($specialtyKeywords as $specialty => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return $specialty;
            }
        }
    }
    
    return 'Nội Tổng Quát'; // Mặc định
}

/**
 * Lấy dữ liệu bác sĩ theo chuyên khoa
 */
function getDoctorsBySpecialtyData() {
    return [
        'Tim Mạch' => [
            ['id' => 1001, 'full_name' => 'BS. Nguyễn Thị Lan', 'experience_years' => 8, 'rating' => 4.8, 'total_reviews' => 156, 'consultation_fee' => 350000],
            ['id' => 1006, 'full_name' => 'BS. Nguyễn Văn Đức', 'experience_years' => 7, 'rating' => 4.5, 'total_reviews' => 92, 'consultation_fee' => 330000],
            ['id' => 1010, 'full_name' => 'BS. Vũ Thị Mai', 'experience_years' => 11, 'rating' => 4.9, 'total_reviews' => 189, 'consultation_fee' => 420000]
        ],
        'Thần Kinh' => [
            ['id' => 1002, 'full_name' => 'BS. Trần Văn Hùng', 'experience_years' => 12, 'rating' => 4.9, 'total_reviews' => 203, 'consultation_fee' => 400000],
            ['id' => 1007, 'full_name' => 'BS. Đỗ Văn Hùng', 'experience_years' => 9, 'rating' => 4.7, 'total_reviews' => 145, 'consultation_fee' => 390000],
            ['id' => 1011, 'full_name' => 'BS. Nguyễn Đức Minh', 'experience_years' => 9, 'rating' => 4.7, 'total_reviews' => 156, 'consultation_fee' => 380000]
        ],
        'Nhi Khoa' => [
            ['id' => 1003, 'full_name' => 'BS. Lê Thị Mai', 'experience_years' => 6, 'rating' => 4.7, 'total_reviews' => 89, 'consultation_fee' => 320000],
            ['id' => 1008, 'full_name' => 'BS. Hoàng Minh Hoàng', 'experience_years' => 5, 'rating' => 4.6, 'total_reviews' => 67, 'consultation_fee' => 310000],
            ['id' => 1013, 'full_name' => 'BS. Phạm Thị Lan', 'experience_years' => 7, 'rating' => 4.8, 'total_reviews' => 134, 'consultation_fee' => 340000]
        ],
        'Da Liễu' => [
            ['id' => 1004, 'full_name' => 'BS. Lê Minh Tuấn', 'experience_years' => 10, 'rating' => 4.6, 'total_reviews' => 134, 'consultation_fee' => 380000],
            ['id' => 1009, 'full_name' => 'BS. Phạm Thị Linh', 'experience_years' => 8, 'rating' => 4.8, 'total_reviews' => 112, 'consultation_fee' => 370000],
            ['id' => 1012, 'full_name' => 'BS. Trần Văn Nam', 'experience_years' => 12, 'rating' => 4.6, 'total_reviews' => 98, 'consultation_fee' => 450000]
        ],
        'Sản Phụ Khoa' => [
            ['id' => 1005, 'full_name' => 'BS. Trần Thị Hương', 'experience_years' => 15, 'rating' => 4.8, 'total_reviews' => 178, 'consultation_fee' => 450000],
            ['id' => 1014, 'full_name' => 'BS. Lê Văn Thành', 'experience_years' => 6, 'rating' => 4.5, 'total_reviews' => 87, 'consultation_fee' => 320000],
            ['id' => 1015, 'full_name' => 'BS. Nguyễn Thị Hạnh', 'experience_years' => 8, 'rating' => 4.7, 'total_reviews' => 145, 'consultation_fee' => 360000]
        ],
        'Nội Tổng Quát' => [
            ['id' => 1016, 'full_name' => 'BS. Nguyễn Văn Hòa', 'experience_years' => 10, 'rating' => 4.6, 'total_reviews' => 123, 'consultation_fee' => 300000],
            ['id' => 1017, 'full_name' => 'BS. Trần Thị Thu', 'experience_years' => 9, 'rating' => 4.8, 'total_reviews' => 167, 'consultation_fee' => 380000],
            ['id' => 1018, 'full_name' => 'BS. Lê Minh Tâm', 'experience_years' => 8, 'rating' => 4.7, 'total_reviews' => 134, 'consultation_fee' => 370000]
        ]
    ];
}
?>

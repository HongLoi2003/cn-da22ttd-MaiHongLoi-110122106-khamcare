<?php
require_once 'db_config.php';

/**
 * Hệ thống phân tích triệu chứng thông minh
 * Nhận diện các cách diễn đạt khác nhau của cùng một triệu chứng
 */
class SymptomAnalyzer {
    private $pdo;
    
    // Từ điển đồng nghĩa cho các triệu chứng
    private $synonyms = [
        'đau đầu' => ['đau đầu', 'nhức đầu', 'đau cái đầu', 'nhức cái đầu', 'đau đầu quá', 'nhức đầu nè', 'đau ở đầu', 'nhức ở đầu', 'đau vùng đầu'],
        'sốt' => ['sốt', 'nóng người', 'bị sốt', 'sốt cao', 'sốt nhẹ', 'nóng sốt', 'cơ thể nóng'],
        'ho' => ['ho', 'bị ho', 'ho khan', 'ho có đờm', 'ho nhiều', 'ho liên tục', 'ho mãi'],
        'đau bụng' => ['đau bụng', 'đau ở bụng', 'đau vùng bụng', 'bụng đau', 'đau quặn bụng', 'đau bụng quá'],
        'mệt mỏi' => ['mệt', 'mệt mỏi', 'mệt lả', 'uể oải', 'mệt người', 'cảm thấy mệt', 'mệt quá'],
        'đau ngực' => ['đau ngực', 'đau ở ngực', 'đau vùng ngực', 'ngực đau', 'tức ngực', 'đau ngực quá'],
        'khó thở' => ['khó thở', 'thở khó', 'thở không được', 'hụt hơi', 'thở gấp', 'khó thở quá'],
        'nôn mửa' => ['nôn', 'mửa', 'nôn mửa', 'buồn nôn', 'muốn nôn', 'ói mửa'],
        'chóng mặt' => ['chóng mặt', 'hoa mắt', 'choáng váng', 'đầu quay', 'mất thăng bằng'],
        'đau họng' => ['đau họng', 'đau cổ họng', 'họng đau', 'đau ở họng', 'đau khi nuốt'],
        'tiêu chảy' => ['tiêu chảy', 'đi ngoài', 'đi lỏng', 'phân lỏng', 'bị tiêu chảy'],
        'táo bón' => ['táo bón', 'khó đi ngoài', 'bí đại tiện', 'không đi được'],
        'đau lưng' => ['đau lưng', 'đau ở lưng', 'lưng đau', 'đau vùng lưng', 'nhức lưng'],
        'đau khớp' => ['đau khớp', 'đau xương khớp', 'khớp đau', 'đau các khớp'],
        'mất ngủ' => ['mất ngủ', 'không ngủ được', 'khó ngủ', 'ngủ không được', 'thức đêm'],
    ];
    
    // Từ khóa liên quan đến mức độ nghiêm trọng
    private $severityKeywords = [
        'nặng' => ['nặng', 'dữ dội', 'quá', 'lắm', 'nhiều', 'mãi', 'liên tục', 'không ngừng'],
        'trung bình' => ['hơi', 'khá', 'vừa phải', 'thỉnh thoảng'],
        'nhẹ' => ['nhẹ', 'chút', 'ít', 'một chút']
    ];
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Chuẩn hóa text đầu vào
     */
    private function normalizeText($text) {
        // Chuyển về chữ thường
        $text = mb_strtolower($text, 'UTF-8');
        
        // Loại bỏ dấu câu thừa
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // Loại bỏ khoảng trắng thừa
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Tìm triệu chứng chính từ text đầu vào
     */
    public function findMainSymptom($userInput) {
        $normalized = $this->normalizeText($userInput);
        
        // Tìm kiếm trong từ điển đồng nghĩa
        foreach ($this->synonyms as $mainSymptom => $variations) {
            foreach ($variations as $variation) {
                if (strpos($normalized, $variation) !== false) {
                    return $mainSymptom;
                }
            }
        }
        
        // Nếu không tìm thấy trong từ điển, trả về text gốc
        return $normalized;
    }
    
    /**
     * Phân tích mức độ nghiêm trọng
     */
    public function analyzeSeverity($userInput) {
        $normalized = $this->normalizeText($userInput);
        
        foreach ($this->severityKeywords as $level => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($normalized, $keyword) !== false) {
                    return $level;
                }
            }
        }
        
        return 'trung bình'; // Mặc định
    }
    
    /**
     * Tìm kiếm triệu chứng trong database
     */
    public function searchSymptomInDatabase($symptomText) {
        try {
            if (!$this->pdo instanceof PDO) {
                return null;
            }
            
            // Tìm triệu chứng chính
            $mainSymptom = $this->findMainSymptom($symptomText);
            
            // Tìm trong database với LIKE
            $stmt = $this->pdo->prepare("
                SELECT * FROM symptoms 
                WHERE symptom_name LIKE ? 
                   OR description LIKE ?
                   OR common_causes LIKE ?
                LIMIT 1
            ");
            
            $searchTerm = "%{$mainSymptom}%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('[SymptomAnalyzer] Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Phân tích triệu chứng và đề xuất chuyên khoa
     */
    public function analyzeSymptom($userInput) {
        $mainSymptom = $this->findMainSymptom($userInput);
        $severity = $this->analyzeSeverity($userInput);
        $symptomData = $this->searchSymptomInDatabase($userInput);
        
        $result = [
            'user_input' => $userInput,
            'main_symptom' => $mainSymptom,
            'severity' => $severity,
            'found_in_db' => $symptomData !== null,
            'symptom_data' => $symptomData,
            'suggested_specialties' => [],
            'ai_analysis' => ''
        ];
        
        if ($symptomData) {
            // Lấy chuyên khoa liên quan
            $result['suggested_specialties'] = $this->getRelatedSpecialties($symptomData);
            
            // Tạo phân tích AI
            $result['ai_analysis'] = $this->generateAIAnalysis($mainSymptom, $severity, $symptomData);
        } else {
            // Nếu không tìm thấy trong DB, đề xuất chuyên khoa dựa trên từ khóa
            $result['suggested_specialties'] = $this->suggestSpecialtiesByKeyword($mainSymptom);
            $result['ai_analysis'] = "Triệu chứng '{$mainSymptom}' cần được bác sĩ khám để chẩn đoán chính xác.";
        }
        
        return $result;
    }
    
    /**
     * Lấy danh sách chuyên khoa liên quan
     */
    private function getRelatedSpecialties($symptomData) {
        $specialties = [];
        
        if (!empty($symptomData['related_specialties'])) {
            // Parse JSON hoặc string
            $relatedStr = $symptomData['related_specialties'];
            $specialtyNames = explode(',', $relatedStr);
            
            foreach ($specialtyNames as $name) {
                $name = trim($name);
                if (!empty($name)) {
                    // Tìm specialty trong database
                    try {
                        $stmt = $this->pdo->prepare("SELECT id, name, description FROM specialties WHERE name LIKE ? LIMIT 1");
                        $stmt->execute(["%{$name}%"]);
                        $specialty = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($specialty) {
                            $specialties[] = $specialty;
                        } else {
                            error_log('[SymptomAnalyzer] Specialty not found in DB: ' . $name);
                        }
                    } catch (Exception $e) {
                        error_log('[SymptomAnalyzer] Error getting specialty: ' . $e->getMessage());
                    }
                }
            }
        }
        
        return $specialties;
    }
    
    /**
     * Đề xuất chuyên khoa dựa trên từ khóa
     * Trả về trực tiếp mà không cần query database
     */
    private function suggestSpecialtiesByKeyword($symptom) {
        // Danh sách 6 chuyên khoa chính có trong hệ thống
        $availableSpecialties = [
            'Tim Mạch' => ['id' => 1, 'name' => 'Tim Mạch', 'description' => 'Chuyên về tim, mạch máu và huyết áp'],
            'Thần Kinh' => ['id' => 2, 'name' => 'Thần Kinh', 'description' => 'Chuyên về não bộ và hệ thần kinh'],
            'Sản Phụ Khoa' => ['id' => 3, 'name' => 'Sản Phụ Khoa', 'description' => 'Chuyên về sức khỏe phụ nữ và thai sản'],
            'Da Liễu' => ['id' => 4, 'name' => 'Da Liễu', 'description' => 'Chuyên về da và các bệnh ngoài da'],
            'Nhi Khoa' => ['id' => 5, 'name' => 'Nhi Khoa', 'description' => 'Chuyên về sức khỏe trẻ em'],
            'Nội Tổng Quát' => ['id' => 6, 'name' => 'Nội Tổng Quát', 'description' => 'Khám và điều trị bệnh nội khoa']
        ];
        
        $keywordMap = [
            'đầu' => ['Thần Kinh', 'Nội Tổng Quát'],
            'chóng mặt' => ['Thần Kinh', 'Nội Tổng Quát'],
            'tim' => ['Tim Mạch'],
            'ngực' => ['Tim Mạch'],
            'khó thở' => ['Tim Mạch'],
            'bụng' => ['Nội Tổng Quát', 'Sản Phụ Khoa'],
            'da' => ['Da Liễu'],
            'ngứa' => ['Da Liễu'],
            'mẩn' => ['Da Liễu'],
            'nổi' => ['Da Liễu'],
            'con' => ['Nhi Khoa'],
            'trẻ' => ['Nhi Khoa'],
            'bé' => ['Nhi Khoa'],
            'ho' => ['Nhi Khoa', 'Nội Tổng Quát'],
            'sốt' => ['Nội Tổng Quát', 'Nhi Khoa'],
            'mệt' => ['Nội Tổng Quát'],
            'thai' => ['Sản Phụ Khoa'],
            'kinh' => ['Sản Phụ Khoa'],
            'phụ nữ' => ['Sản Phụ Khoa'],
        ];
        
        $specialties = [];
        $foundNames = [];
        
        // Tìm kiếm theo từ khóa
        foreach ($keywordMap as $keyword => $specialtyNames) {
            if (stripos($symptom, $keyword) !== false) {
                foreach ($specialtyNames as $name) {
                    if (isset($availableSpecialties[$name]) && !in_array($name, $foundNames)) {
                        $specialties[] = $availableSpecialties[$name];
                        $foundNames[] = $name;
                    }
                }
            }
        }
        
        // Nếu không tìm thấy, đề xuất Nội Tổng Quát
        if (empty($specialties)) {
            $specialties[] = $availableSpecialties['Nội Tổng Quát'];
        }
        
        return $specialties;
    }
    
    /**
     * Tạo phân tích AI
     */
    private function generateAIAnalysis($symptom, $severity, $symptomData) {
        $severityText = [
            'nhẹ' => 'nhẹ',
            'trung bình' => 'ở mức trung bình',
            'nặng' => 'nghiêm trọng'
        ];
        
        $analysis = "Bạn đang gặp triệu chứng '{$symptom}' {$severityText[$severity]}. ";
        
        if ($symptomData) {
            if (!empty($symptomData['description'])) {
                $analysis .= $symptomData['description'] . " ";
            }
            
            if (!empty($symptomData['common_causes'])) {
                $analysis .= "Nguyên nhân thường gặp: " . $symptomData['common_causes'] . " ";
            }
            
            if ($severity === 'nặng') {
                $analysis .= "Do triệu chứng nghiêm trọng, bạn nên đặt lịch khám ngay để được bác sĩ thăm khám và tư vấn kịp thời.";
            } else {
                $analysis .= "Bạn nên đặt lịch khám với bác sĩ chuyên khoa để được tư vấn và điều trị phù hợp.";
            }
        }
        
        return $analysis;
    }
    
    /**
     * Lưu triệu chứng của người dùng vào database
     */
    public function saveUserSymptom($userId, $symptomText, $analysisResult) {
        try {
            if (!$this->pdo instanceof PDO) {
                return false;
            }
            
            $symptomId = null;
            if (isset($analysisResult['symptom_data']) && $analysisResult['symptom_data']) {
                $symptomId = $analysisResult['symptom_data']['id'];
            }
            
            // Nếu không tìm thấy symptom_id, tìm triệu chứng phù hợp
            if ($symptomId === null) {
                error_log('[SymptomAnalyzer] Symptom not found, searching for matching symptom');
                
                // Tìm triệu chứng phù hợp dựa trên từ khóa - mapping đầy đủ 10 triệu chứng
                $symptomText_lower = mb_strtolower($symptomText, 'UTF-8');
                $symptomMapping = [
                    // ID 1: Đau đầu
                    'đau đầu' => 1, 'nhức đầu' => 1, 'đau nửa đầu' => 1, 'migraine' => 1,
                    // ID 2: Đau ngực  
                    'đau ngực' => 2, 'tức ngực' => 2, 'khó thở' => 2, 'ngực' => 2,
                    // ID 3: Sốt
                    'sốt' => 3, 'nóng người' => 3, 'ớn lạnh' => 3, 'cảm' => 3, 'cúm' => 3,
                    // ID 4: Ho
                    'ho' => 4, 'ho khan' => 4, 'ho có đờm' => 4, 'đờm' => 4,
                    // ID 5: Đau bụng
                    'đau bụng' => 5, 'bụng đau' => 5, 'đau dạ dày' => 5, 'dạ dày' => 5, 'tiêu hóa' => 5,
                    // ID 6: Mệt mỏi
                    'mệt' => 6, 'mệt mỏi' => 6, 'uể oải' => 6, 'thiếu năng lượng' => 6, 'kiệt sức' => 6,
                    // ID 7: Chóng mặt
                    'chóng mặt' => 7, 'hoa mắt' => 7, 'mất thăng bằng' => 7, 'choáng' => 7,
                    // ID 8: Đau lưng
                    'đau lưng' => 8, 'lưng đau' => 8, 'đau cột sống' => 8, 'cột sống' => 8,
                    // ID 9: Mất ngủ
                    'mất ngủ' => 9, 'khó ngủ' => 9, 'ngủ không được' => 9, 'thức đêm' => 9, 'insomnia' => 9,
                    // ID 10: Dị ứng da
                    'dị ứng' => 10, 'ngứa' => 10, 'nổi mẩn' => 10, 'phát ban' => 10, 'da' => 10, 'mụn' => 10
                ];
                
                // Tìm triệu chứng phù hợp
                foreach ($symptomMapping as $keyword => $id) {
                    if (strpos($symptomText_lower, $keyword) !== false) {
                        $symptomId = $id;
                        error_log('[SymptomAnalyzer] Found matching symptom ID: ' . $symptomId . ' for keyword: ' . $keyword);
                        break;
                    }
                }
                
                // Nếu vẫn không tìm thấy, dùng ID 6 (Mệt mỏi) làm mặc định cho triệu chứng chung
                if ($symptomId === null) {
                    $symptomId = 6; // Mệt mỏi - triệu chứng chung nhất
                    error_log('[SymptomAnalyzer] Using default symptom ID: 6 (Mệt mỏi)');
                }
            }
            
            // Chuyển đổi mức độ sang enum tiếng Việt
            $mucDoMap = [
                'nhẹ' => 'nhe',
                'trung bình' => 'trung_binh',
                'nặng' => 'nang'
            ];
            $mucDo = $mucDoMap[$analysisResult['severity']] ?? 'trung_binh';
            
            // Tạo ghi chú từ phân tích AI và chuyên khoa đề xuất
            $suggestedSpecialties = [];
            if (isset($analysisResult['suggested_specialties']) && is_array($analysisResult['suggested_specialties'])) {
                foreach ($analysisResult['suggested_specialties'] as $specialty) {
                    if (isset($specialty['name'])) {
                        $suggestedSpecialties[] = $specialty['name'];
                    }
                }
            }
            $ghiChu = "Triệu chứng: " . $symptomText . "\n";
            $ghiChu .= "Chuyên khoa đề xuất: " . implode(', ', $suggestedSpecialties);
            
            // Lấy tên người dùng từ bảng nguoi_dung
            $tenNguoiDung = 'Người dùng #' . $userId;
            try {
                $stmt = $this->pdo->prepare("SELECT ho_ten FROM nguoi_dung WHERE id = ? LIMIT 1");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && !empty($user['ho_ten'])) {
                    $tenNguoiDung = $user['ho_ten'];
                }
            } catch (Exception $e) {
                error_log('[SymptomAnalyzer] Error getting user name: ' . $e->getMessage());
            }
            
            // Lưu vào bảng trieu_chung_nguoi_dung (bao gồm tên người dùng)
            $stmt = $this->pdo->prepare("
                INSERT INTO trieu_chung_nguoi_dung 
                (nguoi_dung_id, ten_nguoi_dung, trieu_chung_id, muc_do, ghi_chu, ngay_tao) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $userId,
                $tenNguoiDung,
                $symptomId,
                $mucDo,
                $ghiChu
            ]);
            
            if ($result) {
                error_log('[SymptomAnalyzer] Successfully saved user symptom to trieu_chung_nguoi_dung for user_id: ' . $userId);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('[SymptomAnalyzer] Error saving user symptom: ' . $e->getMessage());
            return false;
        }
    }
}

// API endpoint để xử lý AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $analyzer = new SymptomAnalyzer();
    
    switch ($_POST['action']) {
        case 'analyze_symptom':
            $symptomText = trim($_POST['symptom_text'] ?? '');
            
            if (empty($symptomText)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập triệu chứng']);
                exit;
            }
            
            $result = $analyzer->analyzeSymptom($symptomText);
            
            // Lưu vào database nếu user đã đăng nhập
            if (isset($_SESSION['user_id'])) {
                $analyzer->saveUserSymptom($_SESSION['user_id'], $symptomText, $result);
            }
            
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    exit;
}
?>
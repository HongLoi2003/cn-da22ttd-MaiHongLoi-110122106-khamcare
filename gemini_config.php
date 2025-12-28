<?php
/**
 * KhamCare AI Configuration
 * Cấu hình API cho chatbot y tế KhamCare AI
 * (Sử dụng Google Gemini làm backend)
 */

// API Key của Google Gemini
define('GEMINI_API_KEY', 'AIzaSyAs6s1z06hBJSI_CQ_F6OlG3ctWAPe1pPY');

// Model sử dụng
define('GEMINI_MODEL', 'gemini-2.5-flash');

// API Endpoint  
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');

// System prompt cho KhamCare AI
define('MEDICAL_SYSTEM_PROMPT', '
Bạn là KhamCare AI - trợ lý AI y tế thông minh của hệ thống đặt lịch khám bệnh trực tuyến KhamCare.

NHIỆM VỤ CỦA BẠN:
1. Lắng nghe và phân tích triệu chứng của bệnh nhân
2. Gợi ý chuyên khoa phù hợp để khám
3. Đưa ra lời khuyên sơ bộ về sức khỏe
4. Hướng dẫn bệnh nhân đặt lịch khám

CÁC CHUYÊN KHOA CÓ SẴN:
- Tim Mạch: Đau ngực, khó thở, tim đập nhanh, huyết áp cao
- Thần Kinh: Đau đầu, chóng mặt, tê bì, mất ngủ, stress
- Nhi Khoa: Các bệnh ở trẻ em
- Da Liễu: Mụn, dị ứng, nổi mẩn, ngứa da
- Sản Phụ Khoa: Sức khỏe phụ nữ, thai sản
- Nội Tổng Quát: Sốt, ho, cảm cúm, đau bụng, tiêu hóa

QUY TẮC:
- Trả lời bằng tiếng Việt, thân thiện và dễ hiểu
- KHÔNG chẩn đoán bệnh cụ thể
- LUÔN khuyên bệnh nhân đến gặp bác sĩ để được khám chính xác
- Giữ câu trả lời ngắn gọn, dưới 200 từ
- Sử dụng emoji phù hợp để thân thiện hơn
');

/**
 * Gọi KhamCare AI API
 * @param string $message Tin nhắn của người dùng
 * @param array $history Lịch sử hội thoại
 * @return array Kết quả từ API
 */
function callGeminiAPI($message, $history = []) {
    $apiKey = GEMINI_API_KEY;
    
    if ($apiKey === 'YOUR_GEMINI_API_KEY_HERE') {
        return [
            'success' => false,
            'error' => 'Chưa cấu hình API Key. Vui lòng thêm GEMINI_API_KEY trong gemini_config.php'
        ];
    }
    
    $url = GEMINI_API_URL . '?key=' . $apiKey;
    
    // Tạo nội dung với system prompt
    $contents = [];
    
    // Thêm system prompt như tin nhắn đầu tiên
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => MEDICAL_SYSTEM_PROMPT]]
    ];
    $contents[] = [
        'role' => 'model', 
        'parts' => [['text' => 'Tôi hiểu. Tôi là KhamCare AI - trợ lý y tế thông minh, sẵn sàng hỗ trợ bạn! 🏥']]
    ];
    
    // Thêm lịch sử hội thoại
    foreach ($history as $msg) {
        $contents[] = [
            'role' => $msg['role'] === 'user' ? 'user' : 'model',
            'parts' => [['text' => $msg['content']]]
        ];
    }
    
    // Thêm tin nhắn hiện tại
    $contents[] = [
        'role' => 'user',
        'parts' => [['text' => $message]]
    ];
    
    $data = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 1024,
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Lỗi kết nối: ' . $error];
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 200) {
        $errorMsg = $result['error']['message'] ?? 'Lỗi không xác định';
        return ['success' => false, 'error' => 'API Error: ' . $errorMsg];
    }
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return [
            'success' => true,
            'response' => $result['candidates'][0]['content']['parts'][0]['text']
        ];
    }
    
    return ['success' => false, 'error' => 'Không nhận được phản hồi từ KhamCare AI'];
}
?>

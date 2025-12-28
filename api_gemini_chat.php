<?php
/**
 * API Endpoint cho KhamCare AI Chatbot
 * Xử lý tin nhắn từ frontend và trả về phản hồi từ AI
 */

// Tắt output errors
error_reporting(0);
ini_set('display_errors', 0);
if (ob_get_level()) ob_end_clean();

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once 'gemini_config.php';

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$history = $input['history'] ?? [];

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Vui lòng nhập tin nhắn']);
    exit;
}

// Gọi KhamCare AI API
$result = callGeminiAPI($message, $history);

if ($result['success']) {
    // Phân tích phản hồi để gợi ý chuyên khoa
    $response = $result['response'];
    $suggestedSpecialty = detectSpecialty($response . ' ' . $message);
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'suggested_specialty' => $suggestedSpecialty,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} else {
    // Fallback response khi API lỗi
    $fallbackResponse = getFallbackResponse($message);
    echo json_encode([
        'success' => true,
        'response' => $fallbackResponse['response'],
        'suggested_specialty' => $fallbackResponse['specialty'],
        'is_fallback' => true,
        'error_detail' => $result['error']
    ]);
}

/**
 * Phát hiện chuyên khoa từ nội dung
 */
function detectSpecialty($text) {
    $text = mb_strtolower($text, 'UTF-8');
    
    $specialtyKeywords = [
        'Tim Mạch' => ['tim', 'ngực', 'huyết áp', 'nhịp tim', 'đau ngực', 'khó thở', 'tim đập'],
        'Thần Kinh' => ['đau đầu', 'chóng mặt', 'tê', 'mất ngủ', 'stress', 'căng thẳng', 'thần kinh', 'não'],
        'Nhi Khoa' => ['trẻ em', 'bé', 'con', 'sơ sinh', 'trẻ nhỏ', 'em bé'],
        'Da Liễu' => ['da', 'mụn', 'ngứa', 'nổi mẩn', 'dị ứng', 'phát ban', 'nấm'],
        'Sản Phụ Khoa' => ['kinh nguyệt', 'thai', 'phụ nữ', 'sinh', 'tử cung', 'buồng trứng'],
        'Nội Tổng Quát' => ['sốt', 'ho', 'cảm', 'đau bụng', 'tiêu hóa', 'dạ dày', 'mệt mỏi']
    ];
    
    foreach ($specialtyKeywords as $specialty => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return $specialty;
            }
        }
    }
    
    return null;
}

/**
 * Phản hồi dự phòng khi API lỗi
 */
function getFallbackResponse($message) {
    $message = mb_strtolower($message, 'UTF-8');
    
    // Phân tích triệu chứng cơ bản
    if (strpos($message, 'đau đầu') !== false || strpos($message, 'chóng mặt') !== false) {
        return [
            'response' => '🧠 Tôi hiểu bạn đang gặp vấn đề về đau đầu/chóng mặt. Đây có thể liên quan đến thần kinh hoặc căng thẳng. Tôi khuyên bạn nên đặt lịch khám với bác sĩ chuyên khoa Thần Kinh để được tư vấn chính xác nhé!',
            'specialty' => 'Thần Kinh'
        ];
    }
    
    if (strpos($message, 'đau ngực') !== false || strpos($message, 'tim') !== false) {
        return [
            'response' => '❤️ Triệu chứng đau ngực hoặc liên quan đến tim cần được khám ngay. Tôi khuyên bạn nên đặt lịch với bác sĩ chuyên khoa Tim Mạch sớm nhất có thể!',
            'specialty' => 'Tim Mạch'
        ];
    }
    
    if (strpos($message, 'da') !== false || strpos($message, 'mụn') !== false || strpos($message, 'ngứa') !== false) {
        return [
            'response' => '🩹 Vấn đề về da cần được bác sĩ Da Liễu khám trực tiếp để chẩn đoán chính xác. Bạn có thể đặt lịch khám ngay trên KhamCare!',
            'specialty' => 'Da Liễu'
        ];
    }
    
    if (strpos($message, 'sốt') !== false || strpos($message, 'ho') !== false || strpos($message, 'cảm') !== false) {
        return [
            'response' => '🤒 Triệu chứng sốt, ho, cảm cúm thường gặp. Bạn nên nghỉ ngơi, uống nhiều nước. Nếu triệu chứng kéo dài, hãy đặt lịch khám Nội Tổng Quát nhé!',
            'specialty' => 'Nội Tổng Quát'
        ];
    }
    
    // Phản hồi mặc định
    return [
        'response' => '👋 Xin chào! Tôi là trợ lý AI y tế của KhamCare. Bạn có thể mô tả triệu chứng cụ thể hơn để tôi gợi ý chuyên khoa phù hợp nhé! Ví dụ: đau đầu, đau bụng, ho, sốt...',
        'specialty' => null
    ];
}
?>

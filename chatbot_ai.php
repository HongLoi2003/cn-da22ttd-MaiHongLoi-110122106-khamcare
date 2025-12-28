<?php
session_start();
require_once 'db_config.php';

// Lấy thông tin user nếu đã đăng nhập
$user = null;
$userName = 'Khách';
if (isset($_SESSION['user_id'])) {
    $user = getUserById($_SESSION['user_id']);
    $userName = $user['full_name'] ?? 'Bạn';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trợ lý AI Y tế - KhamCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 50%, #06b6d4 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: #0891b2;
            font-size: 2.2rem;
            font-weight: 800;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            0% { background-position: 0% center; }
            100% { background-position: 200% center; }
        }

        .logo:hover {
            transform: scale(1.02);
        }

        .logo img {
            width: 65px;
            height: 65px;
            border-radius: 18px;
            object-fit: contain;
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
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        @keyframes logoPulse {
            0%, 100% { filter: drop-shadow(0 4px 12px rgba(6, 182, 212, 0.3)); }
            50% { filter: drop-shadow(0 6px 20px rgba(6, 182, 212, 0.5)); }
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-back {
            background: #f1f5f9;
            color: #475569;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #e2e8f0;
        }

        /* Chat Container */
        .chat-container {
            flex: 1;
            max-width: 900px;
            width: 100%;
            margin: 20px auto;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
        }

        .chat-box {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Chat Header */
        .chat-header {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .ai-avatar {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .chat-header-info h2 {
            font-size: 1.2rem;
            margin-bottom: 4px;
        }

        .chat-header-info p {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            margin-left: auto;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Messages */
        .messages {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background: #f8fafc;
        }

        .message {
            display: flex;
            gap: 12px;
            max-width: 85%;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .message.ai .message-avatar {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
        }

        .message.user .message-avatar {
            background: linear-gradient(135deg, #8b5cf6, #a78bfa);
            color: white;
        }

        .message-content {
            background: white;
            padding: 14px 18px;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            line-height: 1.6;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            color: white;
        }

        .message-time {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 6px;
        }

        .message.user .message-time {
            text-align: right;
            color: rgba(255,255,255,0.7);
        }

        /* Typing indicator */
        .typing-indicator {
            display: none;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
        }

        .typing-indicator.show {
            display: flex;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dots span {
            width: 8px;
            height: 8px;
            background: #0891b2;
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes typing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        /* Specialty Suggestion */
        .specialty-suggestion {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 16px 20px;
            border-radius: 16px;
            margin-top: 12px;
            display: none;
        }

        .specialty-suggestion.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .specialty-suggestion h4 {
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .specialty-suggestion .btn-book {
            background: white;
            color: #059669;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .specialty-suggestion .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Input Area */
        .input-area {
            padding: 20px 24px;
            background: white;
            border-top: 1px solid #e2e8f0;
        }

        .quick-suggestions {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .quick-btn {
            background: #f1f5f9;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #475569;
            cursor: pointer;
            transition: all 0.3s;
        }

        .quick-btn:hover {
            background: #0891b2;
            color: white;
        }

        .input-wrapper {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .input-wrapper input {
            flex: 1;
            padding: 14px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 30px;
            font-size: 1rem;
            outline: none;
            transition: all 0.3s;
        }

        .input-wrapper input:focus {
            border-color: #0891b2;
            box-shadow: 0 0 0 4px rgba(8, 145, 178, 0.1);
        }

        .btn-send {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #0891b2, #06b6d4);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-send:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 20px rgba(8, 145, 178, 0.4);
        }

        .btn-send:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }

            .chat-container {
                margin: 10px;
                padding: 0;
            }

            .messages {
                padding: 16px;
            }

            .message {
                max-width: 90%;
            }

            .quick-suggestions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <a href="TrangChu.php" class="logo">
            <img src="image/logo.png.png" alt="Logo">
            <span>KhamCare</span>
        </a>
        <div class="header-actions">
            <a href="TrangChu.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Trang chủ
            </a>
        </div>
    </div>

    <!-- Chat Container -->
    <div class="chat-container">
        <div class="chat-box">
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="ai-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chat-header-info">
                    <h2>KhamCare AI</h2>
                    <p>Trợ lý AI Y tế thông minh</p>
                </div>
                <div class="status-dot"></div>
            </div>

            <!-- Messages -->
            <div class="messages" id="messages">
                <!-- Welcome message -->
                <div class="message ai">
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <div class="message-content">
                            Xin chào <strong><?php echo htmlspecialchars($userName); ?></strong>! 👋<br><br>
                            Tôi là trợ lý AI y tế của KhamCare. Tôi có thể giúp bạn:<br>
                            • Phân tích triệu chứng và gợi ý chuyên khoa phù hợp<br>
                            • Tư vấn sơ bộ về sức khỏe<br>
                            • Hướng dẫn đặt lịch khám<br><br>
                            Hãy mô tả triệu chứng của bạn để tôi hỗ trợ nhé! 🏥
                        </div>
                        <div class="message-time">Bây giờ</div>
                    </div>
                </div>

                <!-- Typing indicator -->
                <div class="typing-indicator" id="typingIndicator">
                    <div class="message-avatar" style="background: linear-gradient(135deg, #0891b2, #06b6d4); color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>

            <!-- Specialty Suggestion -->
            <div class="specialty-suggestion" id="specialtySuggestion">
                <h4><i class="fas fa-stethoscope"></i> Gợi ý chuyên khoa</h4>
                <p id="specialtyText">Dựa trên triệu chứng, bạn nên khám chuyên khoa: <strong id="specialtyName"></strong></p>
                <a href="#" class="btn-book" id="bookBtn">
                    <i class="fas fa-calendar-plus"></i> Đặt lịch khám ngay
                </a>
            </div>

            <!-- Input Area -->
            <div class="input-area">
                <div class="quick-suggestions">
                    <button class="quick-btn" onclick="sendQuickMessage('Tôi bị đau đầu')">🤕 Đau đầu</button>
                    <button class="quick-btn" onclick="sendQuickMessage('Tôi bị ho và sốt')">🤒 Ho, sốt</button>
                    <button class="quick-btn" onclick="sendQuickMessage('Tôi bị đau bụng')">😣 Đau bụng</button>
                    <button class="quick-btn" onclick="sendQuickMessage('Tôi bị mất ngủ')">😴 Mất ngủ</button>
                    <button class="quick-btn" onclick="sendQuickMessage('Tôi bị dị ứng da')">🩹 Dị ứng da</button>
                </div>
                <div class="input-wrapper">
                    <input type="text" id="messageInput" placeholder="Nhập triệu chứng của bạn..." onkeypress="handleKeyPress(event)">
                    <button class="btn-send" id="sendBtn" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let chatHistory = [];
        const messagesContainer = document.getElementById('messages');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const typingIndicator = document.getElementById('typingIndicator');
        const specialtySuggestion = document.getElementById('specialtySuggestion');

        function handleKeyPress(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        }

        function sendQuickMessage(text) {
            messageInput.value = text;
            sendMessage();
        }

        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            // Disable input
            messageInput.disabled = true;
            sendBtn.disabled = true;

            // Add user message
            addMessage('user', message);
            messageInput.value = '';

            // Show typing indicator
            typingIndicator.classList.add('show');
            scrollToBottom();

            try {
                const response = await fetch('api_gemini_chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        history: chatHistory
                    })
                });

                const data = await response.json();

                // Hide typing indicator
                typingIndicator.classList.remove('show');

                if (data.success) {
                    // Add AI response
                    addMessage('ai', data.response);

                    // Update history
                    chatHistory.push({ role: 'user', content: message });
                    chatHistory.push({ role: 'assistant', content: data.response });

                    // Show specialty suggestion if available
                    if (data.suggested_specialty) {
                        showSpecialtySuggestion(data.suggested_specialty);
                    }
                } else {
                    addMessage('ai', 'Xin lỗi, đã có lỗi xảy ra. Vui lòng thử lại! 😅');
                }
            } catch (error) {
                typingIndicator.classList.remove('show');
                addMessage('ai', 'Không thể kết nối đến server. Vui lòng kiểm tra kết nối mạng! 🔌');
            }

            // Re-enable input
            messageInput.disabled = false;
            sendBtn.disabled = false;
            messageInput.focus();
        }

        function addMessage(type, content) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;

            const time = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

            messageDiv.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-${type === 'ai' ? 'robot' : 'user'}"></i>
                </div>
                <div>
                    <div class="message-content">${formatMessage(content)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;

            // Insert before typing indicator
            messagesContainer.insertBefore(messageDiv, typingIndicator);
            scrollToBottom();
        }

        function formatMessage(text) {
            // Convert line breaks
            return text.replace(/\n/g, '<br>');
        }

        function showSpecialtySuggestion(specialty) {
            const specialtyName = document.getElementById('specialtyName');
            const bookBtn = document.getElementById('bookBtn');

            specialtyName.textContent = specialty;
            bookBtn.href = `TimKiemBS.php?specialty=${encodeURIComponent(specialty)}`;

            specialtySuggestion.classList.add('show');
        }

        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Focus input on load
        messageInput.focus();
    </script>
</body>
</html>

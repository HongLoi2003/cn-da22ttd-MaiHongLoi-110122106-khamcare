<?php
/**
 * Chatbot AI Widget - Có thể kéo thả
 * Include file này vào bất kỳ trang nào để hiển thị chatbot
 */

// Kiểm tra đã login chưa
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn && isset($user['full_name']) ? htmlspecialchars($user['full_name']) : 'Bạn';
?>

<!-- Chatbot Widget Styles -->
<style>
/* Chatbot Floating Button */
.chatbot-toggle {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 65px;
    height: 65px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    cursor: pointer;
    box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4);
    z-index: 99998;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    transition: all 0.3s ease;
    animation: chatbotPulse 2s infinite;
}

.chatbot-toggle:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.5);
}

.chatbot-toggle.active {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    animation: none;
}

@keyframes chatbotPulse {
    0%, 100% { box-shadow: 0 6px 25px rgba(16, 185, 129, 0.4); }
    50% { box-shadow: 0 6px 35px rgba(16, 185, 129, 0.6); }
}

/* Chatbot Widget Container */
.chatbot-widget {
    position: fixed;
    bottom: 110px;
    right: 30px;
    width: 380px;
    height: 520px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 10px 50px rgba(0, 0, 0, 0.2);
    z-index: 99999;
    display: none;
    flex-direction: column;
    overflow: hidden;
    transition: all 0.3s ease;
    resize: both;
    min-width: 320px;
    min-height: 400px;
    max-width: 90vw;
    max-height: 80vh;
}

.chatbot-widget.open {
    display: flex;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Chatbot Header - Draggable */
.chatbot-header {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: move;
    user-select: none;
    flex-shrink: 0;
}

.chatbot-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chatbot-avatar {
    width: 42px;
    height: 42px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.chatbot-info h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
}

.chatbot-info p {
    margin: 0;
    font-size: 0.8rem;
    opacity: 0.9;
}

.chatbot-header-actions {
    display: flex;
    gap: 8px;
}

.chatbot-header-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.chatbot-header-btn:hover {
    background: rgba(255,255,255,0.3);
}

/* Chat Messages */
.chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.chatbot-message {
    max-width: 85%;
    padding: 12px 16px;
    border-radius: 16px;
    font-size: 0.95rem;
    line-height: 1.5;
    word-wrap: break-word;
    animation: messageIn 0.3s ease;
}

@keyframes messageIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.chatbot-message.user {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}

.chatbot-message.bot {
    background: white;
    color: #1f2937;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chatbot-message.bot i {
    color: #10b981;
    margin-right: 6px;
}

/* Typing Indicator */
.chatbot-typing {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: white;
    border-radius: 16px;
    align-self: flex-start;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.chatbot-typing-dots {
    display: flex;
    gap: 4px;
}

.chatbot-typing-dots span {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    animation: typingBounce 1.4s infinite;
}

.chatbot-typing-dots span:nth-child(2) { animation-delay: 0.2s; }
.chatbot-typing-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingBounce {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-8px); }
}

/* Chat Input */
.chatbot-input-area {
    padding: 16px;
    background: white;
    border-top: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.chatbot-input-container {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.chatbot-input {
    flex: 1;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.95rem;
    resize: none;
    max-height: 100px;
    font-family: inherit;
    transition: border-color 0.2s ease;
}

.chatbot-input:focus {
    outline: none;
    border-color: #10b981;
}

.chatbot-send-btn {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    width: 46px;
    height: 46px;
    border-radius: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.chatbot-send-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
}

.chatbot-send-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Quick Actions */
.chatbot-quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px 16px;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.chatbot-quick-btn {
    background: #f0fdf4;
    border: 1px solid #10b981;
    color: #059669;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.chatbot-quick-btn:hover {
    background: #10b981;
    color: white;
}

/* Login Prompt */
.chatbot-login-prompt {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    text-align: center;
    background: #f8fafc;
}

.chatbot-login-prompt i {
    font-size: 4rem;
    color: #10b981;
    margin-bottom: 20px;
}

.chatbot-login-prompt h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.chatbot-login-prompt p {
    color: #6b7280;
    margin-bottom: 20px;
}

.chatbot-login-btn {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 12px 30px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.chatbot-login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

/* Responsive */
@media (max-width: 480px) {
    .chatbot-widget {
        width: calc(100vw - 20px);
        height: calc(100vh - 120px);
        bottom: 100px;
        right: 10px;
        border-radius: 16px;
    }
    
    .chatbot-toggle {
        bottom: 20px;
        right: 20px;
        width: 56px;
        height: 56px;
    }
}
</style>

<!-- Chatbot Toggle Button -->
<button class="chatbot-toggle" id="chatbotToggle" title="Chat với AI" type="button">
    <i class="fas fa-robot"></i>
</button>

<!-- Chatbot Widget -->
<div class="chatbot-widget" id="chatbotWidget">
    <div class="chatbot-header" id="chatbotHeader">
        <div class="chatbot-header-left">
            <div class="chatbot-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="chatbot-info">
                <h4>KhamCare AI</h4>
                <p>Trợ lý sức khỏe 24/7</p>
            </div>
        </div>
        <div class="chatbot-header-actions">
            <button class="chatbot-header-btn" id="chatbotClear" title="Xóa hội thoại" type="button">
                <i class="fas fa-trash-alt"></i>
            </button>
            <button class="chatbot-header-btn" id="chatbotClose" title="Đóng" type="button">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <?php if ($isLoggedIn): ?>
    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chatbot-message bot">
            <i class="fas fa-hand-sparkles"></i>
            Xin chào <strong><?= $userName ?></strong>! 👋<br><br>
            Tôi là trợ lý AI y tế của KhamCare. Hãy mô tả triệu chứng hoặc hỏi bất cứ điều gì về sức khỏe nhé! 🏥
        </div>
    </div>
    
    <div class="chatbot-quick-actions" id="chatbotQuickActions">
        <button class="chatbot-quick-btn" type="button" data-msg="Tôi bị đau đầu">🤕 Đau đầu</button>
        <button class="chatbot-quick-btn" type="button" data-msg="Tôi bị sốt">🤒 Sốt</button>
        <button class="chatbot-quick-btn" type="button" data-msg="Tôi muốn đặt lịch khám">📅 Đặt lịch</button>
        <button class="chatbot-quick-btn" type="button" data-msg="Tư vấn sức khỏe">💊 Tư vấn</button>
    </div>
    
    <div class="chatbot-input-area">
        <div class="chatbot-input-container">
            <textarea 
                id="chatbotInput" 
                class="chatbot-input" 
                placeholder="Nhập tin nhắn..."
                rows="1"
            ></textarea>
            <button class="chatbot-send-btn" id="chatbotSend" type="button">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    <?php else: ?>
    <div class="chatbot-login-prompt">
        <i class="fas fa-user-lock"></i>
        <h3>Đăng nhập để chat</h3>
        <p>Vui lòng đăng nhập để sử dụng trợ lý AI y tế của KhamCare</p>
        <a href="TaiKhoan.php" class="chatbot-login-btn">
            <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Chatbot JavaScript -->
<script>
(function() {
    'use strict';
    
    // Variables
    var widget = document.getElementById('chatbotWidget');
    var toggle = document.getElementById('chatbotToggle');
    var header = document.getElementById('chatbotHeader');
    var closeBtn = document.getElementById('chatbotClose');
    var clearBtn = document.getElementById('chatbotClear');
    var messages = document.getElementById('chatbotMessages');
    var input = document.getElementById('chatbotInput');
    var sendBtn = document.getElementById('chatbotSend');
    var quickBtns = document.querySelectorAll('.chatbot-quick-btn');
    
    var isOpen = false;
    var isDragging = false;
    var dragOffset = { x: 0, y: 0 };
    var chatHistory = [];
    
    console.log('🤖 Chatbot Widget initialized');
    
    // Toggle chatbot
    if (toggle && widget) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            isOpen = !isOpen;
            console.log('🤖 Toggle clicked, isOpen:', isOpen);
            
            if (isOpen) {
                widget.classList.add('open');
                toggle.classList.add('active');
                toggle.innerHTML = '<i class="fas fa-times"></i>';
                if (input) {
                    setTimeout(function() { input.focus(); }, 300);
                }
            } else {
                widget.classList.remove('open');
                toggle.classList.remove('active');
                toggle.innerHTML = '<i class="fas fa-robot"></i>';
            }
        });
    }
    
    // Close button
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            isOpen = false;
            widget.classList.remove('open');
            toggle.classList.remove('active');
            toggle.innerHTML = '<i class="fas fa-robot"></i>';
        });
    }
    
    // Clear chat
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (confirm('Xóa toàn bộ hội thoại?')) {
                chatHistory = [];
                localStorage.removeItem('chatbot_history');
                if (messages) {
                    messages.innerHTML = '<div class="chatbot-message bot"><i class="fas fa-hand-sparkles"></i> Hội thoại đã được xóa. Tôi sẵn sàng hỗ trợ bạn! 🏥</div>';
                }
            }
        });
    }
    
    // Quick action buttons
    quickBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var msg = this.getAttribute('data-msg');
            if (msg && input) {
                input.value = msg;
                sendMessage();
            }
        });
    });
    
    // Draggable functionality
    if (header && widget) {
        header.addEventListener('mousedown', startDrag);
        header.addEventListener('touchstart', startDrag, { passive: false });
    }
    
    function startDrag(e) {
        if (e.target.closest('.chatbot-header-btn')) return;
        
        isDragging = true;
        var rect = widget.getBoundingClientRect();
        var clientX = e.type === 'touchstart' ? e.touches[0].clientX : e.clientX;
        var clientY = e.type === 'touchstart' ? e.touches[0].clientY : e.clientY;
        
        dragOffset.x = clientX - rect.left;
        dragOffset.y = clientY - rect.top;
        
        widget.style.transition = 'none';
        
        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchmove', drag, { passive: false });
        document.addEventListener('touchend', stopDrag);
        
        e.preventDefault();
    }
    
    function drag(e) {
        if (!isDragging) return;
        
        var clientX = e.type === 'touchmove' ? e.touches[0].clientX : e.clientX;
        var clientY = e.type === 'touchmove' ? e.touches[0].clientY : e.clientY;
        
        var newX = clientX - dragOffset.x;
        var newY = clientY - dragOffset.y;
        
        var maxX = window.innerWidth - widget.offsetWidth;
        var maxY = window.innerHeight - widget.offsetHeight;
        
        newX = Math.max(0, Math.min(newX, maxX));
        newY = Math.max(0, Math.min(newY, maxY));
        
        widget.style.left = newX + 'px';
        widget.style.top = newY + 'px';
        widget.style.right = 'auto';
        widget.style.bottom = 'auto';
        
        e.preventDefault();
    }
    
    function stopDrag() {
        isDragging = false;
        widget.style.transition = 'all 0.3s ease';
        
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', stopDrag);
        document.removeEventListener('touchmove', drag);
        document.removeEventListener('touchend', stopDrag);
    }
    
    // Send message
    function sendMessage() {
        if (!input || !messages) return;
        
        var message = input.value.trim();
        if (!message) return;
        
        addMessage('user', message);
        chatHistory.push({ role: 'user', content: message });
        input.value = '';
        input.style.height = 'auto';
        
        showTyping();
        
        fetch('api_gemini_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: message,
                history: chatHistory.slice(-10)
            })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            hideTyping();
            
            if (data.success) {
                addMessage('bot', data.response);
                chatHistory.push({ role: 'model', content: data.response });
                saveChatHistory();
                
                if (data.suggested_specialty) {
                    setTimeout(function() {
                        addMessage('bot', '💡 <strong>Gợi ý:</strong> Bạn có thể cần khám chuyên khoa <strong>' + data.suggested_specialty + '</strong>. <a href="TimKiemBS.php?specialty=' + encodeURIComponent(data.suggested_specialty) + '" style="color: #10b981;">Tìm bác sĩ →</a>');
                    }, 500);
                }
            } else {
                addMessage('bot', '❌ ' + (data.error || 'Có lỗi xảy ra. Vui lòng thử lại.'));
            }
        })
        .catch(function(error) {
            hideTyping();
            console.error('Chatbot error:', error);
            addMessage('bot', '❌ Lỗi kết nối. Vui lòng kiểm tra mạng và thử lại.');
        });
    }
    
    function addMessage(type, content) {
        if (!messages) return;
        
        var div = document.createElement('div');
        div.className = 'chatbot-message ' + type;
        div.innerHTML = content;
        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }
    
    function showTyping() {
        if (!messages) return;
        
        var typing = document.createElement('div');
        typing.className = 'chatbot-typing';
        typing.id = 'chatbotTyping';
        typing.innerHTML = '<i class="fas fa-robot" style="color: #10b981;"></i><div class="chatbot-typing-dots"><span></span><span></span><span></span></div>';
        messages.appendChild(typing);
        messages.scrollTop = messages.scrollHeight;
    }
    
    function hideTyping() {
        var typing = document.getElementById('chatbotTyping');
        if (typing) typing.remove();
    }
    
    function saveChatHistory() {
        try {
            localStorage.setItem('chatbot_history', JSON.stringify(chatHistory.slice(-20)));
        } catch(e) {}
    }
    
    function loadChatHistory() {
        try {
            var saved = localStorage.getItem('chatbot_history');
            if (saved) {
                chatHistory = JSON.parse(saved);
                chatHistory.forEach(function(msg) {
                    addMessage(msg.role === 'user' ? 'user' : 'bot', msg.content);
                });
            }
        } catch(e) {}
    }
    
    // Event listeners
    if (sendBtn) {
        sendBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sendMessage();
        });
    }
    
    if (input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });
    }
    
    // Load history on init
    loadChatHistory();
})();
</script>

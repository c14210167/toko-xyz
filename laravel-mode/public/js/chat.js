// Chat functionality
const chatBtn = document.getElementById('chatBtn');
const chatWidget = document.getElementById('chatWidget');
const chatClose = document.getElementById('chatClose');
const chatSend = document.getElementById('chatSend');
const chatInput = document.getElementById('chatInput');
const chatBody = document.getElementById('chatBody');

let messageCheckInterval;

if (chatBtn) {
    chatBtn.addEventListener('click', () => {
        chatWidget.classList.add('active');
        chatBtn.style.display = 'none';
        loadMessages();
        startMessagePolling();
    });
}

if (chatClose) {
    chatClose.addEventListener('click', () => {
        chatWidget.classList.remove('active');
        chatBtn.style.display = 'flex';
        stopMessagePolling();
    });
}

if (chatSend) {
    chatSend.addEventListener('click', sendMessage);
}

if (chatInput) {
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
}

function loadMessages() {
    fetch('api/get-messages.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chatBody.innerHTML = '';
                data.messages.forEach(msg => {
                    appendMessage(msg);
                });
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

function sendMessage() {
    const message = chatInput.value.trim();
    if (message) {
        fetch('api/send-message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chatInput.value = '';
                loadMessages(); // Reload messages
            } else {
                alert('Failed to send message: ' + data.message);
            }
        })
        .catch(error => console.error('Error sending message:', error));
    }
}

function appendMessage(msg) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'chat-message ' + (msg.is_mine ? 'sent' : 'received');
    
    if (msg.is_mine) {
        messageDiv.innerHTML = `
            <div class="message-content">
                <p>${escapeHtml(msg.message)}</p>
                <span class="message-time">${msg.created_at}</span>
            </div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="message-avatar">${msg.sender_role === 'pegawai' ? '👨‍💼' : '👤'}</div>
            <div class="message-content">
                <p>${escapeHtml(msg.message)}</p>
                <span class="message-time">${msg.created_at}</span>
            </div>
        `;
    }
    
    chatBody.appendChild(messageDiv);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function startMessagePolling() {
    messageCheckInterval = setInterval(loadMessages, 3000); // Check every 3 seconds
}

function stopMessagePolling() {
    if (messageCheckInterval) {
        clearInterval(messageCheckInterval);
    }
}
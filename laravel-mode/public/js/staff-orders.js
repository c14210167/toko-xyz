// State management
let currentFilters = {
    search: '',
    status: 'all',
    location: 'all',
    sortBy: 'created_desc',
    page: 1
};

let currentChatOrder = null;
let currentChatCustomer = null;
let chatInterval = null;

// DOM Elements
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const locationFilter = document.getElementById('locationFilter');
const sortBy = document.getElementById('sortBy');
const ordersTableBody = document.getElementById('ordersTableBody');
const pagination = document.getElementById('pagination');
const orderModal = document.getElementById('orderModal');
const chatModal = document.getElementById('chatModal');

// Event Listeners
searchInput.addEventListener('input', debounce((e) => {
    currentFilters.search = e.target.value;
    currentFilters.page = 1;
    loadOrders();
}, 500));

statusFilter.addEventListener('change', (e) => {
    currentFilters.status = e.target.value;
    currentFilters.page = 1;
    loadOrders();
});

locationFilter.addEventListener('change', (e) => {
    currentFilters.location = e.target.value;
    currentFilters.page = 1;
    loadOrders();
});

sortBy.addEventListener('change', (e) => {
    currentFilters.sortBy = e.target.value;
    currentFilters.page = 1;
    loadOrders();
});

// Close modals
document.querySelectorAll('.modal-close').forEach(closeBtn => {
    closeBtn.addEventListener('click', () => {
        closeBtn.closest('.modal').style.display = 'none';
        if (chatInterval) {
            clearInterval(chatInterval);
            chatInterval = null;
        }
    });
});

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
        if (chatInterval) {
            clearInterval(chatInterval);
            chatInterval = null;
        }
    }
});

// Load orders on page load
loadOrders();

// Auto refresh every 30 seconds
setInterval(() => {
    loadOrders(true); // Silent refresh
}, 30000);

// Functions
async function loadOrders(silent = false) {
    if (!silent) {
        ordersTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="loading-cell">
                    <div class="loading-spinner"></div>
                    Loading orders...
                </td>
            </tr>
        `;
    }

    try {
        const params = new URLSearchParams(currentFilters);
        const response = await fetch(`get-orders.php?${params}`);
        const data = await response.json();

        if (data.success) {
            renderOrders(data.orders);
            renderPagination(data.pagination);
            updateNotificationCount();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        ordersTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="error-cell">
                    ❌ Error loading orders: ${error.message}
                </td>
            </tr>
        `;
    }
}

function renderOrders(orders) {
    if (orders.length === 0) {
        ordersTableBody.innerHTML = `
            <tr>
                <td colspan="8" class="empty-cell">
                    📭 No orders found
                </td>
            </tr>
        `;
        return;
    }

    ordersTableBody.innerHTML = orders.map(order => `
        <tr class="order-row" data-order-id="${order.order_id}">
            <td>
                <strong>${order.order_number}</strong>
                ${order.unread_messages > 0 ? `<span class="unread-badge">${order.unread_messages}</span>` : ''}
            </td>
            <td>
                <div class="customer-info">
                    <div class="customer-name">${escapeHtml(order.customer_name)}</div>
                    <div class="customer-contact">${escapeHtml(order.customer_phone)}</div>
                </div>
            </td>
            <td>${escapeHtml(order.service_type)}</td>
            <td>${order.location_name || '-'}</td>
            <td>
                <select class="status-select ${order.status_class}"
                        data-order-id="${order.order_id}"
                        data-current-status="${order.status}">
                    <option value="pending" ${order.status === 'pending' ? 'selected' : ''}>Pending</option>
                    <option value="in_progress" ${order.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
                    <option value="waiting_parts" ${order.status === 'waiting_parts' ? 'selected' : ''}>Waiting Parts</option>
                    <option value="ready_pickup" ${order.status === 'ready_pickup' ? 'selected' : ''}>Ready Pickup</option>
                    <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Completed</option>
                    <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                </select>
            </td>
            <td>${formatDate(order.created_at)}</td>
            <td>${formatDate(order.updated_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action btn-view" onclick="viewOrderDetail(${order.order_id})" title="View Details">
                        👁️
                    </button>
                    <button class="btn-action btn-edit" onclick="openEditOrderModal(${order.order_id})" title="Edit Order" style="background: #059669; color: white;">
                        ✏️
                    </button>
                    <button class="btn-action btn-chat" onclick="openChat(${order.order_id}, ${order.customer_id}, '${escapeHtml(order.customer_name)}', '${order.order_number}')" title="Chat">
                        💬
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    // Add event listeners to status selects
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', async (e) => {
            const orderId = e.target.dataset.orderId;
            const currentStatus = e.target.dataset.currentStatus;
            const newStatus = e.target.value;

            if (newStatus !== currentStatus) {
                const confirmed = confirm(`Change order status to "${newStatus}"?`);
                if (confirmed) {
                    await updateOrderStatus(orderId, newStatus);
                } else {
                    e.target.value = currentStatus; // Revert
                }
            }
        });
    });
}

function renderPagination(paginationData) {
    if (paginationData.total_pages <= 1) {
        pagination.innerHTML = '';
        return;
    }

    let html = '<div class="pagination-controls">';

    // Previous button
    if (paginationData.current_page > 1) {
        html += `<button class="page-btn" onclick="changePage(${paginationData.current_page - 1})">← Previous</button>`;
    }

    // Page numbers
    for (let i = 1; i <= paginationData.total_pages; i++) {
        if (
            i === 1 ||
            i === paginationData.total_pages ||
            (i >= paginationData.current_page - 2 && i <= paginationData.current_page + 2)
        ) {
            html += `<button class="page-btn ${i === paginationData.current_page ? 'active' : ''}" onclick="changePage(${i})">${i}</button>`;
        } else if (
            i === paginationData.current_page - 3 ||
            i === paginationData.current_page + 3
        ) {
            html += '<span class="page-dots">...</span>';
        }
    }

    // Next button
    if (paginationData.current_page < paginationData.total_pages) {
        html += `<button class="page-btn" onclick="changePage(${paginationData.current_page + 1})">Next →</button>`;
    }

    html += '</div>';
    html += `<div class="pagination-info">Showing page ${paginationData.current_page} of ${paginationData.total_pages} (${paginationData.total_records} total orders)</div>`;

    pagination.innerHTML = html;
}

function changePage(page) {
    currentFilters.page = page;
    loadOrders();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function updateOrderStatus(orderId, newStatus) {
    try {
        const response = await fetch('update-order-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                status: newStatus
            })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('✅ Status updated successfully!', 'success');
            loadOrders(true);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error updating status:', error);
        showNotification('❌ Failed to update status', 'error');
        loadOrders(true);
    }
}

async function viewOrderDetail(orderId) {
    const modalBody = document.getElementById('orderModalBody');
    modalBody.innerHTML = '<div class="loading-spinner"></div><p>Loading order details...</p>';
    orderModal.style.display = 'block';

    try {
        const response = await fetch(`../api/get-order-detail.php?order_id=${orderId}`);
        const data = await response.json();

        if (data.success) {
            renderOrderDetail(data.order);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error loading order detail:', error);
        modalBody.innerHTML = `<p class="error">❌ Error loading order details: ${error.message}</p>`;
    }
}

function renderOrderDetail(order) {
    const modalBody = document.getElementById('orderModalBody');
    modalBody.innerHTML = `
        <div class="order-detail-grid">
            <div class="detail-section">
                <h3>📋 Order Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Order Number:</span>
                    <span class="detail-value"><strong>${order.order_number}</strong></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge ${getStatusClass(order.status)}">${order.status}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Service Type:</span>
                    <span class="detail-value">${escapeHtml(order.service_type)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Device:</span>
                    <span class="detail-value">${escapeHtml(order.device_info || '-')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Issue:</span>
                    <span class="detail-value">${escapeHtml(order.issue_description || '-')}</span>
                </div>
            </div>

            <div class="detail-section">
                <h3>👤 Customer Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">${escapeHtml(order.customer_name)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">${escapeHtml(order.customer_phone || '-')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${escapeHtml(order.customer_email || '-')}</span>
                </div>
            </div>

            <div class="detail-section">
                <h3>💰 Cost Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Service Cost:</span>
                    <span class="detail-value">Rp ${formatNumber(order.service_cost || 0)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Parts Cost:</span>
                    <span class="detail-value">Rp ${formatNumber(order.parts_cost || 0)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Cost:</span>
                    <span class="detail-value"><strong>Rp ${formatNumber(order.total_cost || 0)}</strong></span>
                </div>
            </div>

            <div class="detail-section">
                <h3>📍 Location & Dates</h3>
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span class="detail-value">${escapeHtml(order.location_name || '-')}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value">${formatDate(order.created_at)}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Updated:</span>
                    <span class="detail-value">${formatDate(order.updated_at)}</span>
                </div>
            </div>
        </div>
    `;
}

function openChat(orderId, customerId, customerName, orderNumber) {
    currentChatOrder = orderId;
    currentChatCustomer = customerId;

    document.getElementById('chatCustomerName').textContent = customerName;
    document.getElementById('chatOrderNumber').textContent = orderNumber;
    document.getElementById('chatMessages').innerHTML = '<div class="loading-spinner"></div><p>Loading messages...</p>';

    chatModal.style.display = 'block';

    loadChatMessages();

    // Poll for new messages every 3 seconds
    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(loadChatMessages, 3000);
}

async function loadChatMessages() {
    try {
        const response = await fetch(`get-chat-messages.php?order_id=${currentChatOrder}`);
        const data = await response.json();

        if (data.success) {
            renderChatMessages(data.messages);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error loading chat messages:', error);
        document.getElementById('chatMessages').innerHTML = `<p class="error">Error loading messages</p>`;
    }
}

function renderChatMessages(messages) {
    const chatMessagesDiv = document.getElementById('chatMessages');
    const currentScroll = chatMessagesDiv.scrollTop;
    const isScrolledToBottom = chatMessagesDiv.scrollHeight - chatMessagesDiv.clientHeight <= currentScroll + 50;

    if (messages.length === 0) {
        chatMessagesDiv.innerHTML = '<p class="no-messages">No messages yet. Start the conversation!</p>';
        return;
    }

    chatMessagesDiv.innerHTML = messages.map(msg => `
        <div class="chat-message ${msg.is_mine ? 'sent' : 'received'}">
            ${!msg.is_mine ? `<div class="message-avatar">${msg.sender_role === 'customer' ? '👤' : '👨‍💼'}</div>` : ''}
            <div class="message-content">
                ${!msg.is_mine ? `<div class="message-sender">${escapeHtml(msg.sender_name)}</div>` : ''}
                <p>${escapeHtml(msg.message)}</p>
                <span class="message-time">${msg.time_only}</span>
            </div>
        </div>
    `).join('');

    // Auto scroll to bottom if was already at bottom
    if (isScrolledToBottom || messages.length === 1) {
        chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
    }
}

// Send message
document.getElementById('chatSendBtn').addEventListener('click', sendChatMessage);
document.getElementById('chatInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendChatMessage();
    }
});

async function sendChatMessage() {
    const chatInput = document.getElementById('chatInput');
    const message = chatInput.value.trim();

    if (!message) return;

    try {
        const response = await fetch('send-chat-message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: message,
                order_id: currentChatOrder,
                receiver_id: currentChatCustomer
            })
        });

        const data = await response.json();

        if (data.success) {
            chatInput.value = '';
            loadChatMessages();
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error sending message:', error);
        showNotification('❌ Failed to send message', 'error');
    }
}

async function updateNotificationCount() {
    try {
        // Count total unread messages
        const response = await fetch('get-orders.php?status=all');
        const data = await response.json();

        if (data.success) {
            const totalUnread = data.orders.reduce((sum, order) => sum + order.unread_messages, 0);
            document.getElementById('notifCount').textContent = totalUnread;
        }
    } catch (error) {
        console.error('Error updating notification count:', error);
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const now = new Date();
    const diff = now - date;
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (hours < 1) {
        const minutes = Math.floor(diff / (1000 * 60));
        return `${minutes}m ago`;
    } else if (hours < 24) {
        return `${hours}h ago`;
    } else if (days < 7) {
        return `${days}d ago`;
    } else {
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function getStatusClass(status) {
    const classes = {
        'pending': 'status-pending',
        'in_progress': 'status-progress',
        'waiting_parts': 'status-waiting',
        'completed': 'status-completed',
        'cancelled': 'status-cancelled',
        'ready_pickup': 'status-ready'
    };
    return classes[status] || 'status-default';
}

function showNotification(message, type = 'info') {
    // Simple notification system
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

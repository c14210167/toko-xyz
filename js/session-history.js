/**
 * Session History JavaScript
 */

let currentPage = 1;
let totalPages = 1;
let currentStatus = 'all';

// Load sessions on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSessions();

    // Status filter change
    document.getElementById('statusFilter').addEventListener('change', function() {
        currentStatus = this.value;
        currentPage = 1;
        loadSessions();
    });
});

async function loadSessions() {
    try {
        const response = await fetch(`api/get-sessions.php?status=${currentStatus}&page=${currentPage}&limit=20`);
        const data = await response.json();

        if (data.success) {
            renderSessionsTable(data.sessions);
            renderPagination(data.pagination);
        } else {
            alert('Error loading sessions: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load sessions');
    }
}

function renderSessionsTable(sessions) {
    const tbody = document.getElementById('sessionsTableBody');

    if (sessions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="loading-cell">No sessions found</td>
            </tr>
        `;
        return;
    }

    let html = '';
    sessions.forEach(session => {
        const openedAt = new Date(session.opened_at).toLocaleString('id-ID');
        const closedAt = session.closed_at ? new Date(session.closed_at).toLocaleString('id-ID') : '-';

        // Variance indicator
        let varianceClass = 'variance-zero';
        let varianceText = 'Rp 0';
        if (session.variance > 0) {
            varianceClass = 'variance-positive';
            varianceText = '+ Rp ' + formatNumber(session.variance);
        } else if (session.variance < 0) {
            varianceClass = 'variance-negative';
            varianceText = '- Rp ' + formatNumber(Math.abs(session.variance));
        }

        // Status badge
        const statusClass = session.status === 'open' ? 'status-open' : 'status-closed';
        const statusText = session.status === 'open' ? 'OPEN' : 'CLOSED';

        html += `
            <tr>
                <td>#${session.session_id}</td>
                <td>${session.cashier_name}</td>
                <td>${session.location_name}</td>
                <td>${openedAt}</td>
                <td>${closedAt}</td>
                <td class="${varianceClass}">${varianceText}</td>
                <td>${session.total_transactions}</td>
                <td>Rp ${formatNumber(session.total_sales)}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="viewSessionDetail(${session.session_id})">
                        View Detail
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function renderPagination(pagination) {
    const paginationDiv = document.getElementById('pagination');
    totalPages = pagination.total_pages;

    if (totalPages <= 1) {
        paginationDiv.innerHTML = '';
        return;
    }

    let html = `
        <button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
            ← Previous
        </button>
        <span>Page ${currentPage} of ${totalPages}</span>
        <button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
            Next →
        </button>
    `;

    paginationDiv.innerHTML = html;
}

function changePage(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    loadSessions();
}

async function viewSessionDetail(sessionId) {
    try {
        const response = await fetch(`api/get-sessions.php?status=all&page=1&limit=1000`);
        const data = await response.json();

        if (data.success) {
            const session = data.sessions.find(s => s.session_id === sessionId);
            if (session) {
                showSessionDetailModal(session);
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load session details');
    }
}

function showSessionDetailModal(session) {
    const modal = document.getElementById('sessionDetailModal');
    const sessionIdSpan = document.getElementById('modalSessionId');
    const bodyDiv = document.getElementById('sessionDetailBody');

    sessionIdSpan.textContent = `#${session.session_id}`;

    const openedAt = new Date(session.opened_at).toLocaleString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });

    const closedAt = session.closed_at ? new Date(session.closed_at).toLocaleString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }) : 'Still Open';

    // Variance
    let varianceClass = session.variance === 0 ? 'variance-zero' : (session.variance > 0 ? 'variance-positive' : 'variance-negative');
    let varianceSymbol = session.variance > 0 ? '+' : (session.variance < 0 ? '-' : '');
    let varianceAmount = Math.abs(session.variance);

    let html = `
        <div class="variance-card" style="background: ${session.variance > 0 ? '#10b981' : (session.variance < 0 ? '#ef4444' : '#64748b')};">
            <h3>Cash Variance</h3>
            <div class="amount">${varianceSymbol} Rp ${formatNumber(varianceAmount)}</div>
            <p style="margin-top: 10px; opacity: 0.9; font-size: 14px;">
                ${session.variance > 0 ? 'Cash surplus detected' : (session.variance < 0 ? 'Cash shortage detected' : 'Perfect match!')}
            </p>
        </div>

        <div class="detail-grid">
            <div class="detail-item">
                <label>Cashier</label>
                <div class="value">${session.cashier_name}</div>
            </div>
            <div class="detail-item">
                <label>Location</label>
                <div class="value">${session.location_name}</div>
            </div>
            <div class="detail-item">
                <label>Started At</label>
                <div class="value">${openedAt}</div>
            </div>
            <div class="detail-item">
                <label>Ended At</label>
                <div class="value">${closedAt}</div>
            </div>
            <div class="detail-item">
                <label>Opening Balance</label>
                <div class="value">Rp ${formatNumber(session.opening_balance)}</div>
            </div>
            <div class="detail-item">
                <label>Expected Balance</label>
                <div class="value">Rp ${formatNumber(session.expected_balance)}</div>
            </div>
            <div class="detail-item">
                <label>Actual Closing Balance</label>
                <div class="value">Rp ${formatNumber(session.closing_balance)}</div>
            </div>
            <div class="detail-item">
                <label>Status</label>
                <div class="value">
                    <span class="status-badge ${session.status === 'open' ? 'status-open' : 'status-closed'}">
                        ${session.status.toUpperCase()}
                    </span>
                </div>
            </div>
        </div>

        <div style="background: #f1f5f9; padding: 20px; border-radius: 8px; margin-top: 20px;">
            <h3 style="margin: 0 0 15px 0; color: #1e293b;">Sales Breakdown</h3>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Cash Sales</label>
                    <div class="value">Rp ${formatNumber(session.cash_sales)}</div>
                </div>
                <div class="detail-item">
                    <label>Card Sales</label>
                    <div class="value">Rp ${formatNumber(session.card_sales)}</div>
                </div>
                <div class="detail-item">
                    <label>QRIS Sales</label>
                    <div class="value">Rp ${formatNumber(session.qris_sales)}</div>
                </div>
                <div class="detail-item">
                    <label>Total Sales</label>
                    <div class="value" style="color: #059669; font-size: 20px;">Rp ${formatNumber(session.total_sales)}</div>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <div class="detail-item">
                <label>Total Transactions</label>
                <div class="value">${session.total_transactions} transactions</div>
            </div>
        </div>
    `;

    // Opening Notes
    if (session.opening_notes) {
        html += `
            <div class="notes-section" style="background: #dbeafe; border-left-color: #3b82f6;">
                <h4 style="color: #1e40af;">📝 Opening Notes</h4>
                <p style="color: #1e3a8a;">${escapeHtml(session.opening_notes)}</p>
            </div>
        `;
    }

    // Closing Notes
    if (session.closing_notes) {
        html += `
            <div class="notes-section">
                <h4>📝 Closing Notes</h4>
                <p>${escapeHtml(session.closing_notes)}</p>
            </div>
        `;
    }

    bodyDiv.innerHTML = html;
    modal.classList.add('active');
}

function closeSessionDetailModal() {
    const modal = document.getElementById('sessionDetailModal');
    modal.classList.remove('active');
}

function formatNumber(num) {
    return parseInt(num).toLocaleString('id-ID');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

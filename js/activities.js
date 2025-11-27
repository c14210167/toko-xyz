/**
 * Activity Logs JavaScript
 */

let currentPage = 1;
let totalPages = 1;

// Load activities on page load
document.addEventListener('DOMContentLoaded', function() {
    loadActivities();

    // Add event listeners for filters
    document.getElementById('employeeFilter').addEventListener('change', () => {
        currentPage = 1;
        loadActivities();
    });

    document.getElementById('actionFilter').addEventListener('change', () => {
        currentPage = 1;
        loadActivities();
    });

    document.getElementById('dateFilter').addEventListener('change', () => {
        currentPage = 1;
        loadActivities();
    });
});

// Load activities from API
async function loadActivities() {
    const container = document.getElementById('activitiesContainer');
    container.innerHTML = '<div class="loading-spinner">Loading activities...</div>';

    try {
        const userId = document.getElementById('employeeFilter').value;
        const actionType = document.getElementById('actionFilter').value;
        const dateFilter = document.getElementById('dateFilter').value;

        const params = new URLSearchParams({
            page: currentPage,
            user_id: userId,
            action_type: actionType,
            date_filter: dateFilter
        });

        const response = await fetch(`api/get-activities.php?${params}`);
        const data = await response.json();

        if (data.success) {
            renderActivities(data.activities);
            updatePagination(data.pagination);
        } else {
            container.innerHTML = '<div class="empty-state"><p>Error loading activities</p></div>';
        }
    } catch (error) {
        console.error('Error loading activities:', error);
        container.innerHTML = '<div class="empty-state"><p>Error loading activities</p></div>';
    }
}

// Render activities timeline
function renderActivities(activities) {
    const container = document.getElementById('activitiesContainer');

    if (activities.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3>No activities found</h3>
                <p>Try adjusting your filters</p>
            </div>
        `;
        return;
    }

    const timeline = activities.map(activity => {
        const actionClass = activity.action_type.replace('_', '-');
        const badgeClass = getBadgeClass(activity.action_type);
        const icon = getActivityIcon(activity.action_type);
        const timeAgo = formatTimeAgo(activity.created_at);

        return `
            <div class="activity-item ${activity.action_type}">
                <div class="activity-header">
                    <div class="activity-user">
                        <div class="activity-avatar">
                            ${activity.user_name.charAt(0).toUpperCase()}
                        </div>
                        <div class="activity-user-info">
                            <div class="activity-user-name">${escapeHtml(activity.user_name)}</div>
                            <div class="activity-user-email">${escapeHtml(activity.user_email)}</div>
                        </div>
                    </div>
                    <div class="activity-time">${timeAgo}</div>
                </div>
                <div class="activity-description">
                    ${icon} ${escapeHtml(activity.action_description)}
                    <div>
                        <span class="activity-type-badge ${badgeClass}">
                            ${formatActionType(activity.action_type)}
                        </span>
                    </div>
                </div>
                <div class="activity-meta">
                    ${activity.ip_address ? `<span>🌐 ${activity.ip_address}</span>` : ''}
                    ${activity.related_entity_type ? `<span>📎 ${activity.related_entity_type} #${activity.related_entity_id || 'N/A'}</span>` : ''}
                </div>
            </div>
        `;
    }).join('');

    container.innerHTML = `<div class="activity-timeline">${timeline}</div>`;
}

// Get badge class based on action type
function getBadgeClass(actionType) {
    if (actionType.includes('login') || actionType.includes('logout')) {
        return actionType === 'login' ? 'badge-login' : 'badge-logout';
    }
    if (actionType.includes('order')) return 'badge-order';
    if (actionType.includes('role') || actionType.includes('permission')) return 'badge-role';
    if (actionType.includes('location')) return 'badge-location';
    if (actionType.includes('employee')) return 'badge-employee';
    return 'badge-order';
}

// Get activity icon
function getActivityIcon(actionType) {
    const icons = {
        'login': '🔓',
        'logout': '🔒',
        'order_create': '📝',
        'order_update': '✏️',
        'role_create': '➕',
        'role_update': '🔄',
        'role_delete': '🗑️',
        'permission_change': '🔐',
        'employee_update': '👤',
        'location_create': '📍',
        'location_update': '✏️',
        'location_delete': '🗑️'
    };
    return icons[actionType] || '📋';
}

// Format action type for display
function formatActionType(actionType) {
    return actionType.replace(/_/g, ' ');
}

// Format timestamp to relative time
function formatTimeAgo(timestamp) {
    const now = new Date();
    const date = new Date(timestamp);
    const diff = Math.floor((now - date) / 1000); // difference in seconds

    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)} minutes ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} hours ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)} days ago`;

    // Format as date
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Update pagination controls
function updatePagination(pagination) {
    const paginationContainer = document.getElementById('paginationContainer');
    const pageInfo = document.getElementById('pageInfo');
    const btnPrev = document.getElementById('btnPrevPage');
    const btnNext = document.getElementById('btnNextPage');

    currentPage = pagination.current_page;
    totalPages = pagination.total_pages;

    if (totalPages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }

    paginationContainer.style.display = 'flex';
    pageInfo.textContent = `Page ${currentPage} of ${totalPages} (${pagination.total} activities)`;

    btnPrev.disabled = currentPage <= 1;
    btnNext.disabled = currentPage >= totalPages;
}

// Pagination functions
function previousPage() {
    if (currentPage > 1) {
        currentPage--;
        loadActivities();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        loadActivities();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Utility function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

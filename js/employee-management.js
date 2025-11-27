// Employee Management JavaScript

// Check if we're on the employees list page or employee detail page
const isEmployeeListPage = document.getElementById('employeeGrid') !== null;
const isEmployeeDetailPage = typeof employeeId !== 'undefined';

// ========================================
// EMPLOYEES LIST PAGE
// ========================================
if (isEmployeeListPage) {
    let allEmployees = [];

    // Load employees on page load
    document.addEventListener('DOMContentLoaded', () => {
        loadEmployees();
        setupFilters();
    });

    async function loadEmployees() {
        try {
            const response = await fetch('api/get-employees.php');
            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            allEmployees = data.employees;
            displayEmployees(allEmployees);
        } catch (error) {
            showError('Failed to load employees');
            console.error(error);
        }
    }

    function displayEmployees(employees) {
        const grid = document.getElementById('employeeGrid');

        if (employees.length === 0) {
            grid.innerHTML = '<div class="no-data">No employees found</div>';
            return;
        }

        grid.innerHTML = employees.map(emp => `
            <div class="employee-card" onclick="window.location.href='employee-detail.php?id=${emp.user_id}'">
                <div class="employee-card-header">
                    <div class="employee-avatar">${emp.avatar}</div>
                    <div class="employee-info">
                        <h3>${escapeHtml(emp.name)}</h3>
                        <p class="employee-email">${escapeHtml(emp.email)}</p>
                    </div>
                </div>
                <div class="employee-card-body">
                    <div class="employee-detail-row">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value">${escapeHtml(emp.phone)}</span>
                    </div>
                    <div class="employee-detail-row">
                        <span class="detail-label">Roles:</span>
                        <span class="detail-value">${escapeHtml(emp.roles)}</span>
                    </div>
                    <div class="employee-detail-row">
                        <span class="detail-label">Old Role:</span>
                        <span class="detail-value badge-role">${escapeHtml(emp.old_role)}</span>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function setupFilters() {
        const searchInput = document.getElementById('searchEmployee');
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');

        searchInput.addEventListener('input', applyFilters);
        roleFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
    }

    function applyFilters() {
        const searchTerm = document.getElementById('searchEmployee').value.toLowerCase();
        const roleFilter = document.getElementById('roleFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;

        let filtered = allEmployees;

        // Apply search filter
        if (searchTerm) {
            filtered = filtered.filter(emp =>
                emp.name.toLowerCase().includes(searchTerm) ||
                emp.email.toLowerCase().includes(searchTerm)
            );
        }

        // Apply role filter
        if (roleFilter !== 'all') {
            filtered = filtered.filter(emp =>
                emp.roles.includes(roleFilter)
            );
        }

        // Apply status filter (you can implement this based on your needs)
        // For now, all employees are considered active

        displayEmployees(filtered);
    }
}

// ========================================
// EMPLOYEE DETAIL PAGE
// ========================================
if (isEmployeeDetailPage) {
    let currentRoles = [];
    let allRoles = [];

    document.addEventListener('DOMContentLoaded', () => {
        loadEmployeeRoles();
        setupSaveButton();
    });

    async function loadEmployeeRoles() {
        try {
            const response = await fetch(`api/get-employee-detail.php?id=${employeeId}`);
            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            currentRoles = data.current_roles;
            allRoles = data.all_roles;

            displayCurrentRoles(currentRoles);
            displayAvailableRoles(allRoles);
        } catch (error) {
            showError('Failed to load employee roles');
            console.error(error);
        }
    }

    function displayCurrentRoles(roles) {
        const container = document.getElementById('currentRolesList');

        if (roles.length === 0) {
            container.innerHTML = '<p class="no-data">No roles assigned</p>';
            return;
        }

        container.innerHTML = roles.map(role => `
            <div class="role-badge ${role.is_system_role ? 'system-role' : ''}">
                <span>${escapeHtml(role.role_name)}</span>
                ${role.is_system_role ? '<span title="System Role">🔒</span>' : ''}
            </div>
        `).join('');
    }

    function displayAvailableRoles(roles) {
        const container = document.getElementById('availableRolesList');

        container.innerHTML = roles.map(role => `
            <div class="checkbox-item">
                <input
                    type="checkbox"
                    id="role_${role.role_id}"
                    value="${role.role_id}"
                    ${role.is_assigned ? 'checked' : ''}
                >
                <label for="role_${role.role_id}" class="checkbox-label">
                    <div>${escapeHtml(role.role_name)}</div>
                    ${role.description ? `<div class="checkbox-description">${escapeHtml(role.description)}</div>` : ''}
                </label>
            </div>
        `).join('');
    }

    function setupSaveButton() {
        const saveButton = document.getElementById('btnSaveRoles');
        saveButton.addEventListener('click', saveEmployeeRoles);
    }

    async function saveEmployeeRoles() {
        const checkboxes = document.querySelectorAll('#availableRolesList input[type="checkbox"]:checked');
        const roleIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        try {
            const response = await fetch('api/update-employee-roles.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    role_ids: roleIds
                })
            });

            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            showSuccess('Employee roles updated successfully');
            setTimeout(() => {
                loadEmployeeRoles();
            }, 1000);
        } catch (error) {
            showError('Failed to update employee roles');
            console.error(error);
        }
    }
}

// ========================================
// UTILITY FUNCTIONS
// ========================================
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    // Simple alert for now, can be replaced with a better notification system
    alert('Error: ' + message);
}

function showSuccess(message) {
    // Simple alert for now, can be replaced with a better notification system
    alert('Success: ' + message);
}

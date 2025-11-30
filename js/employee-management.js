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
            <div class="employee-card" onclick="showEditEmployeeModal(${emp.user_id})">
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
// EMPLOYEE DETAIL PAGE (DEPRECATED - Using Modal Instead)
// ========================================
if (isEmployeeDetailPage) {
    // This section is no longer used - employee editing is done via modal
    console.log('Employee detail page detected but using modal-based editing');
}

// ========================================
// MODAL FUNCTIONS (GLOBAL)
// ========================================
function showAddEmployeeModal() {
    const modal = document.getElementById('employeeModal');
    const form = document.getElementById('employeeForm');
    const modalTitle = document.getElementById('modalTitle');
    const passwordGroup = document.getElementById('passwordGroup');

    // Reset form
    form.reset();
    document.getElementById('employeeId').value = '';

    // Set title and show password field
    modalTitle.textContent = 'Add New Employee';
    passwordGroup.style.display = 'block';
    document.getElementById('password').required = true;

    // Show modal
    modal.style.display = 'flex';
}

async function showEditEmployeeModal(employeeId) {
    const modal = document.getElementById('employeeModal');
    const form = document.getElementById('employeeForm');
    const modalTitle = document.getElementById('modalTitle');
    const passwordGroup = document.getElementById('passwordGroup');

    try {
        // Fetch employee data
        const response = await fetch(`api/get-employee-info.php?id=${employeeId}`);
        const data = await response.json();

        if (data.error) {
            showError(data.error);
            return;
        }

        // Additional check: warn if trying to edit owner as non-owner
        if (data.employee.user_type === 'owner' && data.current_user_type !== 'owner') {
            showError('Only owners can edit other owner accounts');
            return;
        }

        // Fill form
        document.getElementById('employeeId').value = data.employee.user_id;
        document.getElementById('firstName').value = data.employee.first_name;
        document.getElementById('lastName').value = data.employee.last_name;
        document.getElementById('email').value = data.employee.email;
        document.getElementById('phone').value = data.employee.phone || '';
        document.getElementById('address').value = data.employee.address || '';

        // Select the role radio button
        if (data.employee.role_id) {
            const roleRadio = document.getElementById(`role_${data.employee.role_id}`);
            if (roleRadio) {
                roleRadio.checked = true;
            }
        }

        // Set title and hide password field for edit
        modalTitle.textContent = 'Edit Employee';
        passwordGroup.style.display = 'none';
        document.getElementById('password').required = false;

        // Show modal
        modal.style.display = 'flex';

    } catch (error) {
        showError('Failed to load employee data');
        console.error(error);
    }
}

function closeEmployeeModal() {
    const modal = document.getElementById('employeeModal');
    modal.style.display = 'none';
}

async function saveEmployee() {
    const form = document.getElementById('employeeForm');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const employeeId = document.getElementById('employeeId').value;
    const formData = {
        employee_id: employeeId || null,
        first_name: document.getElementById('firstName').value,
        last_name: document.getElementById('lastName').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value,
        role_id: document.querySelector('input[name="role_id"]:checked')?.value
    };

    // Add password only if adding new employee or if password field is visible and filled
    if (!employeeId) {
        formData.password = document.getElementById('password').value;
    }

    try {
        const apiUrl = employeeId ? 'api/update-employee.php' : 'api/add-employee.php';
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.error || !data.success) {
            showError(data.error || data.message || 'Failed to save employee');
            return;
        }

        showSuccess(employeeId ? 'Employee updated successfully' : 'Employee added successfully');
        closeEmployeeModal();

        // If user updated their own profile, reload page to update sidebar
        if (data.reload_page) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
            return;
        }

        // Reload employees list if on list page
        if (typeof loadEmployees === 'function') {
            loadEmployees();
        }

    } catch (error) {
        showError('Failed to save employee');
        console.error(error);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('employeeModal');
    if (event.target === modal) {
        closeEmployeeModal();
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

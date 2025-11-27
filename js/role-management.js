// Role Management JavaScript

// Check which page we're on
const isRolesListPage = document.getElementById('rolesGrid') !== null;
const isRoleDetailPage = typeof roleId !== 'undefined';

// ========================================
// ROLES LIST PAGE
// ========================================
if (isRolesListPage) {
    let allRoles = [];
    let deleteRoleId = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadRoles();
        setupModals();
    });

    async function loadRoles() {
        try {
            const response = await fetch('api/get-roles.php');
            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            allRoles = data.roles;
            displayRoles(allRoles);
        } catch (error) {
            showError('Failed to load roles');
            console.error(error);
        }
    }

    function displayRoles(roles) {
        const grid = document.getElementById('rolesGrid');

        if (roles.length === 0) {
            grid.innerHTML = '<div class="no-data">No roles found</div>';
            return;
        }

        grid.innerHTML = roles.map(role => `
            <div class="role-card">
                <div class="role-card-header">
                    <div class="role-title-section">
                        <h3>
                            ${escapeHtml(role.role_name)}
                            ${role.is_system_role == 1 ? '<span class="system-badge">System</span>' : ''}
                        </h3>
                        <p class="role-description">${escapeHtml(role.description || 'No description')}</p>
                    </div>
                </div>
                <div class="role-stats">
                    <div class="role-stat">
                        <span class="role-stat-label">Users</span>
                        <span class="role-stat-value">${role.user_count}</span>
                    </div>
                    <div class="role-stat">
                        <span class="role-stat-label">Permissions</span>
                        <span class="role-stat-value">${role.permission_count}</span>
                    </div>
                </div>
                <div class="role-actions">
                    <button class="btn-edit" onclick="window.location.href='role-detail.php?id=${role.role_id}'">
                        ✏️ Edit Permissions
                    </button>
                    <button
                        class="btn-delete"
                        onclick="confirmDeleteRole(${role.role_id}, '${escapeHtml(role.role_name)}')"
                        ${role.is_system_role == 1 ? 'disabled title="Cannot delete system roles"' : ''}
                    >
                        🗑️ Delete
                    </button>
                </div>
            </div>
        `).join('');
    }

    function setupModals() {
        // Add Role Modal
        const btnAddRole = document.getElementById('btnAddRole');
        const addRoleModal = document.getElementById('addRoleModal');
        const closeAddModal = document.getElementById('closeAddModal');
        const cancelAddRole = document.getElementById('cancelAddRole');
        const confirmAddRole = document.getElementById('confirmAddRole');

        btnAddRole.addEventListener('click', () => {
            addRoleModal.classList.add('show');
            document.getElementById('roleName').value = '';
            document.getElementById('roleDescription').value = '';
        });

        closeAddModal.addEventListener('click', () => {
            addRoleModal.classList.remove('show');
        });

        cancelAddRole.addEventListener('click', () => {
            addRoleModal.classList.remove('show');
        });

        confirmAddRole.addEventListener('click', createRole);

        // Delete Role Modal
        const deleteRoleModal = document.getElementById('deleteRoleModal');
        const closeDeleteModal = document.getElementById('closeDeleteModal');
        const cancelDeleteRole = document.getElementById('cancelDeleteRole');
        const confirmDeleteRole = document.getElementById('confirmDeleteRole');

        closeDeleteModal.addEventListener('click', () => {
            deleteRoleModal.classList.remove('show');
        });

        cancelDeleteRole.addEventListener('click', () => {
            deleteRoleModal.classList.remove('show');
        });

        confirmDeleteRole.addEventListener('click', deleteRole);

        // Close modals on outside click
        window.addEventListener('click', (e) => {
            if (e.target === addRoleModal) {
                addRoleModal.classList.remove('show');
            }
            if (e.target === deleteRoleModal) {
                deleteRoleModal.classList.remove('show');
            }
        });
    }

    async function createRole() {
        const roleName = document.getElementById('roleName').value.trim();
        const roleDescription = document.getElementById('roleDescription').value.trim();

        if (!roleName) {
            showError('Role name is required');
            return;
        }

        try {
            const response = await fetch('api/create-role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    role_name: roleName,
                    description: roleDescription
                })
            });

            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            showSuccess('Role created successfully');
            document.getElementById('addRoleModal').classList.remove('show');
            loadRoles();
        } catch (error) {
            showError('Failed to create role');
            console.error(error);
        }
    }

    window.confirmDeleteRole = function(roleId, roleName) {
        deleteRoleId = roleId;
        document.getElementById('deleteRoleModal').classList.add('show');
    };

    async function deleteRole() {
        if (!deleteRoleId) return;

        try {
            const response = await fetch('api/delete-role.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    role_id: deleteRoleId
                })
            });

            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            showSuccess('Role deleted successfully');
            document.getElementById('deleteRoleModal').classList.remove('show');
            deleteRoleId = null;
            loadRoles();
        } catch (error) {
            showError('Failed to delete role');
            console.error(error);
        }
    }
}

// ========================================
// ROLE DETAIL PAGE
// ========================================
if (isRoleDetailPage) {
    let allPermissions = {};

    document.addEventListener('DOMContentLoaded', () => {
        loadRolePermissions();
        setupButtons();
    });

    async function loadRolePermissions() {
        try {
            const response = await fetch(`api/get-role-permissions.php?role_id=${roleId}`);
            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            allPermissions = data.permissions;
            displayPermissions(allPermissions);
        } catch (error) {
            showError('Failed to load permissions');
            console.error(error);
        }
    }

    function displayPermissions(permissions) {
        const container = document.getElementById('permissionsGrid');

        const categoryIcons = {
            'orders': '📦',
            'users': '👥',
            'inventory': '📦',
            'sales': '💰',
            'payments': '💳',
            'reports': '📊',
            'expenses': '💸',
            'customers': '👤',
            'appointments': '📅',
            'ratings': '⭐',
            'settings': '⚙️',
            'dashboard': '📊'
        };

        const html = Object.keys(permissions).map(category => {
            const perms = permissions[category];
            const icon = categoryIcons[category] || '📋';

            return `
                <div class="permission-category">
                    <div class="category-header">
                        <span class="category-icon">${icon}</span>
                        <span class="category-name">${escapeHtml(category)}</span>
                    </div>
                    <div class="category-permissions">
                        ${perms.map(perm => `
                            <div class="permission-checkbox">
                                <input
                                    type="checkbox"
                                    id="perm_${perm.permission_id}"
                                    value="${perm.permission_id}"
                                    ${perm.is_assigned ? 'checked' : ''}
                                >
                                <label for="perm_${perm.permission_id}" class="permission-label">
                                    <div class="permission-name">${escapeHtml(perm.permission_name)}</div>
                                    ${perm.description ? `<div class="permission-description">${escapeHtml(perm.description)}</div>` : ''}
                                </label>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    function setupButtons() {
        const btnSave = document.getElementById('btnSavePermissions');
        const btnSelectAll = document.getElementById('btnSelectAll');
        const btnDeselectAll = document.getElementById('btnDeselectAll');

        btnSave.addEventListener('click', savePermissions);
        btnSelectAll.addEventListener('click', () => {
            document.querySelectorAll('#permissionsGrid input[type="checkbox"]').forEach(cb => {
                cb.checked = true;
            });
        });
        btnDeselectAll.addEventListener('click', () => {
            document.querySelectorAll('#permissionsGrid input[type="checkbox"]').forEach(cb => {
                cb.checked = false;
            });
        });
    }

    async function savePermissions() {
        const checkboxes = document.querySelectorAll('#permissionsGrid input[type="checkbox"]:checked');
        const permissionIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        try {
            const response = await fetch('api/update-role-permissions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    role_id: roleId,
                    permission_ids: permissionIds
                })
            });

            const data = await response.json();

            if (data.error) {
                showError(data.error);
                return;
            }

            showSuccess('Role permissions updated successfully');
            setTimeout(() => {
                loadRolePermissions();
            }, 1000);
        } catch (error) {
            showError('Failed to update permissions');
            console.error(error);
        }
    }
}

// ========================================
// UTILITY FUNCTIONS
// ========================================
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    alert('Error: ' + message);
}

function showSuccess(message) {
    alert('Success: ' + message);
}

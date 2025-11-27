<?php
session_start();
require_once '../../config/init_permissions.php';

// Check permission
if (!hasPermission('manage_permissions')) {
    header('Location: ../dashboard.php');
    exit();
}

// Get user info
$user_name = $_SESSION['user_name'] ?? 'User';

// Get all users
require_once '../../config/database.php';
$database = new Database();
$conn = $database->getConnection();

$users_query = "SELECT u.user_id, u.username, u.email, CONCAT(u.first_name, ' ', u.last_name) as full_name, u.role as old_role
                FROM users u
                WHERE u.role != 'customer'
                ORDER BY u.username";
$users_stmt = $conn->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission Management - Plus Plus Komputer</title>
    <link rel="stylesheet" href="../../css/staff-dashboard.css">
    <style>
        .permissions-container {
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #333;
        }

        .users-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .users-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        .users-table tbody tr:hover {
            background: #f8f9fa;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 5px;
        }

        .badge-owner {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-manager {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-technician {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-cashier {
            background: #fce7f3;
            color: #9f1239;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .roles-section, .permissions-section {
            margin-bottom: 30px;
        }

        .roles-section h3, .permissions-section h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #555;
        }

        .role-checkbox {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .role-checkbox input {
            margin-right: 10px;
        }

        .role-checkbox label {
            flex: 1;
            cursor: pointer;
        }

        .role-description {
            font-size: 12px;
            color: #999;
            margin-left: 30px;
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .permission-category {
            margin-bottom: 20px;
        }

        .permission-category h4 {
            font-size: 14px;
            color: #667eea;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .permission-item {
            display: flex;
            align-items: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .permission-item input {
            margin-right: 8px;
        }

        .permission-item label {
            font-size: 13px;
            cursor: pointer;
            flex: 1;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-profile">
            <div class="profile-picture">
                <div class="avatar">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <div class="status-indicator"></div>
            </div>
            <h3 class="user-name"><?php echo htmlspecialchars($user_name); ?></h3>
            <p class="user-role">Admin</p>
        </div>

        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span class="nav-text">Dashboard</span>
            </a>
            <a href="../orders.php" class="nav-item">
                <span class="nav-icon">🔧</span>
                <span class="nav-text">Orders</span>
            </a>
            <a href="../customers.php" class="nav-item">
                <span class="nav-icon">👥</span>
                <span class="nav-text">Customers</span>
            </a>
            <a href="../inventory.php" class="nav-item">
                <span class="nav-icon">📦</span>
                <span class="nav-text">Inventory</span>
            </a>
            <a href="permissions.php" class="nav-item active">
                <span class="nav-icon">🔐</span>
                <span class="nav-text">Permissions</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="../../logout.php" class="footer-btn btn-logout">
                <span>🚪</span>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="permissions-container">
            <div class="page-header">
                <h1>🔐 User Permissions & Roles</h1>
            </div>

            <!-- Users Table -->
            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Current Roles</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td id="roles_<?= $user['user_id'] ?>">
                                <span class="loading">Loading...</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="manageUser(<?= $user['user_id'] ?>, '<?= htmlspecialchars($user['full_name']) ?>')">
                                    Manage Permissions
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Manage Permissions Modal -->
    <div id="manageModal" class="modal">
        <div class="modal-content">
            <h2>Manage Permissions for <span id="userName"></span></h2>
            <input type="hidden" id="currentUserId">

            <!-- Roles Section -->
            <div class="roles-section">
                <h3>Assign Roles</h3>
                <div id="rolesContainer">
                    <div class="loading">Loading roles...</div>
                </div>
            </div>

            <!-- Permissions Section -->
            <div class="permissions-section">
                <h3>Individual Permissions (Override)</h3>
                <p style="font-size: 13px; color: #999; margin-bottom: 15px;">
                    Individual permissions will override role-based permissions
                </p>
                <div id="permissionsContainer">
                    <div class="loading">Loading permissions...</div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn" onclick="closeManageModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePermissions()">Save Changes</button>
            </div>
        </div>
    </div>

    <script>
        let allRoles = [];
        let allPermissions = {};

        // Load all data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadAllRoles();
            loadAllPermissions();
            loadUserRoles();
        });

        function loadAllRoles() {
            fetch('../api/get-all-roles.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        allRoles = data.roles;
                    }
                });
        }

        function loadAllPermissions() {
            fetch('../api/get-all-permissions.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        allPermissions = data.permissions;
                    }
                });
        }

        function loadUserRoles() {
            const users = <?= json_encode($users) ?>;
            users.forEach(user => {
                fetch(`../api/get-user-permissions.php?user_id=${user.user_id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            displayUserRoles(user.user_id, data.roles);
                        }
                    });
            });
        }

        function displayUserRoles(userId, roles) {
            const container = document.getElementById(`roles_${userId}`);
            if (roles.length === 0) {
                container.innerHTML = '<span style="color: #999;">No roles assigned</span>';
                return;
            }

            container.innerHTML = roles.map(role => {
                const badgeClass = `badge-${role.role_name.toLowerCase()}`;
                return `<span class="badge ${badgeClass}">${role.role_name}</span>`;
            }).join('');
        }

        function manageUser(userId, userName) {
            document.getElementById('currentUserId').value = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('manageModal').classList.add('active');

            // Load user's current permissions
            loadUserPermissionsForEdit(userId);
        }

        function loadUserPermissionsForEdit(userId) {
            // Display roles
            const rolesContainer = document.getElementById('rolesContainer');
            rolesContainer.innerHTML = allRoles.map(role => `
                <div class="role-checkbox">
                    <input type="checkbox" id="role_${role.role_id}" value="${role.role_id}" onchange="roleChanged()">
                    <label for="role_${role.role_id}">
                        <strong>${role.role_name}</strong>
                        <div class="role-description">${role.description}</div>
                    </label>
                </div>
            `).join('');

            // Display permissions by category
            const permissionsContainer = document.getElementById('permissionsContainer');
            let permissionsHTML = '';

            for (const [category, perms] of Object.entries(allPermissions)) {
                permissionsHTML += `
                    <div class="permission-category">
                        <h4>${category}</h4>
                        <div class="permissions-grid">
                            ${perms.map(perm => `
                                <div class="permission-item">
                                    <input type="checkbox" id="perm_${perm.permission_id}" value="${perm.permission_id}">
                                    <label for="perm_${perm.permission_id}">${perm.permission_name}</label>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            permissionsContainer.innerHTML = permissionsHTML;

            // Get user's current permissions and check the boxes
            fetch(`../api/get-user-permissions.php?user_id=${userId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Check assigned roles
                        data.roles.forEach(role => {
                            const checkbox = document.getElementById(`role_${role.role_id}`);
                            if (checkbox) checkbox.checked = true;
                        });

                        // Check individual permissions
                        data.overrides.forEach(override => {
                            const checkbox = document.getElementById(`perm_${override.permission_id}`);
                            if (checkbox) {
                                checkbox.checked = override.is_granted == 1;
                            }
                        });
                    }
                });
        }

        function roleChanged() {
            // You can add logic here to show/hide permissions based on selected roles
        }

        function closeManageModal() {
            document.getElementById('manageModal').classList.remove('active');
        }

        function savePermissions() {
            const userId = document.getElementById('currentUserId').value;

            // Get selected roles
            const selectedRoles = [];
            allRoles.forEach(role => {
                const checkbox = document.getElementById(`role_${role.role_id}`);
                if (checkbox && checkbox.checked) {
                    selectedRoles.push(role.role_id);
                }
            });

            // Get selected permissions
            const selectedPermissions = [];
            for (const [category, perms] of Object.entries(allPermissions)) {
                perms.forEach(perm => {
                    const checkbox = document.getElementById(`perm_${perm.permission_id}`);
                    if (checkbox && checkbox.checked) {
                        selectedPermissions.push(perm.permission_id);
                    }
                });
            }

            // Save roles
            const rolePromises = selectedRoles.map(roleId => {
                return fetch('../api/assign-user-role.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, role_id: roleId})
                });
            });

            // Save permissions
            const permPromises = selectedPermissions.map(permId => {
                return fetch('../api/update-user-permission.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, permission_id: permId, is_granted: true})
                });
            });

            Promise.all([...rolePromises, ...permPromises])
                .then(() => {
                    alert('Permissions saved successfully!');
                    closeManageModal();
                    loadUserRoles();
                })
                .catch(err => {
                    console.error(err);
                    alert('Failed to save permissions');
                });
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('active');
            }
        });
    </script>
</body>
</html>

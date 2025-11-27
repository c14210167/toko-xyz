/**
 * Suppliers Management JavaScript
 */

let currentSupplierId = null;

// Load suppliers from API
async function loadSuppliers() {
    const grid = document.getElementById('suppliersGrid');
    grid.innerHTML = '<div class="loading-state">Loading suppliers...</div>';

    try {
        const response = await fetch('api/get-suppliers.php');
        const data = await response.json();

        if (data.success) {
            renderSuppliers(data.suppliers);
        } else {
            grid.innerHTML = '<div class="empty-state"><p>Error loading suppliers</p></div>';
        }
    } catch (error) {
        console.error('Error:', error);
        grid.innerHTML = '<div class="empty-state"><p>Failed to load suppliers</p></div>';
    }
}

// Render suppliers grid
function renderSuppliers(suppliers) {
    const grid = document.getElementById('suppliersGrid');

    if (suppliers.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">🏢</div>
                <h3>No Suppliers Yet</h3>
                <p>Click "Add New Supplier" to get started</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = suppliers.map(supplier => `
        <div class="supplier-card">
            <div class="supplier-icon">🏢</div>
            <h3 class="supplier-name">${escapeHtml(supplier.name)}</h3>
            <p class="supplier-description">${escapeHtml(supplier.description || 'No description provided')}</p>
            <div class="supplier-address">
                <span class="supplier-address-icon">📍</span>
                <span>${escapeHtml(supplier.address || 'No address provided')}</span>
            </div>
            <div class="supplier-meta">
                <span class="supplier-date">
                    Added ${formatDate(supplier.created_at)}
                </span>
                <div class="supplier-actions">
                    <button class="btn btn-edit" onclick="showEditModal(${supplier.supplier_id})">
                        <span>✏️</span>
                        <span>Edit</span>
                    </button>
                    <button class="btn btn-delete" onclick="showDeleteModal(${supplier.supplier_id})">
                        <span>🗑️</span>
                        <span>Delete</span>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Show add supplier modal
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Supplier';
    document.getElementById('supplierId').value = '';
    document.getElementById('supplierForm').reset();
    document.getElementById('submitText').textContent = 'Save Supplier';
    document.getElementById('supplierModal').classList.add('active');
    currentSupplierId = null;
}

// Show edit supplier modal
async function showEditModal(supplierId) {
    currentSupplierId = supplierId;

    try {
        const response = await fetch(`api/get-suppliers.php?supplier_id=${supplierId}`);
        const data = await response.json();

        if (data.success && data.supplier) {
            const supplier = data.supplier;
            document.getElementById('modalTitle').textContent = 'Edit Supplier';
            document.getElementById('supplierId').value = supplier.supplier_id;
            document.getElementById('supplierName').value = supplier.name;
            document.getElementById('supplierDescription').value = supplier.description || '';
            document.getElementById('supplierAddress').value = supplier.address || '';
            document.getElementById('submitText').textContent = 'Update Supplier';
            document.getElementById('supplierModal').classList.add('active');
        } else {
            alert('Error loading supplier data');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to load supplier data');
    }
}

// Close supplier modal
function closeModal() {
    document.getElementById('supplierModal').classList.remove('active');
    document.getElementById('supplierForm').reset();
    currentSupplierId = null;
}

// Show delete confirmation modal
function showDeleteModal(supplierId) {
    currentSupplierId = supplierId;
    document.getElementById('deleteModal').classList.add('active');
}

// Close delete modal
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    currentSupplierId = null;
}

// Confirm delete supplier
async function confirmDelete() {
    if (!currentSupplierId) return;

    try {
        const response = await fetch('api/delete-supplier.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ supplier_id: currentSupplierId })
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Supplier deleted successfully!', 'success');
            closeDeleteModal();
            loadSuppliers();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete supplier');
    }
}

// Handle form submission
document.getElementById('supplierForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const data = {
        name: formData.get('name'),
        description: formData.get('description'),
        address: formData.get('address')
    };

    const isEdit = currentSupplierId !== null;
    const endpoint = isEdit ? 'api/update-supplier.php' : 'api/create-supplier.php';

    if (isEdit) {
        data.supplier_id = currentSupplierId;
    }

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            showNotification(
                isEdit ? 'Supplier updated successfully!' : 'Supplier added successfully!',
                'success'
            );
            closeModal();
            loadSuppliers();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save supplier');
    }
});

// Show notification
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 25px;
        background: ${type === 'success' ? '#10b981' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
    if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`;

    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add animations CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

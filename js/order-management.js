/**
 * Order Management JavaScript
 * Handles order creation, editing, and spareparts management
 */

// Global variables
let currentOrderData = {};
let memberSearchTimeout = null;
let sparepartSearchTimeout = null;

// ========================================
// MODAL FUNCTIONS
// ========================================

function showMemberCheckModal() {
    document.getElementById('memberCheckModal').style.display = 'flex';
}

function closeMemberCheckModal() {
    document.getElementById('memberCheckModal').style.display = 'none';
}

function showMemberSearchModal() {
    closeMemberCheckModal();
    document.getElementById('memberSearchModal').style.display = 'flex';
    document.getElementById('memberSearchInput').focus();
}

function closeMemberSearchModal() {
    document.getElementById('memberSearchModal').style.display = 'none';
    document.getElementById('memberSearchInput').value = '';
    document.getElementById('memberSearchResults').innerHTML = '<p style="text-align: center; color: #64748b;">Type to search customers...</p>';
}

function showGuestFormModal() {
    closeMemberCheckModal();
    document.getElementById('guestFormModal').style.display = 'flex';
    document.getElementById('guestName').focus();
}

function closeGuestFormModal() {
    document.getElementById('guestFormModal').style.display = 'none';
    document.getElementById('guestForm').reset();
}

function showCreateOrderModal(customerData) {
    document.getElementById('createOrderModal').style.display = 'flex';

    // Store customer data
    currentOrderData = customerData;

    // Set hidden fields
    if (customerData.is_member) {
        document.getElementById('orderIsMember').value = 'true';
        document.getElementById('orderCustomerId').value = customerData.customer_id;
        document.getElementById('customerInfoDisplay').innerHTML = `
            <strong>👤 Member:</strong> ${customerData.full_name}<br>
            <strong>📧 Email:</strong> ${customerData.email || 'N/A'}<br>
            <strong>📱 Phone:</strong> ${customerData.phone}
        `;
    } else {
        document.getElementById('orderIsMember').value = 'false';
        document.getElementById('orderGuestName').value = customerData.guest_name;
        document.getElementById('orderGuestPhone').value = customerData.guest_phone;
        document.getElementById('orderGuestEmail').value = customerData.guest_email || '';
        document.getElementById('customerInfoDisplay').innerHTML = `
            <strong>👥 Guest:</strong> ${customerData.guest_name}<br>
            <strong>📱 Phone:</strong> ${customerData.guest_phone}<br>
            <strong>📧 Email:</strong> ${customerData.guest_email || 'N/A'}
        `;
    }
}

function closeCreateOrderModal() {
    document.getElementById('createOrderModal').style.display = 'none';
    document.getElementById('createOrderForm').reset();
    currentOrderData = {};
}

function closeEditOrderModal() {
    document.getElementById('editOrderModal').style.display = 'none';
}

function showAddSparepartModal() {
    document.getElementById('addSparepartModal').style.display = 'flex';
    document.getElementById('sparepartSearchInput').focus();
}

function closeAddSparepartModal() {
    document.getElementById('addSparepartModal').style.display = 'none';
    document.getElementById('sparepartSearchInput').value = '';
    document.getElementById('sparepartSearchResults').innerHTML = '<p style="text-align: center; color: #64748b;">Type to search inventory items...</p>';
    document.getElementById('selectedSparepartInfo').style.display = 'none';
    document.getElementById('addSparepartForm').style.display = 'none';
}

function showAddCustomCostModal() {
    document.getElementById('addCustomCostModal').style.display = 'flex';
    document.getElementById('customCostName').focus();
}

function closeAddCustomCostModal() {
    document.getElementById('addCustomCostModal').style.display = 'none';
    document.getElementById('addCustomCostForm').reset();
}

// ========================================
// SEARCH FUNCTIONS
// ========================================

function searchMembers() {
    const searchTerm = document.getElementById('memberSearchInput').value.trim();

    if (searchTerm.length < 2) {
        document.getElementById('memberSearchResults').innerHTML = '<p style="text-align: center; color: #64748b;">Type at least 2 characters...</p>';
        return;
    }

    // Debounce
    clearTimeout(memberSearchTimeout);
    memberSearchTimeout = setTimeout(async () => {
        document.getElementById('memberSearchResults').innerHTML = '<p style="text-align: center; color: #64748b;">Searching...</p>';

        try {
            const response = await fetch(`api/search-customers.php?search=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();

            if (result.success && result.customers.length > 0) {
                let html = '<div class="customer-list">';
                result.customers.forEach(customer => {
                    html += `
                        <div class="customer-card" onclick="selectMember(${customer.user_id}, '${customer.full_name}', '${customer.email}', '${customer.phone}')" style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s;">
                            <div style="font-weight: 600; font-size: 16px; margin-bottom: 5px;">${customer.full_name}</div>
                            <div style="font-size: 14px; color: #64748b;">
                                📧 ${customer.email || 'No email'} | 📱 ${customer.phone}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('memberSearchResults').innerHTML = html;

                // Add hover effect
                const cards = document.querySelectorAll('.customer-card');
                cards.forEach(card => {
                    card.addEventListener('mouseenter', function() {
                        this.style.background = '#f1f5f9';
                        this.style.borderColor = '#3b82f6';
                    });
                    card.addEventListener('mouseleave', function() {
                        this.style.background = 'white';
                        this.style.borderColor = '#e2e8f0';
                    });
                });
            } else {
                document.getElementById('memberSearchResults').innerHTML = '<p style="text-align: center; color: #ef4444;">No customers found</p>';
            }
        } catch (error) {
            document.getElementById('memberSearchResults').innerHTML = '<p style="text-align: center; color: #ef4444;">Error searching customers</p>';
        }
    }, 300);
}

function selectMember(userId, fullName, email, phone) {
    closeMemberSearchModal();
    showCreateOrderModal({
        is_member: true,
        customer_id: userId,
        full_name: fullName,
        email: email,
        phone: phone
    });
}

function searchSpareparts() {
    const searchTerm = document.getElementById('sparepartSearchInput').value.trim();

    if (searchTerm.length < 2) {
        document.getElementById('sparepartSearchResults').innerHTML = '<p style="text-align: center; color: #64748b;">Type at least 2 characters...</p>';
        return;
    }

    clearTimeout(sparepartSearchTimeout);
    sparepartSearchTimeout = setTimeout(async () => {
        document.getElementById('sparepartSearchResults').innerHTML = '<p style="text-align: center; color: #64748b;">Searching...</p>';

        try {
            const response = await fetch(`api/search-inventory-items.php?search=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();

            if (result.success && result.items.length > 0) {
                let html = '<div class="sparepart-list">';
                result.items.forEach(item => {
                    html += `
                        <div class="sparepart-card" onclick='selectSparepart(${JSON.stringify(item)})' style="border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <div style="font-weight: 600;">${item.name}</div>
                                    <div style="font-size: 13px; color: #64748b;">Code: ${item.item_code} | Category: ${item.category_name || 'N/A'}</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-weight: 600; color: #059669;">Rp ${parseInt(item.unit_price).toLocaleString('id-ID')}</div>
                                    <div style="font-size: 13px; color: ${item.quantity < 10 ? '#ef4444' : '#64748b'};">
                                        Stock: ${item.quantity} ${item.unit}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('sparepartSearchResults').innerHTML = html;

                // Add hover effect
                const cards = document.querySelectorAll('.sparepart-card');
                cards.forEach(card => {
                    card.addEventListener('mouseenter', function() {
                        this.style.background = '#f1f5f9';
                        this.style.borderColor = '#3b82f6';
                    });
                    card.addEventListener('mouseleave', function() {
                        this.style.background = 'white';
                        this.style.borderColor = '#e2e8f0';
                    });
                });
            } else {
                document.getElementById('sparepartSearchResults').innerHTML = '<p style="text-align: center; color: #ef4444;">No items found</p>';
            }
        } catch (error) {
            document.getElementById('sparepartSearchResults').innerHTML = '<p style="text-align: center; color: #ef4444;">Error searching items</p>';
        }
    }, 300);
}

function selectSparepart(item) {
    document.getElementById('selectedItemId').value = item.item_id;
    document.getElementById('selectedSparepartInfo').style.display = 'block';
    document.getElementById('selectedSparepartInfo').innerHTML = `
        <div style="font-weight: 600; font-size: 16px; margin-bottom: 8px;">${item.name}</div>
        <div style="display: flex; justify-content: space-between; font-size: 14px;">
            <span>Code: ${item.item_code}</span>
            <span>Price: <strong>Rp ${parseInt(item.unit_price).toLocaleString('id-ID')}</strong></span>
        </div>
        <div style="font-size: 14px; color: ${item.quantity < 10 ? '#ef4444' : '#059669'}; margin-top: 5px;">
            Available Stock: <strong>${item.quantity} ${item.unit}</strong>
        </div>
    `;
    document.getElementById('addSparepartForm').style.display = 'block';
    document.getElementById('sparepartQuantity').max = item.quantity;
    document.getElementById('sparepartQuantity').focus();
}

// ========================================
// FORM SUBMISSION FUNCTIONS
// ========================================

async function submitGuestInfo(event) {
    event.preventDefault();

    const guestData = {
        is_member: false,
        guest_name: document.getElementById('guestName').value,
        guest_phone: document.getElementById('guestPhone').value,
        guest_email: document.getElementById('guestEmail').value
    };

    closeGuestFormModal();
    showCreateOrderModal(guestData);
}

async function submitCreateOrder(event) {
    event.preventDefault();

    const isMember = document.getElementById('orderIsMember').value === 'true';
    const orderData = {
        is_member: isMember,
        service_type: document.getElementById('serviceType').value,
        device_type: document.getElementById('deviceType').value,
        brand: document.getElementById('deviceBrand').value,
        model: document.getElementById('deviceModel').value,
        serial_number: document.getElementById('serialNumber').value,
        problem_description: document.getElementById('problemDescription').value,
        location_id: document.getElementById('orderLocation').value,
        priority: document.getElementById('orderPriority').value
    };

    if (isMember) {
        orderData.customer_id = document.getElementById('orderCustomerId').value;
    } else {
        orderData.guest_name = document.getElementById('orderGuestName').value;
        orderData.guest_phone = document.getElementById('orderGuestPhone').value;
        orderData.guest_email = document.getElementById('orderGuestEmail').value;
    }

    try {
        const response = await fetch('api/create-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ Order created successfully!\n\nOrder Number: ' + result.order_number);
            closeCreateOrderModal();
            // Reload orders table
            if (typeof loadOrders === 'function') {
                loadOrders();
            } else {
                location.reload();
            }
        } else {
            alert('❌ Error: ' + result.message);
        }
    } catch (error) {
        alert('❌ Network error. Please try again.');
    }
}

// ========================================
// EDIT ORDER FUNCTIONS
// ========================================

async function openEditOrderModal(orderId) {
    document.getElementById('editOrderModal').style.display = 'flex';
    document.getElementById('editOrderId').value = orderId;

    // Load order details
    try {
        const response = await fetch(`api/get-order-detail.php?order_id=${orderId}`);
        const result = await response.json();

        if (result.success) {
            document.getElementById('editOrderNumber').textContent = result.order.order_number;

            // Display order info
            document.getElementById('orderInfoDisplay').innerHTML = `
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <strong>Customer:</strong> ${result.order.customer_name}<br>
                        <strong>Phone:</strong> ${result.order.customer_phone}<br>
                        <strong>Email:</strong> ${result.order.customer_email || 'N/A'}
                    </div>
                    <div>
                        <strong>Device:</strong> ${result.order.device_type}<br>
                        <strong>Brand/Model:</strong> ${result.order.brand || 'N/A'} ${result.order.model || ''}<br>
                        <strong>Status:</strong> <span style="color: #059669;">${result.order.status.toUpperCase()}</span>
                    </div>
                </div>
                <div style="margin-top: 15px;">
                    <strong>Problem:</strong> ${result.order.problem_description}
                </div>
            `;

            // Set service cost and discount
            document.getElementById('serviceCostInput').value = result.summary.service_cost || 0;
            document.getElementById('discountInput').value = result.summary.discount || 0;

            // Set warranty status
            document.getElementById('warrantyCheckbox').checked = result.order.warranty_status == 1;

            // Load spareparts
            loadSparepartsTable(result.spareparts);

            // Load custom costs
            loadCustomCostsTable(result.custom_costs);

            // Update summary
            updateTotalSummary(result.summary);
        } else {
            console.error('Order detail error:', result);
            alert('Error loading order details: ' + (result.message || 'Unknown error'));
            if (result.error_detail) {
                console.error('Detail:', result.error_detail);
            }
            closeEditOrderModal();
        }
    } catch (error) {
        console.error('Network error:', error);
        alert('Network error: ' + error.message);
        closeEditOrderModal();
    }
}

function loadSparepartsTable(spareparts) {
    const tbody = document.getElementById('sparepartsTableBody');

    if (!spareparts || spareparts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b;">No spareparts added yet</td></tr>';
        return;
    }

    let html = '';
    spareparts.forEach(item => {
        html += `
            <tr>
                <td>${item.item_code}</td>
                <td>${item.item_name}</td>
                <td>${item.quantity}</td>
                <td>Rp ${parseInt(item.unit_price).toLocaleString('id-ID')}</td>
                <td><strong>Rp ${parseInt(item.subtotal).toLocaleString('id-ID')}</strong></td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="removeSparepart(${item.transaction_id})">
                        Remove
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function loadCustomCostsTable(customCosts) {
    const tbody = document.getElementById('customCostsTableBody');

    if (!customCosts || customCosts.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: #64748b;">No custom costs added yet</td></tr>';
        return;
    }

    let html = '';
    customCosts.forEach(cost => {
        html += `
            <tr>
                <td>${cost.name}</td>
                <td>${cost.description || '-'}</td>
                <td><strong>Rp ${parseInt(cost.amount).toLocaleString('id-ID')}</strong></td>
                <td>
                    <button class="btn btn-danger btn-sm" onclick="removeCustomCost('${cost.id}')">
                        Remove
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function updateTotalSummary(summary) {
    document.getElementById('summaryServiceCost').textContent = 'Rp ' + parseInt(summary.service_cost || 0).toLocaleString('id-ID');
    document.getElementById('summarySpareparts').textContent = 'Rp ' + parseInt(summary.spareparts_total || 0).toLocaleString('id-ID');
    document.getElementById('summaryCustomCosts').textContent = 'Rp ' + parseInt(summary.custom_costs_total || 0).toLocaleString('id-ID');
    document.getElementById('summaryDiscount').textContent = '- Rp ' + parseInt(summary.discount || 0).toLocaleString('id-ID');
    document.getElementById('summaryTotal').textContent = 'Rp ' + parseInt(summary.total_cost || 0).toLocaleString('id-ID');
}

async function updateServiceCost() {
    const orderId = document.getElementById('editOrderId').value;
    const serviceCost = document.getElementById('serviceCostInput').value;
    const discount = document.getElementById('discountInput').value;

    try {
        const response = await fetch('api/update-service-cost.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                service_cost: parseFloat(serviceCost),
                discount: parseFloat(discount)
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ Service cost updated!');
            // Reload order details
            openEditOrderModal(orderId);
        } else {
            alert('❌ Error: ' + result.message);
        }
    } catch (error) {
        alert('❌ Network error');
    }
}

async function updateWarrantyStatus() {
    const orderId = document.getElementById('editOrderId').value;
    const warrantyCheckbox = document.getElementById('warrantyCheckbox');
    const warrantyStatus = warrantyCheckbox.checked ? 1 : 0;

    try {
        const response = await fetch('api/update-warranty.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                warranty_status: warrantyStatus
            })
        });

        const result = await response.json();

        if (result.success) {
            const statusText = warrantyStatus ? 'enabled' : 'disabled';
            alert(`✅ Warranty ${statusText}!`);
        } else {
            alert('❌ Error: ' + result.error);
            // Revert checkbox state on error
            warrantyCheckbox.checked = !warrantyCheckbox.checked;
        }
    } catch (error) {
        alert('❌ Network error');
        // Revert checkbox state on error
        warrantyCheckbox.checked = !warrantyCheckbox.checked;
    }
}

async function submitAddSparepart(event) {
    event.preventDefault();

    const orderId = document.getElementById('editOrderId').value;
    const itemId = document.getElementById('selectedItemId').value;
    const quantity = document.getElementById('sparepartQuantity').value;
    const notes = document.getElementById('sparepartNotes').value;

    try {
        const response = await fetch('api/add-sparepart-to-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                item_id: itemId,
                quantity: parseInt(quantity),
                notes: notes
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ Sparepart added to order!');
            closeAddSparepartModal();
            // Reload order details
            openEditOrderModal(orderId);
        } else {
            alert('❌ Error: ' + result.message);
        }
    } catch (error) {
        alert('❌ Network error');
    }
}

async function removeSparepart(transactionId) {
    if (!confirm('Remove this sparepart from order? Stock will be returned to inventory.')) {
        return;
    }

    const orderId = document.getElementById('editOrderId').value;

    try {
        const response = await fetch('api/remove-sparepart-from-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                transaction_id: transactionId
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ Sparepart removed!');
            openEditOrderModal(orderId);
        } else {
            alert('❌ Error: ' + result.message);
        }
    } catch (error) {
        alert('❌ Network error');
    }
}

async function submitAddCustomCost(event) {
    event.preventDefault();

    const orderId = document.getElementById('editOrderId').value;
    const costName = document.getElementById('customCostName').value;
    const costDescription = document.getElementById('customCostDescription').value;
    const costAmount = document.getElementById('customCostAmount').value;

    try {
        const response = await fetch('api/add-custom-cost.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                cost_name: costName,
                cost_description: costDescription,
                cost_amount: parseFloat(costAmount)
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ Custom cost added!');
            closeAddCustomCostModal();
            openEditOrderModal(orderId);
        } else {
            alert('❌ Error: ' + result.message);
        }
    } catch (error) {
        alert('❌ Network error');
    }
}

async function removeCustomCost(costId) {
    if (!confirm('Remove this custom cost?')) {
        return;
    }

    const orderId = document.getElementById('editOrderId').value;

    try {
        const response = await fetch('api/remove-custom-cost.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                order_id: orderId,
                cost_id: costId
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('✅ Custom cost removed!');
            openEditOrderModal(orderId);
        } else {
            alert('❌ Error: ' + result.message);
        }
    } catch (error) {
        alert('❌ Network error');
    }
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const modals = ['memberCheckModal', 'memberSearchModal', 'guestFormModal', 'createOrderModal', 'editOrderModal', 'addSparepartModal', 'addCustomCostModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

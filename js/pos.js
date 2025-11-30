// POS JavaScript

let cart = [];
let products = [];
let customers = [];

// Initialize based on session status
document.addEventListener('DOMContentLoaded', () => {
    if (hasActiveSession) {
        initPOS();
    } else {
        initSessionStart();
    }
});

// ========================================
// SESSION START
// ========================================
function initSessionStart() {
    const form = document.getElementById('startSessionForm');
    if (form) {
        form.addEventListener('submit', handleStartSession);
    }
}

async function handleStartSession(e) {
    e.preventDefault();

    const openingBalance = document.getElementById('openingBalance').value;
    const notes = document.getElementById('sessionNotes').value;

    try {
        const response = await fetch('api/start-pos-session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                opening_balance: parseFloat(openingBalance),
                notes: notes
            })
        });

        const data = await response.json();

        if (data.success) {
            showSuccess('Session started successfully!');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showError(data.error || 'Failed to start session');
        }
    } catch (error) {
        showError('Failed to start session');
        console.error(error);
    }
}

// ========================================
// POS INITIALIZATION
// ========================================
function initPOS() {
    loadProducts();
    loadCustomers();
    setupProductSearch();
}

async function loadProducts() {
    try {
        const response = await fetch('api/get-pos-products.php');
        const data = await response.json();

        if (data.success) {
            products = data.products;
            displayProducts(products);
        } else {
            showError(data.error || 'Failed to load products');
        }
    } catch (error) {
        showError('Failed to load products');
        console.error(error);
    }
}

function displayProducts(productsToDisplay) {
    const grid = document.getElementById('productGrid');

    if (productsToDisplay.length === 0) {
        grid.innerHTML = '<div class="loading-spinner">No products available</div>';
        return;
    }

    grid.innerHTML = productsToDisplay.map(product => `
        <div class="product-item" onclick='addToCart(${JSON.stringify(product)})'>
            <div class="product-item-name">${escapeHtml(product.product_name)}</div>
            <div class="product-item-sku">${escapeHtml(product.sku)}</div>
            <div class="product-item-price"> Rp${formatNumber(product.price)}</div>
            <div class="product-item-stock ${getStockClass(product.stock_status)}">
                Stock: ${product.stock}
            </div>
        </div>
    `).join('');
}

function getStockClass(status) {
    return status.toLowerCase().replace(' ', '-');
}

function setupProductSearch() {
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filtered = products.filter(p =>
                p.product_name.toLowerCase().includes(searchTerm) ||
                p.sku.toLowerCase().includes(searchTerm) ||
                p.category.toLowerCase().includes(searchTerm)
            );
            displayProducts(filtered);
        });
    }
}

async function loadCustomers() {
    try {
        const response = await fetch('api/get-pos-customers.php');
        const data = await response.json();

        if (data.success) {
            customers = data.customers;
            setupCustomerAutocomplete();
        }
    } catch (error) {
        console.error('Failed to load customers:', error);
    }
}

function setupCustomerAutocomplete() {
    const searchInput = document.getElementById('customerSearch');
    const suggestionsDiv = document.getElementById('customerSuggestions');
    const customerIdInput = document.getElementById('customerId');

    if (!searchInput || !suggestionsDiv) return;

    // Handle input changes
    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase().trim();

        if (searchTerm.length === 0) {
            suggestionsDiv.style.display = 'none';
            customerIdInput.value = '';
            return;
        }

        // Filter customers by name or phone
        const filtered = customers.filter(customer =>
            customer.customer_name.toLowerCase().includes(searchTerm) ||
            customer.phone.includes(searchTerm)
        );

        if (filtered.length === 0) {
            suggestionsDiv.innerHTML = '<div class="no-customer-found">No customers found</div>';
            suggestionsDiv.style.display = 'block';
            customerIdInput.value = '';
            return;
        }

        // Display suggestions
        suggestionsDiv.innerHTML = filtered.map(customer => `
            <div class="customer-suggestion-item" data-customer-id="${customer.user_id}" data-customer-name="${escapeHtml(customer.customer_name)}">
                <div class="customer-suggestion-name">${escapeHtml(customer.customer_name)}</div>
                <div class="customer-suggestion-details">${escapeHtml(customer.phone)} | ${escapeHtml(customer.email)}</div>
            </div>
        `).join('');

        suggestionsDiv.style.display = 'block';

        // Add click handlers to suggestions
        suggestionsDiv.querySelectorAll('.customer-suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                const customerId = item.getAttribute('data-customer-id');
                const customerName = item.getAttribute('data-customer-name');

                searchInput.value = customerName;
                customerIdInput.value = customerId;
                suggestionsDiv.style.display = 'none';
            });
        });
    });

    // Close suggestions when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.style.display = 'none';
        }
    });

    // Handle focus to show suggestions if there's a search term
    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length > 0) {
            const event = new Event('input');
            searchInput.dispatchEvent(event);
        }
    });
}

// ========================================
// CART MANAGEMENT
// ========================================
function addToCart(product) {
    const existingItem = cart.find(item => item.product_id === product.product_id);

    if (existingItem) {
        if (existingItem.quantity < product.stock) {
            existingItem.quantity++;
        } else {
            showError('Cannot add more than available stock');
            return;
        }
    } else {
        cart.push({
            ...product,
            quantity: 1
        });
    }

    updateCart();
}

function updateCartQuantity(productId, change) {
    const item = cart.find(item => item.product_id === productId);
    if (!item) return;

    const newQuantity = item.quantity + change;

    if (newQuantity <= 0) {
        removeFromCart(productId);
        return;
    }

    const product = products.find(p => p.product_id === productId);
    if (newQuantity > product.stock) {
        showError('Cannot exceed available stock');
        return;
    }

    item.quantity = newQuantity;
    updateCart();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.product_id !== productId);
    updateCart();
}

function clearCart() {
    if (cart.length === 0) return;

    if (confirm('Clear all items from cart?')) {
        cart = [];
        updateCart();
    }
}

function updateCart() {
    const cartItems = document.getElementById('cartItems');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart">
                <p>Cart is empty</p>
                <small>Scan or select products to add</small>
            </div>
        `;
        checkoutBtn.disabled = true;
    } else {
        cartItems.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${escapeHtml(item.product_name)}</div>
                    <div class="cart-item-price">Rp ${formatNumber(item.price)} × ${item.quantity}</div>
                </div>
                <div class="cart-item-quantity">
                    <button class="qty-btn" onclick="updateCartQuantity(${item.product_id}, -1)">−</button>
                    <span class="qty-value">${item.quantity}</span>
                    <button class="qty-btn" onclick="updateCartQuantity(${item.product_id}, 1)">+</button>
                </div>
                <button class="cart-item-remove" onclick="removeFromCart(${item.product_id})">×</button>
            </div>
        `).join('');
        checkoutBtn.disabled = false;
    }

    updateCartSummary();
}

function updateCartSummary() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const discount = 0; // Can be implemented later
    const total = subtotal - discount;

    document.getElementById('cartSubtotal').textContent = `Rp ${formatNumber(subtotal)}`;
    document.getElementById('cartDiscount').textContent = `Rp ${formatNumber(discount)}`;
    document.getElementById('cartTotal').textContent = `Rp ${formatNumber(total)}`;
}

// ========================================
// PAYMENT
// ========================================
function showPaymentModal() {
    if (cart.length === 0) {
        showError('Cart is empty');
        return;
    }

    const modal = document.getElementById('paymentModal');
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    document.getElementById('paymentTotal').textContent = `Rp ${formatNumber(total)}`;
    document.getElementById('cashReceived').value = '';
    document.querySelector('input[name="payment_method"][value="cash"]').checked = true;

    // Reset customer search
    document.getElementById('customerSearch').value = '';
    document.getElementById('customerId').value = '';
    document.getElementById('customerSuggestions').style.display = 'none';

    updatePaymentFields();

    modal.style.display = 'flex';
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

function updatePaymentFields() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const cashFields = document.getElementById('cashPaymentFields');

    if (paymentMethod === 'cash') {
        cashFields.style.display = 'block';
    } else {
        cashFields.style.display = 'none';
    }
}

function calculateChange() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
    const change = cashReceived - total;

    const changeDisplay = document.getElementById('changeDisplay');
    const changeAmount = changeDisplay.querySelector('.amount');

    if (change >= 0) {
        changeAmount.textContent = `Rp ${formatNumber(change)}`;
        changeDisplay.style.background = '#d1fae5';
        changeDisplay.style.color = '#065f46';
    } else {
        changeAmount.textContent = `Rp ${formatNumber(Math.abs(change))} short`;
        changeDisplay.style.background = '#fee2e2';
        changeDisplay.style.color = '#991b1b';
    }
}

async function processPayment() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    let transactionData = {
        items: cart.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity
        })),
        payment_method: paymentMethod,
        customer_id: document.getElementById('customerId').value || null,
        notes: document.getElementById('paymentNotes').value
    };

    // Validate cash payment
    if (paymentMethod === 'cash') {
        const cashReceived = parseFloat(document.getElementById('cashReceived').value) || 0;
        if (cashReceived < total) {
            showError('Insufficient cash received');
            return;
        }
        transactionData.cash_received = cashReceived;
    }

    try {
        const response = await fetch('api/create-pos-transaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(transactionData)
        });

        const data = await response.json();

        if (data.success) {
            showSuccess(`Sale completed! Transaction: ${data.transaction.transaction_number}`);
            closePaymentModal();

            // Clear cart
            cart = [];
            updateCart();

            // Reload products to update stock
            loadProducts();

            // Show receipt option
            if (data.transaction.change_amount > 0) {
                setTimeout(() => {
                    alert(`Change: Rp ${formatNumber(data.transaction.change_amount)}`);
                }, 500);
            }
        } else {
            showError(data.error || 'Failed to process payment');
        }
    } catch (error) {
        showError('Failed to process payment');
        console.error(error);
    }
}

// ========================================
// END SESSION
// ========================================
function showEndSessionModal() {
    if (cart.length > 0) {
        if (!confirm('You have items in cart. Are you sure you want to end session?')) {
            return;
        }
    }

    loadSessionSummary();
    document.getElementById('endSessionModal').style.display = 'flex';
}

function closeEndSessionModal() {
    document.getElementById('endSessionModal').style.display = 'none';
}

async function loadSessionSummary() {
    try {
        const response = await fetch('api/get-session-summary.php');
        const data = await response.json();

        if (data.success) {
            const session = data.session;

            document.getElementById('summaryOpeningBalance').textContent = `Rp ${formatNumber(session.opening_balance)}`;
            document.getElementById('summaryTransactions').textContent = session.transaction_count;
            document.getElementById('summaryCashSales').textContent = `Rp ${formatNumber(session.cash_sales)}`;
            document.getElementById('summaryCardSales').textContent = `Rp ${formatNumber(session.card_sales)}`;
            document.getElementById('summaryQrisSales').textContent = `Rp ${formatNumber(session.qris_sales)}`;
            document.getElementById('summaryExpectedBalance').textContent = `Rp ${formatNumber(session.expected_balance)}`;

            // Pre-fill closing balance with expected
            document.getElementById('closingBalance').value = session.expected_balance;
        } else {
            showError(data.error || 'Failed to load session summary');
        }
    } catch (error) {
        showError('Failed to load session summary');
        console.error(error);
    }
}

async function endSession() {
    const closingBalance = document.getElementById('closingBalance').value;
    const notes = document.getElementById('closingNotes').value;

    if (!closingBalance) {
        showError('Please enter closing balance');
        return;
    }

    if (!confirm('Are you sure you want to end this session?')) {
        return;
    }

    try {
        const response = await fetch('api/end-pos-session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                closing_balance: parseFloat(closingBalance),
                notes: notes
            })
        });

        const data = await response.json();

        if (data.success) {
            const summary = data.session_summary;
            const variance = summary.variance;

            let message = `Session ended successfully!\n\n`;
            message += `Total Sales: Rp ${formatNumber(summary.total_sales)}\n`;
            message += `Transactions: ${summary.total_transactions}\n`;
            message += `Variance: Rp ${formatNumber(Math.abs(variance))} `;
            message += variance >= 0 ? '(Over)' : '(Short)';

            alert(message);

            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showError(data.error || 'Failed to end session');
        }
    } catch (error) {
        showError('Failed to end session');
        console.error(error);
    }
}

// ========================================
// UTILITY FUNCTIONS
// ========================================
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

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

// Close modals when clicking outside
window.onclick = function(event) {
    const paymentModal = document.getElementById('paymentModal');
    const endSessionModal = document.getElementById('endSessionModal');

    if (event.target === paymentModal) {
        closePaymentModal();
    }
    if (event.target === endSessionModal) {
        closeEndSessionModal();
    }
}

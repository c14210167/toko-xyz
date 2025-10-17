function showOrderDetail(orderNumber) {
    const modal = document.getElementById('modalOverlay');
    const modalBody = document.getElementById('modalBody');
    
    // Show loading
    modalBody.innerHTML = '<div class="loading">Loading...</div>';
    modal.classList.add('active');
    
    // Fetch order detail from API
    fetch(`api/get-order-detail.php?order_number=${orderNumber}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderOrderDetail(data.order, data.timeline, data.costs);
            } else {
                modalBody.innerHTML = '<p style="color: white;">Error: ' + data.message + '</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<p style="color: white;">Error loading order details</p>';
        });
}

function renderOrderDetail(order, timeline, costs) {
    const modalBody = document.getElementById('modalBody');
    
    const formatRupiah = (number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(number);
    };
    
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    };
    
    const formatDateTime = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    
    const getStatusClass = (status) => {
        const classes = {
            'pending': 'status-waiting',
            'in_progress': 'status-progress',
            'waiting_parts': 'status-waiting',
            'completed': 'status-completed',
            'cancelled': 'status-cancelled'
        };
        return classes[status] || 'status-waiting';
    };
    
    const getStatusText = (status) => {
        const texts = {
            'pending': 'Menunggu',
            'in_progress': 'Dalam Proses',
            'waiting_parts': 'Menunggu Spare Part',
            'completed': 'Selesai',
            'cancelled': 'Dibatalkan'
        };
        return texts[status] || status;
    };

    modalBody.innerHTML = `
        <div class="detail-header">
            <h2>${order.order_number}</h2>
            <span class="detail-status ${getStatusClass(order.status)}">${getStatusText(order.status)}</span>
        </div>
        <div class="detail-section">
            <h3>Informasi Service</h3>
            <div class="detail-info">
                <p><strong>Perangkat:</strong> ${order.device_type.charAt(0).toUpperCase() + order.device_type.slice(1)}</p>
                <p><strong>Brand:</strong> ${order.brand}</p>
                <p><strong>Model:</strong> ${order.model}</p>
                ${order.serial_number ? `<p><strong>Serial Number:</strong> ${order.serial_number}</p>` : ''}
                <p><strong>Kerusakan:</strong> ${order.issue_type}</p>
                <p><strong>Tanggal Masuk:</strong> ${formatDate(order.created_at)}</p>
                <p><strong>Lokasi:</strong> ${order.location_name}</p>
                ${order.technician_name ? `<p><strong>Teknisi:</strong> ${order.technician_name}</p>` : ''}
                ${order.estimated_completion ? `<p><strong>Estimasi Selesai:</strong> ${formatDate(order.estimated_completion)}</p>` : ''}
                <p><strong>Garansi:</strong> ${order.warranty_status == 1 ? 'Ya' : 'Tidak'}</p>
            </div>
        </div>
        ${costs ? `
        <div class="detail-section">
            <h3>Rincian Biaya</h3>
            <div class="cost-breakdown">
                <div class="cost-item">
                    <span>Biaya Spare Part</span>
                    <span>${formatRupiah(costs.spareparts_cost)}</span>
                </div>
                <div class="cost-item">
                    <span>Biaya Servis & Perbaikan</span>
                    <span>${formatRupiah(costs.service_cost)}</span>
                </div>
                <div class="cost-item">
                    <span>Biaya Lainnya</span>
                    <span>${formatRupiah(costs.other_cost)}</span>
                </div>
                <div class="cost-total">
                    <span><strong>Total Biaya</strong></span>
                    <span><strong>${formatRupiah(costs.total_cost)}</strong></span>
                </div>
            </div>
            ${costs.notes ? `<p class="cost-notes">${costs.notes}</p>` : ''}
        </div>
        ` : ''}
        ${order.additional_notes ? `
        <div class="detail-section">
            <h3>Catatan Tambahan</h3>
            <p class="detail-notes">${order.additional_notes}</p>
        </div>
        ` : ''}
        <div class="detail-section">
            <h3>Timeline Service</h3>
            <div class="timeline">
                ${timeline.map(item => `
                    <div class="timeline-item ${item.status}">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4>${item.event_name}</h4>
                            <p>${formatDateTime(item.event_date)}</p>
                            ${item.notes ? `<p class="timeline-notes">${item.notes}</p>` : ''}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

// Close modal
const modalClose = document.getElementById('modalClose');
const modalOverlay = document.getElementById('modalOverlay');

if (modalClose) {
    modalClose.addEventListener('click', () => {
        modalOverlay.classList.remove('active');
    });
}

if (modalOverlay) {
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            modalOverlay.classList.remove('active');
        }
    });
}

// Filter functionality
const filterBtns = document.querySelectorAll('.filter-btn');
const orderCards = document.querySelectorAll('.order-card');

filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        // Remove active class from all buttons
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const filter = btn.dataset.filter;
        
        orderCards.forEach(card => {
            const status = card.dataset.status;
            
            if (filter === 'all') {
                card.style.display = 'block';
            } else if (filter === 'progress' && status === 'in_progress') {
                card.style.display = 'block';
            } else if (filter === 'completed' && status === 'completed') {
                card.style.display = 'block';
            } else if (filter === 'waiting' && (status === 'waiting_parts' || status === 'pending')) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
});
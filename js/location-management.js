/**
 * Location Management JavaScript
 */

// Load locations on page load
document.addEventListener('DOMContentLoaded', function() {
    loadLocations();
});

// Load all locations
async function loadLocations() {
    const grid = document.getElementById('locationsGrid');
    grid.innerHTML = '<div class="loading-spinner">Loading locations...</div>';

    try {
        const response = await fetch('api/get-locations.php');
        const data = await response.json();

        if (data.success) {
            renderLocations(data.locations);
        } else {
            grid.innerHTML = '<div class="empty-state"><p>Error loading locations</p></div>';
        }
    } catch (error) {
        console.error('Error loading locations:', error);
        grid.innerHTML = '<div class="empty-state"><p>Error loading locations</p></div>';
    }
}

// Render locations grid
function renderLocations(locations) {
    const grid = document.getElementById('locationsGrid');

    if (locations.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📍</div>
                <h3>No locations found</h3>
                <p>Add your first location to get started</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = locations.map(location => `
        <div class="location-card">
            <div class="location-header">
                <h3 class="location-name">${escapeHtml(location.name)}</h3>
                <div class="location-actions">
                    <button class="btn-icon btn-edit" onclick="showEditLocationModal(${location.location_id})" title="Edit">
                        ✏️
                    </button>
                    <button class="btn-icon btn-delete" onclick="confirmDeleteLocation(${location.location_id}, '${escapeHtml(location.name)}')" title="Delete">
                        🗑️
                    </button>
                </div>
            </div>

            <div class="location-info">
                ${location.address ? `
                    <div class="info-row">
                        <span class="info-icon">📍</span>
                        <span class="info-text">${escapeHtml(location.address)}</span>
                    </div>
                ` : ''}
                ${location.phone ? `
                    <div class="info-row">
                        <span class="info-icon">📞</span>
                        <span class="info-text">${escapeHtml(location.phone)}</span>
                    </div>
                ` : ''}
            </div>

            <div class="location-stats">
                <div class="stat-item">
                    <div class="stat-value">${location.order_count || 0}</div>
                    <div class="stat-label">Orders</div>
                </div>
            </div>
        </div>
    `).join('');
}

// Show add location modal
function showAddLocationModal() {
    document.getElementById('addLocationModal').style.display = 'flex';
    document.getElementById('locationName').focus();
}

// Close add location modal
function closeAddLocationModal() {
    document.getElementById('addLocationModal').style.display = 'none';
    document.getElementById('addLocationForm').reset();
}

// Handle add location form submission
async function handleAddLocation(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = {
        name: formData.get('name'),
        address: formData.get('address'),
        phone: formData.get('phone')
    };

    try {
        const response = await fetch('api/create-location.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Location added successfully!');
            closeAddLocationModal();
            loadLocations();
        } else {
            alert('Error: ' + (result.error || 'Failed to add location'));
        }
    } catch (error) {
        console.error('Error adding location:', error);
        alert('Error adding location');
    }
}

// Show edit location modal
async function showEditLocationModal(locationId) {
    try {
        // Get all locations and find the one we're editing
        const response = await fetch('api/get-locations.php');
        const data = await response.json();

        if (data.success) {
            const location = data.locations.find(l => l.location_id == locationId);
            if (location) {
                document.getElementById('editLocationId').value = location.location_id;
                document.getElementById('editLocationName').value = location.name;
                document.getElementById('editLocationAddress').value = location.address || '';
                document.getElementById('editLocationPhone').value = location.phone || '';
                document.getElementById('editLocationModal').style.display = 'flex';
            }
        }
    } catch (error) {
        console.error('Error loading location:', error);
        alert('Error loading location details');
    }
}

// Close edit location modal
function closeEditLocationModal() {
    document.getElementById('editLocationModal').style.display = 'none';
    document.getElementById('editLocationForm').reset();
}

// Handle edit location form submission
async function handleEditLocation(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = {
        location_id: parseInt(formData.get('location_id')),
        name: formData.get('name'),
        address: formData.get('address'),
        phone: formData.get('phone')
    };

    try {
        const response = await fetch('api/update-location.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            alert('Location updated successfully!');
            closeEditLocationModal();
            loadLocations();
        } else {
            alert('Error: ' + (result.error || 'Failed to update location'));
        }
    } catch (error) {
        console.error('Error updating location:', error);
        alert('Error updating location');
    }
}

// Confirm delete location
function confirmDeleteLocation(locationId, locationName) {
    if (confirm(`Are you sure you want to delete "${locationName}"?\n\nNote: Locations with existing orders cannot be deleted.`)) {
        deleteLocation(locationId);
    }
}

// Delete location
async function deleteLocation(locationId) {
    try {
        const response = await fetch('api/delete-location.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ location_id: locationId })
        });

        const result = await response.json();

        if (result.success) {
            alert('Location deleted successfully!');
            loadLocations();
        } else {
            alert('Error: ' + (result.error || 'Failed to delete location'));
        }
    } catch (error) {
        console.error('Error deleting location:', error);
        alert('Error deleting location');
    }
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modals when clicking outside
window.addEventListener('click', function(event) {
    const addModal = document.getElementById('addLocationModal');
    const editModal = document.getElementById('editLocationModal');

    if (event.target === addModal) {
        closeAddLocationModal();
    }
    if (event.target === editModal) {
        closeEditLocationModal();
    }
});

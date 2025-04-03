// Sensor Actions
async function editSensor(id) {
    try {
        showLoading();
        const response = await $.get(`get_sensor.php?id=${id}`);
        if (response.success) {
            showEditSensorModal(response.data);
        } else {
            showError(response.message || 'Failed to load sensor data');
        }
    } catch (error) {
        showError('An error occurred while loading sensor data');
        console.error(error);
    } finally {
        hideLoading();
    }
}

async function deleteSensor(id) {
    if (confirm('Are you sure you want to delete this sensor?')) {
        try {
            showLoading();
            const response = await $.post('delete_sensor.php', { id });
            if (response.success) {
                showSuccess('Sensor deleted successfully');
                loadDashboardData();
            } else {
                showError(response.message || 'Failed to delete sensor');
            }
        } catch (error) {
            showError('An error occurred while deleting the sensor');
            console.error(error);
        } finally {
            hideLoading();
        }
    }
}

function showEditSensorModal(sensor) {
    const modal = $(`
        <div class="modal" id="edit-sensor-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Sensor</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="edit-sensor-form">
                        <input type="hidden" name="id" value="${sensor.id}">
                        <div class="form-group">
                            <label for="sensor-name">Name</label>
                            <input type="text" id="sensor-name" name="name" value="${sensor.name}" required>
                        </div>
                        <div class="form-group">
                            <label for="sensor-plant">Plant</label>
                            <select id="sensor-plant" name="plant_id">
                                <option value="">Select Plant</option>
                                <!-- Plants will be loaded dynamically -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="sensor-status">Status</label>
                            <select id="sensor-status" name="status" required>
                                <option value="active" ${sensor.status === 'active' ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${sensor.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `);

    // Load plants for dropdown
    loadPlants().then(plants => {
        const select = modal.find('#sensor-plant');
        plants.forEach(plant => {
            select.append(`<option value="${plant.id}" ${plant.id === sensor.plant_id ? 'selected' : ''}>${plant.name}</option>`);
        });
    });

    // Handle form submission
    modal.find('#edit-sensor-form').on('submit', async function(e) {
        e.preventDefault();
        try {
            showLoading();
            const formData = $(this).serialize();
            const response = await $.post('update_sensor.php', formData);
            if (response.success) {
                showSuccess('Sensor updated successfully');
                loadDashboardData();
                modal.remove();
            } else {
                showError(response.message || 'Failed to update sensor');
            }
        } catch (error) {
            showError('An error occurred while updating the sensor');
            console.error(error);
        } finally {
            hideLoading();
        }
    });

    // Handle modal close
    modal.find('.close-modal').on('click', () => modal.remove());

    // Add modal to body
    $('body').append(modal);
}

// Plant Actions
async function editPlant(id) {
    try {
        showLoading();
        const response = await $.get(`get_plant.php?id=${id}`);
        if (response.success) {
            showEditPlantModal(response.data);
        } else {
            showError(response.message || 'Failed to load plant data');
        }
    } catch (error) {
        showError('An error occurred while loading plant data');
        console.error(error);
    } finally {
        hideLoading();
    }
}

async function deletePlant(id) {
    if (confirm('Are you sure you want to delete this plant?')) {
        try {
            showLoading();
            const response = await $.post('delete_plant.php', { id });
            if (response.success) {
                showSuccess('Plant deleted successfully');
                loadDashboardData();
            } else {
                showError(response.message || 'Failed to delete plant');
            }
        } catch (error) {
            showError('An error occurred while deleting the plant');
            console.error(error);
        } finally {
            hideLoading();
        }
    }
}

function showEditPlantModal(plant) {
    const modal = $(`
        <div class="modal" id="edit-plant-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Plant</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="edit-plant-form">
                        <input type="hidden" name="id" value="${plant.id}">
                        <div class="form-group">
                            <label for="plant-name">Name</label>
                            <input type="text" id="plant-name" name="name" value="${plant.name}" required>
                        </div>
                        <div class="form-group">
                            <label for="plant-type">Type</label>
                            <input type="text" id="plant-type" name="type" value="${plant.type}" required>
                        </div>
                        <div class="form-group">
                            <label for="plant-location">Location</label>
                            <input type="text" id="plant-location" name="location" value="${plant.location}" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `);

    // Handle form submission
    modal.find('#edit-plant-form').on('submit', async function(e) {
        e.preventDefault();
        try {
            showLoading();
            const formData = $(this).serialize();
            const response = await $.post('update_plant.php', formData);
            if (response.success) {
                showSuccess('Plant updated successfully');
                loadDashboardData();
                modal.remove();
            } else {
                showError(response.message || 'Failed to update plant');
            }
        } catch (error) {
            showError('An error occurred while updating the plant');
            console.error(error);
        } finally {
            hideLoading();
        }
    });

    // Handle modal close
    modal.find('.close-modal').on('click', () => modal.remove());

    // Add modal to body
    $('body').append(modal);
}

// Helper Functions
async function loadPlants() {
    try {
        const response = await $.get('get_plants.php');
        if (response.success) {
            return response.data;
        } else {
            showError(response.message || 'Failed to load plants');
            return [];
        }
    } catch (error) {
        showError('An error occurred while loading plants');
        console.error(error);
        return [];
    }
} 
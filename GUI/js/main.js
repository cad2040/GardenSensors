// Global variables
let charts = {};
let refreshInterval;
const REFRESH_INTERVAL = 30000; // 30 seconds
let notifications = [];
let notificationTimeout;

// Document ready
$(document).ready(function() {
    // Initialize the dashboard
    initializeDashboard();
    
    // Set up event listeners
    setupEventListeners();
    
    // Load initial data
    loadDashboardData();
    
    // Start auto-refresh
    startAutoRefresh();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize notification container
    initializeNotifications();
});

// Initialize dashboard
function initializeDashboard() {
    // Show loading overlay
    showLoading();
    
    // Initialize charts
    initializeCharts();
    
    // Initialize interactive elements
    initializeInteractiveElements();
    
    // Hide loading overlay
    hideLoading();
}

// Set up event listeners
function setupEventListeners() {
    // Tab switching
    $('.menu-item').on('click', function(e) {
        e.preventDefault();
        const tab = $(this).data('tab');
        switchTab(tab);
    });
    
    // Form submissions with validation
    $(document).on('submit', 'form', handleFormSubmission);
    
    // Refresh button
    $('.refresh-btn').on('click', function() {
        loadDashboardData();
    });
    
    // Real-time form validation
    $(document).on('input', 'input, select, textarea', function() {
        validateField($(this));
    });
    
    // Interactive elements
    setupInteractiveElements();
}

// Initialize tooltips
function initializeTooltips() {
    $('[data-tooltip]').each(function() {
        const tooltip = $(this).data('tooltip');
        $(this).attr('title', tooltip);
        $(this).tooltip({
            position: { my: "left+15 center", at: "right center" },
            classes: { "ui-tooltip": "custom-tooltip" }
        });
    });
}

// Initialize notifications
function initializeNotifications() {
    // Create notification container if it doesn't exist
    if ($('#notification-container').length === 0) {
        $('body').append('<div id="notification-container"></div>');
    }
}

// Show notification
function showNotification(message, type = 'info', duration = 5000) {
    const id = 'notification-' + Date.now();
    const notification = $(`
        <div id="${id}" class="notification notification-${type}">
            <div class="notification-content">
                <i class="notification-icon ${getNotificationIcon(type)}"></i>
                <span class="notification-message">${message}</span>
            </div>
            <button class="notification-close">&times;</button>
        </div>
    `);
    
    // Add to container
    $('#notification-container').append(notification);
    
    // Add to notifications array
    notifications.push(id);
    
    // Show with animation
    setTimeout(() => {
        notification.addClass('show');
    }, 10);
    
    // Setup close button
    notification.find('.notification-close').on('click', function() {
        closeNotification(id);
    });
    
    // Auto close after duration
    if (duration > 0) {
        setTimeout(() => {
            closeNotification(id);
        }, duration);
    }
    
    return id;
}

// Close notification
function closeNotification(id) {
    const notification = $(`#${id}`);
    notification.removeClass('show');
    
    setTimeout(() => {
        notification.remove();
        notifications = notifications.filter(n => n !== id);
    }, 300);
}

// Get notification icon
function getNotificationIcon(type) {
    switch (type) {
        case 'success':
            return 'fas fa-check-circle';
        case 'error':
            return 'fas fa-exclamation-circle';
        case 'warning':
            return 'fas fa-exclamation-triangle';
        case 'info':
        default:
            return 'fas fa-info-circle';
    }
}

// Show success notification
function showSuccess(message, duration = 5000) {
    return showNotification(message, 'success', duration);
}

// Show error notification
function showError(message, duration = 5000) {
    return showNotification(message, 'error', duration);
}

// Show warning notification
function showWarning(message, duration = 5000) {
    return showNotification(message, 'warning', duration);
}

// Show info notification
function showInfo(message, duration = 5000) {
    return showNotification(message, 'info', duration);
}

// Validate field
function validateField(field) {
    const value = field.val();
    const type = field.attr('type');
    const required = field.prop('required');
    const min = field.attr('min');
    const max = field.attr('max');
    const pattern = field.attr('pattern');
    
    let isValid = true;
    let errorMessage = '';
    
    // Required validation
    if (required && !value) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Type-specific validation
    if (value && type) {
        switch (type) {
            case 'email':
                if (!isValidEmail(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address';
                }
                break;
            case 'number':
                if (isNaN(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid number';
                } else {
                    if (min !== undefined && parseFloat(value) < parseFloat(min)) {
                        isValid = false;
                        errorMessage = `Value must be at least ${min}`;
                    }
                    if (max !== undefined && parseFloat(value) > parseFloat(max)) {
                        isValid = false;
                        errorMessage = `Value must be at most ${max}`;
                    }
                }
                break;
            case 'password':
                if (value.length < 8) {
                    isValid = false;
                    errorMessage = 'Password must be at least 8 characters long';
                }
                break;
        }
    }
    
    // Pattern validation
    if (pattern && value) {
        const regex = new RegExp(pattern);
        if (!regex.test(value)) {
            isValid = false;
            errorMessage = field.attr('data-pattern-message') || 'Please enter a valid value';
        }
    }
    
    // Update field state
    updateFieldValidationState(field, isValid, errorMessage);
    
    return isValid;
}

// Update field validation state
function updateFieldValidationState(field, isValid, errorMessage) {
    const container = field.closest('.form-group');
    
    // Remove existing validation classes
    field.removeClass('is-valid is-invalid');
    container.find('.invalid-feedback').remove();
    
    // Add appropriate class
    if (isValid) {
        field.addClass('is-valid');
    } else {
        field.addClass('is-invalid');
        container.append(`<div class="invalid-feedback">${errorMessage}</div>`);
    }
}

// Validate email
function isValidEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
}

// Initialize interactive elements
function initializeInteractiveElements() {
    // Initialize sensor cards
    $('.sensor-card').each(function() {
        initializeSensorCard($(this));
    });
    
    // Initialize plant cards
    $('.plant-card').each(function() {
        initializePlantCard($(this));
    });
    
    // Initialize settings toggles
    $('.setting-toggle').each(function() {
        initializeSettingToggle($(this));
    });
}

// Setup interactive elements
function setupInteractiveElements() {
    // Sensor card interactions
    $(document).on('mouseenter', '.sensor-card', function() {
        $(this).addClass('hover');
    }).on('mouseleave', '.sensor-card', function() {
        $(this).removeClass('hover');
    });
    
    // Plant card interactions
    $(document).on('mouseenter', '.plant-card', function() {
        $(this).addClass('hover');
    }).on('mouseleave', '.plant-card', function() {
        $(this).removeClass('hover');
    });
    
    // Quick actions
    $(document).on('click', '.quick-action', function(e) {
        e.preventDefault();
        const action = $(this).data('action');
        const id = $(this).data('id');
        handleQuickAction(action, id);
    });
}

// Initialize sensor card
function initializeSensorCard(card) {
    const sensorId = card.data('sensor-id');
    
    // Add quick actions
    card.find('.card-header').append(`
        <div class="quick-actions">
            <button class="quick-action" data-action="edit" data-id="${sensorId}">
                <i class="fas fa-edit"></i>
            </button>
            <button class="quick-action" data-action="delete" data-id="${sensorId}">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `);
    
    // Initialize sensor chart
    initializeSensorChart(sensorId);
}

// Initialize plant card
function initializePlantCard(card) {
    const plantId = card.data('plant-id');
    
    // Add quick actions
    card.find('.card-header').append(`
        <div class="quick-actions">
            <button class="quick-action" data-action="edit" data-id="${plantId}">
                <i class="fas fa-edit"></i>
            </button>
            <button class="quick-action" data-action="delete" data-id="${plantId}">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `);
    
    // Initialize moisture threshold controls
    initializeMoistureControls(card);
}

// Initialize setting toggle
function initializeSettingToggle(toggle) {
    const setting = toggle.data('setting');
    const value = toggle.prop('checked');
    
    // Store original value
    toggle.data('original-value', value);
    
    // Add change handler
    toggle.on('change', function() {
        updateSetting($(this));
    });
}

// Handle quick action
async function handleQuickAction(action, id) {
    switch (action) {
        case 'edit':
            showEditModal(action, id);
            break;
        case 'delete':
            showDeleteConfirmation(action, id);
            break;
        default:
            console.error('Unknown action:', action);
    }
}

// Show edit modal
function showEditModal(type, id) {
    // Implementation depends on your modal system
    // This is a placeholder
    showInfo('Edit functionality coming soon');
}

// Show delete confirmation
function showDeleteConfirmation(type, id) {
    if (confirm(`Are you sure you want to delete this ${type}?`)) {
        // Implementation depends on your API
        // This is a placeholder
        showSuccess(`${type} deleted successfully`);
    }
}

// Initialize moisture controls
function initializeMoistureControls(card) {
    const plantId = card.data('plant-id');
    
    // Add moisture threshold controls
    card.find('.plant-info').append(`
        <div class="moisture-controls">
            <div class="form-group">
                <label>Min Moisture (%)</label>
                <input type="number" 
                       class="form-control moisture-threshold" 
                       data-plant-id="${plantId}" 
                       data-type="min" 
                       min="0" 
                       max="100">
            </div>
            <div class="form-group">
                <label>Max Moisture (%)</label>
                <input type="number" 
                       class="form-control moisture-threshold" 
                       data-plant-id="${plantId}" 
                       data-type="max" 
                       min="0" 
                       max="100">
            </div>
        </div>
    `);
    
    // Add change handlers
    card.find('.moisture-threshold').on('input', function() {
        updateMoistureThreshold($(this));
    });
}

// Switch tabs
function switchTab(tab) {
    // Update active states
    $('.menu-item').removeClass('active');
    $(`.menu-item[data-tab="${tab}"]`).addClass('active');
    
    $('.tab-content').removeClass('active');
    $(`#${tab}`).addClass('active');
    
    // Load tab content if not dashboard
    if (tab !== 'Dashboard') {
        loadTabContent(tab);
    }
}

// Load dashboard data
async function loadDashboardData() {
    showLoading();
    
    try {
        // Load sensor status
        const sensorStatus = await $.get('get_tab_data.php', { section: 'sensor-status' });
        $('#sensor-status').html(sensorStatus);
        
        // Load plant health
        const plantHealth = await $.get('get_tab_data.php', { section: 'plant-health' });
        $('#plant-health').html(plantHealth);
        
        // Load recent readings
        const recentReadings = await $.get('get_tab_data.php', { section: 'recent-readings' });
        $('#recent-readings').html(recentReadings);
        
        // Load alerts
        const alerts = await $.get('get_tab_data.php', { section: 'alerts' });
        $('#alerts').html(alerts);
        
        // Update charts
        updateCharts();
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        showError('Failed to load dashboard data');
    }
    
    hideLoading();
}

// Load tab content
async function loadTabContent(tab) {
    showLoading();
    
    try {
        const content = await $.get('get_tab_data.php', { tab: tab });
        $(`#${tab}`).html(content);
        
        // Initialize any components in the new content
        initializeTabComponents(tab);
        
    } catch (error) {
        console.error(`Error loading ${tab} content:`, error);
        showError(`Failed to load ${tab} content`);
    }
    
    hideLoading();
}

// Initialize charts
function initializeCharts() {
    // Moisture levels chart
    const moistureCtx = document.getElementById('moisture-chart').getContext('2d');
    charts.moisture = new Chart(moistureCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // Temperature chart
    const tempCtx = document.getElementById('temperature-chart').getContext('2d');
    charts.temperature = new Chart(tempCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: []
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Update charts
async function updateCharts() {
    try {
        // Get chart data
        const moistureData = await $.get('get_readings.php', { type: 'moisture' });
        const tempData = await $.get('get_readings.php', { type: 'temperature' });
        
        // Update moisture chart
        charts.moisture.data = moistureData;
        charts.moisture.update();
        
        // Update temperature chart
        charts.temperature.data = tempData;
        charts.temperature.update();
        
    } catch (error) {
        console.error('Error updating charts:', error);
    }
}

// Handle form submissions
async function handleFormSubmission(e) {
    e.preventDefault();
    
    const form = $(this);
    const fields = form.find('input, select, textarea');
    let isValid = true;
    
    // Validate all fields
    fields.each(function() {
        if (!validateField($(this))) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        showError('Please correct the errors in the form');
        return;
    }
    
    showLoading();
    
    try {
        const response = await $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json'
        });
        
        if (response.success) {
            showSuccess(response.message);
            form[0].reset();
            
            // Refresh data if needed
            if (response.refresh) {
                loadDashboardData();
            }
        } else {
            showError(response.message);
        }
    } catch (error) {
        console.error('Form submission error:', error);
        showError('An error occurred while submitting the form');
    }
    
    hideLoading();
}

// Initialize tab-specific components
function initializeTabComponents(tab) {
    switch (tab) {
        case 'Sensors':
            initializeSensorComponents();
            break;
        case 'Plants':
            initializePlantComponents();
            break;
        case 'Settings':
            initializeSettingsComponents();
            break;
    }
}

// Auto-refresh
function startAutoRefresh() {
    refreshInterval = setInterval(loadDashboardData, REFRESH_INTERVAL);
}

function stopAutoRefresh() {
    clearInterval(refreshInterval);
}

// Loading overlay
function showLoading() {
    $('.loading-overlay').fadeIn(200);
}

function hideLoading() {
    $('.loading-overlay').fadeOut(200);
}

// Sensor components
function initializeSensorComponents() {
    // Initialize sensor-specific components
    $('.sensor-card').each(function() {
        const sensorId = $(this).data('sensor-id');
        initializeSensorChart(sensorId);
    });
}

// Plant components
function initializePlantComponents() {
    // Initialize plant-specific components
    $('.moisture-threshold').on('input', function() {
        updateMoistureThreshold($(this));
    });
}

// Settings components
function initializeSettingsComponents() {
    // Initialize settings-specific components
    $('.setting-toggle').on('change', function() {
        updateSetting($(this));
    });
}

// Utility functions
async function updateSetting(element) {
    const setting = element.data('setting');
    const value = element.val();
    
    try {
        const response = await $.post('update_settings.php', {
            setting: setting,
            value: value
        });
        
        if (response.success) {
            showSuccess('Setting updated successfully');
        } else {
            showError('Failed to update setting');
            // Revert the change
            element.val(element.data('original-value'));
        }
    } catch (error) {
        console.error('Error updating setting:', error);
        showError('Failed to update setting');
        // Revert the change
        element.val(element.data('original-value'));
    }
}

async function updateMoistureThreshold(element) {
    const plantId = element.data('plant-id');
    const type = element.data('type'); // min or max
    const value = element.val();
    
    try {
        const response = await $.post('update_plant.php', {
            plant_id: plantId,
            type: type,
            value: value
        });
        
        if (response.success) {
            showSuccess('Threshold updated successfully');
        } else {
            showError('Failed to update threshold');
            // Revert the change
            element.val(element.data('original-value'));
        }
    } catch (error) {
        console.error('Error updating threshold:', error);
        showError('Failed to update threshold');
        // Revert the change
        element.val(element.data('original-value'));
    }
} 
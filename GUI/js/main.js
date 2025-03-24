// Global variables
let currentTab = 'sensors';
let loadingOverlay = null;
let refreshInterval = null;

// DOM Elements
document.addEventListener('DOMContentLoaded', function() {
    // Initialize loading overlay
    loadingOverlay = document.getElementById('loading-overlay');
    
    // Initialize tabs
    initializeTabs();
    
    // Initialize forms
    initializeForms();
    
    // Start auto-refresh
    startAutoRefresh();
});

// Tab Management
function initializeTabs() {
    const tabButtons = document.querySelectorAll('.tablinks');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and content
            document.querySelectorAll('.tablinks').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tabcontent').forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(this.dataset.tab).classList.add('active');
            
            // Refresh data for the selected tab
            refreshTabData(this.dataset.tab);
        });
    });
}

function switchTab(tabId) {
    // Update active tab button
    document.querySelectorAll('.tablinks').forEach(button => {
        button.classList.remove('active');
        if (button.getAttribute('data-tab') === tabId) {
            button.classList.add('active');
        }
    });
    
    // Show selected tab content
    document.querySelectorAll('.tabcontent').forEach(content => {
        content.style.display = 'none';
        if (content.id === tabId) {
            content.style.display = 'block';
        }
    });
    
    currentTab = tabId;
    
    // Refresh data for the selected tab
    refreshTabData();
}

// Form Management
function initializeForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(this);
        });
    });
}

async function handleFormSubmit(form) {
    try {
        showLoading();
        
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: form.method,
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayAlert(result.message, 'success');
            form.reset();
            refreshTabData();
        } else {
            displayAlert(result.message, 'error');
        }
    } catch (error) {
        displayAlert('An error occurred while processing your request.', 'error');
        console.error('Form submission error:', error);
    } finally {
        hideLoading();
    }
}

// Data Refresh
function startAutoRefresh() {
    // Refresh data every 5 minutes
    refreshInterval = setInterval(refreshTabData, 300000);
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

async function refreshTabData(tab = null) {
    const tabs = tab ? [tab] : ['sensors', 'plants', 'readings', 'settings'];
    
    for (const tabName of tabs) {
        try {
            switch (tabName) {
                case 'sensors':
                    await loadSensors();
                    break;
                case 'plants':
                    await loadPlants();
                    break;
                case 'readings':
                    await loadReadings();
                    break;
                case 'settings':
                    await loadSettings();
                    break;
            }
        } catch (error) {
            console.error(`Error refreshing ${tabName} tab:`, error);
            displayAlert(`Error loading ${tabName} data. Please try again.`, 'error');
        }
    }
}

function updateTabContent(content) {
    const tabContent = document.getElementById(currentTab);
    if (tabContent) {
        tabContent.innerHTML = content;
    }
}

// Loading Overlay
function showLoading() {
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
}

function hideLoading() {
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

// Alert Management
function displayAlert(message, type = 'info') {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type}`;
    alertContainer.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertContainer, container.firstChild);
    
    // Auto-remove alert after 5 seconds
    setTimeout(() => {
        alertContainer.remove();
    }, 5000);
}

// Plot Management
function initializePlots() {
    const plots = document.querySelectorAll('.plot-frame');
    plots.forEach(plot => {
        // Add any plot-specific initialization here
        // For example, setting up event listeners for plot interactions
    });
}

// Utility Functions
function formatDate(date) {
    return new Date(date).toLocaleString();
}

function formatNumber(number, decimals = 2) {
    return Number(number).toFixed(decimals);
}

// Error Handling
window.onerror = function(msg, url, lineNo, columnNo, error) {
    console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
    displayAlert('An unexpected error occurred. Please try again.', 'error');
    return false;
};

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
});

// Load sensors data
async function loadSensors() {
    showLoading();
    try {
        const response = await fetch('get_sensors.php');
        const result = await response.json();
        
        if (result.success) {
            const sensorsGrid = document.querySelector('.sensors-grid');
            sensorsGrid.innerHTML = '';
            
            result.sensors.forEach(sensor => {
                const sensorCard = createSensorCard(sensor);
                sensorsGrid.appendChild(sensorCard);
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error loading sensors:', error);
        displayAlert('Error loading sensors. Please try again.', 'error');
    } finally {
        hideLoading();
    }
}

// Create sensor card element
function createSensorCard(sensor) {
    const card = document.createElement('div');
    card.className = 'sensor-card';
    card.innerHTML = `
        <div class="card-header">
            <h3>${sensor.name}</h3>
            <div class="card-actions">
                <button class="btn btn-icon" onclick="editSensor(${sensor.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-icon" onclick="deleteSensor(${sensor.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="sensor-info">
                <p><strong>Type:</strong> ${sensor.type}</p>
                <p><strong>Pin:</strong> ${sensor.pin}</p>
                <p><strong>Plant:</strong> ${sensor.plant_name || 'None'}</p>
                <p><strong>Status:</strong> <span class="status-badge ${sensor.status}">${sensor.status}</span></p>
            </div>
            <div class="sensor-readings">
                <div class="reading">
                    <span class="reading-value">${formatReading(sensor.last_reading, sensor.type)}</span>
                    <span class="reading-unit">${getReadingUnit(sensor.type)}</span>
                </div>
                <div class="reading-time">Last updated: ${formatTimestamp(sensor.last_updated)}</div>
            </div>
        </div>
    `;
    return card;
}

// Load plants data
async function loadPlants() {
    showLoading();
    try {
        const response = await fetch('get_plants.php');
        const result = await response.json();
        
        if (result.success) {
            const plantsGrid = document.querySelector('.plants-grid');
            plantsGrid.innerHTML = '';
            
            result.plants.forEach(plant => {
                const plantCard = createPlantCard(plant);
                plantsGrid.appendChild(plantCard);
            });
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error loading plants:', error);
        displayAlert('Error loading plants. Please try again.', 'error');
    } finally {
        hideLoading();
    }
}

// Create plant card element
function createPlantCard(plant) {
    const card = document.createElement('div');
    card.className = 'plant-card';
    card.innerHTML = `
        <div class="card-header">
            <h3>${plant.name}</h3>
            <div class="card-actions">
                <button class="btn btn-icon" onclick="editPlant(${plant.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-icon" onclick="deletePlant(${plant.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="plant-info">
                <p><strong>Type:</strong> ${plant.type}</p>
                <p><strong>Location:</strong> ${plant.location}</p>
                <p><strong>Moisture Range:</strong> ${plant.min_moisture}% - ${plant.max_moisture}%</p>
            </div>
            <div class="plant-sensors">
                <h4>Associated Sensors</h4>
                <ul>
                    ${plant.sensors.map(sensor => `
                        <li>${sensor.name} (${sensor.type})</li>
                    `).join('')}
                </ul>
            </div>
        </div>
    `;
    return card;
}

// Load sensor readings
async function loadReadings() {
    showLoading();
    try {
        const sensorId = document.getElementById('sensor-filter').value;
        const timeRange = document.getElementById('time-range').value;
        
        const response = await fetch(`get_readings.php?sensor_id=${sensorId}&time_range=${timeRange}`);
        const result = await response.json();
        
        if (result.success) {
            const readingsContainer = document.querySelector('.readings-container');
            readingsContainer.innerHTML = '';
            
            // Create chart container
            const chartContainer = document.createElement('div');
            chartContainer.className = 'readings-chart';
            readingsContainer.appendChild(chartContainer);
            
            // Create readings table
            const table = createReadingsTable(result.readings);
            readingsContainer.appendChild(table);
            
            // Initialize chart
            initializeChart(result.readings, chartContainer);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error loading readings:', error);
        displayAlert('Error loading sensor readings. Please try again.', 'error');
    } finally {
        hideLoading();
    }
}

// Create readings table
function createReadingsTable(readings) {
    const table = document.createElement('table');
    table.className = 'readings-table';
    table.innerHTML = `
        <thead>
            <tr>
                <th>Timestamp</th>
                <th>Value</th>
                <th>Unit</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            ${readings.map(reading => `
                <tr>
                    <td>${formatTimestamp(reading.timestamp)}</td>
                    <td>${formatReading(reading.value, reading.type)}</td>
                    <td>${getReadingUnit(reading.type)}</td>
                    <td><span class="status-badge ${reading.status}">${reading.status}</span></td>
                </tr>
            `).join('')}
        </tbody>
    `;
    return table;
}

// Initialize chart using Chart.js
function initializeChart(readings, container) {
    const ctx = document.createElement('canvas');
    container.appendChild(ctx);
    
    const labels = readings.map(r => formatTimestamp(r.timestamp));
    const values = readings.map(r => r.value);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sensor Readings',
                data: values,
                borderColor: '#4CAF50',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Load settings
async function loadSettings() {
    showLoading();
    try {
        const response = await fetch('get_settings.php');
        const result = await response.json();
        
        if (result.success) {
            const settingsContainer = document.querySelector('.settings-container');
            settingsContainer.innerHTML = '';
            
            // Create settings form
            const form = createSettingsForm(result.settings);
            settingsContainer.appendChild(form);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error loading settings:', error);
        displayAlert('Error loading settings. Please try again.', 'error');
    } finally {
        hideLoading();
    }
}

// Create settings form
function createSettingsForm(settings) {
    const form = document.createElement('form');
    form.className = 'settings-form';
    form.innerHTML = `
        <div class="form-group">
            <label class="form-label" for="update-interval">Update Interval (minutes)</label>
            <input type="number" 
                   class="form-control" 
                   id="update-interval" 
                   name="update_interval" 
                   value="${settings.update_interval}"
                   min="1"
                   required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="alert-threshold">Alert Threshold (%)</label>
            <input type="number" 
                   class="form-control" 
                   id="alert-threshold" 
                   name="alert_threshold" 
                   value="${settings.alert_threshold}"
                   min="0"
                   max="100"
                   required>
        </div>
        
        <div class="form-group">
            <label class="form-label" for="email-notifications">Email Notifications</label>
            <div class="form-check">
                <input type="checkbox" 
                       class="form-check-input" 
                       id="email-notifications" 
                       name="email_notifications" 
                       ${settings.email_notifications ? 'checked' : ''}>
                <label class="form-check-label" for="email-notifications">
                    Enable email notifications for alerts
                </label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    `;
    
    form.addEventListener('submit', handleSettingsForm);
    return form;
}

// Handle settings form submission
async function handleSettingsForm(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(this);
        const response = await fetch('update_settings.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayAlert('Settings updated successfully', 'success');
        } else {
            displayAlert(result.message, 'error');
        }
    } catch (error) {
        displayAlert('An error occurred. Please try again.', 'error');
        console.error('Settings update error:', error);
    }
}

// Utility functions
function formatReading(value, type) {
    switch (type) {
        case 'moisture':
            return formatMoisture(value);
        case 'temperature':
            return formatTemperature(value);
        case 'humidity':
            return formatHumidity(value);
        default:
            return value;
    }
}

function getReadingUnit(type) {
    switch (type) {
        case 'moisture':
            return '%';
        case 'temperature':
            return '°C';
        case 'humidity':
            return '%';
        default:
            return '';
    }
}

function formatTimestamp(timestamp) {
    return new Date(timestamp).toLocaleString();
}

function formatMoisture(value) {
    return `${value}%`;
}

function formatTemperature(value) {
    return `${value}°C`;
}

function formatHumidity(value) {
    return `${value}%`;
} 
<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Get user data
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT * FROM Users WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    logError('Error fetching user data: ' . $e->getMessage());
    header('Location: logout.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Garden Sensors</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <h1>Garden Sensors Dashboard</h1>
            </div>
            <div class="navbar-menu">
                <span class="user-info">
                    Welcome, <?php echo htmlspecialchars($user['name']); ?>
                </span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Tabs -->
        <div class="tabs">
            <button class="tablinks active" data-tab="sensors">
                <i class="fas fa-microchip"></i> Sensors
            </button>
            <button class="tablinks" data-tab="plants">
                <i class="fas fa-leaf"></i> Plants
            </button>
            <button class="tablinks" data-tab="readings">
                <i class="fas fa-chart-line"></i> Readings
            </button>
            <button class="tablinks" data-tab="settings">
                <i class="fas fa-cog"></i> Settings
            </button>
        </div>
        
        <!-- Tab Content -->
        <div id="sensors" class="tabcontent active">
            <div class="dashboard-header">
                <h2>Sensor Management</h2>
                <button class="btn btn-primary" onclick="showAddSensorModal()">
                    <i class="fas fa-plus"></i> Add Sensor
                </button>
            </div>
            <div class="sensors-grid">
                <!-- Sensors will be loaded dynamically -->
            </div>
        </div>
        
        <div id="plants" class="tabcontent">
            <div class="dashboard-header">
                <h2>Plant Management</h2>
                <button class="btn btn-primary" onclick="showAddPlantModal()">
                    <i class="fas fa-plus"></i> Add Plant
                </button>
            </div>
            <div class="plants-grid">
                <!-- Plants will be loaded dynamically -->
            </div>
        </div>
        
        <div id="readings" class="tabcontent">
            <div class="dashboard-header">
                <h2>Sensor Readings</h2>
                <div class="readings-filters">
                    <select id="sensor-filter" class="form-control">
                        <option value="">All Sensors</option>
                        <!-- Options will be loaded dynamically -->
                    </select>
                    <select id="time-range" class="form-control">
                        <option value="1h">Last Hour</option>
                        <option value="24h">Last 24 Hours</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                    </select>
                </div>
            </div>
            <div class="readings-container">
                <!-- Readings will be loaded dynamically -->
            </div>
        </div>
        
        <div id="settings" class="tabcontent">
            <div class="dashboard-header">
                <h2>System Settings</h2>
            </div>
            <div class="settings-container">
                <!-- Settings will be loaded dynamically -->
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading...</div>
        </div>
    </div>
    
    <!-- Add Sensor Modal -->
    <div id="add-sensor-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Sensor</h3>
                <button class="close-modal" onclick="closeModal('add-sensor-modal')">&times;</button>
            </div>
            <form id="add-sensor-form" class="modal-form">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="sensor-name">Name</label>
                    <input type="text" 
                           class="form-control" 
                           id="sensor-name" 
                           name="name" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="sensor-type">Type</label>
                    <select class="form-control" id="sensor-type" name="type" required>
                        <option value="moisture">Soil Moisture</option>
                        <option value="temperature">Temperature</option>
                        <option value="humidity">Humidity</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="sensor-pin">Pin</label>
                    <input type="number" 
                           class="form-control" 
                           id="sensor-pin" 
                           name="pin" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="sensor-plant">Plant</label>
                    <select class="form-control" id="sensor-plant" name="plant_id">
                        <option value="">Select a plant</option>
                        <!-- Options will be loaded dynamically -->
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('add-sensor-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Sensor</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Plant Modal -->
    <div id="add-plant-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Plant</h3>
                <button class="close-modal" onclick="closeModal('add-plant-modal')">&times;</button>
            </div>
            <form id="add-plant-form" class="modal-form">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="plant-name">Name</label>
                    <input type="text" 
                           class="form-control" 
                           id="plant-name" 
                           name="name" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="plant-type">Type</label>
                    <input type="text" 
                           class="form-control" 
                           id="plant-type" 
                           name="type" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="plant-location">Location</label>
                    <input type="text" 
                           class="form-control" 
                           id="plant-location" 
                           name="location" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="plant-min-moisture">Minimum Moisture (%)</label>
                    <input type="number" 
                           class="form-control" 
                           id="plant-min-moisture" 
                           name="min_moisture" 
                           min="0" 
                           max="100" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="plant-max-moisture">Maximum Moisture (%)</label>
                    <input type="number" 
                           class="form-control" 
                           id="plant-max-moisture" 
                           name="max_moisture" 
                           min="0" 
                           max="100" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="plant-notes">Notes</label>
                    <textarea class="form-control" 
                              id="plant-notes" 
                              name="notes" 
                              rows="3"></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('add-plant-modal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Plant</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script src="js/main.js"></script>
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial data
            refreshTabData();
            
            // Set up event listeners
            document.getElementById('sensor-filter').addEventListener('change', refreshReadings);
            document.getElementById('time-range').addEventListener('change', refreshReadings);
            
            // Set up form submissions
            document.getElementById('add-sensor-form').addEventListener('submit', handleSensorForm);
            document.getElementById('add-plant-form').addEventListener('submit', handlePlantForm);
        });
        
        // Modal functions
        function showAddSensorModal() {
            document.getElementById('add-sensor-modal').style.display = 'block';
            loadPlantOptions('sensor-plant');
        }
        
        function showAddPlantModal() {
            document.getElementById('add-plant-modal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Form handlers
        async function handleSensorForm(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
                const response = await fetch('manage_sensor.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayAlert(result.message, 'success');
                    closeModal('add-sensor-modal');
                    this.reset();
                    refreshTabData();
                } else {
                    displayAlert(result.message, 'error');
                }
            } catch (error) {
                displayAlert('An error occurred. Please try again.', 'error');
                console.error('Form submission error:', error);
            }
        }
        
        async function handlePlantForm(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(this);
                const response = await fetch('manage_plant.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayAlert(result.message, 'success');
                    closeModal('add-plant-modal');
                    this.reset();
                    refreshTabData();
                } else {
                    displayAlert(result.message, 'error');
                }
            } catch (error) {
                displayAlert('An error occurred. Please try again.', 'error');
                console.error('Form submission error:', error);
            }
        }
        
        // Helper functions
        async function loadPlantOptions(selectId) {
            try {
                const response = await fetch('get_plants.php');
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById(selectId);
                    select.innerHTML = '<option value="">Select a plant</option>';
                    
                    result.plants.forEach(plant => {
                        const option = document.createElement('option');
                        option.value = plant.id;
                        option.textContent = plant.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading plant options:', error);
            }
        }
        
        function refreshReadings() {
            const sensorId = document.getElementById('sensor-filter').value;
            const timeRange = document.getElementById('time-range').value;
            
            // Update readings display based on filters
            // This will be implemented in the main.js file
        }
    </script>
</body>
</html> 
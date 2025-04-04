// Sensor data handling
class SensorManager {
    constructor() {
        this.sensors = new Map();
        this.updateInterval = 5000; // 5 seconds
        this.init();
    }

    async init() {
        await this.loadSensors();
        this.startUpdates();
        this.setupEventListeners();
    }

    async loadSensors() {
        try {
            const response = await fetch('/api/sensors');
            const sensors = await response.json();
            sensors.forEach(sensor => this.sensors.set(sensor.id, sensor));
            this.updateUI();
        } catch (error) {
            console.error('Failed to load sensors:', error);
            this.showError('Failed to load sensor data');
        }
    }

    startUpdates() {
        setInterval(() => this.updateSensorData(), this.updateInterval);
    }

    async updateSensorData() {
        for (const [id, sensor] of this.sensors) {
            try {
                const response = await fetch(`/api/sensors/${id}/reading`);
                const reading = await response.json();
                this.sensors.set(id, { ...sensor, ...reading });
                this.updateSensorUI(id);
            } catch (error) {
                console.error(`Failed to update sensor ${id}:`, error);
            }
        }
    }

    updateUI() {
        const container = document.getElementById('sensor-readings');
        if (!container) return;

        container.innerHTML = '';
        this.sensors.forEach((sensor, id) => {
            const element = this.createSensorElement(sensor);
            container.appendChild(element);
        });
    }

    updateSensorUI(id) {
        const sensor = this.sensors.get(id);
        const element = document.querySelector(`[data-sensor-id="${id}"]`);
        if (element && sensor) {
            element.querySelector('.sensor-value').textContent = sensor.value;
            element.querySelector('.sensor-label').textContent = sensor.label;
            this.updateSensorStatus(element, sensor);
        }
    }

    createSensorElement(sensor) {
        const div = document.createElement('div');
        div.className = 'sensor-reading';
        div.setAttribute('data-sensor-id', sensor.id);
        
        div.innerHTML = `
            <div class="sensor-value">${sensor.value}</div>
            <div class="sensor-label">${sensor.label}</div>
            <div class="sensor-status"></div>
        `;

        this.updateSensorStatus(div, sensor);
        return div;
    }

    updateSensorStatus(element, sensor) {
        const statusElement = element.querySelector('.sensor-status');
        if (!statusElement) return;

        const status = this.calculateSensorStatus(sensor);
        statusElement.className = `sensor-status ${status.class}`;
        statusElement.textContent = status.message;
    }

    calculateSensorStatus(sensor) {
        if (sensor.value < sensor.minThreshold) {
            return { class: 'alert-warning', message: 'Below threshold' };
        } else if (sensor.value > sensor.maxThreshold) {
            return { class: 'alert-danger', message: 'Above threshold' };
        }
        return { class: 'alert-success', message: 'Normal' };
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger';
        alert.textContent = message;
        
        const container = document.querySelector('.container');
        if (container) {
            container.insertBefore(alert, container.firstChild);
            setTimeout(() => alert.remove(), 5000);
        }
    }

    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            const refreshButton = document.getElementById('refresh-sensors');
            if (refreshButton) {
                refreshButton.addEventListener('click', () => this.loadSensors());
            }
        });
    }
}

// Initialize the sensor manager when the page loads
document.addEventListener('DOMContentLoaded', () => {
    window.sensorManager = new SensorManager();
}); 
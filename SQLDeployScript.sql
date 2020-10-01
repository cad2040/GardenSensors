#Create Database
CREATE DATABASE SoilSensors;

#Switch to new DB
USE SoilSensors;

CREATE TABLE Sensors (
 id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY
,sensor VARCHAR(30) NOT NULL
,inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
);

#Create table to store readings
CREATE TABLE Readings (
 id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY
,sensor_id INT(6) UNSIGNED NOT NULL
,reading INT(6) NOT NULL
,inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
,CONSTRAINT fk_sensor
FOREIGN KEY (sensor_id) 
   REFERENCES Sensors(id) ON DELETE CASCADE
);

#Create account used to load readings, replace USERNAME and Password
CREATE USER 'USERNAME'@'%' IDENTIFIED BY 'PASSWORD';

#Grant privileges to user setup in setp above
GRANT ALL PRIVILEGES ON SoilSensors.* TO 'USERNAME'@'%';

FLUSH PRIVILEGES;

# Create dim table to hold plant data
CREATE TABLE DimPlants (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY
    ,plant VARCHAR(255) NOT NULL
    ,minSoilMoisture INT(6) NOT NULL
    ,maxSoilMoisture INT(6) NOT NULL
    );
    
#Create table to store plant facts E.g. which plant is attached to which sensor
CREATE TABLE FactPlants (
 id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY
,sensor_id INT(6) UNSIGNED NOT NULL
,plant_id INT(6) UNSIGNED NOT NULL
,lastWatered TIMESTAMP 
,CONSTRAINT fk_sensor2
FOREIGN KEY (sensor_id) 
   REFERENCES Sensors(id) ON DELETE CASCADE
,CONSTRAINT fk_plant
FOREIGN KEY (plant_id) 
   REFERENCES DimPlants(id) ON DELETE CASCADE
);

#Create table to store plot URLs
CREATE TABLE Plots (
 id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY
,sensor_id INT(6) UNSIGNED NOT NULL
,sensor VARCHAR(255) NOT NULL
,URL VARCHAR(255) NOT NULL
,CONSTRAINT fk_sensor3
FOREIGN KEY (sensor_id) 
   REFERENCES Sensors(id) ON DELETE CASCADE
);

#Create table to store water pump pin assignments
CREATE TABLE SoilSensors.Pins (
 id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY
,sensor_id INT(6) UNSIGNED NOT NULL
,pin INT(6) UNSIGNED NOT NULL
,CONSTRAINT fk_sensor4
FOREIGN KEY (sensor_id) 
   REFERENCES Sensors(id) ON DELETE CASCADE
);

#Create Database
CREATE DATABASE SoilSensors;

#Create table to store readings
CREATE TABLE Readings (
 id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY
,sensor VARCHAR(30) NOT NULL
,reading INT(6) NOT NULL
,inserted TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
);

#Create account used to load readings, replace USERNAME and Password
CREATE USER 'USERNAME'@'%' IDENTIFIED BY 'PASSWORD';

#Grant privileges to user setup in setp above
GRANT ALL PRIVILEGES ON SoilSensors.* TO 'USERNAME'@'%';

FLUSH PRIVILEGES;
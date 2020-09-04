# GardenSensors

This is my start at building an array of sensors to monitor soil moisture levels and water the plants when required. 

The longer term plan is to automate different growing sectors in a greenhouse, storing readings per sensor in a MySQL database, and visualising via HASS.

So far I have a Pi Zero setup with a MySQL DB, and I have setup an initial ESP8266 with a Capacitive Soil Moisture Sensor to take a reading every 4 hours and load it into the DB.

Once my submersive water pumps arrive my next step is to build a Python script to read the last sensor entry and run the pump to water the plant if the value is < X. Watch this space I will add to the repo as parts arrive and I find the time to code.

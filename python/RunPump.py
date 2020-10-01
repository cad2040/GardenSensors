#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Created on Sat Sep  5 11:34:10 2020
Script to produce HTML plots from sensor data and upload via FTP to HASS RPi
@author: cad2040
"""
import DBConnect as conct
import pandas as pd
import gpiozero as gpio
import time
import sys
from datetime import datetime


def main():
  """Declare variables"""
  db='SoilSensors'
  server='SQL DB IP'
  uname='SQLDB USERNAME';pwd='SQLDB PASSWORD'
  querySensors="SELECT id as sensor_id FROM SoilSensors.Sensors"
  queryReadings="SELECT Readings.reading, Readings.inserted \
  FROM SoilSensors.Readings INNER JOIN SoilSensors.Sensors \
  ON Readings.sensor_id = Sensors.id \
  WHERE Sensors.id = %s \
  ORDER BY Readings.inserted DESC limit 1;"
  queryPlantFacts="SELECT lastWatered, plant_id from SoilSensors.FactPlants \
  WHERE sensor_id = %s"
  queryPlants="SELECT minSoilMoisture, maxSoilMoisture \
  from SoilSensors.DimPlants WHERE id = %s"
  updateQuery="UPDATE SoilSensors.FactPlants SET lastWatered = NOW() \
  WHERE sensor_id = %s AND plant_id = %s;"
  PinQuery="SELECT * FROM SoilSensors.Pins WHERE sensor_id = %s;"
  
  
  """connect to Datasources to gather data"""
  cnx=conct.CFSQLConnect(db,uname,pwd,server) 
  sensors=cnx.queryMySQL(querySensors)
  sensors=list(sensors.sensor_id)
  
  """Check each Sensors last moisture reading"""
  for sensor in sensors:
    plantFacts=cnx.queryMySQL(queryPlantFacts % (sensor))
    Reading=cnx.queryMySQL(queryReadings % (sensor))
    plant_id=plantFacts.plant_id.iloc[0]
    lastWatered=plantFacts.lastWatered.iloc[0]
    lastReading=Reading.inserted.iloc[0]
    moisture=Reading.reading.iloc[0]
    moistureMinMax=cnx.queryMySQL(queryPlants % (plant_id))
    minMoisture=moistureMinMax.minSoilMoisture.iloc[0]
    maxMoisture=moistureMinMax.maxSoilMoisture.iloc[0]
    pin=cnx.queryMySQL(PinQuery % (sensor))
    pin=pin.pin.iloc[0]
    pin=int(pin)
    relay=gpio.LED(pin,active_high=False)
    if lastReading > lastWatered:
      if (moisture <= minMoisture):
        relay.on()
        time.sleep(5)
        relay.off()
        cnx.ExecuteMySQL(updateQuery % (sensor, plant_id))
        
    else:
      sys.exit()
        

if __name__ == "__main__":
  errorLog={}
  try:
    main()
  except Exception as str_error: 
    errorLog[0]=str(str_error)
    now=datetime.now()
    date_time=now.strftime("%m/%d/%Y, %H:%M:%S")
    with open('/home/pi/GardenSensors/python/RunPump.log', 'a') as f:
      f.write("\n At "+date_time+" "+errorLog[0])
      f.close()

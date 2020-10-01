#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Created on Sat Sep  5 11:34:10 2020
Script to produce HTML plots from sensor data and upload via FTP to HASS RPi
@author: cad2040
"""
from bokeh.plotting import figure, output_file
from bokeh.models import ColumnDataSource, DatetimeTickFormatter
import DBConnect as conct
from bokeh.resources import CDN
from bokeh.embed import file_html
import os
from math import pi
import FTPConnectMod as FTPConnt
from datetime import datetime


def WritetoHTML(plot, name, direc):
  """Writes table to HTML"""
  fname = direc+name
  html_str= file_html(plot,CDN,name)
  html_file = open(fname, 'w')
  html_file.write(html_str)
  html_file.close()


def plotLine(dataframe,x,y,title,fname,xlabel,ylabel,direc, color):
  """Produces a line chart HTML file"""
  fname = direc+fname
  output_file(fname)
  ds = ColumnDataSource(dataframe)
  label_dict = {}
  Ts = dataframe['inserted']
  for c, value in enumerate(Ts, 1):
    label_dict[c] = value
          
  p = figure(plot_width=300, plot_height=400, title=title)
  p.line(source=ds, x=x, y=y, line_color=color,
         line_alpha=1.0, line_cap='butt')
  p.xaxis.axis_label = xlabel
  p.yaxis.axis_label = ylabel
  p.xaxis.formatter=DatetimeTickFormatter(hours=["%d %B %Y"],\
                                          days=["%d %B %Y"],\
                                          months=["%d %B %Y"],\
                                          years=["%d %B %Y"],)
  p.xaxis.major_label_orientation = pi/4
  return p

def main():
  """Declare variables"""
  db='SoilSensors'
  server='SQL DB IP'
  uname='SQLDB USERNAME';pwd='SQLDB PASSWORD'
  FTPAdr='FTP IP'
  FTPUn='FTP Uname';FTPpwd='FTP Pass'
  querySensors="SELECT sensor FROM SoilSensors.Sensors"
  queryReadings="SELECT Sensors.sensor, Readings.reading, Readings.inserted \
  Readings.sensor_id \
  FROM SoilSensors.Readings INNER JOIN SoilSensors.Sensors \
  ON Readings.sensor_id = Sensors.id;"
  dirc=os.path.join('save dirc path')
  UploadPath=os.path.join('//files//')
  
  """connect to Datasources to gather data"""
  cnx=conct.CFSQLConnect(db,uname,pwd,server) 
  Data=cnx.queryMySQL(queryReadings)
  sensors=cnx.queryMySQL(querySensors)
  sensors=list(sensors.sensor.unique())
  
  """Produce plot and save to HTML file to display in HASS"""
  files=[]
  truncate="TRUNCATE TABLE SoilSensors.Plots;"
  cnx.ExecuteMySQL(truncate)
  for sensor in sensors:
    fname='Sensor'+str(sensor)+'Plot.html'
    files.append(dirc+fname)
    df=Data[Data.sensor == sensor]
    plot=plotLine(df,'inserted','reading','Moisture',\
                  fname,'Datetime','Moisture Pct',dirc,'blue')
    WritetoHTML(plot,fname,dirc)
    
    """Add URL to DB Table"""
    sensor_id=Data[Data.sensor == sensor]
    sensor_id=sensor_id.sensor_id.iloc[0]
    URL="http://HOST/"+fname
    queryInsertURL="INSERT INTO SoilSensors.Plots (sensor_id, sensor, URL) \
    VALUES (%s, '%s', '%s')"
    cnx.ExecuteMySQL(queryInsertURL % (sensor_id, sensor, URL))
    
    """connect to HASS Pi to upload plot via FTP"""
    FTPObj=FTPConnt.FTPConnectMod(FTPAdr,FTPUn,FTPpwd)
    FTPObj.UploadFile(fname,dirc+fname,UploadPath)
   
  for file in files:
    if os.path.exists(file):
      os.remove(file)
  
  """Remove readings older then a month"""
  query="DELETE FROM SoilSensors.Readings WHERE \
         inserted <  (NOW() - INTERVAL 30 DAY);"
  cnx.ExecuteMySQL(query)


if __name__ == "__main__":
  errorLog={}
  try:
    main()
  except Exception as str_error: 
    errorLog[0]=str(str_error)
    now=datetime.now()
    date_time=now.strftime("%m/%d/%Y, %H:%M:%S")
    with open('/home/pi/GardenSensors/python/ProducePlot.log', 'a') as f:
      f.write('At '+date_time+' '+errorLog[0])
      f.close()

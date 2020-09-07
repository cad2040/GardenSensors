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
  FROM SoilSensors.Readings INNER JOIN SoilSensors.Sensors \
  ON Readings.sensor_id = Sensors.id;"
  dirc=os.path.join('save dirc path')
  UploadPath=os.path.join('//files//')
  
  """connect to Datasources to gather data"""
  cnx=conct.CFSQLConnect(db,uname,pwd,server) 
  Data=cnx.queryMySQL(queryReadings)
  sensors=cnx.queryMySQL(querySensors)
  sensors=list(sensors.sensor)
  
  """Produce plot and save to HTML file to display in HASS"""
  files=[]
  for sensor in sensors:
    fname='Sensor'+str(sensor)+'Plot.html'
    files.append(dirc+fname)
    df=Data[Data.sensor == sensor]
    plot=plotLine(df,'inserted','reading','Moisture',\
                  fname,'Datetime','Moisture Reading',dirc,'blue')
    WritetoHTML(plot,fname,dirc)
    
    """connect to HASS Pi to upload plot via FTP"""
    FTPObj=FTPConnt.FTPConnectMod(FTPAdr,FTPUn,FTPpwd)
    FTPObj.UploadFile(fname,dirc+fname,UploadPath)
   
  for file in files:
    if os.path.exists(file):
      os.remove(file)


if __name__ == "__main__":
    errorLog={}
    try:
      main()
    except Exception as str_error: 
      #Alter to print to datestamped error log
      errorLog[0]=str(str_error)
      print(errorLog[0])

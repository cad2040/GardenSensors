#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Created on Sat Sep  5 11:34:10 2020
Script to produce HTML plots from sensor data and upload via FTP to HASS RPi
@author: cad2040
"""
import os
import logging
from datetime import datetime
from typing import Dict, List, Optional
from dataclasses import dataclass
from pathlib import Path

from bokeh.plotting import figure, output_file
from bokeh.models import ColumnDataSource, DatetimeTickFormatter
from bokeh.resources import CDN
from bokeh.embed import file_html
from math import pi

import DBConnect as conct
import FTPConnectMod as FTPConnt

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('garden_sensors.log'),
        logging.StreamHandler()
    ]
)
logger = logging.getLogger(__name__)

@dataclass
class Config:
    """Configuration settings for the plot generation script."""
    db_name: str
    db_server: str
    db_username: str
    db_password: str
    ftp_address: str
    ftp_username: str
    ftp_password: str
    save_directory: str
    upload_path: str
    plot_width: int = 300
    plot_height: int = 400
    host_url: str = "http://HOST/"

def setup_config() -> Config:
    """Load configuration from environment variables or use defaults."""
    return Config(
        db_name=os.getenv('DB_NAME', 'SoilSensors'),
        db_server=os.getenv('DB_SERVER', 'SQL DB IP'),
        db_username=os.getenv('DB_USERNAME', 'SQLDB USERNAME'),
        db_password=os.getenv('DB_PASSWORD', 'SQLDB PASSWORD'),
        ftp_address=os.getenv('FTP_ADDRESS', 'FTP IP'),
        ftp_username=os.getenv('FTP_USERNAME', 'FTP Uname'),
        ftp_password=os.getenv('FTP_PASSWORD', 'FTP Pass'),
        save_directory=os.getenv('SAVE_DIR', os.path.join(os.getcwd(), 'plots')),
        upload_path=os.getenv('UPLOAD_PATH', '//files//')
    )

def write_to_html(plot, name: str, directory: str) -> None:
    """Write plot to HTML file.
    
    Args:
        plot: Bokeh plot object
        name: Name of the file
        directory: Directory to save the file in
    """
    try:
        fname = os.path.join(directory, name)
        html_str = file_html(plot, CDN, name)
        with open(fname, 'w') as html_file:
            html_file.write(html_str)
        logger.info(f"Successfully wrote plot to {fname}")
    except Exception as e:
        logger.error(f"Failed to write HTML file: {e}")
        raise

def plot_line(dataframe, x: str, y: str, title: str, fname: str, 
              xlabel: str, ylabel: str, directory: str, color: str = 'blue',
              width: int = 300, height: int = 400) -> figure:
    """Produce a line chart HTML file.
    
    Args:
        dataframe: Pandas DataFrame containing the data
        x: Column name for x-axis
        y: Column name for y-axis
        title: Plot title
        fname: Output filename
        xlabel: X-axis label
        ylabel: Y-axis label
        directory: Directory to save the plot
        color: Line color
        width: Plot width
        height: Plot height
        
    Returns:
        Bokeh figure object
    """
    try:
        fname = os.path.join(directory, fname)
        output_file(fname)
        ds = ColumnDataSource(dataframe)
        
        p = figure(plot_width=width, plot_height=height, title=title)
        p.line(source=ds, x=x, y=y, line_color=color,
               line_alpha=1.0, line_cap='butt')
        p.xaxis.axis_label = xlabel
        p.yaxis.axis_label = ylabel
        p.xaxis.formatter = DatetimeTickFormatter(
            hours=["%d %B %Y"],
            days=["%d %B %Y"],
            months=["%d %B %Y"],
            years=["%d %B %Y"]
        )
        p.xaxis.major_label_orientation = pi/4
        return p
    except Exception as e:
        logger.error(f"Failed to create plot: {e}")
        raise

def cleanup_files(files: List[str]) -> None:
    """Clean up temporary files.
    
    Args:
        files: List of file paths to clean up
    """
    for file in files:
        try:
            if os.path.exists(file):
                os.remove(file)
                logger.info(f"Cleaned up file: {file}")
        except Exception as e:
            logger.warning(f"Failed to clean up file {file}: {e}")

def main() -> None:
    """Main function to generate and upload plots."""
    try:
        config = setup_config()
        
        # Ensure save directory exists
        os.makedirs(config.save_directory, exist_ok=True)
        
        # SQL queries
        query_sensors = "SELECT sensor FROM SoilSensors.Sensors"
        query_readings = """
            SELECT Sensors.sensor, Readings.reading, Readings.inserted, Readings.sensor_id 
            FROM SoilSensors.Readings 
            INNER JOIN SoilSensors.Sensors ON Readings.sensor_id = Sensors.id;
        """
        
        # Connect to database
        cnx = conct.CFSQLConnect(
            config.db_name,
            config.db_username,
            config.db_password,
            config.db_server
        )
        
        # Get data
        data = cnx.queryMySQL(query_readings)
        sensors = list(cnx.queryMySQL(query_sensors).sensor.unique())
        
        # Generate and upload plots
        files = []
        cnx.ExecuteMySQL("TRUNCATE TABLE SoilSensors.Plots;")
        
        for sensor in sensors:
            try:
                fname = f'Sensor{sensor}Plot.html'
                files.append(os.path.join(config.save_directory, fname))
                
                df = data[data.sensor == sensor]
                plot = plot_line(
                    df, 'inserted', 'reading', 'Moisture',
                    fname, 'Datetime', 'Moisture Pct',
                    config.save_directory, 'blue',
                    config.plot_width, config.plot_height
                )
                
                write_to_html(plot, fname, config.save_directory)
                
                # Add URL to database
                sensor_id = data[data.sensor == sensor].sensor_id.iloc[0]
                url = f"{config.host_url}{fname}"
                query_insert_url = """
                    INSERT INTO SoilSensors.Plots (sensor_id, sensor, URL)
                    VALUES (%s, '%s', '%s')
                """
                cnx.ExecuteMySQL(query_insert_url % (sensor_id, sensor, url))
                
                # Upload via FTP
                ftp_obj = FTPConnt.FTPConnectMod(
                    config.ftp_address,
                    config.ftp_username,
                    config.ftp_password
                )
                ftp_obj.UploadFile(
                    fname,
                    os.path.join(config.save_directory, fname),
                    config.upload_path
                )
                
            except Exception as e:
                logger.error(f"Error processing sensor {sensor}: {e}")
                continue
        
        # Cleanup
        cleanup_files(files)
        
        # Remove old readings
        cnx.ExecuteMySQL("""
            DELETE FROM SoilSensors.Readings 
            WHERE inserted < (NOW() - INTERVAL 30 DAY);
        """)
        
    except Exception as e:
        logger.error(f"Fatal error in main: {e}")
        raise

if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        logger.error(f"Script failed: {e}")
        raise

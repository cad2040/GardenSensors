#!/usr/bin/env python
# coding: utf-8

import os
import sys
from datetime import datetime, timedelta
import pandas as pd
import numpy as np
from bokeh.plotting import figure, output_file, save
from bokeh.layouts import column, row
from bokeh.models import (
    ColumnDataSource,
    HoverTool,
    DatetimeTickFormatter,
    Range1d,
    LinearAxis,
    Legend
)
from tenacity import retry, stop_after_attempt, wait_exponential
from dotenv import load_dotenv

# Add parent directory to path for imports
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from python.DBConnect import DBConnect

load_dotenv()

class PlotGenerator:
    """Class to generate plots from sensor data"""
    
    def __init__(self):
        """Initialize plot generator with database connection"""
        self.db = DBConnect()
        self.db.connect()
        
    def get_sensor_data(self, days=7):
        """Fetch sensor data for the specified number of days"""
        end_date = datetime.now()
        start_date = end_date - timedelta(days=days)
        
        query = """
        SELECT 
            s.name as sensor_name,
            s.type as sensor_type,
            r.reading_value,
            r.reading_timestamp
        FROM sensors s
        JOIN readings r ON s.id = r.sensor_id
        WHERE r.reading_timestamp BETWEEN %s AND %s
        ORDER BY r.reading_timestamp
        """
        
        return self.db.query_to_dataframe(query, params=(start_date, end_date))
    
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def generate_plot(self, output_path='plots/sensor_readings.html', days=7):
        """Generate an interactive plot of sensor readings"""
        # Fetch data
        df = self.get_sensor_data(days)
        if df.empty:
            print("No data available for plotting")
            return False
        
        # Create figure
        p = figure(
            width=800,
            height=400,
            x_axis_type="datetime",
            title="Sensor Readings Over Time"
        )
        
        # Add hover tool
        hover = HoverTool(
            tooltips=[
                ('Sensor', '@sensor_name'),
                ('Value', '@reading_value'),
                ('Time', '@reading_timestamp{%Y-%m-%d %H:%M:%S}')
            ],
            formatters={
                '@reading_timestamp': 'datetime'
            }
        )
        p.add_tools(hover)
        
        # Configure axes
        p.xaxis.formatter = DatetimeTickFormatter(
            hours="%Y-%m-%d %H:%M",
            days="%Y-%m-%d",
            months="%Y-%m",
            years="%Y"
        )
        p.xaxis.axis_label = 'Time'
        
        # Plot each sensor type on a different y-axis
        colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728']
        for i, (sensor_type, group) in enumerate(df.groupby('sensor_type')):
            source = ColumnDataSource(group)
            
            if i == 0:  # Temperature on left y-axis
                p.yaxis.axis_label = f"{sensor_type.title()} (°C)"
                line = p.line(
                    'reading_timestamp',
                    'reading_value',
                    line_color=colors[i],
                    legend_label=sensor_type.title(),
                    source=source
                )
            else:  # Humidity on right y-axis
                p.extra_y_ranges = {
                    sensor_type: Range1d(
                        start=group['reading_value'].min() * 0.9,
                        end=group['reading_value'].max() * 1.1
                    )
                }
                p.add_layout(LinearAxis(
                    y_range_name=sensor_type,
                    axis_label=f"{sensor_type.title()} (%)"
                ), 'right')
                line = p.line(
                    'reading_timestamp',
                    'reading_value',
                    line_color=colors[i],
                    y_range_name=sensor_type,
                    legend_label=sensor_type.title(),
                    source=source
                )
        
        # Configure legend
        p.legend.click_policy = "hide"
        p.legend.location = "top_left"
        
        # Save plot
        output_file(output_path)
        save(p)
        return True
    
    def cleanup(self):
        """Clean up resources"""
        if self.db:
            self.db.disconnect()

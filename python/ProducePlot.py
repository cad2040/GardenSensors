#!/usr/bin/env python
# coding: utf-8

import os
import sys
from datetime import datetime, timedelta
import pandas as pd
import numpy as np
from bokeh.plotting import figure, output_file, save
from bokeh.embed import components
from bokeh.layouts import column, row
from bokeh.models import (
    ColumnDataSource,
    HoverTool,
    DatetimeTickFormatter,
    Range1d,
    LinearAxis,
    Legend
)
import json
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
        
    def get_sensor_data(self, days=7, plant_id=None):
        """Fetch sensor data for the specified number of days, optionally filtered by plant"""
        end_date = datetime.now()
        start_date = end_date - timedelta(days=days)
        
        if plant_id:
            # Filter by specific plant
            query = """
            SELECT 
                p.name as plant_name,
                p.id as plant_id,
                s.name as sensor_name,
                s.type as sensor_type,
                r.value as reading_value,
                r.created_at as reading_timestamp
            FROM plants p
            JOIN plant_sensors ps ON p.id = ps.plant_id
            JOIN sensors s ON ps.sensor_id = s.id
            JOIN readings r ON s.id = r.sensor_id
            WHERE r.created_at BETWEEN %s AND %s
              AND p.id = %s
              AND p.status = 'active'
            ORDER BY p.name, r.created_at
            """
            params = (start_date, end_date, plant_id)
        else:
            # Get data for all plants
            query = """
            SELECT 
                p.name as plant_name,
                p.id as plant_id,
                s.name as sensor_name,
                s.type as sensor_type,
                r.value as reading_value,
                r.created_at as reading_timestamp
            FROM plants p
            JOIN plant_sensors ps ON p.id = ps.plant_id
            JOIN sensors s ON ps.sensor_id = s.id
            JOIN readings r ON s.id = r.sensor_id
            WHERE r.created_at BETWEEN %s AND %s
              AND p.status = 'active'
            ORDER BY p.name, r.created_at
            """
            params = (start_date, end_date)
        
        return self.db.query_to_dataframe(query, params=params)
    
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def generate_plot(self, output_path='plots/sensor_readings.html', days=7, plant_id=None, return_components=False):
        """Generate an interactive plot of sensor readings by plant
        
        Args:
            output_path: Path to save HTML file (ignored if return_components=True)
            days: Number of days of data to include
            plant_id: Optional plant ID to filter by (None = all plants)
            return_components: If True, return (script, div) tuple for embedding
        
        Returns:
            If return_components: (script, div) tuple
            Otherwise: True on success, False on failure
        """
        # Fetch data
        df = self.get_sensor_data(days, plant_id)
        if df.empty:
            if return_components:
                return None, None
            print("No data available for plotting")
            return False
        
        # Create figure with larger size for better visibility
        plot_title = f"Sensor Readings for {'Selected Plant' if plant_id else 'All Plants'}"
        p = figure(
            width=1000,
            height=500,
            x_axis_type="datetime",
            title=plot_title,
            tools="pan,box_zoom,wheel_zoom,reset,save,hover"
        )
        
        # Add hover tool with plant information
        hover = HoverTool(
            tooltips=[
                ('Plant', '@plant_name'),
                ('Sensor', '@sensor_name'),
                ('Type', '@sensor_type'),
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
        p.yaxis.axis_label = 'Reading Value'
        
        # Color-blind friendly palette - base colors per sensor type
        # Each sensor type has a distinct base color, with more variations for different plants
        # Using Okabe-Ito inspired palette with more distinct shades
        sensor_type_base_colors = {
            'temperature': ['#0072B2', '#56B4E9', '#005F8C', '#0099CC', '#33B5E5'],      # Blues (5 shades)
            'humidity': ['#009E73', '#66C2A5', '#007A5E', '#00C896', '#4DD4B0'],          # Greens (5 shades)
            'moisture': ['#E69F00', '#F0A830', '#CC8F00', '#FFB84D', '#FFCC66'],          # Oranges (5 shades)
            'light': ['#CC79A7', '#E78AC3', '#B36893', '#F5A9D0', '#DE9FC4'],             # Pinks (5 shades)
            'ph': ['#56B4E9', '#7FC8E8', '#3DA5CC', '#99D9F5', '#B3E5F7'],                # Light blues (5 shades)
            'conductivity': ['#D55E00', '#F0803D', '#B84D00', '#FF9933', '#FFB366'],      # Red-oranges (5 shades)
            'pressure': ['#F0E442', '#F5EA6B', '#D4CC1A', '#FFF966', '#FFFD99'],          # Yellows (5 shades)
            'co2': ['#000000', '#333333', '#666666', '#999999', '#CCCCCC']                 # Grays (5 shades)
        }
        
        # Line dash patterns for additional distinction (colorblind-friendly)
        # More patterns for better distinction - using Bokeh-compatible formats
        line_dash_patterns = ['solid', 'dashed', 'dotted', 'dotdash', 'dashdot']
        
        # Fallback colors for unknown sensor types (ColorBrewer Set2)
        fallback_colors = ['#66c2a5', '#fc8d62', '#8da0cb', '#e78ac3', '#a6d854', '#ffd92f', '#e5c494', '#b3b3b3']
        fallback_index = 0
        
        # Track plant-sensor combinations for unique styling
        plant_sensor_combinations = {}  # (plant_name, sensor_type) -> (color, dash_pattern)
        sensor_type_plant_counts = {}  # sensor_type -> {plant_name: index}
        
        # Group by plant and sensor type, then plot
        for (plant_name, sensor_type), group in df.groupby(['plant_name', 'sensor_type']):
            sensor_type_lower = sensor_type.lower() if sensor_type else 'unknown'
            
            # Get or assign color and line style for this plant-sensor combination
            if (plant_name, sensor_type_lower) in plant_sensor_combinations:
                color, dash_pattern = plant_sensor_combinations[(plant_name, sensor_type_lower)]
            else:
                # Determine color based on sensor type and plant
                if sensor_type_lower in sensor_type_base_colors:
                    # Track which plant index this is for this sensor type
                    if sensor_type_lower not in sensor_type_plant_counts:
                        sensor_type_plant_counts[sensor_type_lower] = {}
                    
                    if plant_name not in sensor_type_plant_counts[sensor_type_lower]:
                        plant_index = len(sensor_type_plant_counts[sensor_type_lower])
                        sensor_type_plant_counts[sensor_type_lower][plant_name] = plant_index
                    else:
                        plant_index = sensor_type_plant_counts[sensor_type_lower][plant_name]
                    
                    # Get color from the palette for this sensor type
                    color_palette = sensor_type_base_colors[sensor_type_lower]
                    color = color_palette[plant_index % len(color_palette)]
                    
                    # Get line dash pattern for additional distinction
                    dash_pattern = line_dash_patterns[plant_index % len(line_dash_patterns)]
                else:
                    # Unknown sensor type - use fallback
                    color = fallback_colors[fallback_index % len(fallback_colors)]
                    dash_pattern = 'solid'
                    fallback_index += 1
                
                # Store this combination
                plant_sensor_combinations[(plant_name, sensor_type_lower)] = (color, dash_pattern)
            
            source = ColumnDataSource(group)
            
            # Create legend label: "Plant - Sensor Type"
            legend_label = f"{plant_name} - {sensor_type.title()}"
            
            # Plot line for this plant-sensor combination
            line_glyph = p.line(
                'reading_timestamp',
                'reading_value',
                line_color=color,
                line_dash=dash_pattern,
                legend_label=legend_label,
                source=source,
                line_width=2,
                line_alpha=0.8
            )
            
            # Add scatter points for better visibility
            circle_glyph = p.circle(
                'reading_timestamp',
                'reading_value',
                color=color,
                legend_label=legend_label,
                source=source,
                size=4,
                alpha=0.6
            )
        
        # Configure legend - position it outside the plot area to avoid overlap
        p.legend.click_policy = "hide"
        p.legend.location = "bottom_left"  # Move to bottom left where data is less dense
        p.legend.label_text_font_size = "9pt"  # Smaller font for compact legend
        p.legend.background_fill_alpha = 0.9  # Slightly transparent so data shows through if needed
        p.legend.border_line_color = "gray"
        p.legend.border_line_width = 1
        p.legend.spacing = 3  # Tighter spacing for compact legend
        p.legend.padding = 8  # Less padding for compact legend
        p.legend.glyph_width = 20  # Smaller glyph width
        p.legend.glyph_height = 15  # Smaller glyph height
        
        # If we have many items, reduce font size even more
        if len(plant_sensor_combinations) > 8:
            p.legend.label_text_font_size = "8pt"
            p.legend.spacing = 2
            p.legend.padding = 6
        
        if return_components:
            # Return components for embedding in web page
            script, div = components(p)
            return script, div
        else:
            # Save plot to file
            output_file(output_path)
            save(p)
            return True
    
    def generate_plot_json(self, days=7, plant_id=None):
        """Generate plot data as JSON for client-side rendering"""
        df = self.get_sensor_data(days, plant_id)
        if df.empty:
            return None
        
        # Convert DataFrame to JSON format
        df['reading_timestamp'] = df['reading_timestamp'].astype(str)
        return df.to_json(orient='records', date_format='iso')
    
    def cleanup(self):
        """Clean up resources"""
        if self.db:
            self.db.disconnect()

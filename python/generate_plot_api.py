#!/usr/bin/env python
# coding: utf-8
"""
API script for generating plots - can be called from PHP
Returns JSON with plot components or data
"""

import os
import sys
import json
import argparse

# Add parent directory to path for imports
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from python.ProducePlot import PlotGenerator

def main():
    parser = argparse.ArgumentParser(description='Generate plant-based sensor plots')
    parser.add_argument('--plant-id', type=int, default=None, help='Plant ID to filter by (optional)')
    parser.add_argument('--days', type=int, default=7, help='Number of days of data to include')
    parser.add_argument('--format', choices=['components', 'json'], default='components',
                       help='Output format: components (Bokeh embed) or json (raw data)')
    
    args = parser.parse_args()
    
    try:
        plotter = PlotGenerator()
        
        if args.format == 'components':
            # Generate plot components for embedding
            script, div = plotter.generate_plot(
                days=args.days,
                plant_id=args.plant_id,
                return_components=True
            )
            
            if script is None or div is None:
                print(json.dumps({
                    'success': False,
                    'error': 'No data available for plotting'
                }))
                sys.exit(1)
            
            # Return components as JSON
            result = {
                'success': True,
                'script': script,
                'div': div
            }
            print(json.dumps(result))
            
        else:
            # Generate JSON data
            data = plotter.generate_plot_json(
                days=args.days,
                plant_id=args.plant_id
            )
            
            if data is None:
                print(json.dumps({
                    'success': False,
                    'error': 'No data available for plotting'
                }))
                sys.exit(1)
            
            result = {
                'success': True,
                'data': json.loads(data)
            }
            print(json.dumps(result))
        
        plotter.cleanup()
        
    except Exception as e:
        print(json.dumps({
            'success': False,
            'error': str(e)
        }))
        sys.exit(1)

if __name__ == '__main__':
    main()


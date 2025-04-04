#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script to control water pumps based on soil moisture readings.
"""
import os
import logging
from datetime import datetime
from typing import Dict, Optional
from dataclasses import dataclass
import argparse
import sys
import time
import RPi.GPIO as GPIO

import pandas as pd
import gpiozero as gpio

import DBConnect as conct

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
    """Configuration settings for the pump control script."""
    db_name: str
    db_server: str
    db_username: str
    db_password: str
    pump_duration: int = 5  # seconds

def setup_config() -> Config:
    """Load configuration from environment variables or use defaults."""
    return Config(
        db_name=os.getenv('DB_NAME', 'SoilSensors'),
        db_server=os.getenv('DB_SERVER', 'SQL DB IP'),
        db_username=os.getenv('DB_USERNAME', 'SQLDB USERNAME'),
        db_password=os.getenv('DB_PASSWORD', 'SQLDB PASSWORD')
    )

class PumpController:
    """Class to handle pump control logic."""
    
    def __init__(self):
        self.pin = None
        self.duration = None
        self.is_running = False

    def setup_pin(self, pin):
        """Set up the GPIO pin for pump control."""
        GPIO.setmode(GPIO.BCM)
        GPIO.setup(pin, GPIO.OUT)
        GPIO.output(pin, GPIO.LOW)
        self.pin = pin

    def start(self):
        """Start the pump."""
        if self.pin is None:
            raise Exception("Pin not set up")
        GPIO.output(self.pin, GPIO.HIGH)
        self.is_running = True

    def stop(self):
        """Stop the pump."""
        if self.pin is not None:
            GPIO.output(self.pin, GPIO.LOW)
        self.is_running = False

    def run_for_duration(self, duration):
        """Run the pump for a specified duration."""
        try:
            self.start()
            time.sleep(duration)
        except KeyboardInterrupt:
            self.stop()
            raise
        finally:
            self.stop()

    def cleanup(self):
        """Clean up GPIO resources."""
        if self.pin is not None:
            GPIO.output(self.pin, GPIO.LOW)
        GPIO.cleanup()

def parse_args():
    parser = argparse.ArgumentParser(description='Control garden pump')
    parser.add_argument('--pin', type=int, required=True, help='GPIO pin number')
    parser.add_argument('--duration', type=int, required=True, help='Duration in seconds')
    return parser.parse_args()

def main():
    try:
        args = parse_args()
        pump = PumpController()
        
        # Set up pin
        pump.setup_pin(args.pin)
        
        # Run pump for specified duration
        pump.run_for_duration(args.duration)
        
        print("Pump controlled successfully")
        sys.exit(0)
        
    except KeyboardInterrupt:
        print("Pump control interrupted")
        sys.exit(1)
    except Exception as e:
        print(f"Error: {str(e)}")
        sys.exit(1)
    finally:
        if 'pump' in locals():
            pump.cleanup()

if __name__ == '__main__':
    main()

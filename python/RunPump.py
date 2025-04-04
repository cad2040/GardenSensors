#!/usr/bin/env python
# coding: utf-8

import os
import sys
import time
import argparse
from datetime import datetime, UTC
from dotenv import load_dotenv

# Add parent directory to path for imports
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from python.DBConnect import DBConnect

load_dotenv()

# Try to import RPi.GPIO, use mock if not available
try:
    import RPi.GPIO as GPIO
except ImportError:
    # Mock GPIO class for development
    class GPIO:
        OUT = 'out'
        BCM = 'bcm'
        HIGH = 1
        LOW = 0
        
        @staticmethod
        def setmode(mode):
            print(f"Mock GPIO: Setting mode to {mode}")
            
        @staticmethod
        def setup(pin, mode):
            print(f"Mock GPIO: Setting up pin {pin} in mode {mode}")
            
        @staticmethod
        def output(pin, value):
            print(f"Mock GPIO: Setting pin {pin} to {'HIGH' if value else 'LOW'}")
            
        @staticmethod
        def cleanup():
            print("Mock GPIO: Cleaning up")

class PumpController:
    """Class to control the water pump"""
    
    def __init__(self, pin=18):
        """Initialize pump controller with GPIO pin"""
        self.pin = pin
        self.db = DBConnect()
        self.db.connect()
        
        # Set up GPIO
        GPIO.setmode(GPIO.BCM)
        GPIO.setup(self.pin, GPIO.OUT)
        GPIO.output(self.pin, GPIO.LOW)  # Ensure pump is off initially
        
    def start_pump(self):
        """Start the water pump"""
        GPIO.output(self.pin, GPIO.HIGH)
        self.log_action("start")
        
    def stop_pump(self):
        """Stop the water pump"""
        GPIO.output(self.pin, GPIO.LOW)
        self.log_action("stop")
        
    def run_pump(self, duration):
        """Run the pump for a specified duration in seconds"""
        try:
            print(f"Starting pump for {duration} seconds...")
            self.start_pump()
            time.sleep(duration)
        except KeyboardInterrupt:
            print("\nPump operation cancelled by user")
        finally:
            self.stop_pump()
            print("Pump stopped")
            
    def log_action(self, action):
        """Log pump action to database"""
        query = """
        INSERT INTO system_logs (component, action, timestamp)
        VALUES (%s, %s, %s)
        """
        self.db.execute_query(query, ["pump", action, datetime.now(UTC)])
        
    def cleanup(self):
        """Clean up resources"""
        if self.db:
            self.db.disconnect()
        GPIO.cleanup()

def main():
    parser = argparse.ArgumentParser(description='Control water pump')
    parser.add_argument('--duration', type=int, default=5,
                      help='Duration to run pump in seconds (default: 5)')
    args = parser.parse_args()
    
    pump = PumpController()
    try:
        pump.run_pump(args.duration)
    except Exception as e:
        print(f"Error: {e}")
    finally:
        pump.cleanup()

if __name__ == '__main__':
    main()

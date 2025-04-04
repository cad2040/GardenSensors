import unittest
import os
import sys
from unittest.mock import patch, MagicMock
from datetime import datetime

sys.path.append(os.path.dirname(os.path.dirname(os.path.dirname(__file__))))
from python.RunPump import PumpController

class TestPumpController(unittest.TestCase):
    def setUp(self):
        """Set up test fixtures before each test method."""
        self.mock_db = MagicMock()
        with patch('python.RunPump.DBConnect', return_value=self.mock_db):
            self.pump = PumpController()
        
    def tearDown(self):
        """Clean up after each test method."""
        self.pump.cleanup()

    @patch('python.RunPump.GPIO', autospec=True)
    def test_initialization(self, mock_gpio):
        """Test that the PumpController initializes correctly."""
        with patch('python.RunPump.DBConnect', return_value=self.mock_db):
            pump = PumpController()
        
        mock_gpio.setmode.assert_called_once_with(mock_gpio.BCM)
        mock_gpio.setup.assert_called_once_with(pump.pin, mock_gpio.OUT)
        mock_gpio.output.assert_called_once_with(pump.pin, mock_gpio.LOW)

    @patch('python.RunPump.GPIO', autospec=True)
    def test_start_pump(self, mock_gpio):
        """Test starting the pump."""
        self.pump.start_pump()
        
        mock_gpio.output.assert_called_with(self.pump.pin, mock_gpio.HIGH)
        self.mock_db.execute_query.assert_called_with(
            """
        INSERT INTO system_logs (component, action, timestamp)
        VALUES (%s, %s, %s)
        """,
            ["pump", "start", unittest.mock.ANY]
        )

    @patch('python.RunPump.GPIO', autospec=True)
    def test_stop_pump(self, mock_gpio):
        """Test stopping the pump."""
        self.pump.stop_pump()
        
        mock_gpio.output.assert_called_with(self.pump.pin, mock_gpio.LOW)
        self.mock_db.execute_query.assert_called_with(
            """
        INSERT INTO system_logs (component, action, timestamp)
        VALUES (%s, %s, %s)
        """,
            ["pump", "stop", unittest.mock.ANY]
        )

    @patch('python.RunPump.GPIO', autospec=True)
    def test_cleanup(self, mock_gpio):
        """Test cleanup method."""
        self.pump.cleanup()
        
        mock_gpio.cleanup.assert_called_once()
        self.mock_db.disconnect.assert_called_once()

    @patch('python.RunPump.GPIO', autospec=True)
    @patch('python.RunPump.time.sleep')
    def test_run_pump(self, mock_sleep, mock_gpio):
        """Test running pump for a specific duration."""
        duration = 5
        
        self.pump.run_pump(duration)
        
        mock_gpio.output.assert_any_call(self.pump.pin, mock_gpio.HIGH)
        mock_sleep.assert_called_once_with(duration)
        mock_gpio.output.assert_called_with(self.pump.pin, mock_gpio.LOW)

    @patch('python.RunPump.GPIO', autospec=True)
    @patch('python.RunPump.time.sleep')
    def test_run_pump_keyboard_interrupt(self, mock_sleep, mock_gpio):
        """Test handling keyboard interrupt while running pump."""
        mock_sleep.side_effect = KeyboardInterrupt()
        
        self.pump.run_pump(5)
        
        mock_gpio.output.assert_any_call(self.pump.pin, mock_gpio.HIGH)
        mock_gpio.output.assert_called_with(self.pump.pin, mock_gpio.LOW)

if __name__ == '__main__':
    unittest.main() 
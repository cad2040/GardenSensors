import unittest
import os
from unittest.mock import patch, MagicMock
import sys
sys.path.append(os.path.dirname(os.path.dirname(os.path.dirname(__file__))))
from python.RunPump import PumpController

class TestPumpController(unittest.TestCase):
    def setUp(self):
        """Set up test fixtures before each test method."""
        self.pump = PumpController()
        
    def tearDown(self):
        """Clean up after each test method."""
        if hasattr(self, 'pump'):
            self.pump.cleanup()

    def test_initialization(self):
        """Test that the PumpController class initializes correctly."""
        self.assertIsNotNone(self.pump)
        self.assertIsNone(self.pump.pin)
        self.assertIsNone(self.pump.duration)
        self.assertIsNone(self.pump.is_running)

    @patch('python.RunPump.RPi.GPIO')
    def test_setup_pin(self, mock_gpio):
        """Test GPIO pin setup."""
        pin = 18
        
        self.pump.setup_pin(pin)
        
        self.assertEqual(self.pump.pin, pin)
        mock_gpio.setmode.assert_called_once_with(mock_gpio.BCM)
        mock_gpio.setup.assert_called_once_with(pin, mock_gpio.OUT)
        mock_gpio.output.assert_called_once_with(pin, mock_gpio.LOW)

    @patch('python.RunPump.RPi.GPIO')
    def test_setup_pin_failure(self, mock_gpio):
        """Test GPIO pin setup failure."""
        mock_gpio.setup.side_effect = Exception("Setup failed")
        
        with self.assertRaises(Exception):
            self.pump.setup_pin(18)

    @patch('python.RunPump.RPi.GPIO')
    def test_start_pump(self, mock_gpio):
        """Test starting the pump."""
        self.pump.pin = 18
        self.pump.duration = 5  # seconds
        
        self.pump.start()
        
        self.assertTrue(self.pump.is_running)
        mock_gpio.output.assert_called_with(18, mock_gpio.HIGH)

    @patch('python.RunPump.RPi.GPIO')
    def test_start_pump_without_setup(self, mock_gpio):
        """Test starting the pump without pin setup."""
        with self.assertRaises(Exception):
            self.pump.start()

    @patch('python.RunPump.RPi.GPIO')
    def test_stop_pump(self, mock_gpio):
        """Test stopping the pump."""
        self.pump.pin = 18
        self.pump.is_running = True
        
        self.pump.stop()
        
        self.assertFalse(self.pump.is_running)
        mock_gpio.output.assert_called_with(18, mock_gpio.LOW)

    @patch('python.RunPump.RPi.GPIO')
    def test_run_for_duration(self, mock_gpio):
        """Test running the pump for a specific duration."""
        self.pump.pin = 18
        duration = 2  # seconds
        
        with patch('time.sleep') as mock_sleep:
            self.pump.run_for_duration(duration)
            
            self.assertEqual(self.pump.duration, duration)
            mock_gpio.output.assert_any_call(18, mock_gpio.HIGH)
            mock_gpio.output.assert_any_call(18, mock_gpio.LOW)
            mock_sleep.assert_called_once_with(duration)

    @patch('python.RunPump.RPi.GPIO')
    def test_run_for_duration_interrupted(self, mock_gpio):
        """Test running the pump with interruption."""
        self.pump.pin = 18
        duration = 5  # seconds
        
        with patch('time.sleep') as mock_sleep:
            mock_sleep.side_effect = KeyboardInterrupt()
            
            with self.assertRaises(KeyboardInterrupt):
                self.pump.run_for_duration(duration)
            
            mock_gpio.output.assert_any_call(18, mock_gpio.LOW)

    def test_cleanup(self):
        """Test cleanup of GPIO resources."""
        mock_gpio = MagicMock()
        self.pump.pin = 18
        
        with patch('python.RunPump.RPi.GPIO', mock_gpio):
            self.pump.cleanup()
            
            mock_gpio.output.assert_called_with(18, mock_gpio.LOW)
            mock_gpio.cleanup.assert_called_once()

    def test_status_check(self):
        """Test pump status checking."""
        self.pump.is_running = True
        self.assertTrue(self.pump.is_running)
        
        self.pump.is_running = False
        self.assertFalse(self.pump.is_running)

    @patch('python.RunPump.RPi.GPIO')
    def test_pump_cycle(self, mock_gpio):
        """Test complete pump cycle."""
        self.pump.pin = 18
        duration = 3  # seconds
        
        with patch('time.sleep') as mock_sleep:
            self.pump.run_for_duration(duration)
            
            # Verify the sequence of operations
            calls = mock_gpio.output.call_args_list
            self.assertEqual(len(calls), 2)  # HIGH then LOW
            self.assertEqual(calls[0][0][1], mock_gpio.HIGH)
            self.assertEqual(calls[1][0][1], mock_gpio.LOW)
            mock_sleep.assert_called_once_with(duration)

if __name__ == '__main__':
    unittest.main() 
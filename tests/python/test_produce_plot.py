import unittest
import os
from unittest.mock import patch, MagicMock
import sys
import pandas as pd
from datetime import datetime, timedelta

sys.path.append(os.path.dirname(os.path.dirname(os.path.dirname(__file__))))
from python.ProducePlot import PlotGenerator

class TestPlotGenerator(unittest.TestCase):
    def setUp(self):
        """Set up test fixtures before each test method."""
        self.mock_db = MagicMock()
        with patch('python.ProducePlot.DBConnect', return_value=self.mock_db):
            self.plotter = PlotGenerator()
        
    def tearDown(self):
        """Clean up after each test method."""
        if hasattr(self, 'plotter'):
            self.plotter.cleanup()

    def test_initialization(self):
        """Test that the PlotGenerator class initializes correctly."""
        self.assertIsNotNone(self.plotter)
        self.assertIsNotNone(self.plotter.db)

    def test_get_sensor_data(self):
        """Test getting sensor data from database."""
        mock_data = pd.DataFrame({
            'sensor_name': ['Sensor1', 'Sensor1'],
            'sensor_type': ['temperature', 'temperature'],
            'reading_value': [25.5, 26.2],
            'reading_timestamp': [
                datetime.now() - timedelta(hours=1),
                datetime.now()
            ]
        })
        
        self.mock_db.query_to_dataframe.return_value = mock_data
        result = self.plotter.get_sensor_data(days=1)
        
        self.assertTrue(isinstance(result, pd.DataFrame))
        self.assertEqual(len(result), 2)
        self.assertEqual(list(result.columns), ['sensor_name', 'sensor_type', 'reading_value', 'reading_timestamp'])

    def test_get_sensor_data_empty(self):
        """Test getting sensor data when no data is available."""
        self.mock_db.query_to_dataframe.return_value = pd.DataFrame()
        result = self.plotter.get_sensor_data(days=1)
        
        self.assertTrue(result.empty)

    @patch('python.ProducePlot.figure')
    @patch('python.ProducePlot.output_file')
    @patch('python.ProducePlot.save')
    def test_generate_plot(self, mock_save, mock_output_file, mock_figure):
        """Test generating a plot."""
        mock_data = pd.DataFrame({
            'sensor_name': ['Sensor1', 'Sensor1'],
            'sensor_type': ['temperature', 'temperature'],
            'reading_value': [25.5, 26.2],
            'reading_timestamp': [
                datetime.now() - timedelta(hours=1),
                datetime.now()
            ]
        })
        
        self.mock_db.query_to_dataframe.return_value = mock_data
        result = self.plotter.generate_plot('test_plot.html', days=1)
        
        self.assertTrue(result)
        mock_figure.assert_called_once()
        mock_output_file.assert_called_once_with('test_plot.html')
        mock_save.assert_called_once()

    @patch('python.ProducePlot.figure')
    @patch('python.ProducePlot.output_file')
    @patch('python.ProducePlot.save')
    def test_generate_plot_empty_data(self, mock_save, mock_output_file, mock_figure):
        """Test generating a plot with no data."""
        self.mock_db.query_to_dataframe.return_value = pd.DataFrame()
        result = self.plotter.generate_plot('test_plot.html', days=1)
        
        self.assertFalse(result)
        mock_figure.assert_not_called()
        mock_output_file.assert_not_called()
        mock_save.assert_not_called()

    def test_cleanup(self):
        """Test cleanup of resources."""
        self.plotter.cleanup()
        self.mock_db.disconnect.assert_called_once()

if __name__ == '__main__':
    unittest.main() 
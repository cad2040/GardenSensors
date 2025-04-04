import unittest
import os
from unittest.mock import patch, MagicMock
import sys
sys.path.append(os.path.dirname(os.path.dirname(os.path.dirname(__file__))))
from python.ProducePlot import PlotGenerator

class TestPlotGenerator(unittest.TestCase):
    def setUp(self):
        """Set up test fixtures before each test method."""
        self.plotter = PlotGenerator()
        
    def tearDown(self):
        """Clean up after each test method."""
        if hasattr(self, 'plotter'):
            self.plotter.cleanup()

    def test_initialization(self):
        """Test that the PlotGenerator class initializes correctly."""
        self.assertIsNotNone(self.plotter)
        self.assertIsNone(self.plotter.data)
        self.assertIsNone(self.plotter.figure)
        self.assertIsNone(self.plotter.axes)

    def test_load_data(self):
        """Test loading data from database."""
        mock_data = [
            {'timestamp': '2024-01-01 10:00:00', 'value': 45.5},
            {'timestamp': '2024-01-01 11:00:00', 'value': 46.2}
        ]
        
        with patch('python.ProducePlot.DBConnect') as mock_db:
            mock_db.return_value.__enter__.return_value.execute_query.return_value = mock_data
            self.plotter.load_data('test_sensor', '2024-01-01', '2024-01-02')
            
            self.assertEqual(len(self.plotter.data), 2)
            self.assertEqual(self.plotter.data[0]['value'], 45.5)

    def test_load_data_empty(self):
        """Test loading data when no data is available."""
        with patch('python.ProducePlot.DBConnect') as mock_db:
            mock_db.return_value.__enter__.return_value.execute_query.return_value = []
            self.plotter.load_data('test_sensor', '2024-01-01', '2024-01-02')
            
            self.assertEqual(len(self.plotter.data), 0)

    def test_create_figure(self):
        """Test creating a new figure."""
        self.plotter.create_figure()
        
        self.assertIsNotNone(self.plotter.figure)
        self.assertIsNotNone(self.plotter.axes)

    def test_plot_line(self):
        """Test plotting a line graph."""
        self.plotter.data = [
            {'timestamp': '2024-01-01 10:00:00', 'value': 45.5},
            {'timestamp': '2024-01-01 11:00:00', 'value': 46.2}
        ]
        
        self.plotter.create_figure()
        self.plotter.plot_line('value', 'Test Line')
        
        self.assertEqual(len(self.plotter.axes.lines), 1)
        self.assertEqual(self.plotter.axes.lines[0].get_label(), 'Test Line')

    def test_plot_bar(self):
        """Test plotting a bar graph."""
        self.plotter.data = [
            {'timestamp': '2024-01-01 10:00:00', 'value': 45.5},
            {'timestamp': '2024-01-01 11:00:00', 'value': 46.2}
        ]
        
        self.plotter.create_figure()
        self.plotter.plot_bar('value', 'Test Bar')
        
        self.assertEqual(len(self.plotter.axes.patches), 2)
        self.assertEqual(self.plotter.axes.patches[0].get_label(), 'Test Bar')

    def test_set_title(self):
        """Test setting plot title."""
        self.plotter.create_figure()
        self.plotter.set_title('Test Title')
        
        self.assertEqual(self.plotter.axes.get_title(), 'Test Title')

    def test_set_labels(self):
        """Test setting axis labels."""
        self.plotter.create_figure()
        self.plotter.set_labels('X Label', 'Y Label')
        
        self.assertEqual(self.plotter.axes.get_xlabel(), 'X Label')
        self.assertEqual(self.plotter.axes.get_ylabel(), 'Y Label')

    def test_set_legend(self):
        """Test setting plot legend."""
        self.plotter.create_figure()
        self.plotter.set_legend()
        
        self.assertIsNotNone(self.plotter.axes.get_legend())

    def test_save_plot(self):
        """Test saving plot to file."""
        self.plotter.create_figure()
        test_file = 'test_plot.png'
        
        with patch('matplotlib.figure.Figure.savefig') as mock_save:
            self.plotter.save_plot(test_file)
            mock_save.assert_called_once_with(test_file)

    def test_cleanup(self):
        """Test cleanup of plot resources."""
        self.plotter.create_figure()
        
        with patch('matplotlib.figure.Figure.close') as mock_close:
            self.plotter.cleanup()
            mock_close.assert_called_once()

    def test_plot_multiple_lines(self):
        """Test plotting multiple lines on the same graph."""
        self.plotter.data = [
            {'timestamp': '2024-01-01 10:00:00', 'value1': 45.5, 'value2': 55.5},
            {'timestamp': '2024-01-01 11:00:00', 'value1': 46.2, 'value2': 56.2}
        ]
        
        self.plotter.create_figure()
        self.plotter.plot_line('value1', 'Line 1')
        self.plotter.plot_line('value2', 'Line 2')
        
        self.assertEqual(len(self.plotter.axes.lines), 2)
        self.assertEqual(self.plotter.axes.lines[0].get_label(), 'Line 1')
        self.assertEqual(self.plotter.axes.lines[1].get_label(), 'Line 2')

    def test_plot_with_grid(self):
        """Test plotting with grid enabled."""
        self.plotter.create_figure()
        self.plotter.set_grid(True)
        
        self.assertTrue(self.plotter.axes.xaxis._gridOnMajor)
        self.assertTrue(self.plotter.axes.yaxis._gridOnMajor)

    def test_plot_with_custom_style(self):
        """Test plotting with custom style."""
        self.plotter.create_figure()
        self.plotter.set_style('dark_background')
        
        self.assertEqual(self.plotter.figure.get_facecolor(), '#000000')
        self.assertEqual(self.plotter.axes.get_facecolor(), '#000000')

if __name__ == '__main__':
    unittest.main() 
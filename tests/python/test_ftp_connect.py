import unittest
import os
from unittest.mock import patch, MagicMock
import sys
sys.path.append(os.path.dirname(os.path.dirname(os.path.dirname(__file__))))
from python.FTPConnectMod import FTPConnect

class TestFTPConnect(unittest.TestCase):
    def setUp(self):
        """Set up test fixtures before each test method."""
        self.ftp = FTPConnect()
        
    def tearDown(self):
        """Clean up after each test method."""
        if hasattr(self, 'ftp') and self.ftp.ftp:
            self.ftp.ftp.quit()

    def test_initialization(self):
        """Test that the FTPConnect class initializes correctly."""
        self.assertIsNotNone(self.ftp)
        self.assertIsNone(self.ftp.ftp)
        self.assertIsNone(self.ftp.host)
        self.assertIsNone(self.ftp.username)
        self.assertIsNone(self.ftp.password)

    @patch('python.FTPConnectMod.ftplib.FTP')
    def test_connect_success(self, mock_ftp):
        """Test successful FTP connection."""
        mock_ftp_instance = MagicMock()
        mock_ftp.return_value = mock_ftp_instance
        
        self.ftp.connect('test.host.com', 'user', 'pass')
        
        self.assertIsNotNone(self.ftp.ftp)
        self.assertEqual(self.ftp.host, 'test.host.com')
        self.assertEqual(self.ftp.username, 'user')
        self.assertEqual(self.ftp.password, 'pass')
        mock_ftp_instance.login.assert_called_once_with('user', 'pass')

    @patch('python.FTPConnectMod.ftplib.FTP')
    def test_connect_failure(self, mock_ftp):
        """Test FTP connection failure."""
        mock_ftp.side_effect = Exception("Connection failed")
        
        with self.assertRaises(Exception):
            self.ftp.connect('test.host.com', 'user', 'pass')

    def test_disconnect(self):
        """Test FTP disconnection."""
        mock_ftp = MagicMock()
        self.ftp.ftp = mock_ftp
        
        self.ftp.disconnect()
        
        self.assertIsNone(self.ftp.ftp)
        mock_ftp.quit.assert_called_once()

    def test_upload_file_success(self):
        """Test successful file upload."""
        mock_ftp = MagicMock()
        self.ftp.ftp = mock_ftp
        
        local_path = 'test.txt'
        remote_path = 'remote/test.txt'
        
        with patch('builtins.open', create=True) as mock_open:
            self.ftp.upload_file(local_path, remote_path)
            
            mock_open.assert_called_once_with(local_path, 'rb')
            mock_ftp.storbinary.assert_called_once()

    def test_upload_file_failure(self):
        """Test file upload failure."""
        mock_ftp = MagicMock()
        mock_ftp.storbinary.side_effect = Exception("Upload failed")
        self.ftp.ftp = mock_ftp
        
        with patch('builtins.open', create=True):
            with self.assertRaises(Exception):
                self.ftp.upload_file('test.txt', 'remote/test.txt')

    def test_download_file_success(self):
        """Test successful file download."""
        mock_ftp = MagicMock()
        self.ftp.ftp = mock_ftp
        
        remote_path = 'remote/test.txt'
        local_path = 'test.txt'
        
        with patch('builtins.open', create=True) as mock_open:
            self.ftp.download_file(remote_path, local_path)
            
            mock_open.assert_called_once_with(local_path, 'wb')
            mock_ftp.retrbinary.assert_called_once()

    def test_download_file_failure(self):
        """Test file download failure."""
        mock_ftp = MagicMock()
        mock_ftp.retrbinary.side_effect = Exception("Download failed")
        self.ftp.ftp = mock_ftp
        
        with patch('builtins.open', create=True):
            with self.assertRaises(Exception):
                self.ftp.download_file('remote/test.txt', 'test.txt')

    def test_list_directory(self):
        """Test directory listing."""
        mock_ftp = MagicMock()
        mock_ftp.nlst.return_value = ['file1.txt', 'file2.txt']
        self.ftp.ftp = mock_ftp
        
        files = self.ftp.list_directory('remote_dir')
        
        self.assertEqual(files, ['file1.txt', 'file2.txt'])
        mock_ftp.nlst.assert_called_once_with('remote_dir')

    def test_create_directory(self):
        """Test directory creation."""
        mock_ftp = MagicMock()
        self.ftp.ftp = mock_ftp
        
        self.ftp.create_directory('new_dir')
        
        mock_ftp.mkd.assert_called_once_with('new_dir')

    def test_delete_file(self):
        """Test file deletion."""
        mock_ftp = MagicMock()
        self.ftp.ftp = mock_ftp
        
        self.ftp.delete_file('file.txt')
        
        mock_ftp.delete.assert_called_once_with('file.txt')

    def test_context_manager(self):
        """Test FTPConnect as a context manager."""
        mock_ftp = MagicMock()
        
        with patch('python.FTPConnectMod.ftplib.FTP', return_value=mock_ftp) as mock_ftp_class:
            with FTPConnect() as ftp:
                self.assertIsNotNone(ftp.ftp)
            
            mock_ftp_class.assert_called_once()
            mock_ftp.quit.assert_called_once()

if __name__ == '__main__':
    unittest.main() 
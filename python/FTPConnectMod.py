#!/usr/bin/env python
# coding: utf-8

import os
import ftplib
import io
from dotenv import load_dotenv
from tenacity import retry, stop_after_attempt, wait_exponential

load_dotenv()

class FTPConnect:
    """Class to handle FTP connections and operations."""
    
    def __init__(self):
        """Initialize FTP connection parameters."""
        self.ftp = None
        self.host = os.getenv('FTP_HOST')
        self.username = os.getenv('FTP_USERNAME')
        self.password = os.getenv('FTP_PASSWORD')
        
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def connect(self):
        """Connect to FTP server."""
        try:
            if not self.ftp:
                self.ftp = ftplib.FTP(self.host)
                self.ftp.login(self.username, self.password)
            return True
        except Exception as e:
            print(f"FTP connection error: {e}")
            self.disconnect()
            raise
            
    def disconnect(self):
        """Disconnect from FTP server."""
        if self.ftp:
            try:
                self.ftp.quit()
            except:
                self.ftp.close()
            self.ftp = None
            
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def upload_file(self, local_path, remote_path):
        """Upload a file to FTP server."""
        try:
            if not self.ftp:
                self.connect()
            with open(local_path, 'rb') as file:
                self.ftp.storbinary(f'STOR {remote_path}', file)
            return True
        except Exception as e:
            print(f"File upload error: {e}")
            raise
            
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def download_file(self, remote_path, local_path):
        """Download a file from FTP server."""
        try:
            if not self.ftp:
                self.connect()
            with open(local_path, 'wb') as file:
                self.ftp.retrbinary(f'RETR {remote_path}', file.write)
            return True
        except Exception as e:
            print(f"File download error: {e}")
            raise
            
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def list_directory(self, path='.'):
        """List contents of a directory."""
        try:
            if not self.ftp:
                self.connect()
            return self.ftp.nlst(path)
        except Exception as e:
            print(f"Directory listing error: {e}")
            raise
            
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def create_directory(self, path):
        """Create a new directory on the FTP server."""
        try:
            if not self.ftp:
                self.connect()
            self.ftp.mkd(path)
            return True
        except Exception as e:
            print(f"Directory creation error: {e}")
            raise
            
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def delete_file(self, path):
        """Delete a file from the FTP server."""
        try:
            if not self.ftp:
                self.connect()
            self.ftp.delete(path)
            return True
        except Exception as e:
            print(f"File deletion error: {e}")
            raise
            
    def __enter__(self):
        """Enter context manager."""
        self.connect()
        return self
        
    def __exit__(self, exc_type, exc_val, exc_tb):
        """Exit context manager."""
        self.disconnect()
        
    def close(self):
        """Close FTP connection."""
        self.disconnect()
        
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def get_directory_list(self):
        """Get detailed directory listing."""
        try:
            if not self.ftp:
                self.connect()
            contents = []
            self.ftp.retrlines('LIST', contents.append)
            return contents
        except Exception as e:
            print(f"Directory listing error: {e}")
            raise
            
    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def retrieve_file(self, filename):
        """Retrieve a file into memory."""
        try:
            if not self.ftp:
                self.connect()
            download_file = io.BytesIO()
            self.ftp.retrbinary(f'RETR {filename}', download_file.write)
            download_file.seek(0)
            return download_file
        except Exception as e:
            print(f"File retrieval error: {e}")
            raise
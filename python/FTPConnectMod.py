#!/usr/bin/env python
# coding: utf-8

from ftplib import FTP
import io
import os

# In[4]:

class FTPConnectMod(object):
    """class to connect to a FTP Server"""
    def __init__(self, FTPAddress, Uname, Pwd):
        self.FTPAddress=FTPAddress
        self.Uname=Uname
        self.Pwd=Pwd

    def GetDirectoryList(self):
        ftp = FTP(self.FTPAddress)
        ftp.login(self.Uname,self.Pwd)
        contents=[]
        ftp.retrlines('LIST', contents.append)
        ftp.quit()
        return contents
    
    def RetriveFile(self, filename):
        ftp = FTP(self.FTPAddress)
        ftp.login(self.Uname,self.Pwd)
        cmd='RETR '+filename
        download_file = io.BytesIO()
        ftp.retrbinary(cmd, download_file.write)
        download_file.seek(0)
        ftp.quit()
        return download_file
        
    def UploadFile(self, filename, FullPath, UploadPath):
      ftp = FTP(self.FTPAddress)
      ftp.login(self.Uname,self.Pwd)
      ftp.cwd(UploadPath)
      file = open(FullPath, 'rb')
      ftp.storbinary("STOR "+os.path.basename(filename), file)
      file.close()
      ftp.quit()
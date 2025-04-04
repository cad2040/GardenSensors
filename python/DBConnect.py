#!/usr/bin/env python
# coding: utf-8

import mysql.connector
import pandas as pd
from time import sleep
import sys
# In[4]:

class CFSQLConnect(object):
  """class to connect to a SQL DB"""
  def __init__(self, DB, username, pwd, server):
    self.DB=DB
    self.username=username
    self.pwd=pwd
    self.server=server
    
  def queryMySQL(self, query):
    """initiate connection and execute query"""
    errorCount=0
    errorLog={}
    for x in range(0, 10):
      try:
        cnxn=mysql.connector.connect(user=self.username,password=self.pwd,\
                                     host=self.server,database=self.DB)
        df=pd.read_sql(query, cnxn)
        cnxn.close()
        str_error = None
        break
      except Exception as str_error:
        if errorCount == 9:
          print(errorLog)
          sys.exit()
        else:
          errorLog[errorCount]=str(str_error)
          errorCount+=1
          # wait for 2 seconds before trying again
          sleep(2) 
          pass
    return df
  
  def ExecuteMySQL(self, query):
    """initiate connection and execute query"""
    errorCount=0
    errorLog={}
    for x in range(0, 10):
      try:
        cnxn=mysql.connector.connect(user=self.username,password=self.pwd,\
                                     host=self.server,database=self.DB)
        cursor=cnxn.cursor()
        cursor.execute(query)
        cnxn.commit()
        cursor.close()
        cnxn.close()
        str_error = None
        break
      except Exception as str_error:
        if errorCount == 9:
          print(errorLog)
          sys.exit()
        else:
          errorLog[errorCount]=str(str_error)
          errorCount+=1
          # wait for 2 seconds before trying again
          sleep(2) 
          pass
    

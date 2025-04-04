#!/usr/bin/env python
# coding: utf-8

import mysql.connector
import pandas as pd
from time import sleep
import sys
from dotenv import load_dotenv
import os
from tenacity import retry, stop_after_attempt, wait_exponential

load_dotenv()

class DBConnect:
    """Database connection manager"""
    def __init__(self):
        self.conn = None
        self.cursor = None
        self.host = os.getenv('DB_HOST', 'localhost')
        self.user = os.getenv('DB_USER', 'garden_user')
        self.password = os.getenv('DB_PASS', '')
        # Use test database if TESTING environment variable is set
        self.database = os.getenv('DB_NAME', 'garden_sensors_test' if os.getenv('TESTING') == 'true' else 'garden_sensors')

    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def connect(self):
        """Establish database connection"""
        try:
            self.conn = mysql.connector.connect(
                host=self.host,
                user=self.user,
                password=self.password,
                database=self.database
            )
            self.cursor = self.conn.cursor()
            return True
        except Exception as e:
            print(f"Connection error: {str(e)}")
            raise

    def disconnect(self):
        """Close database connection"""
        if self.cursor:
            self.cursor.close()
            self.cursor = None
        if self.conn:
            self.conn.close()
            self.conn = None

    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def execute_query(self, query, params=None):
        """Execute a query and return results"""
        try:
            if not self.conn or not self.cursor:
                self.connect()
            
            if params:
                self.cursor.execute(query, params)
            else:
                self.cursor.execute(query)
            
            self.conn.commit()
            return self.cursor.fetchall()
        except Exception as e:
            print(f"Query error: {str(e)}")
            raise

    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def execute_many(self, query, params):
        """Execute a batch query"""
        try:
            if not self.conn or not self.cursor:
                self.connect()
            
            self.cursor.executemany(query, params)
            self.conn.commit()
        except Exception as e:
            print(f"Batch query error: {str(e)}")
            raise

    def rollback(self):
        """Rollback current transaction"""
        if self.conn:
            self.conn.rollback()

    def __enter__(self):
        """Context manager entry"""
        self.connect()
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        """Context manager exit"""
        self.disconnect()

    @retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
    def query_to_dataframe(self, query, params=None):
        """Execute a query and return results as pandas DataFrame"""
        try:
            if not self.conn:
                self.connect()
            return pd.read_sql(query, self.conn, params=params)
        except Exception as e:
            print(f"DataFrame query error: {str(e)}")
            raise

    def queryMySQL(self, query):
        """initiate connection and execute query"""
        errorCount=0
        errorLog={}
        for x in range(0, 10):
          try:
            cnxn=mysql.connector.connect(user=self.user,password=self.password,\
                                         host=self.host,database=self.database)
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
            cnxn=mysql.connector.connect(user=self.user,password=self.password,\
                                         host=self.host,database=self.database)
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
    

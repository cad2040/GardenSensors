import unittest
import os
from unittest.mock import patch, MagicMock
import sys
sys.path.append(os.path.dirname(os.path.dirname(os.path.dirname(__file__))))
from python.DBConnect import DBConnect

class TestDBConnect(unittest.TestCase):
    def setUp(self):
        """Set up test fixtures before each test method."""
        self.db = DBConnect()
        
    def tearDown(self):
        """Clean up after each test method."""
        if hasattr(self, 'db'):
            self.db.disconnect()

    def test_initialization(self):
        """Test that the DBConnect class initializes correctly."""
        self.assertIsNotNone(self.db)
        self.assertIsNone(self.db.conn)
        self.assertIsNone(self.db.cursor)

    @patch('mysql.connector.connect')
    def test_connect_success(self, mock_connect):
        """Test successful database connection."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_connect.return_value = mock_conn
        mock_conn.cursor.return_value = mock_cursor

        result = self.db.connect()
        
        self.assertTrue(result)
        self.assertIsNotNone(self.db.conn)
        self.assertIsNotNone(self.db.cursor)
        mock_connect.assert_called_once_with(
            host=self.db.host,
            user=self.db.user,
            password=self.db.password,
            database=self.db.database
        )
        mock_conn.cursor.assert_called_once()

    @patch('mysql.connector.connect')
    def test_connect_failure(self, mock_connect):
        """Test database connection failure."""
        mock_connect.side_effect = Exception("Connection failed")
        
        with self.assertRaises(Exception):
            self.db.connect()

    def test_disconnect(self):
        """Test database disconnection."""
        # First connect
        self.db.conn = MagicMock()
        self.db.cursor = MagicMock()
        
        # Then disconnect
        self.db.disconnect()
        
        self.assertIsNone(self.db.conn)
        self.assertIsNone(self.db.cursor)

    def test_execute_query_success(self):
        """Test successful query execution."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_cursor.fetchall.return_value = [('result1',), ('result2',)]
        mock_conn.cursor.return_value = mock_cursor
        
        self.db.conn = mock_conn
        self.db.cursor = mock_cursor
        
        query = "SELECT * FROM test_table"
        params = ['param1']
        
        results = self.db.execute_query(query, params)
        
        self.assertEqual(results, [('result1',), ('result2',)])
        mock_cursor.execute.assert_called_once_with(query, params)
        mock_conn.commit.assert_called_once()

    def test_execute_query_failure(self):
        """Test query execution failure."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_cursor.execute.side_effect = Exception("Query failed")
        mock_conn.cursor.return_value = mock_cursor
        
        self.db.conn = mock_conn
        self.db.cursor = mock_cursor
        
        with self.assertRaises(Exception):
            self.db.execute_query("SELECT * FROM test_table")

    def test_execute_many_success(self):
        """Test successful batch query execution."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value = mock_cursor
        
        self.db.conn = mock_conn
        self.db.cursor = mock_cursor
        
        query = "INSERT INTO test_table (col1, col2) VALUES (%s, %s)"
        params = [('val1', 'val2'), ('val3', 'val4')]
        
        self.db.execute_many(query, params)
        
        mock_cursor.executemany.assert_called_once_with(query, params)
        mock_conn.commit.assert_called_once()

    def test_execute_many_failure(self):
        """Test batch query execution failure."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_cursor.executemany.side_effect = Exception("Batch query failed")
        mock_conn.cursor.return_value = mock_cursor
        
        self.db.conn = mock_conn
        self.db.cursor = mock_cursor
        
        with self.assertRaises(Exception):
            self.db.execute_many("INSERT INTO test_table VALUES (%s)", [('val1',)])

    def test_rollback(self):
        """Test transaction rollback."""
        mock_conn = MagicMock()
        self.db.conn = mock_conn
        
        self.db.rollback()
        
        mock_conn.rollback.assert_called_once()

    @patch('mysql.connector.connect')
    def test_context_manager(self, mock_connect):
        """Test DBConnect as a context manager."""
        mock_conn = MagicMock()
        mock_cursor = MagicMock()
        mock_conn.cursor.return_value = mock_cursor
        mock_connect.return_value = mock_conn
        
        with DBConnect() as db:
            self.assertIsNotNone(db.conn)
            self.assertIsNotNone(db.cursor)
        
        mock_connect.assert_called_once()
        mock_conn.close.assert_called_once()

if __name__ == '__main__':
    unittest.main() 
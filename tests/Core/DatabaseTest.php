<?php

namespace GardenSensors\Tests\Core;

use PHPUnit\Framework\TestCase;
use GardenSensors\Core\Database;

class DatabaseTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::getInstance();
    }

    public function testConnection()
    {
        $this->assertNotNull($this->db);
        $this->assertInstanceOf(Database::class, $this->db);
    }

    public function testQuery()
    {
        $result = $this->db->query('SELECT 1 as test');
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['test']);
    }

    public function testExecute()
    {
        // Create a test table
        $this->db->exec('CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY, name VARCHAR(255))');
        
        // Insert a test record
        $result = $this->db->execute(
            'INSERT INTO test_table (id, name) VALUES (?, ?)',
            [1, 'Test']
        );
        
        $this->assertTrue($result);
        
        // Verify the record was inserted
        $records = $this->db->query('SELECT * FROM test_table WHERE id = ?', [1]);
        $this->assertCount(1, $records);
        $this->assertEquals('Test', $records[0]['name']);
        
        // Clean up
        $this->db->exec('DROP TABLE IF EXISTS test_table');
    }

    public function testTransaction()
    {
        // Create a test table
        $this->db->exec('CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY, name VARCHAR(255))');
        
        // Start transaction
        $this->db->beginTransaction();
        
        // Insert a test record
        $this->db->execute(
            'INSERT INTO test_table (id, name) VALUES (?, ?)',
            [1, 'Test']
        );
        
        // Verify the record is visible within the transaction
        $records = $this->db->query('SELECT * FROM test_table WHERE id = ?', [1]);
        $this->assertCount(1, $records);
        
        // Rollback the transaction
        $this->db->rollback();
        
        // Verify the record is not visible after rollback
        $records = $this->db->query('SELECT * FROM test_table WHERE id = ?', [1]);
        $this->assertCount(0, $records);
        
        // Start a new transaction
        $this->db->beginTransaction();
        
        // Insert a test record
        $this->db->execute(
            'INSERT INTO test_table (id, name) VALUES (?, ?)',
            [1, 'Test']
        );
        
        // Commit the transaction
        $this->db->commit();
        
        // Verify the record is visible after commit
        $records = $this->db->query('SELECT * FROM test_table WHERE id = ?', [1]);
        $this->assertCount(1, $records);
        
        // Clean up
        $this->db->exec('DROP TABLE IF EXISTS test_table');
    }

    public function testCommit()
    {
        // Create a test table
        $this->db->exec('CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY, name VARCHAR(255))');
        
        // Start transaction
        $this->db->beginTransaction();
        
        // Insert a test record
        $this->db->execute(
            'INSERT INTO test_table (id, name) VALUES (?, ?)',
            [1, 'Test']
        );
        
        // Commit the transaction
        $result = $this->db->commit();
        $this->assertTrue($result);
        
        // Verify the record is visible after commit
        $records = $this->db->query('SELECT * FROM test_table WHERE id = ?', [1]);
        $this->assertCount(1, $records);
        $this->assertEquals('Test', $records[0]['name']);
        
        // Clean up
        $this->db->exec('DROP TABLE IF EXISTS test_table');
    }

    public function testRollback()
    {
        // Create a test table
        $this->db->exec('CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY, name VARCHAR(255))');
        
        // Start transaction
        $this->db->beginTransaction();
        
        // Insert a test record
        $this->db->execute(
            'INSERT INTO test_table (id, name) VALUES (?, ?)',
            [1, 'Test']
        );
        
        // Rollback the transaction
        $result = $this->db->rollback();
        $this->assertTrue($result);
        
        // Verify the record is not visible after rollback
        $records = $this->db->query('SELECT * FROM test_table WHERE id = ?', [1]);
        $this->assertCount(0, $records);
        
        // Clean up
        $this->db->exec('DROP TABLE IF EXISTS test_table');
    }

    public function testLastInsertId()
    {
        // Create a test table with auto-increment
        $this->db->exec('CREATE TABLE IF NOT EXISTS test_table (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))');
        
        // Insert a test record
        $this->db->execute(
            'INSERT INTO test_table (name) VALUES (?)',
            ['Test']
        );
        
        // Get the last insert ID
        $id = $this->db->lastInsertId();
        $this->assertNotNull($id);
        $this->assertIsNumeric($id);
        
        // Clean up
        $this->db->exec('DROP TABLE IF EXISTS test_table');
    }

    public function testErrorHandling()
    {
        // Test invalid SQL
        $this->expectException(\PDOException::class);
        $this->db->query('INVALID SQL');
    }
} 
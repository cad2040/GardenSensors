<?php

namespace GardenSensors\Tests\Unit;

use GardenSensors\Database;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOStatement;

class DatabaseTest extends TestCase
{
    private $db;
    private $pdoMock;
    private $statementMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create PDO mock
        $this->pdoMock = $this->createMock(PDO::class);
        
        // Create statement mock
        $this->statementMock = $this->createMock(PDOStatement::class);
        
        // Create database instance with mocked PDO
        $this->db = new Database($this->pdoMock);
    }

    public function testQuery()
    {
        $sql = "SELECT * FROM test WHERE id = ?";
        $params = [1];
        $expectedResult = ['id' => 1, 'name' => 'test'];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($this->statementMock);

        $this->statementMock->expects($this->once())
            ->method('execute')
            ->with($params)
            ->willReturn(true);

        $this->statementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([$expectedResult]);

        $result = $this->db->query($sql, $params);
        $this->assertEquals([$expectedResult], $result);
    }

    public function testQueryWithError()
    {
        $sql = "SELECT * FROM test WHERE id = ?";
        $params = [1];

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('Database error');

        $this->db->query($sql, $params);
    }

    public function testBeginTransaction()
    {
        $this->pdoMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $result = $this->db->beginTransaction();
        $this->assertTrue($result);
    }

    public function testCommit()
    {
        $this->pdoMock->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        $result = $this->db->commit();
        $this->assertTrue($result);
    }

    public function testRollback()
    {
        $this->pdoMock->expects($this->once())
            ->method('rollBack')
            ->willReturn(true);

        $result = $this->db->rollback();
        $this->assertTrue($result);
    }

    public function testLastInsertId()
    {
        $expectedId = 1;

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn($expectedId);

        $result = $this->db->lastInsertId();
        $this->assertEquals($expectedId, $result);
    }

    public function testQuote()
    {
        $value = "test'value";
        $expectedQuoted = "'test\\'value'";

        $this->pdoMock->expects($this->once())
            ->method('quote')
            ->with($value)
            ->willReturn($expectedQuoted);

        $result = $this->db->quote($value);
        $this->assertEquals($expectedQuoted, $result);
    }

    public function testGetConnection()
    {
        $result = $this->db->getConnection();
        $this->assertSame($this->pdoMock, $result);
    }
} 
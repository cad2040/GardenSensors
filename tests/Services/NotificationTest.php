<?php

namespace GardenSensors\Tests\Unit;

use GardenSensors\Notification;
use GardenSensors\Database;
use GardenSensors\Cache;
use GardenSensors\Logger;
use PHPUnit\Framework\TestCase;
use Mockery;

class NotificationTest extends TestCase
{
    private $notification;
    private $dbMock;
    private $cacheMock;
    private $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->dbMock = Mockery::mock(Database::class);
        $this->cacheMock = Mockery::mock(Cache::class);
        $this->loggerMock = Mockery::mock(Logger::class);
        
        // Create notification instance with mocked dependencies
        $this->notification = new Notification(
            $this->dbMock,
            $this->cacheMock,
            $this->loggerMock,
            1 // test user ID
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateNotification()
    {
        $notificationData = [
            'type' => 'alert',
            'message' => 'Low battery alert',
            'data' => ['sensor_id' => 1, 'battery_level' => 20]
        ];

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "INSERT INTO notifications (user_id, type, message, data, created_at) 
                VALUES (?, ?, ?, ?, NOW())",
                [
                    1,
                    $notificationData['type'],
                    $notificationData['message'],
                    json_encode($notificationData['data'])
                ]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('notifications:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Notification created', ['user_id' => 1, 'type' => $notificationData['type']]);

        $result = $this->notification->create(
            $notificationData['type'],
            $notificationData['message'],
            $notificationData['data']
        );
        $this->assertTrue($result);
    }

    public function testMarkAsRead()
    {
        $notificationId = 1;

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?",
                [$notificationId, 1]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('notifications:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Notification marked as read', ['notification_id' => $notificationId, 'user_id' => 1]);

        $result = $this->notification->markAsRead($notificationId);
        $this->assertTrue($result);
    }

    public function testMarkAllAsRead()
    {
        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL",
                [1]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('notifications:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('All notifications marked as read', ['user_id' => 1]);

        $result = $this->notification->markAllAsRead();
        $this->assertTrue($result);
    }

    public function testDeleteNotification()
    {
        $notificationId = 1;

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "DELETE FROM notifications WHERE id = ? AND user_id = ?",
                [$notificationId, 1]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('notifications:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Notification deleted', ['notification_id' => $notificationId, 'user_id' => 1]);

        $result = $this->notification->delete($notificationId);
        $this->assertTrue($result);
    }

    public function testGetUnreadCount()
    {
        $expectedCount = 5;

        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with('notifications:unread:1')
            ->andReturn(null);

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL",
                [1]
            )
            ->andReturn(['count' => $expectedCount]);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with('notifications:unread:1', $expectedCount, 300);

        $result = $this->notification->getUnreadCount();
        $this->assertEquals($expectedCount, $result);
    }

    public function testGetNotifications()
    {
        $expectedNotifications = [
            [
                'id' => 1,
                'type' => 'alert',
                'message' => 'Low battery alert',
                'data' => json_encode(['sensor_id' => 1, 'battery_level' => 20]),
                'created_at' => '2024-01-01 12:00:00'
            ],
            [
                'id' => 2,
                'type' => 'info',
                'message' => 'System update',
                'data' => null,
                'created_at' => '2024-01-01 11:00:00'
            ]
        ];

        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with('notifications:list:1:1:10')
            ->andReturn(null);

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
                [1, 10, 0]
            )
            ->andReturn($expectedNotifications);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with('notifications:list:1:1:10', $expectedNotifications, 300);

        $result = $this->notification->getNotifications(1, 10);
        $this->assertEquals($expectedNotifications, $result);
    }
} 
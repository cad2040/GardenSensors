<?php

namespace GardenSensors\Tests\Services;

use PHPUnit\Framework\TestCase;
use GardenSensors\Services\Notification;
use GardenSensors\Core\Database;
use GardenSensors\Core\Cache;
use GardenSensors\Core\Logger;
use Mockery;

class NotificationTest extends TestCase
{
    private $notification;
    private $db;
    private $cache;
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->db = Mockery::mock(Database::class);
        $this->cache = Mockery::mock(Cache::class);
        $this->logger = Mockery::mock(Logger::class);
        
        // Create notification service with mocks
        $this->notification = new Notification($this->db, $this->cache, $this->logger, 1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCreateNotification()
    {
        // Set up expectations
        $this->db->shouldReceive('execute')
            ->once()
            ->with(
                'INSERT INTO notifications (user_id, type, message, data, created_at) VALUES (?, ?, ?, ?, NOW())',
                [1, 'alert', 'Low battery alert', '{"sensor_id":1,"battery_level":20}']
            )
            ->andReturn(true);
        
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Notification created', ['user_id' => 1, 'type' => 'alert']);
        
        $this->cache->shouldReceive('clear')
            ->once()
            ->with('notifications:1');
        
        // Test notification creation
        $result = $this->notification->create('alert', 'Low battery alert', [
            'sensor_id' => 1,
            'battery_level' => 20
        ]);
        
        $this->assertTrue($result);
    }

    public function testMarkAsRead()
    {
        // Set up expectations
        $this->db->shouldReceive('execute')
            ->once()
            ->with(
                'UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?',
                [1, 1]
            )
            ->andReturn(true);
        
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Notification marked as read', ['notification_id' => 1, 'user_id' => 1]);
        
        $this->cache->shouldReceive('clear')
            ->once()
            ->with('notifications:1');
        
        // Test marking notification as read
        $result = $this->notification->markAsRead(1);
        
        $this->assertTrue($result);
    }

    public function testMarkAllAsRead()
    {
        // Set up expectations
        $this->db->shouldReceive('execute')
            ->once()
            ->with(
                'UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL',
                [1]
            )
            ->andReturn(true);
        
        $this->logger->shouldReceive('info')
            ->once()
            ->with('All notifications marked as read', ['user_id' => 1]);
        
        $this->cache->shouldReceive('clear')
            ->once()
            ->with('notifications:1');
        
        // Test marking all notifications as read
        $result = $this->notification->markAllAsRead();
        
        $this->assertTrue($result);
    }

    public function testDeleteNotification()
    {
        // Set up expectations
        $this->db->shouldReceive('execute')
            ->once()
            ->with(
                'DELETE FROM notifications WHERE id = ? AND user_id = ?',
                [1, 1]
            )
            ->andReturn(true);
        
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Notification deleted', ['notification_id' => 1, 'user_id' => 1]);
        
        $this->cache->shouldReceive('clear')
            ->once()
            ->with('notifications:1');
        
        // Test deleting notification
        $result = $this->notification->delete(1);
        
        $this->assertTrue($result);
    }

    public function testGetUnreadCount()
    {
        // Set up expectations for cache miss
        $this->cache->shouldReceive('get')
            ->once()
            ->with('notifications:unread:1')
            ->andReturn(null);
        
        $this->db->shouldReceive('query')
            ->once()
            ->with(
                'SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL',
                [1]
            )
            ->andReturn([['count' => 5]]);
        
        $this->cache->shouldReceive('set')
            ->once()
            ->with('notifications:unread:1', 5, 300);
        
        // Test getting unread count
        $count = $this->notification->getUnreadCount();
        
        $this->assertEquals(5, $count);
    }

    public function testGetNotifications()
    {
        $notifications = [
            [
                'id' => 1,
                'user_id' => 1,
                'type' => 'alert',
                'message' => 'Test notification',
                'data' => '{"key":"value"}',
                'created_at' => '2024-01-01 12:00:00',
                'read_at' => null
            ]
        ];
        
        // Set up expectations
        $this->cache->shouldReceive('get')
            ->once()
            ->with('notifications:list:1:1:10')
            ->andReturn(null);
        
        $this->db->shouldReceive('query')
            ->once()
            ->with(
                'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?',
                [1, 10, 0]
            )
            ->andReturn($notifications);
        
        $this->cache->shouldReceive('set')
            ->once()
            ->with('notifications:list:1:1:10', $notifications, 300);
        
        // Test getting notifications
        $result = $this->notification->getNotifications(1, 10);
        
        $this->assertEquals($notifications, $result);
    }
} 
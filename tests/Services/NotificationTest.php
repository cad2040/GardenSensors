<?php

namespace GardenSensors\Tests\Unit;

use GardenSensors\Services\Notification;
use GardenSensors\Core\Database;
use GardenSensors\Core\Cache;
use GardenSensors\Core\Logger;
use PHPUnit\Framework\TestCase;
use Mockery;

class NotificationTest extends TestCase
{
    private $db;
    private $cache;
    private $logger;
    private $notification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Mockery::mock(Database::class);
        $this->cache = Mockery::mock(Cache::class);
        $this->logger = Mockery::mock(Logger::class);
        $this->notification = new Notification($this->db, $this->cache, $this->logger);
    }

    public function testCreateNotification()
    {
        $userId = 1;
        $type = 'alert';
        $message = 'Low battery alert';
        $data = ['sensor_id' => 1, 'battery_level' => 20];

        $this->db->shouldReceive('execute')
            ->once()
            ->with(
                'INSERT INTO notifications (user_id, type, message, data, created_at) VALUES (?, ?, ?, ?, NOW())',
                [$userId, $type, $message, json_encode($data)]
            )
            ->andReturn(true);

        $result = $this->notification->create($userId, $type, $message, $data);
        $this->assertTrue($result);
    }

    public function testMarkAsRead()
    {
        $notificationId = 1;
        $userId = 1;

        $this->db->shouldReceive('execute')
            ->once()
            ->with(
                'UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?',
                [$notificationId, $userId]
            )
            ->andReturn(true);

        $result = $this->notification->markAsRead($notificationId, $userId);
        $this->assertTrue($result);
    }

    public function testMarkAllAsRead()
    {
        $userId = 1;

        $this->db->shouldReceive('execute')
            ->once()
            ->with(
                'UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL',
                [$userId]
            )
            ->andReturn(true);

        $result = $this->notification->markAllAsRead($userId);
        $this->assertTrue($result);
    }

    public function testDeleteNotification()
    {
        $notificationId = 1;
        $userId = 1;

        $this->db->shouldReceive('execute')
            ->once()
            ->with(
                'DELETE FROM notifications WHERE id = ? AND user_id = ?',
                [$notificationId, $userId]
            )
            ->andReturn(true);

        $result = $this->notification->delete($notificationId, $userId);
        $this->assertTrue($result);
    }

    public function testGetUnreadCount()
    {
        $userId = 1;
        $count = 5;

        $this->db->shouldReceive('query')
            ->once()
            ->with(
                'SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL',
                [$userId]
            )
            ->andReturn([['count' => $count]]);

        $this->cache->shouldReceive('set')
            ->once()
            ->with('notifications:unread:' . $userId, $count, 300)
            ->andReturn(true);

        $result = $this->notification->getUnreadCount($userId);
        $this->assertEquals($count, $result);
    }

    public function testGetNotifications()
    {
        $userId = 1;
        $notifications = [
            [
                'id' => 1,
                'type' => 'alert',
                'message' => 'Test notification',
                'data' => json_encode(['key' => 'value']),
                'read_at' => null,
                'created_at' => '2024-01-01 00:00:00'
            ]
        ];

        $this->db->shouldReceive('query')
            ->once()
            ->with(
                'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC',
                [$userId]
            )
            ->andReturn($notifications);

        $result = $this->notification->getNotifications($userId);
        $this->assertEquals($notifications, $result);
    }
} 
<?php

namespace GardenSensors\Tests\Unit;

use GardenSensors\Core\Settings;
use GardenSensors\Core\Database;
use GardenSensors\Core\Cache;
use GardenSensors\Core\Logger;
use PHPUnit\Framework\TestCase;
use Mockery;

class SettingsTest extends TestCase
{
    private $settings;
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
        
        // Create settings instance with mocked dependencies
        $this->settings = new Settings(
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

    public function testUpdateSettings()
    {
        $settingsData = [
            'email_notifications' => true,
            'low_battery_alerts' => true,
            'moisture_alerts' => true,
            'temperature_alerts' => true,
            'update_interval' => 300,
            'theme' => 'dark',
            'language' => 'en',
            'timezone' => 'UTC'
        ];

        // Check if settings exist
        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT id FROM user_settings WHERE user_id = ?",
                [1]
            )
            ->andReturn([['id' => 1]]);

        // Update settings
        $this->dbMock->shouldReceive('execute')
            ->once()
            ->with(
                "UPDATE user_settings SET 
                email_notifications = ?,
                low_battery_alerts = ?,
                moisture_alerts = ?,
                temperature_alerts = ?,
                update_interval = ?,
                theme = ?,
                language = ?,
                timezone = ?,
                updated_at = NOW()
                WHERE user_id = ?",
                [
                    $settingsData['email_notifications'],
                    $settingsData['low_battery_alerts'],
                    $settingsData['moisture_alerts'],
                    $settingsData['temperature_alerts'],
                    $settingsData['update_interval'],
                    $settingsData['theme'],
                    $settingsData['language'],
                    $settingsData['timezone'],
                    1
                ]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('settings:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Settings updated', ['user_id' => 1]);

        $result = $this->settings->update($settingsData);
        $this->assertTrue($result);
    }

    public function testGetSettings()
    {
        $expectedSettings = [
            'id' => 1,
            'user_id' => 1,
            'email_notifications' => true,
            'low_battery_alerts' => true,
            'moisture_alerts' => true,
            'temperature_alerts' => true,
            'update_interval' => 300,
            'theme' => 'dark',
            'language' => 'en',
            'timezone' => 'UTC'
        ];

        $this->cacheMock->shouldReceive('get')
            ->once()
            ->with('settings:1')
            ->andReturn(null);

        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT * FROM user_settings WHERE user_id = ?",
                [1]
            )
            ->andReturn([$expectedSettings]);

        $this->cacheMock->shouldReceive('set')
            ->once()
            ->with('settings:1', $expectedSettings, 3600);

        $result = $this->settings->get();
        $this->assertEquals($expectedSettings, $result);
    }

    public function testResetSettings()
    {
        $defaultSettings = [
            'email_notifications' => true,
            'low_battery_alerts' => true,
            'moisture_alerts' => true,
            'temperature_alerts' => true,
            'update_interval' => 300,
            'theme' => 'light',
            'language' => 'en',
            'timezone' => 'UTC'
        ];

        // Check if settings exist
        $this->dbMock->shouldReceive('query')
            ->once()
            ->with(
                "SELECT id FROM user_settings WHERE user_id = ?",
                [1]
            )
            ->andReturn([['id' => 1]]);

        // Reset settings
        $this->dbMock->shouldReceive('execute')
            ->once()
            ->with(
                "UPDATE user_settings SET 
                email_notifications = ?,
                low_battery_alerts = ?,
                moisture_alerts = ?,
                temperature_alerts = ?,
                update_interval = ?,
                theme = ?,
                language = ?,
                timezone = ?,
                updated_at = NOW()
                WHERE user_id = ?",
                [
                    $defaultSettings['email_notifications'],
                    $defaultSettings['low_battery_alerts'],
                    $defaultSettings['moisture_alerts'],
                    $defaultSettings['temperature_alerts'],
                    $defaultSettings['update_interval'],
                    $defaultSettings['theme'],
                    $defaultSettings['language'],
                    $defaultSettings['timezone'],
                    1
                ]
            )
            ->andReturn(true);

        $this->cacheMock->shouldReceive('clear')
            ->once()
            ->with('settings:1');

        $this->loggerMock->shouldReceive('info')
            ->once()
            ->with('Settings updated', ['user_id' => 1]);

        $result = $this->settings->reset();
        $this->assertTrue($result);
    }

    public function testValidateUpdateInterval()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Update interval must be between 60 and 3600 seconds');

        $settingsData = [
            'update_interval' => 30 // Invalid interval
        ];

        $this->settings->update($settingsData);
    }

    public function testValidateTimezone()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid timezone');

        $settingsData = [
            'timezone' => 'Invalid/Timezone' // Invalid timezone
        ];

        $this->settings->update($settingsData);
    }
} 
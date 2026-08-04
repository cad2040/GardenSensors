<?php

namespace GardenSensors\Tests\Services;

use GardenSensors\Services\MailService;
use PHPUnit\Framework\TestCase;

class MailServiceTest extends TestCase
{
    public function testIsConfiguredRequiresSmtpCredentials(): void
    {
        $empty = new MailService(['host' => '', 'username' => '', 'password' => '']);
        $this->assertFalse($empty->isConfigured());

        $ready = new MailService([
            'host' => 'smtp.example.com',
            'username' => 'user',
            'password' => 'secret',
            'port' => 587,
            'encryption' => 'tls',
        ]);
        $this->assertTrue($ready->isConfigured());
    }

    public function testMailFallbackReportsFailureWithoutSmtp(): void
    {
        $mailer = new MailService(['host' => '', 'username' => '', 'password' => '']);
        // May succeed or fail depending on local MTA; method must be mail
        $result = $mailer->send('nobody@example.com', 'test', 'body');
        $this->assertSame('mail', $result['method']);
        $this->assertArrayHasKey('ok', $result);
        $this->assertArrayHasKey('error', $result);
    }
}

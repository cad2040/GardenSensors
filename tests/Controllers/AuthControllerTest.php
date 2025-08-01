<?php
namespace GardenSensors\Tests\Controllers;

use GardenSensors\Tests\TestCase;
use GardenSensors\Controllers\AuthController;

class AuthControllerTest extends TestCase
{
    private $authController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authController = new AuthController();
    }

    public function testAuthControllerInitialization()
    {
        $this->assertInstanceOf(AuthController::class, $this->authController);
    }

    public function testLoginWithValidCredentials()
    {
        $credentials = [
            'username' => 'admin',
            'password' => 'password'
        ];
        
        $result = $this->authController->login($credentials);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function testLoginWithInvalidCredentials()
    {
        $credentials = [
            'username' => 'invalid',
            'password' => 'wrong'
        ];
        
        $result = $this->authController->login($credentials);
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
    }

    public function testLogout()
    {
        $result = $this->authController->logout();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function testPasswordReset()
    {
        $email = 'test@example.com';
        
        $result = $this->authController->resetPassword($email);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function testUserRegistration()
    {
        $userData = [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ];
        
        $result = $this->authController->register($userData);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
} 
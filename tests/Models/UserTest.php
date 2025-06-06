<?php
namespace GardenSensors\Tests\Models;

use GardenSensors\Tests\TestCase;
use GardenSensors\Models\User;
use GardenSensors\Core\Database;

class UserTest extends TestCase {
    private $user;

    protected function setUp(): void {
        parent::setUp();
        
        putenv('TESTING=true');
        
        $this->user = new User([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'user',
            'status' => 'active'
        ]);
    }

    protected function tearDown(): void {
        $this->user->delete();
        parent::tearDown();
    }

    public function testUserCreation() {
        $this->user->save();
        
        $this->assertNotNull($this->user->getId());
        $this->assertEquals('testuser', $this->user->getUsername());
        $this->assertEquals('test@example.com', $this->user->getEmail());
        $this->assertEquals('user', $this->user->getRole());
        $this->assertEquals('active', $this->user->getStatus());
    }

    public function testPasswordHashing() {
        $this->user->setPassword('newpassword');
        $this->user->save();
        
        $this->assertTrue($this->user->verifyPassword('newpassword'));
        $this->assertFalse($this->user->verifyPassword('wrongpassword'));
    }

    public function testUserStatusManagement() {
        $this->user->save();
        
        $this->user->setStatus('inactive');
        $this->user->save();
        
        $this->assertEquals('inactive', $this->user->getStatus());
    }

    public function testUserRoleManagement() {
        $this->user->save();
        
        $this->user->setRole('admin');
        $this->user->save();
        
        $this->assertEquals('admin', $this->user->getRole());
        $this->assertTrue($this->user->isAdmin());
    }

    public function testUserAuthentication() {
        $this->user->save();
        
        $this->assertTrue($this->user->verifyPassword('password123'));
        $this->assertFalse($this->user->verifyPassword('wrongpassword'));
    }

    public function testUserUpdate() {
        $this->user->save();
        
        $this->user->setUsername('updateduser');
        $this->user->setEmail('updated@example.com');
        $this->user->save();
        
        $this->assertEquals('updateduser', $this->user->getUsername());
        $this->assertEquals('updated@example.com', $this->user->getEmail());
    }

    public function testUserDeletion() {
        $this->user->save();
        $id = $this->user->getId();
        
        $this->user->delete();
        
        $deleted = User::find($id);
        $this->assertNull($deleted);
    }

    public function testUserTimestamps() {
        $this->user->save();
        
        $this->assertNotNull($this->user->getCreatedAt());
        $this->assertNotNull($this->user->getUpdatedAt());
    }

    public function testUserValidation() {
        $this->expectException(\InvalidArgumentException::class);
        
        new User([
            'username' => '',
            'email' => 'invalid-email',
            'password' => ''
        ]);
    }

    public function testUserFindByEmail() {
        $this->user->save();
        
        $found = User::findByEmail('test@example.com');
        $this->assertNotNull($found);
        $this->assertEquals($this->user->getId(), $found->getId());
    }

    public function testUserFindByUsername() {
        $this->user->save();
        
        $found = User::findByUsername('testuser');
        $this->assertNotNull($found);
        $this->assertEquals($this->user->getId(), $found->getId());
    }
} 
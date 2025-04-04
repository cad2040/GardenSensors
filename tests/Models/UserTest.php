<?php
namespace Tests\Models;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserTest extends TestCase {
    private $user;

    protected function setUp(): void {
        parent::setUp();
        
        $this->user = new User([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => password_hash('testpassword', PASSWORD_DEFAULT),
            'role' => 'user',
            'status' => 'active'
        ]);
        $this->user->save();
    }

    protected function tearDown(): void {
        $this->user->delete();
        parent::tearDown();
    }

    public function testUserCreation() {
        $this->assertNotNull($this->user->id);
        $this->assertEquals('testuser', $this->user->username);
        $this->assertEquals('test@example.com', $this->user->email);
        $this->assertEquals('user', $this->user->role);
        $this->assertEquals('active', $this->user->status);
    }

    public function testPasswordHashing() {
        $this->assertTrue(password_verify('testpassword', $this->user->password));
        $this->assertFalse(password_verify('wrongpassword', $this->user->password));
    }

    public function testUserStatusManagement() {
        $this->assertTrue($this->user->isActive());
        
        $this->user->updateStatus('inactive');
        $this->assertFalse($this->user->isActive());
        
        $this->user->updateStatus('active');
        $this->assertTrue($this->user->isActive());
    }

    public function testUserRoleManagement() {
        $this->assertEquals('user', $this->user->role);
        
        $this->user->updateRole('admin');
        $this->assertEquals('admin', $this->user->role);
        $this->assertTrue($this->user->isAdmin());
        
        $this->user->updateRole('user');
        $this->assertEquals('user', $this->user->role);
        $this->assertFalse($this->user->isAdmin());
    }

    public function testUserAuthentication() {
        $this->assertTrue($this->user->verifyPassword('testpassword'));
        $this->assertFalse($this->user->verifyPassword('wrongpassword'));
    }

    public function testUserUpdate() {
        $this->user->update([
            'username' => 'updateduser',
            'email' => 'updated@example.com'
        ]);
        
        $this->assertEquals('updateduser', $this->user->username);
        $this->assertEquals('updated@example.com', $this->user->email);
    }

    public function testUserDeletion() {
        $userId = $this->user->id;
        $this->user->delete();
        
        $deletedUser = User::find($userId);
        $this->assertNull($deletedUser);
    }

    public function testUserTimestamps() {
        $this->assertNotNull($this->user->inserted);
        $this->assertNotNull($this->user->updated);
    }

    public function testUserValidation() {
        $this->expectException(\InvalidArgumentException::class);
        
        new User([
            'username' => '',  // Empty username
            'email' => 'invalid-email',  // Invalid email
            'password' => 'short'  // Too short password
        ]);
    }

    public function testUserFindByEmail() {
        $foundUser = User::findByEmail('test@example.com');
        $this->assertNotNull($foundUser);
        $this->assertEquals($this->user->id, $foundUser->id);
    }

    public function testUserFindByUsername() {
        $foundUser = User::findByUsername('testuser');
        $this->assertNotNull($foundUser);
        $this->assertEquals($this->user->id, $foundUser->id);
    }
} 
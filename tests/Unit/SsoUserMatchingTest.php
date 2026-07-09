<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\SsoController;
use App\Models\User;
use App\Services\SsoClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Mockery;

class SsoUserMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected SsoController $controller;
    protected $ssoClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock SsoClientService
        $this->ssoClientMock = Mockery::mock(SsoClientService::class);
        $this->controller = new SsoController($this->ssoClientMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: findOrCreateUser matches by sso_uid first
     */
    public function test_matches_by_sso_uid_first(): void
    {
        // Arrange: Create user with sso_uid
        $user = User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => 'zhangsan@example.com',
            'sso_uid' => 'sso-uid-123',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        $userInfo = [
            'sso_uid' => 'sso-uid-123',
            'email' => 'different@example.com', // Different email
            'name' => '张三',
        ];

        // Act: Use reflection to call protected method
        $method = new \ReflectionMethod($this->controller, 'findOrCreateUser');
        $result = $method->invoke($this->controller, $userInfo);

        // Assert: User matched by sso_uid
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    /**
     * Test: findOrCreateUser matches by email when sso_uid not found
     */
    public function test_matches_by_email_when_sso_uid_not_found(): void
    {
        // Arrange: Create user without sso_uid
        $user = User::create([
            'name' => '李四',
            'employee_no' => 'EMP002',
            'email' => 'lisi@example.com',
            'sso_uid' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        $userInfo = [
            'sso_uid' => 'new-sso-uid',
            'email' => 'lisi@example.com',
            'name' => '李四',
        ];

        // Act
        $method = new \ReflectionMethod($this->controller, 'findOrCreateUser');
        $result = $method->invoke($this->controller, $userInfo);

        // Assert: User matched by email, sso_uid updated
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals('new-sso-uid', $result->fresh()->sso_uid);
    }

    /**
     * Test: findOrCreateUser matches by employee_no as fallback
     */
    public function test_matches_by_employee_no_as_fallback(): void
    {
        // Arrange: Create user without sso_uid and with different email
        $user = User::create([
            'name' => '王五',
            'employee_no' => 'EMP003',
            'email' => 'old-email@example.com',
            'sso_uid' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        $userInfo = [
            'sso_uid' => 'new-sso-uid',
            'email' => 'new-email@example.com', // Different email
            'name' => '王五',
            'employee_no' => 'EMP003', // Same employee_no
        ];

        // Act
        $method = new \ReflectionMethod($this->controller, 'findOrCreateUser');
        $result = $method->invoke($this->controller, $userInfo);

        // Assert: User matched by employee_no
        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    /**
     * Test: findOrCreateUser updates placeholder email with SSO real email
     */
    public function test_updates_placeholder_email_with_sso_real_email(): void
    {
        // Arrange: Create user with placeholder email
        $user = User::create([
            'name' => '赵六',
            'employee_no' => 'EMP004',
            'email' => 'EMP004@internal.renhotec.cn',
            'is_placeholder_email' => true,
            'sso_uid' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        $userInfo = [
            'sso_uid' => 'sso-uid-004',
            'email' => 'zhaoliu@company.com', // Real email from SSO
            'name' => '赵六',
        ];

        // Act
        $method = new \ReflectionMethod($this->controller, 'findOrCreateUser');
        $result = $method->invoke($this->controller, $userInfo);

        // Assert: Email updated, is_placeholder_email set to false
        $this->assertNotNull($result);
        $this->assertEquals('zhaoliu@company.com', $result->fresh()->email);
        $this->assertFalse($result->fresh()->is_placeholder_email);
    }

    /**
     * Test: findOrCreateUser creates new user when no match found
     */
    public function test_creates_new_user_when_no_match_found(): void
    {
        // Arrange: No existing user
        $userInfo = [
            'sso_uid' => 'new-sso-uid',
            'email' => 'newuser@example.com',
            'name' => '新用户',
            'employee_no' => 'EMP005',
        ];

        // Act
        $method = new \ReflectionMethod($this->controller, 'findOrCreateUser');
        $result = $method->invoke($this->controller, $userInfo);

        // Assert: New user created
        $this->assertNotNull($result);
        $this->assertEquals('newuser@example.com', $result->email);
        $this->assertEquals('新用户', $result->name);
        $this->assertEquals('EMP005', $result->employee_no);
    }

    /**
     * Test: findOrCreateUser returns null when no email and auto_create disabled
     */
    public function test_returns_null_when_no_email_and_auto_create_disabled(): void
    {
        // Arrange: No email in userInfo and auto_create disabled
        config(['sso-client.auto_create_user' => false]);

        $userInfo = [
            'sso_uid' => 'sso-uid-006',
            'email' => null,
            'name' => '无邮箱用户',
        ];

        // Act
        $method = new \ReflectionMethod($this->controller, 'findOrCreateUser');
        $result = $method->invoke($this->controller, $userInfo);

        // Assert: Returns null
        $this->assertNull($result);
    }
}

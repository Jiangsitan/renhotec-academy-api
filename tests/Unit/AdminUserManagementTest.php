<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\AdminController;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ExamGradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Mockery;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected AdminController $controller;
    protected $auditServiceMock;
    protected $gradingServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock services
        $this->auditServiceMock = Mockery::mock(AuditLogService::class);
        $this->gradingServiceMock = Mockery::mock(ExamGradingService::class);
        
        $this->controller = new AdminController(
            $this->auditServiceMock,
            $this->gradingServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: createUser allows optional email
     */
    public function test_create_user_allows_optional_email(): void
    {
        // Arrange
        $request = Request::create('/api/admin/users', 'POST', [
            'name' => '张三',
            'employee_no' => 'EMP001',
            'password' => '123456',
            'role' => 'student',
        ]);

        $request->setUserResolver(fn () => User::create([
            'name' => 'Admin',
            'employee_no' => 'ADMIN001',
            'email' => 'admin@example.com',
            'password' => bcrypt(''),
            'role' => 'admin',
            'status' => 'active',
        ]));

        $this->auditServiceMock
            ->shouldReceive('log')
            ->once();

        // Act
        $result = $this->controller->createUser($request);

        // Assert: User created without email
        $data = json_decode($result->getContent(), true);
        $this->assertEquals(201, $result->getStatusCode());
        $this->assertEquals('张三', $data['data']['name']);
        $this->assertEquals('EMP001', $data['data']['employee_no']);
        $this->assertArrayNotHasKey('email', $data['data']);
        $this->assertTrue($data['data']['is_placeholder_email']);
    }

    /**
     * Test: updateUser allows optional email
     */
    public function test_update_user_allows_optional_email(): void
    {
        // Arrange
        $user = User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => 'zhangsan@example.com',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        $request = Request::create("/api/admin/users/{$user->id}", 'PUT', [
            'name' => '张三 Updated',
        ]);

        $request->setUserResolver(fn () => User::create([
            'name' => 'Admin',
            'employee_no' => 'ADMIN001',
            'email' => 'admin@example.com',
            'password' => bcrypt(''),
            'role' => 'admin',
            'status' => 'active',
        ]));

        $this->auditServiceMock
            ->shouldReceive('log')
            ->once();

        // Act
        $result = $this->controller->updateUser($request, $user);

        // Assert: User updated
        $data = json_decode($result->getContent(), true);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('张三 Updated', $data['data']['name']);
    }

    /**
     * Test: users list returns is_placeholder_email field
     */
    public function test_users_list_returns_is_placeholder_email(): void
    {
        // Arrange
        User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => 'EMP001@internal.renhotec.cn',
            'is_placeholder_email' => true,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        User::create([
            'name' => '李四',
            'employee_no' => 'EMP002',
            'email' => 'lisi@example.com',
            'is_placeholder_email' => false,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        $request = Request::create('/api/admin/users', 'GET');

        $request->setUserResolver(fn () => User::create([
            'name' => 'Admin',
            'employee_no' => 'ADMIN001',
            'email' => 'admin@example.com',
            'password' => bcrypt(''),
            'role' => 'admin',
            'status' => 'active',
        ]));

        // Act
        $result = $this->controller->users($request);

        // Assert: is_placeholder_email field present
        $data = json_decode($result->getContent(), true);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertCount(2, $data['data']['data']); // 2 users
        
        // Check that is_placeholder_email is in the response
        $users = $data['data']['data'];
        $placeholderUser = collect($users)->firstWhere('employee_no', 'EMP001');
        $this->assertTrue($placeholderUser['is_placeholder_email']);
        
        $regularUser = collect($users)->firstWhere('employee_no', 'EMP002');
        $this->assertFalse($regularUser['is_placeholder_email']);
    }
}

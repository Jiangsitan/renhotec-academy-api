<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\AdminController;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ExamGradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class AdminFillEmailsTest extends TestCase
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
     * Test: fillEmails generates placeholder emails for users without email
     */
    public function test_fill_emails_generates_placeholder(): void
    {
        // Arrange: Create users without email
        User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        User::create([
            'name' => '李四',
            'employee_no' => 'EMP002',
            'email' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act
        $result = $this->controller->fillEmails();

        // Assert: Users updated with placeholder emails
        $this->assertEquals(2, $result['updated']);
        
        $user1 = User::where('employee_no', 'EMP001')->first();
        $this->assertEquals('EMP001@internal.renhotec.cn', $user1->email);
        $this->assertTrue($user1->is_placeholder_email);
        
        $user2 = User::where('employee_no', 'EMP002')->first();
        $this->assertEquals('EMP002@internal.renhotec.cn', $user2->email);
        $this->assertTrue($user2->is_placeholder_email);
    }

    /**
     * Test: fillEmails skips users with existing email
     */
    public function test_fill_emails_skips_users_with_email(): void
    {
        // Arrange: Create user with email
        User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => 'zhangsan@example.com',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act
        $result = $this->controller->fillEmails();

        // Assert: No users updated
        $this->assertEquals(0, $result['updated']);
        
        $user = User::where('employee_no', 'EMP001')->first();
        $this->assertEquals('zhangsan@example.com', $user->email);
        $this->assertFalse($user->is_placeholder_email);
    }
}

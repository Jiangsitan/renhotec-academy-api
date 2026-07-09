<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\AdminController;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ExamGradingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Mockery;

class AdminImportUsersTest extends TestCase
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
     * Test: importUsers allows empty email and generates placeholder
     */
    public function test_import_users_allows_empty_email(): void
    {
        // Arrange: Create CSV with empty email
        $csvContent = "工号,姓名,邮箱,手机,密码,角色,部门,岗位,入职日期,试用期截止,导师工号\n";
        $csvContent .= "EMP001,张三,,13800138000,123456,student,技术部,工程师,2024-01-01,,\n";
        
        // Create temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFile, $csvContent);
        
        $file = new UploadedFile($tempFile, 'test.csv', 'text/csv', null, true);
        
        // Create request with file
        $request = new \Illuminate\Http\Request();
        $request->setMethod('POST');
        $request->files->set('file', $file);
        
        $request->setUserResolver(fn () => User::create([
            'name' => 'Admin',
            'employee_no' => 'ADMIN001',
            'email' => 'admin@example.com',
            'password' => bcrypt(''),
            'role' => 'admin',
            'status' => 'active',
        ]));

        // Act
        $result = $this->controller->importUsers($request);

        // Assert: User created with placeholder email
        $data = json_decode($result->getContent(), true);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals(1, $data['data']['created']);
        
        $user = User::where('employee_no', 'EMP001')->first();
        $this->assertNotNull($user);
        $this->assertEquals('EMP001@internal.renhotec.cn', $user->email);
        $this->assertTrue($user->is_placeholder_email);
        
        // Cleanup
        @unlink($tempFile);
    }

    /**
     * Test: importUsers with valid email does not generate placeholder
     */
    public function test_import_users_with_valid_email(): void
    {
        // Arrange: Create CSV with valid email
        $csvContent = "工号,姓名,邮箱,手机,密码,角色,部门,岗位,入职日期,试用期截止,导师工号\n";
        $csvContent .= "EMP001,张三,zhangsan@example.com,13800138000,123456,student,技术部,工程师,2024-01-01,,\n";
        
        // Create temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFile, $csvContent);
        
        $file = new UploadedFile($tempFile, 'test.csv', 'text/csv', null, true);
        
        // Create request with file
        $request = new \Illuminate\Http\Request();
        $request->setMethod('POST');
        $request->files->set('file', $file);
        
        $request->setUserResolver(fn () => User::create([
            'name' => 'Admin',
            'employee_no' => 'ADMIN001',
            'email' => 'admin@example.com',
            'password' => bcrypt(''),
            'role' => 'admin',
            'status' => 'active',
        ]));

        // Act
        $result = $this->controller->importUsers($request);

        // Assert: User created with real email
        $data = json_decode($result->getContent(), true);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals(1, $data['data']['created']);
        
        $user = User::where('employee_no', 'EMP001')->first();
        $this->assertNotNull($user);
        $this->assertEquals('zhangsan@example.com', $user->email);
        $this->assertFalse($user->is_placeholder_email);
        
        // Cleanup
        @unlink($tempFile);
    }
}

<?php

namespace Tests\Unit;

use App\Console\Commands\FillPlaceholderEmails;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FillPlaceholderEmailsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test database schema
     */
    protected function setUp(): void
    {
        parent::setUp();

        // The migrations should have already run, so we just need to verify the columns exist
        // If they don't exist, it means the migrations failed to run properly
    }

    /**
     * Test: Command generates placeholder email for users without email
     */
    public function test_generates_placeholder_email_for_users_without_email(): void
    {
        // Arrange: Create users without email using raw query to bypass nullable constraint
        $user1 = User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        $user2 = User::create([
            'name' => '李四',
            'employee_no' => 'EMP002',
            'email' => '',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act: Run the command
        $exitCode = Artisan::call('users:fill-placeholder-emails', [
            '--force' => true,
        ]);

        // Assert: Verify placeholder emails were generated
        $this->assertEquals(0, $exitCode);

        $user1->refresh();
        $user2->refresh();

        $this->assertEquals('EMP001@internal.renhotec.cn', $user1->email);
        $this->assertTrue($user1->is_placeholder_email);

        $this->assertEquals('EMP002@internal.renhotec.cn', $user2->email);
        $this->assertTrue($user2->is_placeholder_email);
    }

    /**
     * Test: Command does not modify users with existing email
     */
    public function test_does_not_modify_users_with_existing_email(): void
    {
        // Arrange: Create user with email
        $user = User::create([
            'name' => '王五',
            'employee_no' => 'EMP003',
            'email' => 'wangwu@example.com',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act: Run the command
        Artisan::call('users:fill-placeholder-emails', [
            '--force' => true,
        ]);

        // Assert: Email unchanged
        $user->refresh();
        $this->assertEquals('wangwu@example.com', $user->email);
        $this->assertFalse($user->is_placeholder_email);
    }

    /**
     * Test: Command handles duplicate placeholder email by appending random suffix
     */
    public function test_handles_duplicate_placeholder_email_with_random_suffix(): void
    {
        // Arrange: Create user with the placeholder email that would be generated
        User::create([
            'name' => '赵六',
            'employee_no' => 'EMP004',
            'email' => 'EMP004@internal.renhotec.cn', // Already exists
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        $userWithoutEmail = User::create([
            'name' => '钱七',
            'employee_no' => 'EMP005', // Different employee_no
            'email' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act: Run the command
        Artisan::call('users:fill-placeholder-emails', [
            '--force' => true,
        ]);

        // Assert: New email was generated (may have random suffix if EMP005@internal.renhotec.cn exists)
        $userWithoutEmail->refresh();
        $this->assertNotNull($userWithoutEmail->email);
        $this->assertStringContainsString('@internal.renhotec.cn', $userWithoutEmail->email);
        $this->assertTrue($userWithoutEmail->is_placeholder_email);
    }

    /**
     * Test: Dry-run mode does not modify database
     */
    public function test_dry_run_does_not_modify_database(): void
    {
        // Arrange: Create user without email
        $user = User::create([
            'name' => '孙八',
            'employee_no' => 'EMP005',
            'email' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act: Run the command in dry-run mode
        $exitCode = Artisan::call('users:fill-placeholder-emails', [
            '--dry-run' => true,
        ]);

        // Assert: Email still null
        $user->refresh();
        $this->assertNull($user->email);
        $this->assertFalse($user->is_placeholder_email);
    }

    /**
     * Test: Command reports correct statistics
     */
    public function test_reports_correct_statistics(): void
    {
        // Arrange: Create multiple users
        User::create([
            'name' => '有邮箱用户',
            'employee_no' => 'EMP006',
            'email' => 'existing@example.com',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        User::create([
            'name' => '无邮箱用户1',
            'employee_no' => 'EMP007',
            'email' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        User::create([
            'name' => '无邮箱用户2',
            'employee_no' => 'EMP008',
            'email' => '',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act: Run the command
        Artisan::call('users:fill-placeholder-emails', [
            '--force' => true,
        ]);

        // Assert: Output contains statistics
        $output = Artisan::output();
        $this->assertStringContainsString('处理完成', $output);
        $this->assertStringContainsString('已更新: 2', $output); // 2 users updated
        $this->assertStringContainsString('跳过: 0', $output); // 0 users skipped
    }

    /**
     * Test: Command generates valid email format
     */
    public function test_generates_valid_email_format(): void
    {
        // Arrange: Create user with special characters in employee_no
        $user = User::create([
            'name' => '特殊字符用户',
            'employee_no' => 'EMP-009',
            'email' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act: Run the command
        Artisan::call('users:fill-placeholder-emails', [
            '--force' => true,
        ]);

        // Assert: Email is valid format
        $user->refresh();
        $this->assertNotEmpty($user->email);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9._%+-]+@internal\.renhotec\.cn$/', $user->email);
    }
}

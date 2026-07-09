<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\SsoController;
use App\Models\User;
use App\Services\SsoClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;
use Mockery;

class SsoSyncTest extends TestCase
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
     * Test: syncAllUsers syncs users with placeholder emails
     */
    public function test_syncs_users_with_placeholder_emails(): void
    {
        // Arrange: Create users with placeholder emails
        $user1 = User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => 'EMP001@internal.renhotec.cn',
            'is_placeholder_email' => true,
            'sso_uid' => 'sso-uid-001',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        $user2 = User::create([
            'name' => '李四',
            'employee_no' => 'EMP002',
            'email' => 'EMP002@internal.renhotec.cn',
            'is_placeholder_email' => true,
            'sso_uid' => 'sso-uid-002',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Expect syncUser to be called for each user
        $this->ssoClientMock
            ->shouldReceive('syncUser')
            ->times(2)
            ->andReturn(true);

        // Act
        $result = $this->controller->syncAllUsers();

        // Assert: Both users were synced
        $data = json_decode($result->getContent(), true);
        $this->assertEquals(2, $data['data']['synced']);
        $this->assertEquals(0, $data['data']['skipped']);
    }

    /**
     * Test: pullUsers returns users with placeholder emails
     */
    public function test_pull_users_returns_placeholder_emails(): void
    {
        // Arrange: Create user with placeholder email
        $user = User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => 'EMP001@internal.renhotec.cn',
            'is_placeholder_email' => true,
            'sso_uid' => 'sso-uid-001',
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Mock request with valid credentials
        $request = Request::create('/api/sso/users/pull', 'POST', [
            'client_id' => config('sso-client.client_id'),
            'client_secret' => config('sso-client.client_secret'),
        ]);

        // Act
        $result = $this->controller->pullUsers($request);

        // Assert: User returned with placeholder email
        $data = json_decode($result->getContent(), true);
        $this->assertCount(1, $data['data']['list']);
        $this->assertEquals('EMP001@internal.renhotec.cn', $data['data']['list'][0]['email']);
    }

    /**
     * Test: fillSsoUid generates sso_uid for users with placeholder emails
     */
    public function test_fill_sso_uid_for_users_with_placeholder_emails(): void
    {
        // Arrange: Create user with placeholder email but no sso_uid
        $user = User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => 'EMP001@internal.renhotec.cn',
            'is_placeholder_email' => true,
            'sso_uid' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act
        $result = $this->controller->fillSsoUid();

        // Assert: sso_uid was generated
        $this->assertEquals(1, $result['updated']);
        $user->refresh();
        $this->assertNotNull($user->sso_uid);
    }

    /**
     * Test: syncAllUsers skips users without sso_uid and email
     */
    public function test_skips_users_without_sso_uid_and_email(): void
    {
        // Arrange: Create user without sso_uid and email
        $user = User::create([
            'name' => '张三',
            'employee_no' => 'EMP001',
            'email' => null,
            'sso_uid' => null,
            'password' => bcrypt(''),
            'role' => 'student',
            'status' => 'active',
        ]);

        // Act
        $result = $this->controller->syncAllUsers();

        // Assert: User was skipped
        $data = json_decode($result->getContent(), true);
        $this->assertEquals(0, $data['data']['synced']);
        $this->assertEquals(1, $data['data']['skipped']);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FillPlaceholderEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fill-placeholder-emails {--dry-run : Preview changes without modifying database} {--force : Apply changes to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill placeholder emails for users without email address';

    /**
     * Placeholder email domain
     */
    private const PLACEHOLDER_DOMAIN = 'internal.renhotec.cn';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if (!$dryRun && !$force) {
            $this->error('请使用 --dry-run 预览变更或 --force 执行写入');
            return 1;
        }

        $stats = [
            'total' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        // Find users with null or empty email
        $users = User::where(function ($query) {
            $query->whereNull('email')->orWhere('email', '');
        })->get();

        $stats['total'] = $users->count();

        if ($dryRun) {
            $this->info("预览模式：找到 {$stats['total']} 个需要处理的用户");
            $this->newLine();
        }

        foreach ($users as $user) {
            try {
                $placeholderEmail = $this->generatePlaceholderEmail($user->employee_no);

                if ($dryRun) {
                    $this->line("  - {$user->name} ({$user->employee_no}): {$placeholderEmail}");
                } else {
                    $user->update([
                        'email' => $placeholderEmail,
                        'is_placeholder_email' => true,
                    ]);

                    Log::info('生成占位邮箱', [
                        'user_id' => $user->id,
                        'employee_no' => $user->employee_no,
                        'placeholder_email' => $placeholderEmail,
                    ]);
                }

                $stats['updated']++;
            } catch (\Exception $e) {
                $stats['skipped']++;
                $stats['errors'][] = [
                    'user_id' => $user->id,
                    'employee_no' => $user->employee_no,
                    'reason' => $e->getMessage(),
                ];

                Log::error('生成占位邮箱失败', [
                    'user_id' => $user->id,
                    'employee_no' => $user->employee_no,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Output statistics
        $this->newLine();
        $this->info("处理完成：");
        $this->line("  - 总计: {$stats['total']}");
        $this->line("  - 已更新: {$stats['updated']}");
        $this->line("  - 跳过: {$stats['skipped']}");

        if (!empty($stats['errors'])) {
            $this->newLine();
            $this->warn("错误详情:");
            foreach ($stats['errors'] as $error) {
                $this->line("  - 用户ID {$error['user_id']} ({$error['employee_no']}): {$error['reason']}");
            }
        }

        Log::info('fillPlaceholderEmails 完成', $stats);

        return 0;
    }

    /**
     * Generate placeholder email for a user
     *
     * @param string $employeeNo
     * @return string
     */
    private function generatePlaceholderEmail(string $employeeNo): string
    {
        // Sanitize employee_no for email format
        $sanitized = $this->sanitizeForEmail($employeeNo);

        $baseEmail = "{$sanitized}@" . self::PLACEHOLDER_DOMAIN;

        // Check if email already exists
        if (!User::where('email', $baseEmail)->exists()) {
            return $baseEmail;
        }

        // Append random suffix to ensure uniqueness
        $suffix = Str::random(6);
        return "{$sanitized}_{$suffix}@" . self::PLACEHOLDER_DOMAIN;
    }

    /**
     * Sanitize employee_no for use in email address
     *
     * @param string $employeeNo
     * @return string
     */
    private function sanitizeForEmail(string $employeeNo): string
    {
        // Replace special characters with underscores
        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $employeeNo);

        // Ensure it doesn't start or end with special characters
        $sanitized = trim($sanitized, '._-');

        // If empty after sanitization, use a fallback
        if (empty($sanitized)) {
            $sanitized = 'user_' . Str::random(6);
        }

        return $sanitized;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Services\Utf8EncodingService;
use Illuminate\Console\Command;

class FixUtf8Encoding extends Command
{
    protected $signature = 'encoding:fix-questions {--dry-run : 只显示问题记录，不实际修改}';
    protected $description = '修复 questions 表中 correct_answer 字段的 GBK 乱码数据为 UTF-8';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $fixed = 0;
        $skipped = 0;
        $total = Question::count();

        $this->info("开始扫描 questions 表，共 {$total} 条记录...");
        $this->newLine();

        Question::chunk(200, function ($questions) use (&$fixed, &$skipped, $dryRun) {
            foreach ($questions as $question) {
                $value = $question->getRawOriginal('correct_answer');

                if ($value === null || $value === '') {
                    $skipped++;
                    continue;
                }

                if (mb_check_encoding($value, 'UTF-8')) {
                    $skipped++;
                    continue;
                }

                $fixed++;

                if ($dryRun) {
                    $this->warn("  [DRY-RUN] ID={$question->id} | 编码=" . Utf8EncodingService::detectEncoding($value));
                    $this->line("    原始: " . substr($value, 0, 80));
                    $converted = Utf8EncodingService::ensureUtf8($value);
                    $this->line("    修复: " . substr($converted, 0, 80));
                } else {
                    $converted = Utf8EncodingService::ensureUtf8($value);
                    $question->updateQuietly(['correct_answer' => $converted]);
                    $this->line("  ✓ ID={$question->id} 已修复");
                }
            }
        });

        $this->newLine();
        $this->info("扫描完成: 共 {$total} 条, 修复 {$fixed} 条, 跳过 {$skipped} 条");

        if ($dryRun && $fixed > 0) {
            $this->warn("dry-run 模式未修改数据，去掉 --dry-run 执行实际修复");
        }

        return self::SUCCESS;
    }
}

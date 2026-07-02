<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Services\Utf8EncodingService;
use Illuminate\Console\Command;

class FixUtf8Encoding extends Command
{
    protected $signature = 'encoding:fix-questions {--dry-run : 只显示问题记录，不实际修改} {--double-encode : 额外处理双重编码的 UTF-8 数据}';
    protected $description = '修复 questions 表中 correct_answer 字段的 GBK 乱码数据为 UTF-8';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $doubleEncode = $this->option('double-encode');
        $fixed = 0;
        $skipped = 0;
        $total = Question::count();

        $mode = $doubleEncode ? '双重编码修复' : '编码修复';
        $this->info("开始扫描 questions 表 [{$mode}]，共 {$total} 条记录...");
        $this->newLine();

        Question::chunk(200, function ($questions) use (&$fixed, &$skipped, $dryRun, $doubleEncode) {
            foreach ($questions as $question) {
                $value = $question->getRawOriginal('correct_answer');

                if ($value === null || $value === '') {
                    $skipped++;
                    continue;
                }

                if ($doubleEncode) {
                    // 双重编码模式: 只处理 mb_check_encoding 通过但实际是双重编码的数据
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        $skipped++;
                        continue;
                    }
                    $decoded = Utf8EncodingService::decodeDoubleUtf8($value);
                    if ($decoded === $value) {
                        $skipped++;
                        continue;
                    }
                    $fixed++;
                    if ($dryRun) {
                        $this->warn("  [DRY-RUN] ID={$question->id} | 双重编码");
                        $this->line("    原始: " . substr($value, 0, 80));
                        $this->line("    修复: " . substr($decoded, 0, 80));
                    } else {
                        $question->updateQuietly(['correct_answer' => $decoded]);
                        $this->line("  ✓ ID={$question->id} 双重编码已修复");
                    }
                } else {
                    // 标准模式: 处理非 UTF-8 编码的数据
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

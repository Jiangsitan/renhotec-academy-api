<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return; // SQLite doesn't support ALTER TABLE COMMENT
        }

        // 自定义业务表
        DB::statement("ALTER TABLE comment_likes COMMENT = '评论点赞表'");
        DB::statement("ALTER TABLE comments COMMENT = '课程评论表'");
        DB::statement("ALTER TABLE exam_cheats COMMENT = '考试作弊记录表'");
        DB::statement("ALTER TABLE exam_courses COMMENT = '考试-课程关联表'");
        DB::statement("ALTER TABLE notifications COMMENT = '系统通知表'");

        // Laravel 内部表
        DB::statement("ALTER TABLE cache COMMENT = 'Laravel 缓存表'");
        DB::statement("ALTER TABLE cache_locks COMMENT = 'Laravel 缓存锁表'");
        DB::statement("ALTER TABLE failed_jobs COMMENT = 'Laravel 失败任务表'");
        DB::statement("ALTER TABLE job_batches COMMENT = 'Laravel 任务批次表'");
        DB::statement("ALTER TABLE jobs COMMENT = 'Laravel 任务表'");
        DB::statement("ALTER TABLE migrations COMMENT = 'Laravel 迁移记录表'");
        DB::statement("ALTER TABLE password_reset_tokens COMMENT = 'Laravel 密码重置表'");
        DB::statement("ALTER TABLE personal_access_tokens COMMENT = 'Laravel 个人访问令牌表'");
        DB::statement("ALTER TABLE sessions COMMENT = 'Laravel 会话表'");
    }

    public function down(): void
    {
        $tables = [
            'comment_likes', 'comments', 'exam_cheats', 'exam_courses', 'notifications',
            'cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs',
            'migrations', 'password_reset_tokens', 'personal_access_tokens', 'sessions'
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} COMMENT = ''");
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== 自定义业务表 ====================
        
        // comment_likes 表
        DB::statement("ALTER TABLE comment_likes COMMENT = '评论点赞表'");
        DB::statement("ALTER TABLE comment_likes MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID'");
        DB::statement("ALTER TABLE comment_likes MODIFY COLUMN comment_id bigint unsigned NOT NULL COMMENT '评论ID'");
        DB::statement("ALTER TABLE comment_likes MODIFY COLUMN user_id bigint unsigned NOT NULL COMMENT '点赞用户ID'");
        DB::statement("ALTER TABLE comment_likes MODIFY COLUMN created_at timestamp COMMENT '创建时间'");

        // comments 表
        DB::statement("ALTER TABLE comments COMMENT = '课程评论表'");
        DB::statement("ALTER TABLE comments MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID'");
        DB::statement("ALTER TABLE comments MODIFY COLUMN series_id bigint unsigned NOT NULL COMMENT '所属系列ID'");
        DB::statement("ALTER TABLE comments MODIFY COLUMN user_id bigint unsigned NOT NULL COMMENT '评论用户ID'");
        DB::statement("ALTER TABLE comments MODIFY COLUMN parent_id bigint unsigned COMMENT '父评论ID（用于回复）'");
        DB::statement("ALTER TABLE comments MODIFY COLUMN content text NOT NULL COMMENT '评论内容'");
        DB::statement("ALTER TABLE comments MODIFY COLUMN likes_count int unsigned NOT NULL COMMENT '点赞数'");
        DB::statement("ALTER TABLE comments MODIFY COLUMN created_at timestamp COMMENT '创建时间'");
        DB::statement("ALTER TABLE comments MODIFY COLUMN updated_at timestamp COMMENT '更新时间'");

        // exam_cheats 表
        DB::statement("ALTER TABLE exam_cheats COMMENT = '考试作弊记录表'");
        DB::statement("ALTER TABLE exam_cheats MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID'");
        DB::statement("ALTER TABLE exam_cheats MODIFY COLUMN user_id bigint unsigned NOT NULL COMMENT '学员用户ID'");
        DB::statement("ALTER TABLE exam_cheats MODIFY COLUMN exam_id bigint unsigned NOT NULL COMMENT '考试ID'");
        DB::statement("ALTER TABLE exam_cheats MODIFY COLUMN exam_record_id bigint unsigned COMMENT '考试记录ID'");
        DB::statement("ALTER TABLE exam_cheats MODIFY COLUMN action varchar(50) NOT NULL COMMENT '作弊类型：leave_page=离开页面, blur=窗口失焦, exit_fullscreen=退出全屏'");
        DB::statement("ALTER TABLE exam_cheats MODIFY COLUMN detail varchar(500) COMMENT '详情'");
        DB::statement("ALTER TABLE exam_cheats MODIFY COLUMN created_at timestamp COMMENT '创建时间'");
        DB::statement("ALTER TABLE exam_cheats MODIFY COLUMN updated_at timestamp COMMENT '更新时间'");

        // exam_courses 表
        DB::statement("ALTER TABLE exam_courses COMMENT = '考试-课程关联表'");
        DB::statement("ALTER TABLE exam_courses MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID'");
        DB::statement("ALTER TABLE exam_courses MODIFY COLUMN exam_id bigint unsigned NOT NULL COMMENT '考试ID'");
        DB::statement("ALTER TABLE exam_courses MODIFY COLUMN course_id bigint unsigned NOT NULL COMMENT '课程ID'");
        DB::statement("ALTER TABLE exam_courses MODIFY COLUMN created_at timestamp COMMENT '创建时间'");
        DB::statement("ALTER TABLE exam_courses MODIFY COLUMN updated_at timestamp COMMENT '更新时间'");

        // notifications 表
        DB::statement("ALTER TABLE notifications COMMENT = '系统通知表'");
        DB::statement("ALTER TABLE notifications MODIFY COLUMN id char(36) NOT NULL COMMENT '主键ID'");
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type varchar(255) NOT NULL COMMENT '通知类型'");
        DB::statement("ALTER TABLE notifications MODIFY COLUMN notifiable_type varchar(255) NOT NULL COMMENT '关联对象类型'");
        DB::statement("ALTER TABLE notifications MODIFY COLUMN notifiable_id bigint unsigned NOT NULL COMMENT '关联对象ID'");
        DB::statement("ALTER TABLE notifications MODIFY COLUMN data text NOT NULL COMMENT '通知数据（JSON格式）'");
        DB::statement("ALTER TABLE notifications MODIFY COLUMN read_at timestamp COMMENT '已读时间'");
        DB::statement("ALTER TABLE notifications MODIFY COLUMN created_at timestamp COMMENT '创建时间'");
        DB::statement("ALTER TABLE notifications MODIFY COLUMN updated_at timestamp COMMENT '更新时间'");

        // ==================== Laravel 内部表 ====================
        
        // cache 表
        DB::statement("ALTER TABLE cache COMMENT = 'Laravel 缓存表'");
        DB::statement("ALTER TABLE cache MODIFY COLUMN `key` varchar(255) NOT NULL COMMENT '缓存键'");
        DB::statement("ALTER TABLE cache MODIFY COLUMN value mediumtext NOT NULL COMMENT '缓存值'");
        DB::statement("ALTER TABLE cache MODIFY COLUMN expiration bigint NOT NULL COMMENT '过期时间'");

        // cache_locks 表
        DB::statement("ALTER TABLE cache_locks COMMENT = 'Laravel 缓存锁表'");
        DB::statement("ALTER TABLE cache_locks MODIFY COLUMN `key` varchar(255) NOT NULL COMMENT '锁键'");
        DB::statement("ALTER TABLE cache_locks MODIFY COLUMN owner varchar(255) NOT NULL COMMENT '锁所有者'");
        DB::statement("ALTER TABLE cache_locks MODIFY COLUMN expiration int NOT NULL COMMENT '过期时间'");

        // failed_jobs 表
        DB::statement("ALTER TABLE failed_jobs COMMENT = 'Laravel 失败任务表'");
        DB::statement("ALTER TABLE failed_jobs MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID'");
        DB::statement("ALTER TABLE failed_jobs MODIFY COLUMN uuid varchar(255) NOT NULL COMMENT '任务UUID'");
        DB::statement("ALTER TABLE failed_jobs MODIFY COLUMN connection varchar(255) NOT NULL COMMENT '连接名称'");
        DB::statement("ALTER TABLE failed_jobs MODIFY COLUMN queue varchar(255) NOT NULL COMMENT '队列名称'");
        DB::statement("ALTER TABLE failed_jobs MODIFY COLUMN payload longtext NOT NULL COMMENT '任务负载'");
        DB::statement("ALTER TABLE failed_jobs MODIFY COLUMN exception longtext NOT NULL COMMENT '异常信息'");
        DB::statement("ALTER TABLE failed_jobs MODIFY COLUMN failed_at timestamp NOT NULL COMMENT '失败时间'");

        // job_batches 表
        DB::statement("ALTER TABLE job_batches COMMENT = 'Laravel 任务批次表'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN id varchar(255) NOT NULL COMMENT '批次ID'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN name varchar(255) NOT NULL COMMENT '批次名称'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN total_jobs int NOT NULL COMMENT '总任务数'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN pending_jobs int NOT NULL COMMENT '待处理任务数'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN failed_jobs int NOT NULL COMMENT '失败任务数'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN failed_job_ids longtext NOT NULL COMMENT '失败任务ID列表'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN options mediumtext COMMENT '选项'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN cancelled_at int COMMENT '取消时间'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN created_at int NOT NULL COMMENT '创建时间'");
        DB::statement("ALTER TABLE job_batches MODIFY COLUMN finished_at int COMMENT '完成时间'");

        // jobs 表
        DB::statement("ALTER TABLE jobs COMMENT = 'Laravel 任务表'");
        DB::statement("ALTER TABLE jobs MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID'");
        DB::statement("ALTER TABLE jobs MODIFY COLUMN queue varchar(255) NOT NULL COMMENT '队列名称'");
        DB::statement("ALTER TABLE jobs MODIFY COLUMN payload longtext NOT NULL COMMENT '任务负载'");
        DB::statement("ALTER TABLE jobs MODIFY COLUMN attempts tinyint unsigned NOT NULL COMMENT '尝试次数'");
        DB::statement("ALTER TABLE jobs MODIFY COLUMN reserved_at int unsigned COMMENT '预留时间'");
        DB::statement("ALTER TABLE jobs MODIFY COLUMN available_at int unsigned NOT NULL COMMENT '可用时间'");
        DB::statement("ALTER TABLE jobs MODIFY COLUMN created_at int unsigned NOT NULL COMMENT '创建时间'");

        // migrations 表
        DB::statement("ALTER TABLE migrations COMMENT = 'Laravel 迁移记录表'");
        DB::statement("ALTER TABLE migrations MODIFY COLUMN id int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID'");
        DB::statement("ALTER TABLE migrations MODIFY COLUMN migration varchar(255) NOT NULL COMMENT '迁移文件名'");
        DB::statement("ALTER TABLE migrations MODIFY COLUMN batch int NOT NULL COMMENT '批次号'");

        // password_reset_tokens 表
        DB::statement("ALTER TABLE password_reset_tokens COMMENT = 'Laravel 密码重置表'");
        DB::statement("ALTER TABLE password_reset_tokens MODIFY COLUMN email varchar(255) NOT NULL COMMENT '邮箱地址'");
        DB::statement("ALTER TABLE password_reset_tokens MODIFY COLUMN token varchar(255) NOT NULL COMMENT '重置令牌'");
        DB::statement("ALTER TABLE password_reset_tokens MODIFY COLUMN created_at timestamp COMMENT '创建时间'");

        // personal_access_tokens 表
        DB::statement("ALTER TABLE personal_access_tokens COMMENT = 'Laravel 个人访问令牌表'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN id bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN tokenable_type varchar(255) NOT NULL COMMENT '令牌所属类型'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN tokenable_id bigint unsigned NOT NULL COMMENT '令牌所属ID'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN name varchar(255) NOT NULL COMMENT '令牌名称'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN token varchar(64) NOT NULL COMMENT '令牌哈希'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN abilities text COMMENT '权限列表'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN last_used_at timestamp COMMENT '最后使用时间'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN expires_at timestamp COMMENT '过期时间'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN created_at timestamp COMMENT '创建时间'");
        DB::statement("ALTER TABLE personal_access_tokens MODIFY COLUMN updated_at timestamp COMMENT '更新时间'");

        // sessions 表
        DB::statement("ALTER TABLE sessions COMMENT = 'Laravel 会话表'");
        DB::statement("ALTER TABLE sessions MODIFY COLUMN id varchar(255) NOT NULL COMMENT '会话ID'");
        DB::statement("ALTER TABLE sessions MODIFY COLUMN user_id bigint unsigned COMMENT '用户ID'");
        DB::statement("ALTER TABLE sessions MODIFY COLUMN ip_address varchar(45) COMMENT 'IP地址'");
        DB::statement("ALTER TABLE sessions MODIFY COLUMN user_agent text COMMENT '用户代理'");
        DB::statement("ALTER TABLE sessions MODIFY COLUMN payload longtext NOT NULL COMMENT '会话数据'");
        DB::statement("ALTER TABLE sessions MODIFY COLUMN last_activity int NOT NULL COMMENT '最后活动时间'");
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

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

        DB::statement("ALTER TABLE users COMMENT = '用户表：存储员工信息、角色、状态等'");
        DB::statement("ALTER TABLE categories COMMENT = '课程分类表：支持两级分类结构'");
        DB::statement("ALTER TABLE series COMMENT = '培训系列表：关联分类，包含多个课程'");
        DB::statement("ALTER TABLE courses COMMENT = '课程表：支持视频和文档两种类型'");
        DB::statement("ALTER TABLE exams COMMENT = '考试表：关联课程，设置时长和及格分'");
        DB::statement("ALTER TABLE questions COMMENT = '题目表：支持单选、多选、判断、简答题型'");
        DB::statement("ALTER TABLE exam_records COMMENT = '考试记录表：记录学员考试成绩和状态'");
        DB::statement("ALTER TABLE learning_progress COMMENT = '学习进度表：记录学员课程学习进度和时长'");
        DB::statement("ALTER TABLE mentor_student COMMENT = '导师学员绑定表：多对多关系'");
        DB::statement("ALTER TABLE attachments COMMENT = '课程附件表：区分课程附件和工具附件'");
        DB::statement("ALTER TABLE audit_logs COMMENT = '审计日志表：记录系统操作日志'");
        DB::statement("ALTER TABLE settings COMMENT = '系统设置表：存储键值对配置'");
    }

    public function down(): void
    {
        // 无回滚支持
    }
};

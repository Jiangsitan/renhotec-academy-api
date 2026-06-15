<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 只在 parent_id 列存在时才执行（说明是原始 categories 表结构）
        if (!Schema::hasColumn('categories', 'parent_id')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            // 安全删除外键（如果存在）
            $foreigns = Schema::getForeignKeys('categories');
            foreach ($foreigns as $fk) {
                if (in_array('parent_id', $fk['columns'] ?? [])) {
                    $table->dropForeign($fk['name']);
                }
            }

            // 安全删除索引（如果存在）
            $indexes = Schema::getIndexes('categories');
            foreach ($indexes as $index) {
                if (in_array('parent_id', $index['columns'] ?? [])) {
                    $table->dropIndex($index['name']);
                }
            }

            $table->dropColumn('parent_id');
        });

        // level 列可能不存在，单独处理
        if (Schema::hasColumn('categories', 'level')) {
            Schema::table('categories', function (Blueprint $table) {
                $indexes = Schema::getIndexes('categories');
                foreach ($indexes as $index) {
                    if (in_array('level', $index['columns'] ?? [])) {
                        $table->dropIndex($index['name']);
                    }
                }
                $table->dropColumn('level');
            });
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('name')->constrained('categories')->nullOnDelete();
            $table->tinyInteger('level')->default(1)->after('parent_id');
            $table->index('parent_id');
            $table->index(['parent_id', 'sort_order']);
            $table->index('level');
        });
    }
};

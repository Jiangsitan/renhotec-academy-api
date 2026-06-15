<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->enum('type', ['document', 'video'])->comment('课程类型');
                $table->string('content_url', 500)->comment('视频URL或文档路径');
                $table->string('cover_image', 500)->nullable();
                $table->unsignedInteger('min_read_time')->default(0)->comment('文档最低阅读秒数');
                $table->unsignedInteger('duration')->default(0)->comment('视频总时长秒数');
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('category_id');
                $table->index('type');
                $table->index('status');
                $table->index(['category_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};

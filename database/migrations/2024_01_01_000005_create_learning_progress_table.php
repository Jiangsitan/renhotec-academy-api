<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('learning_progress')) {
            Schema::create('learning_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->boolean('is_completed')->default(false);
                $table->decimal('progress_percentage', 5, 2)->default(0);
                $table->unsignedInteger('total_learning_time')->default(0)->comment('累计学习秒数');
                $table->unsignedInteger('last_position_seconds')->default(0)->comment('视频上次播放位置(秒)');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'course_id']);
                $table->index('user_id');
                $table->index('course_id');
                $table->index('is_completed');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_progress');
    }
};

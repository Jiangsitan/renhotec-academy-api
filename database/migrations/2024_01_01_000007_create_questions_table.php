<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
                $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete()->comment('关联课程，用于错题回跳');
                $table->enum('type', ['single', 'multiple', 'truefalse', 'short_answer'])->comment('题型');
                $table->text('content')->comment('题干');
                $table->json('options')->nullable()->comment('选项');
                $table->text('correct_answer')->comment('正确答案');
                $table->decimal('score', 5, 2)->comment('分值');
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('exam_id');
                $table->index('course_id');
                $table->index('type');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

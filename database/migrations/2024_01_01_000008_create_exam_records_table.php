<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->json('answers')->comment('答题详情');
            $table->decimal('objective_score', 5, 2)->default(0)->comment('客观题自动评分');
            $table->decimal('subjective_score', 5, 2)->nullable()->comment('主观题人工评分');
            $table->decimal('total_score', 5, 2)->nullable()->comment('总分');
            $table->enum('status', ['in_progress', 'submitted', 'auto_graded', 'pending_review', 'graded'])->default('in_progress');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('mentor_comment')->nullable()->comment('导师评语');
            $table->timestamps();

            $table->index(['user_id', 'exam_id']);
            $table->index('status');
            $table->index('graded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_records');
    }
};

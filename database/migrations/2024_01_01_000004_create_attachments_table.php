<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attachments')) {
            Schema::create('attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->string('file_name', 255)->comment('原始文件名');
                $table->string('file_path', 500)->comment('存储路径');
                $table->unsignedBigInteger('file_size')->default(0)->comment('文件大小(字节)');
                $table->string('mime_type', 100)->nullable();
                $table->unsignedInteger('download_count')->default(0);
                $table->timestamps();

                $table->index('course_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('content_source', ['online', 'local'])->default('online')->after('content_url')->comment('内容来源');
            $table->string('file_name', 255)->nullable()->after('content_source')->comment('原始文件名');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name')->comment('文件大小(字节)');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['content_source', 'file_name', 'file_size']);
        });
    }
};

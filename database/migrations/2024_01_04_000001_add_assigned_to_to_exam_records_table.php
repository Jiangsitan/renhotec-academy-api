<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_records', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('graded_by')->comment('分配给谁批改');
            $table->string('assignment_note', 500)->nullable()->after('assigned_to')->comment('分配备注');
        });
    }

    public function down(): void
    {
        Schema::table('exam_records', function (Blueprint $table) {
            $table->dropColumn(['assigned_to', 'assignment_note']);
        });
    }
};

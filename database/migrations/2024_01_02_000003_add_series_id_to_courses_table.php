<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('courses', 'series_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreignId('series_id')->nullable()->after('category_id')->constrained('series')->nullOnDelete();
                $table->index('series_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropIndex('courses_series_id_index');
            $table->dropColumn('series_id');
        });
    }
};

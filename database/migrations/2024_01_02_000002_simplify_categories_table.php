<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex('categories_parent_id_index');
            $table->dropIndex('categories_parent_id_sort_order_index');
            $table->dropIndex('categories_level_index');
            $table->dropColumn(['parent_id', 'level']);
        });
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

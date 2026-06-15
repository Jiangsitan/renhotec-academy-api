<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('series')) {
            Schema::create('series', function (Blueprint $table) {
                $table->id();
                $table->string('name', 200)->comment('系列名称');
                $table->text('description')->nullable()->comment('系列简介');
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->string('cover_image', 500)->nullable()->comment('封面图');
                $table->integer('sort_order')->default(0);
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->timestamps();

                $table->index('category_id');
                $table->index('status');
                $table->index(['category_id', 'status', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};

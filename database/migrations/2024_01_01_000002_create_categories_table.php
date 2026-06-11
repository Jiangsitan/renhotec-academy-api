<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->tinyInteger('level')->default(1)->comment('层级: 1/2/3');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('parent_id');
            $table->index(['parent_id', 'sort_order']);
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('employee_no', 50)->unique()->comment('工号');
                $table->string('email', 255)->unique();
                $table->string('phone', 20)->nullable();
                $table->string('password');
                $table->string('department', 100)->nullable()->comment('部门');
                $table->string('position', 100)->nullable()->comment('岗位');
                $table->enum('role', ['student', 'mentor', 'admin'])->default('student')->index();
                $table->enum('status', ['active', 'inactive', 'locked'])->default('active')->index();
                $table->date('hire_date')->nullable()->comment('入职日期');
                $table->date('trial_end_date')->nullable()->comment('试用期截止日');
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

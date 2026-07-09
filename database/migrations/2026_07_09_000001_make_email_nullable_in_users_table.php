<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: Recreate table with nullable email
            DB::statement('PRAGMA foreign_keys = OFF');
            DB::statement('ALTER TABLE users RENAME TO users_old');
            DB::statement('
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    sso_uid VARCHAR(255) NULL,
                    name VARCHAR(100) NOT NULL,
                    employee_no VARCHAR(50) NOT NULL,
                    email VARCHAR(255) NULL,
                    is_placeholder_email BOOLEAN NOT NULL DEFAULT 0,
                    phone VARCHAR(20) NULL,
                    password VARCHAR(255) NOT NULL,
                    department VARCHAR(100) NULL,
                    position VARCHAR(100) NULL,
                    role VARCHAR(20) NOT NULL DEFAULT \'student\',
                    status VARCHAR(20) NOT NULL DEFAULT \'active\',
                    hire_date DATE NULL,
                    trial_end_date DATE NULL,
                    email_verified_at TIMESTAMP NULL,
                    remember_token VARCHAR(100) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL
                )
            ');
            DB::statement('INSERT INTO users (id, sso_uid, name, employee_no, email, phone, password, department, position, role, status, hire_date, trial_end_date, email_verified_at, remember_token, created_at, updated_at) SELECT id, sso_uid, name, employee_no, email, phone, password, department, position, role, status, hire_date, trial_end_date, email_verified_at, remember_token, created_at, updated_at FROM users_old');
            DB::statement('DROP TABLE users_old');
            DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
            DB::statement('CREATE UNIQUE INDEX users_employee_no_unique ON users (employee_no)');
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            // MySQL: Drop existing unique index, modify column, then re-add unique index
            $indexExists = DB::select("SHOW INDEX FROM users WHERE Key_name = 'users_email_unique'");
            if (!empty($indexExists)) {
                DB::statement('ALTER TABLE users DROP INDEX users_email_unique');
            }
            
            Schema::table('users', function (Blueprint $table) {
                $table->string('email', 255)->nullable()->change();
            });
            
            // Re-add unique index
            DB::statement('ALTER TABLE users ADD UNIQUE INDEX users_email_unique (email)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any NULL emails to empty string to avoid constraint violation
        DB::table('users')->whereNull('email')->update(['email' => '']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('email', 255)->unique()->change();
        });
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('users')->insert([
            [
                'name' => '系统管理员',
                'employee_no' => 'ADMIN001',
                'email' => 'admin@renhotec.com',
                'phone' => '13800000001',
                'password' => Hash::make('admin123456'),
                'department' => '技术部',
                'position' => '系统管理员',
                'role' => 'admin',
                'status' => 'active',
                'hire_date' => '2024-01-01',
                'trial_end_date' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '张导师',
                'employee_no' => 'MEN001',
                'email' => 'mentor@renhotec.com',
                'phone' => '13800000002',
                'password' => Hash::make('mentor123456'),
                'department' => '销售部',
                'position' => '高级销售经理',
                'role' => 'mentor',
                'status' => 'active',
                'hire_date' => '2023-06-01',
                'trial_end_date' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '李学员',
                'employee_no' => 'STU001',
                'email' => 'student@renhotec.com',
                'phone' => '13800000003',
                'password' => Hash::make('student123456'),
                'department' => '销售部',
                'position' => '销售专员',
                'role' => 'student',
                'status' => 'active',
                'hire_date' => '2024-10-01',
                'trial_end_date' => '2025-01-01',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        // 绑定导师学员关系
        $mentorId = DB::table('users')->where('employee_no', 'MEN001')->value('id');
        $studentId = DB::table('users')->where('employee_no', 'STU001')->value('id');

        DB::table('mentor_student')->insert([
            'mentor_id' => $mentorId,
            'student_id' => $studentId,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

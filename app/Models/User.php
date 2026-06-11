<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'employee_no',
        'email',
        'phone',
        'password',
        'department',
        'position',
        'role',
        'status',
        'hire_date',
        'trial_end_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hire_date' => 'date',
            'trial_end_date' => 'date',
            'role' => UserRole::class,
        ];
    }

    public function isTrialEmployee(): bool
    {
        return $this->trial_end_date && $this->trial_end_date->isFuture();
    }

    public function mentoredStudents()
    {
        return $this->belongsToMany(User::class, 'mentor_student', 'mentor_id', 'student_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function mentors()
    {
        return $this->belongsToMany(User::class, 'mentor_student', 'student_id', 'mentor_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function learningProgress()
    {
        return $this->hasMany(LearningProgress::class);
    }

    public function examRecords()
    {
        return $this->hasMany(ExamRecord::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function gradedRecords()
    {
        return $this->hasMany(ExamRecord::class, 'graded_by');
    }
}

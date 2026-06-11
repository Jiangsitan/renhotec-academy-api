<?php

namespace App\Models;

use App\Enums\ExamRecordStatus;
use Illuminate\Database\Eloquent\Model;

class ExamRecord extends Model
{
    protected $fillable = [
        'user_id',
        'exam_id',
        'answers',
        'objective_score',
        'subjective_score',
        'total_score',
        'status',
        'submitted_at',
        'graded_at',
        'graded_by',
        'assigned_to',
        'assignment_note',
        'mentor_comment',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'objective_score' => 'decimal:2',
            'subjective_score' => 'decimal:2',
            'total_score' => 'decimal:2',
            'status' => ExamRecordStatus::class,
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function cheats()
    {
        return $this->hasMany(ExamCheat::class, 'exam_record_id');
    }
}

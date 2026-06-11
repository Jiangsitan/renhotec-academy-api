<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'time_limit',
        'passing_score',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'time_limit' => 'integer',
            'passing_score' => 'decimal:2',
        ];
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'exam_courses');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function examRecords()
    {
        return $this->hasMany(ExamRecord::class);
    }
}

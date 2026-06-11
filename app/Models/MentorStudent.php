<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MentorStudent extends Model
{
    protected $table = 'mentor_student';

    protected $fillable = [
        'mentor_id',
        'student_id',
        'status',
    ];

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}

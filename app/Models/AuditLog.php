<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action_type',
        'target_type',
        'target_id',
        'ip_address',
        'user_agent',
        'extra_data',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'extra_data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

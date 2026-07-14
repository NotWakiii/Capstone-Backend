<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSession extends Model
{
    protected $fillable = [
        'exam_id',
        'student_name',
        'started_at',
        'submitted_at',
        'score',
        'percentage',
        'progress',
        'current_question',
        'tab_switches',
        'idle_seconds',
        'time_remaining',
        'last_seen_at',
        'time_spent',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'last_seen_at' => 'datetime',

        'score' => 'integer',
        'progress' => 'integer',
        'current_question' => 'integer',
        'tab_switches' => 'integer',
        'idle_seconds' => 'integer',
        'time_remaining' => 'integer',
        'time_spent' => 'integer',
        'percentage' => 'decimal:2',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}

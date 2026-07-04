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
        'tab_switches',
        'idle_seconds',
        'time_spent',
        'status'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    /**
     * Exam Relationship
     */
    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Student Answers
     */
    public function answers()
    {
        return $this->hasMany(StudentAnswer::class);
    }
}
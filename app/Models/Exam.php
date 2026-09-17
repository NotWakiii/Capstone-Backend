<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'description',
        'course',
        'duration',
        'passing',
        'access_code',
        'created_by',
        'class_id',
        'assessment_type',
        'status',
        'started_at',
        'ended_at',
        'grade',
        'section',
        'subject',
        'tab_switch_penalty_seconds',
        'fullscreen_exit_penalty_seconds',
        'copy_attempt_penalty_seconds',
        'paste_attempt_penalty_seconds',
        'idle_penalty_seconds',
    ];
    protected $casts = [
        'tab_switch_penalty_seconds' => 'integer',
        'fullscreen_exit_penalty_seconds' => 'integer',
        'copy_attempt_penalty_seconds' => 'integer',
        'paste_attempt_penalty_seconds' => 'integer',
        'idle_penalty_seconds' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function schoolClass()
    {
        return $this->belongsTo(
            SchoolClass::class,
            'class_id'
        );
    }


    public function questions()
    {
        return $this->hasMany(
            Question::class
        );
    }

    public function sessions()
    {
        return $this->hasMany(
            ExamSession::class
        );
    }

}

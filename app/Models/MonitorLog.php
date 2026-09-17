<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_session_id',
        'student_id',
        'activity',
        'details',
        'duration_seconds',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
    ];

    public function examSession()
    {
        return $this->belongsTo(
            ExamSession::class,
            'exam_session_id'
        );
    }

    public function student()
    {
        return $this->belongsTo(
            User::class,
            'student_id'
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentFeedback extends Model
{
    protected $table = 'assessment_feedback';

    protected $fillable = [
        'student_id',
        'class_id',
        'exam_id',
        'exam_session_id',
        'message',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function examSession()
    {
        return $this->belongsTo(ExamSession::class, 'exam_session_id');
    }
}

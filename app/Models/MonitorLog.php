<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorLog extends Model
{
    protected $fillable = [
        'exam_session_id',
        'student_id',
        'activity'
    ];
}

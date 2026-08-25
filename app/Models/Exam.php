<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'description',
        'duration',
        'passing',
        'access_code',
        'created_by',

        'class_id',

        'status',
        'grade',
        'section',
        'subject',
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

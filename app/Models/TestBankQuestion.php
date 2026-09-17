<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestBankQuestion extends Model
{
    protected $fillable = [
        'faculty_id',
        'subject_id',
        'question',
        'question_type',
        'competency',
        'answer',
        'points',
    ];

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function options()
    {
        return $this->hasMany(
            TestBankQuestionOption::class,
            'test_bank_question_id'
        );
    }
}

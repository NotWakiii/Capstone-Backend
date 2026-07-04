<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
   protected $fillable = [
    'exam_id',
    'question',
    'question_type',
    'answer',
    'points',
    'time_limit',
    'question_order'
];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers()
{
    return $this->hasMany(StudentAnswer::class);
}

    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function matchingPairs()
    {
        return $this->hasMany(MatchingPair::class);
    }
}

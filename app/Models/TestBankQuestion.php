<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolClass;

class TestBankQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'subject_id',
        'question',
        'question_type',
        'competency',
        'answer',
        'points',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function classes()
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'test_bank_question_classes',
            'test_bank_question_id',
            'class_id'
        )->withTimestamps();
    }

    public function examQuestions()
    {
        return $this->hasMany(
            Question::class,
            'test_bank_question_id'
        );
    }

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

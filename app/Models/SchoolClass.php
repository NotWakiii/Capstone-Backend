<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $fillable = [
        'faculty_id',
        'class_code',
        'school_year_id',
        'semester',
        'grade',
        'strand_id',
        'section_id',
        'subject_id',
        'section',
    ];

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }
    public function strand()
    {
        return $this->belongsTo(Strand::class);
    }
    public function sectionData()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function students()
    {
        return $this->hasMany(
            ClassStudent::class,
            'class_id'
        );
    }
}

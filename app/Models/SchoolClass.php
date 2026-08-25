<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $fillable = [
        'faculty_id',
        'grade',
        'section',
    ];

    public function faculty()
    {
        return $this->belongsTo(
            User::class,
            'faculty_id'
        );
    }

    public function students()
    {
        return $this->hasMany(
            ClassStudent::class,
            'class_id'
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'title',
        'description',
        'duration',
        'access_code',
        'created_by',
        'status'
    ];

    // relationship to user
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

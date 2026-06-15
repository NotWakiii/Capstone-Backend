<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchingPair extends Model
{
    protected $fillable = [
        'question_id',
        'left_item',
        'right_item'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}

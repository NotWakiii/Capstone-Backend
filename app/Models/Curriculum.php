<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Curriculum extends Model
{
    protected $fillable = [
        'grade',
        'strand_id',
    ];

    public function strand(): BelongsTo
    {
        return $this->belongsTo(Strand::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'curriculum_subjects'
        )->withTimestamps();
    }
}

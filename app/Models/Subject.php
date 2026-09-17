<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
    ];

    public function strand(): BelongsTo
    {
        return $this->belongsTo(Strand::class);
    }
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
        public function curricula(): BelongsToMany
    {
        return $this->belongsToMany(
            Curriculum::class,
            'curriculum_subjects'
        )->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Section extends Model
{
    protected $fillable = [
        'grade',
        'strand_id',
        'section',
    ];

    public function strand(): BelongsTo
    {
        return $this->belongsTo(Strand::class);
    }
}

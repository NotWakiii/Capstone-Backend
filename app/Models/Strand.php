<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Strand extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }
    public function curricula(): HasMany
    {
        return $this->hasMany(Curriculum::class);
    }
}

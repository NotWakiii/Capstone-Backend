<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'lrn',
        'sex',
        'status',
        'strand_id',
        'section_id',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function strand()
    {
        return $this->belongsTo(Strand::class);
    }
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}

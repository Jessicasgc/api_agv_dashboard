<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogLogin extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'user_id',
        'login_at',
        'logout_at',
        'ip_address',
        'user_agent',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

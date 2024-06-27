<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAuth extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'id_user', 
        'action', 
        'action_time', 
        'ip_address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

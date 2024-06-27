<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionLog extends Model
{
    use HasFactory;
    protected $table = 'log_actions';
    protected $fillable = [
        'id_user',
        'action',
        'table_name',
        'row_id',
        'data',
        'action_time',  
    ];
}

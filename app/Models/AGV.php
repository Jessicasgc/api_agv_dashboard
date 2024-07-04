<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AGV extends Model
{
    use HasFactory;
    protected $table = 'agv';
    protected $fillable = [
        'id',
        'agv_name',
        'agv_code',
        'agv_status',
        // 'is_charging',
        'position',
        // 'container',
        // 'collision',
        'power',
        'speed',
        // 'orientation',
        // 'acceleration',
        // 'localMap',
     

    ];

    public function getCreatedAtAttribute(){
        if(!is_null($this->attributes['created_at'])){
            return Carbon::parse($this->attributes['created_at'])->format('Y-m-d H:i:s');
        }
    }
    public function getUpdateAtAttribute(){
        if(!is_null($this->attributes['update_at'])){
            return Carbon::parse($this->attributes['update_at'])->format('Y-m-d H:i:s');
        }
    }
    public function tasks()
    {
        return $this->hasMany(Task::class, 'id_agv');
    }
    public function getPositionAttribute($value)
    {
        $point = DB::selectOne('SELECT ST_X(position) AS x, ST_Y(position) AS y FROM agv WHERE id = ?', [$this->id]);
        return $point ? ['x' => $point->x, 'y' => $point->y] : null;
    }
    // public function getPositionAttribute($value)
    // {
    //     // Assuming the format is "x,y"
    //     $coordinates = explode(',', $value);
    //     // return new Point($coordinates[0], $coordinates[1]);
    //     if (count($coordinates) === 2) {
    //         return new AGV\Point($coordinates[0], $coordinates[1]);
    //     } else {
    //         return null;
    //     }
    // }
    // public function setPositionAttribute(AGV\Point $point)
    // {
    //     $this->attributes['position'] = $point->getX() . ',' . $point->getY();
    // }

 
}   
// class Point
// {
//     private $x;
//     private $y;

//     public function __construct($x, $y)
//     {
//         $this->x = $x;
//         $this->y = $y;
//     }

//     public function getX()
//     {
//         return $this->x;
//     }

//     public function getY()
//     {
//         return $this->y;
//     }
// }
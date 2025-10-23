<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointHistory extends Model
{
    use HasFactory;

    protected $table = 'point_histories';


    protected $fillable = [
        'user_id',
        'order_id',
        'points',
    ];


    protected $casts = [
        'user_id'                => 'integer',
        'order_id'               => 'integer',
        'points'                 => 'integer',
    ];
}

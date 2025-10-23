<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPointDiscount extends Model
{
    use HasFactory;

    protected $table = "order_point_discounts";
    protected $fillable = ['order_id', 'applied_points', 'user_id', 'point_discount_amount'];
    protected $casts = [
        'id'                    => 'integer',
        'order_id'              => 'integer',
        'applied_points'        => 'integer',
        'user_id'               => 'integer',
        'point_discount_amount' => 'decimal:6',
    ];
}

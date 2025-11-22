<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $table = "delivery_zones";
    
    protected $fillable = [
        'branch_id',
        'name',
        'max_distance_km',
        'delivery_price',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'id'              => 'integer',
        'branch_id'       => 'integer',
        'name'            => 'string',
        'max_distance_km' => 'decimal:2',
        'delivery_price'  => 'decimal:6',
        'sort_order'      => 'integer',
        'status'          => 'integer',
    ];

    /**
     * Get the branch that owns the delivery zone.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}



<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCost extends Model
{
    use HasFactory;

    protected $table = 'order_costs';
    protected $primaryKey = 'cost_id';

    protected $fillable = [
        'order_id',
        'service_cost',
        'sparepart_cost',
        'custom_cost',
        'total_cost',
    ];

    protected $casts = [
        'service_cost' => 'decimal:2',
        'sparepart_cost' => 'decimal:2',
        'custom_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
}

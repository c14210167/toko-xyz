<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';
    protected $primaryKey = 'item_id';

    protected $fillable = [
        'item_code',
        'name',
        'category_id',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'reorder_level',
        'location_id',
        'image_url',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id', 'category_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'item_id', 'item_id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class, 'item_id', 'item_id');
    }

    // Scopes
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }
}

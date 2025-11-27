<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';
    protected $primaryKey = 'location_id';

    protected $fillable = [
        'location_name',
        'address',
        'city',
        'province',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'location_id', 'location_id');
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'location_id', 'location_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'location_id', 'location_id');
    }

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class, 'location_id', 'location_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'location_id', 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';
    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'customer_id',
        'location_id',
        'slot_id',
        'appointment_date',
        'service_type',
        'device_info',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'slot_id', 'slot_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'Confirmed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'Cancelled');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
            ->whereIn('status', ['Pending', 'Confirmed'])
            ->orderBy('appointment_date');
    }

    public function scopeToday($query)
    {
        return $query->where('appointment_date', now()->toDateString());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $table = 'time_slots';
    protected $primaryKey = 'slot_id';
    public $timestamps = false;

    protected $fillable = [
        'location_id',
        'day_of_week',
        'start_time',
        'end_time',
        'max_bookings',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_bookings' => 'integer',
    ];

    // Relationships
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'slot_id', 'slot_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDay($query, $day)
    {
        return $query->where('day_of_week', $day);
    }

    // Helper methods
    public function isAvailable($date)
    {
        $bookingsCount = $this->appointments()
            ->where('appointment_date', $date)
            ->where('status', '!=', 'Cancelled')
            ->count();

        return $bookingsCount < $this->max_bookings;
    }
}

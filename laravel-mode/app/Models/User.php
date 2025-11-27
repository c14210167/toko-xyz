<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'address',
        'user_type',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withPivot('assigned_by', 'assigned_at');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
            ->withPivot('is_granted', 'granted_by', 'granted_at');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id', 'user_id');
    }

    public function technicianOrders()
    {
        return $this->hasMany(Order::class, 'technician_id', 'user_id');
    }

    public function createdOrders()
    {
        return $this->hasMany(Order::class, 'created_by', 'user_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id', 'user_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(ChatMessage::class, 'receiver_id', 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'customer_id', 'user_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'customer_id', 'user_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id', 'user_id');
    }

    // Helper methods
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function hasRole($roleName)
    {
        return $this->roles()->where('role_name', $roleName)->exists();
    }

    public function hasPermission($permissionKey)
    {
        // Check user-specific permission override
        $userPermission = $this->permissions()->where('permission_key', $permissionKey)->first();
        if ($userPermission) {
            return $userPermission->pivot->is_granted;
        }

        // Check role permissions
        return $this->roles()
            ->join('role_permissions', 'roles.role_id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.permission_id')
            ->where('permissions.permission_key', $permissionKey)
            ->exists();
    }
}

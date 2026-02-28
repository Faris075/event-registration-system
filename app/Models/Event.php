<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'date_time', 'location', 'capacity', 'price', 'status'];

    protected $casts = [
        'date_time' => 'datetime',
    ];

    /**
     * All registrations linked to this event.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Registrations currently confirmed for this event.
     */
    public function confirmedRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'confirmed');
    }

    /**
     * Accessor for number of confirmed attendees.
     */
    public function getConfirmedCountAttribute(): int
    {
        return $this->confirmedRegistrations()->count();
    }

    /**
     * Accessor for available confirmed slots remaining.
     */
    public function getRemainingSpotAttribute(): int
    {
        return max(0, $this->remainingCapacity());
    }

    /**
     * Maximum number of waitlist spots (25% of capacity, minimum 1).
     */
    public function waitlistCapacity(): int
    {
        return (int) max(1, ceil($this->capacity * 0.25));
    }

    /**
     * Current number of waitlisted registrations.
     */
    public function waitlistCount(): int
    {
        return $this->registrations()->where('status', 'waitlisted')->count();
    }

    /**
     * Number of admin-override confirmed registrations for this event.
     */
    public function adminOverrideCount(): int
    {
        return $this->registrations()->where('is_admin_override', true)->where('status', 'confirmed')->count();
    }

    /**
     * Remaining slots using confirmed registrations only.
     */
    public function remainingCapacity(): int
    {
        return (int) max(0, $this->capacity - $this->registrations()->where('status', 'confirmed')->count());
    }

    /**
     * Determine whether the event is completed based on current system date.
     */
    public function isCompleted(): bool
    {
        return now()->greaterThanOrEqualTo($this->date_time);
    }
}

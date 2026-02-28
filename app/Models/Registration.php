<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = ['event_id', 'attendee_id', 'registration_date', 'status', 'waitlist_position', 'payment_status', 'is_admin_override'];

    /**
     * Event associated with this registration.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Attendee associated with this registration.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class);
    }
}

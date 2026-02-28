<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendee extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone', 'company'];

    /**
     * Registrations made by this attendee.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }
}

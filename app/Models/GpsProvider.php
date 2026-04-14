<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GpsProvider extends Model
{
    protected $fillable = [
        'name',
        'driver',            // e.g. 'teltonika', 'concox', 'generic'
        'base_url',
        'auth_type',         // 'api_key' | 'bearer' | 'basic' | 'oauth2'
        'auth_config',       // JSONB: { header, value } or { token } or { username, password }
        'location_endpoint', // e.g. /api/v1/devices/{id}/location
        'history_endpoint',  // optional
        'field_map',         // JSONB: used by GenericRestProvider
        'poll_interval',     // seconds
        'is_active',
    ];

    protected $casts = [
        'auth_config' => 'array',
        'field_map'   => 'array',
        'is_active'   => 'boolean',
    ];

    /**
     * Never expose auth_config in API responses.
     */
    protected $hidden = ['auth_config'];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}

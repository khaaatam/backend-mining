<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VehicleType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'icon_key'
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}

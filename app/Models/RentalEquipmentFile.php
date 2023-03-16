<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalEquipmentFile extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function rental_equipment()
    {
        return $this->belongsTo(RentalEquipment::class);
    }
}

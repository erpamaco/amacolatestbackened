<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UOM extends Model
{
    use HasFactory;

    protected $table = 'unit_of_measures';
    protected $fillable = [
        	'id',
        	'value',
        	'label',
        	'created_at',
        	'updated_at',
    ];
}

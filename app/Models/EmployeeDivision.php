<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDivision extends Model
{
    use HasFactory;
    protected $table = 'employee_division';
    protected $fillable = [
        'id',	
        'e_id',	
        'div_id',	
        'created_at',	
        'updated_at',
    ];
}

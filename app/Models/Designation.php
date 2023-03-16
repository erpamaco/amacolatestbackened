<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected  $table="designations";
    protected $fillable = [
        'name',
        'user_id',
        'designation',
       
    ];
  
    use HasFactory;
    public function users()
    {
        return $this->belongsTo(Users::class);
    }
}

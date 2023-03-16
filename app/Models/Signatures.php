<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signatures extends Model
{
    use HasFactory;
    protected $table = 'signatures';
    protected $fillable = [
        'id',
        'prepared_by',
        'approval_by',
        'user_id',
        'created_at',
        'updated_at'
    ];
}

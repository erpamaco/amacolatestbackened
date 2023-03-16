<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    use HasFactory;
      protected $table = "login_logs";
    protected $fillable = [
        "id",
        "u_id",
        "platform",
        "browser",
        "date_time",
        "created_at",
        "updated_at",
        "type",
        "status",
    ];
}

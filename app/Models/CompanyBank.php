<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyBank extends Model
{
    use HasFactory;

    protected $guarded=[];
    public function quotation_bank()
    {
        return $this->hasOne(quotation::class,'bank_id','id');
    }

    public function invoice_bank()
    {
        return $this->hasOne(invoice::class,'bank_id','id');
    }
}

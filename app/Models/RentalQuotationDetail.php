<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RentalQuotationDetail extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function quotation()
    {
        return $this->belongsTo(RentalQuotation::class);
    }
    public function party()
    {
        return $this->hasOne(Party::class, 'id', 'party_id');
    }
    public function partyDivision()
    {
        return $this->hasMany(party_division::class, 'party_id','id');

    }

    public function equipment()
    {
        return $this->hasOne(RentalEquipment::class, 'id', 'equipment_id');
    }
    public function equipment_quotation()
    {
        return $this->hasMany(RentalEquipment::class, 'id', 'equipment_id');
    }
    public function getDeliveredQuantityrental(RentalQuotationDetail $quotation_detail)
    {
        
        $deliveryNoteDetails = DB::table('delivery_notes')
        ->leftJoin('delivery_note_details', 'delivery_note_details.delivery_note_id','=', 'delivery_notes.id')
        ->where('delivery_notes.rental_quotation_id',$quotation_detail->rental_quotation_id)
        ->where('delivery_note_details.rental_quote_detail_id', $quotation_detail->id)
        ->get();

        if($deliveryNoteDetails) {
            $totalDeliveryNoteDetail = 0;
            foreach ($deliveryNoteDetails as $item) {
                $totalDeliveryNoteDetail += intval($item->delivered_quantity);
            }
            return $totalDeliveryNoteDetail;
        }
        return 0;
    }
    
}

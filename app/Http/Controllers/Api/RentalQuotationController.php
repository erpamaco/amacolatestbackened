<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RentalQuotation;
use App\Models\RentalQuotationDetail;
use Illuminate\Http\Request;
use App\Models\DeliveryNote;
use App\Models\Designation;
use App\Models\Invoice;
use App\Models\DeliveryNoteDetail;
use App\Models\CompanyBank;
use Illuminate\Database\Eloquent\Collection;

use App\Models\equipment;

use App\Models\notes;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use Exception;
use Illuminate\Support\Facades\File;

use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\CompanyBankController;
use App\Http\Controllers\Api\equipmentController;
use App\Http\Controllers\Api\UOMController;
use App\Http\Controllers\Api\equipmentPriceController;

class RentalQuotationController extends Controller
{



    public function mjrQuoteEdit($did,$id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        

        return response()->json([
            'customer' => PartyController::customer($did),
            'users' => DesignationController::index(),
            'banks' => CompanyBankController::banks(),
            'equipments' => equipmentController::index(),
            'sales' => $this -> shows($id),
            'uom' => UOMController::uom(),
        ]);
    }

    public function mjrPurchase($did,$id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json([
            'vendor' => PartyController::vendor($did),
            'users' => DesignationController::index(),
            'banks' => CompanyBankController::banks(),
            'equipments' => equipmentController::index(),
            'sales' => $this -> shows($id),
            'uom' => UOMController::uom(),
            'equipmentPrice' => equipmentPriceController::equipmentPrice(),

        ]);
    }

    public function mjrQuoteDno($did,$id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json([           
            'equipments' => equipmentController::index(),
            'sales' => $this -> showsSale($id),
        ]);
    }


    public function rentalmjrQuoteInc($did){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json([
            'customer' => PartyController::customer($did),
            'users' => DesignationController::index(),
            'banks' => CompanyBankController::banks(),
            'equipments' => RentalEquipmentController::index(),
            'uom' => UOMController::uom(),
        ]);
    }




    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    // public function checkDeliveredequipmentQuantity($quotation_detail)
    // {
    //     $equipment_quantity = $quotation_detail->equipment->quantity;
    //     $equipment_id = $quotation_detail->equipment->id;
    //     $quotation_id = $quotation_detail->quotation->id;
    //     $deliveryNote = DeliveryNote::where('quotation_id',$quotation_id)->firstOrFail();
    //     $res = $deliveryNote->deliveryNoteDetail ?? $deliveryNote->deliveryNoteDetail->map(function($deliveryNoteD, $this->equipment_quantity,$this->equipment_id){
    //         if($deliveryNoteD->equipment_id == $equipment_id){
    //             if($equipment_quantity - $deliveryNoteD->quantity != 0){
    //                 return true;
    //             }else{
    //                 return false;
    //             }
    //         }
    //     });
    //     // dd($quotation_detail);
    //     return $res;
    // }

    public function getCurrentYear($date)
    {
        return substr(date('Y',strtotime($date)), 2);
        // return substr(date('Y'), 2);
    }

    public function getCurrentMonth($date)
    {
        return date('m',strtotime($date));
        // return date('m');
    }

    public function getLastRentalQuotationNo($date,$d)
    {
        // $quotation = RentalQuotation::where('transaction_type', 'sale')
        // ->where('quotation_no','not like', '%REV%')->latest('created_at')->first();


        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);

        $patern='AMC'.$d.'-QT-'.$current_year.'-'.$current_month;
        $quotation = RentalQuotation::where('transaction_type', 'sale')
        ->where('quotation_no','not like', '%REV%')->where('quotation_no', 'like', '%'.$patern.'%')->latest('created_at')->first();

       
        if ($quotation) {
            $latest_quotation_no = $quotation->quotation_no ? $quotation->quotation_no : 0;
            return ($latest_quotation_no);
        } else {
            return ('AMC'.$d.'-QT-' . $this->getCurrentYear($date) . '-' . $this->getCurrentMonth($date) . sprintf("%02d", 0));
        }
    }

    public function getLastPONo($date,$d)
    {


        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);
       
        $patern='AMC'.$d.'-PO-'.$current_year.'-'.$current_month;
        // $res=Invoice::where('invoice_no', 'like', '%'.$patern.'%')->latest('created_at')->first();

        $quotation = RentalQuotation::where('transaction_type', 'purchase')->where('po_number', 'like', '%'.$patern.'%')->latest('created_at')->first();
        if ($quotation) {
            $latest_po_number = $quotation->po_number ? $quotation->po_number : 0;
            return ($latest_po_number);
        } else {
            return ('AMC'.$d.'-PO-' . $this->getCurrentYear($date) . '-' . $this->getCurrentMonth($date) . sprintf("%02d", 0));
        }
    }

    public function getLastSONo()
    {
        $quotation = RentalQuotation::where('transaction_type', 'sale')
            ->latest('created_at')->first();
        if ($quotation) {
            $latest_sales_order_number = $quotation->sales_order_number ? $quotation->sales_order_number : 0;
            return ($latest_sales_order_number);
        } else {
            return ('ASON-' . $this->getCurrentYear() . '-' . sprintf("%04d", 0));
        }
    }

    public function getRentalQuotationNo($date,$d)
    {
        $latest_quotation_no = $this->getLastRentalQuotationNo($date,$d);
        $last_year = substr($latest_quotation_no, 8, 2);
        $last_month = substr($latest_quotation_no, 11, 2);
        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);
        if ($current_year != $last_year) {
            return ('AMC'.$d.'-QT-' . $current_year . '-' . $current_month  . sprintf("%02d", 1));
        } else {
            if ($current_month != $last_month) {
                return ('AMC'.$d.'-QT-' . $current_year . '-' . $current_month  . sprintf("%02d", 1));
            } else {
                if (((int)substr($this->getLastRentalQuotationNo($date,$d), 13) < 99)) {
                    return ('AMC'.$d.'-QT-' . $current_year . '-' . $current_month . sprintf("%02d", ((int)substr($this->getLastRentalQuotationNo($date,$d), 13)) + 1));
                } else {
                    return ('AMC'.$d.'-QT-' . $current_year . '-' . $current_month . sprintf("%03d", ((int)substr($this->getLastRentalQuotationNo($date,$d), 13)) + 1));
                }
            }
        }
    }

    public function revisedRentalQuotationNo($quotationNo,$date)
    {
        if(strlen($quotationNo) > 15){
            $revisedRentalQuotation =  substr($quotationNo, 0,15)."-REV-".sprintf("%02d",((int)substr($quotationNo, 20))+1);
            return $revisedRentalQuotation;
        }else{
            $revisedRentalQuotation =  $quotationNo. "-REV-" . sprintf("%02d", 1);
            return $revisedRentalQuotation;
        }
    }


    public function getPONo($date,$d)
    {
        $latest_po_number = $this->getLastPONo($date,$d);
       $last_year = substr($latest_po_number, 8, 2);
        $last_month = substr($latest_po_number, 11, 2);
        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);
        if ($current_year != $last_year) {
            return ('AMC'.$d.'-PO-' . $current_year . '-' . $current_month  . sprintf("%02d", 1));
        } else {
            if ($current_month != $last_month) {
                return ('AMC'.$d.'-PO-' . $current_year . '-' . $current_month  . sprintf("%02d", 1));
            } else {
                if (((int)substr($this->getLastPONo($date,$d), 13) < 99)) {
                    return ('AMC'.$d.'-PO-' . $current_year . '-' . $current_month . sprintf("%02d", ((int)substr($this->getLastPONo($date,$d), 13)) + 1));
                } else {
                    return ('AMC'.$d.'-PO-' . $current_year . '-' . $current_month . sprintf("%03d", ((int)substr($this->getLastPONo($date,$d), 13)) + 1));
                }
            }
        }
    }

    public function getSalesOrderNumber($date)
    {
        $latest_sales_order_number = $this->getLastSONo();
        $last_year = substr($latest_sales_order_number, 5, 2);
        $current_year = $this->getCurrentYear($date);
        // dd([$last_year, $current_year]);
        if ($current_year != $last_year) {
            return ('ASON-' . $current_year . '-' . sprintf("%04d", 1));
        } else {
            return ('ASON-' . $current_year . '-' . sprintf("%04d", ((int)substr($this->getLastSONo(), 9)) + 1));
        }
    }

    public function index() // Purchase List
    {
        if(!auth()->check())
        return ["THIS API IS NOT ACCESSIBLE"];
        
        $quotations = RentalQuotation::where(['status' => 'New', 'transaction_type' => 'purchase'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('invoices')
                    ->whereRaw('invoices.quotation_id = quotations.id');
            })->orderBy('created_at', 'DESC')
            ->get();
        // $quotations = RentalQuotation::where('status','=','New')->orderBy('created_at','DESC')->get();
        $quotations_data = [
            $quotations->map(
                function ($quotation) {
                    $data = [
                        'id' => $quotation->id,
                        'po_number' => $quotation->po_number,
                        'created_at' => $quotation->created_at,
                        'updated_at' => $quotation->updated_at,
                        'status' => $quotation->status,
                        'total_value' => $quotation->total_value,
                        'party_id' => $quotation->party_id,
                        "contact_id" => $quotation->contact_id,
                        "contact" => $quotation->contact,
                        "party" => $quotation->party,
                        // "partyDivision"=>$quotation->partyDivision,
                        "vat_in_value" => $quotation->vat_in_value,
                        "net_amount" => $quotation->net_amount,
                        "transaction_type" => $quotation->transaction_type,
                        'discount_in_p' => $quotation->discount_in_p,
                        'ps_date' => $quotation->ps_date,
                        'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                            $quotation_detail = RentalQuotationDetail::where('id', '=', $quotation_detail->id)->first();
                            return [
                                "id" => $quotation_detail['id'],
                                "created_at" => $quotation_detail->created_at,
                                "updated_at" => $quotation_detail->updated_at,
                                "equipment_id" => $quotation_detail->equipment_id,
                                "equipment" => array($quotation_detail->equipment),
                                "description" => $quotation_detail->description,
                                "quantity" => $quotation_detail->quantity,
                                "total_amount" => $quotation_detail->total_amount,
                                "analyse_id" => $quotation_detail->analyse_id,
                                "purchase_price" => $quotation_detail->purchase_price,
                                "amaco_description" => $quotation_detail->descriptionss,
                                "margin" => $quotation_detail->margin,
                                "sell_price" => $quotation_detail->sell_price,
                                "remark" => $quotation_detail->remark,
                            ];
                        }),
                    ];
                    return $data;
                }
            ),
        ];
        return response()->json($quotations_data[0], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     */

    public function store(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        
        
        // $rfqId = null;
        $rfqId = $request->rfq_id ? $request->rfq_id :null;
        $parentId = null;
        if($request['parent_id']){
            $parentId = $request['parent_id'];

        }



        // try {
            $datas = [
                'party_id' => $request['party_id']?$request['party_id']:0,
                'rfq_id' => $request['rfq_id']?$request['rfq_id']:0,
                'status' => $request['status'],
                'parent_id' => $parentId,
                'total_value' => isset($request['total_value']) ? $request['total_value'] == "NaN" ? 0 : $request['total_value'] : 0,
                'net_amount' => $request['net_amount'],
                'qstatus' => $request['qstatus'],
                'vat_in_value' => $request['vat_in_value'],
                'freight_charges' => $request['freight_charges'],
                'discount_in_p' => $request['discount_in_p'],
                'terms1' => $request['terms1'],
                'terms2' => $request['terms2'],
                'terms3' => $request['terms3'],
                'terms4' => $request['terms4'],
                'terms5' => $request['terms5'],
                'terms6' => $request['terms6'],
                'terms7' => $request['terms7'],
                'terms8' => $request['terms8'],
                'terms9' => $request['terms9'],
                'terms10' => $request['terms10'],
                'terms11' => $request['terms11'],
                'exclude_from_vat' => $request['exclude_from_vat'] ? $request['exclude_from_vat'] : 0,
                
                'currency_type' => $request['currency_type'],
                'freight_type' => $request['freight'],
              
                'contact_id' => isset($request['contact_id'])?$request['contact_id']:0,
                'transaction_type' => $request['transaction_type'],
                'ps_date' => $request['ps_date'],  // ? $request['ps_date'] : Carbon::now()
                'sign' => $request['sign'],  // ? $request['ps_date'] : Carbon::now()
                'bank_id' => (int)$request['bank_id'],  // ? $request['ps_date'] : Carbon::now()
                'subject' => $request['subject']?$request['subject']:null,  // ? $request['ps_date'] : Carbon::now()
                'rfq_no' => $request['rfq_no']?$request['rfq_no']:null,  // ? $request['ps_date'] : Carbon::now()
                'transport' => $request['transport']?$request['transport']:null,  // ? $request['ps_date'] : Carbon::now()
                'other' => $request['other']?$request['other']:null,  // ? $request['ps_date'] : Carbon::now()
                'div_id' => $request['div_id']?$request['div_id']:0,  // ? $request['ps_date'] : Carbon::now()
                'user_id' => $request['user_id']?$request['user_id']:0,
            ];

            $divi = $request['div_id'] == 1 ? "T" : "R";
            // $divi = ($request['div_id'] == 1) ? "T":(($request['div_id'] == 5 )? "PO":"P");
            if ($request->transaction_type === 'sale') {
                if ($request['parent_id']) {
                    $datas['quotation_no'] = $this->revisedRentalQuotationNo($request['quotation_no'],$request['ps_date'],$divi);
                
                }else{
                    $datas['quotation_no'] = $this->getRentalQuotationNo($request['ps_date'],$divi);
                }
            } elseif ($request->transaction_type === 'purchase') {
                $datas['po_number'] = $this->getPONo($request['ps_date'],$divi);
            } else {
                $datas['quotation_no'] = null;
                $datas['po_number'] = null;
            }

            $quotation = RentalQuotation::create($datas);
           


            global $quotation_id;
            global $equipment_ID;
            $quotation_id = $quotation->id;
            
            if ($request->transaction_type === 'purchase') {
                foreach ($request['quotation_details'] as $key => $quotation_detail) {
                    // if(!$quotation_detail['equipmentId'])
                    // {
                    //    $equipment=equipment::create([
                    //         'name'=> $quotation_detail['equipment'],
                    //         'div_id' => $request['div_id']?$request['div_id']:0,  // ? $request['ps_date'] : Carbon::now()
                    //         'user_id' => $request['user_id']?$request['user_id']:0,
                    //         'type' => 'Non inventory',
                    //     ]);
                    //     $equipment_ID = $equipment->id;
                    // }
                    
                    RentalQuotationDetail::create([
                        'quotation_id' => $quotation_id,
                        'total_amount' => isset($quotation_detail['total_amount']) ? $quotation_detail['total_amount'] : 0,
                        'analyse_id' => null,
                        'equipment_id' => isset($quotation_detail['equipmentId'])?$quotation_detail['equipmentId']:0,
                        // (isset($equipment_ID)?$equipment_ID:0)
                        'purchase_price' => isset($quotation_detail['purchase_price']) ? $quotation_detail['purchase_price'] : 0,
                        'description' => isset($quotation_detail['equipment_name'])?$quotation_detail['equipment_name']:(isset($quotation_detail['equipment']->description)?$quotation_detail['equipment']:null),
                        'equipment_description' => $quotation_detail['description'],
                        'quantity' => $quotation_detail['quantity'],
                        'unit_of_measure' => $quotation_detail['unit_of_measure'] ? $quotation_detail['unit_of_measure'] : null,
                        'margin' => isset($quotation_detail['margin']) ? $quotation_detail['margin'] : 0,
                        'sell_price' => isset($quotation_detail['sell_price']) ? $quotation_detail['sell_price'] : 0,
                        'remark' => isset($quotation_detail['remark']) ? $quotation_detail['remark'] : 0,
                    ]);
                }
            } else {
                // $note_detail = json_decode($request->notes, true);
            //     foreach ($note_detail as $div) {
            //        try {
            //           notes::create([
            //         'quotation_id' => $quotation_id,
            //         'notes' => $div['note'] , 
            //         'div_id' => $request['div_id']?$request['div_id']:0,  // ? $request['ps_date'] : Carbon::now()
            //         'user_id' => $request['user_id']?$request['user_id']:0, 
            //     ]); 
            //        } catch (\Throwable $th) {
            //            //throw $th;
            //        }
                
            // }
                $index = 0;
                while ($request['quotation_detail' . $index] != null) {
                   
                    $quotation_detail = (array) json_decode($request['quotation_detail' . $index], true);
                    $filePath = null;
                    if ($request->file('file' . $index)) {
                        $filePath = $request->file('file' . $index)->move('quotation/quotation_detail/' . $quotation_id);
                    }else{
                        if(isset($quotation_detail['file'])){
                            // $filePath = explode("public/",$quotation_detail['file'])[1];
                            $filePath = $quotation_detail['file'];
                        }
                    }
                    if(!$quotation_detail['equipment_id'])
                    {
                        // $equipment_exist=equipment::where('name','=',$quotation_detail['descriptionss']?$quotation_detail['descriptionss']:" ")->first();
                        // if(!$equipment_exist){
                        //     $equipment=equipment::create([
                        //         'name'=> $quotation_detail['descriptionss'],
                        //         'description'=> $quotation_detail['description'],
                        //         'unit_of_measure'=> $quotation_detail['unit_of_measure']?$quotation_detail['unit_of_measure']:'',
                        //         'div_id' => $request['div_id']?$request['div_id']:0,  // ? $request['ps_date'] : Carbon::now()
                        //         'user_id' => $request['user_id']?$request['user_id']:0,
                        //         'type' => 'Non inventory',
                        //     ]);
                        // }
                        // else
                        // {
                        //     // $equipment=$equipment_exist;
                        // }  
                      
                    }
                    RentalQuotationDetail::create([
                        'quotation_id' => $quotation_id,
                        'total_amount' => $quotation_detail['total_amount'],
                        'analyse_id' => null,
                        'equipment_id' => $quotation_detail['equipment_id']?$quotation_detail['equipment_id']:0,
                        'purchase_price' => $quotation_detail['purchase_price'],
                        'description' => $quotation_detail['description'],
                        'unit_of_measure' => $quotation_detail['unit_of_measure']?$quotation_detail['unit_of_measure']:"",
                        'equipment_description' => $quotation_detail['descriptionss']?$quotation_detail['descriptionss']:"",
                        'quantity' => $quotation_detail['quantity'],
                        'discount' => $quotation_detail['discount'],
                        'discount_val' => $quotation_detail['discount_val'],
                        'margin' => $quotation_detail['margin'],
                        'sell_price' => $quotation_detail['sell_price'],
                        'remark' => $quotation_detail['remark'],
                        'index1' => $quotation_detail['index1'],
                        // "amaco_description" => $quotation_detail['descriptionss'],
                        'file_img_url' => $filePath,
                    ]);
                    $index++;
                }
               
            //     $note_detail = json_decode($request->notes, true);
            //     foreach ($note_detail as $div) {
                   
            //     notes::create([
            //         'quotation_id' => $quotation_id,
            //         'notes' => $div['note'], 
                    
        
            //     ]); 
            // }
            
            }

            if ($request['parent_id']) {
                $tempQuotaion = RentalQuotation::where('id', $request['parent_id'])->first();
                if ($tempQuotaion) {
                    $tempQuotaion->update(['is_revised' => 1]);
                }
            }
           
               
           
           
            

            return response()->json($quotation_id);
        // } catch (Exception $e) {
        //     return response()->json($request, 201);
        // }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RentalQuotation  $quotation
     * @return \Illuminate\Http\Response
     */

    public  function shows($id)
    {
        
        $quotation = RentalQuotation::where('id', $id)->first();
        $data = [
            "id" => $quotation->id,
            'quotation_no' => $quotation->quotation_no,
            "party_id" => $quotation->party_id,
            "exclude_from_vat" => $quotation->exclude_from_vat,
            "file" => $quotation->file,
            "rfq_id" => $quotation->rfq_id || null,
            "status" => $quotation->status,
            "total_value" => isset($quotation->total_value)?$quotation->total_value:0,
            "discount_in_p" => isset($quotation->discount_in_p)?$quotation->discount_in_p:0,
            "vat_in_value" => isset($quotation->vat_in_value)? $quotation->vat_in_value:0,
            "freight_charges" => isset($quotation->freight_charges)? $quotation->freight_charges:0,
            "net_amount" => isset($quotation->net_amount)?$quotation->net_amount:0,
            "created_at" => $quotation->created_at,
            "updated_at" => $quotation->updated_at,
            "validity" => $quotation->validity,
            "payment_terms" => $quotation->payment_terms,
            "warranty" => $quotation->warranty,
            "delivery_time" => $quotation->delivery_time,
            "inco_terms" => $quotation->inco_terms,
            "po_number" => $quotation->po_number,
            "transaction_type" => $quotation->transaction_type,
            "contact_id" => $quotation->contact_id,
            "ps_date" => $quotation->ps_date,
            "qstatus" => $quotation->qstatus,
            "sales_order_number" => $quotation->sales_order_number,
            "contact" => $quotation->contact,
            "party" => $quotation->party,
            "partyDivision" => $quotation->party && ($quotation->party->partyDivision->map(function($payment){
                return $payment->partyDivision;
            })),
            "rfq" => $quotation->rfq,
            "is_revised" => $quotation->is_revised,
            // "sign" => $quotation->signature,
            "sign" => Designation::select('users.email','users.contact','designations.*')->join('users','users.id','designations.user_id')->where('designations.id',$quotation->sign)->get(),
            "notes" => $quotation->notes,
            "bank" => $quotation->bank,
            "currency_type" => $quotation->currency_type,
            "freight_type" => $quotation->freight_type,
            "subject" => $quotation->subject,
            "rfq_no" => $quotation->rfq_no,
            "transport" => $quotation->transport,
            "other" => $quotation->other,
            "div_id" => $quotation->div_id,
            "user_id" => $quotation->user_id,
            "delete" => $quotation->delete,

            "quotation_details" => $quotation->quotationDetail->map(function ($quotation_detail) {
                $filePath = $quotation_detail->file_img_url ? $quotation_detail->file_img_url : '';
                $urlPath = $filePath ? url($filePath) : null;
                return [
                    "id" => $quotation_detail->id,
                    "index1" => $quotation_detail->index1,
                    "total_amount" => $quotation_detail->total_amount,
                    "analyse_id" => $quotation_detail->analyse_id,
                    "equipment_id" => $quotation_detail->equipment_id,
                    "descriptions" => $quotation_detail->description,
                    "descriptionss" => $quotation_detail->equipment_description,
                    "amaco_description" => $quotation_detail->amaco_description,
                    "equipment" => $quotation_detail->equipment,
                    "name" => isset($quotation_detail->equipment->name)? $quotation_detail->equipment->name:'',
                    "equipment_name" => " ",
                    // "partyDivision" => $quotation_detail->partyDivision,
                    "equipment_price_list" => $quotation_detail->equipment? $quotation_detail->equipment->equipmentPrice->map(function ($equipmentP) {
                        return [
                            'price' => $equipmentP->price?$equipmentP->price:"",
                            'firm_name' =>$equipmentP->party->firm_name
                        ];
                    }):null,
                    
                    // "equipment_price_list" => $quotation_detail->equipment->equipmentPrice,
                    "purchase_price" => isset($quotation_detail->purchase_price)?$quotation_detail->purchase_price:0,
                    "description" => $quotation_detail->description,
                    "quantity" => isset($quotation_detail->quantity)?$quotation_detail->quantity:0,
                    "discount" => $quotation_detail->discount,
                    "margin_val"=>$quotation_detail->purchase_price ? ((((float)$quotation_detail->purchase_price)*(float)$quotation_detail->margin)/100)*(float)($quotation_detail->quantity):((float)($quotation_detail->quantity)*(float)($quotation_detail->sell_price))+$quotation_detail->discount_val,
                    "discount_val"=>$quotation_detail->purchase_price?(((float)((float)($quotation_detail->discount) * ((float)(((float)$quotation_detail->margin * (float)($quotation_detail->purchase_price) / 100) + (float)($quotation_detail->purchase_price))) / 100)) * (float)($quotation_detail->quantity)):(float)$quotation_detail->discount_val,
                    "cost_qty"  => (float)$quotation_detail->purchase_price*(int)$quotation_detail->quantity,
                    'unit_of_measure' => $quotation_detail->unit_of_measure,
                    // "delivered_quantity"=> $quotation_detail->quantity,
                    "delivered_quantity" => $quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    "balance" => (int)$quotation_detail->quantity - (int)$quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    "margin" => $quotation_detail->margin? $quotation_detail->margin:'',
                    "sell_price" => $quotation_detail->sell_price,
                    "remark" => $quotation_detail->remark,
                    "file" => $urlPath,
                    "created_at" => $quotation_detail->created_at,
                    "updated_at" => $quotation_detail->updated_at,
                    "delete" => false,
                   
                  
                ];
            })
        ];

        return $data;
    }
    public  function showsSale($id)
    {
        
        $quotation = RentalQuotation::where('id', $id)->first();
        $data = [
            "id" => $quotation->id,
            'quotation_no' => $quotation->quotation_no,
            "party_id" => $quotation->party_id,
            "exclude_from_vat" => $quotation->exclude_from_vat,
            "file" => $quotation->file,
            "rfq_id" => $quotation->rfq_id || null,
            "status" => $quotation->status,
            "total_value" => isset($quotation->total_value)?$quotation->total_value:0,
            "discount_in_p" => isset($quotation->discount_in_p)?$quotation->discount_in_p:0,
            "vat_in_value" => isset($quotation->vat_in_value)? $quotation->vat_in_value:0,
            "freight_charges" => isset($quotation->freight_charges)? $quotation->freight_charges:0,
            "net_amount" => isset($quotation->net_amount)?$quotation->net_amount:0,
            "created_at" => $quotation->created_at,
            "updated_at" => $quotation->updated_at,
            "validity" => $quotation->validity,
            "payment_terms" => $quotation->payment_terms,
            "warranty" => $quotation->warranty,
            "delivery_time" => $quotation->delivery_time,
            "inco_terms" => $quotation->inco_terms,
            "po_number" => $quotation->po_number,
            "transaction_type" => $quotation->transaction_type,
            "ps_date" => $quotation->ps_date,
            "qstatus" => $quotation->qstatus,
            "sales_order_number" => $quotation->sales_order_number,
            "contact" => $quotation->contact,
            "party" => $quotation->party,
            "partyDivision" => $quotation->party && ($quotation->party->partyDivision->map(function($payment){
                return $payment->partyDivision;
            })),
            "rfq" => $quotation->rfq,
            "is_revised" => $quotation->is_revised,
            // "sign" => $quotation->signature,
            "sign" => Designation::select('users.email','users.contact','designations.*')->join('users','users.id','designations.user_id')->where('designations.id',$quotation->sign)->get(),
            "notes" => $quotation->notes,
            "bank" => $quotation->bank,
            "currency_type" => $quotation->currency_type,
            "freight_type" => $quotation->freight_type,
            "subject" => $quotation->subject,
            "rfq_no" => $quotation->rfq_no,
            "transport" => $quotation->transport,
            "other" => $quotation->other,
            "div_id" => $quotation->div_id,
            "user_id" => $quotation->user_id,
            "delete" => $quotation->delete,

            "quotation_details" => $quotation->quotationDetail->map(function ($quotation_detail) {
                $filePath = $quotation_detail->file_img_url ? $quotation_detail->file_img_url : '';
                $urlPath = $filePath ? url($filePath) : null;
                return [
                    "id" => $quotation_detail->id,
                    "index1" => $quotation_detail->index1,
                    "total_amount" => $quotation_detail->total_amount,
                    "analyse_id" => $quotation_detail->analyse_id,
                    "equipment_id" => $quotation_detail->equipment_id,
                    "descriptions" => $quotation_detail->description,
                    "descriptionss" => $quotation_detail->equipment_description,
                    "description" => $quotation_detail->equipment_description,
                    "amaco_description" => $quotation_detail->amaco_description,
                    "equipment" => $quotation_detail->equipment,
                    "name" => isset($quotation_detail->equipment->name)? $quotation_detail->equipment->name:'',
                    "equipment_name" => " ",
                    // "partyDivision" => $quotation_detail->partyDivision,
                    "equipment_price_list" => $quotation_detail->equipment? $quotation_detail->equipment->equipmentPrice->map(function ($equipmentP) {
                        return [
                            'price' => $equipmentP->price?$equipmentP->price:"",
                            'firm_name' =>$equipmentP->party->firm_name
                        ];
                    }):null,
                    
                    // "equipment_price_list" => $quotation_detail->equipment->equipmentPrice,
                    "purchase_price" => isset($quotation_detail->purchase_price)?$quotation_detail->purchase_price:0,
                    // "description" => $quotation_detail->description,
                    "quantity" => isset($quotation_detail->quantity)?$quotation_detail->quantity:0,
                    "discount" => $quotation_detail->discount,
                    "margin_val"=>$quotation_detail->purchase_price ? ((((float)$quotation_detail->purchase_price)*(float)$quotation_detail->margin)/100)*(float)($quotation_detail->quantity):((float)($quotation_detail->quantity)*(float)($quotation_detail->sell_price))+$quotation_detail->discount_val,
                    "discount_val"=>$quotation_detail->purchase_price?(((float)((float)($quotation_detail->discount) * ((float)(((float)$quotation_detail->margin * (float)($quotation_detail->purchase_price) / 100) + (float)($quotation_detail->purchase_price))) / 100)) * (float)($quotation_detail->quantity)):(float)$quotation_detail->discount_val,
                    "cost_qty"  => (float)$quotation_detail->purchase_price*(int)$quotation_detail->quantity,
                    'unit_of_measure' => $quotation_detail->unit_of_measure,
                    // "delivered_quantity"=> $quotation_detail->quantity,
                    "delivered_quantity" => $quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    "balance" => (int)$quotation_detail->quantity - (int)$quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    "margin" => $quotation_detail->margin? $quotation_detail->margin:'',
                    "sell_price" => $quotation_detail->sell_price,
                    "remark" => $quotation_detail->remark,
                    "file" => $urlPath,
                    "created_at" => $quotation_detail->created_at,
                    "updated_at" => $quotation_detail->updated_at,
                    "delete" => false,
                   
                  
                ];
            })
        ];

        return $data;
    }
    public static function showReport($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotation = RentalQuotation::where('quotation_no','=', $id)->first();
        $data = [
            "id" => $quotation->id,
            'quotation_no' => $quotation->quotation_no,
            "party_id" => $quotation->party_id,
            "file" => $quotation->file,
            'delete'=>$quotation->delete,
            "rfq_id" => $quotation->rfq_id || null,
            "status" => $quotation->status,
            "total_value" => isset($quotation->total_value)?$quotation->total_value:0,
            "discount_in_p" => isset($quotation->discount_in_p)?$quotation->discount_in_p:0,
            "vat_in_value" => isset($quotation->vat_in_value)? $quotation->vat_in_value:0,
            "freight_charges" => isset($quotation->freight_charges)? $quotation->freight_charges:0,
            "net_amount" => isset($quotation->net_amount)?$quotation->net_amount:0,
            "created_at" => $quotation->created_at,
            "updated_at" => $quotation->updated_at,
            "validity" => $quotation->validity,
            "payment_terms" => $quotation->payment_terms,
            "warranty" => $quotation->warranty,
            "qstatus" => $quotation->qstatus,
            "delivery_time" => $quotation->delivery_time,
            "inco_terms" => $quotation->inco_terms,
            "po_number" => $quotation->po_number,
            "transaction_type" => $quotation->transaction_type,
            "ps_date" => $quotation->ps_date,
            "sales_order_number" => $quotation->sales_order_number,
            "contact" => $quotation->contact,
            "party" => $quotation->party,
            "pBank" => isset($quotation->party->bank)?$quotation->party->bank:" ",
            "partyDivision" => $quotation->party && ($quotation->party->partyDivision->map(function($payment){
                return $payment->partyDivision;
            })),
            "rfq" => $quotation->rfq,
            "is_revised" => $quotation->is_revised,
            // "sign" => $quotation->signature,
            "sign" => Designation::select('users.email','users.contact','designations.*')->join('users','users.id','designations.user_id')->where('designations.id',$quotation->sign)->get(),
            "notes" => $quotation->notes,
            "bank" => $quotation->bank,
            "currency_type" => $quotation->currency_type,
            "freight_type" => $quotation->freight_type,
            "subject" => $quotation->subject,
            "rfq_no" => $quotation->rfq_no,
            "transport" => $quotation->transport,
            "other" => $quotation->other,
            "div_id" => $quotation->div_id,
            "user_id" => $quotation->user_id,
            "delete" => $quotation->delete,

            "quotation_details" => $quotation->quotationDetail->map(function ($quotation_detail) {
                $filePath = $quotation_detail->file_img_url ? $quotation_detail->file_img_url : '';
                $urlPath = $filePath ? url($filePath) : null;
                return [
                    "id" => $quotation_detail->id,
                    "index1" => $quotation_detail->index1,
                    "total_amount" => $quotation_detail->total_amount,
                    "analyse_id" => $quotation_detail->analyse_id,
                    "equipment_id" => $quotation_detail->equipment_id,
                    "descriptions" => $quotation_detail->description,
                    "descriptionss" => $quotation_detail->equipment_description,
                    "amaco_description" => $quotation_detail->amaco_description,
                    "equipment" => $quotation_detail->equipment,
                    "name" => isset($quotation_detail->equipment->name)? $quotation_detail->equipment->name:'',
                    "equipment_name" => " ",
                    // "partyDivision" => $quotation_detail->partyDivision,
                    "equipment_price_list" => $quotation_detail->equipment? $quotation_detail->equipment->equipmentPrice->map(function ($equipmentP) {
                        return [
                            'price' => $equipmentP->price?$equipmentP->price:"",
                            'firm_name' =>$equipmentP->party->firm_name
                        ];
                    }):null,
                    
                    // "equipment_price_list" => $quotation_detail->equipment->equipmentPrice,
                    "purchase_price" => isset($quotation_detail->purchase_price)?$quotation_detail->purchase_price:0,
                    "description" => $quotation_detail->description,
                    "quantity" => isset($quotation_detail->quantity)?$quotation_detail->quantity:0,
                    "discount" => $quotation_detail->discount,
                    "margin_val"=>$quotation_detail->purchase_price ? ((((float)$quotation_detail->purchase_price)*(float)$quotation_detail->margin)/100)*(float)($quotation_detail->quantity):((float)($quotation_detail->quantity)*(float)($quotation_detail->sell_price))+$quotation_detail->discount_val,
                    "discount_val"=>$quotation_detail->purchase_price?(((float)((float)($quotation_detail->discount) * ((float)(((float)$quotation_detail->margin * (float)($quotation_detail->purchase_price) / 100) + (float)($quotation_detail->purchase_price))) / 100)) * (float)($quotation_detail->quantity)):(float)$quotation_detail->discount_val,
                    "cost_qty"  => (float)$quotation_detail->purchase_price*(int)$quotation_detail->quantity,
                    'unit_of_measure' => $quotation_detail->unit_of_measure,
                    // "delivered_quantity"=> $quotation_detail->quantity,
                    "delivered_quantity" => $quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    "balance" => (int)$quotation_detail->quantity - (int)$quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    "margin" => $quotation_detail->margin? $quotation_detail->margin:'',
                    "sell_price" => $quotation_detail->sell_price,
                    "remark" => $quotation_detail->remark,
                    "file" => $urlPath,
                    "created_at" => $quotation_detail->created_at,
                    "updated_at" => $quotation_detail->updated_at,
                   
                  
                ];
            })
        ];

        return $data;
    }

    public function show($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotation = RentalQuotation::where('id','=', $id)->first();
        $data = [
            "id" => $id,
            'quotation_no' => $quotation->quotation_no,
            "party_id" => $quotation->party_id,
            "file" => $quotation->file,
            'delete'=>$quotation->delete,
            "rfq_id" => $quotation->rfq_id || null,
            "status" => $quotation->status,
            "total_value" => isset($quotation->total_value)?$quotation->total_value:0,
            "discount_in_p" => isset($quotation->discount_in_p)?$quotation->discount_in_p:0,
            "vat_in_value" => isset($quotation->vat_in_value)? $quotation->vat_in_value:0,
            "freight_charges" => isset($quotation->freight_charges)? $quotation->freight_charges:0,
            "net_amount" => isset($quotation->net_amount)?$quotation->net_amount:0,
            "created_at" => $quotation->created_at,
            "updated_at" => $quotation->updated_at,
            "qstatus" => $quotation->qstatus,
            "terms1" => $quotation->terms1,
            "terms2" => $quotation->terms2,
            "terms3" => $quotation->terms3,
            "terms4" => $quotation->terms4,
            "terms5" => $quotation->terms5,
            "terms6" => $quotation->terms6,
            "terms7" => $quotation->terms7,
            "terms8" => $quotation->terms8,
            "terms9" => $quotation->terms9,
            "terms10" => $quotation->terms10,
            "terms11" => $quotation->terms11,
            "po_number" => $quotation->po_number,
            "transaction_type" => $quotation->transaction_type,
            "ps_date" => $quotation->ps_date,
            "sales_order_number" => $quotation->sales_order_number,
            "contact" => $quotation->contact,
            "party" => $quotation->party,
            "pBank" => isset($quotation->party->bank)?$quotation->party->bank:" ",
            "partyDivision" => $quotation->party && ($quotation->party->partyDivision->map(function($payment){
                return $payment->partyDivision;
            })),
            "rfq" => $quotation->rfq,
            "is_revised" => $quotation->is_revised,
            // "sign" => $quotation->signature,
            "sign" => Designation::select('users.email','users.contact','designations.*')->join('users','users.id','designations.user_id')->where('designations.id',$quotation->sign)->get(),
            "notes" => $quotation->notes,
            "bank" => $quotation->bank,
            "currency_type" => $quotation->currency_type,
            "freight_type" => $quotation->freight_type,
            "subject" => $quotation->subject,
            "rfq_no" => $quotation->rfq_no,
            "transport" => $quotation->transport,
            "other" => $quotation->other,
            "div_id" => $quotation->div_id,
            "user_id" => $quotation->user_id,
            "delete" => $quotation->delete,

            "rental_quotation_details" => $quotation->quotationDetail->map(function ($quotation_detail) {
                $filePath = $quotation_detail->file_img_url ? $quotation_detail->file_img_url : '';
                $urlPath = $filePath ? url($filePath) : null;
                return [
                    "id" => $quotation_detail->id,
                    "index1" => $quotation_detail->index1,
                    "total_amount" => $quotation_detail->total_amount,
                    "analyse_id" => $quotation_detail->analyse_id,
                    "equipment_id" => $quotation_detail->equipment_id,
                    "descriptions" => $quotation_detail->description,
                    "descriptionss" => $quotation_detail->equipment_description,
                    "amaco_description" => $quotation_detail->amaco_description,
                    "equipment" => $quotation_detail->equipment,
                    "name" => isset($quotation_detail->equipment->name)? $quotation_detail->equipment->name:'',
                    "equipment_name" => " ",
                    // "partyDivision" => $quotation_detail->partyDivision,
                    "equipment_price_list" => $quotation_detail->equipment? $quotation_detail->equipment->equipmentPrice->map(function ($equipmentP) {
                        return [
                            'price' => $equipmentP->price?$equipmentP->price:"",
                            'firm_name' =>$equipmentP->party->firm_name
                        ];
                    }):null,
                    
                    // "equipment_price_list" => $quotation_detail->equipment->equipmentPrice,
                    "purchase_price" => isset($quotation_detail->purchase_price)?$quotation_detail->purchase_price:0,
                    "description" => $quotation_detail->description,
                    "quantity" => isset($quotation_detail->quantity)?$quotation_detail->quantity:0,
                    "discount" => $quotation_detail->discount,
                    "margin_val"=>$quotation_detail->purchase_price ? ((((float)$quotation_detail->purchase_price)*(float)$quotation_detail->margin)/100)*(float)($quotation_detail->quantity):((float)($quotation_detail->quantity)*(float)($quotation_detail->sell_price))+$quotation_detail->discount_val,
                    "discount_val"=>$quotation_detail->purchase_price?(((float)((float)($quotation_detail->discount) * ((float)(((float)$quotation_detail->margin * (float)($quotation_detail->purchase_price) / 100) + (float)($quotation_detail->purchase_price))) / 100)) * (float)($quotation_detail->quantity)):(float)$quotation_detail->discount_val,
                    "cost_qty"  => (float)$quotation_detail->purchase_price*(int)$quotation_detail->quantity,
                    'unit_of_measure' => $quotation_detail->unit_of_measure,
                    // "delivered_quantity"=> $quotation_detail->quantity,
                    "delivered_quantity" => $quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    "balance" => (int)$quotation_detail->quantity - (int)$quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    "margin" => $quotation_detail->margin? $quotation_detail->margin:'',
                    "sell_price" => $quotation_detail->sell_price,
                    "remark" => $quotation_detail->remark,
                    "file" => $urlPath,
                    "created_at" => $quotation_detail->created_at,
                    "updated_at" => $quotation_detail->updated_at,
                   
                  
                ];
            })
        ];

        return response()->json([
            $data
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RentalQuotation  $quotation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
      
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        // return $request;
        $quotation = RentalQuotation::where("id", $request->id)->first();
        $data = $request->all();
        if ($request->transaction_type !== 'purchase') {
        $quotation->update([
            'po_number' => $request->po_number,

           

            // 'status' => $request->status,

            'div_id' => $request->div_id,
            'user_id' => $request->user_id,

            'total_value' => $request->total_value,
            'party_id' => $request->party_id,
            'contact_id' => $request->contact_id,
            'vat_in_value' => $request->vat_in_value,
            'net_amount' => $request->net_amount,
            'transaction_type' => $request->transaction_type,
            'discount_in_p' => $request->discount_in_p,
            'ps_date' => $request->ps_date,
            'sign' => $request->sign,
            'rfq_no' => $request->rfq_no,
            'subject' => isset($request->subject)?$request->subject:null,
            'bank_id'=> (int)$request->bank_id,
            'validity' => $request['validity'],
            'payment_terms' => $request['payment_terms'],
            'warranty' => $request['warranty'],
            'currency_type' => $request['currency_type'],
            'delivery_time' => $request['delivery_time'],
            'inco_terms' => $request['inco_terms'],
            'transport' => $request['transport'],
            'other' => $request['other'],
            'status' => $request['status']=="accept"?"accept":$request['status'],
            // 'sales_order_number' => $data['sales_order_number'],
        ]);
        $index = 0;
        $quotationDetail = RentalQuotationDetail::where([
            // 'id' => $quotation_detail['id'],
            'quotation_id' => $request->id
        ])->delete();
        $res=notes::where('quotation_id',$quotation->id)->delete();
                $note_detail = json_decode($request->notes, true);
                if($note_detail)
                {
                foreach ($note_detail as $div) {
           
                notes::create([
                'quotation_id' => $quotation->id,
                'notes' => $div['notes'], 
                'div_id' => $request['div_id']?$request['div_id']:0,  // ? $request
                'user_id' => $request['user_id']?$request['user_id']:0,
                ]); 
                }
                }
        while ($request['quotation_detail' . $index] != null) {
            $quotation_detail = (array) json_decode($request['quotation_detail' . $index], true);
            $filePath = null;
         
                if ($request->hasfile('files' . $index)) {
                $filePath = $request->file('files' . $index)->move('quotation/quotation_detail/' . $request->id);
            }else{
                if(isset($quotation_detail['file'])){
                    try {
                        $filePath = explode("public/",$quotation_detail['file'])[1];
                    } catch (\Throwable $th) {
                        $filePath =$quotation_detail['file'];
                    }
                   
                }
                
            }
            
            $quotationDetail = RentalQuotationDetail::where([
                'id' => $quotation_detail['id'],
                // 'quotation_id' => $request->id
            ])->first();
            // if(!$quotation_detail['equipment_id'])
            // {
            //    $equipment=equipment::create([
            //         'name'=> $quotation_detail['description']
            //     ]);
            // }
            // if ($quotationDetail) {
                
            //     $quotationDetail->update([
            //         'total_amount' => $quotation_detail['total_amount'],
            //         'analyse_id' => $quotation_detail['analyse_id'],
            //         'equipment_id' => $quotation_detail['equipment_id'],
            //         'purchase_price' => $quotation_detail['purchase_price'],
            //         'description' => $quotation_detail['description'],
            //         'quantity' => $quotation_detail['quantity'],
            //         'discount' => $quotation_detail['discount'],
            //         'discount_val' => $quotation_detail['discount_val'],
            //         'margin' => $quotation_detail['margin'],
            //         'sell_price' => $quotation_detail['sell_price'],
            //         'unit_of_measure' => $quotation_detail['unit_of_measure'],
            //         'equipment_description' => $quotation_detail['descriptionss']?$quotation_detail['descriptionss']:"",
            //         'remark' => $quotation_detail['remark'],
            //         'index1' => $quotation_detail['index1'],
            //         'file_img_url' => $filePath,

            //     ]);


                // $res=notes::where('quotation_id',$quotation->id)->delete();
                // $note_detail = json_decode($request->notes, true);
                // if($note_detail)
                // {
                // foreach ($note_detail as $div) {
           
                // notes::create([
                // 'quotation_id' => $quotation->id,
                // 'notes' => $div['notes'], 
                // 'div_id' => $request['div_id']?$request['div_id']:0,  // ? $request
                // 'user_id' => $request['user_id']?$request['user_id']:0,
                // ]); 
                // }
                // }
            // } else {
                // if(!$quotation_detail['equipment_id'] )
                // {
                    
                   
                // }
                RentalQuotationDetail::create([
                    'quotation_id' => $quotation->id,
                    'total_amount' => $quotation_detail['total_amount'],
                    // 'analyse_id' => $quotation_detail['analyse_id'],
                    'equipment_id' => $quotation_detail['equipment_id']?$quotation_detail['equipment_id']:null,
                    'purchase_price' => $quotation_detail['purchase_price'],
                    'description' => $quotation_detail['description'],
                    'quantity' => $quotation_detail['quantity'],
                    'margin' => $quotation_detail['margin'],
                    'discount' => $quotation_detail['discount'],
                    'discount_val' => $quotation_detail['discount_val'],
                    'sell_price' => $quotation_detail['sell_price'],
                    'equipment_description' => $quotation_detail['descriptionss']?$quotation_detail['descriptionss']:"",
                    'unit_of_measure' => $quotation_detail['unit_of_measure'],
                    'remark' => $quotation_detail['remark'],
                    'index1' => $quotation_detail['index1'],
                    'file_img_url' => $filePath,

                ]);
            // }
            $index++;
        }
        return response()->json($quotation->id);
        }
        else
        {
            $quotation->update([
                // 'status' => $request->status,
                'total_value' => $request->total_value,
                'vat_in_value' => $request->vat_in_value,
                'party_id' => $request->party_id,
                'contact_id' => $request->contact_id,
                'freight_type' => $request->freight,
                'payment_terms' => $request->payment_terms,
                'currency_type' => $request->currency_type,
                'delivery_time' => $request->delivery_time,
                'inco_terms' => $request->inco_terms,
                'net_amount' => $request->net_amount,
                'transaction_type' => $request->transaction_type,
                'discount_in_p' => $request->discount_in_p,
                'ps_date'=>$request->ps_date,
                // 'div_id' => $request->div_id?$request->div_id:0,  // ? $request['ps_date'] : Carbon::now()
                // 'user_id' => $request->user_id?$request->user_id:0,
                
                // 'sales_order_number' => $data['sales_order_number'],
            ]);
            $index = 0;
            while ($request['quotation_details'] != null) {
                $quotation_detail = (array) json_encode($request['quotation_details'], true);
                // $quotation_detail = (collect($request['quotation_details'])->toArray())[0];
                // foreach ($request['quotation_details'] as $key => $quotation_detail) {
                
                $quotationDetail = RentalQuotationDetail::where([
                    'id' => $quotation_detail['id'],
                    // 'quotation_id' => $request->id
                ])->first();
                
                if ($quotation_detail['id']) {
                   
                    $quotationDetail->update([
                        'total_amount' => $quotation_detail['total_amount'],
                        'equipment_id' => $quotation_detail['equipment_id'],
                        'purchase_price' => $quotation_detail['purchase_price'],
                        // 'description' => $quotation_detail['equipment']?$quotation_detail['equipment']:$quotation_detail['description'],
                        'quantity' => $quotation_detail['quantity'],
                        'margin' => $quotation_detail['margin'],
                        'sell_price' => $quotation_detail['sell_price'],
                        'remark' => $quotation_detail['remark'],
                        'equipment_description' => $quotation_detail['descriptionss']?$quotation_detail['descriptionss']:"",
                        'unit_of_measure' => $quotation_detail['unit_of_measure'],
    
                    ]);
                } else {
                    if(!$quotation_detail['equipment_id'])
                    {
                       $equipment=equipment::create([
                            'name'=> $quotation_detail['equipment'],
                            'div_id' => $request['div_id']?$request['div_id']:0,  // ? $request['ps_date'] : Carbon::now()
                'user_id' => $request['user_id']?$request['user_id']:0,
                'type' => 'Non inventory',
                        ]);
                    }
                    RentalQuotationDetail::create([
                        'quotation_id' => $quotation->id,
                        'total_amount' => $quotation_detail['total_amount'],
                        // 'analyse_id' => $quotation_detail['analyse_id'],
                        'equipment_id' => $quotation_detail['equipment_id']?$quotation_detail['equipment_id']:$equipment->id,
                        'purchase_price' => $quotation_detail['purchase_price'],
                        'description' => $quotation_detail['equipment'],
                        'quantity' => $quotation_detail['quantity'],
                        'margin' => $quotation_detail['margin'],
                        'sell_price' => $quotation_detail['sell_price'],
                        'unit_of_measure' => $quotation_detail['unit_of_measure'],
                        'equipment_description' => $quotation_detail['descriptionss']?$quotation_detail['descriptionss']:null,
                       
    
                    ]);
                   
      
        
                    }
                $index++;
               
                
        
               
    
               
      
        // }
      
       
        
        
    }
        
   
    }
        
    return response()->json("hi");      
    
    }

    public function updateRentalQuotation(Request $request, $id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        

        // add validation

        // $validator = Validator::make($request->all(), [
        //     'title' => 'unique:quotations'
        // ]);
        // if ($validator->fails()) {
        //     return response()->json(['msg' => 'P.O.Number is already exists'],201);
        // }

        // new validation logic for po_number

        $unique_po_no = RentalQuotation::where('po_number', $request->po_number)->first();
        $data = $request->all();
        $quotation = RentalQuotation::where("id", $id)->firstOrFail();
        $filePath = null;
        if ($request->file('file')) {
            $filePath = $request->file('file')->move("quotation/filePath",  $request->file('file')->getClientOriginalName());
        }
        if ($request->po_number) {

            if (isset($unique_po_no)) {
                return response()->json(['msg' => 'P.O.Number is exsits']);
            }

            $data['sales_order_number'] = $this->getSalesOrderNumber();
            $quotation->update([
                'status' => $data['status'],
                'sales_order_number' => $data['sales_order_number'],
                'po_number' => $data['po_number'],
                'file' => $filePath,
                
            ]);
        } else {
            $quotation->update([
                'status' => $data['status'],
            ]);
        }



        return response()->json($quotation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RentalQuotation  $quotation
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotationStatus = RentalQuotation::where('id', $id)->first();
        $quotation = RentalQuotation::where('id', $id)->update([
            'delete'=> !$quotationStatus->delete
        ]);

        // $res = $quotation->delete();
        $res = $quotation;
        if ($res) {
            return (['msg' => 'RentalQuotation' . ' ' . $id . ' is successfully deleted']);
        }
    }
    public function destroy_details($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotation = RentalQuotationDetail::where('id', $id)->first();

        $res = $quotation->delete();
        if ($res) {
            return (['msg' => 'RentalQuotation' . ' ' . $quotation->id . ' is successfully deleted']);
        }
    }

    public function invoice_list()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = RentalQuotation::where('status', '=', 'po')->orderBy('created_at', 'DESC')->get();
        $quotations_data = [
            $quotations->map(
                function ($quotation) {
                    return [
                        'id' => $quotation->id,
                        'created_at' => $quotation->created_at,
                        'updated_at' => $quotation->updated_at,
                        'status' => $quotation->status,
                        'total_value' => $quotation->total_value,
                        'party_id' => $quotation->party_id,
                        'quotation_no' => $quotation->quotation_no,
                        "vat_in_value" => $quotation->vat_in_value,
                        "net_amount" => $quotation->net_amount,
                        'discount_in_p' => $quotation['discount_in_p'],
                        "party" => $quotation->party,
                        'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                            $quotation_detail = RentalQuotationDetail::where('id', '=', $quotation_detail->id)->first();
                            return [
                                "id" => $quotation_detail['id'],
                                "created_at" => $quotation_detail->created_at,
                                "updated_at" => $quotation_detail->updated_at,
                                "equipment_id" => $quotation_detail->equipment_id,
                                "equipment" => array($quotation_detail->equipment),
                                "description" => $quotation_detail->description,
                                "quantity" => $quotation_detail->quantity,
                                "total_amount" => $quotation_detail->total_amount,
                                "analyse_id" => $quotation_detail->analyse_id,
                                "purchase_price" => $quotation_detail->purchase_price,
                                "margin" => $quotation_detail->margin,
                                "sell_price" => $quotation_detail->sell_price,
                                "remark" => $quotation_detail->remark,
                            ];
                        }),
                    ];
                }
            ),
        ];
        return response()->json($quotations_data[0], 200);
    }

    public function history()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = RentalQuotation::whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('invoices')
                ->whereRaw('invoices.quotation_id = quotations.id');
        })->orderBy('created_at', 'DESC')
            //->where('status', '=', 'po')
            ->get();

        return response()->json($quotations);
    }
    public static function allSalesList()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        
        $quotations = RentalQuotation::where(['quotations.transaction_type' => 'sale'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('invoices')
                    ->whereRaw('invoices.quotation_id = quotations.id');
            })
            ->select('quotations.*','quotations.id as qid')
            ->orderBy('quotations.created_at', 'DESC')
            ->get();
            
        $invoices = RentalQuotation::join('invoices','invoices.quotation_id' , 'quotations.id')->where(['quotations.transaction_type' => 'sale'])
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('invoices')
                ->whereRaw('invoices.quotation_id = quotations.id');
        })
        ->select('quotations.*','invoices.*','quotations.id as qid')
        ->orderBy('quotations.created_at', 'DESC')
        ->get();
        $res=$quotations->concat($invoices);
        $quotations_data = [
            $res->map(
                function ($quotation) {
                    return [
                        'id' => $quotation->qid,
                        'quotation_no' => $quotation->quotation_no,
                        'ps_date' => $quotation->ps_date,
                        'created_at' => $quotation->created_at,
                        'updated_at' => $quotation->updated_at,
                        'status' => (isset($quotation->invoice_no))?'history':$quotation->status,
                        'total_value' => $quotation->total_value,
                        'party_id' => $quotation->party_id,
                        "contact_id" => $quotation->contact_id,
                        "contact" => $quotation->contact,
                        "party" => $quotation->party,
                        "vat_in_value" => $quotation->vat_in_value,
                        "net_amount" => $quotation->net_amount,
                        "transaction_type" => $quotation->transaction_type,
                        'discount_in_p' => $quotation['discount_in_p'],
                        'div_id' => $quotation->div_id,
                        'is_revised' => $quotation->is_revised,
                        "subject" => isset($quotation->subject)?$quotation->subject:"",
                        'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                            $quotation_detail = RentalQuotationDetail::where('id', '=', $quotation_detail->id)->first();
                            return [
                                "id" => $quotation_detail['id'],
                                "created_at" => $quotation_detail->created_at,
                                "updated_at" => $quotation_detail->updated_at,
                                "equipment_id" => $quotation_detail->equipment_id,
                                "equipment" => array($quotation_detail->equipment),
                                "description" => $quotation_detail->description,
                                "quantity" => $quotation_detail->quantity,
                                "total_amount" => $quotation_detail->total_amount,
                                "analyse_id" => $quotation_detail->analyse_id,
                                "purchase_price" => $quotation_detail->purchase_price,
                                "margin" => $quotation_detail->margin,
                                "sell_price" => $quotation_detail->sell_price,
                                "remark" => $quotation_detail->remark,
                               
                            ];
                        }),
                    ];
                }
            ),
        ];
        return response()->json($quotations_data[0], 200);
    }

    public static function salesList()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = RentalQuotation::where(['transaction_type' => 'sale'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('invoices')
                    ->whereRaw('invoices.quotation_id = quotations.id');
            })->orderBy('created_at', 'DESC')
            ->get();
        // $quotations = RentalQuotation::where('status','=','New')->orderBy('created_at','DESC')->get();
        $quotations_data = [
            $quotations->map(
                function ($quotation) {
                    return [
                        'id' => $quotation->id,
                        'quotation_no' => $quotation->quotation_no,
                        'quote_date' => $quotation->ps_date,
                        'created_at' => $quotation->created_at,
                        'updated_at' => $quotation->updated_at,
                        'status' => $quotation->status,
                        'total_value' => $quotation->total_value,
                        'party_id' => $quotation->party_id,
                        "contact_id" => $quotation->contact_id,
                        "contact" => $quotation->contact,
                        "party" => $quotation->party,
                        "vat_in_value" => $quotation->vat_in_value,
                        "net_amount" => $quotation->net_amount,
                        "transaction_type" => $quotation->transaction_type,
                        'discount_in_p' => $quotation['discount_in_p'],
                        'div_id' => $quotation->div_id,
                        'is_revised' => $quotation->is_revised,
                        "subject" => isset($quotation->subject)?$quotation->subject:"",
                        'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                            $quotation_detail = RentalQuotationDetail::where('id', '=', $quotation_detail->id)->first();
                            return [
                                "id" => $quotation_detail['id'],
                                "created_at" => $quotation_detail->created_at,
                                "updated_at" => $quotation_detail->updated_at,
                                "equipment_id" => $quotation_detail->equipment_id,
                                "equipment" => array($quotation_detail->equipment),
                                "description" => $quotation_detail->description,
                                "quantity" => $quotation_detail->quantity,
                                "total_amount" => $quotation_detail->total_amount,
                                "analyse_id" => $quotation_detail->analyse_id,
                                "purchase_price" => $quotation_detail->purchase_price,
                                "margin" => $quotation_detail->margin,
                                "sell_price" => $quotation_detail->sell_price,
                                "remark" => $quotation_detail->remark,
                               
                            ];
                        }),
                    ];
                }
            ),
        ];
        return response()->json($quotations_data[0], 200);
    }

    public static function acceptedList()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = RentalQuotation::where(['status' => 'accept', 'transaction_type' => 'sale'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('invoices')
                    ->whereRaw('invoices.quotation_id = quotations.id');
            })->orderBy('created_at', 'DESC')
            ->get();
        // $quotations = RentalQuotation::where('status','=','New')->orderBy('created_at','DESC')->get();
        $quotations_data = [
            $quotations->map(
                function ($quotation) {
                    return [
                        'id' => $quotation->id,
                        'quotation_no' => $quotation->quotation_no,
                        'quotation_date' => $quotation->ps_date,
                        'created_at' => $quotation->created_at,
                        'updated_at' => $quotation->updated_at,
                        'status' => $quotation->status,
                        'total_value' => $quotation->total_value,
                        'po_number' => $quotation->po_number,
                        'party_id' => $quotation->party_id,
                        "contact_id" => $quotation->contact_id,
                        "contact" => $quotation->contact,
                        "party" => $quotation->party,
                        "vat_in_value" => $quotation->vat_in_value,
                        "net_amount" => $quotation->net_amount,
                        "transaction_type" => $quotation->transaction_type,
                        'discount_in_p' => $quotation['discount_in_p'],
                        'div_id' => $quotation->div_id,
                        'subject' => $quotation->subject,
                        'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                            $quotation_detail = RentalQuotationDetail::where('id', '=', $quotation_detail->id)->first();
                            // $isDelivered = $this->checkDeliveredequipmentQuantity($quotation_detail);
                            // dd($isDelivered);
                            return [
                                "id" => $quotation_detail['id'],
                                "created_at" => $quotation_detail->created_at,
                                "updated_at" => $quotation_detail->updated_at,
                                "equipment_id" => $quotation_detail->equipment_id,
                                "equipment" => array($quotation_detail->equipment),
                                "description" => $quotation_detail->description,
                                "amaco_description" => $quotation_detail->descriptionss,
                                "quantity" => $quotation_detail->quantity,
                                "total_amount" => $quotation_detail->total_amount,
                                "analyse_id" => $quotation_detail->analyse_id,
                                "purchase_price" => $quotation_detail->purchase_price,
                                "margin" => $quotation_detail->margin,
                                "sell_price" => $quotation_detail->sell_price,
                                "remark" => $quotation_detail->remark,
                            ];
                        }),
                    ];
                }
            ),
        ];
        return response()->json($quotations_data[0], 200);
    }

    public function rejectedList()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = RentalQuotation::where(['status' => 'reject', 'transaction_type' => 'sale'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('invoices')
                    ->whereRaw('invoices.quotation_id = quotations.id');
            })->orderBy('created_at', 'DESC')
            ->get();
        // $quotations = RentalQuotation::where('status','=','New')->orderBy('created_at','DESC')->get();
        $quotations_data = [
            $quotations->map(
                function ($quotation) {
                    return [
                        'id' => $quotation->id,
                        'div_id' => $quotation->div_id,
                        'user_id' => $quotation->id,
                        'ps_date' => $quotation->ps_date,
                        'quotation_no' => $quotation->quotation_no,
                        'created_at' => $quotation->created_at,
                        'updated_at' => $quotation->updated_at,
                        'status' => $quotation->status,
                        'total_value' => $quotation->total_value,
                        'party_id' => $quotation->party_id,
                        "contact_id" => $quotation->contact_id,
                        "contact" => $quotation->contact,
                        "party" => $quotation->party,
                        "vat_in_value" => $quotation->vat_in_value,
                        "net_amount" => $quotation->net_amount,
                        "transaction_type" => $quotation->transaction_type,
                        'discount_in_p' => $quotation['discount_in_p'],
                        'subject' => $quotation['subject'],
                        'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                            $quotation_detail = RentalQuotationDetail::where('id', '=', $quotation_detail->id)->first();
                            return [
                                "id" => $quotation_detail['id'],
                                "created_at" => $quotation_detail->created_at,
                                "updated_at" => $quotation_detail->updated_at,
                                "equipment_id" => $quotation_detail->equipment_id,
                                "equipment" => array($quotation_detail->equipment),
                                "description" => $quotation_detail->description,
                                "quantity" => $quotation_detail->quantity,
                                "total_amount" => $quotation_detail->total_amount,
                                "analyse_id" => $quotation_detail->analyse_id,
                                "purchase_price" => $quotation_detail->purchase_price,
                                "margin" => $quotation_detail->margin,
                                "sell_price" => $quotation_detail->sell_price,
                                "remark" => $quotation_detail->remark,
                            ];
                        }),
                    ];
                }
            ),
        ];
        return response()->json($quotations_data[0], 200);
    }

    public function deleteFile(RentalQuotationDetail $quotation_detail)
    {
        
        if (File::exists(public_path($quotation_detail->file_img_url))) {

            File::delete(public_path($quotation_detail->file_img_url));

            $quotation_detail->update([
                'file_img_url' => null
            ]);

            return response()->json(['msg' => "Successfully file is deleted"]);


        }
        return response()->json(['msg' => "There is no file in quotation detail"]);
    }

    

    public function saleReport(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $reports = RentalQuotation::where('transaction_type','sale')->where('delete',0)
        ->whereBetween('created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->get();

        if($reports){
            $reports->map(
                function ($quotation) {
                    $data = [
                        'id' => $quotation->id,
                        'po_number' => $quotation->po_number,
                        'created_at' => $quotation->created_at,
                        'updated_at' => $quotation->updated_at,
                        'status' => $quotation->status,
                        'total_value' => $quotation->total_value,
                        'party_id' => $quotation->party_id,
                        "contact_id" => $quotation->contact_id,
                        "contact" => $quotation->contact,
                        "party" => $quotation->party,
                        "vat_in_value" => $quotation->vat_in_value,
                        "net_amount" => $quotation->net_amount,
                        "transaction_type" => $quotation->transaction_type,
                        'discount_in_p' => $quotation->discount_in_p,
                        'ps_date' => $quotation->ps_date,
                        'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                            $quotation_detail = RentalQuotationDetail::where('id', '=', $quotation_detail->id)->first();
                            return [
                                "id" => $quotation_detail['id'],
                                "created_at" => $quotation_detail->created_at,
                                "updated_at" => $quotation_detail->updated_at,
                                "equipment_id" => $quotation_detail->equipment_id,
                                "equipment" => array($quotation_detail->equipment),
                                "description" => $quotation_detail->description,
                                "quantity" => $quotation_detail->quantity,
                                "total_amount" => $quotation_detail->total_amount,
                                "analyse_id" => $quotation_detail->analyse_id,
                                "purchase_price" => $quotation_detail->purchase_price,
                                "margin" => $quotation_detail->margin,
                                "sell_price" => $quotation_detail->sell_price,
                                "remark" => $quotation_detail->remark,
                            ];
                        }),
                    ];
                    // return $data;
                }
            );

            return response()->json($reports);
        }
        return response()->json(['msg'=>"There is no report between the given date"],500);
    }
    public function updateQuotestatus(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $unique_po_no = RentalQuotation::where('po_number', $request->po_number)->first();
        $data = $request->all();
        $quotation = RentalQuotation::where("id", $request->id)->firstOrFail();
        $filePath = null;
        if ($request->file('file')) {
            $filePath = $request->file('file')->move("quotate/filePath",  $request->file('file')->getClientOriginalName());
        }
        if ($request->po_number) {

            // if (isset($unique_po_no)) {
            //     return response()->json(['msg' => 'P.O.Number is exsits']);
            // }

            $sales_order_number = $this->getSalesOrderNumber($quotation->issue_date);
            $quotation->update([
                'status' => $request->status,
                'sales_order_number' => $sales_order_number,
                'po_number' => $request->po_number,
                'file' => $filePath,
                
            ]);
        } else {
            $quotation->update([
                'status' => $request->status,
            ]);
            // if($request->status=="accept")
            // {
            // $str = $request->po_number;
            // $visitors = RentalQuotation::orderBy('created_at', 'desc')->where('quotation_no', 'like', '%'. $str .'%')->get();
            // $visitors->map(function($item){
                
            //     $item->update(['is_revised'=>2]);

            // });
            
           
        // }
        }



        return response()->json($quotation);
    }
    public function update_company(Request $request)
    {
        $quotations = RentalQuotation::where('id',$request->id)->update([
            'company_address'=> $request->company_address,

        ]);

    }
    public function  equipmentAdd($request)
    {
        $index = 0;
                while ($request['quotation_detail' . $index] != null) {
                    $quotation_detail = (array) json_decode($request['quotation_detail' . $index], true);
                    if(!$quotation_detail['equipment_id'])
                    {
                        equipment::create([
                            'name'=> $quotation_detail['description']
                        ]);
                    }
                    
                    $index++;
                }
                return response("success");
    }

    public function  purchaseUpdate(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
         // return $request;
         $quotation = RentalQuotation::where("id", $request->id)->first();
        $data = $request->all();
             $quotation->update([
                 // 'status' => $request->status,
                 'total_value' => $request->total_value,
                 'vat_in_value' => $request->vat_in_value,
                 'freight_charges' => $request->freight_charges,
                 'party_id' => $request->party_id,
                 'contact_id' => $request->contact_id,
                 'freight_type' => $request->freight,
                 'payment_terms' => $request->payment_terms,
                 'currency_type' => $request->currency_type,
                 'delivery_time' => $request->delivery_time,
                 'inco_terms' => $request->inco_terms,
                 'net_amount' => $request->net_amount,
                 'transaction_type' => $request->transaction_type,
                 'exclude_from_vat' => $request->exclude_from_vat,
                 'discount_in_p' => $request->discount_in_p,
                 'ps_date'=>$request->ps_date,
                 // 'div_id' => $request->div_id?$request->div_id:0,  // ? $request['ps_date'] : Carbon::now()
                 // 'user_id' => $request->user_id?$request->user_id:0,
                 
                 // 'sales_order_number' => $data['sales_order_number'],
             ]);
             $index = 0;
             $resDel = RentalQuotationDetail::where([
                'quotation_id' => $request->id,
                // 'quotation_id' => $request->id
            ])->delete();
             while ($request['quotation_detail' . $index] != null) {
                $quotation_detail = (array) json_decode($request['quotation_detail' . $index], true);
                 
                 // $quotation_detail = (collect($request['quotation_details'])->toArray())[0];
                 // foreach ($request['quotation_details'] as $key => $quotation_detail) {
                 
                 $quotationDetail = RentalQuotationDetail::where([
                     'id' => $quotation_detail['id'],
                     // 'quotation_id' => $request->id
                 ])->first();
                
                 
                //  if ($quotationDetail) {
                    
                //      $quotationDetail->update([
                //          'total_amount' => $quotation_detail['total_amount'],
                //          'equipment_id' => $quotation_detail['equipment_id'],
                //          'purchase_price' => $quotation_detail['purchase_price'],
                //          'description' => $quotation_detail['descriptions']?$quotation_detail['descriptions']:$quotation_detail['equipment_name'],
                //          'quantity' => $quotation_detail['quantity'],
                //          'margin' => $quotation_detail['margin'],
                //          'sell_price' => $quotation_detail['sell_price'],
                //          'remark' => $quotation_detail['remark'],
                //          'equipment_description' => $quotation_detail['descriptionss']?$quotation_detail['descriptionss']:"",
                //          'unit_of_measure' => $quotation_detail['unit_of_measure'],
     
                //      ]);
                //  } else {
                    //  if(!$quotation_detail['equipment_id'])
                    //  {
                //         $equipment_exist=equipment::where('name','=',$quotation_detail['descriptions'])->first();
                //         if(!$equipment_exist){
                //         $equipment=equipment::create([
                //              'name'=> $quotation_detail['equipment'],
                //              'div_id' => $request['div_id']?$request['div_id']:0,  // ? $request['ps_date'] : Carbon::now()
                //  'user_id' => $request['user_id']?$request['user_id']:0,
                //  'type' => 'Non inventory',
                //          ]);
                //         }
                    //  }
                     RentalQuotationDetail::create([
                         'quotation_id' => $quotation->id,
                         'total_amount' => $quotation_detail['total_amount'],
                         // 'analyse_id' => $quotation_detail['analyse_id'],
                         'equipment_id' => isset($quotation_detail['equipment_id'])?$quotation_detail['equipment_id']:0,
                         'description' => $quotation_detail['descriptions']?$quotation_detail['descriptions']:'',
                         'purchase_price' => $quotation_detail['purchase_price'],
                         'quantity' => $quotation_detail['quantity'],
                         'margin' => $quotation_detail['margin'],
                         'sell_price' => $quotation_detail['sell_price'],
                         'unit_of_measure' => isset($quotation_detail['unit_of_measure'])?$quotation_detail['unit_of_measure']:null,
                         'equipment_description' => isset($quotation_detail['descriptionss'])?$quotation_detail['descriptionss']:null,
                         
                        
     
                     ]);
                    
       
         
                    //  }
                 $index++;
                
                 
         
                
     
                
       
                    }
                }
       
        
         
         
       
     
   
 


public function show_quotation($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotation = RentalQuotation::where('id', $id)->first();
        $temp =  new Collection();

        $data = [
            "id" => $quotation->id,
            'quotation_no' => $quotation->quotation_no,
            "party_id" => $quotation->party_id,
            "file" => $quotation->file,
            "rfq_id" => $quotation->rfq_id || null,
            "status" => $quotation->status,
            "total_value" => $quotation->total_value,
            "discount_in_p" => $quotation->discount_in_p,
            "vat_in_value" => $quotation->vat_in_value,
            "net_amount" => $quotation->net_amount,
            "created_at" => $quotation->created_at,
            "updated_at" => $quotation->updated_at,
            "validity" => $quotation->validity,
            "payment_terms" => $quotation->payment_terms,
            "warranty" => $quotation->warranty,
            "delivery_time" => $quotation->delivery_time,
            "inco_terms" => $quotation->inco_terms,
            "po_number" => $quotation->po_number,
            "transaction_type" => $quotation->transaction_type,
            "ps_date" => $quotation->ps_date,
            "sales_order_number" => $quotation->sales_order_number,
            "contact" => $quotation->contact,
            "party" => $quotation->party,
            "partyDivision" => $quotation->party && ($quotation->party->partyDivision->map(function($payment){
                return $payment->partyDivision;
            })),
            "rfq" => $quotation->rfq,
            "is_revised" => $quotation->is_revised,
            // "sign" => $quotation->signature,
            "sign" => Designation::select('designations.*','users.email','users.contact')->join('users','users.id','designations.user_id')->where('designations.id',$quotation->sign)->get(),
            "notes" => $quotation->notes,
            "bank" => $quotation->bank,
            "currency_type" => $quotation->currency_type,
            "freight_type" => $quotation->freight_type,
            "subject" => $quotation->subject,
            "rfq_no" => $quotation->rfq_no,
            "transport" => $quotation->transport,
            "other" => $quotation->other,
            "div_id" => $quotation->div_id,
            "user_id" => $quotation->user_id,

            $quotation->quotationDetail->map(function ($quotation_detail) {
                $filePath = $quotation_detail->file_img_url ? $quotation_detail->file_img_url : '';
                $urlPath = $filePath ? url($filePath) : null;
            
                    // -> as it return std object
                // $temp->filter(function ($item) {  
                // if($item->index1==$quotation_detail->index1)
                
                return [
                   
                        // -> as it return std object
                    // 'index1'.$quotation_detail->index1= $quotation_detail
                    
                    // "id" => $quotation_detail->id,
                    // "index1" => $quotation_detail->index1,
                    // "total_amount" => $quotation_detail->total_amount,
                    // "analyse_id" => $quotation_detail->analyse_id,
                    // "equipment_id" => $quotation_detail->equipment_id,
                    // "descriptions" => $quotation_detail->description,
                    // "descriptionss" => $quotation_detail->equipment_description,
                    // "amaco_description" => $quotation_detail->amaco_description,
                    // "equipment" => $quotation_detail->equipment,
                    // "equipment_name" => " ",
                    // // "partyDivision" => $quotation_detail->partyDivision,
                    // "equipment_price_list" => $quotation_detail->equipment? $quotation_detail->equipment->equipmentPrice->map(function ($equipmentP) {
                    //     return [
                    //         'price' => $equipmentP->price,
                    //         'firm_name' => $equipmentP->party->firm_name
                    //     ];
                    // }):null,
                    
                    // // "equipment_price_list" => $quotation_detail->equipment->equipmentPrice,
                    // "purchase_price" => $quotation_detail->purchase_price?$quotation_detail->purchase_price:'',
                    // "description" => $quotation_detail->description,
                    // "quantity" => $quotation_detail->quantity,
                    // "discount" => $quotation_detail->discount,
                    // "margin_val"=>$quotation_detail->purchase_price ? ((((float)$quotation_detail->purchase_price)*(float)$quotation_detail->margin)/100)*(float)($quotation_detail->quantity):((float)($quotation_detail->quantity)*(float)($quotation_detail->sell_price))+$quotation_detail->discount_val,
                    // "discount_val"=>$quotation_detail->purchase_price?(((float)((float)($quotation_detail->discount) * ((float)(((float)$quotation_detail->margin * (float)($quotation_detail->purchase_price) / 100) + (float)($quotation_detail->purchase_price))) / 100)) * (float)($quotation_detail->quantity)):(float)$quotation_detail->discount_val,
                    // "cost_qty"  => (float)$quotation_detail->purchase_price*(int)$quotation_detail->quantity,
                    // 'unit_of_measure' => $quotation_detail->unit_of_measure,
                    // // "delivered_quantity"=> $quotation_detail->quantity,
                    // "delivered_quantity" => $quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    // "balance" => (int)$quotation_detail->quantity - (int)$quotation_detail->getDeliveredQuantityrental($quotation_detail),
                    // "margin" => $quotation_detail->margin? $quotation_detail->margin:'',
                    // "sell_price" => $quotation_detail->sell_price,
                    // "remark" => $quotation_detail->remark,
                    // "file" => $urlPath,
                    // "created_at" => $quotation_detail->created_at,
                    // "updated_at" => $quotation_detail->updated_at,
                   
                  
                ];
            }
                         // });
            ) 
        ];
        $qData = RentalQuotationDetail::where('quotation_id', $id)->orderBy('index1','asc')->get();
        foreach ($qData as $key => $value) {
            $ind[] = $value -> index1;
        }
        $result = array_unique($ind); 
         $result = array_unique($ind); 
        foreach ($result as $key => $value) {
            $a[$value]= RentalQuotationDetail::where('quotation_id', $id)
            ->where('index1',$value)
            ->get();
        }
        $data['quotation_details'] = [$a];
        return response()->json(
           [$data]
        );
    }
    public function quoteHistory(){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = RentalQuotation::where(['status' => 'accept', 'transaction_type' => 'sale'])
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('invoices')
                ->whereRaw('invoices.quotation_id = quotations.id');
        })->orderBy('created_at', 'DESC')
        ->get();
        $revisedQuote=RentalQuotation::where(['is_revised' => 1])->get();
    // $quotations = RentalQuotation::where('status','=','New')->orderBy('created_at','DESC')->get();
    $resData=$quotations->concat($revisedQuote);
    $quotations_data = [
        $resData->map(
            function ($quotation) {
                return [
                    'id' => $quotation->id,
                    'ps_date' => $quotation->ps_date,
                    'quotation_no' => $quotation->quotation_no,
                    'created_at' => $quotation->created_at,
                    'updated_at' => $quotation->updated_at,
                    'status' => $quotation->status,
                    'total_value' => $quotation->total_value,
                    'party_id' => $quotation->party_id,
                    "contact_id" => $quotation->contact_id,
                    "contact" => $quotation->contact,
                    "party" => $quotation->party,
                    "vat_in_value" => $quotation->vat_in_value,
                    "net_amount" => $quotation->net_amount,
                    "transaction_type" => $quotation->transaction_type,
                    'discount_in_p' => $quotation['discount_in_p'],
                    'div_id' => $quotation->div_id,
                    'subject' => $quotation->subject,
                    'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                        $quotation_detail = RentalQuotationDetail::where('id', '=', $quotation_detail->id)->first();
                        // $isDelivered = $this->checkDeliveredequipmentQuantity($quotation_detail);
                        // dd($isDelivered);
                        return [
                            "id" => $quotation_detail['id'],
                            "created_at" => $quotation_detail->created_at,
                            "updated_at" => $quotation_detail->updated_at,
                            "equipment_id" => $quotation_detail->equipment_id,
                            "equipment" => array($quotation_detail->equipment),
                            "description" => $quotation_detail->description,
                            "amaco_description" => $quotation_detail->descriptionss,
                            "quantity" => $quotation_detail->quantity,
                            "total_amount" => $quotation_detail->total_amount,
                            "analyse_id" => $quotation_detail->analyse_id,
                            "purchase_price" => $quotation_detail->purchase_price,
                            "margin" => $quotation_detail->margin,
                            "sell_price" => $quotation_detail->sell_price,
                            "remark" => $quotation_detail->remark,
                        ];
                    }),
                ];
            }
        ),
    ];
    return response()->json($quotations_data[0], 200);  
    }


    //multiple response data for purchase Invoice generate from purchase order
    public function mjrPurchaseInvoice($poid)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json([
            // 'vendor' => PartyController::vendor($did),
            'sales_quotation' => $this->shows($poid),
            'equipment' => equipmentController::index(),
            
           
        ]);
    }
}
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\PurchaseReturn;
use App\Models\PurchaseInvoice;
use App\Models\PaymentAccount;
use App\Models\Expense;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Party;
use App\Models\Contact;
use App\Models\Division;
use App\Models\PurchaseReturnDetail;
use DB;


use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\CompanyBankController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UOMController;
use App\Http\Controllers\Api\ProductPriceController;


class PurchaseReturnController extends Controller
{
    

    public function mjrPurchaseReturnInc($did){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json([
            'vendor' => PartyController::vendor($did),
            'products' => ProductController::index(),
            'uom' => UOMController::uom(),
            'productPrice' => ProductPriceController::productPrice(),
        ]);

    }

    public function mjrPurchaseReturnEdit($did,$id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $d = $this -> getPurchaseReturnEditData($id);
        return response()->json([
            'vendor' => PartyController::vendor($did),
            'products' => ProductController::index(),
            'uom' => UOMController::uom(),
            'productPrice' => ProductPriceController::productPrice(),
            'eData' => $d->original,
        ]);

    }


    public function index($id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        
        $data = PurchaseInvoice::
        where('party_id','=',$id)
        ->get();
         $data -> map(function ($item){
            $item['products'] = $this -> getProductsPR($item -> invoice_no);
            return $item;
        });

        return response()->json([
            'status' => 200,
            'getPurchaseReturnData' => $data,
        ]);
    }



    public function getPurchaseReturnEditData($id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $data = PurchaseReturn::
        where('purchase_returns.transaction_type','purchase')
        ->where('purchase_returns.pr_id','=',$id)
        ->orderBy('purchase_returns.created_at', 'DESC')
        ->get(); 
         $datas = PurchaseReturnDetail::
        join('products','products.id','purchase_returns_details.product_id')
        ->where('pr_id','=',$id)
        ->select('products.*','purchase_returns_details.unit_of_measure as unit_of_measure','purchase_returns_details.*')
        ->get();

        $datas -> map(function ($item) {
            $item['delete'] = false;
        });
        // $data[0]->party_id

        $party = Party::where('id',$data[0]->party_id)->get();
        $cont  = Contact::where('party_id',$data[0]->party_id)->get();

        $Odata = PurchaseInvoice::
        join('purchase_invoice_details','purchase_invoice_details.purchase_invoice_id','purchase_invoices.id')
        ->where('purchase_invoices.party_id','=',$data[0]->party_id)
        ->orderBy('purchase_invoices.created_at', 'DESC')
        ->get(); 

        // $odata = 

        return response()->json([
            'status' => 200,
            'data' => $data,
            'party' => $party,
            'cont' => $cont,
            'datas' => $datas,
            'Odata' => $Odata,
        ]);
    }


    public function getReturnInv($id){

        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $purchaseReturn = PurchaseReturn::
        join('parties','parties.id','purchase_returns.party_id')
        ->where('purchase_returns.transaction_type','purchase')
        ->where('purchase_returns.pr_id','=',$id)
        ->orderBy('purchase_returns.created_at', 'DESC')
        ->get();

         $returnItems = PurchaseReturnDetail::
        where('pr_id','=',$id)
        ->get()->values();
        return response()->json([
            'status' => 200,
            'getReturnParty' => $purchaseReturn,
            'getReturnItems' => $returnItems,
        ]);    
    }

    public function getProductsPR($po){
        if(!auth()->check())
        return ["You are not authorized to access this API."];

       
          $dd = PurchaseReturnDetail::
          join('products','products.id','purchase_returns_details.product_id')
        //   ->join('purchase_returns','purchase_returns.id','purchase_returns_details.pr_id')
        ->where('purchase_returns_details.po_number','=',$po)
        ->get();


        return $dd;
    }

       //test for po number generation
       public function genPurchaseNo($date, $div)
       {
   
           $current_year = $this->getCurrentYear($date);
           $current_month = $this->getCurrentMonth($date);
   
           $patern = 'AMC'.$div.'-DN-' . $current_year . '-' . $current_month;
   
   
           $quotation=PurchaseReturn::where('pr_number', 'like', '%'.$patern.'%')->latest('created_at')->first();
           
   
               if ($quotation) {
                   $subval = explode("-", $quotation->pr_number,)[3];
   
                   return ('AMC' . $div . '-DN-' . $current_year . '-' . $current_month .  sprintf("%02d", ((int)(substr($subval, 2)) + 1)));
               } else {
                   return ('AMC' . $div . '-DN-' . $current_year . '-' . $current_month . "01");
               }
           
       }


  public function getLastPONo($date,$d)
    {


        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);

        $patern='AMC'.$d.'-DN-'.$current_year.'-'.$current_month;
        $quotation=PurchaseReturn::where('pr_number', 'like', '%'.$patern.'%')->latest('created_at')->first();

        // $quotation = PurchaseReturn::
        //     orderby('pr_id','DESC')->first();
        if ($quotation) {
            $latest_po_number = $quotation->pr_number ? $quotation->pr_number : 0;
            return ($latest_po_number);
        } else {
            return ('AMC'.$d.'-DN-' . $this->getCurrentYear($date) . '-' . $this->getCurrentMonth($date) . sprintf("%02d", 0));
        }
    }

    
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


       public function getPONo($date,$d)
    {
        $latest_po_number = $this->getLastPONo($date,$d);
        $last_year = substr($latest_po_number, 8, 2);
        $last_month = substr($latest_po_number, 11, 2);
        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);
        if ($current_year != $last_year) {
            return ('AMC'.$d.'-DN-' . $current_year . '-' . $current_month  . sprintf("%02d", 1));
        } 
        else {
            if ($current_month != $last_month) {
                return ('AMC'.$d.'-DN-' . $current_year . '-' . $current_month  . sprintf("%02d", 1));
            } else {
                if (((int)substr($this->getLastPONo($date,$d), 13) < 99)) {
                    return ('AMC'.$d.'-DN-' . $current_year . '-' . $current_month . sprintf("%02d", ((int)substr($this->getLastPONo($date,$d), 13)) + 1));
                } else {
                    return ('AMC'.$d.'-DN-' . $current_year . '-' . $current_month . sprintf("%03d", ((int)substr($this->getLastPONo($date,$d), 13)) + 1));
                }
            }
        }
    }

    public function purchasereturn(Request $request){

        if(!auth()->check())
        return ["You are not authorized to access this API."];

        $rfqId = $request->rfq_id ? $request->rfq_id :null;
        $parentId = null;
        if($request['parent_id']){
            $parentId = $request['parent_id'];
        }



        // // try {
            $datas = [
                'party_id' => $request['party_id'],
                'rfq_id' => $request['rfq_id']?$request['rfq_id']:0,
                'status' => 'New',
                'parent_id' => $parentId,
                'total_value' => $request['total_value'],
                'user_id' => $request['user_id'],
                'div_id' => $request['div_id'],
                'net_amount' => $request['net_amount'],
                'vat_in_value' => $request['vat_in_value'],
                'discount_in_p' => $request['discount_in_p'],
                'validity' => $request['validity'],
                'payment_terms' => $request['payment_terms'],
                'warranty' => $request['warranty'],
                'currency_type' => $request['currency_type'],
                'freight_type' => $request['freight'],
                'delivery_time' => $request['delivery_time'],
                
                'inco_terms' => $request['inco_terms'],
                // 'pr_number' => $this->getPONo(),
                'contact_id' => $request['contact_id']?$request['contact_id']:null,
                'transaction_type' => $request['transaction_type'],
                'ps_date' => $request['ps_date'],  // ? $request['ps_date'] : Carbon::now()
                'sign' => $request['sign'],  // ? $request['ps_date'] : Carbon::now()
                'bank_id' => (int)$request['bank_id'],  // ? $request['ps_date'] : Carbon::now()
                'subject' => $request['subject']?$request['subject']:null,  // ? $request['ps_date'] : Carbon::now()
                'rfq_no' => $request['rfq_no']?$request['rfq_no']:null,  // ? $request['ps_date'] : Carbon::now()
            ];
            if ($request['transaction_type'] == "sales") {
                
       //     $payment_account_id=PaymentAccount::where('div_id', $request['div_id'])->first();
    //     $receipt = Receipt::create(["party_id" => $request->party_id,
    //     "payment_mode" => "cash",
    //     "narration" => "Credit Note",
    //     "paid_amount" => $request['total_value'],
    //     "paid_date" => $request['ps_date'],
    //     "div_id" => $payment_account_id->id,
    //     "user_id" => $request['user_id'],
    //     "division_id" => $request['div_id'],
    //     "sender" => $request['party_id'],
    //     "receiver" => $request['div_id'],
    // ]);

    // if ($receipt->id) {
    //     $receipt->update(['voucher_no' => 'AMC-'.'TR-'.'RV-'.date('y').'-' . sprintf('%05d', $receipt->id)]);
    // }
    // if($request->payment_mode=="cash")
    // {
        // $res=AdvancePayment::create([
        //     'payment_account_id' => $payment_account_id->id,
        //     'received_by' =>$request->receiver,
        //     'payment_mode' => $request->payment_mode,
        //     'amount' => $request->paid_amount,
        //     "received_date" => $request->paid_date,
        // ]);
    // }

            }
            else{
                $payment_account_id=PaymentAccount::where('div_id', $request['div_id'])->first();
                $res=Expense::create([
                   
                    'amount' =>  $request['total_value'],
                    'payment_type' => "cash",
                    'paid_date' => $request['ps_date'],
                    'payment_account_id' =>$payment_account_id->id,
                    'description' => "Debit Note",
                   
                    "account_category_id" => 33,
                    "status" =>"new",
                   
                    "div_id" => $request['div_id']? $request['div_id']:0,
                    "user_id" => $request['user_id'],
                   
                    "utilize_div_id"=> $payment_account_id->id? $payment_account_id->id:0,
                    "vendor_id"=>$request['party_id'],
                   
        
                ]);
                if ($res->id) {
                    $res->update(['voucher_no' => 'AMC-'.'TR-'.'EV-'.date('y').'-' . sprintf('%05d', $res->id)]);
                    }
            }
            
 
            if ($request['transaction_type'] == "sales") {
                $divi = Division::where('id', $request['div_id'])->get(['id_initials']);
                // $divi = $request['div_id'] == '1' ? 'T' : 'P';
                
                    $datas['quotationr_no'] = $this->getQuotationNo($request['ps_date'],$divi[0]['id_initials']);
            } elseif ($request['transaction_type'] == "purchase") {
                $divi = Division::where('id', $request['div_id'])->get(['id_initials']);
                $datas['pr_number'] = $this->genPurchaseNo($request['ps_date'],$divi[0]['id_initials']);
            }

            // $datas['pr_number'] = $this->getPONo();
            $quotation = PurchaseReturn::create($datas);
           
            global $quotation_id;
            $quotation_id = $quotation->pr_id;
            
            if ($request->transaction_type === 'purchase') {
                foreach ($request['quotation_details'] as $key => $quotation_detail) {
                    $a = $quotation_detail['po_number'] ? $quotation_detail['po_number'] : null;
                    PurchaseReturnDetail::create([
                        'pr_id' => $quotation_id,
                        'total_amount' => $quotation_detail['total_amount'],
                        'analyse_id' => null,
                         
                        'po_number' => $quotation_detail['po_number'],
                        'product_id' => $quotation_detail['product_id']?$quotation_detail['product_id']:null,
                        'purchase_price' => $quotation_detail['purchase_price'],
                        'description' => $quotation_detail['description']?$quotation_detail['description']:'',
                        'product_description' => $quotation_detail['product_name'],
                        'quantity' => $quotation_detail['quantity'],
                        'unit_of_measure' => isset($quotation_detail['unit_of_measure']) ? $quotation_detail['unit_of_measure'] : null,
                        'margin' => $quotation_detail['margin'],
                        'sell_price' => $quotation_detail['sell_price'],
                        'remark' => $quotation_detail['remark'],
                    ]);
                }
            }else{
                 foreach ($request['quotation_details'] as $key => $quotation_detail) {
                    // $a = $quotation_detail['po_number'];
                    PurchaseReturnDetail::create([
                        'pr_id' => $quotation_id,
                        'total_amount' => $quotation_detail['total_amount'],
                        'analyse_id' => null,
                        'invoice_no' => $quotation_detail['invoice_no'],
                        'product_id' => $quotation_detail['product_id']?$quotation_detail['product_id']:null,
                        'purchase_price' => $quotation_detail['purchase_price'],
                        'description' => $quotation_detail['product_name']?$quotation_detail['product_name']:$quotation_detail['product'],
                        'product_description' => $quotation_detail['description'],
                        'quantity' => $quotation_detail['quantity'],
                        'unit_of_measure' => isset($quotation_detail['unit_of_measure'])? $quotation_detail['unit_of_measure'] : null,
                        'margin' => $quotation_detail['margin'],
                        'sell_price' => $quotation_detail['sell_price'],
                        'remark' => $quotation_detail['remark'],
                    ]);
                }
                
            }

            return response()->json([
            'status' => 200,
            'getReturnParty' =>$request['transaction_type'],
        ]);  
          
    }

     public function revisedQuotationNo($quotationNo)
    {
        if(strlen($quotationNo) > 14){
            $revisedQuotation =  substr($quotationNo, 0,14)."-REV-".sprintf("%02d",((int)substr($quotationNo, 19))+1);
            return $revisedQuotation;
        }else{
            $revisedQuotation =  $quotationNo. "-REV-" . sprintf("%02d", 1);
            return $revisedQuotation;
        }
    }
     public function getQuotationNo($date,$d)
     {
   
        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);

        $patern = 'AMC'.$d.'-CN-' . $current_year . '-' . $current_month;


        $quotation=PurchaseReturn::where('quotationr_no', 'like', '%'.$patern.'%')->where('transaction_type', 'sales')->latest('created_at')->first();
        

            if ($quotation) {
                $subval = explode("-", $quotation->quotationr_no,)[3];

                return ('AMC' . $d . '-CN-' . $current_year . '-' . $current_month .  sprintf("%02d", ((int)(substr($subval, 2)) + 1)));
            } else {
                return ('AMC' . $d . '-CN-' . $current_year . '-' . $current_month . "01");
            }
        
    }
    public function getLastQuotationNo($date,$d)
    {


        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);
       
        $patern='AMC'.$d.'-CN-'.$current_year.'-'.$current_month;
        $quotation=PurchaseReturn::where('quotationr_no', 'like', '%'.$patern.'%')->where('transaction_type', 'sales')->latest('created_at')->first();


        // $quotation = PurchaseReturn::where('transaction_type', 'sales')
        //     ->latest('created_at')->first();


            if ($quotation) {
            $latest_quotation_no = $quotation->quotationr_no ? $quotation->quotationr_no : 0;
            return ($latest_quotation_no);
        } else {
            return ('AMC'.$d.'-CN-' . $this->getCurrentYear($date) . '-' . $this->getCurrentMonth($date) . sprintf("%02d", 0));
        }
    }

    public function purchaseReturnTableData(){

        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = PurchaseReturn::
        join('parties','parties.id','purchase_returns.party_id')
        ->where("purchase_returns.transaction_type",'purchase')
        ->orderBy('purchase_returns.created_at', 'DESC')
        ->get();


        return response()->json($quotations, 200);
    }

    public function getPurchaseReturnINV($id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];

        $purchaseReturn = PurchaseReturn::
        join('parties','parties.id','purchase_returns.party_id')
        ->where('purchase_returns.transaction_type','purchase')
        ->where('purchase_returns.pr_id','=',$id)
        ->orderBy('purchase_returns.created_at', 'DESC')
        ->get()->values();

        $returnItems = PurchaseReturnDetail::
        where('pr_id','=',$id)
        ->get()->values();

        $temp = $returnItems -> map(function ($item){
            $item['podata'] = $item -> po_number;
            // $item['podata'] = $this -> getProductsPR($item -> po_number);
            return $item['podata'];
        });

        return response()->json([
            'status' => 200,
            'getReturnParty' => $purchaseReturn,
            'getReturnItems' => $returnItems,
            'podatas' => $temp,
        ]);    
    }

    public function deletepurchasereturn($id){

        if(!auth()->check())
        return ["You are not authorized to access this API."];

        $purchaseR = PurchaseReturn::where('pr_id','=',$id)->update([
            'delete_status' => 1
        ]);
        // $purchaseRD = PurchaseReturnDetail::where('pr_id','=',$id)->delete();
         return response()->json([
            'status' => 200,
            'getReturnParty' => "Deleted"
        ]);  
    }
    public function restorePr($id){

        if(!auth()->check())
        return ["You are not authorized to access this API."];

        $purchaseR = PurchaseReturn::where('pr_id','=',$id)->update([
            'delete_status' => 0
        ]);
        // $purchaseRD = PurchaseReturnDetail::where('pr_id','=',$id)->delete();
         return response()->json([
            'status' => 200,
            'getReturnParty' => "Deleted"
        ]);  
    }

    public function deletePr($id){

        if(!auth()->check())
        return ["You are not authorized to access this API."];

        $purchaseR = PurchaseReturn::where('pr_id','=',$id)->delete();
        $purchaseRD = PurchaseReturnDetail::where('pr_id','=',$id)->delete();
         return response()->json([
            'status' => 200,
            'getReturnParty' => "Deleted"
        ]);  
    }

    public function purchasereturnUpdate(Request $request){
        if(!auth()->check())
        return ["You are not authorized to access this API."];

        // $rfqId = $request->rfq_id ? $request->rfq_id :null;
        // $parentId = null;
        // if($request['parent_id']){
        //     $parentId = $request['parent_id'];

        // }



        // // try {
            $datas = [
                'party_id' => $request['party_id'],
                // 'rfq_id' => $request['rfq_id']?$request['rfq_id']:0,
                // 'status' => 'New',
                // 'parent_id' => $parentId,
                'total_value' => $request['total_value'],
                'user_id' => $request['user_id'],
                'div_id' => $request['div_id'],
                'net_amount' => $request['net_amount'],
                'vat_in_value' => $request['vat_in_value'],
                'discount_in_p' => $request['discount_in_p'],
                'validity' => $request['validity'],
                'payment_terms' => $request['payment_terms'],
                'warranty' => $request['warranty'],
                'currency_type' => $request['currency_type'],
                'freight_type' => $request['freight'],
                'delivery_time' => $request['delivery_time'],
                
                'inco_terms' => $request['inco_terms'],
                // 'pr_number' => $this->getPONo(),
                'contact_id' => $request['contact_id']?$request['contact_id']:null,
                // 'transaction_type' => $request['transaction_type'],
                'ps_date' => $request['ps_date'],  // ? $request['ps_date'] : Carbon::now()
                // 'sign' => $request['sign'],  // ? $request['ps_date'] : Carbon::now()
                // 'bank_id' => (int)$request['bank_id'],  // ? $request['ps_date'] : Carbon::now()
                // 'subject' => $request['subject']?$request['subject']:null,  // ? $request['ps_date'] : Carbon::now()
                // 'rfq_no' => $request['rfq_no']?$request['rfq_no']:null,  // ? $request['ps_date'] : Carbon::now()
            ];
 
 
            // if ($request['transaction_type'] == "sales") {
            //         $datas['quotationr_no'] = $this->getQuotationNo();
            // } elseif ($request['transaction_type'] == "purchase") {
            //     $datas['pr_number'] = $this->getPONo();
            // }

            // $datas['pr_number'] = $this->getPONo();
            $findId = PurchaseReturn::where('pr_id',$request['rfq_id'])->first();
            $quotation = $findId->update(['party_id' => $request['party_id'],
            
            'total_value' => $request['total_value'],
            'user_id' => $request['user_id'],
            'div_id' => $request['div_id'],
            'net_amount' => $request['net_amount'],
            'vat_in_value' => $request['vat_in_value'],
            'discount_in_p' => $request['discount_in_p'],
            'validity' => $request['validity'],
            'payment_terms' => $request['payment_terms'],
            'warranty' => $request['warranty'],
            'currency_type' => $request['currency_type'],
            'freight_type' => $request['freight'],
            'delivery_time' => $request['delivery_time'],
            
            'inco_terms' => $request['inco_terms'],
    
            'contact_id' => $request['contact_id']?$request['contact_id']:null,
          
            'ps_date' => $request['ps_date'],  // ? $request['ps_date'] : Carbon::now()
            
            ]);
           
            global $quotation_id;
            // $quotation_id = $quotation->pr_id;
            


            PurchaseReturnDetail::where('pr_id', $request['rfq_id'])->delete();
            
            if ($request->transaction_type === 'purchase') {
                foreach ($request['quotation_details'] as $key => $quotation_detail) {
                  
                        // return $quotation_detail['prd_id'];
                    
                    $a = $quotation_detail['po_number'];
                    $datas=PurchaseReturnDetail::where('prd_id',$quotation_detail['prd_id'])->first();
                    if($datas)
                    {
                        $datas->update([
                            // 'pr_id' => $quotation_id,
                            'total_amount' => $quotation_detail['total_amount'],
                            'analyse_id' => null,
                             
                            'po_number' => $quotation_detail['po_number'],
                            'product_id' => $quotation_detail['product_id']?$quotation_detail['product_id']:null,
                            'purchase_price' => $quotation_detail['purchase_price'],
                            'description' => $quotation_detail['description']?$quotation_detail['description']:$quotation_detail['description'],
                            'product_description' => $quotation_detail['product_description'],
                            'quantity' => $quotation_detail['quantity'],
                            'unit_of_measure' => isset($quotation_detail['unit_of_measure']) ? $quotation_detail['unit_of_measure'] : null,
                            'margin' => $quotation_detail['margin'],
                            'sell_price' => $quotation_detail['sell_price'],
                            'remark' => $quotation_detail['remark'],
                        ]);
                    }
                    else{
                    PurchaseReturnDetail::create([
                        'pr_id' => $request['rfq_id'],
                        'total_amount' => $quotation_detail['total_amount'],
                        'analyse_id' => null,
                         
                        'po_number' => $quotation_detail['po_number'],
                        'product_id' => $quotation_detail['product_id']?$quotation_detail['product_id']:null,
                        'purchase_price' => $quotation_detail['purchase_price'],
                        'description' => $quotation_detail['description']?$quotation_detail['description']:$quotation_detail['description'],
                        'product_description' => isset($quotation_detail['product_description'])?$quotation_detail['product_description']:null,
                        'quantity' => $quotation_detail['quantity'],
                        'unit_of_measure' => isset($quotation_detail['unit_of_measure']) ? $quotation_detail['unit_of_measure'] : null,
                        'margin' => $quotation_detail['margin'],
                        'sell_price' => $quotation_detail['sell_price'],
                        'remark' => $quotation_detail['remark'],
                    ]);
                    }
                }
            }else{
                foreach ($request['quotation_details'] as $key => $quotation_detail) {
                    $a = $quotation_detail['invoice_no'];
                    $datas=PurchaseReturnDetail::where('prd_id',$quotation_detail['prd_id'])->first();
                    if($datas)
                    {
                        $datas->update([
                            // 'pr_id' => $quotation_id,
                            'total_amount' => $quotation_detail['total_amount'],
                            'analyse_id' => null,
                             
                            'invoice_no' => $quotation_detail['invoice_no'],
                            'product_id' => $quotation_detail['product_id']?$quotation_detail['product_id']:null,
                            'purchase_price' => $quotation_detail['purchase_price'],
                            'description' => $quotation_detail['description']?$quotation_detail['description']:$quotation_detail['description'],
                            'product_description' => $quotation_detail['product_description'],
                            'quantity' => $quotation_detail['quantity'],
                            'unit_of_measure' => isset($quotation_detail['unit_of_measure'])? $quotation_detail['unit_of_measure'] : null,
                            'margin' => $quotation_detail['margin'],
                            'sell_price' => $quotation_detail['sell_price'],
                            'remark' => $quotation_detail['remark'],
                        ]);
                    }
                    else{
                    PurchaseReturnDetail::create([
                        'pr_id' => $request['rfq_id'],
                        'total_amount' => $quotation_detail['total_amount'],
                        'analyse_id' => null,
                         
                        'invoice_no' => $quotation_detail['invoice_no'],
                        'product_id' => $quotation_detail['product_id']?$quotation_detail['product_id']:null,
                        'purchase_price' => $quotation_detail['purchase_price'],
                        'description' => $quotation_detail['description']?$quotation_detail['description']:$quotation_detail['description'],
                        'product_description' => $quotation_detail['product_description'],
                        'quantity' => $quotation_detail['quantity'],
                        'unit_of_measure' => isset($quotation_detail['unit_of_measure'])? $quotation_detail['unit_of_measure'] : null,
                        'margin' => $quotation_detail['margin'],
                        'sell_price' => $quotation_detail['sell_price'],
                        'remark' => $quotation_detail['remark'],
                    ]);
                    }
                }
            }

            return response()->json([
            'status' => 200,
            'getReturnParty' =>$request['transaction_type'],
        ]);  

    

        
    
    
    
    }

}




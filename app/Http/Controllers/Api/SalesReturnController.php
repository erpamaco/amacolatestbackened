<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quotation;
use App\Models\PurchaseReturn;
use App\Models\party_division;
use App\Models\Contact;
use App\Models\Party;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\PurchaseReturnDetail;



use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\CompanyBankController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UOMController;
use App\Http\Controllers\Api\ProductPriceController;



class SalesReturnController extends Controller
{


    public function mjrSalesReturnInc($did){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        

        return response()->json([
            'customer' => PartyController::customer($did),
            'products' => ProductController::index(),
            'uom' => UOMController::uom(),
            'productPrice' => ProductPriceController::productPrice(),
        ]);

    }


    public function mjrSalesReturnEdit($did,$id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        

         $d = $this -> getsReturnEditData($id);
        return response()->json([
            'customer' => PartyController::customer($did),
            'products' => ProductController::index(),
            'uom' => UOMController::uom(),
            'productPrice' => ProductPriceController::productPrice(),
            'eData' => $d->original,
        ]);

    }

    public function index($id){
        
        $data = Invoice::
        where('party_id','=',$id)
        ->get();
        return response()->json([
            'status' => 200,
            'getPurchaseReturnData' => $data
        ]);

    }

      public function getSalesReturnINV($id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $purchaseReturn = PurchaseReturn::
        join('parties','parties.id','purchase_returns.party_id')
        ->where('purchase_returns.transaction_type','sales')
        ->where('purchase_returns.pr_id','=',$id)
        ->orderBy('purchase_returns.created_at', 'DESC')
        ->get();

        $returnItems = PurchaseReturnDetail::
        where('pr_id','=',$id)
        ->get();

        return response()->json([
            'status' => 200,
            'getReturnParty' => $purchaseReturn,
            'getReturnItems' => $returnItems
        ]);    
    }  


    public function salesData($id){
  
        $data = Invoice::
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

     public function getProductsPR($po){
          $dd = PurchaseReturnDetail::
          join('products','products.id','purchase_returns_details.product_id')
        //   ->join('purchase_returns','purchase_returns.id','purchase_returns_details.pr_id')
        ->where('purchase_returns_details.invoice_no','=',$po)
        ->get();
            return $dd;
    }
    
    public function getsReturnEditData($id){
        $data = PurchaseReturn::
        where('purchase_returns.transaction_type','sales')
        ->where('purchase_returns.pr_id','=',$id)
        ->orderBy('purchase_returns.created_at', 'DESC')
        ->get(); 

        $party=Party::where('id',$data[0]->party_id)->get();
        $contact=Contact::where('party_id',$data[0]->party_id)->get();


         $datas = PurchaseReturnDetail::
        join('products','products.id','purchase_returns_details.product_id')
        ->where('pr_id','=',$id)
        ->select('products.*','purchase_returns_details.unit_of_measure as unit_of_measure','purchase_returns_details.*')
        ->get();
        // $data[0]->party_id

        $Odata = Invoice::
        join('invoice_details','invoice_details.invoice_id','invoices.id')
        ->where('invoices.party_id','=',$data[0]->party_id)
        ->orderBy('invoices.created_at', 'DESC')
        ->get(); 

        // $odata = 

        return response()->json([
            'status' => 200,
            'data' => $data,
            'party' => $party,
            'contact' => $contact,
            'datas' => $datas,
            'Odata' => $Odata,
        ]);
    }
    
    public function getSalesReturnEdit($id){
        $purchaseReturn = PurchaseReturn::
        join('parties','parties.id','purchase_returns.party_id')
        ->where('purchase_returns.transaction_type','sales')
        ->where('purchase_returns.pr_id','=',$id)
        ->orderBy('purchase_returns.created_at', 'DESC')
        ->get();

        

         $purchaseReturn->map(function ($item) use($id) {
            $item['details'] = $this -> newFun($id);
            return $item;
        });




        // $returnItems->map(function ($item) {
        //     $item['aaaa'] = $purchaseReturn[0]->p;
        //     return $item;
        // });

        $json =  \Config::get('example.key');
        $contacts = Contact::where('party_id', '=', $purchaseReturn[0]->party_id)->get();
        $divisions=party_division::where('party_id',$purchaseReturn[0]->party_id)->join('payment_accounts','payment_accounts.id','party_divisions.div_id')->get();
        $data =
            [
                'contacts' => $contacts->map(function ($contact) {
                    return $contact;
                }),
          
            ];
        return response()->json([
            // 'status' => 200,
            'getReturnParty' => $purchaseReturn,
            // 'getReturnItems' => $returnItems,
            // 'party' => $data,
        ]);    
    }

    public function newFun($id){
        
        $returnItems = PurchaseReturnDetail::
        join('products','products.id','purchase_returns_details.product_id')
        ->where('pr_id','=',$id)
        ->get();
        return $returnItems;
    }

    public function getProductsSR($iv){
         $data = Invoice::
        where('invoice_no','=',$iv)
        ->get('id');

          $dd = InvoiceDetail::
        where('invoice_id','=',$data[0]->id)
        ->get();


        return response()->json([
            'status' => 200,
            'getPData' => $dd,
        ]);
    }



    public function SalesReturnTableData(){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = PurchaseReturn::
        join('parties','parties.id','purchase_returns.party_id')
        ->where("transaction_type",'sales')
        ->orderBy('purchase_returns.created_at', 'DESC')
        ->select('purchase_returns.*','parties.*','purchase_returns.div_id as d_id')
        ->get();


        return response()->json($quotations, 200);
    }


    public function getSalesFormData(){
        $getCust = Quotation::
        join('parties','parties.id','quotations.party_id')
        ->where('transaction_type','=','sale')
        ->get();


        return response()->json([
            'status' => 200,
            'getCust' => $getCust
        ]);
    }
    public function deleteReturnDetail($id){
        PurchaseReturnDetail::where('prd_id',$id)->delete();

        return response()->json([
            'status' => 200,
        ]);
    }
}

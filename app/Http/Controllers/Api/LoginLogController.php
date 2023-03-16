<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LoginLog;
use App\Models\PaymentAccount;
use DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class LoginLogController extends Controller
{

    public function loginActivities(){
         
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $data = LoginLog::join('users','users.id','login_logs.u_id')
        ->select('login_logs.u_id as id','users.name as name','login_logs.date_time as time','login_logs.type as type')
        ->orderBy('login_logs.id','DESC')
        ->get();
         return response()->json($data);
    }


    public function logoutLog($id){
    
        date_default_timezone_set("Asia/Calcutta"); 
          LoginLog::create([
                        'u_id' => $id,
                        // 'platform' => ,
                        // 'browser' => ,
                        // 'created_at' => ,
                        // 'updated_at' => ,
                        'type' => 'Logout',
                        'date_time' => date('d-m-Y @ H:i:s'),
                        // 'status' => ,
        ]);

          return response()->json($id);

    }

    public function activityLogs(){
         
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        

        $productLog = $this->getProductLog();
        $partyLog = $this ->getPartyLog();
        $partyContact = $this -> getPartyContactLog();
        $partyBank = $this -> getPartyBankLog();
        $rfq = $this -> getRFQLog();
        $purchaseOrder = $this -> getPurchaseOrderLog();
        $purchaseInvoice = $this -> getPurchaseInvoiceLog();
        $deliveryNote = $this -> getDeliveryNoteLog();
        $quotation = $this -> getQuotationLog();
        $salesInvoice = $this -> getSalesInvoiceLog();
        $salesReturn = $this -> getSalesReturnLog();
        $purchaseReturn = $this -> getPurchaseReturnLog();
        $expense = $this -> getExpenseLog();
        $receipts = $this -> getReceiptsLog();
        $payments = $this -> getPaymentsLog();
        $mAllLog =  $productLog->merge($partyLog);
        $mAllLog =  $mAllLog->merge($partyContact);
        $mAllLog =  $mAllLog->merge($partyBank);
        $mAllLog =  $mAllLog->merge($rfq);
        $mAllLog =  $mAllLog->merge($purchaseOrder);
        $mAllLog =  $mAllLog->merge($purchaseInvoice);
        $mAllLog =  $mAllLog->merge($quotation);
        $mAllLog =  $mAllLog->merge($deliveryNote);
        $mAllLog =  $mAllLog->merge($salesInvoice);
        $mAllLog =  $mAllLog->merge($salesReturn);
        $mAllLog =  $mAllLog->merge($purchaseReturn);
        $mAllLog =  $mAllLog->merge($expense);
        $mAllLog =  $mAllLog->merge($receipts);
        $mAllLog =  $mAllLog->merge($payments);
        // $mAllLog =  $mAllLog->merge($expense);
        $finalLog = collect($mAllLog)->sortBy('updated_at')->reverse()->values();
        return response()->json($finalLog);
    }


    public function getPaymentsLog(){
        $data = DB::table('advance_payments')
        ->join('users','users.id','advance_payments.user_id')
        ->join('payment_accounts','payment_accounts.id','advance_payments.received_by')
        ->select('users.name as uname','users.id as id','payment_accounts.name as received_by','advance_payments.payment_account_id as payment_account_id','advance_payments.amount as amount','advance_payments.created_at as created_at','advance_payments.updated_at as updated_at')
        ->get();
          $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "A Payment Details (".$item-> amount."/- ,Received By ". $item ->received_by." ,Paid By ".$this->getPaidBy($item -> payment_account_id)." ) has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "A Payment Details (".$item-> amount."/- ,Received By ". $item ->received_by." ,Paid By ".$this->getPaidBy($item -> payment_account_id)." ) has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }

    public function getPaidBy($id){
        $data = PaymentAccount::where('id',$id)->select('name')->get();
        $cleanName = $this -> clean($data);
        $cleanName = str_replace('name','',$cleanName);
        return($cleanName);
    }

    public function clean($name){
        $string = str_replace(' ', '-', $name); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $name);
    }
    public function getReceiptsLog(){
        $data = DB::table('receipts')
        ->join('users','users.id','receipts.user_id')
        ->join('parties','parties.id','receipts.party_id')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','receipts.voucher_no as voucher_no','receipts.paid_amount as paid_amount','receipts.created_at as created_at','receipts.updated_at as updated_at')
        ->get();
          $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "A Receipt Details (" . $item-> firm_name."-".$item->voucher_no."(".$item-> paid_amount."/-)) has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "A Receipt Details (" . $item-> firm_name."-".$item->voucher_no."(".$item-> paid_amount."/-)) has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }


        public function getExpenseLog(){
        $data = DB::table('expenses')
        ->join('users','users.id','expenses.user_id')
        ->join('payment_accounts','payment_accounts.id','expenses.utilize_div_id')
        ->join('account_categories','account_categories.id','expenses.account_category_id')
        ->select('users.name as uname','users.id as id','account_categories.name as catname','expenses.voucher_no as voucher_no','expenses.amount as amount','payment_accounts.name as utilized_div','expenses.created_at as created_at','expenses.updated_at as updated_at')
        ->orderBy('expenses.id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> updated_at  >= $item -> created_at){
                 $item -> log = 'crea';
                $item->log = "Expense Details "."Utilized Division is ".$item-> utilized_div. " And Expense Category ".$item-> catname." (". $item-> voucher_no." :- ".$item->amount.") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Expense Details "."Utilized Division is ".$item-> utilized_div. " And Expense Category ".$item-> catname." (". $item-> voucher_no." :- ".$item->amount.") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }
            return $item;
        });
        return $data;
    }
        public function getPurchaseReturnLog(){
        $data = DB::table('purchase_returns')
        ->join('users','users.id','purchase_returns.user_id')
        ->join('parties','parties.id','purchase_returns.party_id')
        ->where('purchase_returns.transaction_type','purchase')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','purchase_returns.pr_number as pr_number','purchase_returns.created_at as created_at','purchase_returns.updated_at as updated_at')
        ->orderBy('purchase_returns.pr_id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "Purchase Return details (" . $item-> firm_name."-".$item->pr_number.") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Purchase Return details (" . $item-> firm_name."-".$item->pr_number.") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }

        public function getSalesReturnLog(){
    $data = DB::table('purchase_returns')
        ->join('users','users.id','purchase_returns.user_id')
        ->join('parties','parties.id','purchase_returns.party_id')
        ->where('purchase_returns.transaction_type','sales')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','purchase_returns.quotationr_no as quotationr_no','purchase_returns.created_at as created_at','purchase_returns.updated_at as updated_at')
       ->orderBy('purchase_returns.pr_id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "Sales Return details (" . $item-> firm_name."-".$item->quotationr_no.") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Sales Return details (" . $item-> firm_name."-".$item->quotationr_no.") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }
    
    public function getSalesInvoiceLog(){
    $data = DB::table('invoices')
        ->join('users','users.id','invoices.user_id')
        ->join('parties','parties.id','invoices.party_id')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','invoices.invoice_no as invoice_no','invoices.created_at as created_at','invoices.updated_at as updated_at')
       ->orderBy('invoices.id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "Sales Invoice details (" . $item-> firm_name."-".$item->invoice_no. ") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Sales Invoice details (" . $item-> firm_name."-".$item->invoice_no. ") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }

    
    public function getDeliveryNoteLog(){
    $data = DB::table('delivery_notes')
        ->join('users','users.id','delivery_notes.user_id')
        ->join('quotations','quotations.id','delivery_notes.quotation_id')
        ->join('parties','parties.id','quotations.party_id')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','delivery_notes.delivery_number as delivery_number','delivery_notes.created_at as created_at','delivery_notes.updated_at as updated_at')
       ->orderBy('delivery_notes.id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "Delivery note details (" . $item-> firm_name."-".$item->delivery_number. ") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Delivery note details (" . $item-> firm_name."-".$item->delivery_number. ") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }

    public function getQuotationLog(){
    $data = DB::table('quotations')
        ->join('users','users.id','quotations.user_id')
        ->join('parties','parties.id','quotations.party_id')
        ->where('quotations.transaction_type','sale')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','quotations.quotation_no as quotation_no','quotations.status as status','quotations.created_at as created_at','quotations.updated_at as updated_at')
       ->orderBy('quotations.id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                 if($item-> status == "New"){
                    $item->log = "A New Quotation (" . $item-> firm_name."-".$item->quotation_no. ") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
                 }else if($item-> status == "reject"){
                    $item->log = "Quotation (" . $item-> firm_name."-".$item->quotation_no. ") has Been Rejected By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
                 }else if($item -> status == "draft"){
                    $item->log = "Quotation (" . $item-> firm_name."-".$item->quotation_no. ") has Been Drafted By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
                 }else if($item -> status == "accept"){
                    $item->log = "Quotation (" . $item-> firm_name."-".$item->quotation_no. ") has Been Accepted By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
                 }
            }else{
                if($item-> status == "New"){
                    $item->log = "Quotation (" . $item-> firm_name."-".$item->quotation_no. ") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
                 }else if($item-> status == "reject"){
                    $item->log = "Quotation (" . $item-> firm_name."-".$item->quotation_no. ") has Been Rejected By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
                 }else if($item -> status == "draft"){
                    $item->log = "Quotation (" . $item-> firm_name."-".$item->quotation_no. ") has Been Drafted By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
                 }else if($item -> status == "accept"){
                    $item->log = "Quotation (" . $item-> firm_name."-".$item->quotation_no. ") has Been Accepted By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
                 }
            }
            return $item;
        });
        return $data;
    }

    public function getPurchaseInvoiceLog(){
    $data = DB::table('purchase_invoices')
        ->join('users','users.id','purchase_invoices.user_id')
        ->join('parties','parties.id','purchase_invoices.party_id')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','purchase_invoices.invoice_no as invoice_no','purchase_invoices.created_at as created_at','purchase_invoices.updated_at as updated_at')
       ->orderBy('purchase_invoices.id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "Purchase invoice details (" . $item-> firm_name."-".$item->invoice_no. ") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Purchase invoice details (" . $item-> firm_name."-".$item->invoice_no. ") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }
    public function getPurchaseOrderLog(){
    $data = DB::table('quotations')
        ->join('users','users.id','quotations.user_id')
        ->join('parties','parties.id','quotations.party_id')
        ->where('quotations.transaction_type','purchase')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','quotations.po_number as po_number','quotations.created_at as created_at','quotations.updated_at as updated_at')
       ->orderBy('quotations.id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "Purchase order details (" . $item-> firm_name."-".$item->po_number.") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Purchase order details (" . $item-> firm_name."-".$item->po_number.") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }
    public function getRFQLog(){
    $data = DB::table('r_f_q_s')
        ->join('users','users.id','r_f_q_s.user_id')
        ->join('parties','parties.id','r_f_q_s.party_id')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','r_f_q_s.created_at as created_at','r_f_q_s.updated_at as updated_at')
       ->orderBy('r_f_q_s.id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "RFQ Details (" . $item-> firm_name. ") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "RFQ Details (" . $item-> firm_name. ") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }
    public function getPartyBankLog(){
    $data = DB::table('party_banks')
        ->join('users','users.id','party_banks.user_id')
        ->join('parties','parties.id','party_banks.party_id')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','party_banks.created_at as created_at','party_banks.updated_at as updated_at')
       ->orderBy('party_banks.id','DESC')
        ->get();
        $data -> map(function ($item){
             if($item -> created_at >= $item -> updated_at){
                $item->log = "Bank Details (" . $item-> firm_name. ") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Bank Details (" . $item-> firm_name. ") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }
    public function getPartyContactLog(){
    $data = DB::table('contacts')
        ->join('users','users.id','contacts.user_id')
        ->select('users.name as uname','users.id as id','contacts.mobno as mobno','contacts.prefix as prefix','contacts.designation as designation','contacts.fname as cname','contacts.created_at as created_at','contacts.updated_at as updated_at')
        ->orderBy('contacts.id','DESC')
        ->get();
        $data -> map(function ($item){
            if($item -> created_at >= $item -> updated_at){
                $item->log = "Contact (" . $item-> prefix. " ". $item-> cname." ".$item->mobno. " ".$item->designation.") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Contact (" . $item-> prefix. " ". $item-> cname." ".$item->mobno. " ".$item->designation.") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }

    public function getPartyLog(){
    $data = DB::table('parties')
        ->join('users','users.id','parties.user_id')
        ->select('users.name as uname','users.id as id','parties.firm_name as firm_name','parties.created_at as created_at','parties.updated_at as updated_at')
        ->orderBy('parties.id','DESC')
        ->get();
        $data -> map(function ($item){
            if($item -> created_at >= $item -> updated_at){
                $item->log = "Party (" . $item-> firm_name. ") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "Party (" . $item-> firm_name. ") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }

    public function getProductLog(){
        $data = DB::table('products')
        ->join('users','users.id','products.user_id')
        ->select('users.name as uname','users.id as id','products.name as pname','products.created_at as created_at','products.updated_at as updated_at')
        ->orderBy('products.id','DESC')
        ->get();
        $data -> map(function ($item){
            if($item -> created_at >= $item -> updated_at){
                $item->log = "A Product (" . $item-> pname. ") has Been Created By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> created_at));
            }else{
                $item->log = "A Product (" . $item-> pname. ") has Been Updated By " .$item->uname . " @ ". date("D-d-M-Y", strtotime($item -> updated_at));
            }
            return $item;
        });
        return $data;
    }

}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Division;
use App\Models\Quotation;
use DB;
use Config;
use Stichoza\GoogleTranslate\GoogleTranslate;


use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\CompanyBankController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UOMController;

class InvoiceController extends Controller
{

    public function updateStatus(Request $request, $id)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];
        if ($request -> data == '0') {
            return (Invoice::where('id', $id)->update([
                'delete_status' => 1
            ]));
        } else {
            return (Invoice::where('id', $id)->update([
                'approve' => 1
            ]));
        }
    }



    public function mjrInvInc($did)
    {

        if (!auth()->check())
            return ["You are not authorized to access this API."];



        return response()->json([
            'customer' => PartyController::customer($did),
            'products' => ProductController::index(),
            'uom' => UOMController::uom(),
            'banks' => CompanyBankController::banks(),
            'productPrice' => ProductPriceController::productPrice(),
        ]);
    }

    public function invoiceFilter(Request $request)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $invoices = Invoice::where('status', '!=', 'Delivered')
            ->whereBetween('issue_date', $request->from_date, $request->to_date)
            ->orderBy('created_at', 'DESC')->get();
        // $result=$invoices->party;
        $invoices->map(function ($invoice) {
            // $invoice->payment_account;
            return $invoice->party;
        });
        return $invoices;
    }

    public function invoiceVatFile($id, $vat)
    {
        Invoice::where('id', $id)->update([
            'is_vat_filed' => $vat
        ]);

        $invoices = Invoice::where('status', '!=', 'Delivered')
            ->orderBy('created_at', 'DESC')->get();
        // $result=$invoices->party;
        $invoices->map(function ($invoice) {

            // $invoice->payment_account;
            return $invoice->party;
        });
        return $invoices;
    }

    public function changeStatus($id, $status, $type)
    {
        if ($type == 'generate') {
            Invoice::where('id', $id)->update([
                'genarate_status' => $status
            ]);
        } else if ($type == 'submit') {
            Invoice::where('id', $id)->update([
                'submit_status' => $status
            ]);
        } else if ($type == 'ack') {
            Invoice::where('id', $id)->update([
                'acknowledge_status' => $status
            ]);
        }
        return 200;
    }

    public function invoiceStatus($id, $status)
    {
        Invoice::where('id', $id)->update([
            'invoice_status' => $status
        ]);

        $invoices = Invoice::where('status', '!=', 'Delivered')
            ->orderBy('created_at', 'DESC')->get();
        // $result=$invoices->party;
        $invoices->map(function ($invoice) {

            // $invoice->payment_account;
            return $invoice->party;
        });
        return $invoices;
    }

    public function mjrEditInc($did, $id)
    {


        if (!auth()->check())
            return ["You are not authorized to access this API."];


        return response()->json([
            'customer' => PartyController::customer($did),
            'products' => ProductController::index(),
            'uom' => UOMController::uom(),
            'inv' => $this->shows($id),
            'banks' => CompanyBankController::banks(),
            'productPrice' => ProductPriceController::productPrice(),
        ]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCurrentYear($date)
    {
        return substr(date('Y', strtotime($date)), 2);
        // return substr(date('Y'), 2);
    }

    public function getCurrentMonth($date)
    {
        return date('m', strtotime($date));
        // return date('m');
    }

    public function getLastInvoiceNo()
    {
        $invoice = Invoice::latest('created_at')->first();
        if ($invoice) {
            $latest_invoice_no = $invoice->invoice_no ? $invoice->invoice_no : 0;
            return ($latest_invoice_no);
        }
        // else {
        //     return ('AMC-INV-' . $this->getCurrentYear() . '-' . sprintf("%02d", 0));
        // }
    }

    public function getInvoiceNo($date)
    {
        $latest_invoice_no = $this->getLastInvoiceNo();
        $last_year = substr($latest_invoice_no, 6, 2);
        $last_month = substr($latest_invoice_no, 11, 2);
        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);
        // dd([$last_year, $current_year]);
        // if ($current_year != $last_year) {
        //     return ('AMC-INV-' . $current_year . '-'. $current_month  . sprintf("%02d", 1));
        // } else {
        //     if($current_month != $last_month){
        //         return ('AMC-INV-' . $current_year . '-'. $current_month . sprintf("%02d", 1));
        //     }else{
        //         if((int)substr($this->getLastInvoiceNo(), 13) < 99){
        return ('AMC-INV-' . $current_year . '-' . $current_month .  sprintf("%02d", ((int)substr($latest_invoice_no, 13)) + 1));
        // return ('AMC-INV-' . $current_year . '-'. $current_month . sprintf("%02d", ((int)substr($this->getLastInvoiceNo(), 13)) + 1));
        //     }else{
        //         return ('AMC-INV-' . $current_year . '-'. $current_month . sprintf("%03d", $latest_invoice_no + 1));
        //         // return ('AMC-INV-' . $current_year . '-'. $current_month . sprintf("%03d", ((int)substr($this->getLastInvoiceNo(), 13)) + 1));
        //     }
        // }
        // }
    }

    public function partyInvoices($id)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $invoices = Invoice::where('party_id', $id)
            ->orderBy('created_at', 'DESC')->get();
        // $result=$invoices->party;
        $invoices->map(function ($invoice) {
            // $invoice->payment_account;
            return $invoice->party;
        });
        return $invoices;
    }
    public static function index()
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $invoices = Invoice::where('status', '!=', 'Delivered')
            ->orderBy('created_at', 'DESC')->get();
        // $result=$invoices->party;
        $invoices->map(function ($invoice) {

            // $invoice->payment_account;
            return $invoice->party;
        });
        return $invoices;
    }
    public function genInvoiceNo($date, $div)
    {

        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);

        $patern = 'AMC' . $div . '-INV-' . $current_year . '-' . $current_month;


        $deletedDatas = Invoice::where('invoice_no', 'like', '%' . $patern . '%')->where('delete_status', '1')->orderBy('invoice_no', 'desc')->get();
        if (count($deletedDatas) > 0) {
            Invoice::where('id', $deletedDatas[0]->id)->update([
                'invoice_no' => '',
            ]);
            return $deletedDatas[0]->invoice_no;
        } else {

            $res = Invoice::where('invoice_no', 'like', '%' . $patern . '%')->orderBy('invoice_no', 'desc')->first();
            if ($res) {
                $subval = explode("-", $res->invoice_no,)[3];

                return ('AMC' . $div . '-INV-' . $current_year . '-' . $current_month .  sprintf("%02d", ((int)(substr($subval, 2)) + 1)));
            } else {
                return ('AMC' . $div . '-INV-' . $current_year . '-' . $current_month . "01");
            }
        }
    }


    // ---------------proforma pattern

    public function genInvoiceNo2($date, $div)
    {

        $current_year = $this->getCurrentYear($date);
        $current_month = $this->getCurrentMonth($date);

        $patern = 'AMC' . $div . '-PINV-' . $current_year . '-' . $current_month;


        $deletedDatas = Invoice::where('invoice_no', 'like', '%' . $patern . '%')->where('delete_status', '1')->orderBy('invoice_no', 'desc')->get();
        if (count($deletedDatas) > 0) {
            Invoice::where('id', $deletedDatas[0]->id)->update([
                'invoice_no' => '',
            ]);
            return $deletedDatas[0]->invoice_no;
        } else {

            $res = Invoice::where('invoice_no', 'like', '%' . $patern . '%')->orderBy('invoice_no', 'desc')->first();
            if ($res) {
                $subval = explode("-", $res->invoice_no,)[3];

                return ('AMC' . $div . '-PINV-' . $current_year . '-' . $current_month .  sprintf("%02d", ((int)(substr($subval, 2)) + 1)));
            } else {
                return ('AMC' . $div . '-PINV-' . $current_year . '-' . $current_month . "01");
            }
        }
    }

    // ---
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        // $div = $request['div_id'] == '1' ? 'T' : 'P';

        // return $this->genInvoiceNo($request['ps_date'], $div);

        if (!auth()->check())
            return ["You are not authorized to access this API."];


        $data = $request->json()->all();

        // ------------------------------------------------------

        // $unique_po_no = Quotation::where('po_number', $request->po_number)->first();
        // $data = $request->all();
        // if ($request->po_number2) {
        // $quotation = Quotation::where("id", $request->id)->firstOrFail();
        // $filePath = null;
        // if ($request->file('file')) {
        //     $filePath = $request->file('file')->move("quotate/filePath",  $request->file('file')->getClientOriginalName());
        // }
        // if ($request->po_number) {

            // if (isset($unique_po_no)) {
            //     return response()->json(['msg' => 'P.O.Number is exsits']);
            // }

            // $sales_order_number = $this->getSalesOrderNumber($quotation->issue_date);
            // $quotation->update([
            //     // 'status' => $request->status,
            //     // 'sales_order_number' => $sales_order_number,
            //     'po_number' => $request->po_number2,
            //     'file' => $filePath,
                
            // ]);

        // }

        // ------------------------------------------------------
        // dd($data);
        // dd($request->vat_in_value);
        // dd($request->vat_in_value);
        // $div = $request['div_id'] == '1' ? 'T' : 'P';
        $divi = Division::where('id', $request['div_id'])->get(['id_initials']);
        // if($request['proforma'] == "pinv"){
            // $data['invoice_no'] = $this->genInvoiceNo2($request['ps_date'], $div);

        // }else{

            $data['invoice_no'] = $request['proforma'] == "pinv" ? $this->genInvoiceNo2($request['ps_date'], $divi[0]['id_initials']) :$this->genInvoiceNo($request['ps_date'], $divi[0]['id_initials']) ;
        // }
        
        
        // return $this->genInvoiceNo($request['ps_date']);
        // $data['invoice_no'] = $this->getInvoiceNo($request['ps_date']);
        $data['issue_date'] = $request['ps_date'];
        $data['vatExclude'] = $request['vatExclude'];
        $data['status'] = "New";
        $data['quotation_id'] = $request['quotation_id'];
        $data['proforma'] = $request['proforma'] ? "pinv" : null;
        $data['po_number'] = $request['po_number'] ? $request['po_number']  : $request->po_number ;
        $data['total_value'] = $request['total_value'];
        $data['discount_in_percentage'] = $request['discount_in_p'];
        $data['vat_in_value'] = $request['vat_in_value'];
        $data['grand_total'] = $request['grand_total'];
        $invoice = Invoice::create([
            'exclude_from_vat' => $data['vatExclude'],
            'approve' => auth()->user()->role->name == 'SA' ? 1 : 0,
            'invoice_no' => $data['invoice_no'],
            'po_number' => isset($request->po_number) ? $data['po_number'] : $request['po_number'] ,
            'issue_date' => $data['issue_date'],
            'contact_id' => $request['contact_id'] ? $request['contact_id'] : null,
            'status' => $data['status'],
            'proforma' => $data['proforma'],
            'quotation_id' => $data['quotation_id'],
            'bank_id' => $request['bank_id'] ? $data['bank_id'] : null, 
            'total_value' =>  $data['total_value'] == "NaN" ? 0 : (isset($data['total_value']) ? $data['total_value'] : 0),
            'discount_in_percentage' => $data['discount_in_percentage'],
            'vat_in_value' => $data['vat_in_value'] == "NaN" ? 0 : (isset($data['vat_in_value']) ? $data['vat_in_value'] : 0),
            'grand_total' =>  $data['grand_total'] == "NaN" ? 0 : (isset($data['grand_total']) ? $data['grand_total'] : 0),
            'delivery_no' => null,
            'party_id' => $request['party_id'],
            'div_id' => $request['div_id'] ? $request['div_id'] : 0,  // ? $request['ps_date'] : Carbon::now()
            'user_id' => $request['user_id'] ? $request['user_id'] : 0,
        ]);

        global $_invoice_id;
        $_invoice_id = $invoice['id'];
        $invoice_details = $request['invoice_details'];
        foreach ($invoice_details as $invoice_detail) {
            $apikey =  \Config::get('example.key');
            // $json = json_decode(file_get_contents($path), true);
            // -----------

            $inv_det = new GoogleTranslate('en');
            // $building_no_arr = 
    
    
            // -----------
            if($invoice_detail['product']){
                $arDescription = $invoice_detail['id'] ? null : $inv_det->setSource('en')->setTarget('ar')->translate($invoice_detail['product']);

            }else{
                $arDescription = null;

            }
            // ---------------
            $inv_prod_det = new GoogleTranslate('en');
            // $building_no_arr = 
    
    
            // -----------
            if($invoice_detail['product']){
                $arproductDescription =  $inv_prod_det->setSource('en')->setTarget('ar')->translate($invoice_detail['product']);

            }else{
                $arproductDescription = null;

            }
            // ---------------
            
            if (!$invoice_detail['productId']) {
                $product = Product::create([
                    'name' => $invoice_detail['product'],
                    'unit_of_measure' => $invoice_detail['unit_of_measure'],
                    'div_id' => $request['div_id'] ? $request['div_id'] : 0,  // ? $request['ps_date'] : Carbon::now()
                    'user_id' => $request['user_id'] ? $request['user_id'] : 0,
                    'type' => 'Non inventory',
                ]);
            }
            $_invoice_detail = InvoiceDetail::create([
                'quotation_detail_id' => $invoice_detail['id'] ? $invoice_detail['id'] : null,
                'product_id' => $invoice_detail['productId'] ? $invoice_detail['productId'] : $product->id,
                'sell_price' => isset($invoice_detail['sell_price']) ? $invoice_detail['sell_price'] : 0,
                'quantity' => $invoice_detail['quantity'],
                'margin' => $invoice_detail['margin'],
                'total_amount' => $invoice_detail['total_amount'] == 'NaN' ? 0 : (isset($invoice_detail['total_amount']) ? $invoice_detail['total_amount'] : 0),
                'unit_of_measure' => $invoice_detail['unit_of_measure'],
                'description' => $invoice_detail['description'] ? $invoice_detail['description'] : $invoice_detail['product'],
                'arabic_description' => $arproductDescription,
                'invoice_id' => $_invoice_id,
                'purchase_price' => isset($invoice_detail['purchase_price']) ? $invoice_detail['purchase_price'] : 0,
                // 'product_name' => $invoice_detail['product']?$invoice_detail['product']:null,
                // 'unit_of_measure' => $invoice_detail['unit_of_measure']?$invoice_detail['unit_of_measure']:null,
            ]);
        }

        if (auth()->user()->role->name == 'SA') {
      
        } else {
            $path = "/newinvoice/" . $_invoice_id;
            $noti = 'An Invoice has been Created by ' . auth()->user()->name . ' Please Verify Invoice and Approve';
            NotificationController::sendNotification('Invoice', 'alert', 'An Invoice Has Been Created', $noti, 'SA', $path);
        }
        // return 'success';
        return response()->json($invoice_details);
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
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public static function showsReport($inv)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $d = Invoice::where('invoice_no', $inv)->get();
        // return $invoice = $d[0];
        return [
            $invoice = $d[0],
            $invoice->party,
            // $invoice->contact,
            $invoice->quotation,
            //$invoice->quotation->quotationDetail,
            $invoice->invoiceDetail->map(function ($invoice_detail) {
                return [


                    $invoice_detail['margin'] = $invoice_detail->purchase_price ? (((((float)$invoice_detail->sell_price) - ((float)$invoice_detail->purchase_price)) / ((float)
                    $invoice_detail->purchase_price)) * 100) : $invoice_detail->margin,
                    $invoice_detail['delivered_quantity'] = $invoice_detail->getDelivered_invoice_Quantity($invoice_detail),
                    $invoice_detail['balance'] = (int)$invoice_detail->quantity - (int)$invoice_detail->getDelivered_invoice_Quantity($invoice_detail),
                    $invoice_detail->quotationDetail,
                    $invoice_detail->product
                ];
            }),

            $invoice->contact,
            // $invoice->invoiceDetail->map(function ($invoice_detail){
            //     return [
            //         $invoice_detail->quotationDetail,
            //     ];
            // }),

            // $product['name_ar'] = file_get_contents('https://api.mymemory.translated.net/get?q=helloworld!&langpair=en|ar');

        ];
    }
    public function shows($id)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $d = Invoice::where('id', $id)->get();
        // return $invoice = $d[0];
        return [
            $invoice = $d[0],
            $invoice->party,
            $invoice->bank,
            // $invoice->contact,
            $invoice->quotation,
            //$invoice->quotation->quotationDetail,
            $invoice->invoiceDetail->map(function ($invoice_detail) {
                return [


                    $invoice_detail['margin'] = $invoice_detail->purchase_price ? (((((float)$invoice_detail->sell_price) - ((float)$invoice_detail->purchase_price)) / ((float)
                    $invoice_detail->purchase_price)) * 100) : $invoice_detail->margin,
                    $invoice_detail['delivered_quantity'] = $invoice_detail->getDelivered_invoice_Quantity($invoice_detail),
                    $invoice_detail['balance'] = (int)$invoice_detail->quantity - (int)$invoice_detail->getDelivered_invoice_Quantity($invoice_detail),
                    $invoice_detail->quotationDetail,
                    $invoice_detail->product
                ];
            }),

            $invoice->contact,
            // $invoice->invoiceDetail->map(function ($invoice_detail){
            //     return [
            //         $invoice_detail->quotationDetail,
            //     ];
            // }),

            // $product['name_ar'] = file_get_contents('https://api.mymemory.translated.net/get?q=helloworld!&langpair=en|ar');

        ];
    }
    public function show(Invoice $invoice)
    {
        return [
            $invoice,
            $invoice->party,
            $invoice->bank,
            // $invoice->contact,
            $invoice->quotation,
            //$invoice->quotation->quotationDetail,
            $invoice->invoiceDetail->map(function ($invoice_detail) {
                return [


                    $invoice_detail['margin'] = $invoice_detail->purchase_price ? (((((float)$invoice_detail->sell_price) - ((float)$invoice_detail->purchase_price)) / ((float)
                    $invoice_detail->purchase_price)) * 100) : $invoice_detail->margin,
                    $invoice_detail['delivered_quantity'] = $invoice_detail->getDelivered_invoice_Quantity($invoice_detail),
                    $invoice_detail['balance'] = (int)$invoice_detail->quantity - (int)$invoice_detail->getDelivered_invoice_Quantity($invoice_detail),
                    $invoice_detail->quotationDetail,
                    $invoice_detail->product
                ];
            }),
            // $invoice->party->bank
            // $invoice->invoiceDetail->map(function ($invoice_detail){
            //     return [
            //         $invoice_detail->quotationDetail,
            //     ];
            // }),

            // $product['name_ar'] = file_get_contents('https://api.mymemory.translated.net/get?q=helloworld!&langpair=en|ar');

        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */

    public function getCurrentDeliveryYear()
    {
        return substr(date('Y'), 2);
    }

    public function getLastDeliveryNo()
    {
        $invoice = Invoice::latest('created_at')->first();
        if ($invoice) {
            $latest_delivery_no = $invoice->delivery_no ? $invoice->delivery_no : 0;
            return ($latest_delivery_no);
        } else {
            return ('AMDLV-' . $this->getCurrentDeliveryYear() . '-' . sprintf("%04d", 0));
        }
    }

    public function getDeliveryNo()
    {
        $latest_delivery_no = $this->getLastDeliveryNo();
        $last_year = substr($latest_delivery_no, 6, 2);
        $current_year = $this->getCurrentDeliveryYear();
        // dd([$last_year, $current_year]);
        if ($current_year != $last_year) {
            return ('AMDLV-' . $current_year . '-' . sprintf("%04d", 1));
        } else {
            return ('AMDLV-' . $current_year . '-' . sprintf("%04d", ((int)substr($this->getLastDeliveryNo(), 9)) + 1));
        }
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $data = $request->all();
        $data['status'] = 'Delivered';
        $data['delivery_no'] = $this->getDeliveryNo();
        $invoice->update($data);
        return $invoice;
    }
    public function Invoiceupdate(Request $request, Invoice $invoice)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $apikey =  \Config::get('example.key');
        $invoice = Invoice::where('id', $request->id)->first();

        $timestamp = strtotime($request->ps_date);
        $psMonth = date('M', $timestamp);

        $timestamp = strtotime($invoice->issue_date);
        $dbMonth = date('M', $timestamp);


        if ($psMonth !== $dbMonth) {
            $div = $request->div_id == '1' ? 'T' : 'P';
            $invoice->update([
                'invoice_no' => $this->genInvoiceNo($request->ps_date, $div)
            ]);
        }
        $quotation = Quotation::where("id", $request->qtid)->firstOrFail();
        $filePath = null;
        if ($request->file('file')) {
            $filePath = $request->file('file')->move("quotate/filePath",  $request->file('file')->getClientOriginalName());
        }
        $quotation->update([
            
            'file' => $filePath,
            
        ]);

        $invoice->update([
            // 'invoice_no' => $request->invoice_no,
            'po_number' => isset($request->po_number) ? $request->po_number : null,
            'issue_date' => $request->ps_date,
            'contact_id' => isset($request->contact_id) ? ($request->contact_id ? $request->contact_id : 0) : 0,
            // 'status' => $request->status,
            // 'quotation_id' => $request->quotation_id,
            'total_value' => $request->total_value,
            'bank_id' => $request->bank_id ? $request->bank_id : null,
            'exclude_from_vat' => $request->vatExclude,
            'discount_in_percentage' => $request->discount_in_p,
            'vat_in_value' => $request->vat_in_value,
            'grand_total' => $request->grand_total,
            'proforma' => $request->proforma ? "pinv" : null,
            'delivery_no' => null,
            'party_id' => $request->party_id ? $request->party_id : 0,
            'div_id' => $request->div_id ? $request->div_id : 0,  // ? $request->ps_date : Carbon::now()
            'user_id' => $request->user_id ? $request->user_id : 0,
            // 'contact_id' => $request->contact_id
        ]);
        $temp = json_decode($request['invoice_details'], true);
        $i = 0;

        InvoiceDetail::where('invoice_id', $request->id)->delete();
        foreach ((array) $temp as $invoice_detail) {



            $invoiceDetail = InvoiceDetail::where('id', $invoice_detail['id'])->first();
            if ($invoiceDetail) {

                if (!$invoice_detail['product_id']) {
                    $product_exist = Product::where('name', '=', $invoice_detail['product'])->first();
                    if (!$product_exist) {
                        $product = Product::create([
                            'name' => $invoice_detail['product'],
                            'unit_of_measure' => $invoice_detail['unit_of_measure'],
                            'type' => 'Non inventory',
                        ]);
                    } else {
                        $product = null;
                    }
                }
                $arDescription = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key=' . $apikey . '&q=' . urlencode($invoice_detail['description']) . '&target=ar'));
                $invoiceDetail->update([
                    // 'quotation_detail_id' => $invoice_detail['id']?$invoice_detail['id']:null,
                    'product_id' => $invoice_detail['product_id'] ? $invoice_detail['product_id'] : ($product ? $product->id : null),
                    'sell_price' => $invoice_detail['sell_price'],
                    'quantity' => $invoice_detail['quantity'],
                    'total_amount' => $invoice_detail['total_amount'],
                    'unit_of_measure' => $invoice_detail['unit_of_measure'] ? $invoice_detail['unit_of_measure'] : "",
                    'margin' => $invoice_detail['margin'],
                    'description' => $invoice_detail['description'] ? $invoice_detail['description'] : $invoice_detail['product'],
                    'arabic_description' => $invoice_detail['arabic_description'] ? $invoice_detail['arabic_description'] : $arDescription->data->translations[0]->translatedText,
                    // 'invoice_id' => $_invoice_id,
                    'purchase_price' => $invoice_detail['purchase_price'] ? $invoice_detail['purchase_price'] : null,


                ]);
            } else {


                if (!$invoice_detail['product_id']) {
                    $product_exist = Product::where('name', '=', $invoice_detail['product'])->first();
                    if (!$product_exist) {
                        $product = Product::create([
                            'name' => $invoice_detail['product'],
                            'unit_of_measure' => $invoice_detail['unit_of_measure'],
                            'div_id' => $request['div_id'] ? $request['div_id'] : 0,  // ? $request['ps_date'] : Carbon::now()
                            'user_id' => $request['user_id'] ? $request['user_id'] : 0,
                            'type' => 'Non inventory',
                        ]);
                    } else {
                        $product = null;
                    }
                }
                $inv_det_up = new GoogleTranslate('en');
                if($invoice_detail['description']){
                    $arDescription = $inv_det_up->setSource('en')->setTarget('ar')->translate($invoice_detail['description']);
                }
                else{
                    $arDescription = null;

                }
                InvoiceDetail::create([

                    'quotation_detail_id' => $invoice['quotation_id'] ? $invoice['quotation_id'] : null,
                    'product_id' => $invoice_detail['product_id'] ? $invoice_detail['product_id'] : ($product ? $product->id : null),
                    'sell_price' => $invoice_detail['sell_price'],
                    'quantity' => $invoice_detail['quantity'],
                    'margin' => $invoice_detail['margin'],
                    'total_amount' => $invoice_detail['total_amount'],
                    'unit_of_measure' => $invoice_detail['unit_of_measure'],
                    'description' => $invoice_detail['description'] ? $invoice_detail['description'] : $invoice_detail['product'],
                    'arabic_description' => $arDescription,
                    'invoice_id' => $request['id'],
                    'purchase_price' => $invoice_detail['purchase_price'] ? $invoice_detail['purchase_price'] : null,




                ]);
            }


            $i++;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        return ($invoice->update([
            'delete_status' => 1
        ]));
    }
    public function destroyNew($id, $comment)
    {

        if (!auth()->check())
            return ["You are not authorized to access this API."];

        if (auth()->user()->role->name == 'SA') {
        } else {
            $path = "/newinvoice/" . $id;
            $noti = 'An Invoice has been deleted by ' . auth()->user()->name;
            NotificationController::sendNotification('Invoice', 'alert', 'An Invoice Has Been Deleted', $noti, 'SA', $path);
        }
        return (Invoice::where('id', $id)->update([
            'delete_status' => 1,
            'comment' => $comment
        ]));
    }
    public function restoreSInv($id, $div)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];


        $div = $div == '1' ? 'T' : 'P';
        $data = Invoice::where('id', $id)->first();
        Invoice::where('id', $id)->update([
            'delete_status' => 0
        ]);
        //    return $this->genInvoiceNo($data -> issue_date, $div);
        // $data['invoice_no'] = 
        Invoice::where('id', $id)->update([
            'delete_status' => 0
        ]);

        return (Invoice::where('id', $id)->update([
            'invoice_no' => $this->genInvoiceNo($data->issue_date, $div),
        ]));
    }
    public function deleteSinv($id)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        return (Invoice::where('id', $id)->delete());
    }

    public function history()
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $invoices = Invoice::where('status', '=', 'Delivered')
            ->orderBy('created_at', 'DESC')->get();
        return response()->json($invoices);
    }
    public static function salesTax(Invoice $invoice)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $invoices = Invoice::where('approve',1)->get();
        $invoices->map(function ($val) {

            return $val->party;
        });
        return $invoices;
    }

    public static function salesTax2()
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $invoices = Invoice::where('approve',1)->get();
        $invoices->map(function ($val) {

            return $val->party;
        });
        return $invoices;
    }

    public function PurchaseInvoiceupdate(Request $request, Invoice $invoice)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];


        $apikey =  \Config::get('example.key');
        $invoice = PurchaseInvoice::where('id', $request->id)->first();



        $invoice->update([
            'invoice_no' => isset($request->invoice_no) ? $request->invoice_no : null,
            // 'po_number' => $request->po_number,
            'issue_date' => $request->ps_date,
            // 'status' => $request->status,
            // 'quotation_id' => $request->quotation_id,
            'total_value' => $request->total_value,
            'discount_in_percentage' => $request->discount_in_p,
            'vat_in_value' => $request->vat_in_value,
            'grand_total' => $request->grand_total,
            // 'delivery_no' => null,
            'party_id' => $request->party_id,
            'div_id' => $request->div_id ? $request->div_id : 0,  // ? $request->ps_date : Carbon::now()
            'user_id' => $request->user_id ? $request->user_id : 0,
            'currency_type' => $request->currency_type
        ]);
        $temp = json_decode($request['invoice_details'], true);
        $i = 0;

        PurchaseInvoiceDetail::where('purchase_invoice_id', $request->id)->delete();
        foreach ((array) $temp as $invoice_detail) {



            $invoiceDetail = PurchaseInvoiceDetail::where('id', $invoice_detail['id'])->first();
            if ($invoiceDetail) {

                if (!$invoice_detail['product_id']) {
                    $product_exist = Product::where('name', '=', $invoice_detail['product'])->first();
                    if (!$product_exist) {
                        $product = Product::create([
                            'name' => $invoice_detail['product'],
                            'type' => 'Non inventory',
                            'div_id' => $request['div_id'] ? $request['div_id'] : 0,  // ? $request['ps_date'] : Carbon::now()
                            'user_id' => $request['user_id'] ? $request['user_id'] : 0,
                        ]);
                    } else {
                        $product = null;
                    }
                }
                $arDescription = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key=' . $apikey . '&q=' . urlencode($invoice_detail['description']) . '&target=ar'));
                $invoiceDetail->update([
                    // 'quotation_detail_id' => $invoice_detail['id']?$invoice_detail['id']:null,
                    'product_id' => $invoice_detail['product_id'] ? $invoice_detail['product_id'] : ($product ? $product->id : null),
                    // 'sell_price' => $invoice_detail['sell_price'],
                    'quantity' => $invoice_detail['quantity'],
                    'total_amount' => $invoice_detail['total_amount'],
                    'unit_of_measure' => isset($invoice_detail['unit_of_measure']) ? $invoice_detail['unit_of_measure'] : null,
                    // 'margin' => $invoice_detail['margin'],
                    'description' => $invoice_detail['description'] ? $invoice_detail['description'] : $invoice_detail['product'],
                    'arabic_description' => isset($invoice_detail['arabic_description']) ? $invoice_detail['arabic_description'] : $arDescription->data->translations[0]->translatedText,
                    // 'invoice_id' => $_invoice_id,
                    'purchase_price' => $invoice_detail['purchase_price'] ? $invoice_detail['purchase_price'] : null,


                ]);
            } else {


                if (!$invoice_detail['product_id']) {
                    $product_exist = Product::where('name', '=', $invoice_detail['product'])->first();
                    if (!$product_exist) {
                        $product = Product::create([
                            'name' => $invoice_detail['product'],
                            'div_id' => $request['div_id'] ? $request['div_id'] : 0,  // ? $request['ps_date'] : Carbon::now()
                            'user_id' => $request['user_id'] ? $request['user_id'] : 0,
                            'type' => 'Non inventory',
                        ]);
                    } else {
                        $product = null;
                    }
                }
                $arDescriptionar = new GoogleTranslate('en');
                // $building_no_arr = 
        
        
                // -----------
                if($invoice_detail['description']){
                    $arDescription = $arDescriptionar->setSource('en')->setTarget('ar')->translate($invoice_detail['description']);
    
                }else{
                    $arDescription = null;
    
                }
                // $arDescription = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key=' . $apikey . '&q=' . urlencode($invoice_detail['description']) . '&target=ar'));
                PurchaseInvoiceDetail::create([

                    'quotation_detail_id' => $invoice['quotation_id'] ? $invoice['quotation_id'] : null,
                    'purchase_invoice_id' => $invoice['id'] ? $invoice['id'] : null,
                    'product_id' => $invoice_detail['product_id'] ? $invoice_detail['product_id'] : ($product ? $product->id : null),
                    // 'sell_price' => $invoice_detail['sell_price'],
                    'quantity' => $invoice_detail['quantity'],
                    // 'margin' => $invoice_detail['margin'],
                    'total_amount' => $invoice_detail['total_amount'],
                    'unit_of_measure' => isset($invoice_detail['unit_of_measure']) ? $invoice_detail['unit_of_measure'] : null,
                    'description' => $invoice_detail['description'] ? $invoice_detail['description'] : $invoice_detail['product'],
                    'arabic_description' =>  $arDescription,
                    // 'invoice_id' => $request['id'],
                    'purchase_price' => $invoice_detail['purchase_price'] ? $invoice_detail['purchase_price'] : null,




                ]);
            }


            $i++;
        }
    }
    public function PurchaseInvoiceCreate(Request $request, Invoice $invoice)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $apikey =  \Config::get('example.key');
        $invoice = PurchaseInvoice::where('id', $request->id)->first();

        $invoice = PurchaseInvoice::create([
            // 'invoice_no' => $request->invoice_no,
            'invoice_no' => $request->invoice_no,
            'issue_date' => $request->ps_date,
            // 'status' => $request->status,
            // 'quotation_id' => $request->quotation_id,
            'total_value' => $request->total_value,
            'discount_in_percentage' => $request->discount_in_p,
            'vat_in_value' => $request->vat_in_value,
            'grand_total' => $request->grand_total,
            // 'delivery_no' => null,
            'party_id' => $request->party_id,
            'currency_type' => $request->currency_type,
            'div_id' => $request->div_id ? $request->div_id : 0,  // ? $request->ps_date : Carbon::now()
            'user_id' => $request->user_id ? $request->user_id : 0,
            // 'contact_id' => $request->contact_id
        ]);
        $temp = json_decode($request['invoice_details'], true);
        $i = 0;
        foreach ((array) $temp as $invoice_detail) {



            $invoiceDetail = PurchaseInvoiceDetail::where('id', $invoice_detail['id'])->first();
            if ($invoiceDetail) {

                if (!$invoice_detail['product_id']) {
                    $product_exist = Product::where('name', '=', $invoice_detail['product'])->exists();
                    if (!$product_exist) {
                        $product = Product::create([
                            'name' => $invoice_detail['product'],
                            'div_id' => $request['div_id'] ? $request['div_id'] : 0,  // ? $request['ps_date'] : Carbon::now()
                            'user_id' => $request['user_id'] ? $request['user_id'] : 0,
                            'type' => 'Non inventory',
                        ]);
                    } else {
                        $product = null;
                    }
                }
                $arDescription = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key=' . $apikey . '&q=' . urlencode($invoice_detail['description']) . '&target=ar'));
                $invoiceDetail->create([
                    // 'quotation_detail_id' => $invoice_detail['id']?$invoice_detail['id']:null,
                    'product_id' => $invoice_detail['product_id'] ? $invoice_detail['product_id'] : ($product ? $product->id : null),
                    // 'sell_price' => $invoice_detail['sell_price'],
                    'quantity' => $invoice_detail['quantity'],
                    'total_amount' => $invoice_detail['total_amount'],
                    'unit_of_measure' => isset($invoice_detail['unit_of_measure']) ? $invoice_detail['unit_of_measure'] : null,
                    // 'margin' => $invoice_detail['margin'],
                    'description' => $invoice_detail['description'] ? $invoice_detail['description'] : $invoice_detail['product'],
                    'arabic_description' => $invoice_detail['arabic_description'] ? $invoice_detail['arabic_description'] : $arDescription->data->translations[0]->translatedText,
                    // 'invoice_id' => $_invoice_id,
                    'purchase_price' => $invoice_detail['purchase_price'] ? $invoice_detail['purchase_price'] : null,


                ]);
            } else {


                if (!$invoice_detail['product_id']) {
                    $product_exist = Product::where('name', '=', $invoice_detail['product'])->first();
                    if (!$product_exist) {
                        $product = Product::create([
                            'name' => $invoice_detail['product'],
                            'div_id' => $request['div_id'] ? $request['div_id'] : 0,  // ? $request['ps_date'] : Carbon::now()
                            'user_id' => $request['user_id'] ? $request['user_id'] : 0,
                            'type' => 'Non inventory',
                        ]);
                    } else {
                        $product = null;
                    }
                }
                $purinvv = new GoogleTranslate('en');
                if($invoice_detail['description']){
                    
                    $arDescription =  $purinvv->setSource('en')->setTarget('ar')->translate($invoice_detail['description']);
    
                }else{
                    $arDescription = null;
    
                }
                
                PurchaseInvoiceDetail::create([

                    'quotation_detail_id' => $invoice['quotation_id'] ? $invoice['quotation_id'] : null,
                    'purchase_invoice_id' => $invoice['id'] ? $invoice['id'] : null,
                    'product_id' => $invoice_detail['product_id'] ? $invoice_detail['product_id'] : (isset($product) ? $product->id : null),
                    // 'sell_price' => $invoice_detail['sell_price'],
                    'quantity' => $invoice_detail['quantity'],
                    // 'margin' => $invoice_detail['margin'],
                    'total_amount' => $invoice_detail['total_amount'],
                    'unit_of_measure' => isset($invoice_detail['unit_of_measure']) ? $invoice_detail['unit_of_measure'] : null,
                    'description' => $invoice_detail['description'] ? $invoice_detail['description'] : $invoice_detail['product'],
                    'arabic_description' => $arDescription,
                    // 'invoice_id' => $request['id'],
                    'purchase_price' => $invoice_detail['purchase_price'] ? $invoice_detail['purchase_price'] : null,




                ]);
            }


            $i++;
        }
    }
}

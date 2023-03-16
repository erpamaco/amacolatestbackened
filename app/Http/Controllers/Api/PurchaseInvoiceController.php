<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\Party;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{


    public function mjrPurchaseEdit($did,$id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json([
            'vendor' => PartyController::vendor($did),
            'products' => ProductController::index(),
            'uom' => UOMController::uom(),
            'inv' => $this -> shows($id) ,
            'productPrice' => ProductPriceController::productPrice(),
        ]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCurrentYear()
    {
        return substr(date('Y'), 2);
    }

    public function getLastInvoiceNo()
    {
        $invoice = PurchaseInvoice::latest('created_at')->first();
        if ($invoice) {
            $latest_invoice_no = $invoice->invoice_no ? $invoice->invoice_no : 0;
            return ($latest_invoice_no);
        } else {
            return ('AMINV-' . $this->getCurrentYear() . '-' . sprintf("%04d", 0));
        }
    }

    public function getInvoiceNo()
    {
        $latest_invoice_no = $this->getLastInvoiceNo();
        $last_year = substr($latest_invoice_no, 6, 2);
        $current_year = $this->getCurrentYear();
        // dd([$last_year, $current_year]);
        if ($current_year != $last_year) {
            return ('AMINV-' . $current_year . '-' . sprintf("%04d", 1));
        } else {
            return ('AMINV-' . $current_year . '-' . sprintf("%04d", ((int)substr($this->getLastInvoiceNo(), 9)) + 1));
        }
    }
    public function index()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        // $invoices = PurchaseInvoice::where('status','!=','Delivered')
        // ->orderBy('created_at','DESC')->get();
        // return $invoices;
         $invoices = PurchaseInvoice::
        orderBy('created_at','DESC')->get();
        // $result=$invoices->party;
        $invoices->map(function ($invoice) {
               
            // $invoice->payment_account;
           return $invoice->party;
       });
        return $invoices;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        

        
        $fpath = NULL;
        if($request->hasFile('file')){
            $request->file('file');
            $fname =  $request->file('file','name');
            $extension = $request->file->getClientOriginalExtension();
            $fpath = $request->file('file')->move('uploadedFiles/',$fname.'.'.$extension);
        }

        $data = $request->all();

        $data['issue_date'] = $request['issue_date'];
        $data['status'] = "New";
        $data['quotation_id'] = $request['quotation_id'];
        $data['total_value'] = $request['total_value'];
        $data['discount_in_percentage'] = $request['discount_in_percentage'];
        $data['vat_in_value'] = $request['vat_in_value'];
        $data['grand_total'] = $request['grand_total'];
        $data['bill_no'] = $request['bill_no'];
        $data['party_id'] = $request['party_id'];
        $data['po_number'] = $request['po_number'];
        $data['ps_date'] = $request['ps_date'];
        $data['div_id'] = $request['div_id'];
        $data['invoice_no'] = $request['invoice_no'];
        $data['user_id'] = $request['user_id'];
        $invoice = PurchaseInvoice::create([
            'po_number' => $data['po_number'],
            'invoice_no' => isset($request['invoice_no'])?$data['invoice_no']:null,
            'issue_date' => $data['ps_date'],
            'div_id' => $data['div_id'],
            'user_id' => $data['user_id'],
            'file' => $fpath,
            'status' => $data['status'],
            'party_id' => $data['party_id'],
            'quotation_id' => $data['quotation_id'],
            'total_value' => $data['total_value'],
            'discount_in_percentage' => $data['discount_in_percentage'],
            'vat_in_value' => $data['vat_in_value'],
            'grand_total' => $data['grand_total'],
            'bill_no' => $data['bill_no'],
            'currency_type' => $data['currency_type'],
        ]);

        //   return response()->json(gettype($a));
        global $_invoice_id;
        $_invoice_id = $invoice['id'];
        $index = 0;
         while ($request['invoice_details' . $index] != null) {
                    $invoice_detail = (array) json_decode($request['invoice_details' . $index], true);
                    if(!$invoice_detail['product_id'])
                    {
                    $product_exist=Product::where('name','=',$invoice_detail['product'])->exists();
                        if(!$product_exist){
                       $product=Product::create([
                        'name'=> $invoice_detail['product'],
                        'div_id' => $request['div_id']?$request['div_id']:0,  // ? $request['ps_date'] : Carbon::now()
                        'user_id' => $request['user_id']?$request['user_id']:0,
                        'type' => 'Non inventory',
                        ]);
                        }
                        else
                        {
                            $product=null;
                        }  
                    }
                    $_invoice_detail = PurchaseInvoiceDetail::create([
                'quotation_detail_id' => $invoice_detail['id'],
                'product_id' => $invoice_detail['productId']?$invoice_detail['productId']:($product?$product->id:0),
                'purchase_price' => $invoice_detail['purchase_price'],
                'quantity' => $invoice_detail['quantity'],
                
                'unit_of_measure' => isset($invoice_detail['unit_of_measure']) ? $invoice_detail['unit_of_measure'] : null,
                'description' => $invoice_detail['description']?$invoice_detail['description']:$invoice_detail['product'],
                // 'arabic_description' => $invoice_detail['arabic_description']?$invoice_detail['arabic_description']:$arDescription->data->translations[0]->translatedText,
                'total_amount' => $invoice_detail['total_amount'],
                'purchase_invoice_id' => $_invoice_id,
            ]);
                    $index++;
                }
      
              
                  return response()->json('success');



        // return 'success';
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function shows($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $d = PurchaseInvoice::where('id',$id)->get();
        return [
            $purchaseInvoice = $d[0],
            $purchaseInvoice->party,
            // $purchaseInvoice->quotation->quotationDetail,
            $purchaseInvoice->purchaseInvoiceDetail->map(function ($purchaseInvoice_detail){
                return [
                    $purchaseInvoice_detail->quotationDetail,
                    $purchaseInvoice_detail->product,
                ];
            }),
        ];
    }
    public function show(PurchaseInvoice $purchaseInvoice)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return [
            $purchaseInvoice,
            $purchaseInvoice->party,
            // $purchaseInvoice->quotation->quotationDetail,
            $purchaseInvoice->purchaseInvoiceDetail->map(function ($purchaseInvoice_detail){
                return [
                    $purchaseInvoice_detail->quotationDetail,
                    $purchaseInvoice_detail->product,
                ];
            }),
            $purchaseInvoice->party->bank,

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
        $invoice = PurchaseInvoice::latest('created_at')->first();
        if ($invoice) {
            $latest_bill_no = $invoice->bill_no ? $invoice->bill_no : 0;
            return ($latest_bill_no);
        } else {
            return ('AMDLV-' . $this->getCurrentDeliveryYear() . '-' . sprintf("%04d", 0));
        }
    }

    public function getDeliveryNo()
    {
        $latest_bill_no = $this->getLastDeliveryNo();
        $last_year = substr($latest_bill_no, 6, 2);
        $current_year = $this->getCurrentDeliveryYear();
        // dd([$last_year, $current_year]);
        if ($current_year != $last_year) {
            return ('AMDLV-' . $current_year . '-' . sprintf("%04d", 1));
        } else {
            return ('AMDLV-' . $current_year . '-' . sprintf("%04d", ((int)substr($this->getLastDeliveryNo(), 9)) + 1));
        }
    }

    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $data = $request->all();
        // $data['status'] = 'Delivered';
        // $data['bill_no'] = $this->getDeliveryNo();
        $purchaseInvoice->update($data);
        return $purchaseInvoice;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $purchaseInvoice
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        // $purchaseInvoice->delete();
        $data=PurchaseInvoice::where('id',$id)->update([
            'delete_status' => 1
        ]);
        // PurchaseInvoiceDetail::where('purchase_invoice_id',$id)->delete();
        return ($id);
        // return ($purchaseInvoice->delete());
    }

    public function pInvRec($id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        

        $data=PurchaseInvoice::where('id',$id)->update([
            'delete_status' => 0
        ]);
        return ($id);
    }
    public function deletePurInv($id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $data=PurchaseInvoice::where('id',$id)->delete();
        PurchaseInvoiceDetail::where('purchase_invoice_id',$id)->delete();
        return ($id);
    }

    public function deleteInv($id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
                PurchaseInvoiceDetail::where('id',$id)->delete();
        return ($id);

    }

    // public function history()
    // {
    //     $invoices = PurchaseInvoice::where('status', '=', 'Delivered')
    //     ->orderBy('created_at', 'DESC')->get();
    //     return response()->json($invoices);
    // }

    public function purchaseInvoiceList()
    {
        
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = Quotation::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('purchase_invoices')
                ->whereRaw('purchase_invoices.quotation_id = quotations.id');
        })
        ->where("transaction_type",'purchase')
        ->orderBy('created_at', 'DESC')
        ->get();

        $quotations_data =
            $quotations->map(
                function ($quotation) {
                    $data = [
                        'id' => $quotation->id,
                        'div_id' => $quotation->div_id,
                        'user_id' => $quotation->user_id,
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
                        'delete' => $quotation->delete,
                        'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                            $quotation_detail = QuotationDetail::where('id', '=', $quotation_detail->id)->first();
                            return [
                                "id" => $quotation_detail['id'],
                                "created_at" => $quotation_detail->created_at,
                                "updated_at" => $quotation_detail->updated_at,
                                "product_id" => $quotation_detail->product_id,
                                "product" => array($quotation_detail->product),
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
                    return $data;
                }
            );

        return response()->json($quotations_data, 200);
    }
    public function purchaseInvoiceHList()
    {
        
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $quotations = Quotation::whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('purchase_invoices')
                ->whereRaw('purchase_invoices.quotation_id = quotations.id');
        })
        ->where("transaction_type",'purchase')
        ->orderBy('created_at', 'DESC')
        ->get();

        $quotations_data =
            $quotations->map(
                function ($quotation) {
                    $data = [
                        'id' => $quotation->id,
                        'div_id' => $quotation->div_id,
                        'user_id' => $quotation->user_id,
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
                        'delete' => $quotation->delete,
                        'quotation_details' => $quotation->quotationDetail->map(function ($quotation_detail) {
                            $quotation_detail = QuotationDetail::where('id', '=', $quotation_detail->id)->first();
                            return [
                                "id" => $quotation_detail['id'],
                                "created_at" => $quotation_detail->created_at,
                                "updated_at" => $quotation_detail->updated_at,
                                "product_id" => $quotation_detail->product_id,
                                "product" => array($quotation_detail->product),
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
                    return $data;
                }
            );

        return response()->json($quotations_data, 200);
    }
    public function PurchaseInvoice()
    {
        // $data = purchase_invoices::create([
        //     'total_value' => $request['total_value'],
        //     'discount_in_percentage' => $request['discount_in_p'],
        //     'vat_in_value' => $request['vat_in_value'],
        //     'grand_total' => $request['net_amount'],
        // ]);
        return response()->json(null);
    }
}




<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PurchaseReturnDetail;
use App\Models\QuotationDetail;
use App\Models\InvoiceDetail;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\PurchaseInvoiceDetail;
use App\Models\PurchaseInvoice;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        

        // $category=Category::join('products','products.category_id','categories.id')->Select('categories.*')->get();
     
        $category=Category::where('parent_id',null)->orderBy('name','asc')->get();
       
        $data=$category->map(function($category){
            
         return
         [  
                  
                    $data=$category->product_category->map(function($category){
                        
                    $category->product->map(function($arr){
                    // if($arr->type=="Inventory")
                    // {
                    $arr["purchase"] =  $this->purchase($arr->id);
                    $arr["purchaseQuantity"] =  $this->purchaseQuantity($arr->id);
                    $arr["sales"] = $this -> sale($arr->id);
                    $arr["salesQuantity"] = $this -> salesQuantity($arr->id);
                    $arr["purchaseReturn"] = $this -> purchaseReturn($arr->id);
                    $arr["purchaseReturnQuantity"] = $this -> purchaseReturnQuantity($arr->id);
                    $arr["salesReturn"] = $this -> salesReturn($arr->id);
                    $arr["salesReturnQuantity"] = $this -> salesReturnQuantity($arr->id);
                    $arr["latestPrice"] = $this -> latestPrice($arr->id);
                    // }
                });
            })];
        });

        $other=Product::where('type','Inventory')->where('category_id',null)->get();
        $other -> map(function ($item){
                    $item["purchase"] =  $this->purchase($item->id);
                    $item["purchaseQuantity"] =  $this->purchaseQuantity($item->id);
                    $item["sales"] = $this -> sale($item->id);
                    $item["salesQuantity"] = $this -> salesQuantity($item->id);
                    $item["purchaseReturn"] = $this -> purchaseReturn($item->id);
                    $item["purchaseReturnQuantity"] = $this -> purchaseReturnQuantity($item->id);
                    $item["salesReturn"] = $this -> salesReturn($item->id);
                    $item["salesReturnQuantity"] = $this -> salesReturnQuantity($item->id);
                    $item["latestPrice"] = $this -> latestPrice($item->id); 
               });
         return response()->json([
            'status' => 200,
            'category' => $category,
            'other' => $other
        ]);
       
        
    }

    public function latestPrice($id){
        $data = PurchaseInvoiceDetail::where('product_id',$id)->orderBy('id','DESC')->limit(1)->get('purchase_price');
        return $data->count() > 0 ? $data : 0;
    }
    public function purchase($id){
        $data = PurchaseInvoiceDetail::where('product_id',$id)->get();
        return $data;
    }
    public function purchaseQuantity($id){
        $data = PurchaseInvoiceDetail::where('product_id',$id)->sum('quantity');
        return $data;
    }
    public function sale($id){
        $data = InvoiceDetail::where('product_id',$id)->get();
        return $data;
    }
    public function salesQuantity($id){
        $data = InvoiceDetail::where('product_id',$id)->sum('quantity');
        return $data;
    }

    public function purchaseReturn($id){
        $data = PurchaseReturnDetail::where('product_id',$id)->whereNotNull('po_number')->get();
        return $data;
    }
    public function purchaseReturnQuantity($id){
        $data = PurchaseReturnDetail::where('product_id',$id)->whereNotNull('po_number')->sum('quantity');
        return $data;
    }   
    public function salesReturn($id){
        $data = PurchaseReturnDetail::where('product_id',$id)->whereNull('po_number')->get();
        return $data;
    }
    public function salesReturnQuantity($id){
        $data = PurchaseReturnDetail::where('product_id',$id)->whereNull('po_number')->sum('quantity');
        return $data;
    }

    public function check($id){
        $data = QuotationDetail::where('product_id',$id)->get();
        $q=0;
        foreach ($data as $key => $value) {
            $q = $value->quantity;
        }
        $data['purchasequantity'] = $q;
        return $data;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

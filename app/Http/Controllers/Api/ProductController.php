<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\Manufacturer;
use Illuminate\Support\Facades\DB;
// use Stichoza\GoogleTranslate\GoogleTranslate;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\UOMController;

class ProductController extends Controller
{

    public static function index()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
    //    return $products = Product::get();
        // return ($products);
        $products = DB::table('products')
            ->leftJoin('categories','categories.id','=','products.category_id')
            ->leftJoin('divisions','divisions.id','=','products.div_id')
            ->select('products.*','categories.name as category_name', 'divisions.name as division_name')->where('products.delete',0)
            ->orderBy('products.name')
            ->get();
        return $products;
    }
//
    public function store(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $rules = [
            'division_id' => 'required',
            'name' => 'required|max:255',
            // 'description' => 'required|max:500',
            // 'unit_of_measure' => 'required',
            // 'unit_price' => 'required',
            // 'type' => 'required',
            // 'hsn_code' => 'required',
            // 'initial_quantity' => 'required',
            // 'minimum_quantity' => 'required',
        ];

        $validatedData = $request->validate($rules);

        // if($validatedData->fails()){
        //     $returnData = array(
        //         'status'=>'error',
        //         'message'=>'Please review fields',
        //         'error'=>$validatedData->errors()->all()
        //     );
        //     return response()->json($returnData, 500);
        // }
            // dd($request->all());
            $index = 0;
        // while ($request['myFile' . $index] != null) {
        //     if ($request->file('myFile' . $index)) {
        //         $name = $request['myFile' . $index]->getClientOriginalName();
        //         $path = $request->file('myFile' . $index)->move('rfq/' . $rfq->id, $name);
        //         FileUpload::create([
        //             'rfq_id' => $rfq->id,
        //             'file_name' => $path
        //         ]);
        //     }
        //     $index++;
        // }
        // $filePath = null;
        // if ($request->file('file' . $index)) {
        //     $filePath = $request->file('file' . $index)->move('quotation/quotation_detail/');
        // }

        // $fpath = NULL;
        // if($request->hasFile('file')){
        //     $request->file('file');
        //     $fname =  $request->file('file','name');
        //     $fpath = $request->file('file')->move('uploadedFiles/',$fname.'.'.$request->file('file')->getClientOriginalExtension());
        // }

        $filePath = null;
        if ($request->file('file')) {
            $filePath = $request->file('file')->move("products/filePath",  $request->file('file')->getClientOriginalName());
        }
        
        $product = new Product;
        $product->category_id = $request->category_id;
        $product->mcat_id = null;
        $product->division_id = $request->division_id;
        // $product->party_id = $request->party_id;
        $product->name = $request->name?ucwords(trans($request->name)):'';
        $product->name_in_ar = $request->name_in_ar;
        $product->description = $request->description?ucwords(trans($request->description)):"";
        $product->unit_of_measure = $request->unit_of_measure;
        // $product->unit_price = $request->unit_price;
        $product->type = $request->type;
        $product->brand_name = $request->bname?ucwords(trans($request->bname)):'';
        $product->ecapacity = $request->ecapacity;
        $product->hsn_code = $request->hsn_code;
        $product->initial_quantity = $request->initial_quantity;
        $product->manufacturer_id = $request->manufacturer_id;
        $product->model_no = $request->model_no;
        // $product->file = $filePath ? $filePath : NULL;
        $product->file = $filePath;

        $product->minimum_quantity = $request->minimum_quantity;
        $product->div_id = $request->div_id?$request->div_id:0;
        $product->user_id = $request->user_id?$request->user_id:0;
        $product->save();
        return($product);
//

    }


    public function main_products(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $rules = [
            'division_id' => 'required',
            'name' => 'required|max:255',
            // 'description' => 'required|max:500',
            // 'unit_of_measure' => 'required',
            // 'unit_price' => 'required',
            // 'type' => 'required',
            // 'hsn_code' => 'required',
            // 'initial_quantity' => 'required',
            // 'minimum_quantity' => 'required',
        ];

        $validatedData = $request->validate($rules);

        // if($validatedData->fails()){
        //     $returnData = array(
        //         'status'=>'error',
        //         'message'=>'Please review fields',
        //         'error'=>$validatedData->errors()->all()
        //     );
        //     return response()->json($returnData, 500);
        // }
            // dd($request->all());
            $index = 0;
        while ($request['myFile' . $index] != null) {
            if ($request->file('myFile' . $index)) {
                $name = $request['myFile' . $index]->getClientOriginalName();
                $path = $request->file('myFile' . $index)->move('rfq/' . $rfq->id, $name);
                FileUpload::create([
                    'rfq_id' => $rfq->id,
                    'file_name' => $path
                ]);
            }
            $index++;
        }
        $filePath = null;
        if ($request->file('file' . $index)) {
            $filePath = $request->file('file' . $index)->move('quotation/quotation_detail/');
        }

        $fpath = NULL;
        if($request->hasFile('file')){
            $request->file('file');
            $fname =  $request->file('file','name');
            $fpath = $request->file('file')->move('uploadedFiles/',$fname.'.'.$request->file('file')->getClientOriginalExtension());
        }
        
        $product = new Product;
        $product->category_id = null;
        $product->mcat_id = $request->category_id ? $request->category_id :null;
        $product->division_id = $request->division_id;
        // $product->party_id = $request->party_id;
        $product->name = $request->name?ucwords(trans($request->name)):'';
        $product->name_in_ar = $request->name_in_ar;
        $product->description = $request->description?ucwords(trans($request->description)):"";
        $product->unit_of_measure = $request->unit_of_measure;
        // $product->unit_price = $request->unit_price;
        $product->type = $request->type;
        $product->hsn_code = $request->hsn_code;
        $product->initial_quantity = $request->initial_quantity;
        $product->manufacturer_id = $request->manufacturer_id;
        $product->model_no = $request->model_no;
        // $product->file = $filePath ? $filePath : NULL;
        $product->file = $fpath ? $fpath : "hello";

        $product->minimum_quantity = $request->minimum_quantity;
        $product->div_id = $request->div_id?$request->div_id:0;
        $product->user_id = $request->user_id?$request->user_id:0;
        $product->save();
        return($product);
//

    }

//
    public function show($product)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $productPrice = Product::where('id','=',$product)->first();
        $prices = $productPrice->productPrice->map(function ($productdetail){
                return [
                    'id'=> $productdetail->id,
                    'party_id'=> $productdetail->party_id,
                    'product_id'=> $productdetail->product_id,
                    'price'=> $productdetail->price,
                    'firm_name' => $productdetail->party->firm_name,
                ];
            });
        $product = DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('divisions', 'divisions.id', '=', 'products.division_id')
            ->leftJoin('manufacturers','manufacturers.id', '=', 'products.manufacturer_id')
            ->select('products.*', 'categories.name as category_name', 'divisions.name as division_name', 'manufacturers.name as manufacturer_name')
            ->where('products.id','=',$product)
            ->get();

        $data = ['product' => $product,'prices' => $prices];
        return response()->json($data);

    }

//
    public function update(Request $request, $id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];

        $product=Product::where("id",$request->id)->first();
        
        // $rules = [
        //     'category_id' => 'required',
        //     'division_id' => 'required',
        //     'name' => 'required|max:255',
        //     'description' => 'required|max:500',
        //     'unit_of_measure' => 'required',
        //     'unit_price' => 'required',
        //     'mrp' => 'required',
        //     'real_price' => 'required',
        // ];
        // $validatedData = $request->validate($rules);

        // $product = Product::findOrfail($id);
        // $product = Product::findOrfail($id);
        // $filePath = null;
        // if ($request->file('file')) {
        //     $filePath = $request->file('file')->move("products/filePath",  $request->file('file')->getClientOriginalName());
        // }
        // $product->update($request->all());
        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'unit_of_measure' => $request->unit_of_measure,
            'division_id' => $request->division_id,
            'type' => $request->type,
            'hsn_code' => $request->hsn_code,
            'initial_quantity' => $request->initial_quantity,
            'minimum_quantity' => $request->minimum_quantity,
            'category_id' => $request->category_id,
            'model_no' => $request->model_no,
            'brand_name' => $request->brand_name,
            'name_in_ar' => $request->name_in_ar,
            'ecapacity' => $request->ecapacity,
            'manufacturer_id' => $request->manufacturer_id,
            'div_id' => $request->div_id?$request->div_id:0,
            'user_id' => $request->user_id?$request->user_id:0,
            
        ]);
        $path=null;
        if($request->file('file')){
                $path = $request->file('file')->move("products/filePath",  $request->file('file')->getClientOriginalName());
                $product->update([
                    // 'family_address_id' => $familyAddress->id,
                    'file' => $path,
                ]);
    
            
            
            }
            return response()->json($product, 200);
        // return ($product);
        // return response()->json($product);
    }
    //
    public function destroy($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $product=Product::findOrfail($id);
        $res = $product->update(['delete'=>1]);
        // $res = $product->delete();
        if($res){
            return (['msg'=>$product->name.' is successfully deleted']);
        }
    }
    

    //show the Single product Info
    public function productShow($product)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $productPrice = Product::where('id','=',$product)->first();
        $prices = $productPrice->productPrice->map(function ($productdetail){
                return [
                    'id'=> $productdetail->id,
                    'party_id'=> $productdetail->party_id,
                    'product_id'=> $productdetail->product_id,
                    'price'=> $productdetail->price,
                    'firm_name' => $productdetail->party->firm_name,
                ];
            });
        $product = DB::table('products')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('divisions', 'divisions.id', '=', 'products.division_id')
            ->leftJoin('manufacturers','manufacturers.id', '=', 'products.manufacturer_id')
            ->select('products.*', 'categories.name as category_name', 'divisions.name as division_name', 'manufacturers.name as manufacturer_name')
            ->where('products.id','=',$product)
            ->get();

        $data = ['product' => $product,'prices' => $prices];
        return $data;

    }


    public function mjrProductAdd($did,$cid){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json([
            'vendor' => PartyController::vendor($did),
            'product_in_category' => CategoryController::products_in_category2(),
            'manufacture' => Manufacturer::get(),
            'category'=>Category::where('id',$cid)->get(),
            // 'banks' => CompanyBankController::banks(),
            // 'products' => ProductController::index(),
            // 'sales' => $this -> shows($id),
            // 'uom' => UOMController::uom(),
        ]);
    }

    //json response to get single product info,price 
    public function mjrProductUpdate($pid){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json([
            // 'vendor' => PartyController::vendor($did),
            'product' => $this->productShow($pid)['product'],
            'price' => $this->productShow($pid)['prices'],
            'product_in_category' => CategoryController::products_in_category2(),
            'manufacture' => Manufacturer::get(),
            'getAllCat'=>Category::get(),
            'uom'=>UOMController::uom(),
           
        ]);
    }
}

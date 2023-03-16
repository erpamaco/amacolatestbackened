<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Product;
use App\Models\party_division;
use App\Models\Contact;
use App\Models\PartyBank;
use Illuminate\Support\Facades\Auth;

use App\Models\Division;
use App\Models\ProductPrice;
use App\Models\PaymentAccount;


use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceDetail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Config;
use Illuminate\Validation\Rules\Exists;
use Stichoza\GoogleTranslate\GoogleTranslate;

// use Stichoza\GoogleTranslate\GoogleTranslate;

class PartyController extends Controller
{
    

    public function validationParty(){

        $party = Party::get();

        return response()->json($party);

    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    

     public function checkVerifyParty($id){

        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $parties = Party::where('id',$id)->get();
        return response()->json($parties[0]->status, 200);

     }

     public function verifyParty($id){

        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $parties = Party::where('id',$id)->update([
            'status' => 1
        ]);
        return response()->json('1', 200);

     }


    public function index()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $parties = Party::where('delete',0)->get();
        return response()->json($parties, 200);
    }


    public function getParties($id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $vendors = Party::join('party_divisions','party_divisions.party_id','parties.id')
        ->join('payment_accounts','payment_accounts.id','party_divisions.div_id')
        ->where('payment_accounts.div_id',$id)
        ->where('parties.delete',0)
        ->orderBy('parties.firm_name','ASC')
        ->select('parties.id', 'parties.firm_name','parties.party_type','parties.contact','parties.vat_no','parties.opening_balance','parties.credit_days','payment_accounts.div_id')
        ->get();
       
        $vendors->map(function($payment){
            return $payment->partyDivision;
        });

           return response()->json($vendors, 200);
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


        // if(isset($request->vat_no) || isset($request->registration_no)){
        //     $data = Party::where('vat_no', $request->vat_no)->where('registration_no',$request->registration_no)->exists();
        //     if($data){
        //         return response()->json([$data, 200]);
        //     }
            
        // }

      
       


        if($request->firm_name){

        $firm_arr = new GoogleTranslate('en');
        $firm_name_in_ar = $firm_arr->setSource('en')->setTarget('ar')->translate($request->firm_name);
        }
        else{

            $firm_name_in_ar = null;

        }
        // ----

        if($request->city){

            $cityy_arr = new GoogleTranslate('en');
            $cityar = $cityy_arr->setSource('en')->setTarget('ar')->translate($request->city);
    
        }
        else{
            $cityar = null;
        }

       
        // ---

        if($request->street){
        $streett_ar = new GoogleTranslate('en');
        $streetar = $streett_ar->setSource('en')->setTarget('ar')->translate($request->street);


        }
        else {
            $streetar = null;

        }

        
        // --

        if($request->country){
            $countryy_ar = new GoogleTranslate('en');
            $countryar = $countryy_ar->setSource('en')->setTarget('ar')->translate($request->country);
    

        }else{
            $countryar = null;

        }
       
        // ---

        if($request->proviance){
        $proviancee_ar = new GoogleTranslate('en');
        $proviancear = $proviancee_ar->setSource('en')->setTarget('ar')->translate($request->proviance);



        }else{
            $proviancear = null;


        }


        

        // --

        if($request->buildNumber){

        $buildingl_no_arr = new GoogleTranslate('en');
        $building_no_arr = $buildingl_no_arr->setSource('en')->setTarget('ar')->translate($request->buildNumber);



        }else{
            $building_no_arr = null;


        }

        







        
        // return $request;
        // $path = storage_path() . "/json/jsondata.json";
        $apikey=  \Config::get('example.key');
        // $json = json_decode(file_get_contents($path), true);
        // $cityar = json_decode(file_get_contents('https://api.cognitive.microsofttranslator.com/translate/v2?key='.$apikey.'&q='.urlencode($request->city).'&target=ar'));
        // $streetar = json_decode(file_get_contents('https://api.cognitive.microsofttranslator.com/translate/v2?key='.$apikey.'&q='.urlencode($request->street).'&target=ar'));
        // $countryar = json_decode(file_get_contents('https://api.cognitive.microsofttranslator.com/translate/v2?key='.$apikey.'&q='.urlencode($request->country).'&target=ar'));
        // $proviancear = json_decode(file_get_contents('https://api.cognitive.microsofttranslator.com/translate/v2?key='.$apikey.'&q='.urlencode($request->proviance).'&target=ar'));
        // $firm_name_in_ar = json_decode(file_get_contents('https://api.cognitive.microsofttranslator.com/translate/v2?key='.$apikey.'&q='.urlencode($request->firm_name).'&target=ar'));
        // $building_no_arr = json_decode(file_get_contents('https://api.cognitive.microsofttranslator.com/translate/v2?key='.$apikey.'&q='.urlencode($request->buildNumber).'&target=ar'));
        
        
       

        $party = Party::create([
            'firm_name' => $request->firm_name?ucwords(trans($request->firm_name)):'',
            'firm_name_in_ar' => $firm_name_in_ar,
            'registration_no' => $request->registration_no,
            'status' => Auth::user()->role->name == 'SA' ? 1 : 0,

            'vat_no' => $request->vat_no,
            'payment_term' => isset($request->payment_term) ? $request->payment_term : null ,
           
            'post_box_no' => $request->post_box_no,
            'building_no' => $request->buildNumber,
            'building_no_arr' =>  $building_no_arr,
            'street' => $request->street?ucwords(trans($request->street)):null,
            'street_ar' => $streetar,
            'city' => $request->city?ucwords(trans($request->city)):null,
            'proviance' => $request->proviance?ucwords(trans($request->proviance)):null,
            'country' =>$request->country?ucwords(trans($request->country)):null,
            'zip_code' => $request->zip_code,
            'party_type' => $request->party_type,
            'contact' => $request->contact,
            'website' => $request->website,
            'fax' => $request->fax,
            'opening_balance' => $request->opening_balance,
            'credit_days' => $request->credit_days,
            'credit_limit' => $request->credit_limit,
            'party_code' => $request->party_code,
            'vendor_id' => $request->vendor_id,
            'city_ar' => $cityar,
            'country_ar' => $countryar,
            'proviance_ar' =>$proviancear,
            'zip_code_ar' => $request->zip_code_ar,
            'vat_no_in_ar' => $request->vat_no_in_ar,
            'div_id' => $request->div_id?$request->div_id:1,
            'user_id' => $request->user_id?$request->user_id:0,
            'lext' => $request->lext?$request->lext:0,
            'mext' => $request->mext?$request->mext:0,
            'ext' => $request->ext?$request->ext:0,
            'code' => $request->code?$request->code:0,
            
        ]);
        $request->account_no &&
            PartyBank::create([
                'account_no' => $request->account_no,
                'iban_no' => $request->iban_no,
                'bank_name' => $request->bank_name?ucwords(trans($request->bank_name)):"",
                'bank_address' => $request->bank_address?ucwords(trans($request->bank_address)):"",
                'party_id' => $party->id,
                'div_id' => $request->div_id?$request->div_id:1,
                'user_id' => $request->user_id?$request->user_id:0,
            ]);

        $contact = Contact::create([
            'prefix' => $request->prefix,
            'party_id' => $party->id,
            'fname' => $request->fname?ucwords(trans($request->fname)):"",
            'lname' => $request->lname?ucwords(trans($request->lname)):"",
            'designation' =>$request->designation?ucwords(trans($request->designation)):"",
            'mobno' => $request->mobno,
            'landline' => $request->landline,
            'email' => $request->email,
            'address'=>$request->address?ucwords(trans($request->address)):"",
            'div_id' => $request->div_id?$request->div_id:1,
            'user_id' => $request->user_id?$request->user_id:0,
            'lext' => $request->lext?$request->lext:0,
            'mext' => $request->mext?$request->mext:0,
            'mcode' => $request->mcode?$request->mcode:0,
            'lcode' => $request->lcode?$request->lcode:0,
        ]);
     
        if($request->party_type=="Both")
        {
            $type="CV";
        }
        else if($request->party_type=="Vendor")
        {
            $type="V";
        }
        else{
            $type="C";
        }


        if ($party->party_code == null) {
            $party->update(['party_code' => 'AMCT-PC-' . sprintf('%05d', $party->id)]);
        }
        if($request->division)
        {
        foreach ($request->division as $div) {
           
            $contact = party_division::create([
                'party_id' => $party->id,
                'div_id' => $div['id'],
               
                'vendor_code' => $div['vendor_code'].'-'.$type.sprintf('%05d', $party->id)
                
    
            ]); 
            }
        }

        if(Auth::user()->role->name == 'SA'){

        }else{
            $path = '/pages/view-customer/'.$party->id;
            $noti = 'Please Verify Party that created by '.Auth::user()->name;
            NotificationController::sendNotification('Party','alert','Party Has Been Added',$noti,'SA',$path);    
        }



        // $res=json_decode($cityar->data->translations);
        return response()->json([$party->id], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Party  $party
     * @return \Illuminate\Http\Response
     */
    public function show(Party $party)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        



        // $path = storage_path() . "/json/jsondata.json"; // ie: /var/www/laravel/app/storage/json/filename.json
        
        // $json = json_decode(file_get_contents($path), true);
        $json =  \Config::get('example.key');
        $contacts = Contact::orderBy('fname','ASC')->where('party_id', '=', $party->id)->where('delete', '=', 0)->get();
        $divisions=party_division::where('party_id',$party->id)->join('payment_accounts','payment_accounts.id','party_divisions.div_id')->get();
        $data =
            [
                'firm_name' => $party->firm_name,
                'firm_name_in_ar' => $party->firm_name_in_ar,
                'registration_no' => $party->registration_no,
                // 'registration_no_in_ar' => $party->registration_no_in_ar,
                'vat_no' => $party->vat_no,
                // 'vat_no_in_ar' => $party->vat_no_in_ar,
                'post_box_no' => $party->post_box_no,
                'street' => $party->street,
                'city' => $party->city,
                'proviance' => $party->proviance,
                'status' => $party->status,
                'country' => $party->country,
                'zip_code' => $party->zip_code,
                'party_type' => $party->party_type,
                'contact' => $party->contact,
                'website' => $party->website,
                'building_no_arr' => $party->building_no_arr,
                'building_no' => $party->building_no,
                'fax' => $party->fax,
                'opening_balance' => $party->opening_balance,
                'credit_days' => $party->credit_days,
                'credit_limit' => $party->credit_limit,
                'payment_term' => $party->payment_term,
                'party_code' => $party->party_code,
                'code' => $party->code,
                'ext' => $party->ext,
                'vendor_id' => $party->vendor_id,
                "bank" => $party->bank->where('delete',0)->map(function ($bankDetail) {
                    return $bankDetail;
                }),
                'contacts' => $contacts->map(function ($item) {
                    $item -> fname = strtoupper($item -> fname) .' '.strtoupper($item -> lname);
                    return $item;
                }),
                'partyDivision'=>$party->partyDivision->map(function($item){
                    $a = PaymentAccount::where('id',$item['div_id'])->get(['div_id']);
                    $item['division_id']= $a[0]->div_id;
                    return $item;
                }),
                $json,
            ];

        return response()->json(array($data));
           
        
    }  
    
    public function getPartyDet($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        // $path = storage_path() . "/json/jsondata.json"; // ie: /var/www/laravel/app/storage/json/filename.json

        // $json = json_decode(file_get_contents($path), true);
        $json =  \Config::get('example.key');
        $data = Party::where('id', '=', $id)->get();
        $cont = Contact::where('party_id', $id)->get();
        $data -> map(function ($item){
            $item['inv'] =  $this -> getInv($item -> id);
            return $item;
        });
              
        return response()->json([
            'data' => $data,
            'contacts' => $cont,
        ]);
    }

    public function getInv($id){
        $inv = PurchaseInvoice::where('party_id',$id)->get();
        $inv -> map(function ($item){
            $item['details'] = $this -> getInvDet($item -> id);
            return $item;
        });  
        return $inv;
    }

    public function getInvDet($id){
        $invd = PurchaseInvoiceDetail::where('purchase_invoice_id',$id)->get();
        return $invd;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Party  $party
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Party $party)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        // $rules = [
        //     'firm_name' => 'required',
        //     'registration_no' => 'required|max:12',
        //     'vat_no' => 'required|max:15',
        //     'post_box_no' => 'required',
        //     'street' => 'required',
        //     'city' => 'required',
        //     'proviance' => 'required',
        //     'country' => 'required',
        //     'zip_code' => 'required',
        //     'party_type' => 'required',
        //     'contact' => 'required',
        //     'fax' => 'required',
        //     'opening_balance' => 'required',
        //     'website' => 'required',
        // ];

        // $messages = ['required' => 'The :attribute field is required.'];

        // $validator = Validator::make($request->all(), $rules, $messages);
        // $errors = $validator->errors();
        // foreach ($errors as $error) {
        //     echo $error;
        // }
        // echo $errors->first('email');

        // if($validator->fails()){
        // return ("somethin went wrong");
        // $path = storage_path() . "/json/jsondata.json";

        // $json = json_decode(file_get_contents($path), true);
        $apikey=  \Config::get('example.key');
        // $cityar = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key='.$apikey.'&q='.urlencode($request->city).'&target=ar'));
        // $streetar = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key='.$apikey.'&q='.urlencode($request->street).'&target=ar'));
        // $countryar = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key='.$apikey.'&q='.urlencode($request->country).'&target=ar'));
        // $proviancear = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key='.$apikey.'&q='.urlencode($request->proviance).'&target=ar'));

       
            // ----

            if($request->firm_name){

                $firm_arr = new GoogleTranslate('en');
                $firm_name_in_ar = $firm_arr->setSource('en')->setTarget('ar')->translate($request->firm_name);
                }
                else{
        
                    $firm_name_in_ar = null;
        
                }
    
            if($request->city){
    
                $cityy_arr = new GoogleTranslate('en');
                $cityar = $cityy_arr->setSource('en')->setTarget('ar')->translate($request->city);
        
            }
            else{
                $cityar = null;
            }
    
           
            // ---
    
            if($request->street){
            $streett_ar = new GoogleTranslate('en');
            $streetar = $streett_ar->setSource('en')->setTarget('ar')->translate($request->street);
    
    
            }
            else {
                $streetar = null;
    
            }
    
            
            // --
    
            if($request->country){
                $countryy_ar = new GoogleTranslate('en');
                $countryar = $countryy_ar->setSource('en')->setTarget('ar')->translate($request->country);
        
    
            }else{
                $countryar = null;
    
            }
           
            // ---
    
            if($request->proviance){
            $proviancee_ar = new GoogleTranslate('en');
            $proviancear = $proviancee_ar->setSource('en')->setTarget('ar')->translate($request->proviance);
    
    
    
            }else{
                $proviancear = null;
    
    
            }
    
    
            
    
            // --
    
           
    

        $party->update([
            'firm_name' => $request->firm_name == null ? null: ucwords(trans($request->firm_name)),
            'firm_name_in_ar' => $firm_name_in_ar,
            'registration_no' => $request->registration_no == null ? null : $request->registration_no,
            // 'registration_no_in_ar'=> $request->registration_no == null ? $party->registration_no_in_ar : GoogleTranslate::trans($request->registration_no,'ar'),
            'vat_no' => $request->vat_no == null ? null : $request->vat_no,
            // 'vat_no_in_ar'=> $request->registration_no == null ? $party->vat_no_in_ar :   GoogleTranslate::trans($request->vat_no,'ar'),
            'post_box_no' => $request->post_box_no == null ? null : $request->post_box_no,
            'street' => $request->street == null ? null : ucwords(trans($request->street)),
            'city' => $request->city == null ? null : ucwords(trans($request->city)),
            'proviance' => $request->proviance == null ? null : ucwords(trans($request->proviance)),
            'country' => $request->country == null ? null : ucwords(trans($request->country)),
            'building_no_arr' => $request->buildNumber == null ? null : ucwords(trans($request->buildNumber)),
            'zip_code' => $request->zip_code == null ? null : $request->zip_code,
            'building_no' => $request->buildNumber == null ? null : $request->buildNumber,
            'party_type' => $request->party_type == null ? null : $request->party_type,
            'payment_term' => $request->payment_term == null ? null : $request->payment_term,
            'contact' => $request->contact == null ? null : $request->contact,
            // 'lext' => $request->lext == null ? null : $request->lext,
            // 'mext' => $request->mext == null ? null : $request->mext,
            'ext' => $request->ext == null ? null : $request->ext,
            'code' => $request->code == null ? null : $request->code,
            'website' => $request->website == null ? null : $request->website,
            'fax' => $request->fax == null ?null : $request->fax,
            'credit_days' => $request->credit_days == null ? null : $request->credit_days,
            'credit_limit' => $request->credit_limit == null ? null : $request->credit_limit,
            'opening_balance' => $request->opening_balance == null ? null : $request->opening_balance,
            'account_no' => $request->account_no == null ? null : $request->account_no,
            'iban_no' => $request->iban_no == null ? null :  $request->iban_no,
            'bank_name' => $request->bank_name == null ? null :  ucwords(trans($request->bank_name)),
            'bank_address' => $request->bank_address == null ? null :  ucwords(trans($request->bank_address)),
            'party_code' => $request->party_code == null ? null :  $request->party_code,
            'vendor_id' => $request->vendor_id == null ? null :  $request->vendor_id,
            'zip_code_ar' => $request->zip_code_ar,
            'vat_no_in_ar' => $request->vat_no_in_ar,
            'city_ar' => $cityar,
            'country_ar' => $countryar,
            'proviance_ar' => $proviancear,
            'zip_code_ar' =>isset($request->zip_code_ar)?$request->zip_code_ar:null,
            'street_ar' => $streetar,
            'div_id' => $request->div_id?$request->div_id:1,
            'user_id' => $request->user_id?$request->user_id:0,
        ]);
        if($request->party_type=="Both")
        {
            $type="CV";
        }
        else if($request->party_type=="Vendor")
        {
            $type="V";
        }
        else{
            $type="C";
        }
        if($request->division)
        {
        $res=party_division::where('party_id',$party->id)->delete();
       

        foreach ($request->division as $div) {
           
            $contact = party_division::create([
                'party_id' => $party->id,
                'div_id' => $div['id'],
               
                'vendor_code' => $div['vendor_code'].'-'.$type.sprintf('%05d', $party->id)
                
    
            ]); 
            }
        }
        else
        {
            $party_Div=party_division::where('party_id',$party->id)->get();
            foreach ($party_Div as $div) {
                $code=explode("-",$div->vendor_code);
                $div->update([
                        'vendor_code' => $code[0].'-'.$code[1].'-'.$type.sprintf('%05d', $party->id)
                    ]);
                

            }


            
        }

        return response()->json($party, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Party  $party
     * @return \Illuminate\Http\Response
     */
    public function destroy(Party $party)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $res = $party->update(['delete'=>1]);
        // $res = $party->delete();
        if ($res) {
            return (['msg' => 'party' . ' ' . $party->id . ' is successfully deleted']);
        }
    }

    // Api fo vendor list
    public static function vendor($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $vendors = Party::join('party_divisions','party_divisions.party_id','parties.id')
        ->join('payment_accounts','payment_accounts.id','party_divisions.div_id')
        ->where('payment_accounts.div_id',$id)
        ->where('parties.delete',0)
        ->where('parties.status',1)
        ->where('parties.party_type','!=','Customer')
        ->select('parties.id', 'parties.firm_name','parties.party_type','parties.contact','parties.opening_balance','parties.credit_days','payment_accounts.div_id')
        ->orderBy('parties.firm_name', 'ASC')
        ->get();
            // ->toArray();
            $vendors->map(function($payment){
                return $payment->partyDivision;
            });
            return  $vendors;
    }

    // Api for customer list
    public static function customer($id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $vendors = Party::join('party_divisions','party_divisions.party_id','parties.id')
        ->join('payment_accounts','payment_accounts.id','party_divisions.div_id')
        ->where('payment_accounts.div_id',$id)
        ->where('parties.delete',0)
        ->where('parties.status',1)
        ->where('parties.party_type','!=','Vendor')
        ->select('parties.id', 'parties.firm_name','parties.party_type','parties.contact','parties.opening_balance','parties.credit_days','payment_accounts.div_id')->orderBy('parties.firm_name', 'ASC')
        ->get();
            // $vendors = Party::where('party_type', '=', 'customer')->orWhere('party_type', '=', 'both')
            // ->select('id', 'firm_name', 'contact','opening_balance','credit_days')
            // ->get();
            // ->toArray();
       
        $vendors->map(function($payment){
            return $payment->partyDivision;
        });
   

        
        return  $vendors;
    }

    // public function allVendorExcept($product)
    // {
    //     // to get the all vendors excepts product assigned vendor
    //     $product_price = ProductPrice::where('product_id','=',$product)->first();
    //     // dd($product_price);
    //     if($product_price == null){
    //         $vendors = Party::where('party_type', '=', 'vendor')
    //         ->select('id', 'firm_name', 'contact')
    //         ->get()
    //         ->toArray();
    //         return $vendors;
    //     }
    //     else{
    //     $vendors = Party::where('id','!=', $product_price->party_id)
    //     ->orWhere('party_type', '=', 'vendor')
    //     // ->whereNotIn('id',[$party])
    //     ->select('id', 'firm_name', 'contact')
    //     ->get()
    //     ->toArray();
    //     return $vendors;
    //     }
    // }
    public function allVendorExcept($product)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        // $this->product = $product;
        // $vendors = Party::whereNotExists(function ($query) {
        //     $query->select(DB::raw(1))
        //         ->from('product_prices')
        //         ->whereRaw('product_prices.product_id='.$this->product);
        // })
        // ->get();
        $results = DB::select(DB::raw("select * from parties where id not in (select party_id from product_prices where product_id= " . $product . ") and party_type !='Customer'
"));

            $data = Product::where('id', $product)->get();
            $data -> map(function ($item){
                $item['data'] = $this -> getVData($item -> id,$item -> div_id);
                $item['ids'] = $pdata = ProductPrice::where('product_id',$item -> id)->get();

            });

            $temp = $data[0]->ids -> map(function ($item){
                return $item -> party_id;
            });
        return response()->json([
            'data' => $data[0]->data,
            'ids' => $temp,
        ]);
    }

    public function getVData($product_id,$div_id){
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $data = Party::join('party_divisions','party_divisions.party_id','parties.id')
        ->join('payment_accounts','payment_accounts.id','party_divisions.div_id')
        ->where('payment_accounts.div_id',$div_id)
        ->where('parties.party_type','!=','Customer')
        // ->where('parties.id','!=',$temp[0])
        ->select('parties.id', 'parties.firm_name','parties.party_type','parties.contact','parties.vat_no','parties.opening_balance','parties.credit_days','payment_accounts.div_id')
        ->get();


        return $data;


    }
    public function partyDelete_all(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        // $expense = ColumnData::where('expense_id',$id)->join('expenses','ColumnData.expense_id','parties.id')->where('party_id', $party_id)->get();
        if($request->status=="contact")
        {
        $tempArray = (array) json_decode($request->data, true);
            foreach ($tempArray as $column_data_) {
                
                $res=Contact::where('id',$column_data_['id'])->delete(); 
                      
            }
        }
        if($request->status=="bank")
        {
        $tempArray = (array) json_decode($request->data, true);
            foreach ($tempArray as $column_data_) {
                
                $res=PartyBank::where('id',$column_data_['id'])->delete(); 
                      
            }
        }
        
        

    }
    
}

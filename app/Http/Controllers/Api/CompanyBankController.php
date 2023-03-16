<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyBank;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;
use DB;
class CompanyBankController extends Controller
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
        
        $bank = CompanyBank::all();
       
        return response()->json($bank);
    }
    public static function banks()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $bank = CompanyBank::all();
       
        return $bank;
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
        
        $bank = CompanyBank::create([
            'name'=> $request->name, 
            'iban_no'=> $request->iban_no, 
            'ac_no'=> $request->ac_no, 
            'bank_address'=> $request->bank_address, 
            
            
        ]);
        // PaymentAccount::create([
        //     'bank_id'=> $bank->id,
        //     'name'=>$bank->name,
        //     'balance'=>$request->balance,
        //     'type'=>'bank',


        // ]);
        return response()->json($bank);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CompanyBank  $companyBank
     * @return \Illuminate\Http\Response
     */
    public function show(CompanyBank $companyBank)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json($companyBank);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CompanyBank  $companyBank
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CompanyBank $companyBank)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        

        $companyBank->update($request->all());

        return response()->json($companyBank);
    } 
    
    public function company_bank_update(Request $request, CompanyBank $companyBank,$id)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];

        DB::table('company_banks')
        ->update(['status' => null]);
        $companyBank=CompanyBank::where("id",$id)->first();
        $companyBank->update([
            // 'family_code' => $this->getFamilyCode(),
            'status'=>"Yes",
                    ]);
        

        // $companyBank->update($request->all());

        return response()->json($companyBank);
    }

    

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CompanyBank  $companyBank
     * @return \Illuminate\Http\Response
     */
    public function destroy(CompanyBank $companyBank)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $companyBank->delete();

        return response()->json(['msg'=>"Successfully Deleted"]);
    }
    public function companybank(CompanyBank $companyBank)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $companyBank->delete();

        return response()->json(['msg'=>"Successfully Deleted"]);
    }
}

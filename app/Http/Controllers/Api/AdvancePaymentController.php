<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdvancePayment;
use App\Models\Receipt;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class AdvancePaymentController extends Controller
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
        
        $allPayments = AdvancePayment::all();

        $allPayments->map(function($payment){
                    $payment['credit']=$payment->amount;
                    $payment->receivedBy;
            return $payment->paymentAccount;
        });

        return response()->json($allPayments);
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
        
        $data = $request->json()->all();

        $payment = AdvancePayment::create($data);

        return response()->json($payment, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AdvancePayment  $advancePayment
     * @return \Illuminate\Http\Response
     */
    public function show(AdvancePayment $advancePayment)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json([$advancePayment], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AdvancePayment  $advancePayment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AdvancePayment $advancePayment)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $advancePayment = AdvancePayment::findOrFail($request->id);
        
        $advancePayment->update([
            'party_id' => $request->party_id,
            'paid_amount' => $request->paid_amount,
            'div_id' => $request->div_id,
            'narration' => $request->narration,
            'check_no' => $request->check_no,
            'bank_id' => $request->bank_id?$request->bank_id:null,
            'received_date' => $request->received_date,
            "user_id" => $request->user_id?$request->user_id:0,
            "div_id" => $request->div_id?$request->div_id:0,
            
            // 'contact_id' => $request->contact_id,
        ]);
        return response()->json(['referrenceImgUrl' => $expense->referrenceImg()]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AdvancePayment  $advancePayment
     * @return \Illuminate\Http\Response
     */
    public function destroy(AdvancePayment $advancePayment)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $advancePayment->delete();

        return response()->json(['msg'=>"Successfully destroyed"], 200);
    }
    public function updateAdvancepay(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $advancePayment = AdvancePayment::findOrFail($request->id);
        
        $advancePayment->update([
            'payment_account_id' => $request->payment_account_id,
            'amount' => $request->amount,
            'narration' => $request->narration,
            'payment_mode' => $request->payment_mode,
            'bank_id' => $request->bank_id?$request->bank_id:'',
            'received_by' => $request->received_by?$request->received_by:'',
            'received_date' => $request->received_date,
            "user_id" => $request->user_id?$request->user_id:0,
        "div_id" => $request->div_id?$request->div_id:0,
            
        
            
            // 'contact_id' => $request->contact_id,
        ]);

        $findRef=AdvancePayment::where('id',$advancePayment->id)->first();
        if($findRef->ref_id)
        {
            Receipt::Where('id',$findRef->ref_id)->update([
                'party_id' => $request->party_id,
                "payment_mode" => $request->payment_mode,
                "narration" => $request->narration,
                'paid_amount' => $request->paid_amount,
                'div_id' => $request->div_id,
                'narration' => $request->narration,
                'check_no' => $request->check_no,
                'bank_id' => $request->bank_id,
                "sender" => $request->sender,
                "receiver" => $request->receiver==" "?0:$request->receiver,
                "paid_date" => $request->paid_date,
        ]);
        }
        return response()->json([$advancePayment]);
    }

}

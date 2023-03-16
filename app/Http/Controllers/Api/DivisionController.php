<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Expense;
use App\Models\Receipt;
use App\Models\PaymentAccount;
use App\Models\AdvancePayment;
use Illuminate\Http\Request;
use DB;
use Config;
use Stichoza\GoogleTranslate\GoogleTranslate;
class DivisionController extends Controller
{
    //


    public function index()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $div = Division::all();
        return response()->json($div);
    }


    public function show(Division $div)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        return response()->json(array($div));
    }
    public function store(Request $request)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
       
        $data = $request->json()->all();
        $apikey=  \Config::get('example.key');
        // if($request->company_name)
        // {
        // $arbic = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key='.$apikey.'&q='.urlencode($request->company_name).'&target=ar'));
        // }
        // else
        // {
        //     $arbic="";
        // }

        if($request->company_name){

            $arbic_irr = new GoogleTranslate('en');
            $arbic = $arbic_irr->setSource('en')->setTarget('ar')->translate($request->company_name);
            }
            else{
    
                $arbic = null;
    
            }
        $party = Division::create([
            'name' => $request->name,
            'opening_bal' => (string) $request->opening_balance,
            'company_name' => (string) $request->company_name,
            'company_arabic' =>$arbic,
            'cr_no' => $request->cr_no,
            'vat_no' => $request->vat_no,
            'id_initials' => $request->id_initials,
            
        
            
        ]);
        PaymentAccount::create([
            'div_id'=> $party->id,
            'name'=>$party->name,
            'balance'=>$party->opening_bal,
            'type'=>'division',


        ]);

        return response()->json([$data]);
    }

    public function update(Request $request, Division $div)
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
       
        $apikey=  \Config::get('example.key');
        // if($request->company_name)
        // {
        // $arbic = json_decode(file_get_contents('https://translation.googleapis.com/language/translate/v2?key='.$apikey.'&q='.urlencode($request->company_name).'&target=ar'));
        // }
        // else
        // {
        //     $arbic="";
        // }
        if($request->company_name){

            $arbic_irr = new GoogleTranslate('en');
            $arbic = $arbic_irr->setSource('en')->setTarget('ar')->translate($request->company_name);
            }
            else{
    
                $arbic = null;
    
            }
    
        $division = Division::findOrFail($request->id);
        $division->update([
            'name' => $request->name,
            'opening_bal' => $request->opening_bal,
            'company_name' => (string) $request->company_name,
            'company_arabic' =>$arbic,
            'cr_no' => $request->cr_no,
            'vat_no' => $request->vat_no,
            'id_initials' => $request->id_initials,
            // 'contact_id' => $request->contact_id,
        ]);
        $res=PaymentAccount::where('div_id',$request->id)->update([
            'name'=>$request->name,
            'balance'=>$request->opening_bal,

        ]);
        
        // return $contact;
    return response()->json([$request->json()->all()]);
    }
    public function singleDivision($id)
    {
       
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $division = Division::where('id',$id)->get();
        $divs = Division::get();
        return response()->json([
            'div' => $division,
            'divs' => $divs
        ]);
    }
    public static function paidDivision()
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
       
        $divEopenbalance=Expense::where('is_paid',1)->sum('amount');
        $divRopenbalance=Receipt::sum('paid_amount');
        
        $division = PaymentAccount::get();
        $datas=$division->map(function ($item) {
            if($item['div_id'])
            {
                $divEopenbalance=Expense::where('is_paid',1)->where('utilize_div_id',$item['id'])->sum('amount'); 
                $accountSum=PaymentAccount::where('id',$item['id'])->sum('balance');
                $recievedby=AdvancePayment::where('received_by',$item['id'])->sum('amount');
                $paidby=AdvancePayment::where('payment_account_id',$item['id'])->sum('amount');
                $divRopenbalance=Receipt::where('div_id',$item['id'])->sum('paid_amount');
                $item['name']=$item->name;
                $item['id']=$item->id;
                $item['balance'] = ($accountSum+$divRopenbalance+$recievedby)-($paidby+$divEopenbalance);
                
                return $item;
            }
            
           else
           {
            $accountSum=PaymentAccount::where('id',$item['id'])->sum('balance');
            $recievedby=AdvancePayment::where('received_by',$item['id'])->sum('amount');
            $paidby=AdvancePayment::where('payment_account_id',$item['id'])->sum('amount');
            $paid_date=AdvancePayment::where('received_by',$item['id'])->orderBy('received_date','DESC')->first('received_date');
            $item['date']=!empty($paid_date->received_date)?$paid_date->received_date:$item->created_at;
            $item['name']=$item->name;
            $item['id']=$item->id;
            $item['balance'] =$accountSum+$recievedby-$paidby;
            return $item;

           }
        
    });
    return response()->json($datas);
    }
//  Multiple Division
    public static function paidDivision2()
    {
       
        $divEopenbalance=Expense::where('is_paid',1)->sum('amount');
        $divRopenbalance=Receipt::sum('paid_amount');
        
        $division = PaymentAccount::get();
        $datas=$division->map(function ($item) {
            if($item['div_id'])
            {
                $divEopenbalance=Expense::where('is_paid',1)->where('utilize_div_id',$item['id'])->sum('amount'); 
                $accountSum=PaymentAccount::where('id',$item['id'])->sum('balance');
                $recievedby=AdvancePayment::where('received_by',$item['id'])->sum('amount');
                $paidby=AdvancePayment::where('payment_account_id',$item['id'])->sum('amount');
                $divRopenbalance=Receipt::where('div_id',$item['id'])->sum('paid_amount');
                $item['name']=$item->name;
                $item['id']=$item->id;
                $item['balance'] = ($accountSum+$divRopenbalance+$recievedby)-($paidby+$divEopenbalance);
                
                return $item;
            }
            
           else
           {
            $accountSum=PaymentAccount::where('id',$item['id'])->sum('balance');
            $recievedby=AdvancePayment::where('received_by',$item['id'])->sum('amount');
            $paidby=AdvancePayment::where('payment_account_id',$item['id'])->sum('amount');
            $paid_date=AdvancePayment::where('received_by',$item['id'])->orderBy('received_date','DESC')->first('received_date');
            $item['date']=!empty($paid_date->received_date)?$paid_date->received_date:$item->created_at;
            $item['name']=$item->name;
            $item['id']=$item->id;
            $item['balance'] =$accountSum+$recievedby-$paidby;
            return $item;

           }
        
    });
    return $datas;
    }
    public function getDivbyId($id){

        $divs = DB::table('user_divisions')->join('divisions','divisions.id','user_divisions.div_id')->where('user_divisions.u_id',$id)->get();
        return response()->json($divs);
    }
}

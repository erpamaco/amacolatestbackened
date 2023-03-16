<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdvancePayment;
use App\Models\Expense;
use App\Models\PaymentAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AdvancePaymentStatementController extends Controller
{

    public function getExpenseData($payment_account_id,  $to_date, $from_date =null)
    {

        $temp = new Collection();
        $temp = Expense::join('payment_accounts','expenses.payment_account_id','payment_accounts.id')->where('expenses.payment_account_id', $payment_account_id)->where('is_paid',1)->whereBetween('expenses.paid_date', [$from_date . ' ' . '00:00:00', $to_date . ' ' . '23:59:59'])->select('payment_accounts.name as user_name','expenses.*')->get();
        return $temp;
    }
    public function getExpenseDataSingle($payment_account_id, $from,$to)
    {
    
        $temp = new Collection();
        $compareDate=date('Y-m-d');
        $temp = Expense::where('payment_account_id', $payment_account_id)->where('is_paid',1)->whereBetween('created_at',[$from . ' ' . '00:00:00', $to!==$compareDate ? $to . ' ' . '23:59:59' : now()])->get();
        if($temp)
        return $temp;

        else{
            $date=date('Y-m-d');
            $temp = Expense::where('payment_account_id', $payment_account_id)->where('is_paid',1)->where('created_at','<=',now())->get();
             return $temp;
        }
        
    }

    public function getAdvancePaymentData($payment_account_id,  $to_date, $from_date = null)
    {
        $temp = new Collection();
        $temp = AdvancePayment::where('payment_account_id', $payment_account_id)
            ->whereBetween('received_date', [$from . ' ' . '00:00:00', $to . ' ' . '23:59:59'])->get();
            return $temp;
    }
    public function getAdvancePaymentDataSingle($payment_account_id,   $from, $to)
    {
        $temp = new Collection();
        $compareDate=date('Y-m-d');
        $temp = AdvancePayment::where('payment_account_id', $payment_account_id)
            ->whereBetween('created_at', [$from . ' ' . '00:00:00', $to!==$compareDate ? $to . ' ' . '23:59:59' : now()])->get();
            if($temp)
            return $temp;
            else{
                $date=date('Y-m-d');
                $temp = AdvancePayment::where('payment_account_id', $payment_account_id)->where('created_at','<=',$date)->get();
                 return $temp;
            }
    
           
    }

    public function statement(Request $request)
    {
        $paymentAccount = PaymentAccount::where('id', intval($request['payment_account_id']))->first();

        if (!$paymentAccount) {
            return response('No paymentAccount exists by this id', 500);
        }

        // -----------------------------------
        $paymentAccountOpeningBalance = floatval(0);

        $oldExpenseCollection = $this->getExpenseDataSingle($paymentAccount->id, $request['from_date'],$request['to_date']);
        $oldAdvancePaymentCollection = $this->getAdvancePaymentDataSingle($paymentAccount->id, $request['from_date'],$request['to_date']);
        $data = $oldExpenseCollection->concat($oldAdvancePaymentCollection);
        if (!$data) {
            return response()->json(['msg' => "There are no entries between" . $request['from_date'] . " to " . $request['from_date']], 400);
        }
        
        $data = $data->sortBy('created_at')->take(10);
        $date = date('Y-m-d', strtotime('-1 day', strtotime($request->from_date)));
        $opening_expenseCollection = Expense::where('is_paid',1)->where('payment_account_id',$paymentAccount->id)->whereBetween('created_at', ['2021-01-01' . ' ' . '00:00:00', $date  . ' ' . '23:59:59' ])->sum('amount');

        $opening_advancePaymentCollection = AdvancePayment::where('payment_account_id',$paymentAccount->id)->whereBetween('created_at', ['2021-01-01' . ' ' . '00:00:00', $date . ' ' . '23:59:59' ])->sum('amount');

        $closingBalance= $opening_advancePaymentCollection-$opening_expenseCollection;
        $balance=$opening_expenseCollection;
        $sum=0.00;

       

       

        $data && ($datas['data'] = $data->map(function ($item) {
            if ($item->paid_date) {
                $item['name']  =$item->user_name;
                $item['date'] = $item->created_at;
                $item['code_no'] = $item->transaction_id;
                $item['description'] = $item->description;
                $item['debit'] = null;
                $item['credit'] =floatval(str_replace(",","",$item->amount));
               
                return [$item];
               

            }

            if ($item->received_date) {
                $item['date'] = $item->created_at;
                $item['code_no'] = null;
                $item['description'] = $item->narration;
                $item['credit'] = null;
                $item['debit'] = floatval(str_replace(",","",$item->amount));
                return [$item];
               
            }
        }));

        !$data && $datas['data'] = null;
        $datas['opening_balance'] = $closingBalance;
        $datas['name'] = $paymentAccount->name;
        $datas['from_date'] = $request['from_date'];
        $datas['to_date'] = $request['to_date'];
        $datas['balance'] = $sum;
        return response()->json([$datas]);
        
    }

    public function allAdvancePaymentStatement(Request $request)
    
    {
        if(!auth()->check())
        return ["You are not authorized to access this API."];
        
        $advanceEopenbalance=Floatval('0.00');
        $advanceAopenbalance=Floatval('0.00');
        $paymentAccount = PaymentAccount::where('id', intval($request['payment_account_id']))->first();
        $expenseCollection = new Collection();
        if ($request->from_date) {
            $expenseCollection = Expense::join('payment_accounts','expenses.payment_account_id','payment_accounts.id')->where('expenses.status','verified')->whereBetween('expenses.created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->select('payment_accounts.name as user_name','expenses.*')->get();
            $advanceEopenbalance=Expense::where('created_at', '<=', $request->from_date. ' ' . '00:00:00')->sum(str_replace(",","",'amount'));
        } else {
            $expenseCollection = Expense::all();
        }

        $advancePaymentCollection = new Collection();
        if ($request->from_date) {
            $advancePaymentCollection1 = AdvancePayment::where('payment_account_id',$request['payment_account_id'])->join('payment_accounts','advance_payments.received_by','payment_accounts.id')->whereBetween('advance_payments.created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->select('payment_accounts.name as user_name','advance_payments.*')->get();
            $advancePaymentCollection2 = AdvancePayment::where('received_by',$request['payment_account_id'])->join('payment_accounts','advance_payments.payment_account_id','payment_accounts.id')->whereBetween('advance_payments.created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->select('payment_accounts.name as user_name','advance_payments.*')->get();
            


            $advanceAopenbalance=AdvancePayment::where('created_at', '<=',$request->from_date. ' ' . '00:00:00')->sum(str_replace(",","",'amount'));
            
        } else {
            $advancePaymentCollection = AdvancePayment::all();
        }

        // $data = $expenseCollection->concat($advancePaymentCollection);
        $data = $advancePaymentCollection;
        $data = $data->sortBy('created_at');
        $paidby = $advancePaymentCollection1->map(function ($item) {
               
            $item['name']  =$item->user_name;
            $item['date'] = $item->created_at;
            $item['code_no'] = $item->transaction_id;
            $item['description'] = $item->narration;
            $item['debit'] = floatval(str_replace(",","",$item->amount));
            $item['credit'] = null;
            return [$item];
    });
    $receivedby = $advancePaymentCollection2->map(function ($item) {
               
        $item['name']  =$item->user_name;
        $item['date'] = $item->created_at;
        $item['code_no'] = $item->transaction_id;
        $item['description'] = $item->narration;;
        $item['credit'] = floatval(str_replace(",","",$item->amount));
        $item['debit'] = null;
        return [$item];
});
    $datas['data'] = $paidby->concat($receivedby);

        // $data && ($datas['data'] = $data->map(function ($item) {
        //     if ($item->paid_date) {
        //         $item['name']  =$item->user_name;
        //         $item['date'] = $item->created_at;
        //         $item['code_no'] = $item->transaction_id;
        //         $item['description'] = $item->description;
        //         $item['debit'] = null;
        //         $item['credit'] = floatval(str_replace(",","",$item->amount));
        //         return [$item];
        //     }

        //     if ($item->received_date) {
        //         $item['date'] = $item->created_at;
        //         $item['code_no'] = null;
        //         $item['description'] = $item->narration;
        //         $item['credit'] = null;
        //         $item['debit'] = floatval(str_replace(",","",$item->amount));
        //         return [$item];
        //     }
        // }));
        
        $datas['opening_balance'] = $advanceEopenbalance-$advanceAopenbalance;
        $datas['name'] = $paymentAccount->name;
        $datas['from_date'] = $request['from_date'] ? $request['from_date'] : "2021-01-01";
        $datas['to_date'] = $request['to_date'] ? $request['to_date'] : substr(now(),0, 10);
        $datas['balance'] = 0.00;

        return response()->json([$datas]);
    }
    public function accountSummary(Request $request)
    
    {
        

        

        // $data = $expenseCollection->concat($advancePaymentCollection);
        $data = PaymentAccount::get();
        $ids=(int)$request['id'];
        $advancePaymentCollection1 = $data->sortBy('created_at');
        $paidby = $advancePaymentCollection1->map(function ($item) use($ids) {
            $credit=AdvancePayment::where([['received_by',$item->id]])->sum('amount'); 
            $debit=AdvancePayment::where([['payment_account_id',$item->id]])->sum('amount'); 
            $item['name']  =$item->name;
            $item['balance'] = $credit-$debit;
            $item['credit'] = $credit;
            $item['debit'] = $debit;
            return [$item];
    });
   
            $datas['data'] = $paidby;

        
        
        // $datas['opening_balance'] = $advanceEopenbalance-$advanceAopenbalance;
        $datas['id'] = (int)$request['id'];
        // $datas['from_date'] = $request['from_date'] ? $request['from_date'] : "2021-01-01";
        // $datas['to_date'] = $request['to_date'] ? $request['to_date'] : substr(now(),0, 10);
        // $datas['balance'] = 0.00;

        return response()->json([$datas]);
    }
}

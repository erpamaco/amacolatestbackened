<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\ColumnData;
use App\Models\Expense;
use App\Models\payment_account;
use App\Models\Party;
use App\Models\Division;
use App\Models\PaymentAccount;
use App\Models\PurchaseInvoice;
use App\Models\CompanyBank;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\EmployeesController;
use App\Http\Controllers\Api\DivisionController;
// use App\Models\Party;
use App\Models\AccountCategory;

use App\Models\AdvancePayment;
use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class ExpenseController extends Controller
{
    /**
     * Display a listing of the expenses which are not paid.
     *
     * @return \Illuminate\Http\Response
     * 
     */
    // public function __construct(){
    //     $this->middleware('auth:api');

    // }


    public function expenseInvoiceReport()
    {
        $allExpense =  Expense::join('account_categories', 'account_categories.id', 'expenses.account_category_id')->where('account_categories.name', 'Material Purchase')->select('expenses.*')->orderBy('expenses.id', 'desc')->get();
        $allExpense = $allExpense->values();
        $allExpense->map(function ($item) {
            $item->party = Party::where('id', $item->vendor_id)->first();
            if (strpos($item->q_i_number, 'QT')) {
                $item->type = 'Q';
                $item->allData = QuotationController::showReport($item->q_i_number);
                $qt = Quotation::where('quotation_no', $item->q_i_number)->get();
                $item->profit = isset($qt[0]) ? $qt[0] ? $qt[0]->net_amount -  $item->amount : '' : '';;
                $item->quotation = isset($qt[0]) ? $qt[0] ? $qt[0] : '' : '';
                $item->tot_amount = isset($qt[0]) ? $qt[0] ? $qt[0]->net_amount : '' : '';
            } else {
                $inv = Invoice::where('invoice_no', $item->q_i_number)->get();
                $item->type = 'I';
                $item->allData = InvoiceController::showsReport($item->q_i_number);
                $item->profit = isset($inv[0]) ? $inv[0] ?  $inv[0]->grand_total - $item->amount : '' : '';;
                $item->invoice = isset($inv[0]) ? $inv[0] ? $inv[0] : '' : '';
                $item->tot_amount = isset($inv[0]) ? $inv[0] ? $inv[0]->grand_total : '' : '';
            }

            return $item;
        });
        return $allExpense;
    }


    public function index()
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        //         $expenses = Expense::join('account_categories','expenses.account_category_id','account_categories.id')->join('divisions','expenses.div_id','divisions.id')->
        //         join('divisions as divtable','expenses.utilize_div_id','divtable.id')->select(
        //     'divisions.name as paid_from',
        //     'divtable.name as paid_towards',
        //     'account_categories.name',
        //             'expenses.*'
        // )->where("status", "new")->orderBy('created_at', 'DESC')->get();
        //         $expenses->map(function ($expense) {
        //             return $expense->payment_account;
        //         });
        //         return response()->json($expenses);

        $expenses = Expense::join('account_categories', 'expenses.account_category_id', 'account_categories.id')->join('payment_accounts', 'expenses.utilize_div_id', 'payment_accounts.id')->select(
            'payment_accounts.name as paid_from',
            'payment_accounts.name as paid_towards',
            'account_categories.name',
            'expenses.*'
        )->where("status", "new")->orderBy('created_at', 'DESC')->get();
        $expenses->map(function ($expense) {
            return $expense->payment_account;
        });
        $expenses->map(function ($item) {
            if ($item['vendor_id'] !== '0') {
                $item['paid_to'] = $this->getPartyName($item['vendor_id']);
            }
        });
        return response()->json($expenses);
    }
    public static function index1()
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        //         $expenses = Expense::join('account_categories','expenses.account_category_id','account_categories.id')->join('divisions','expenses.div_id','divisions.id')->
        //         join('divisions as divtable','expenses.utilize_div_id','divtable.id')->select(
        //     'divisions.name as paid_from',
        //     'divtable.name as paid_towards',
        //     'account_categories.name',
        //             'expenses.*'
        // )->where("status", "new")->orderBy('created_at', 'DESC')->get();
        //         $expenses->map(function ($expense) {
        //             return $expense->payment_account;
        //         });
        //         return response()->json($expenses);

        $expenses = Expense::join('account_categories', 'expenses.account_category_id', 'account_categories.id')->join('payment_accounts', 'expenses.utilize_div_id', 'payment_accounts.id')->select(
            'payment_accounts.name as paid_from',
            'payment_accounts.name as paid_towards',
            'account_categories.name',
            'expenses.*'
        )->orderBy('created_at', 'DESC')->get();
        $expenses->map(function ($expense) {
            return $expense->payment_account;
        });
        // $expenses -> map(function ($item){
        //     if($item['vendor_id'] !== '0'){
        //         $item['paid_to'] = $this -> getPartyName($item['vendor_id']);
        //     }
        // });
        return response()->json($expenses);
    }


    public function getPartyName($id)
    {

        $data = Party::where('id', $id)->get();
        try {
            return $data[0]->firm_name;
        } catch (\Throwable $th) {
            return '--';
        }
        // return "vbsdbcbs";
    }

    // to get all paid expenses
    public function paid()
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $expenses = $expenses = Expense::join('account_categories', 'expenses.account_category_id', 'account_categories.id')->join('payment_accounts', 'expenses.utilize_div_id', 'payment_accounts.id')->select(
            'payment_accounts.name as paid_from',
            'payment_accounts.name as paid_towards',
            'account_categories.name',
            'expenses.*'
        )->where("status", 'verified')->orderBy('created_at', 'DESC')->get();
        $expenses->map(function ($expense) {

            $expense['debit'] = $expense->amount;
            $expense->payment_account;
            return $expense->account_categories;
        });
        $expenses->map(function ($item) {
            if ($item['vendor_id'] !== '0') {
                $item['paid_to'] = $this->getPartyName($item['vendor_id']);
            }
        });
        return response()->json($expenses);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $bank_slip_path = null;
        if ($request->file('bank_slip')) {
            $bank_slip_path = $request->file('bank_slip')->move("expenses/bankSlip", $request->file('bank_slip')->getClientOriginalName());
        }

        if ($request->file('file_path')) {
            $filePath = $request->file('file_path')->move("expenses/filePath",  $request->file('file_path')->getClientOriginalName());
        }

        if ($request->payment_account_id === "other") {
            $account = PaymentAccount::create([
                'name' => $request->payeename,
                'user_id' => null,
            ]);
            $lastInsertedId = $account->id;
        } else {
            $lastInsertedId = $request->payment_account_ids;
        }
        $data = [];
        $div_id = $request->utilize_div_id;
        $arr = collect($request->payment_account_ids);





        $sumVal = floatval(0);
        $status = false;
        $amountVal = $request->amount;

        $map = $arr->map(
            function ($items) use ($request, $sumVal, $status, $amountVal) {
                $pieces = explode(",", $items);
                $data['id'] = floatval($pieces[0]);











                return $data['id'];
            }
        );


        // $collection = [1,2,3,4,5];
        $demo = $map->toArray();
        $test = implode(',', $demo);
        $expense = Expense::create([
            'created_by' => $request->created_by,
            'paid_date' => $request->paid_date,
            'paid_to' => $request->paid_to ? $request->paid_to : '',
            'amount' => $request->amount,
            'q_i_number' => $request->q_i_number,
            'payment_type' => $request->payment_type,
            'check_no' => $request->cheque_no,
            'transaction_id' => $request->transaction_id,
            'payment_account_id' => $test,
            'description' => $request->description ? $request->description : '',
            'referrence_bill_no' => $request->referrence_bill_no,
            'tax' => $request->tax,
            'status' => $request->status,
            // 'paid_by' => $lastInsertedId,
            'bank_ref_no' => $request->bank_ref_no,
            'bank_id' => $request->bank_id ? $request->bank_id : null,
            'bank_slip' => $request->file('bank_slip') ? $bank_slip_path : null,
            // // 'bank_slip' =>  $path ,
            "account_category_id" => $request->account_category_id,
            "company_name" => $request->company_name ? $request->company_name : " ",
            "file_path" => $request->file('file_path') ? $filePath : null,
            "div_id" => $request->div_id ? $request->div_id : 0,
            "user_id" => $request->user_id ? $request->user_id : 0,
            "company" => $request->company ? $request->company : " ",
            "vatno" => $request->vatno ? $request->vatno : " ",
            "inv_no" => $request->inv_no ? $request->inv_no : " ",
            "utilize_div_id" => $request->utilize_div_id ? $request->utilize_div_id : " ",
            "vendor_id" => $request->vendor_id ? $request->vendor_id : 0,
            "employee_id" => $request->employee_id ? $request->employee_id : 0

        ]);

        $tempArray = (array) json_decode($request->data, true);
        foreach ($tempArray as $column_data_) {
            $column_data = $column_data_;

            $column_type = $column_data['type'];
            if ($column_type != 'file') {
                $column_data_value = $column_data[$column_type];
            }
            $tempFile = "file" . $column_data['id'];
            if ($request->file($tempFile)) {
                $column_data_value = $request->file($tempFile)->move('expenses/files', $request->file($tempFile)->getClientOriginalName());
            }




            ColumnData::create([
                "expense_id" => $expense->id,
                "column_id" => $column_data['id'],
                "value" => $column_data_value ? $column_data_value : null,
            ]);
        }
        $maps = $arr->map(
            function ($items) use ($expense, $request, $sumVal, $status, $amountVal) {
                $pieces = explode(",", $items);
                $data['id'] = floatval($pieces[0]);



                if (floatval($request->utilize_div_id) !== floatval($pieces[0])) {

                    AdvancePayment::create([
                        "payment_account_id" => $data['id'],
                        "received_by" => $request->utilize_div_id,
                        "amount" => floatval($pieces[2]),
                        "payment_mode" => $request->payment_type,
                        "expense_id" => $expense->id,
                        "narration" => $request->description,
                        'received_date' => $request->paid_date,
                        'user_id' => $request->user_id ? $request->user_id : 0,
                        'div_id' => $request->div_id ? $request->div_id : 0,
                    ]);
                }







                return $data['id'];
            }
        );
        if ($expense->id) {
            $expense->update(['voucher_no' => 'AMC-' . 'TR-' . 'EV-' . date('y') . '-' . sprintf('%05d', $expense->id)]);
        }
        return response()->json($test);
    }
    // }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function show(Expense $expense)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $data = [];
        $resultArray = collect(explode(',', $expense->payment_account_id));
        $memebrsInfo = $expense->payment_account_id;
        $map = $resultArray->map(
            function ($items, $key) use ($data) {


                $result = Paymentaccount::where('id', floatval($items))->get();


                return $result;
            }
        );
        $res = AccountCategory::where('id', $expense->account_category_id)->get();
        // $collection =  collect([explode('.',$memebrsInfo)]);

        // $multiplied = $collection->map(function ($item, $key) {
        //     return floatval($item) * 2;
        // });
        return response()->json([
            $expense,
            $expense->payment_account,
            $expense->vendor_id,
            $expense->column_data->map(function ($item) {
                if (File::exists(public_path($item->value))) {
                    $item['file'] = url($item->value);
                }
                return $item->column;
            }),
            'mapdata' => $map,

            'account' => $res,

            'img' => $expense->img(),
            'referrenceImgUrl' => $expense->referrenceImg(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Expense $expense)
    {
        // $request['is_paid'] = true;
        $reqdata = $request->all();
        // $expense = Expense::findOrfail($request->id);
        if ($request->status === "verified") {
            $expense = Expense::where('id', $request->id)->update(['status' => $request->status]);
        } else {
            $expense = Expense::where('id', $request->id)->update(['is_paid' => 2]);
        }
        // $expense->update($request->all());

        return response()->json($request->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     * 
     * 
     */
    public function destroy(Expense $expense)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $expense->delete();
        $res = AdvancePayment::where('expense_id', $expense->id)->delete();
        return response()->json(['msg' => 'Expense ' . $expense . ' has been deleted.']);
    }

    public function expenseUpdate(Request $request, Expense $expense)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        if ($request->file('file_path')) {
            $filePath = $request->file('file_path')->move("expenses/filePath",  $request->file('file_path')->getClientOriginalName());
            $expenseF = Expense::where('id', $request->id)->update([
                "file_path" => $request->file('file_path') ? $filePath : null,
            ]);
        }
        if ($request->file('bank_slip')) {
            $bank_slip_path = $request->file('bank_slip')->move("expenses/bankSlip", $request->file('bank_slip')->getClientOriginalName());
            $expenseB = Expense::where('id', $request->id)->update([
                "bank_slip" => $request->file('bank_slip') ? $bank_slip_path : null,
            ]);
        }
        $expenseId = Expense::findOrfail($request->id);
        $res = AdvancePayment::where('expense_id', $request->id)->delete();

        if ($request->payeename) {
            $account = PaymentAccount::create([
                'name' => $user->name,
                'user_id' => null,
            ]);
            return $lastInsertedId = $account->id;
        }
        $data = [];
        $div_id = $request->utilize_div_id;
        $arr = collect($request->payment_account_ids);





        $sumVal = floatval(0);
        $status = false;
        $amountVal = $request->amount;


        $map = $arr->map(
            function ($items) use ($request, $sumVal, $status, $amountVal) {
                $pieces = explode(",", $items);
                $data['id'] = floatval($pieces[0]);



                if (floatval($request->utilize_div_id) !== floatval($pieces[0])) {

                    AdvancePayment::create([
                        "payment_account_id" => $data['id'],
                        "received_by" => $request->utilize_div_id,
                        "amount" => floatval($pieces[2]),
                        "payment_mode" => $request->payment_type,
                        "received_date" => $request->paid_date,
                        "narration" => $request->description,

                        "expense_id" => $request->id,
                        'user_id' => $request->user_id ? $request->user_id : 0,
                        'div_id' => $request->div_id ? $request->div_id : 0,
                    ]);
                }







                return $data['id'];
            }
        );


        // $collection = [1,2,3,4,5];
        $demo = $map->toArray();
        $test = implode(',', $demo);

        $expense = Expense::where('id', $request->id)->update([
            'created_by' => $request->created_by,
            'paid_date' => $request->paid_date,
            'paid_to' => $request->paid_to ? $request->paid_to : " ",
            'amount' => $request->amount,
            'payment_type' => $request->payment_type,
            'check_no' => $request->cheque_no,
            'transaction_id' => $request->transaction_id,
            'payment_account_id' => $test,
            'description' => $request->description ? $request->description : " ",
            // 'referrence_bill_no' => $request->referrence_bill_no,
            'tax' => $request->tax,
            // 'status' => $request->status,
            // 'paid_by' => $request->payment_account_id?$request->payment_account_id:null,
            'bank_ref_no' => $request->bank_ref_no,
            // 'bank_slip' => $request->file('bank_slip') ? $bank_slip_path : null,
            // 'bank_slip' =>  $path ,
            "account_category_id" => $request->account_category_id,
            "company_name" => $request->company_name ? $request->company_name : " ",
            "company" => $request->company ? $request->company : " ",
            "vatno" => $request->vatno ? $request->vatno : " ",
            "inv_no" => $request->inv_no ? $request->inv_no : " ",
            "utilize_div_id" => $request->utilize_div_id ? $request->utilize_div_id : " ",
            'user_id' => $request->user_id ? $request->user_id : 0,
            'div_id' => $request->div_id ? $request->div_id : 0,

            'bank_id' => $request->bank_id ? $request->bank_id : null,
            "vendor_id" => $request->vendor_id ? $request->vendor_id : " ",
            "employee_id" => $request->employee_id ? $request->employee_id : 0

        ]);


        $res = ColumnData::where('expense_id', $request->id)->delete();
        $tempArray = (array) json_decode($request->data, true);
        foreach ($tempArray as $column_data_) {
            $column_data = $column_data_;

            $column_type = $column_data['type'];
            if ($column_type != 'file') {
                $column_data_value = $column_data[$column_type];
            }
            $tempFile = "file" . $column_data['id'];
            if ($request->file($tempFile)) {
                $column_data_value = $request->file($tempFile)->move('expenses/files', $request->file($tempFile)->getClientOriginalName());
            }


            ColumnData::create([
                "expense_id" => $request->id,
                "column_id" => $column_data['column_id'],
                "value" => $column_data_value ? $column_data_value : null,
            ]);
        }
        return response()->json($tempArray);
    }
    public function singleExpense($id)
    {
        $expense = ColumnData::where('expense_id', $id)->join('expenses', 'ColumnData.expense_id', 'parties.id')->where('party_id', $party_id)->get();


        return response()->json([$expense]);
    }
    public function Expense_delete_verify(Request $request, Expense $expense)
    {
        // $expense = ColumnData::where('expense_id',$id)->join('expenses','ColumnData.expense_id','parties.id')->where('party_id', $party_id)->get();


        if ($request->status == "delete") {

            $tempArray = $request->data;
            $arr = (array) json_decode($request->data, true);




            Expense::destroy($arr);
            // AdvancePayment::where('expense_id',$arr)->delete();
            $res = AdvancePayment::whereIn('expense_id', $arr)->delete();


            return response()->json($res);
        }
        if ($request->status == "verify") {
            $tempArray = (array) json_decode($request->data, true);
            foreach ($tempArray as $column_data_) {

                // $res=Expense::where('id',$column_data_['id'])->get(); 
                $ExpenseRes = Expense::findOrFail($column_data_['id']);
                $ExpenseRes->update(['status' => 'verified']);
                // return response($column_data_['id']);      
            }
        }
    }
    public function expense_chart()
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        // $aResult=AccountCategory::where('parent_id',null)->get();
        $eResult = Expense::join('account_categories', 'account_categories.id', 'expenses.account_category_id')->get();


        return response([$eResult]);
    }
    public function purchaseTax(Expense $expense)
    {
        $expenses = Expense::select('expenses.paid_date as issue_date','expenses.*')->where('tax', '!=', ' ')->get();
        // $result=$invoices->party;
        return $expenses;
    }

    public static function shows($id)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $expense = Expense::where('id', $id)->first();
        $expesne = $expense;
        $data = [];
        $resultArray = collect(explode(',', $expense->payment_account_id));
        $memebrsInfo = $expense->payment_account_id;
        $map = $resultArray->map(
            function ($items, $key) use ($data) {


                $result = Paymentaccount::where('id', floatval($items))->get();


                return $result;
            }
        );
        $res = AccountCategory::where('id', $expense->account_category_id)->get();
        // $collection =  collect([explode('.',$memebrsInfo)]);

        // $multiplied = $collection->map(function ($item, $key) {
        //     return floatval($item) * 2;
        // });
        return response()->json([
            $expense,
            $expense->payment_account,
            $expense->vendor_id,
            $expense->column_data->map(function ($item) {
                if (File::exists(public_path($item->value))) {
                    $item['file'] = url($item->value);
                }
                return $item->column;
            }),
            'mapdata' => $map,

            'account' => $res,

            'img' => $expense->img(),
            'referrenceImgUrl' => $expense->referrenceImg(),
        ]);
    }


    public static function parties($id)
    {
        $vendors = Party::join('party_divisions', 'party_divisions.party_id', 'parties.id')
            ->join('payment_accounts', 'payment_accounts.id', 'party_divisions.div_id')
            ->where('payment_accounts.div_id', $id)
            ->where('parties.delete', 0)
            ->where('parties.status', 1)
            ->where('parties.party_type', '!=', 'Vendor')
            ->select('parties.id', 'parties.firm_name', 'parties.party_type', 'parties.contact', 'parties.opening_balance', 'parties.credit_days', 'payment_accounts.div_id')
            ->orderBy('parties.firm_name', 'ASC')
            ->get();
        // ->toArray();

        return  $vendors;
    }

    public static function vendors_det($id)
    {
        $vendors = Party::join('party_divisions', 'party_divisions.party_id', 'parties.id')
            ->join('payment_accounts', 'payment_accounts.id', 'party_divisions.div_id')
            ->where('payment_accounts.div_id', $id)
            ->where('parties.delete', 0)
            ->where('parties.status', 1)
            ->where('parties.party_type', '!=', 'Customer')
            ->select('parties.id', 'parties.firm_name', 'parties.party_type', 'parties.contact', 'parties.opening_balance', 'parties.credit_days', 'payment_accounts.div_id')
            ->orderBy('parties.firm_name', 'ASC')
            ->get();
        // ->toArray();

        return  $vendors;
    }
    public static function mjrExpense($did)
    {
        // $parties = ExpenseController::parties($did);

        // $parties->map(function ($item) {
        //     $item->quotation = Quotation::where('party_id', $item->id)->where(['status' => 'accept', 'transaction_type' => 'sale'])
        //         ->whereNotExists(function ($query) {
        //             $query->select(DB::raw(1))
        //                 ->from('invoices')
        //                 ->whereRaw('invoices.quotation_id = quotations.id');
        //         })->orderBy('id', 'DESC')
        //         ->get();
        //     $item->invoice = Invoice::where('genarate_status', 'Invoice Generated')->where('status', '!=', 'Delivered')
        //         ->orderBy('created_at', 'DESC')->get();
        //     return $item;
        // });
        $parties = ExpenseController::parties($did);
        $vendors = ExpenseController::vendors_det($did);

        $vendors->map(function ($item) {
            // where('party_id', $item->id)->
            $item->p_order = Quotation::where(['transaction_type' => 'purchase','quotation_no' => 0])->get();
            $item->p_invoice = PurchaseInvoice::where('delete_status', 0)->where('status', '!=', 'Delivered')
                ->orderBy('created_at', 'DESC')->get();
            return $item;
        });


        if (!auth()->check())
            return ["You are not authorized to access this API."];




        $account_categories = AccountCategoryController::index();
        return response()->json([
            'vendor' => $vendors,
            'parties' => $parties,
            'payment_account' => PaymentAccount::all(),
            'employee' => EmployeesController::getEmp()->original,
            'account_categories' => $account_categories->original,
            'division' => Division::all(),
            'paidDivision' => DivisionController::paidDivision()->original,
            'companyBank' => CompanyBank::all()
            // 'product' => $this->productShow($pid)['product'],
            // 'price' => $this->productShow($pid)['prices'],
            // 'product_in_category' => CategoryController::products_in_category2(),
            // 'manufacture' => Manufacturer::get(),
            // 'getAllCat'=>Category::get(),

        ]);
    }
    public static function mjrExpenseUpdate($did, $eid, $cid)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $colum = ColumnController::show($cid);
        $account_categories = AccountCategoryController::index();
        return response()->json([
            'vendor' => PartyController::vendor($did),
            'payment_account' => PaymentAccount::all(),
            'employee' => EmployeesController::getEmp()->original,
            'account_categories' => $account_categories->original,
            'division' => Division::all(),
            'paidDivision' => DivisionController::paidDivision()->original,
            'companyBank' => CompanyBank::all(),
            'expense' => self::shows($eid)->original,
            'columns' => $colum->original
            // 'product' => $this->productShow($pid)['product'],
            // 'price' => $this->productShow($pid)['prices'],
            // 'product_in_category' => CategoryController::products_in_category2(),
            // 'manufacture' => Manufacturer::get(),
            // 'getAllCat'=>Category::get(),

        ]);
    }
}

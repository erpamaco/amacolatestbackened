<?php
// Account statement controller
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\Receipt;
use App\Models\Expense;
use App\Models\PaymentAccount;
use App\Models\AdvancePayment;
use App\Models\PurchaseInvoice;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\AccountCategoryController;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\PartyController;


class AccountStatementController extends Controller
{


    public function vendorStatementNew($did)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $d = $this->vendorStatement1();
        return response()->json([
            'venState' => $d->original,
            'vendors' => PartyController::vendor($did),

        ]);
    }

    public function getInvoiceData($party_id,  $to_date, $from_date = null)
    {
        $temp = new Collection();
        $temp = Invoice::join('parties', 'invoices.party_id', 'parties.id')->where('invoices.approve', 1)->where('invoices.party_id', $party_id)->select(
            'parties.credit_days',
            'invoices.*'
        )
            ->whereBetween('invoices.created_at', [$from_date . ' ' . '00:00:00', $to_date . ' ' . '23:59:59'])->get();
        return $temp;
    }

    public function getReceiptData($party_id,  $to_date, $from_date = null)
    {
        $temp = new Collection();
        $temp = Receipt::join('parties', 'receipts.party_id', 'parties.id')->where('party_id', $party_id)->select(
            'parties.credit_days',
            'receipts.*'
        )->where('party_id', $party_id)
            ->whereBetween('receipts.created_at', [$from_date . ' ' . '00:00:00', $to_date . ' ' . '23:59:59'])->get();
        return $temp;
    }


    public function accountStatement(Request $request)
    {
        $party = Party::where('id', intval($request['party_id']))->first();
        if (!$party) {
            return response('No party exists by this id', 400);
        }

        // -----------------------------------
        $partyOpeningBalance = floatval($party->opening_balance);

        $oldInvoiceCollection = $this->getInvoiceData($party->id, $request['from_date']);
        $oldReceiptCollection = $this->getReceiptData($party->id, $request['from_date']);
        $oldData = $oldInvoiceCollection->concat($oldReceiptCollection);
        if (!$oldData) {
            return response()->json(['msg' => "There are no entries between" . $request['from_date'] . " to " . $request['from_date']], 400);
        }
        $oldData = $oldData->sortBy('created_at');

        foreach ($oldData as $key => $item) {
            if ($item->total_value) {
                $partyOpeningBalance += floatVal($item['total_value']);
            }

            if ($item->paid_amount) {
                $partyOpeningBalance -= floatVal($item['paid_amount']);
            }
        }
        // ------------------------------------


        $invoiceCollection = $this->getInvoiceData($party->id, $request['to_date'], $request['from_date']);

        $receiptCollection = $this->getReceiptData($party->id, $request['to_date'], $request['from_date']);
        $data = $invoiceCollection->concat($receiptCollection);
        $data = $data->sortBy('created_at');

        $data && ($datas['data'] = $data->map(function ($item) {
            if ($item->total_value) {
                $item['date'] = $item->created_at;
                $item['code_no'] = $item->invoice_no;
                $item['description'] = "Sale";
                $item['debit'] = $item->total_value;
                $item['po_number'] = $item->po_number;
                $item['credit_days'] = floatval($item->credit_days);
                $item['credit'] = null;
                return [$item];
            }

            if ($item->paid_amount) {
                $item['date'] = $item->created_at;
                $item['code_no'] = $item->receipt_no;
                $item['description'] = "Payment Incoming";
                $item['credit'] = $item->paid_amount;
                $item['po_number'] = $item->po_number;
                $item['credit_days'] = floatval($item->credit_days);
                $item['debit'] = null;
                return [$item];
            }
        }));

        !$data && $datas['data'] = null;
        $datas['opening_balance'] = $partyOpeningBalance;
        $datas['firm_name'] = $party->firm_name;
        $datas['credit_days'] = $party->credit_days;
        $datas['from_date'] = $request['from_date'];
        $datas['to_date'] = $request['to_date'];

        return response()->json([$datas]);
    }

    public function allAccountStatement(Request $request)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $invoiceCollection = new Collection();
        if ($request->from_date) {
            $invoiceCollection = Invoice::join('parties', 'invoices.party_id', 'parties.id')->select('parties.credit_days', 'invoices.*')->whereBetween('invoices.created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->get();
        } else {
            $invoiceCollection = Invoice::all();
        }

        $receiptCollection = new Collection();
        if ($request->from_date) {
            $receiptCollection = Receipt::join('parties', 'receipts.party_id', 'parties.id')->select('parties.credit_days', 'receipts.*')->whereBetween('receipts.created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->get();
        } else {
            $receiptCollection = Receipt::all();
        }

        $data = $invoiceCollection->concat($receiptCollection);
        $data = $data->sortBy('created_at');

        $data && ($datas['data'] = $data->map(function ($item) {
            if ($item->total_value) {
                $item['date'] = $item->created_at;
                $item['code_no'] = $item->invoice_no;
                $item['description'] = "Sale" . "/" . (isset($item->party) ? $item->party->firm_name : " ");
                $item['debit'] = floatval(str_replace(",", "", $item->total_value));
                $item['po_number'] = $item->po_number;
                $item['credit'] = 0;
                $item['credit_days'] = floatval(isset($item->party) ? ($item->party->credit_days) : 0);
                return [$item];
            }

            if ($item->paid_amount) {
                $item['date'] = $item->created_at;
                $item['code_no'] = $item->receipt_no;
                $item['description'] = "Receipt" . "/" . $item->party->firm_name;
                $item['credit'] = floatval(str_replace(",", "", $item->paid_amount));
                $item['po_number'] = $item->po_number;
                $item['debit'] = 0;
                $item['credit_days'] = floatval($item->party->credit_days);
                return [$item];
            }
        }));
        $datas['opening_balance'] = 0;
        $datas['name'] = "All";
        $datas['from_date'] = $request['from_date'] ? $request['from_date'] : "2021-01-01";
        $datas['to_date'] = $request['to_date'] ? $request['to_date'] : substr(now(), 0, 10);

        return response()->json([$datas]);
    }
    public function profitLoss(Request $request)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $user_details = new Collection();
        $user_details = PaymentAccount::join('investments', 'investments.payment_account_id', 'payment_accounts.id')->get();
        $datas['data'] = $user_details->map(function ($item) {
            $item['investment_details'] = $item->investment_details;
            return $item;
        });
        return response([$datas]);
    }
    public function vat(Request $request)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $invoiceCollection = new Collection();

        $invoiceCollection = Invoice::where('exclude_from_vat', '0')->where('approve', 1)->whereBetween('created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->get();
        $expense = Expense::whereBetween('created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->get();
        $data = $invoiceCollection->concat($expense);
        // $data = $data->sortBy('created_at');

        $data && ($datas['data'] = $data->filter(function ($item) {

            if ($item->vat_in_value) {
                $item['type'] = "SALES";
                $item['number'] = $item->invoice_no;
                $item['debit'] = $item->vat_in_value;
                $item['credit'] = null;

                return [$item];
            }
            if ($item->tax) {
                $item['type'] = 'PURCHASE/' . $item->company;
                $item['credit'] = $item->tax;
                $item['number'] = $item->voucher_no;
                $item['debit'] = null;
                return [$item];
            }
            if ($item->account_category_id == 27) {
                $item['type'] = 'VAT';
                $item['credit'] = $item->amount;
                $item['number'] = $item->voucher_no;
                $item['debit'] = null;
                return [$item];
            }

            // return [$item];

        }));
        $datas['opening_balance'] = 0;
        $datas['name'] = "All";
        $datas['from_date'] = $request['from_date'] ? $request['from_date'] : "2021-01-01";
        $datas['to_date'] = $request['to_date'] ? $request['to_date'] : substr(now(), 0, 10);


        return response([$datas]);
    }
    public function responseData()
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $date = "2021-01-01";
        $to_date = now();
        $temp = date('Y-m-d H:i:s');
        $allReceipt = Receipt::join('payment_accounts', 'receipts.div_id', 'payment_accounts.id')->select(
            'payment_accounts.name as div_name',
            'receipts.*'
        )->get();

        $allReceipt->map(function ($receipt) {
            $receipt['credit'] = $receipt->paid_amount;
            return $receipt->party;
        });
        $expenses = $expenses = Expense::join('account_categories', 'expenses.account_category_id', 'account_categories.id')->join('payment_accounts', 'expenses.utilize_div_id', 'payment_accounts.id')->select(
            'payment_accounts.name as paid_from',
            'payment_accounts.name as paid_towards',
            'account_categories.name',
            'expenses.*'
        )->orderBy('created_at', 'DESC')->get();
        $expenses->map(function ($expense) {

            $expense['debit'] = $expense->amount;
            $expense->payment_account;
            return $expense->account_categories;
        });
        $allPayments = AdvancePayment::whereBetween('created_at', [$date . ' ' . '00:00:00', $to_date . ' ' . '23:59:59'])->get();

        $allPayments->map(function ($payment) {
            // $payment['credit']=$payment->amount;
            $payment->receivedBy;
            return $payment->paymentAccount;
        });
        $datas['Receipt'] = $allReceipt;
        $datas['Expense'] = $expenses;
        $datas['Advance'] = $allPayments;


        return response($datas);
    }
    public function vendorStatement1()
    {
        $invoiceCollection = new Collection();

        $invoiceCollection = PurchaseInvoice::join('parties', 'purchase_invoices.party_id', 'parties.id')->select('parties.credit_days', 'purchase_invoices.*')->get();
        //   $invoiceCollection = Quotation::join('parties','quotations.party_id','parties.id')->where('transaction_type','purchase')->select('parties.credit_days','quotations.*')->get();


        $receiptCollection = new Collection();

        $receiptCollection = Expense::where('vendor_id', '!=', 0)->where('is_paid',2)->get();
        //   $receiptCollection = Expense::join('parties','expenses.vendor_id','parties.id')->get();


        $data = $invoiceCollection->concat($receiptCollection);
        $data = $data->sortBy('created_at');

        $data && ($datas['data'] = $data->map(function ($item) {
            if (isset($item->invoice_no)) {
                $item['date'] = $item->created_at;
                $item['code_no'] = $item->invoice_no;
                $item['description'] = "Purchase" . "/" . (isset($item->party) ? $item->party->firm_name : " ");
                $item['debit'] = null;
                $item['credit'] = floatval(str_replace(",", "", $item->total_value));
                //   $item['po_number'] = $item->po_number;
                $item['credit_days'] = floatval(isset($item->party) ? $item->party->credit_days : " ");
                return [$item];
            }

            if ($item->voucher_no) {
                $item['date'] = $item->created_at;
                $item['code_no'] = $item->voucher_no;
                $item['description'] = "Matrial Purchase";
                $item['debit'] = floatval($item->amount);
                $item['party_id'] = floatval($item->vendor_id);
                //   $item['po_number'] = $item->voucher_no;
                $item['credit'] = null;
                $item['credit_days'] = floatval($item->credit_days);
                return [$item];
            }
        }));
        $datas['opening_balance'] = 0;
        $datas['name'] = "All";
        // $datas['from_date'] =  substr(now(), 0, 10);
        // $datas['to_date'] =  substr(now(), 0, 10);
        $datas['from_date'] = "2021-01-01";
        $datas['to_date'] =  substr(now(), 0, 10);

        return response()->json([$datas]);
    }
    public function vendorStatement(Request $request)
    {
        $invoiceCollection = new Collection();

        $invoiceCollection = PurchaseInvoice::join('parties', 'purchase_invoices.party_id', 'parties.id')->select('parties.credit_days', 'purchase_invoices.*')->get();
        //   $invoiceCollection = Quotation::join('parties','quotations.party_id','parties.id')->where('transaction_type','purchase')->select('parties.credit_days','quotations.*')->get();


        $receiptCollection = new Collection();

        $receiptCollection = Expense::where('vendor_id', '!=', 0)->get();
        //   $receiptCollection = Expense::join('parties','expenses.vendor_id','parties.id')->get();


        $data = $invoiceCollection->concat($receiptCollection);
        $data = $data->sortBy('created_at');

        $data && ($datas['data'] = $data->map(function ($item) {
            if (isset($item->invoice_no)) {
                $item['date'] = $item->created_at;
                $item['code_no'] = $item->invoice_no;
                $item['description'] = "Purchase" . "/" . (isset($item->party) ? $item->party->firm_name : " ");
                $item['debit'] = null;
                $item['credit'] = floatval(str_replace(",", "", $item->total_value));
                //   $item['po_number'] = $item->po_number;
                $item['credit_days'] = floatval(isset($item->party) ? $item->party->credit_days : " ");
                return [$item];
            }

            if ($item->voucher_no) {
                $item['date'] = $item->created_at;
                $item['code_no'] = $item->voucher_no;
                $item['description'] = "Matrial Purchase";
                $item['debit'] = floatval($item->amount);
                $item['party_id'] = floatval($item->vendor_id);
                //   $item['po_number'] = $item->voucher_no;
                $item['credit'] = null;
                $item['credit_days'] = floatval($item->credit_days);
                return [$item];
            }
        }));
        $datas['opening_balance'] = 0;
        $datas['name'] = "All";
        $datas['from_date'] = $request['from_date'] ? $request['from_date'] : "2021-01-01";
        $datas['to_date'] = $request['to_date'] ? $request['to_date'] : substr(now(), 0, 10);

        return response()->json([$datas]);
    }

    //Balance Sheet Data
    public function mjrBalanceSheet()
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];

        $paid_div = DivisionController::paidDivision();
        $response_data = $this->responseData();
        $res = InvoiceController::salesTax2();
        $salesExpense = AccountCategoryController::salesExpenseReport();
        $invoices = Invoice::where('approve', 1)->where('status', '!=', 'Delivered')
            ->orderBy('created_at', 'DESC')->get();
        // $result=$invoices->party;
        $invoices->map(function ($invoice) {

            // $invoice->payment_account;
            return $invoice->party;
        });
        return response()->json([

            'paidDivision' => $paid_div->original,
            'responseData' => $response_data->original,
            'salesTax' => $res,
            'salesExpenseReport' => $salesExpense->original,
            'invoice' => $invoices




        ]);
    }

    //Customer Statements
    public static function allCustomerStatement()
    {
        $invoiceCollection = new Collection();

        // if($request->from_date){
        //     $invoiceCollection = Invoice::join('parties','invoices.party_id','parties.id')->select('parties.credit_days','invoices.*')where('invoices.approve',1)->whereBetween('invoices.created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->get();
        // }else{
        $invoiceCollection = Invoice::where('delete_status', '0')->where('approve', 1)->get();
        // }

        $receiptCollection = new Collection();
        // if($request->from_date){
        //     $receiptCollection = Receipt::join('parties','receipts.party_id','parties.id')->select('parties.credit_days','receipts.*')->whereBetween('receipts.created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date. ' ' . '23:59:59' : now()])->get();
        // }else{
        $receiptCollection = Receipt::all();
        // }

        $data = $invoiceCollection->concat($receiptCollection);
        $data = $data->sortBy('created_at');

        $data && ($datas['data'] = $data->map(function ($item) {
            if ($item->total_value) {
                $paid_amount = Receipt::where('invoice_id',$item -> id)->sum('paid_amount');
                $invStatus = floatval(str_replace(",", "", $item->grand_total)) - $paid_amount <= 0 ? true : false;
                $item['date'] = $item->issue_date;
                $item['code_no'] = $item->invoice_no;
                $item['description'] = "Sale" . "/" . (isset($item->party) ? $item->party->firm_name : " ");
                $item['debit'] = floatval(str_replace(",", "", $item->grand_total));
                $item['po_number'] = $item->po_number;
                $item['credit'] = 0;
                $item['invStatus'] = $invStatus;
                $item['credit_days'] = floatval(isset($item->party) ? ($item->party->credit_days) : 0);
                return [$item];
            }

            if ($item->paid_amount) {
                $item['date'] = $item->paid_date;
                $item['code_no'] = $item->receipt_no;
                $item['description'] = "Receipt" . "/" . $item->party->firm_name;
                $item['credit'] = floatval(str_replace(",", "", $item->paid_amount));
                $item['po_number'] = $item->po_number;
                $item['debit'] = 0;
                $item['invStatus'] = '';
                $item['credit_days'] = floatval($item->party->credit_days);
                return [$item];
            }
        }));
        $datas['opening_balance'] = 0;
        $datas['name'] = "All";
        $datas['from_date'] = "2021-01-01";
        $datas['to_date'] =  substr(now(), 0, 10);

        return response()->json([$datas]);
    }

    public static function allCustomerStatement1($did)
    {
        $invoiceCollection = new Collection();

        // if($request->from_date){
        //     $invoiceCollection = Invoice::join('parties','invoices.party_id','parties.id')->select('parties.credit_days','invoices.*')where('invoices.approve',1)->whereBetween('invoices.created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date . ' ' . '23:59:59' : now()])->get();
        // }else{
        $invoiceCollection = Invoice::where('delete_status', '0')->where('div_id',$did)->where('approve', 1)->get();
        // }

        $receiptCollection = new Collection();
        // if($request->from_date){->where('div_id',$did)
        //     $receiptCollection = Receipt::join('parties','receipts.party_id','parties.id')->select('parties.credit_days','receipts.*')->whereBetween('receipts.created_at', [$request->from_date . ' ' . '00:00:00', $request->to_date ? $request->to_date. ' ' . '23:59:59' : now()])->get();
        // }else{
        // $receiptCollection = Receipt::where('division_id',$did)->get();
        // }

        $data = $invoiceCollection->concat($receiptCollection);
        $data = $data->sortBy('created_at');

        $data && ($datas['data'] = $data->map(function ($item) {
            if ($item->total_value) {
                $paidam = Receipt::where('invoice_id',$item->id)->sum('paid_amount');
                $item['paid_amount'] = $item -> grand_total - $paidam;
                $item['date'] = $item->issue_date;
                $item['code_no'] = $item->invoice_no;
                $item['description'] = "Sale" . "/" . (isset($item->party) ? $item->party->firm_name : " ");
                $item['debit'] = floatval(str_replace(",", "", $item->grand_total));
                $item['po_number'] = $item->po_number;
                $item['credit'] = 0;
                $item['credit_days'] = floatval(isset($item->party) ? ($item->party->credit_days) : 0);
                return [$item];
            }

            if ($item->paid_amount) {
                $item['date'] = $item->issue_date;
                $item['code_no'] = $item->receipt_no;
                $item['description'] = "Receipt" . "/" . $item->party->firm_name;
                $item['credit'] = floatval(str_replace(",", "", $item->paid_amount));
                $item['po_number'] = $item->po_number;
                $item['debit'] = 0;
                $item['credit_days'] = floatval($item->party->credit_days);
                return [$item];
            }
        }));
        $datas['opening_balance'] = 0;
        $datas['name'] = "All";
        $datas['from_date'] = "2021-01-01";
        $datas['to_date'] =  substr(now(), 0, 10);

        return response()->json([$datas]);
    }

    public static function mjrCustomerStatement($did)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];


        return response()->json([
            'vendor' => PartyController::customer($did),
            'customerStatement' => self::allCustomerStatement1($did)->original,



        ]);
    }
    public static function mjrCustomerStatement1($did)
    {
        if (!auth()->check())
            return ["You are not authorized to access this API."];


        return response()->json([
            'vendor' => PartyController::customer($did),
            'customerStatement' => self::allCustomerStatement($did)->original,



        ]);
    }
}

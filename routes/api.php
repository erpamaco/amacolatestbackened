<?php

use App\Http\Controllers\Api\AccountCategoryController;
use App\Http\Controllers\Api\AccountStatementController;
use App\Http\Controllers\Api\AdvancePaymentController;
use App\Http\Controllers\Api\AdvancePaymentStatementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductQuotationDetail;
use App\Http\Controllers\Api\PHPMailerController;
use App\Http\Controllers\Api\EncController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RentalEquipmentController;
use App\Http\Controllers\Api\PartyController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\RFQController;
use App\Http\Controllers\Api\RFQDetailsController;
use App\Http\Controllers\Api\AnalyseController;
use App\Http\Controllers\Api\ColumnController;
use App\Http\Controllers\Api\ColumnDataController;
use App\Http\Controllers\Api\QuotationController;
use App\Http\Controllers\Api\RentalQuotationController;
use App\Http\Controllers\Api\QuotationDetailController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleDetailController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\RFQImageController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceDetailController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ManufacturerController;
use App\Http\Controllers\Api\PaymentAccountController;
use App\Http\Controllers\Api\ProductPriceController;
use App\Http\Controllers\Api\PurchaseInvoiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyBankController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DeliveryNoteController;
use App\Http\Controllers\Api\DeliveryNoteDetailController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TaxController;
use App\Http\Controllers\Api\PartyBankController;
use App\Http\Controllers\Api\demo;
use App\Http\Controllers\Api\DivisionController;
use App\Http\Controllers\Api\MasterAccountController;
use App\Http\Controllers\Api\InvestmentsDetailsController;
use App\Http\Controllers\Api\ProfitLossController;
use App\Http\Controllers\Api\UOMController;
use App\Http\Controllers\Api\StackController;

use App\Http\Controllers\Api\PurchaseReturnController;
use App\Http\Controllers\Api\SalesReturnController;
use App\Http\Controllers\Api\EmployeesController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\MobileController;
use App\Http\Controllers\Api\PermissionDeniedController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\LoginLogController;
use App\Http\Controllers\Api\DesignationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\TestController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
    nnnnnnnnnnnnnnnn
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return [$request->user()];
});

// jwt auth links

Route::group(
    [

        'middleware' => 'api',
        'prefix' => 'auth'

    ],
    function ($router) {

        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('me', [AuthController::class, 'me']);
    }
);


// resource api links

Route::apiResource('products',ProductController::class);
Route::apiResource('rental_equipment',RentalEquipmentController::class);
Route::apiResource('parties',PartyController::class);
Route::apiResource('categories',CategoryController::class);
Route::apiResource('rfq',RFQController::class);
Route::apiResource('rfq-details',RFQDetailsController::class);
Route::apiResource('analyse',AnalyseController::class);
Route::apiResource('purchase-quotation',QuotationController::class);
Route::apiResource('sale-quotation',QuotationController::class);
Route::apiResource('rental-sale-quotation',RentalQuotationController::class);
Route::apiResource('quotation-detail',QuotationDetailController::class);
Route::apiResource('sale',SaleController::class);
Route::apiResource('sale-detail',SaleDetailController::class);
Route::apiResource('contact',ContactController::class);
Route::apiResource('fileUpload',FileUploadController::class);
Route::apiResource('invoice',InvoiceController::class);
Route::apiResource('invoice-detail',InvoiceDetailController::class);
Route::apiResource('expense',ExpenseController::class);
Route::apiResource('employee',EmployeeController::class);
Route::apiResource('manufacturer',ManufacturerController::class);
Route::apiResource('product-price',ProductPriceController::class);
Route::apiResource('payment-account',PaymentAccountController::class);
Route::apiResource('purchase-invoice',PurchaseInvoiceController::class);
Route::apiResource('account-categories',AccountCategoryController::class);
Route::apiResource('columns',ColumnController::class);
Route::apiResource('columnDatas',ColumnDataController::class);
Route::apiResource('delivery-notes',DeliveryNoteController::class);
Route::apiResource('delivery-notes-details',DeliveryNoteDetailController::class);
Route::apiResource('receipts', ReceiptController::class);
Route::apiResource('advance-payments', AdvancePaymentController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('party-bank', PartyBankController::class);
Route::apiResource('company', CompanyController::class);
Route::apiResource('company-bank', CompanyBankController::class);
Route::apiResource('demo', demo::class);
Route::apiResource('division', DivisionController::class);
Route::apiResource('stock', StockController::class);
Route::apiResource('designation', DesignationController::class);
Route::apiResource('uom', UOMController::class);
// restful api links
Route::get('allReceiptData',[ReceiptController::class,'allReceiptData']);


Route::get('getAllEmails',[UserController::class,'getAllEmails']);
Route::put('company-bank-update/{id}',[CompanyBankController::class,'company_bank_update']);
Route::get('getAllRentalEquipments',[RentalEquipmentController::class,'EquipmentShow']);
Route::get('getequipfiles/{id}',[RentalEquipmentController::class,'getequipfiles']);
Route::get('getequipfilessingle/{id}',[RentalEquipmentController::class,'getequipfilessingle']);
Route::get('getAvailableRentalEquipments',[RentalEquipmentController::class,'EquipmentAvailable']);
Route::get('getScrapRentalEquipments',[RentalEquipmentController::class,'EquipmentScrap']);
Route::delete('purchaseinvoiceitem/{id}',[PurchaseInvoiceController::class,'deleteInv']);
Route::post('company_edit',[CompanyController::class,'company_edit']);
Route::post('product-price-rental',[ProductPriceController::class,'product_price_rental']);
Route::post('rfq-history', [RFQController::class, 'history'])->name('rfq.history');
Route::post('invoice-history', [InvoiceController::class, 'history'])->name('invoice.history');
Route::post('Invoiceupdate', [InvoiceController::class, 'Invoiceupdate'])->name('invoice.Invoiceupdate');
Route::post('PurchaseInvoiceupdate', [InvoiceController::class, 'PurchaseInvoiceupdate'])->name('invoice.PurchaseInvoiceupdate');
Route::post('PurchaseInvoiceCreate', [InvoiceController::class, 'PurchaseInvoiceCreate'])->name('invoice.PurchaseInvoiceCreate');
Route::post('quotation-history', [QuotationController::class, 'history'])->name('quotation.history');
Route::get('categorized-products/{id}',[CategoryController::class, 'categorized_products'])->name('categorized.products');
Route::get('main-categorized-products/{id}',[CategoryController::class, 'main_categorized_products'])->name('maincategorized.products');
Route::get('quotation-po/',[QuotationController::class, 'invoice_list'])->name('invoice.list');
Route::post('add-user', [UserController::class, 'add'])->name('add.user');
Route::post('upload-file', [RFQImageController::class, 'store'])->name('file.upload');
Route::get('parties-vendor/{id}',[PartyController::class, 'vendor'])->name('parties.vendor');
Route::get('products-in-category',[CategoryController::class, 'products_in_category'])->name('products.in.category');
Route::get('sub-category/{id}', [CategoryController::class, 'subCategory'])->name('subCategory');
Route::get('category/{name}', [CategoryController::class, 'search'])->name('category.name');
Route::get('parties-except/{product}', [PartyController::class, 'allVendorExcept'])->name('except.vendor');
Route::get('product-quotation-detail/{id}', [ProductQuotationDetail::class, 'show'])->name('product.quotationdetail');
Route::get('expense-paid', [ExpenseController::class, 'paid'])->name('expense.paid');
Route::get('customer-list/{id}', [PartyController::class, 'customer'])->name('customer.list');
Route::get('sales-list', [QuotationController::class, 'salesList'])->name('sales.list');
Route::get('all-list', [QuotationController::class, 'AllSalesList'])->name('AllSalesList');
Route::get('purchase-invoice-list',[PurchaseInvoiceController::class, 'purchaseInvoiceList'])->name('purchase.invoice.list');
Route::get('purchaseInvoiceHList',[PurchaseInvoiceController::class, 'purchaseInvoiceHList'])->name('purchase.invoice.list');
Route::get('account-subcategories/{id}', [AccountCategoryController::class, 'subCategory'])->name('account.category.subcategory');
Route::get('account-categories-search/{name}', [AccountCategoryController::class, 'search'])->name('account.category.search');
Route::get('quotations-accepted-list', [QuotationController::class, 'acceptedList'])->name('quotaions.accepted.list');
Route::get('quotations-rejected-list', [QuotationController::class, 'rejectedList'])->name('quotaions.rejected.list');
Route::put('update-quotation/{id}', [QuotationController::class, 'updateQuotation'])->name('quotations.status.update');
Route::post('old-password', [UserController::class, 'oldPassword']);
Route::post('old-password-new', [UserController::class, 'oldPasswordNew']);
Route::post('account-statement', [AccountStatementController::class, 'accountStatement']);
Route::post('mainproducts', [ProductController::class, 'main_products']);
Route::post('all-account-statement', [AccountStatementController::class, 'allAccountStatement']);
Route::post('vendorStatement', [AccountStatementController::class, 'vendorStatement']);
Route::post('all-account-masterstatement', [MasterAccountController::class, 'allAccountmasterStatement']);
Route::post('advance-payment-statement',[AdvancePaymentStatementController::class,'statement']);
Route::post('all-advance-payment-statement',[AdvancePaymentStatementController::class, 'allAdvancePaymentStatement']);
Route::post('rfq-update',[RFQController::class, 'update']);
Route::post('product-update/{id}',[ProductController::class, 'update']);
Route::post('sale-quotation-update',[QuotationController::class, 'update']);
Route::post('purchaseUpdate',[QuotationController::class, 'purchaseUpdate']);
Route::delete('delete-quotation-detail/{quotation_detail}', [QuotationController::class, 'deleteFile']);
Route::delete('delete-sales-return-detail/{id}', [SalesReturnController::class, 'deleteReturnDetail']);
Route::delete('invoiceDelete/{id}/{comment}', [InvoiceController::class, 'destroyNew']);
Route::post('sale-tax', [TaxController::class, 'saleTax']);
Route::post('purchase-tax', [TaxController::class, 'purchaseTax']);
Route::get('all-categories',[CategoryController::class, 'categories']);
Route::delete('quotation_details/{id}',[QuotationController::class, 'destroy_details']);
Route::delete('rfq_details/{id}',[RFQController::class, 'destroy_details']);
Route::post('sale-report',[QuotationController::class, 'saleReport']);
Route::post('invoice-filter',[InvoiceController::class, 'invoiceFilter']);
Route::get('purchase-quote',[PurchaseInvoiceController::class, 'PurchaseInvoice'])->name('purchase.get');
Route::post('expenseUpdate',[ExpenseController::class, 'expenseUpdate']);
Route::get('singleExpenses/{id}', [ExpenseController::class, 'singleExpense']);
Route::get('singleDivision/{id}', [DivisionController::class, 'singleDivision']);
Route::get('singleReceipt/{id}', [ReceiptController::class, 'singleReceipt']);
Route::post('updateReceipt', [ReceiptController::class, 'updateReceipt']);
Route::post('updateQuotestatus', [QuotationController::class, 'updateQuotestatus']);
Route::post('updateAdvancePay', [AdvancePaymentController::class, 'updateAdvancepay']);
Route::post('masterstatement', [MasterAccountController::class, 'masterStatement']);
Route::post('all-account-masterstatement', [MasterAccountController::class, 'allAccountmasterStatement']);
Route::post('accountSummary', [AdvancePaymentStatementController::class, 'accountSummary']);
Route::get('paidDivision', [DivisionController::class, 'paidDivision']);
//  Route::post('all-account-masterstatementvbbbbbbb', [MasterAccountController::class, 'allAccountmasterStatement']);
Route::get('Userstatus/{id}', [UserController::class, 'Userstatus']);
Route::post('Usersprofile', [UserController::class, 'Usersprofile']);
Route::post('Expense_delete_verify', [ExpenseController::class, 'Expense_delete_verify']);
Route::post('partyDelete_all', [PartyController::class, 'partyDelete_all']);
Route::get('expense_chart', [ExpenseController::class, 'expense_chart']);
Route::get('accountCategory', [AccountCategoryController::class, 'accountCategory']);
Route::get('salesTax', [InvoiceController::class, 'salesTax']);
Route::get('invoice-party/{id}', [InvoiceController::class, 'partyInvoices']);
Route::post('invoice-vat-file/{id}/{vat}', [InvoiceController::class, 'invoiceVatFile']);
Route::post('invoice-Status/{id}/{status}', [InvoiceController::class, 'invoiceStatus']);
Route::post('change-invoice-status/{id}/{status}/{type}', [InvoiceController::class, 'changeStatus']);
Route::get('purchaseTax', [ExpenseController::class,'purchaseTax']);
Route::get('salesExpenseReport', [AccountCategoryController::class, 'salesExpenseReport']);
Route::get('profitLoss', [AccountStatementController::class, 'profitLoss']);
Route::post('InvestmentsDetails', [InvestmentsDetailsController::class, 'store']);
Route::post('vat', [AccountStatementController::class, 'vat']);
Route::post('update_company', [QuotationController::class, 'update_company']);
Route::get('responseData', [AccountStatementController::class, 'responseData']);
Route::get('accountcategories/{id}', [AccountCategoryController::class, 'accountcategories']);
Route::put('accountEdit/{id}', [AccountCategoryController::class, 'accountEdit']);
// Invoice delivery note
Route::post('invoce_note', [DeliveryNoteController::class, 'invoce_note']);

//purchase Return API's

Route::delete('purchase-return-delete/{id}', [PurchaseReturnController::class, 'deletepurchasereturn']);
Route::get('getPurchaseReturnINV/{id}', [PurchaseReturnController::class, 'getPurchaseReturnINV']);
Route::get('getPurchaseReturnDetails/{id}', [PurchaseReturnController::class, 'getReturnInv']);
Route::get('purchase-return-data/{id}', [PurchaseReturnController::class, 'index']);
Route::get('getProductsPR/{id}', [PurchaseReturnController::class, 'getProductsPR']);
Route::get('getPurchaseReturnEditData/{id}', [PurchaseReturnController::class, 'getPurchaseReturnEditData']);
Route::get('purchase-return-table', [PurchaseReturnController::class, 'purchaseReturnTableData']);
Route::post('purchase-return', [PurchaseReturnController::class, 'purchasereturn']);
Route::post('purchase-return-update', [PurchaseReturnController::class, 'purchasereturnupdate']);



// sales Return API's
Route::get('sales-return-data/{id}', [SalesReturnController::class, 'salesData']);

Route::get('getSalesFormData/{id}', [SalesReturnController::class, 'index']);
Route::get('getSalesReturnINV/{id}', [SalesReturnController::class, 'getSalesReturnINV']);
Route::get('getSalesReturnEdit/{id}', [SalesReturnController::class, 'getsReturnEditData']);
Route::get('getInvSr/{id}', [SalesReturnController::class, 'getProductsSR']);

Route::get('sales-return-table', [SalesReturnController::class, 'SalesReturnTableData']);

// empoyee Api
Route::post('save-emp', [EmployeesController::class, 'store']);
Route::post('update-emp', [EmployeesController::class, 'update']);
Route::post('update-emp-div', [EmployeesController::class, 'updateDiv']);
Route::get('getEmp/{id}', [EmployeesController::class, 'index']);
Route::get('getEmp', [EmployeesController::class, 'getEmp']);
Route::delete('delete-emp/{id}', [EmployeesController::class, 'destroy']);
Route::get('getAllCat', [CategoryController::class, 'getAllCat']);

//userPermission
Route::post('add-permission', [PermissionDeniedController::class, 'store']);
Route::get('get-modules-per/{id}/{i}', [PermissionDeniedController::class, 'index']);
Route::get('userPermission/{id}', [PermissionDeniedController::class, 'userPermission']);

//Module API's
Route::post('add-module', [ModuleController::class, 'store']);
Route::delete('delete-modules/{id}', [ModuleController::class, 'destroy']);
Route::get('edit-data-modules/{id}', [ModuleController::class, 'edit']);
Route::put('update-module/{id}', [ModuleController::class, 'update']);
Route::get('get-modules/{id}', [ModuleController::class, 'index']);
Route::get('unCategorized-products',[CategoryController::class, 'unCategorized_products']);
Route::get('check/{id}', [StockController::class, 'check']);

// mobile routes 


Route::get('getMCat', [MobileController::class, 'getMCat']);
Route::post('expenceStore', [MobileController::class, 'storeExpence']);
Route::get('divisionbyid/{id}', [DivisionController::class, 'getDivbyId']);


Route::get('getParties/{id}', [PartyController::class, 'getParties']);
Route::get('userActivityLogin', [LoginLogController::class, 'loginActivities']);
Route::get('activityLogs', [LoginLogController::class, 'activityLogs']);

Route::get('newparties/{id}', [PartyController::class, 'getPartyDet']);

Route::post('logoutLog/{id}', [LoginLogController::class, 'logoutLog']);

Route::post('sendOtp', [PHPMailerController::class, 'sendOtp']);
Route::post('change-password', [UserController::class, 'changePasswordF']);
Route::post('signature-app', [UserController::class, 'signatureApp']);
Route::post('signature-prep', [UserController::class, 'signaturePrep']);
Route::get('signature', [UserController::class, 'signature']);
Route::get('expense-invoice-report', [ExpenseController::class, 'expenseInvoiceReport']);

// index wise quote view

Route::get('show_quotation/{id}', [QuotationController::class, 'show_quotation']);
Route::get('quoteHistory', [QuotationController::class, 'quoteHistory']);
Route::get('invoice_delivery_note/{id}/{s}', [DeliveryNoteController::class, 'show']);
Route::post('change-delivery-status/{id}/{s}/{ty}', [DeliveryNoteController::class, 'deliveryStatus']);
Route::get('enc', [EncController::class, 'index']);

// Multi Response


//multi Json responce API's
Route::get('mjrProductAdd/{did}/{cid}', [ProductController::class, 'mjrProductAdd']);
Route::get('mjrProductUpdate/{pid}', [ProductController::class, 'mjrProductUpdate']);

Route::get('findInvoices/{pid}', [ReceiptController::class, 'findInvoices']);

Route::get('mjrPurchaseInvoice/{poid}',[QuotationController::class, 'mjrPurchaseInvoice']);
Route::get('mjrBalanceSheet',[AccountStatementController::class, 'mjrBalanceSheet']);
Route::get('mjrExpense/{did}',[ExpenseController::class, 'mjrExpense']);
Route::get('mjrCustomerStatement/{did}',[AccountStatementController::class, 'mjrCustomerStatement']);
Route::get('mjrCustomerStatement1/{did}',[AccountStatementController::class, 'mjrCustomerStatement1']);
Route::get('mjrExpenseUpdate/{did}/{eid}/{cid}',[ExpenseController::class, 'mjrExpenseUpdate']);
Route::get('mjrQuoteEdit/{did}/{id}', [QuotationController::class, 'mjrQuoteEdit']);
Route::get('mjrQuoteDno/{did}/{id}', [QuotationController::class, 'mjrQuoteDno']);
Route::get('mjrPurchase/{did}/{id}', [QuotationController::class, 'mjrPurchase']);
Route::get('mjrQuoteInc/{did}', [QuotationController::class, 'mjrQuoteInc']);
Route::get('rentalmjrQuoteInc/{did}', [RentalQuotationController::class, 'rentalmjrQuoteInc']);
Route::get('mjrInvInc/{did}', [InvoiceController::class, 'mjrInvInc']);
Route::get('mjrEditInc/{did}/{id}', [InvoiceController::class, 'mjrEditInc']);
Route::get('mjrRfqInc/{did}', [RFQController::class, 'mjrRfqInc']);
Route::get('mjrRfqEdit/{did}/{id}', [RFQController::class, 'mjrRfqEdit']);
Route::get('mjrPurchaseEdit/{did}/{id}', [PurchaseInvoiceController::class, 'mjrPurchaseEdit']);

Route::get('mjrSalesReturnInc/{did}', [SalesReturnController::class, 'mjrSalesReturnInc']);
Route::get('mjrSalesReturnEdit/{did}/{id}', [SalesReturnController::class, 'mjrSalesReturnEdit']);


Route::get('mjrPurchaseReturnInc/{did}', [PurchaseReturnController::class, 'mjrPurchaseReturnInc']);
Route::get('mjrPurchaseReturnEdit/{did}/{id}', [PurchaseReturnController::class, 'mjrPurchaseReturnEdit']);
Route::get('mjrCategory', [CategoryController::class, 'mjrCategory']);
Route::get('stateCard', [StackController::class, 'stateCard']);
Route::get('dashboard', [StackController::class, 'dashboard']);
Route::get('getNotifications', [StackController::class, 'getNotifications']);
Route::get('vendorStatementNew/{id}', [AccountStatementController::class, 'vendorStatementNew']);

Route::delete('rfqdelete/{id}', [RFQController::class, 'rfqdelete']);
Route::put('rfqRecover/{id}', [RFQController::class, 'rfqRecover']);
Route::put('pInvRec/{id}', [PurchaseInvoiceController::class, 'pInvRec']);
Route::delete('deletePurInv/{id}', [PurchaseInvoiceController::class, 'deletePurInv']);
Route::delete('deletePr/{id}', [PurchaseReturnController::class, 'deletePr']);
Route::put('restorePr/{id}', [PurchaseReturnController::class, 'restorePr']);

Route::delete('deleteSinv/{id}', [InvoiceController::class, 'deleteSinv']);
Route::post('updateApproveRejectStatus/{id}', [InvoiceController::class, 'updateStatus']);
Route::put('restoreSInv/{id}/{div}', [InvoiceController::class, 'restoreSInv']);


Route::get('getPONo/{date}', [PurchaseReturnController::class, 'getPurchaseReturnEditData']);
Route::get('checkVerifyParty/{id}', [PartyController::class, 'checkVerifyParty']);
Route::put('verifyParty/{id}', [PartyController::class, 'verifyParty']);


Route::post('resetNotification', [NotificationController::class, 'resetNotification']);
Route::get('notification', [NotificationController::class, 'sendNotification']);
Route::delete('clearNotification/{d}', [NotificationController::class, 'clearNotification']);
Route::get('dummy/{a}/{b}/{c}', [DeliveryNoteController::class, 'getDeliveryNumber']);
Route::get('validationParty', [PartyController::class, 'validationParty']);
Route::post('deleveryPrep/{d}/{t}/{id}', [DeliveryNoteController::class, 'deleveryPrep']);
Route::post('deleveryUpdate', [DeliveryNoteController::class, 'deleveryUpdate']);
Route::get('getDeliveryNoteEdit/{id}', [DeliveryNoteController::class, 'getDeliveryNoteEdit']);
Route::get('dDetails', [DeliveryNoteController::class, 'dDetails']);




Route::get('test/{date}/{div}', [QuotationController::class, 'getPONo']);

Route::post('test', [TestController::class, 'scanFile']);


// Route::get('/notification', 'PusherNotificationController@sendNotification');




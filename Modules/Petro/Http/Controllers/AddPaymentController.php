<?php

namespace Modules\Petro\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountType;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\CustomerReference;
use App\ExpenseCategory;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\Settlement;
use App\Utils\Util;
use App\Utils\ProductUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
;
use Modules\Petro\Entities\DailyCollection;
use Modules\Petro\Entities\MeterSale;
use Modules\Petro\Entities\OtherIncome;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Petro\Entities\SettlementExcessPayment;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\SettlementShortagePayment;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\DailyVoucherItem;

use Modules\Petro\Entities\SettlementCustomerLoan;

use App\Http\Controllers\ContactController;

use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Modules\Petro\Entities\DailyCard;
use Modules\Petro\Entities\DailyChequePayment;
use Modules\Superadmin\Entities\Subscription;
use Modules\Petro\Entities\PumpOperatorPayment;
use Modules\Petro\Entities\PumpOperatorOtherSale;

class AddPaymentController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;

    private $barcode_types;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('petro::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    
    public function addDailyCards($settlement_no,$pump_operator_id,$business_id){
        $daily_cards = DailyCard::where('business_id',$business_id)
                                ->where('pump_operator_id',$pump_operator_id)
                                ->whereNUll('used_status')
                                ->get();
        if(!empty($daily_cards)){
            foreach($daily_cards as $daily_card){
                $card = DailyCard::findOrFail($daily_card->id);
                
                $data = array(
                    'business_id' => $business_id,
                    'settlement_no' => $settlement_no,
                    'amount' => $daily_card->amount,
                    'card_type' => $daily_card->card_type,
                    'card_number' => $daily_card->card_number,
                    'customer_id' => $daily_card->customer_id,
                    'note' => $daily_card->note,
                    'slip_no' => $daily_card->slip_no
                );
    
                $settlement_card_payment = SettlementCardPayment::create($data);
                
                $card->used_status = 1;
                $card->settlement_no = $settlement_no;
                $card->save();
            }
        }
        
        // update the credit_nos
        SettlementCreditSalePayment::whereNull('settlement_no')->where('pump_operator_id',$pump_operator_id)->update(['settlement_no' => $settlement_no]);
        
        $pending_vouchers = DailyVoucher::where('business_id',$business_id)->whereNull('settlement_no')->get();
        $action = false;
        foreach($pending_vouchers as $voucher){
            
            $settlement_no = SettlementCreditSalePayment::where('order_number',$voucher->voucher_order_number)->where('order_date',$voucher->voucher_order_date)->where('pump_operator_id',$voucher->operator_id)->first()->settlement_no ?? NULL;
            $exists = SettlementCreditSalePayment::where('order_number',$voucher->voucher_order_number)->where('order_date',$voucher->voucher_order_date)->where('pump_operator_id',$voucher->operator_id)->count();
            
            if($exists > 0){
                $voucher->settlement_no = $settlement_no;
                $voucher->save();
            }else{
                $item = DailyVoucherItem::where('daily_voucher_id',$voucher->id)->first();
                
                $dt = array(
                    'business_id' => $business_id,
                    'pump_operator_id' => $voucher->operator_id,
                    'customer_id' => $voucher->customer_id,
                    'product_id' => $item->product_id,
                    'order_number' => $voucher->voucher_order_number,
                    'order_date' => $voucher->voucher_order_date,
                    'price' => $item->unit_price,
                    'discount' => 0,
                    'qty' => $item->qty,
                    'amount' => $voucher->total_amount,
                    'sub_total' => $voucher->total_amount,
                    'total_discount' => 0,
                    'outstanding' => $voucher->current_outstanding,
                    'credit_limit' => null,
                    'customer_reference' => $voucher->vehicle_no
                );
                $credit_sale_payment = SettlementCreditSalePayment::create($dt);
                $action = true;
            }
            
            
        }
        
        if(!empty($action)){
            $this->addDailyCards($settlement_no,$pump_operator_id,$business_id);
        }
        
        
    }
    
    public function addDailyCheques($settlement_no, $pump_operator_id,$business_id){
        $pending = DailyChequePayment::join('petro_shifts','petro_shifts.id','daily_cheque_payments.shift_id')
                                    ->join('pump_operator_payments','pump_operator_payments.id','daily_cheque_payments.linked_payment_id')
                                    ->where('petro_shifts.pump_operator_id',$pump_operator_id)
                                    ->whereNull('daily_cheque_payments.settlement_no')
                                    ->select('daily_cheque_payments.*')
                                    ->get();
                                    
        foreach($pending as $one){
            
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement_no,
                'amount' => $one->amount,
                'bank_name' => $one->bank_name,
                'cheque_number' => $one->cheque_number,
                'cheque_date' => $one->cheque_date,
                'customer_id' => $one->customer_id
            );

            $settlement_cheque_payment = SettlementChequePayment::create($data);
            
            $pmt = PumpOperatorPayment::findOrFail($one->linked_payment_id);
            $pmt->is_used = 1;
            $pmt->parent_id = $settlement_cheque_payment->id;
            $pmt->settlement_no = $settlement_no;
            $pmt->save();
            
            $one->settlement_no = $settlement_no;
            $one->save();
        }
        
        
        return true;
        
    }
    
    public function addDailyShortageExcess($settlement_no,$pump_operator_id,$business_id){
        $daily_shortage_excess = PumpOperatorPayment::where('business_id',$business_id)
                                ->where('pump_operator_id',$pump_operator_id)
                                ->where(function($query){
                                    $query->whereNUll('is_used')->orWhere('is_used',0);
                                })
                                ->whereIn('payment_type',['shortage','excess'])
                                ->get();
        
        $settlement = Settlement::findOrFail($settlement_no);
        
        $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);
        
        if(!empty($daily_shortage_excess)){
            foreach($daily_shortage_excess as $daily_shortage_excess){
                $shortage_excess = PumpOperatorPayment::findOrFail($daily_shortage_excess->id);
                
                if($daily_shortage_excess->payment_type == 'shortage'){
                     $data = array(
                        'business_id' => $business_id,
                        'settlement_no' => $settlement->id,
                        'amount' => $daily_shortage_excess->payment_amount,
                        'current_shortage' => $pump_operator->short_amount
                    );
        
                    $parent_payment = SettlementShortagePayment::create($data);
                }
                
                if($daily_shortage_excess->payment_type == 'excess'){
                    $data = array(
                        'business_id' => $business_id,
                        'settlement_no' => $settlement->id,
                        'amount' => $daily_shortage_excess->payment_amount,
                        'current_excess' => $pump_operator->excess_amount,
                    );
                    if(request()->amount > 0){
                        continue;
                    }
        
                    $parent_payment = SettlementExcessPayment::create($data);
                }
                
                
                $shortage_excess->is_used = 1;
                $shortage_excess->parent_id = $parent_payment->id;
                $shortage_excess->settlement_no = $settlement_no;
                $shortage_excess->save();
            }
        }
        
       
    }
    
    public function create(Request $request)
    {
        $business_id = request()->session()->get('business.id');
        $business = Business::where('id', $business_id)->first();
        $pos_settings = json_decode($business->pos_settings,true);
        $cash_denoms = !empty($pos_settings['cash_denominations']) ? explode(',',$pos_settings['cash_denominations']) : array();

        $settlement_no = $request->settlement_no;

        $pump_operator_id = $request->operator_id;
    
        $settlement = Settlement::where('settlement_no', $settlement_no)->where('business_id', $business_id)->first();
        
        // prefill the added card nummbers in daily collection
        $this->addDailyCards($settlement->id,$pump_operator_id,$business_id);
        $this->addDailyCheques($settlement->id,$pump_operator_id,$business_id);
        
        $this->addDailyShortageExcess($settlement->id,$pump_operator_id,$business_id);

        $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();

        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location = current(array_keys($business_locations->toArray()));
        $payment_types = $this->productUtil->payment_types($default_location,false, false, false, false,"is_sale_enabled");

        $expense_no = $this->getExpenseNumber($settlement->id);
        $expense_categories = ExpenseCategory::where('business_id', $business_id)
            ->pluck('name', 'id');
        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();
        $expense_accounts = [];
        if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account')) {
            if (!empty($expense_account_type_id)) {
                $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');
            }
        }
        
        $subscription = Subscription::current_subscription($business_id);
        $package_details = $subscription->package_details;
        
        $only_walkin = $package_details['only_walkin'] ?? 0;
        
        if(!empty($only_walkin)){
            $credit_customers = Contact::customersDropdown($business_id, false, true, 'customer');
        }else{
            $credit_customers = Contact::where('name','!=','Walk-In Customer')->where('type','customer')->where('business_id', $business_id)->pluck('name','id');
        }
        
        $customers = Contact::customersDropdown($business_id, false, true, 'customer');
        
        $walkin = Contact::where('name','Walk-In Customer')->where('business_id', $business_id)->pluck('name','id');
        $products = Product::where('business_id', $business_id)->forModule('petro_settlements')->pluck('name', 'id');

        $card_types = [];
        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (!empty($card_group)) {
            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        $customer_payments_tab = CustomerPayment::leftjoin('contacts', 'customer_payments.customer_id', 'contacts.id')
            ->where('customer_payments.settlement_no', $settlement->id)
            ->select('customer_payments.*', 'contacts.name as customer_name')
            ->get();

        $settlement_cash_payments = SettlementCashPayment::leftjoin('contacts', 'settlement_cash_payments.customer_id', 'contacts.id')
            ->where('settlement_cash_payments.settlement_no', $settlement->id)
            ->select('settlement_cash_payments.*', 'contacts.name as customer_name')
            ->get();
            
        $settlement_customer_loans = SettlementCustomerLoan::leftjoin('contacts', 'settlement_customer_loans.customer_id', 'contacts.id')
            ->where('settlement_customer_loans.settlement_no', $settlement->id)
            ->select('settlement_customer_loans.*', 'contacts.name as customer_name')
            ->get();
            
            
        $settlement_loan_payments = SettlementLoanPayment::leftjoin('accounts','accounts.id','settlement_loan_payments.loan_account')->where('settlement_loan_payments.settlement_no', $settlement->id)
            ->select('settlement_loan_payments.*','accounts.name as loan_account_name')
            ->get();
            
        $settlement_drawings_payments = SettlementDrawingPayment::leftjoin('accounts','accounts.id','settlement_drawing_payments.loan_account')->where('settlement_drawing_payments.settlement_no', $settlement->id)
            ->select('settlement_drawing_payments.*','accounts.name as loan_account_name')
            ->get();
            
            
        $settlement_card_payments = SettlementCardPayment::leftjoin('contacts', 'settlement_card_payments.customer_id', 'contacts.id')
            ->leftjoin('accounts', 'settlement_card_payments.card_type', 'accounts.id')
            ->where('settlement_card_payments.settlement_no', $settlement->id)
            ->select('settlement_card_payments.*', 'contacts.name as customer_name', 'accounts.name as card_type')
            ->get();
            
        $settlement_cash_deposits = SettlementCashDeposit::leftjoin('accounts', 'settlement_cash_deposits.bank_id', 'accounts.id')
            ->where('settlement_cash_deposits.settlement_no', $settlement->id)
            ->select('settlement_cash_deposits.*', 'accounts.name as bank_name')
            ->get();
        $settlement_cheque_payments = SettlementChequePayment::leftjoin('contacts', 'settlement_cheque_payments.customer_id', 'contacts.id')
            ->where('settlement_cheque_payments.settlement_no', $settlement->id)
            // ->where('settlement_cheque_payments.business_id', $business_id)
            ->select('settlement_cheque_payments.*', 'contacts.name as customer_name')
            ->get();
        $settlement_credit_sale_payments = SettlementCreditSalePayment::leftjoin('contacts', 'settlement_credit_sale_payments.customer_id', 'contacts.id')
            ->leftjoin('products', 'settlement_credit_sale_payments.product_id', 'products.id')
            ->where('settlement_credit_sale_payments.settlement_no', $settlement->id)
            ->select('settlement_credit_sale_payments.*', 'contacts.name as customer_name', 'products.name as product_name')
            ->get();
        $settlement_expense_payments = SettlementExpensePayment::leftjoin('accounts', 'settlement_expense_payments.account_id', 'accounts.id')
            ->leftjoin('expense_categories', 'settlement_expense_payments.category_id', 'expense_categories.id')
            ->where('settlement_expense_payments.settlement_no', $settlement->id)
            ->select('settlement_expense_payments.*', 'accounts.name as account_name', 'expense_categories.name as category_name')
            ->get();
        $settlement_shortage_payments = SettlementShortagePayment::where('settlement_shortage_payments.settlement_no', $settlement->id)
            ->select('settlement_shortage_payments.*')
            ->get();
        $settlement_excess_payments = SettlementExcessPayment::where('settlement_excess_payments.settlement_no', $settlement->id)
            ->select('settlement_excess_payments.*')
            ->get();
        /**
         * @ChangedBy Afes
         * @Date 25-05-2021
         * @Date 02-06-2021
         * @Task 12700
         * @Task 127004
         */
        $total_daily_collection = floatval(DailyCollection::where('pump_operator_id', $pump_operator_id)->where('business_id', $business_id)->whereNull('settlement_id')->sum('current_amount'));

        /**
         * @ModifiedBy Afes Oktavianus
         * @Date 02-06-2021
         * @Date 03-06-2021
         * @Task 127004
         */
        $total_excess = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'excess');

        $total_shortage = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'shortage');
        
        $operator_bal = $this->transactionUtil->getPumpOperatorBalance($pump_operator_id);

        $total_commission = $this->calculateCommission($pump_operator_id, $settlement->id);
        
        $business_details = Business::find($business_id);
        $currency_precision = $business_details->currency_precision;

        $total_meter_sale = MeterSale::where('settlement_no', $settlement->id)->sum('discount_amount');
        $total_other_sale = OtherSale::where('settlement_no', $settlement->id)->sum('sub_total');
        $total_other_sale_discount = OtherSale::where('settlement_no', $settlement->id)->sum('discount_amount');
        
        $total_other_sale -= $total_other_sale_discount;

        if($request->shift_ids){
            $shift_ids = explode(",", $request->shift_ids);
            $pump_operator_total_other_sale = PumpOperatorOtherSale::join('products', 'products.id', '=', 'pump_operator_other_sales.product_id')
            ->leftJoin('variations', 'products.id', 'variations.product_id')
            ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
            ->whereIn('pump_operator_other_sales.shift_id', $shift_ids);
            $pump_operator_total_other_sale = $pump_operator_total_other_sale->join('pump_operator_assignments', function ($join) {
                $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')
                    ->where('pump_operator_assignments.status', 'close')
                    ->whereRaw('pump_operator_assignments.id = (
                        SELECT MAX(poa.id)
                        FROM pump_operator_assignments poa
                        WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"
                    )');
            });
            $pump_operator_total_other_sale = $pump_operator_total_other_sale->sum("sub_total");
            $total_other_sale = $total_other_sale + $pump_operator_total_other_sale;
        }
        
        $total_other_income = OtherIncome::where('settlement_no', $settlement->id)->sum('sub_total');
        $total_customer_payment = CustomerPayment::where('settlement_no', $settlement->id)->sum('sub_total');

        $total_amount = number_format(($total_meter_sale + $total_other_sale + $total_other_income + $total_customer_payment), $currency_precision, '.', '');

        $total_settlement_cash_payment = SettlementCashPayment::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_loan_payment = SettlementLoanPayment::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_drawings_payment = SettlementDrawingPayment::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_cash_deposit = SettlementCashDeposit::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_card_payment = SettlementCardPayment::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_customer_loan = SettlementCustomerLoan::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_cheque_payment = SettlementChequePayment::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_credit_sale_payment = SettlementCreditSalePayment::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_credit_sale_discount = SettlementCreditSalePayment::where('settlement_no', $settlement->id)->sum('total_discount');
        $total_settlement_expense_payment = SettlementExpensePayment::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_shortage_payment = SettlementShortagePayment::where('settlement_no', $settlement->id)->sum('amount');
        $total_settlement_excess_payment = SettlementExcessPayment::where('settlement_no', $settlement->id)->sum('amount');
        $total_paid = number_format(($total_settlement_customer_loan+
                                    $total_settlement_loan_payment + 
                                    $total_settlement_cash_deposit + 
                                    $total_daily_collection + 
                                    $total_settlement_cash_payment + 
                                    $total_settlement_card_payment + 
                                    $total_settlement_cheque_payment + 
                                    $total_settlement_credit_sale_payment - $total_settlement_credit_sale_discount + 
                                    $total_settlement_expense_payment + 
                                    $total_settlement_shortage_payment + 
                                    $total_settlement_excess_payment +
                                    $total_settlement_drawings_payment
                    ), $currency_precision, '.', '');
        
        $total_balance = number_format($total_amount - $total_paid, $currency_precision, '.', '');
        $total_balance = abs($total_balance);

        $loans_given_group_id = AccountGroup::getGroupByName('Loans Given');
        $drawings_group_id = AccountGroup::getGroupByName('Owners Drawings');
        
        $bank_account_group_id = AccountGroup::getGroupByName('Bank Account');
        $bank_accounts = Account::where('business_id', $business_id)->where('asset_type', $bank_account_group_id->id)->pluck('name','id');
        
        if(!empty($loans_given_group_id)){
            $loans_given = Account::where('business_id', $business_id)->where('asset_type', $loans_given_group_id->id)->pluck('name','id');   
        }else{
            $loans_given = array();
        }
        
        if(!empty($drawings_group_id)){
            $drawings_acc = Account::where('business_id', $business_id)->where('asset_type', $drawings_group_id->id)->pluck('name','id');   
        }else{
            $drawings_acc = array();
        }
        
    
        $message = $package_details['notsubscribed_message_content'];
        $font_family = $package_details['ns_font_family'];;
        $font_color = $package_details['ns_font_color'];;
        $font_size = $package_details['ns_font_size'];;
        $background_color = $package_details['ns_background_color'];;
        
        $message = !empty($message) ? $message : "You have not subscribed to this module!";
        
        return view('petro::settlement.partials.add_payment')->with(compact(
            'operator_bal',
            
            'message','font_family','font_color','font_size','background_color','package_details',
            'bank_accounts',
            'total_settlement_cash_deposit',
            'settlement',
            'cash_denoms',
            'pump_operator',
            'customer_payments_tab',
            'settlement_cash_payments',
            'settlement_customer_loans',
            'settlement_loan_payments',
            'settlement_drawings_payments',
            'settlement_card_payments',
            'settlement_cheque_payments',
            'settlement_credit_sale_payments',
            'settlement_expense_payments',
            'settlement_shortage_payments',
            'settlement_excess_payments',
            'settlement_cash_deposits',
            'payment_types',
            'expense_accounts',
            'expense_categories',
            'expense_no',
            'customers',
            'products',
            'card_types',
            'total_daily_collection',
            'total_commission',
            'total_amount',
            'total_paid',
            'total_balance',
            'total_excess',
            'total_shortage',
            'loans_given',
            'drawings_acc',
            'only_walkin',
            'walkin',
            'credit_customers'
        ));
    }

    public function getExpenseNumber($settlement_id)
    {
        $settlement_int = preg_match_all('!\d+!', $settlement_id, $matches);
        $ref_no_prefixes = request()->session()->get('business.ref_no_prefixes');
        $expense_prefix =   !empty($ref_no_prefixes['expense']) ? $ref_no_prefixes['expense'] : '';
        $expense_count = SettlementExpensePayment::where('settlement_no', $settlement_id)->count();
        $expense_no = $expense_prefix . '-' . $settlement_int . '-' . ($expense_count + 1);

        return $expense_no;
    }

    /**
     * Store a newly created resource in storage.
     * @param  pump_operator_id
     * @param  settlement_no
     * @return Response
     */
    public function calculateCommission($pump_operator_id, $settlement_id)
    {
        $all_sales = OtherSale::where('settlement_no', $settlement_id)->get();

        $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();
        $pump_operator_commission_type = isset($pump_operator->commission_type) ? $pump_operator->commission_type : '';
        $pump_operator_commission_value = isset($pump_operator->commission_ap) ? $pump_operator->commission_ap : 0;

        if ($pump_operator_commission_type == 'fixed') {
            $total_sales_counts = $all_sales->count();

            return $total_sales_counts * $pump_operator_commission_value;
        } elseif ($pump_operator_commission_type == 'percentage') {
            $total_sales_commission = 0;


            return $total_sales_commission;
        } else {
            return 0.00;
        }
    }
    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        // return view('petro::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        // return view('petro::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }


    /**
     * add cash payment data to db
     * @return Response
     */
    public function saveCashPayment(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $request->amount,
                'customer_id' => $request->customer_id,
                'note' => $request->note
            );
            
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);
   
            $settlement_cash_payment = SettlementCashPayment::create($data);

            $output = [
                'success' => true,
                'settlement_cash_payment_id' => $settlement_cash_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
   public function saveCustomerLoan(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $request->amount,
                'customer_id' => $request->customer_id,
                'note' => $request->note
            );
            
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);
   
            $settlement_cash_payment = SettlementCustomerLoan::create($data);

            $output = [
                'success' => true,
                'settlement_customer_loan_id' => $settlement_cash_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
    public function saveLoanPayment(Request $request)
    {
        try {

            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $request->amount,
                'loan_account' => $request->loan_account,
                'note' => $request->note
            );

            $settlement_loan_payment = SettlementLoanPayment::create($data);
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [
                'success' => true,
                'settlement_loan_payment_id' => $settlement_loan_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
    public function saveDrawingPayment(Request $request)
    {
        try {

            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $request->amount,
                'loan_account' => $request->loan_account,
                'note' => $request->note
            );

            $settlement_loan_payment = SettlementDrawingPayment::create($data);
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [
                'success' => true,
                'settlement_loan_payment_id' => $settlement_loan_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
    public function saveCashDeposit(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            
           
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $request->cash_deposit_amount,
                'bank_id' => $request->bank_id,
                'account_no' => $request->account,
                'time_deposited' => $request->time
            );

            $settlement_cash_payment = SettlementCashDeposit::create($data);
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [
                'success' => true,
                'settlement_cash_payment_id' => $settlement_cash_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * delete cash payment data to db
     * @return Response
     */
    public function deleteCashPayment($id)
    {
        try {
            $payment = SettlementCashPayment::where('id', $id)->first();
            
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
    public function deleteCustomerLoan($id)
    {
        try {
            $payment = SettlementCustomerLoan::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
    
    public function deleteLoanPayment($id)
    {
        try {
            $payment = SettlementLoanPayment::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
    public function deleteDrawingPayment($id)
    {
        try {
            $payment = SettlementDrawingPayment::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
    
    public function deleteCashDeposit($id)
    {
        try {
            $payment = SettlementCashDeposit::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
    /**
     * add card payment data to db
     * @return Response
     */
    public function saveCardPayment(Request $request)
    {
        try {
            $slip_no = trim(str_replace(' ', '', $request->slip_no));
            $today = \Carbon::now()->format('Y-m-d');
            $existingRecord = SettlementCardPayment::where('slip_no', $slip_no)
                                ->whereDate('created_at', $today)
                                ->exists();
            if (!empty($slip_no) && $existingRecord) {
               $output = [
                    'success' => false,
                    'msg' => __('messages.duplicate_slip')
                ];
                
                return $output;
            }
            
            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $request->amount,
                'card_type' => $request->card_type,
                'card_number' => $request->card_number,
                'customer_id' => $request->customer_id,
                'note' => $request->note,
                'slip_no' => $slip_no
            );

            $settlement_card_payment = SettlementCardPayment::create($data);
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [
                'success' => true,
                'settlement_card_payment_id' => $settlement_card_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * delete card payment data to db
     * @return Response
     */
    public function deleteCardPayment($id)
    {
        try {
            $payment = SettlementCardPayment::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * add cheque payment data to db
     * @return Response
     */
    public function saveChequePayment(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $request->amount,
                'bank_name' => $request->bank_name,
                'cheque_number' => $request->cheque_number,
                'cheque_date' => \Carbon::parse($request->cheque_date)->format('Y-m-d'),
                'customer_id' => $request->customer_id,
                'note' => $request->note,
                'post_dated_cheque' => $request->post_dated_cheque
            );

            $settlement_cheque_payment = SettlementChequePayment::create($data);
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [
                'success' => true,
                'settlement_cheque_payment_id' => $settlement_cheque_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * delete cheque payment data to db
     * @return Response
     */
    public function deleteChequePayment($id)
    {
        try {
            $payment = SettlementChequePayment::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

    /**
     * add credit_sale payment data to db
     * @return Response
     */
    public function saveCreditSalePayment(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');
            
            if(!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'same_order_no')){
                $validator = Validator::make($request->all(), [
                    'order_number' => [
                        'required',
                        Rule::unique('settlement_credit_sale_payments', 'order_number')
                            ->where(function ($query) use ($request) {
                                return $query->where('customer_id', $request->customer_id);
                            }),
                    ],
                ]);
            
                if ($validator->fails()) {
                    return [
                                'success' => false,
                                'msg' => $validator->errors()->first()
                            ];
                }
            }
            
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);
            
            
            $price = $this->productUtil->num_uf($request->price);
            $unit_discount = $this->productUtil->num_uf($request->unit_discount);
            $qty = $this->productUtil->num_uf($request->qty);
            $amount = $this->productUtil->num_uf($request->amount);
            $sub_total = $this->productUtil->num_uf($request->sub_total);
            $total_discount = $this->productUtil->num_uf($request->total_discount);
            
            
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'customer_id' => $request->customer_id,
                'product_id' => $request->product_id,
                'order_number' => $request->order_number,
                'order_date' => \Carbon::parse($request->order_date)->format('Y-m-d'),
                'price' => $price,
                'discount' => $unit_discount,
                'qty' => $qty,
                'amount' => $amount,
                'sub_total' => $sub_total,
                'total_discount' => $total_discount,
                'outstanding' => $this->productUtil->num_uf($request->outstanding),
                'credit_limit' => $request->credit_limit,
                'customer_reference' => $request->customer_reference,
                'note' => $request->note
            );
            $settlement_credit_sale_payment = SettlementCreditSalePayment::create($data);

            $output = [
                'success' => true,
                'settlement_credit_sale_payment_id' => $settlement_credit_sale_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * delete credit_sale payment data to db
     * @return Response
     */
    public function deleteCreditSalePayment($id)
    {
        try {
            $payment = SettlementCreditSalePayment::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            $amount = $payment->amount;
            $discount = $payment->total_discount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount-$discount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * get price of product
     * @return Response
     */
    public function getProductPrice(Request $request)
    {
        $product_id =  $request->product_id;
        $product = Product::leftjoin('variations', 'products.id', 'variations.product_id')
            ->where('products.id', $product_id)
            ->select('sell_price_inc_tax')
            ->first();
        if (!empty($product)) {
            $price = $product->sell_price_inc_tax;
        } else {
            $price = 0.00;
        }
        return ['price' => $price];
    }
    /**
     * get price of product
     * @return Response
     */
    public function getCustomerDetails($customer_id, ContactController $contactController)
    {
        $business_id = request()->session()->get('business.id');
        $query = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->leftjoin('contact_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
            ->where('contacts.business_id', $business_id)
            ->where('contacts.id', $customer_id)
            // ->onlyCustomers()
            ->select([
                'contacts.vat_number','contacts.contact_id', 'contacts.name', 'contacts.created_at', 'total_rp', 'cg.name as customer_group', 'city', 'state', 'country', 'landmark', 'mobile', 'contacts.id', 'is_default','contacts.sub_customers',
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'advance_payment', -1*final_total, 0)) as advance_payment"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                'email', 'tax_number', 'contacts.pay_term_number', 'contacts.pay_term_type', 'contacts.credit_limit', 'contacts.custom_field1', 'contacts.custom_field2', 'contacts.custom_field3', 'contacts.custom_field4', 'contacts.type'
            ])
            ->groupBy('contacts.id')->first();
        $due = 0;
        $return_due = 0;
        $opening_balance = 0;
            
        if(!empty($query)){
            $due = $query->total_invoice - $query->invoice_received + $query->advance_payment;
            $return_due = $query->total_sell_return - $query->sell_return_paid;
            $opening_balance = $query->opening_balance - $query->opening_balance_paid;
        }
        

        $total_outstanding =  $due -  $return_due + $opening_balance ;
        if (empty($total_outstanding)) {
            $total_outstanding = 0.00;
        }
        if (empty($query->credit_limit)) {
            $credit_limit = 'No Limit';
        } else {
            $credit_limit = $query->credit_limit;
        }
        $business_details = Business::find($business_id);
        $customer_references = CustomerReference::where('contact_id', $customer_id)->where('business_id', $business_id)->select('reference')->get();
        
        $sub_ids = json_decode($query->sub_customers) ?? [];
        $sub_customers = Contact::whereIn('id',$sub_ids)->pluck('name','id');
        
        

        // return ['total_outstanding' =>  strval($this->productUtil->num_f($total_outstanding, false, $business_details, true)), 'credit_limit' => strval($credit_limit), 'customer_references' => $customer_references];
        return ['total_outstanding' =>  $this->productUtil->num_f(strval($contactController->get_cus_due_bal($customer_id, false))), 'credit_limit' => strval($credit_limit), 'customer_references' => $customer_references,'sub_customers' => $sub_customers, 'vat_number' => $query->vat_number];
    }

    /**
     * add expense payment data to db
     * @return Response
     */
    public function saveExpensePayment(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'expense_number' => $request->expense_number,
                'category_id' => $request->category_id,
                'reference_no' => $request->reference_no,
                'account_id' => $request->account_id,
                'reason' => $request->reason,
                'amount' => $request->amount,
            );

            //Update reference count
            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');
            //Generate reference number
            if (empty($request->reference_no)) {
                $data['reference_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);
            }

            $settlement_expense_payment = SettlementExpensePayment::create($data);
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);

            $expense_number = $this->getExpenseNumber($request->settlement_no);

            $output = [
                'success' => true,
                'expense_number' => $expense_number,
                'reference_no' => $settlement_expense_payment->reference_no,
                'settlement_expense_payment_id' => $settlement_expense_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * delete expense payment data to db
     * @return Response
     */
    public function deleteExpensePayment($id)
    {
        try {
            $payment = SettlementExpensePayment::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * add shortage payment data to db
     * @return Response
     */
    public function saveShortagePayment(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $request->amount,
                'current_shortage' => $pump_operator->short_amount,
                'note' => $request->note
            );

            $settlement_shortage_payment = SettlementShortagePayment::create($data);
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [
                'success' => true,
                'settlement_shortage_payment_id' => $settlement_shortage_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * delete shortage payment data to db
     * @return Response
     */
    public function deleteShortagePayment($id)
    {
        try {
            $payment = SettlementShortagePayment::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * add excess payment data to db
     * @return Response
     */
    public function saveExcessPayment(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');
            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();
            $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);
            $data = array(
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $request->amount,
                'current_excess' => $pump_operator->excess_amount,
                'note' => $request->note
            );
            if($request->amount > 0){
                $output = [
                    'success' => false,
                    'msg' => __('Please enter the amount with a negative symbol')
                ];
                return $output;
            }

            $settlement_excess_payment = SettlementExcessPayment::create($data);
            Settlement::where('id',$settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [
                'success' => true,
                'settlement_excess_payment_id' => $settlement_excess_payment->id,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * delete excess payment data to db
     * @return Response
     */
    public function deleteExcessPayment($id)
    {
        try {
            $payment = SettlementExcessPayment::where('id', $id)->first();
            Settlement::where('id',$payment->settlement_no)->update(['is_edit' => request()->is_edit]);
            $amount = $payment->amount;
            $payment->delete();
            $output = [
                'success' => true,
                'amount' => $amount,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    /**
     * preview payment details
     * @return Response
     */
    public function preview($id)
    {
        $business_id = request()->session()->get('business.id');

        $settlement = Settlement::where('settlements.id', $id)->where('settlements.business_id', $business_id)
            ->leftjoin('pump_operators', 'settlements.pump_operator_id', 'pump_operators.id')
            ->with([
                'meter_sales',
                'other_sales',
                'other_incomes',
                'customer_payments',
                'cash_payments',
                'cash_deposits',
                'card_payments',
                'cheque_payments',
                'credit_sale_payments',
                'expense_payments',
                'excess_payments',
                'shortage_payments',
                'loan_payments'
            ])
            ->select('settlements.*', 'pump_operators.name as pump_operator_name')
            ->first();
        
        $daily_collections = DailyCollection::where('daily_collections.business_id', $business_id)
        ->where('daily_collections.pump_operator_id', $settlement->pump_operator_id)
        ->whereNull('settlement_id')
        ->select([
            'daily_collections.*',
        ])->orderBy('daily_collections.id')->get();
        foreach ($daily_collections as $daily_collections) {
            $customers = Contact::customersDropdown($business_id, false, true, 'customer');
            $settlementCashPayment = new SettlementCashPayment();
            $settlementCashPayment->business_id = $business_id;
            $settlementCashPayment->settlement_no = $settlement->id;
            $settlementCashPayment->amount = floatval($daily_collections->current_amount);
            $settlementCashPayment->customer_id = array_key_first($customers->toArray());
            $settlement->cash_payments[] = $settlementCashPayment;
        }

        $business = Business::where('id', $settlement->business_id)->first();
        $pump_operator = PumpOperator::where('id', $settlement->pump_operator_id)->first();

        //this for only to show in print page customer payments which entered in customer payments tab
        $customer_payments_tab = CustomerPayment::leftjoin('contacts', 'customer_payments.customer_id', 'contacts.id')
            ->where('customer_payments.settlement_no', $id)
            ->where('customer_payments.business_id', $business_id)
            ->select('customer_payments.*', 'contacts.name as customer_name')
            ->get();

        return view('petro::settlement.partials.payment_preview')->with(compact('settlement', 'business', 'pump_operator', 'customer_payments_tab'));
    }

        /**
     * preview payment details
     * @return Response
     */
    public function productPreview($id)
    {

        $business_id = request()->session()->get('business.id');

        $settlement = Settlement::where('settlements.id', $id)

            ->leftjoin('settlement_credit_sale_payments', 'settlements.id', 'settlement_credit_sale_payments.settlement_no')
            ->leftjoin('products', 'products.id', 'settlement_credit_sale_payments.product_id')
            ->select('settlements.*', 'products.*', 'settlement_credit_sale_payments.*')
            ->get();

        return view('petro::settlement.partials.product_preview')->with(compact('settlement'));
    }
}

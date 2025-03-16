<?php

namespace Modules\MPCS\Http\Controllers;
use App\Account;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Store;
use App\Unit;
use Modules\Petro\Entities\Pump;
use App\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Modules\Petro\Entities\MeterSale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\MPCS\Entities\FormF16Detail;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF17HeaderController;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\FormF22Detail;
use App\Contact;
use App\Transaction;
use App\MergedSubCategory;
class F21CFormController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $util;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, Util $util)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->util = $util;
    }


    /**
     * Display a listing of the resource.
     * @return Response
     */
   
    
    
    public function index(Request $request)
{
        if (!auth()->check()) {
            return redirect()->route('login');
        } 
       
        $business_id = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        $business_locations = BusinessLocation::forDropdown($business_id);
      
           $business_details = Business::find($business_id);
            $currency_precision = (int) $business_details->currency_precision;
            $qty_precision = (int) $business_details->quantity_precision;
      
       
          $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
          $settings = MpcsFormSetting::where('business_id', $business_id)->first();
            if (!empty($settings)) {
            $F21c_from_no = $settings->F21C_form_sn;
        } else {
            $F21c_from_no = 1;
        }
        $layout = 'layouts.app';
        return view('mpcs::forms.21CForm.F21_form')->with(compact(
            
           'F21c_from_no',
            'sub_categories',
            'currency_precision',
            'qty_precision',
            'merged_sub_categories',
             'business_locations',
            'layout'
            ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
        public function get21CForms(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        if (!empty($settings)) {
            $F9C_sn = $settings->F9C_sn;
        } else {
            $F9C_sn = 1;
        }
        if (request()->ajax()) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $location_id = $request->location_id;

            $credit_sales = $this->Form9CQuery($business_id, $start_date, $end_date, $location_id);

            $location = [];
            if (!empty($request->location_id)) {
                $location = BusinessLocation::findOrFail($request->location_id);
            }

            $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();

            return view('mpcs::forms.partials.9c_details_section')->with(compact(
                'credit_sales',
                'sub_categories',
                'start_date',
                'end_date',
                'location',
                'F9C_sn'
            ));
        }
    }
    
     public function get_21_c_form_all_query(Request $request)
    {
       
        $business_id = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        
       $start_date = $request->start_date;
            $end_date = $request->end_date;
            $location_id = $request->location_id;

            $settings = MpcsFormSetting::where('business_id', $business_id)->first();
            $F21C_form_tdate = $settings->F21C_form_tdate;
            $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $startDate = Carbon::createFromFormat('Y-m-d', $start_date);
            $endDate = Carbon::createFromFormat('Y-m-d', $end_date);

            $credit_sales = $this->Form21CQuery($business_id, $start_date, $end_date, $location_id);
            $previous_credit_sales = $this->Form21CQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
            $form22_details = FormF22Detail::where('business_id', $business_id)
                                ->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate) 
                                ->orderBy('id', 'DESC')               
                                ->first();
            $form17_increase = FormF17Detail::where('select_mode', 'increase')
                                ->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate) 
                                ->orderBy('id', 'DESC')               
                                ->first();

            $form17_increase_previous = FormF17Detail::where('select_mode', 'increase')
                                ->whereDate('created_at', '>=', $previous_start_date) 
                                ->orderBy('id', 'DESC')               
                                ->first();

            $form17_decrease = FormF17Detail::where('select_mode', 'descrease')
                                ->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate) 
                                ->orderBy('id', 'DESC')               
                                ->first();
                                
            $form17_decrease_previous = FormF17Detail::where('select_mode', 'decrease')
                                ->whereDate('created_at', '>=', $previous_start_date)
                                // ->whereDate('created_at', '<=', $previous_end_date) 
                                ->orderBy('id', 'DESC')               
                                ->first();

            $transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $start_date)
                            ->whereDate('transactions.transaction_date', '<=', $end_date)
                            ->orWhere('transaction_payments.method', 'cash')
                            ->orWhere('transaction_payments.method', 'cheque')
                            ->orWhere('transaction_payments.method', 'card')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $previous_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                            // ->whereDate('transactions.transaction_date', '<=', $previous_end_date)
                            ->orWhere('transaction_payments.method', 'cash')
                            ->orWhere('transaction_payments.method', 'cheque')
                            ->orWhere('transaction_payments.method', 'card')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $start_date)
                            ->whereDate('transactions.transaction_date', '<=', $end_date)
                            ->orWhere('transaction_payments.method', 'custom_pay_1')
                            ->orWhere('transaction_payments.method', 'custom_pay_2')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $previous_own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                            ->orWhere('transaction_payments.method', 'custom_pay_1')
                            ->orWhere('transaction_payments.method', 'custom_pay_2')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $start_date)
                            ->whereDate('transactions.transaction_date', '<=', $end_date)
                            ->where('transaction_payments.method', 'credit_sales')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $previous_credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                            ->where('transaction_payments.method', 'credit_sales')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $account_transactions = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date)
                    ->where('account_transactions.business_id',$business_id)
                    ->get();
                    
            $opening_stock = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    //->whereDate('transactions.transaction_date', '>=', $start_date)
                    //->whereDate('transactions.transaction_date', '<=', $end_date)
                    ->where('account_transactions.business_id',$business_id)
                    ->where('transactions.type','opening_stock')
                    ->where('transactions.status','final')
                    ->sum('account_transactions.amount');
                    // ->get();
                    
            $today = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '=', Carbon::now())
                    ->where('account_transactions.business_id',$business_id)
                    ->sum('account_transactions.amount');   
                    // ->get();
            $previous_day = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '=', Carbon::now()->subDays(1))
                    ->where('account_transactions.business_id',$business_id)
                    ->sum('account_transactions.amount');
                    // ->get();
                        $incomeGrp_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')->where('accounts.business_id', $business_id)->where('account_groups.name', 'Sales Income Group')->select('accounts.id')->get()->pluck('id');
             $cash_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', Carbon::now())
                ->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
                ->where('account_transactions.business_id',$business_id)
                ->where('account_transactions.type','debit')
                ->get()->sum('amount');
             $credit_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', Carbon::now())
                ->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
                ->where('account_transactions.business_id',$business_id)
                ->where('account_transactions.type','credit')
                ->get()->sum('amount');
            
            return [
                "credit_sales" => $credit_sales, 
                "previous_credit_sales" => $previous_credit_sales,
                "form22_details" => $form22_details,
                "form17_increase" => $form17_increase,
                "form17_decrease" => $form17_decrease,
                "transaction" => $transaction,
                "own_group" => $own_group,
                "credit_sales_transaction" => $credit_sales_transaction,
                "previous_transaction" => $previous_transaction,
                "previous_own_group" => $previous_own_group,
                "previous_credit_sales_transaction" => $previous_credit_sales_transaction,
                "form17_increase_previous" => $form17_increase_previous,
                "form17_decrease_previous" => $form17_decrease_previous,
                "merged_sub_categories" => $merged_sub_categories,
                "account_transactions" => $account_transactions,
                "opening_stock" => $opening_stock,
                "previous_day" => (int)$previous_day,
                "today" => (int)$today,
                "cash_sales_today" => (int)$cash_sales_today,
                "credit_sales_today" => (int)$credit_sales_today,
            ];
    }
    public function get_21_c_form_all_querys(Request $request)
    {
       
        $business_id = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        
       $start_date = $request->start_date;
            $end_date = $request->end_date;
            $location_id = $request->location_id;

            $settings = MpcsFormSetting::where('business_id', $business_id)->first();
            $F21C_form_tdate = $settings->F21C_form_tdate;
            $previous_start_date = Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date = Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $startDate = Carbon::createFromFormat('Y-m-d', $start_date);
            $endDate = Carbon::createFromFormat('Y-m-d', $end_date);

            $credit_sales = $this->Form21CQuery($business_id, $start_date, $end_date, $location_id);
            $previous_credit_sales = $this->Form21CQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
            $form22_details = FormF22Detail::where('business_id', $business_id)
                                ->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate) 
                                ->orderBy('id', 'DESC')               
                                ->first();
            $form17_increase = FormF17Detail::where('select_mode', 'increase')
                                ->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate) 
                                ->orderBy('id', 'DESC')               
                                ->first();

            $form17_increase_previous = FormF17Detail::where('select_mode', 'increase')
                                ->whereDate('created_at', '>=', $previous_start_date) 
                                ->orderBy('id', 'DESC')               
                                ->first();

            $form17_decrease = FormF17Detail::where('select_mode', 'descrease')
                                ->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate) 
                                ->orderBy('id', 'DESC')               
                                ->first();
                                
            $form17_decrease_previous = FormF17Detail::where('select_mode', 'decrease')
                                ->whereDate('created_at', '>=', $previous_start_date)
                                // ->whereDate('created_at', '<=', $previous_end_date) 
                                ->orderBy('id', 'DESC')               
                                ->first();

            $transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $start_date)
                            ->whereDate('transactions.transaction_date', '<=', $end_date)
                            ->orWhere('transaction_payments.method', 'cash')
                            ->orWhere('transaction_payments.method', 'cheque')
                            ->orWhere('transaction_payments.method', 'card')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $previous_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                            // ->whereDate('transactions.transaction_date', '<=', $previous_end_date)
                            ->orWhere('transaction_payments.method', 'cash')
                            ->orWhere('transaction_payments.method', 'cheque')
                            ->orWhere('transaction_payments.method', 'card')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $start_date)
                            ->whereDate('transactions.transaction_date', '<=', $end_date)
                            ->orWhere('transaction_payments.method', 'custom_pay_1')
                            ->orWhere('transaction_payments.method', 'custom_pay_2')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $previous_own_group = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                            ->orWhere('transaction_payments.method', 'custom_pay_1')
                            ->orWhere('transaction_payments.method', 'custom_pay_2')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $start_date)
                            ->whereDate('transactions.transaction_date', '<=', $end_date)
                            ->where('transaction_payments.method', 'credit_sales')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $previous_credit_sales_transaction = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
                            ->leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                            ->select(
                                'transactions.id',
                                'transactions.transaction_date',
                                'transactions.final_total',
                                'transaction_payments.method as payment_method',
                                'transaction_sell_lines.quantity',
                                'transaction_sell_lines.unit_price',
                                'transactions.ref_no',
                                'transactions.invoice_no',
                                'transactions.invoice_no as order_no'
                            )
                            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                            ->where('transaction_payments.method', 'credit_sales')
                            ->orderBy('id', 'DESC')               
                            ->first();

            $account_transactions = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '>=', $start_date)
                    ->whereDate('transactions.transaction_date', '<=', $end_date)
                    ->where('account_transactions.business_id',$business_id)
                    ->get();
                    
            $opening_stock = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    //->whereDate('transactions.transaction_date', '>=', $start_date)
                    //->whereDate('transactions.transaction_date', '<=', $end_date)
                    ->where('account_transactions.business_id',$business_id)
                    ->where('transactions.type','opening_stock')
                    ->where('transactions.status','final')
                    ->sum('account_transactions.amount');
                    // ->get();
                    
            $today = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '=', Carbon::now())
                    ->where('account_transactions.business_id',$business_id)
                    ->sum('account_transactions.amount');   
                    // ->get();
            $previous_day = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '=', Carbon::now()->subDays(1))
                    ->where('account_transactions.business_id',$business_id)
                    ->sum('account_transactions.amount');
                    // ->get();
                        $incomeGrp_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')->where('accounts.business_id', $business_id)->where('account_groups.name', 'Sales Income Group')->select('accounts.id')->get()->pluck('id');
             $cash_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', Carbon::now())
                ->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
                ->where('account_transactions.business_id',$business_id)
                ->where('account_transactions.type','debit')
                ->get()->sum('amount');
             $credit_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', Carbon::now())
                ->join('transactions', 'transactions.id', '=', 'account_transactions.trfansaction_id')
                ->where('account_transactions.business_id',$business_id)
                ->where('account_transactions.type','credit')
                ->get()->sum('amount');
            
            return [
                "credit_sales" => $credit_sales, 
                "previous_credit_sales" => $previous_credit_sales,
                "form22_details" => $form22_details,
                "form17_increase" => $form17_increase,
                "form17_decrease" => $form17_decrease,
                "transaction" => $transaction,
                "own_group" => $own_group,
                "credit_sales_transaction" => $credit_sales_transaction,
                "previous_transaction" => $previous_transaction,
                "previous_own_group" => $previous_own_group,
                "previous_credit_sales_transaction" => $previous_credit_sales_transaction,
                "form17_increase_previous" => $form17_increase_previous,
                "form17_decrease_previous" => $form17_decrease_previous,
                "merged_sub_categories" => $merged_sub_categories,
                "account_transactions" => $account_transactions,
                "opening_stock" => $opening_stock,
                "previous_day" => (int)$previous_day,
                "today" => (int)$today,
                "cash_sales_today" => (int)$cash_sales_today,
                "credit_sales_today" => (int)$credit_sales_today,
            ];
    }
	 
    public function Form21CQuery($business_id, $start_date, $end_date, $location_id)
    {
        
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.is_credit_sale', 1)
             ->whereNull('transactions.customer_group_id')
               ->whereDate('transactions.transaction_date', '=', Carbon::now())
            ->select(
                'transactions.transaction_date',
                'transactions.final_total',
                'products.name as description',
                'products.sub_category_id',
                'transaction_sell_lines.quantity',
                'transaction_sell_lines.unit_price',
                'transactions.ref_no',
                'transactions.invoice_no',
                'contacts.name as customer',
                'transactions.invoice_no as order_no',
                'business.name as comapany',
                'business_locations.mobile as tel',
            );
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $credit_sales = $query->get();

        return $credit_sales;
    }
  
   
   
 
}

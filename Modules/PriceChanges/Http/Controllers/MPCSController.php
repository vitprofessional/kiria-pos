<?php

namespace Modules\MPCS\Http\Controllers;

use App\Account;

use App\AccountGroup;
use App\Brands;
use App\BusinessLocation;
use App\Category;
use App\MergedSubCategory;
use App\Product;
use App\Store;
use App\TaxRate;
use App\Transaction;
use App\Unit;
use App\Variation;
use App\AccountTransaction;
;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\MPCS\Entities\Form9cSubCategory;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF22Detail;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;

class MPCSController extends Controller
{

    public function FromSet1()
    {
        $business_id = request()->session()->get('business.id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        if (!empty($settings)) {
            $F9C_sn = $settings->F9C_sn;
        } else {
            $F9C_sn = 1;
        }
        if (!empty($settings)) {
            $F16a_from_no = $settings->F16A_form_sn;
        } else {
            $F16a_from_no = 1;
        }
        if (!empty($settings)) {
            $F21c_from_no = $settings->F21C_form_sn;
        } else {
            $F21c_from_no = 1;
        }
        if (!empty($settings)) {
            $F15a9ab_from_no = $settings->F159ABC_form_sn;
        } else {
            $F15a9ab_from_no = 1;
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();

        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();

        $setting = MpcsFormSetting::where('business_id', $business_id)->first();
        
        return view('mpcs::forms.form_set_1')->with(compact(
            'business_locations',
            'F9C_sn',
            'F16a_from_no',
            'F21c_from_no',
            'F15a9ab_from_no',
            'merged_sub_categories',
            'sub_categories',
            'setting'
        ));
    }

    /**
     * Show the form for getFrom20 
     * @return Response
     */
    public function get9CForm(Request $request)
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

    public function Form9CQuery($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.is_credit_sale', 1)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
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
                'business_locations.mobile as tel'
            );
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        $credit_sales = $query->get();

        return $credit_sales;
    }

    public function getPreviousValue9CForm(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $F9C_tdate =  $settings->F9C_tdate;
        $checkSetValueZero = $this->checkSetValueZero9C($request);
        $start_date = $checkSetValueZero['start_date'];
        $end_date = \Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
        $location_id = $request->location_id;



        $credit_sales = $this->Form9CQuery($business_id, $start_date, $end_date, $location_id);
        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
        $sub_cat_data = [];
        foreach ($sub_categories as $item) {
            $pre_page_set_value = Form9cSubCategory::where('business_id', $business_id)->where('sub_category_id', $item->id)->first();
            if ($request->start_date == $F9C_tdate) {
                $sub_cat_data[$item->id]['qty'] = !empty($pre_page_set_value->qty) ? $pre_page_set_value->qty : 0;
                $sub_cat_data[$item->id]['amount'] =  !empty($pre_page_set_value->amount) ? $pre_page_set_value->amount : 0;
            } else if ($checkSetValueZero['status']) {
                $sub_cat_data[$item->id]['qty'] = 0.00;
                $sub_cat_data[$item->id]['amount'] =  0.00;
            } else if ($checkSetValueZero['lap']) {
                $sub_cat_data[$item->id]['qty'] =  0;
                $sub_cat_data[$item->id]['amount'] =  0;
                foreach ($credit_sales as $sale) {
                    if ($item->id == $sale->sub_category_id) {
                        $sub_cat_data[$item->id]['qty'] =  $sale->quantity;
                        $sub_cat_data[$item->id]['amount'] =  ($sale->unit_price * $sale->quantity);
                    }
                }
            } else {
                $sub_cat_data[$item->id]['qty'] = !empty($pre_page_set_value->qty) ? $pre_page_set_value->qty : 0;
                $sub_cat_data[$item->id]['amount'] =  !empty($pre_page_set_value->amount) ? $pre_page_set_value->amount : 0;
                foreach ($credit_sales as $sale) {
                    if ($item->id == $sale->sub_category_id) {
                        $sub_cat_data[$item->id]['qty'] = !empty($pre_page_set_value->qty) ? $pre_page_set_value->qty : 0 + $sale->quantity;
                        $sub_cat_data[$item->id]['amount'] =  !empty($pre_page_set_value->amount) ? $pre_page_set_value->amount : 0 + ($sale->unit_price * $sale->quantity);
                    }
                }
            }
        }

        return $sub_cat_data;
    }

    public function checkSetValueZero9C($request)
    {
        $business_id = request()->session()->get('user.business_id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $result['status'] = 0;
        $result['lap'] = 0;
        $result['start_date'] = $settings->F9C_tdate;
        $date_diff_days = \Carbon::parse($settings->F9C_tdate)->diffInDays(\Carbon::parse($request->start_date), false);
        $date_diff_months = \Carbon::parse($settings->F9C_tdate)->diffInMonths(\Carbon::parse($request->start_date), false);
        if ($date_diff_days < 0) {
            $result['status'] = 1;
        }
        $stock_taking_date = FormF22Header::where('business_id', $business_id)->orderBy('id', 'desc')->first();
        if (!empty($stock_taking_date)) {
            if ($request->start_date == $stock_taking_date->form_date) {
                $result['lap'] = 1;
            }
        }

        if ($settings->F9C_first_day_of_next_month) {
            $F9C_first_day_of_next_month_selected = $date_diff_days <=  30 * $settings->F9C_first_day_of_next_month_selected ? $settings->F9C_first_day_of_next_month_selected - 1 : $settings->F9C_first_day_of_next_month_selected;
            if (!empty($F9C_first_day_of_next_month_selected)) {
                $first_date_after_selected_mothn_from_start_date = \Carbon::parse($settings->F9C_tdate)->addMonth($F9C_first_day_of_next_month_selected)->firstOfMonth()->format('Y-m-d');

                if ($date_diff_months % $F9C_first_day_of_next_month_selected == 0 && $date_diff_months > 0) {
                    $result['start_date'] =  $first_date_after_selected_mothn_from_start_date;
                    $result['lap'] = 1;
                }
                if ($request->start_date == $first_date_after_selected_mothn_from_start_date) {
                    $result['status'] = 1;
                }
            }
        }

        return $result;
    }

    public function get16AForm(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $location_id = $request->location_id;

        if (request()->ajax()) {
            $purchases = $this->F16AQuery($business_id,  $start_date, $end_date, $location_id);

            return Datatables::of($purchases)

                ->addColumn('index_no', function ($row) {
                    return $row->id;
                })
                ->removeColumn('id')
                ->editColumn('unit_sale_price', function ($row) {
                    return number_format($row->default_sell_price,2);
                })
                ->editColumn('total_purchase_price', function ($row) {
                    return '<span class="display_currency total_purchase_price" data-orig-value="' . $row->total_purchase_price . '" data-currency_symbol = false>' . number_format($row->total_purchase_price,2) . '</span>';
                })
                ->editColumn('total_sale_price', function ($row) {
                    return '<span class="display_currency total_sale_price" data-orig-value="' .  $row->default_sell_price * $row->received_qty . '" data-currency_symbol = false>' .  number_format($row->default_sell_price * $row->received_qty,2) . '</span>';
                })
                ->editColumn('stock_book_no', function ($row) {
                    return '';
                })
                ->rawColumns(['total_purchase_price', 'total_sale_price'])
                ->make(true);
        }
    }

    public function F16AQuery($business_id, $start_date, $end_date, $location_id)
    {
        $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->join(
                'business_locations AS BS',
                'transactions.location_id',
                '=',
                'BS.id'
            )
            ->leftJoin(
                'transaction_payments AS TP',
                'transactions.id',
                '=',
                'TP.transaction_id'
            )
            ->leftJoin(
                'transactions AS PR',
                'transactions.id',
                '=',
                'PR.return_parent_id'
            )
            ->leftjoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
            ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
            ->leftjoin('variations', 'products.id', 'variations.product_id')
            ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.status', 'received')
            ->select(
                'transactions.id',
                'transactions.ref_no as reference_no',
                'purchase_lines.quantity as received_qty',
                'purchase_lines.purchase_price as unit_purchase_price',
                'transactions.final_total as total_purchase_price',
                'BS.name as location',
                'products.name as product',
                'products.id as product_id',
                'variations.default_sell_price',
                'transactions.pay_term_number',
                'transactions.pay_term_type',
                'PR.id as return_transaction_id',
                DB::raw('SUM(TP.amount) as amount_paid'),
                DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                        TP2.transaction_id=PR.id ) as return_paid'),
                DB::raw('COUNT(PR.id) as return_exists'),
                DB::raw('COALESCE(PR.final_total, 0) as amount_return'),
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
            )
            ->groupBy('transactions.id');
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $purchases->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty($location_id)) {
            $purchases->where('transactions.location_id', $location_id);
        }

        if (!empty($end_date) && !empty($start_date)) {
            $purchases->whereDate('transactions.transaction_date', '>=', $start_date);
            $purchases->whereDate('transactions.transaction_date', '<=', $end_date);
        }
        return $purchases;
    }

    public function getPreviousValue16AForm(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $F16A_form_tdate = $settings->F16A_form_tdate;
        $start_date = $F16A_form_tdate;
        $end_date = \Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
        $location_id = $request->location_id;

        $purchases = $this->F16AQuery($business_id, $start_date, $end_date, $location_id)->get();
        $pre_total_purchase_price = $settings->F16A_total_pp;
        $pre_total_sale_price = $settings->F16A_total_sp;

        foreach ($purchases as $item) {
            $pre_total_purchase_price = $pre_total_purchase_price + $item->total_purchase_price;
            $pre_total_sale_price =  $pre_total_sale_price + ($item->default_sell_price * $item->received_qty);
        }
        return ['pre_total_purchase_price' => $pre_total_purchase_price, 'pre_total_sale_price' => $pre_total_sale_price];
    }

    public function F14()
    {
        return view('mpcs::forms.F14_form');
    }

    public function F159ABC()
    {
        $business_id = request()->session()->get('business.id');
        
        if (request()->ajax()) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $location_id = $request->location_id;

            $settings = MpcsFormSetting::where('business_id', $business_id)->first();
            $previous_start_date = \Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date = \Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $startDate = \Carbon::createFromFormat('Y-m-d', $start_date);
            $endDate = \Carbon::createFromFormat('Y-m-d', $end_date);

            $credit_sales = $this->F159ABCQuery($business_id, $start_date, $end_date, $location_id);
            $previous_credit_sales = $this->F159ABCQuery($business_id, $previous_end_date, $previous_end_date, $location_id);
            $form22_details = FormF22Detail::where('business_id', $business_id)
                                ->whereDate('created_at', '>=', $startDate)
                                ->whereDate('created_at', '<=', $endDate) 
                                ->orderBy('id', 'DESC')               
                                ->first();

            return array(
                "credit_sales" => $credit_sales, 
                "previous_credit_sales" => $previous_credit_sales,
                "form22_details" => $form22_details
            );
        }
    }

    public function F159ABCQuery($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.is_credit_sale', 1)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
            ->select(
                'transactions.transaction_date',
                'transactions.final_total as opening_balance',
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

    public function F21()
    {
        $layout = 'layouts.app';
        return view('mpcs::forms.F21_form')->with(compact('layout'));
    }

    public function get21CForm(Request $request)
    {
        $business_id = request()->session()->get('business.id');
        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        
        if (request()->ajax()) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $location_id = $request->location_id;

            $settings = MpcsFormSetting::where('business_id', $business_id)->first();
            $F21C_form_tdate = $settings->F21C_form_tdate;
            $previous_start_date = \Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date = \Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $startDate = \Carbon::createFromFormat('Y-m-d', $start_date);
            $endDate = \Carbon::createFromFormat('Y-m-d', $end_date);

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

            $form17_decrease_previous = FormF17Detail::where('select_mode', 'descrease')
                                ->whereDate('created_at', '>=', $previous_start_date)
                                // ->whereDate('created_at', '<=', $previous_end_date) 
                                ->orderBy('id', 'DESC')               
                                ->first();
            $form17_decrease_previous_day = FormF17Detail::where('select_mode', 'descrease')
                                ->whereDate('created_at', '>=', \Carbon::now())
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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
                            )
                            ->whereDate('transactions.transaction_date', '>=', $previous_start_date)
                            ->where('transaction_payments.method', 'credit_sales')
                            ->orderBy('id', 'DESC')               
                            ->first();

            return array(
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
                "merged_sub_categories" => $merged_sub_categories
            );
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
            $previous_start_date = \Carbon::parse($request->start_date)->subDays(1)->format('Y-m-d');
            $previous_end_date = \Carbon::parse($request->end_date)->subDays(1)->format('Y-m-d');
            $startDate = \Carbon::createFromFormat('Y-m-d', $start_date);
            $endDate = \Carbon::createFromFormat('Y-m-d', $end_date);

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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
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
                                'transactions.invoice_no as order_no',
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
                    ->whereDate('transactions.transaction_date', '=', \Carbon::now())
                    ->where('account_transactions.business_id',$business_id)
                    ->sum('account_transactions.amount');   
                    // ->get();
            $previous_day = AccountTransaction::join('transactions','transactions.id','account_transactions.transaction_id')
                    ->whereDate('transactions.transaction_date', '=', \Carbon::now()->subDays(1))
                    ->where('account_transactions.business_id',$business_id)
                    ->sum('account_transactions.amount');
                    // ->get();
                        $incomeGrp_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')->where('accounts.business_id', $business_id)->where('account_groups.name', 'Sales Income Group')->select('accounts.id')->get()->pluck('id');
             $cash_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', \Carbon::now())
                ->join('transactions', 'transactions.id', '=', 'account_transactions.transaction_id')
                ->where('account_transactions.business_id',$business_id)
                ->where('account_transactions.type','debit')
                ->get()->sum('amount');
             $credit_sales_today = AccountTransaction::whereDate('account_transactions.operation_date','=', \Carbon::now())
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

    public function Form21CQuery($business_id, $start_date, $end_date, $location_id)
    {
        $query = Transaction::leftjoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')
            ->leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('contacts', 'transactions.contact_id', 'contacts.id')
            ->leftjoin('business', 'transactions.business_id', 'business.id')
            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.is_credit_sale', 1)
            ->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date)
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

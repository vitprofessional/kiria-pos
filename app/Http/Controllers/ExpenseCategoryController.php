<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountType;
use App\ExpenseCategory;
use App\ExpenseCategoryCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use App\Contact;
use Modules\Essentials\Entities\EssentialsEmployee;

class ExpenseCategoryController extends Controller
{
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil =  $moduleUtil;
        $this->productUtil =  $productUtil;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $expense_category = ExpenseCategory::leftjoin('accounts', 'expense_account', 'accounts.id')
                ->leftjoin('contacts', 'contacts.id', '=', 'expense_categories.payee_id')
                ->where('expense_categories.business_id', $business_id)
                //->where('expense_categories.payee_id', 'NULL')
                ->select(['expense_categories.name', 'code', 'accounts.name as account_name', 'contacts.name as payee_name', 'expense_categories.id']);
                
            return Datatables::of($expense_category)
                ->addColumn(
                    'action',
                    '<button data-href="{{action(\'ExpenseCategoryController@show\', [$id])}}" class="btn btn-xs btn-success btn-modal" data-container=".expense_category_modal"><i class="glyphicon glyphicon-eye-open"></i> @lang("messages.view")</button>
                        &nbsp;
                    <button data-href="{{action(\'ExpenseCategoryController@edit\', [$id])}}" class="btn btn-xs btn-primary btn-modal" data-container=".expense_category_modal"><i class="glyphicon glyphicon-edit"></i>  @lang("messages.edit")</button>
                        &nbsp;
                    <button data-href="{{action(\'ExpenseCategoryController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_expense_category"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>'
                )
                ->removeColumn('id')
                ->rawColumns([4])
                ->make(false);
        }

        return view('expense_category.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        
        $expense_cat = ExpenseCategoryCode::where('business_id',$business_id)->first();
        $expense = ExpenseCategory::where('business_id',$business_id)->get()->last();
        if(!empty($expense)){
            $code = explode('-',$expense->code);
            
            $expcode = (!empty($expense_cat->prefix) ? $expense_cat->prefix : '')."-".((!empty($code[1]) && $code[1] >= $expense_cat->starting_no) ? $code[1]+1 : $expense_cat->starting_no);
        }else{
            $expcode = (!empty($expense_cat->prefix) ? $expense_cat->prefix : '')."-".$expense_cat->starting_no;
        }
        
        
        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();
        $expense_accounts = [];
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $expense_account_id = null;
        if ($account_access) {
            if (!empty($expense_account_type_id)) {
                $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->where('is_main_account', 0)->pluck('name', 'id');
            }
        } else {
            $expense_account_id = Account::where('name', 'Expenses')->where('business_id', $business_id)->first()->id;
            $expense_accounts = Account::where('name', 'Expenses')->where('business_id', $business_id)->where('is_main_account', 0)->pluck('name', 'id');
        }

        $expense_categories = ExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
        $employees = EssentialsEmployee::pluck('name', 'id');
        $quick_add = request()->quick_add ? 1 : 0;

        $payees = Contact::where('business_id', $business_id)->whereIn('type',['supplier','both'])->pluck('name', 'id');
        $payees[''] = 'No Payee';
        //dd($payees);

        return view('expense_category.create')->with(compact('expcode','expense_accounts', 'account_access', 'expense_account_id', 'quick_add', 'expense_categories', 'employees', 'payees'));
    }
    public function view($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        
        $expense_cat = ExpenseCategoryCode::where('business_id',$business_id)->first();
        $expense = ExpenseCategory::where('business_id',$business_id)->get()->last();
        if(!empty($expense)){
            $code = explode('-',$expense->code);
            
            $expcode = (!empty($expense_cat->prefix) ? $expense_cat->prefix : '')."-".((!empty($code[1]) && $code[1] >= $expense_cat->starting_no) ? $code[1]+1 : $expense_cat->starting_no);
        }else{
            $expcode = (!empty($expense_cat->prefix) ? $expense_cat->prefix : '')."-".$expense_cat->starting_no;
        }
        
        
        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();
        $expense_accounts = [];
        $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
        $expense_account_id = null;
        if ($account_access) {
            if (!empty($expense_account_type_id)) {
                $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->where('is_main_account', 0)->pluck('name', 'id');
            }
        } else {
            $expense_account_id = Account::where('name', 'Expenses')->where('business_id', $business_id)->first()->id;
            $expense_accounts = Account::where('name', 'Expenses')->where('business_id', $business_id)->where('is_main_account', 0)->pluck('name', 'id');
        }

        $expense_categories = ExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
        $employees = EssentialsEmployee::pluck('name', 'id');
        $quick_add = request()->quick_add ? 1 : 0;

        $payees = Contact::where('business_id', $business_id)->whereIn('type',['supplier','both'])->pluck('name', 'id');
        $payees[''] = 'No Payee';
        //dd($payees);

        return view('expense_category.view')->with(compact('expcode','expense_accounts', 'account_access', 'expense_account_id', 'quick_add', 'expense_categories', 'employees', 'payees'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }


        try {
//            $validator = Validator::make($request->all(), [
//                'name' => 'required|string',
//                'code' => 'required|string',
//                'expense_account' => 'required|string',
//                'payee_id' => 'required|string',
//                'parent_id' => 'nullable|string',
//            ]);
//
//            if ($validator->fails()) {
//                return [
//                    'success' => false,
//                    'msg' => __("messages.something_went_wrong")
//                ];
//            }
            $input = $request->only(['name', 'code', 'expense_account', 'payee_id', 'is_sub_category','vat_claimed', 'parent_id', 'is_employee', 'employee_id']);


            if (!$request->payee_id) {
                $input['payee_id'] = '0';
            }else{
                $input['payee_id'] = $request->payee_id;
            }
            
            

            $input['business_id'] = $request->session()->get('user.business_id');


            $expense_category = ExpenseCategory::create($input);
            $output = [
                'success' => true,
                'expense_category_id' => $expense_category->id,
                'msg' => __("expense.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\ExpenseCategory  $expenseCategory
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $expense_category = ExpenseCategory::where('business_id', $business_id)->find($id);
            $business_id = request()->session()->get('user.business_id');
            $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();
            $expense_accounts = [];
            $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
            if ($account_access) {
                if (!empty($expense_account_type_id)) {
                    $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->where('is_main_account', 0)->pluck('name', 'id');
                }
            } else {
                $expense_accounts = Account::where('name', 'Expenses')->where('business_id', $business_id)->where('is_main_account', 0)->pluck('name', 'id');
            }

            $expense_categories = ExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
            $employees = EssentialsEmployee::pluck('name', 'id');
            $payees = Contact::where('business_id', $business_id)->whereIn('type',['supplier','both'])->pluck('name', 'id');
            return view('expense_category.show')
                ->with(compact('expense_category', 'expense_accounts', 'expense_categories', 'employees', 'payees'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $expense_category = ExpenseCategory::where('business_id', $business_id)->find($id);
            $business_id = request()->session()->get('user.business_id');
            $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();
            $expense_accounts = [];
            $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');
            if ($account_access) {
                if (!empty($expense_account_type_id)) {
                    $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->where('is_main_account', 0)->pluck('name', 'id');
                }
            } else {
                $expense_accounts = Account::where('name', 'Expenses')->where('business_id', $business_id)->where('is_main_account', 0)->pluck('name', 'id');
            }

            $expense_categories = ExpenseCategory::where('business_id', $business_id)->pluck('name', 'id');
            $employees = EssentialsEmployee::pluck('name', 'id');
            $payees = Contact::where('business_id', $business_id)->whereIn('type',['supplier','both'])->pluck('name', 'id');
            return view('expense_category.edit')
                ->with(compact('expense_category', 'expense_accounts', 'expense_categories', 'employees', 'payees'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'code', 'payee_id', 'expense_account', 'is_sub_category', 'parent_id','vat_claimed']);
                $business_id = $request->session()->get('user.business_id');

                $expense_category = ExpenseCategory::where('business_id', $business_id)->findOrFail($id);
                $expense_category->name = $input['name'];
                $expense_category->code = $input['code'];
                $expense_category->is_sub_category = !empty($input['is_sub_category']) ? 1 : 0;
                $expense_category->vat_claimed = !empty($input['vat_claimed']) ? 1 : 0;
                $expense_category->parent_id = $input['parent_id'];
                $expense_category->expense_account = $input['expense_account'];
                if ($request->payee_id ===  NULL) {
                    $input['payee_id'] = '0';
                }
                $expense_category->payee_id = $input['payee_id'];
                $expense_category->save();
                
                

                $output = [
                    'success' => true,
                    'msg' => __("expense.updated_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('expense.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $expense_category = ExpenseCategory::where('business_id', $business_id)->findOrFail($id);
                $expense_category->delete();

                $output = [
                    'success' => true,
                    'msg' => __("expense.deleted_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    public function getAccountIdByCategory($id)
    {
        $expense_category = ExpenseCategory::leftjoin('accounts', 'expense_account', 'accounts.id')
            ->leftjoin('contacts', 'contacts.id', '=', 'expense_categories.payee_id')
            ->where('expense_categories.id', $id)->select('expense_account', 'accounts.name', 'contacts.name as payee_name')->first();

        return ['expense_account_id' => !empty($expense_category) ? $expense_category->expense_account : null, 'name' => !empty($expense_category) ? $expense_category->name : null, 'payee_name' => !empty($expense_category) ? $expense_category->payee_name : null];
    }

    public function getExpenseCategoryDropDown()
    {
        $business_id = request()->session()->get('business.id');
        $expense_category = ExpenseCategory::where('business_id', $business_id)->select('name', 'id')->get();

        $html = '<option value="">' . __("lang_v1.please_select") . '</option>';
        foreach ($expense_category as $category) {
            $html .= '<option value="' . $category->id . '">' . $category->name . '</option>';
        }

        return $html;
    }
    public function checkDuplicate(Request $request)
    {
        $business_id = request()->session()->get('business.id');
        $name = $request->name;
        $is_sub_category = $request->is_sub_category;
        $parent_name = $request->parent_name;

        if ($is_sub_category == 0) {
            $expense_category = ExpenseCategory::where('business_id', $business_id)->where('name', $name)->select('id')->first();
            if (!empty($expense_category)) {
                $output = [
                    'success' => '0',
                    'msg' => __('expense.duplicate_name_msg')
                ];
                return $output;
            }
        } else {
            $expense_category = ExpenseCategory::where('business_id', $business_id)->where('is_sub_category', 1)->where('name', $name)->select('id')->first();
            if (!empty($expense_category)) {
                $output = [
                    'success' => '0',
                    'msg' => __('expense.duplicate_name_msg')
                ];
                return $output;
            }
        }


        return null;
    }
}

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
use Modules\MPCS\Entities\Mpcs20FormSettings;
use App\MergedSubCategory;
class F20FormController extends Controller
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
       if (!auth()->check()) {
            return redirect()->route('login');
        } 
        $business_id = request()->session()->get('business.id');
        if (auth()->check() && auth()->user()->can('superadmin'))
            $settings = Mpcs20FormSettings::first();
        else 
            $settings = Mpcs20FormSettings::where('business_id', $business_id)->first();

        $bname = Business::where('id', $business_id)->first();
    
        $form_number = optional($settings)->starting_number ? $settings->starting_number : "";
        $date = optional($settings)->date ? $settings->date : "";
        $userAdded = $bname ? $bname->name : "";

        $merged_sub_categories = MergedSubCategory::where('business_id', $business_id)->get();
        $business_locations = BusinessLocation::forDropdown($business_id);
      
           $business_details = Business::find($business_id);
            $currency_precision = (int) $business_details->currency_precision;
            $qty_precision = (int) $business_details->quantity_precision;
      
       
          $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
          //$settings = MpcsFormSetting::where('business_id', $business_id)->first();
            if (!empty($settings)) {
            $F21c_from_no = $settings->starting_number;
        } else {
            $F21c_from_no = 1;
        }

       $fuelCategoryId = Category::where('name', 'Fuel')->value('id');

       if(auth()->user()->can('superadmin')) {
            $fuelCategory = Category::where('parent_id', $fuelCategoryId)
            ->select(['name', 'id'])
            ->get()->pluck('name', 'id');
        } else {
            $fuelCategory = Category::where('business_id', $business_id)
            ->where('parent_id', $fuelCategoryId)
            ->select(['name', 'id'])
            ->get()->pluck('name', 'id');
        }
        
        $layout = 'layouts.app';
        return view('mpcs::forms.20Form.F20_form')->with(compact(
            
           'F21c_from_no',
            'sub_categories',
            'fuelCategory',
            'currency_precision',
            'qty_precision',
            'merged_sub_categories',
             'business_locations',
             'settings',
             'form_number',
             'date',
             'userAdded',
            'layout'
            ));    
    }
    
    public function get20FormSettings() {

        $business_id = request()->session()->get('user.business_id');
        $pumps = [];
        if (auth()->user()->can('superadmin')) {
            $categories = Category::select(['name', 'id'])
                ->get()
                ->pluck('name', 'id');
        
        } else {
            $categories = Category::where('business_id', $business_id)
                ->select(['name', 'id'])
                ->get()
                ->pluck('name', 'id');
        }


        return view('mpcs::forms.20Form.create_20_form_settings', compact('categories', 'pumps'));

    }
  
    public function store20FormSettings(Request $request) {

        $business_id = session()->get('user.business_id');
        // dd($request->all());
         // Prepare the data for insertion
         $formData = [
            'business_id' => $business_id,
            'opening_date' => $request->date,
            'starting_number' => $request->starting_number,
            'total_sale' => $request->total_sale,
            'cash_sale' => $request->cash_sale,
            'credit_sale' => $request->credit_sale,
            'category' => $request->selected_categories,
            'created_by' => Auth::user()->id       
        ];
        

        // Insert into database
        Mpcs20FormSettings::create($formData);

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_16a_settings_add_success')
        ];

        return $output;
    }
   
    public function mpcs20FormSettings()
    {
        if (request()->ajax()) {
            $header = Mpcs20FormSettings::select('*');
            $business_id = request()->session()->get('user.business_id');
            return DataTables::of($header)
            ->addColumn('action', function ($row) {
                // if (auth()->user()->can('superadmin')) {
                    return '<button href="#" data-href="' . url('/mpcs/edit-20-form-settings/' . $row->id) . '" class="btn-modal btn btn-primary btn-xs" data-container=".update_form_16_a_settings_modal"><i class="fa fa-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</button>';
                // }
                return '';
            })
            ->editColumn('date', function($row) {
                $formattedTime = Carbon::parse($row->time)->format('Y-m-d');
                return $row->date.' '.$formattedTime;
            })
             ->editColumn('starting_number', function($row) {
                return $row->starting_number;
            })
            ->editColumn('total_sale', function($row) {
                return number_format($row->total_sale, 2, '.', ',');
            })
            ->editColumn('cash_sale', function($row) {
                return number_format($row->cash_sale, 2, '.', ',');
            })
            ->editColumn('credit_sale', function($row) {
                return number_format($row->credit_sale, 2, '.', ',');
            })
            ->editColumn('category', function($row) {
                $categoryId = explode(",", $row->category);
                $category = Category::whereIn('id', $categoryId)
                    ->select(['name', 'id'])
                    ->get();
                $html = '';
                foreach($category as $cat){
                    $html .= '<span class="badge badge-primary">'.$cat->name.'</span>';
                }
                return $html;
            })
            ->rawColumns(['action', 'date', 'starting_number', 'total_sale', 'cash_sale', 'credit_sale', 'category'])
            ->make(true);
        }

    }
    
    public function edit20FormSetting($id) {
        $business_id = request()->session()->get('user.business_id');
        $settings = Mpcs20FormSettings::where('business_id', $business_id)->where('id', $id)->first();
        $categoryId = explode(",", $settings->category);

        if(auth()->user()->can('superadmin')) {

            $categories = Category::select(['name', 'id'])
            ->get()->pluck('name', 'id');

        } else {

            $categories = Category::where('business_id', $business_id)
            ->select(['name', 'id'])
            ->get()->pluck('name', 'id');

        }    
        
        return view('mpcs::forms.20Form.edit_20_form_settings')->with(compact(
                    'categories',
                    'categoryId',
                    'settings'
        ));
    }
    
 
    public function mpcs20Update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        // dd($request->all());
        $prev21cDet = Mpcs20FormSettings::find($id);
        
        Mpcs20FormSettings::destroy($id);

        $formData = [
            'business_id' => $business_id,
            'opening_date' => $request->date,
            'starting_number' => $request->starting_number,
            'total_sale' => $request->total_sale,
            'cash_sale' => $request->cash_sale,
            'credit_sale' => $request->credit_sale,
            'category' => $request->selected_categories,
            'created_by' => Auth::user()->id       
        ];
        
        Mpcs20FormSettings::create($formData);

        $output = [
            'success' => 1,
            'msg' => __('mpcs::lang.form_21c_settings_update_success')
        ];

        return $output;
    }
}

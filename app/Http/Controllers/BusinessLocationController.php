<?php

namespace App\Http\Controllers;

use App\Account;
use App\Currency;
use App\Utils\Util;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\BusinessLocation;
use App\Utils\ModuleUtil;
use App\SellingPriceGroup;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use App\Utils\TransactionUtil;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;
use Modules\Property\Entities\PaymentOption;
use Modules\Property\Entities\InstallmentCycle;
use Modules\Property\Entities\PurchaseLandAccount;

class BusinessLocationController extends Controller
{
    protected $moduleUtil;
    protected $commonUtil;
    protected $businessUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param ModuleUtil $moduleUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, Util $commonUtil, BusinessUtil $businessUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $locations = BusinessLocation::where('business_locations.business_id', $business_id)
                ->leftjoin(
                    'currencies as cr',
                    'business_locations.currency_id',
                    '=',
                    'cr.id'
                )
                
                ->leftjoin(
                    'invoice_schemes as ic',
                    'business_locations.invoice_scheme_id',
                    '=',
                    'ic.id'
                )
                ->leftjoin(
                    'invoice_layouts as il',
                    'business_locations.invoice_layout_id',
                    '=',
                    'il.id'
                )
                ->leftjoin(
                    'selling_price_groups as spg',
                    'business_locations.selling_price_group_id',
                    '=',
                    'spg.id'
                )
                ->select([
                    'business_locations.name', 'location_id', 'landmark', 'city', 'zip_code', 'state',
                    'business_locations.country', 'business_locations.id', 'spg.name as price_group','cr.symbol as currency','ic.name as invoice_scheme', 'il.name as invoice_layout', 'business_locations.is_active'
                ]);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $locations->whereIn('business_locations.id', $permitted_locations);
            }

            return Datatables::of($locations)
                ->addColumn(
                    'action',
                    '<button type="button" data-href="{{action(\'BusinessLocationController@edit\', [$id])}}" class="btn btn-xs btn-primary btn-modal" data-container=".location_edit_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                    <a href="{{route(\'location.settings\', [$id])}}" class="btn btn-success btn-xs"><i class="fa fa-wrench"></i> @lang("messages.settings")</a>

                    <button type="button" data-href="{{action(\'BusinessLocationController@activateDeactivateLocation\', [$id])}}" class="btn btn-xs activate-deactivate-location @if($is_active) btn-danger @else btn-success @endif"><i class="fa fa-power-off"></i> @if($is_active) @lang("lang_v1.deactivate_location") @else @lang("lang_v1.activate_location") @endif </button>
                    '
                )
                ->removeColumn('id')
                ->removeColumn('is_active')
                ->rawColumns([10])
                //->rawColumns(['action'])
                ->escapeColumns([])
                ->make(false);
        }

        return view('business_location.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for location quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (!$this->moduleUtil->isQuotaAvailable('locations', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('locations', $business_id);
        }

        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');

        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');

        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $payment_types = $this->commonUtil->payment_types();

        $branch_id = $this->businessUtil->getIdWithIncrement($business_id, 'business_location');

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        $currency = Currency::select(['id',DB::raw("CONCAT(country,' ',currency,' ',symbol)  AS name")])->pluck('name','id');
       

        return view('business_location.create')
            ->with(compact(
                'invoice_layouts',
                'invoice_schemes',
                'price_groups',
                'payment_types',
                'accounts',
                'branch_id',
                'currency'
            ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not, then check for location quota
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            } elseif (!$this->moduleUtil->isQuotaAvailable('locations', $business_id)) {
                return $this->moduleUtil->quotaExpiredResponse('locations', $business_id);
            }

            $input = $request->only([
                'name', 'landmark', 'city', 'state', 'country', 'zip_code', 'invoice_scheme_id',
                'invoice_layout_id', 'mobile', 'alternate_number', 'email', 'website', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'location_id', 'selling_price_group_id','address_1','address_2','address_3','currency_id' 
            ]);

            $input['business_id'] = $business_id;

            // $input['default_payment_accounts'] = !empty($input['default_payment_accounts']) ? json_encode($input['default_payment_accounts']) : null;

            //Update reference count
            $ref_count = $this->moduleUtil->setAndGetReferenceCount('business_location');

            if (empty($input['location_id'])) {
                $input['location_id'] = $this->moduleUtil->generateReferenceNumber('business_location', $ref_count);
            }

            $location = BusinessLocation::create($input);

            //Create a new permission related to the created location
            Permission::create(['name' => 'location.' . $location->id]);
            
            // create advance default account
            $this-> checkCreateAdvances($business_id);
            $this-> checkCreateLand($business_id);
            $this->checkCreateCycles($business_id,'Daily');
            $this->checkCreateCycles($business_id,'Weekly');
            $this->checkCreateCycles($business_id,'Bi-Montly');
            $this->checkCreateCycles($business_id,'Monthly');
            $this->checkCreateCycles($business_id,'Quarterly');
            $this->checkCreateCycles($business_id,'Bi-Annually');
            $this->checkCreateCycles($business_id,'Annually');
            
            
            $output = [
                'success' => true,
                'msg' => __("business.business_location_added_success")
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
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $location = BusinessLocation::where('business_id', $business_id)
            ->find($id);
        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');
        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
            ->get()
            ->pluck('name', 'id');

        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $payment_types = $this->commonUtil->payment_types();

        //Accounts
        $accounts = [];
        //only current assets type accounts 
        $accounts = Account::leftjoin('account_types', 'accounts.account_type_id', 'account_types.id')->where('accounts.business_id', $business_id)->notClosed()->where(function ($query) {
            $query->where('account_types.name', 'Current Assets');
        })->pluck('accounts.name', 'accounts.id');

        $currency = Currency::select(['id',DB::raw("CONCAT(country,' ',currency,' ',symbol)  AS name")])->pluck('name','id');
       
        return view('business_location.edit')
            ->with(compact(
                'location',
                'invoice_layouts',
                'invoice_schemes',
                'price_groups',
                'payment_types',
                'accounts',
                'currency'
            ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only([
                'landmark', 'invoice_scheme_id',
                'invoice_layout_id', 'mobile', 'alternate_number', 'email', 'website', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'selling_price_group_id','address_1','address_2','address_3','currency_id' 
            ]);

            $business_id = $request->session()->get('user.business_id');

            // $input['default_payment_accounts'] = !empty($input['default_payment_accounts']) ? json_encode($input['default_payment_accounts']) : null;

            BusinessLocation::where('business_id', $business_id)
                ->where('id', $id)
                ->update($input);
                
            
            $this-> checkCreateAdvances($business_id);
            $this-> checkCreateLand($business_id);
            $this->checkCreateCycles($business_id,'Daily');
            $this->checkCreateCycles($business_id,'Weekly');
            $this->checkCreateCycles($business_id,'Bi-Montly');
            $this->checkCreateCycles($business_id,'Monthly');
            $this->checkCreateCycles($business_id,'Quarterly');
            $this->checkCreateCycles($business_id,'Bi-Annually');
            $this->checkCreateCycles($business_id,'Annually');

            $output = [
                'success' => true,
                'msg' => __('business.business_location_updated_success')
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
     * Remove the specified resource from storage.
     *
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    
    public function checkCreateAdvances($business_id){
        // insert default if the business was already created without
        $payment_option = PaymentOption::where('business_id', $business_id)
            ->where('payment_option','Advance Amount')
            ->where('is_default',1)
            ->count();
        $linked_id = $this->transactionUtil->account_exist_return_id('Advance Account – Sale of Land Blocks');
        if(!$payment_option > 0){
             PaymentOption::create([
                'business_id' => $business_id,
                'payment_option' => 'Advance Amount',
                'date' => date('Y-m-d'),
                'location_id' => $business_id,
                'created_by' => request()->session()->get('user.id'),
                'is_default' => 1,
                'credit_account' => $linked_id
            ]); 
         } 
             
        return true;
    }
    
    
    public function checkCreateLand($business_id){
        // insert default if the business was already created without
        $payment_option = PurchaseLandAccount::where('business_id', $business_id)
            ->where('payment_option','Advance Amount')
            ->where('is_default',1)
            ->count();
        $linked_id = $this->transactionUtil->account_exist_return_id('Advance Account – Sale of Land Blocks');
        if(!$payment_option > 0){
             PurchaseLandAccount::create([
                'business_id' => $business_id,
                'payment_option' => 'Advance Amount',
                'date' => date('Y-m-d'),
                'location_id' => $business_id,
                'created_by' => request()->session()->get('user.id'),
                'is_default' => 1,
                'credit_account' => $linked_id
            ]); 
         } 
             
        return true;
    }
    
    
    public function checkCreateCycles($business_id,$cycle){
        // insert default if the business was already created without
        $payment_option = InstallmentCycle::where('business_id', $business_id)
            ->where('name',$cycle)
            ->where('is_default',1)
            ->count();
        
        if(!$payment_option > 0){
             InstallmentCycle::create([
                'business_id' => $business_id,
                'name' => $cycle,
                'date' => date('Y-m-d'),
                'cycle_date' => date('Y-m-d'),
                'created_by' => request()->session()->get('user.id'),
                'is_default' => 1
            ]); 
         } 
             
        return true;
    }
    
    public function destroy($id)
    {
        //
    }

    /**
     * Checks if the given location id already exist for the current business.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkLocationId(Request $request)
    {
        $location_id = $request->input('location_id');

        $valid = 'true';
        if (!empty($location_id)) {
            $business_id = $request->session()->get('user.business_id');
            $hidden_id = $request->input('hidden_id');

            $query = BusinessLocation::where('business_id', $business_id)
                ->where('location_id', $location_id);
            if (!empty($hidden_id)) {
                $query->where('id', '!=', $hidden_id);
            }
            $count = $query->count();
            if ($count > 0) {
                $valid = 'false';
            }
        }
        echo $valid;
        exit;
    }

    /**
     * Function to activate or deactivate a location.
     * @param int $location_id
     *
     * @return json
     */
    public function activateDeactivateLocation($location_id)
    {
        if (!auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $business_location = BusinessLocation::where('business_id', $business_id)
                ->findOrFail($location_id);

            $business_location->is_active = !$business_location->is_active;
            $business_location->save();

            $msg = $business_location->is_active ? __('lang_v1.business_location_activated_successfully') : __('lang_v1.business_location_deactivated_successfully');

            $output = [
                'success' => true,
                'msg' => $msg
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
     * Get Location Currency
     **/
    public function getCurrency(Request $request)
    {
        $currency = BusinessLocation::with('currency')->find($request->id)->currency;
        return response()->json([
            'currency' => $currency->symbol,
        ]);
    }
}

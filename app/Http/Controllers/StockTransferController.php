<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\VariationStoreDetail;
use App\PurchaseLine;
use App\Store;
use App\Transaction;
use App\TransactionSellLinesPurchaseLines;
use App\Utils\ModuleUtil;
use App\TransactionSellLine;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $edit_days = request()->session()->get('business.transaction_edit_days');
            $stock_transfers = Transaction::join(
                'business_locations AS l1',
                'transactions.location_id',
                '=',
                'l1.id'
            )
                ->join('transactions as t2', 't2.transfer_parent_id', '=', 'transactions.id')
                ->join(
                    'business_locations AS l2',
                    't2.location_id',
                    '=',
                    'l2.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell_transfer')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.ref_no',
                    'l1.name as location_from',
                    'l2.name as location_to',
                    'transactions.From_Account as from_store',
                    'transactions.To_Account as to_store',
                    'transactions.final_total',
                    'transactions.shipping_charges',
                    'transactions.additional_notes',
                    'transactions.id as DT_RowId'
                );
            return Datatables::of($stock_transfers)
                ->addColumn('action', function ($row) use ($edit_days) {
                    $html = '<button type="button" title="' . __("stock_adjustment.view_details") . '" class="btn btn-primary btn-xs view_stock_transfer"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>';
                    $html .= ' <a href="#" class="print-invoice btn btn-info btn-xs" data-href="' . action('StockTransferController@printInvoice', [$row->id]) . '"><i class="fa fa-print" aria-hidden="true"></i> ' . __("messages.print") . '</a>';
                    $date = \Carbon::parse($row->transaction_date)
                        ->addDays($edit_days);
                    $today = today();
                    if ($date->gte($today)) {
                        $html .= '&nbsp;
                        <button type="button" data-href="' . action("StockTransferController@destroy", [$row->id]) . '" class="btn btn-danger btn-xs delete_stock_transfer"><i class="fa fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '</button>';
                    }
                    return $html;
                })
                ->removeColumn('id')
                ->removeColumn('shipping_charges')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency" data-currency_symbol="true">{{$final_total}}</span>'
                )
                ->editColumn(
                    'from_store',
                    function($row) use($business_id) {
                        $store = Store::where('status', 1)->where('id',$row->from_store)->select('id','name')->first();
                        
                        return $store->name ?? '---';
                    }
                )
                ->editColumn(
                    'to_store',
                    function($row) use($business_id) {
                        $store = Store::where('status', 1)->where('id',$row->to_store)->select('id','name')->first();
                        return $store->name ?? '---';
                    }
                )
                ->editColumn(
                    'shipping_charges',
                    '<span class="display_currency" data-currency_symbol="true">{{$shipping_charges}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->rawColumns(['final_total', 'action', 'shipping_charges'])
                ->make(true);
        }
        return view('stock_transfer.index');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listStores()
    {
        if (!auth()->user()->can('purchase.view') && !auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $edit_days = request()->session()->get('business.transaction_edit_days');
            $stock_transfers = Transaction::join(
                'business_locations AS l1',
                'transactions.location_id',
                '=',
                'l1.id'
            )
                ->join('transactions as t2', 't2.transfer_parent_id', '=', 'transactions.id')
                ->join(
                    'business_locations AS l2',
                    't2.location_id',
                    '=',
                    'l2.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell_transfer')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.ref_no',
                    'l1.name as location_from',
                    'l2.name as location_to',
                    'transactions.final_total',
                    'transactions.shipping_charges',
                    'transactions.additional_notes',
                    'transactions.id as DT_RowId'
                );
            return Datatables::of($stock_transfers)
                ->addColumn('action', function ($row) use ($edit_days) {
                    $html = '<button type="button" title="' . __("stock_adjustment.view_details") . '" class="btn btn-primary btn-xs view_stock_transfer"><i class="fa fa-eye-slash" aria-hidden="true"></i></button>';
                    $html .= ' <a href="#" class="print-invoice btn btn-info btn-xs" data-href="' . action('StockTransferController@printInvoice', [$row->id]) . '"><i class="fa fa-print" aria-hidden="true"></i> ' . __("messages.print") . '</a>';
                    $date = \Carbon::parse($row->transaction_date)
                        ->addDays($edit_days);
                    $today = today();
                    if ($date->gte($today)) {
                        $html .= '&nbsp;
                        <button type="button" data-href="' . action("StockTransferController@destroy", [$row->id]) . '" class="btn btn-danger btn-xs delete_stock_transfer"><i class="fa fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '</button>';
                    }
                    return $html;
                })
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency" data-currency_symbol="true">{{$final_total}}</span>'
                )
                ->editColumn(
                    'shipping_charges',
                    '<span class="display_currency" data-currency_symbol="true">{{$shipping_charges}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->rawColumns(['final_total', 'action', 'shipping_charges'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $orderStatuses = $this->productUtil->orderStatuses();
        return view('stock_transfer.index_stores')
            ->with(compact('business_locations', 'suppliers', 'orderStatuses'));
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');
        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('StockTransferController@index'));
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        /**
         * @ModifiedBy Afes Oktavianus
         * @DateBy 27-05-2021
         * @task 1523
         */
        $default_location = current(array_keys($business_locations->toArray()));
        $ref_count = $this->productUtil->setAndGetReferenceCount('stock_transfer');
        // dd($ref_count);
        //Generate reference number
        $stock_transfer_form_no = $this->productUtil->generateReferenceNumber('stock_transfer', $ref_count);
        // dd($ref_count);
        $temp_data = DB::table('temp_data')->where('business_id', $business_id)->select('stock_transfer_data')->first();
        if (!empty($temp_data)) {
            $temp_data = json_decode($temp_data->stock_transfer_data);
        }
        if (!request()->session()->get('business.popup_load_save_data')) {
            $temp_data = [];
        }
        return view('stock_transfer.create')
            ->with(compact('business_locations', 'temp_data', 'stock_transfer_form_no', 'default_location'));
    }
    public function getBusinessLocationExcept($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::where('business_id', $business_id)->select('id', 'name')->get();
        return $locations;
    }
    public function getBusinessLocationTemp($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::where('business_id', $business_id)->select('id', 'name')->get();
        return $locations;
    }
    public function getBusinessLocationStoreId($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $store = Store::getStores($business_id, request()->input('check_store_not'),$id,request()->input('permission'));
        return $store;
    }
    public function getBusinessLocationStoreIdTemp($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (!empty(request()->input('check_store_not'))) {
            $store = Store::where('business_id', $business_id)->where('status', 1)->where('location_id', $id)->where('id', '!=', request()->input('check_store_not'))->select('id', 'name')->get();
        } else {
            $store = Store::where('business_id', $business_id)->where('status', 1)->where('location_id', $id)->select('id', 'name')->get();
        }
        return $store;
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $business_id = $request->session()->get('user.business_id');
            DB::table('temp_data')->where('business_id', $business_id)->update(['stock_transfer_data' => '']);
            //Check if subscribed or not
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('StockTransferController@index'));
            }
            DB::beginTransaction();
            $input_data = $request->only(['location_id', 'ref_no', 'transaction_date', 'additional_notes', 'shipping_charges', 'final_total']);
            
             $has_reviewed = $this->transactionUtil->hasReviewed($input_data['transaction_date']);
        
            if(!empty($has_reviewed)){
                $output              = [
                    'success' => 0,
                    'msg'     =>__('lang_v1.review_first'),
                ];
                
                return redirect()->back()->with(['status' => $output]);
            }
            
            $reviewed = $this->transactionUtil->get_review($input_data['transaction_date'],$input_data['transaction_date']);
            
            if(!empty($reviewed)){
                $output = [
                    'success' => 0,
                    'msg'     =>"You can't add a transfer for an already reviewed date",
                ];
                
                return redirect()->back()->with(['status' => $output]);
            }
            
            $from_store = $request->input('from_store');
            $to_store = $request->input('to_store');
            $user_id = $request->session()->get('user.id');
            $input_data['final_total'] = $this->productUtil->num_uf($input_data['final_total']);
            $input_data['total_before_tax'] = $input_data['final_total'];
            $input_data['type'] = 'sell_transfer';
            $input_data['business_id'] = $business_id;
            $input_data['created_by'] = $user_id;
            // Code add here
            $input_data['To_Account'] = $to_store;
            $input_data['From_account'] = $from_store;
            // code for the balance quantiity
            // 
            $input_data['transaction_date'] = $this->productUtil->uf_date($input_data['transaction_date'], true);
            $input_data['shipping_charges'] = $this->productUtil->num_uf($input_data['shipping_charges']);
            $input_data['status'] = 'final';
            $input_data['payment_status'] = 'paid';
            //Update reference count
            $ref_count = $this->productUtil->setAndGetReferenceCount('stock_transfer');
            //Generate reference number
            if (empty($input_data['ref_no'])) {
                $input_data['ref_no'] = $this->productUtil->generateReferenceNumber('stock_transfer', $ref_count);
            }
            $products = $request->input('products');
            $sell_lines = [];
            $purchase_lines = [];
            if (!empty($products)) {
                foreach ($products as $product) {
                    $sell_line_arr = [
                        'product_id' => $product['product_id'],
                        'variation_id' => $product['variation_id'],
                        'quantity' => $this->productUtil->num_uf($product['quantity']),
                        'item_tax' => 0,
                        'tax_id' => null
                    ];
                    $variationStoreData=VariationStoreDetail::where('store_id',$from_store)->where('product_id',$product['product_id'])->get();
                    $purchase_line_arr = $sell_line_arr;
                    if (!$variationStoreData->isEmpty()) {
                    $number1=$variationStoreData[0]->qty_available;
                }
                    if($from_store==$to_store)
                    {
                        if (!$variationStoreData->isEmpty()) {
                         $input_data['balance_quantity']=$variationStoreData[0]->qty_available;
                     }
                    }
                    else
                    {
                        if (!$variationStoreData->isEmpty()) {
                         $input_data['balance_quantity']=$variationStoreData[0]->qty_available-$this->productUtil->num_uf($product['quantity']);
                     }
                    }
                    $sell_line_arr['unit_price'] = $this->productUtil->num_uf($product['unit_price']);
                    $sell_line_arr['unit_price_inc_tax'] = $sell_line_arr['unit_price'];
                    $purchase_line_arr['purchase_price'] = $sell_line_arr['unit_price'];
                    $purchase_line_arr['purchase_price_inc_tax'] = $sell_line_arr['unit_price'];
                    if (!empty($product['lot_no_line_id'])) {
                        //Add lot_no_line_id to sell line
                        $sell_line_arr['lot_no_line_id'] = $product['lot_no_line_id'];
                        //Copy lot number and expiry date to purchase line
                        $lot_details = PurchaseLine::find($product['lot_no_line_id']);
                        $purchase_line_arr['lot_number'] = $lot_details->lot_number;
                        $purchase_line_arr['mfg_date'] = $lot_details->mfg_date;
                        $purchase_line_arr['exp_date'] = $lot_details->exp_date;
                    }
                    $sell_lines[] = $sell_line_arr;
                    $purchase_lines[] = $purchase_line_arr;
                }
            }
            //set the store id
            $input_data['store_id'] = $request->input('from_store');
            //Create Sell Transfer transaction
            $sell_transfer = Transaction::create($input_data);
            //Create Purchase Transfer at transfer location
            $input_data['type'] = 'purchase_transfer';
            $input_data['status'] = 'received';
            $input_data['location_id'] = $request->input('transfer_location_id');
            $input_data['transfer_parent_id'] = $sell_transfer->id;
            //transfer to store id
            $input_data['store_id'] = $request->input('to_store');
            $purchase_transfer = Transaction::create($input_data);
            //Sell Product from first location
            if (!empty($sell_lines)) {
                $this->transactionUtil->createOrUpdateSellLines($sell_transfer, $sell_lines, $input_data['location_id']);
            }
            //Purchase product in second location
            if (!empty($purchase_lines)) {
                $purchase_transfer->purchase_lines()->createMany($purchase_lines);
            }
            //Decrease product stock from sell location
            //And increase product stock at purchase location
            foreach ($products as $product) {
                if ($product['enable_stock']) {
                    $this->productUtil->decreaseProductQuantity(
                        $product['product_id'],
                        $product['variation_id'],
                        $sell_transfer->location_id,
                        $this->productUtil->num_uf($product['quantity']),
                        0,
                        'decrease'
                    );
                    
                    $this->productUtil->decreaseProductQuantityStore(
                        $product['product_id'],
                        $product['variation_id'],
                        $sell_transfer->location_id,
                        $this->productUtil->num_uf($product['quantity']),
                        $from_store,
                        "decrease",
                        0
                    );
                    
                    $this->productUtil->updateProductQuantity(
                        $purchase_transfer->location_id,
                        $product['product_id'],
                        $product['variation_id'],
                        $product['quantity']
                    );
                    $this->productUtil->updateProductQuantityStore(
                        $purchase_transfer->location_id,
                        $product['product_id'],
                        $product['variation_id'],
                        $product['quantity'],
                        $to_store
                    );
                }
            }
            //Adjust stock over selling if found
            $this->productUtil->adjustStockOverSelling($purchase_transfer);
            //Map sell lines with purchase lines
            $business = [
                'id' => $business_id,
                'accounting_method' => $request->session()->get('business.accounting_method'),
                'location_id' => $sell_transfer->location_id
            ];
            // dd($sell_transfer->sell_lines);
            $this->transactionUtil->mapPurchaseSell($business, $sell_transfer->sell_lines, 'purchase');
            $output = [
                'success' => 1,
                'msg' => __('lang_v1.stock_transfer_added_successfully')
            ];
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => $e->getMessage()
            ];
        }
        return redirect('stock-transfers')->with('status', $output);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }
        $stock_adjustment_details = Transaction::join(
            'transaction_sell_lines as sl',
            'sl.transaction_id',
            '=',
            'transactions.id'
        )
            ->join('products as p', 'sl.product_id', '=', 'p.id')
            ->join('variations as v', 'sl.variation_id', '=', 'v.id')
            ->join('product_variations as pv', 'v.product_variation_id', '=', 'pv.id')
            ->where('transactions.id', $id)
            ->where('transactions.type', 'sell_transfer')
            ->leftjoin('purchase_lines as pl', 'sl.lot_no_line_id', '=', 'pl.id')
            ->select(
                'p.name as product',
                'p.type as type',
                'pv.name as product_variation',
                'v.name as variation',
                'v.sub_sku',
                'sl.quantity',
                'sl.unit_price',
                'pl.lot_number',
                'pl.exp_date',
                'transactions.shipping_charges',
                'transactions.additional_notes'
            )
            ->groupBy('sl.id')
            ->get();
        $lot_n_exp_enabled = false;
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_n_exp_enabled = true;
        }
        return view('stock_transfer.partials.details')
            ->with(compact('stock_adjustment_details', 'lot_n_exp_enabled'));
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            if (request()->ajax()) {
                $edit_days = request()->session()->get('business.transaction_edit_days');
                if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
                    return [
                        'success' => 0,
                        'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])
                    ];
                }
                //Get sell transfer transaction
                $sell_transfer = Transaction::where('id', $id)
                    ->where('type', 'sell_transfer')
                    ->with(['sell_lines'])
                    ->first();
                    
                    
                 $has_reviewed = $this->transactionUtil->hasReviewed($sell_transfer->transaction_date);
        
                if(!empty($has_reviewed)){
                    $output              = [
                        'success' => 0,
                        'msg'     =>__('lang_v1.review_first'),
                    ];
                    
                    return redirect()->back()->with(['status' => $output]);
                }
                    
                $reviewed = $this->transactionUtil->get_review($sell_transfer->transaction_date,$sell_transfer->transaction_date);
                if(!empty($reviewed)){
                    $output = [
                        'success' => 0,
                        'msg'     =>"You can't add a transfer for an already reviewed date",
                    ];
                    
                    return  $output;
                }
            
                //Get purchase transfer transaction
                $purchase_transfer = Transaction::where('transfer_parent_id', $sell_transfer->id)
                    ->where('type', 'purchase_transfer')
                    ->with(['purchase_lines'])
                    ->first();
                    
                    
                $has_reviewed = $this->transactionUtil->hasReviewed($purchase_transfer->transaction_date);
        
                if(!empty($has_reviewed)){
                    $output              = [
                        'success' => 0,
                        'msg'     =>__('lang_v1.review_first'),
                    ];
                    
                    return redirect()->back()->with(['status' => $output]);
                }
            
                
                $reviewed = $this->transactionUtil->get_review($purchase_transfer->transaction_date,$purchase_transfer->transaction_date);
                if(!empty($reviewed)){
                    $output = [
                        'success' => 0,
                        'msg'     =>"You can't add a transfer for an already reviewed date",
                    ];
                    
                    return  $output;
                }    
                    
                //Check if any transfer stock is deleted and delete purchase lines
                $purchase_lines = $purchase_transfer->purchase_lines;
                foreach ($purchase_lines as $purchase_line) {
                    if ($purchase_line->quantity_sold > 0) {
                        return [
                            'success' => 0,
                            'msg' => __('lang_v1.stock_transfer_cannot_be_deleted')
                        ];
                    }
                }
                DB::beginTransaction();
                //Get purchase lines from transaction_sell_lines_purchase_lines and decrease quantity_sold
                $sell_lines = $sell_transfer->sell_lines;
                $deleted_sell_purchase_ids = [];
                $products = []; //variation_id as array
                foreach ($sell_lines as $sell_line) {
                    $purchase_sell_line = TransactionSellLinesPurchaseLines::where('sell_line_id', $sell_line->id)->first();
                    if (!empty($purchase_sell_line)) {
                        //Decrease quntity sold from purchase line
                        PurchaseLine::where('id', $purchase_sell_line->purchase_line_id)
                            ->decrement('quantity_sold', $sell_line->quantity);
                        $deleted_sell_purchase_ids[] = $purchase_sell_line->id;
                        //variation details
                        if (isset($products[$sell_line->variation_id])) {
                            $products[$sell_line->variation_id]['quantity'] += $sell_line->quantity;
                            $products[$sell_line->variation_id]['product_id'] = $sell_line->product_id;
                        } else {
                            $products[$sell_line->variation_id]['quantity'] = $sell_line->quantity;
                            $products[$sell_line->variation_id]['product_id'] = $sell_line->product_id;
                        }
                    }
                }
                //Update quantity available in both location
                if (!empty($products)) {
                    foreach ($products as $key => $value) {
                        //Decrease from location 2
                        $this->productUtil->decreaseProductQuantity(
                            $products[$key]['product_id'],
                            $key,
                            $purchase_transfer->location_id,
                            $products[$key]['quantity'],
                            0,
                            'decrease'
                        );
                        
                        $this->productUtil->decreaseProductQuantityStore(
                            $products[$key]['product_id'],
                            $key,
                            $purchase_transfer->location_id,
                            $products[$key]['quantity'],
                            $purchase_transfer->store_id,
                            "decrease",
                            0
                        );
                        
                        //Increase in location 1
                        $this->productUtil->updateProductQuantity(
                            $sell_transfer->location_id,
                            $products[$key]['product_id'],
                            $key,
                            $products[$key]['quantity']
                        );
                        
                        $this->productUtil->updateProductQuantityStore(
                            $sell_transfer->location_id,
                            $products[$key]['product_id'],
                            $key,
                            $products[$key]['quantity'],
                            $purchase_transfer->store_id
                        );
                    
                    }
                }
                //Delete sale line purchase line
                if (!empty($deleted_sell_purchase_ids)) {
                    TransactionSellLinesPurchaseLines::whereIn('id', $deleted_sell_purchase_ids)
                        ->delete();
                }
                //Delete both transactions
                $sell_transfer->delete();
                $purchase_transfer->delete();
                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.stock_transfer_delete_success')
                ];
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return $output;
    }
    /**
     * Checks if ref_number and supplier combination already exists.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');
            $sell_transfer = Transaction::where('business_id', $business_id)
                ->where('id', $id)
                ->where('type', 'sell_transfer')
                ->with(
                    'contact',
                    'sell_lines',
                    'sell_lines.product',
                    'sell_lines.variations',
                    'sell_lines.variations.product_variation',
                    'sell_lines.lot_details',
                    'location',
                    'sell_lines.product.unit'
                )
                ->first();
            $purchase_transfer = Transaction::where('business_id', $business_id)
                ->where('transfer_parent_id', $sell_transfer->id)
                ->where('type', 'purchase_transfer')
                ->first();
            $location_details = ['sell' => $sell_transfer->location, 'purchase' => $purchase_transfer->location];
            $lot_n_exp_enabled = false;
            if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                $lot_n_exp_enabled = true;
            }
            $output = ['success' => 1, 'receipt' => []];
            $output['receipt']['html_content'] = view('stock_transfer.print', compact('sell_transfer', 'location_details', 'lot_n_exp_enabled'))->render();
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];
        }
        return $output;
    }
}

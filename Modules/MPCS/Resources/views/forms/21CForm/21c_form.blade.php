<style>
   .rows {
    padding: 0 !important; 
    margin: 0 !important;
}
.full-width-input {
    width: 100% !important; 
    box-sizing: border-box; 
    display: block; 
    padding: 5px; 
    margin: 0;
    border: 1px solid #ccc;
    height: 100%; 
}

.table tbody tr td.rows {
    padding: 0 !important;
    vertical-align: middle !important;
}

</style>
<!-- Main content -->
<section class="content">
{!! Form::open(['id' => 'f21c_form']) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('16a_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('16a_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('form_16a_date', __('report.date') . ':') !!}
                    {!! Form::text('form_16a_date', @format_date(date('Y-m-d')), ['class' => 'form-control input_number customer_transaction_date', 'id' =>
                      'form_16a_date','required','readonly']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('type', __('mpcs::lang.F16a_from_no') . ':') !!}
                    {!! Form::text('F21c_from_no', $F21c_from_no ?? '', ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>


            @endcomponent
        </div>
    </div>

    <div class="row" id ="print-area">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="col-md-12">
                <div class="row" style="margin-top: 14px;">
                    <div class="text-center">
                     <h4 style="font-weight: bold;">{{request()->session()->get('business.name')}}</h4><br> 
                    </div>
                </div>
                <div class="row" style="margin-top: 14px;">
                    <div class="row text-right" style="display: flex; justify-content: end;">
                     <h3 style="font-weight: bold; margin-right: 20px;">21C</h3>
                     <button type="submit" name="submit_type" id="f21c_print" value="print"
                     class="btn btn-primary pull-right">@lang('mpcs::lang.print')</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-red" style="margin-top: 14px;">
                        <h5 style="font-weight: bold;" class="text-red">Manager Name: {{ optional($settings)->manager_name }}</h5>
                        <input type="hidden" name="manager_name" value="{{ optional($settings)->manager_name }}" />
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <div style="display: flex;">
                                <h5 style="font-weight: bold;">Filling Station Date : </h5><input class="form-control input_number customer_transaction_date hasDatepicker" id="form_21c_date" required="" readonly="" name="form_21c_date" type="text" style="width: 200px; margin-left: 30px;">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h5 style="font-weight: bold;">Balance Stock For The Day : </h5>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center pull-left">
                            <h5 style="font-weight: bold;" class="text-red">Form No: {{ $F21c_from_no }}</h5>
                        </div>
                    </div>
                </div><br>
              
                <div class="table-responsive">
        @php
        $columnsArray = array(
            'receipts' => 'Receipts',
            'today' => 'Today',
            'previous_day' => 'Previous Day',
            'price_increment_today' => 'Price Increment Today',
            'price_increment_previous_day' => 'Price Increment Previous Day',
            'previous_day' => 'Previous Day',
            'total_receipts' => 'Total Receipts',
            'opening_stock' => 'Opening Stock',
            'total_receipts_today' => 'Total Receipts Today', 
            'issue' => 'Issue',
            'cash_for_today' => 'Cash for Today',
            'credit_for_today' => 'Credit for Today',
            'cooperative_section_for_today' => 'Cooperative Section for Today',
            'total_issues' => 'Total Issues',
            'issues_up_to_last_day' => 'Issues up to Last Day',
            'total_issues_one' => 'Total Issues (1)',
            'price_discounts_for_today' => 'Price Discounts for Today',
            'pre_date' => 'Pre Date',
            'total_discounts' => 'Total Discounts (2)',
            'total_for_today_one_plus_two' => 'Total for Today (1 + 2)',
            'balances' => 'Balances',
            'sub_total_for_today' => 'Sub Total for Today',
            'pump_meters' => 'Pump Meters'
            );

            $pumps = ($settings) ? json_decode($settings->pumps, true) : [];

            if (!empty($pumps)) {
                foreach ($pumps as $pump) {
                    $columnsArray[$pump] = \Modules\Petro\Entities\Pump::where('id', $pump)->value('pump_name');
                }
            }

            $columnsArray['issued_qty_for_today'] = 'Issued Qty for Today';

        @endphp    
           <table class="table table-bordered" id="form_21c_table">
    <thead>
        <tr>
            <th rowspan="3">@lang('mpcs::lang.description')</th>
            <th rowspan="3">@lang('mpcs::lang.no')</th>
            @foreach ($fuelCategory as $categoryName)
                <th colspan="2">{{ $categoryName }}</th>
            @endforeach
                <th colspan="2">Total</th>
        </tr>
        <tr>
            @foreach ($fuelCategory as $categoryName)
                <th colspan="2">@lang('mpcs::lang.tank_capacity')</th>
            @endforeach
            <th colspan="2">@lang('mpcs::lang.tank_capacity')</th>
        </tr>
        <tr>
            @foreach ($fuelCategory as $categoryName)
                <th>Qty</th>
                <th>@lang('mpcs::lang.value_in_sale_price')</th>
            @endforeach
                <th>Qty</th>
                <th>@lang('mpcs::lang.value_in_sale_price')</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($columnsArray as $colKey => $column)
    
<tr>
    @php $color = (($colKey == 'receipts' || $colKey == 'issue' || $colKey == 'pump_meters')) ? 'color: #00c0ff; font-weight: bold;': ''; @endphp
    <td style="{{ $color }}">{{ $column }}</td>

    @if ($colKey == 'receipts' || $colKey == 'issue' || $colKey == 'pump_meters')  
        <td colspan="{{ count($fuelCategory) }}"></td>
    @else 
        <td class="rows">
            <input type="number" step="0.01" name="{{ $colKey }}[no]" class="full-width-input">
        </td>
        @php
            $TotalQty = 0;
            $TotalAmount = 0;
        @endphp

        @foreach ($fuelCategory as $categoryKey => $categoryName)
        
@php
    //get category data to query product list
    $catData = \App\Category::where(['name'=>'Fuel'])->first();
    $subCatData = \App\Category::where(['name'=>$categoryName,'parent_id'=>$catData->id])->first();
    $productData = \App\Product::where(['category_id'=>$catData->id,'sub_category_id'=>$subCatData->id])->first();

    $columnName = $colKey;
    
    $qtyToday    = 0;
    $amountToday = 0;

    $qtyPrevDay    = 0;
    $amountPrevDay = 0;

    
    //current date data show here
    if($columnName == 'today'):

        if($productData):
            $qtyData = \App\PurchaseLine::where(['product_id'=>$productData->id])->whereDate('created_at', '=', date('Y-m-d'))->get();
        else:
            $qtyData = '';
        endif;
        if($qtyData):
            $qtyTotal = $qtyData->sum('quantity');
            $purchase_price = $qtyData->sum('purchase_price');
            $amountTotal = round($purchase_price*$qtyTotal,2);
        endif;
    endif;

    //previous date data show here
    
    if($columnName == 'previous_day'):

        if($productData):
            $qtyData = \App\PurchaseLine::where(['product_id'=>$productData->id])->whereDate('created_at', '=', date('Y-m-d', strtotime( '-1 days' )))->get();
        else:
            $qtyData = '';
        endif;
        if($qtyData):
            $qtyTotal = $qtyData->sum('quantity');
            $purchase_price = $qtyData->sum('purchase_price');
            $amountTotal = round($purchase_price*$qtyTotal,2);
        endif;
    endif;

    //previous date data show here

    if($columnName == 'total_receipts'):
        if($productData):
            echo $qtyDataTotal = \App\PurchaseLine::where(['product_id'=>$productData->id])->whereDate('created_at', '=', date('Y-m-d'))->whereDate('created_at', '=', date('Y-m-d', strtotime( '-1 days' )))->get();
        else:
            $qtyDataTotal = '';
        endif;
        if($qtyDataTotal):
            $qtyTotal = $qtyDataTotal->sum('quantity');
            $purchase_price = $qtyDataTotal->sum('purchase_price');
            $amountTotal = round($purchase_price*$qtyTotal,2);
        endif;
    endif;
        
    $TotalQty      += $qtyTotal;
    $TotalAmount   += $amountTotal;
    
@endphp
            <td class="rows">
                <input type="number" step="0.01" name="{{ $colKey }}[{{ $categoryKey }}][qty]" class="full-width-input" value="{{ $qtyTotal }}" readonly>
            </td>
            <td class="rows">
                <input type="number" step="0.01" name="{{ $colKey }}[{{ $categoryKey }}][val]" value="{{ $amountTotal }}" class="full-width-input">
            </td>
        @endforeach
            <td class="rows">
                <input type="number" step="0.01" name="{{ $colKey }}[{{ $categoryKey }}][qty]" class="full-width-input" value="{{ $TotalQty }}" readonly>
            </td>
            <td class="rows">
                <input type="number" step="0.01" name="{{ $colKey }}[{{ $categoryKey }}][val]" value="{{ $TotalAmount }}" class="full-width-input">
            </td>
        @endif
    </tr>
@endforeach
    </tbody>
</table>
    

            </div>

            <div class="row">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('finalize', 1, false, ['class' => 'input-icheck', 'id' => 'finalize']); !!}
                                That all the details are entered correctly
                            </label>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-md-6">
                            <p>@lang('mpcs::lang.checked_by')____________</p>  <br>
                            <p>@lang('mpcs::lang.date')____________</p> 
                        </div>
                        <div class="col-md-6 text-right">
                            <p>@lang('mpcs::lang.manage_signature')____________</p>  <br>
                            <p>@lang('mpcs::lang.date')____________</p> 
                        </div>
                    </div>

            </div>

            {!! Form::close() !!}

            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->

<script>

function printDiv(divId) {
        // Get the content of the div
        var divContent = document.getElementById(divId).innerHTML;

        // Open a new window
        var printWindow = window.open('', '', 'width=900,height=700');

        // Write the content into the new window
        printWindow.document.write('<html><head><title>Print Table</title>');
        printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
        printWindow.document.write('<style>table{border: 1px solid black}width: 100%; border-collapse: collapse; } th, td { border: 1px solid black; #form_21c_table input { border: none; outline: none;  width: 50px !important; text-align: center;  background: transparent;  }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(divContent); // Insert the div content
        printWindow.document.write('</body></html>');

        // Close the document and print
        printWindow.document.close();
        printWindow.print();
    }


    $(document).ready(function(){
        $('#form_21c_date').datepicker({
    autoclose: true, // Ensures the calendar closes after selection
    format: 'dd/mm/yyyy' // Adjust format as needed
}).datepicker('setDate', new Date())    });
</script>
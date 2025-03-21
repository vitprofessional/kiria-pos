@php
$hide_tax = '';
if( session()->get('business.enable_inline_tax') == 0){
$hide_tax = 'hide';
}
$currency_precision = config('constants.currency_precision', 2);
$quantity_precision = config('constants.quantity_precision', 2);
$business_id = request()->session()->get('user.business_id');
$enable_free_qty = App\Business::where('id', $business_id)->select('enable_free_qty')->first()->enable_free_qty;
@endphp
<style>
    .current_stock_td {
        display: none;
    }
</style>
<div class="table-responsive">
    <table class="table table-condensed table-bordered table-th-green text-center table-striped"
        id="purchase_entry_table">
        <thead>
            <tr>
				<th style="width: 2%;" >#</th>
				<th style="width: 10%;">@lang( 'product.product_name' )</th>
				<th style="width: 8%;">@lang( 'purchase.purchase_quantity' )</th>
				<th style="width: 8%;">@lang( 'lang_v1.unit_cost_before_discount' )</th>
				<th style="width: 8%;">@lang( 'lang_v1.discount_percent' )</th>
				<th style="width: 8%;">@lang( 'purchase.unit_cost_before_tax' )</th>
				<th class="{{$hide_tax}}"  style="width: 8%;">@lang( 'purchase.subtotal_before_tax' )</th>
				<th class="{{$hide_tax}}"  style="width: 8%;">@lang( 'purchase.product_tax' )</th>
				<th class="{{$hide_tax}}"  style="width: 8%;">@lang( 'purchase.net_cost' )</th>
				<th  style="width: 8%;">@lang( 'purchase.line_total' )</th>
				<th style="width: 8%;" class="@if(!session('business.enable_editing_product_from_purchase')) hide @endif">
					@lang( 'lang_v1.profit_margin' )
				</th>
				<th style="width: 6%;">
					@lang( 'purchase.unit_selling_price' )
					<small>(@lang('product.inc_of_tax'))</small>
				</th>
				@if(session('business.enable_lot_number'))
				<th style="width: 6%;">
					@lang('lang_v1.lot_number')
				</th>
				@endif
				@if(session('business.enable_product_expiry'))
				<th style="width: 6%;">
					@lang('product.mfg_date') / @lang('product.exp_date')
				</th>
				@endif
				<th style="width: 2%;"><i class="fa fa-trash" aria-hidden="true"></i></th>
			</tr>
        </thead>
        <tbody>
            <?php $row_count = 0; ?>
            @foreach($purchase->purchase_lines as $purchase_line)
            <tr>
                <td><span class="sr_number"></span></td>
                <td>
                    {{ $purchase_line->product->name }} ({{$purchase_line->variations->sub_sku}})
                    @if( $purchase_line->product->type == 'variable')
                    <br />(<b>{{ $purchase_line->variations->product_variation->name}}</b> :
                    {{ $purchase_line->variations->name}})
                    @endif
                </td>

                <td style="line-height: 2px !important;">
                    {!! Form::hidden('purchases[' . $loop->index . '][product_id]', $purchase_line->product_id, ['class' => 'hidden_product_id'] ); !!}
                    {!! Form::hidden('purchases[' . $loop->index . '][variation_id]', $purchase_line->variation_id, ['class' => 'hidden_variation_id'] );
                    !!}
                    {!! Form::hidden('purchases[' . $loop->index . '][purchase_line_id]', $purchase_line->id, ['class' => 'hidden_purchase_id']); !!}

                    @php
                    $check_decimal = 'false';
                    if($purchase_line->product->unit->allow_decimal == 0){
                    $check_decimal = 'true';
                    }
                    @endphp

                    {!! Form::text('purchases[' . $loop->index . '][quantity]',
                    number_format($purchase_line->quantity - $purchase_line->bonus_qty, $quantity_precision,
                    $currency_details->decimal_separator, $currency_details->thousand_separator),
                    ['class' => 'form-control input-sm purchase_quantity input_number mousetrap', 'required',
                    'data-rule-abs_digit' => $check_decimal, 'data-msg-abs_digit' =>
                    __('lang_v1.decimal_value_not_allowed'), 'id' => 'product_id'.$purchase_line->product_id]); !!}

                    <input type="hidden" class="base_unit_cost"
                        value="{{$purchase_line->variations->default_purchase_price}}">
                        <br>
                        @if(!empty($purchase_line->sub_units_options))
                        
                        <select name="purchases[{{$loop->index}}][sub_unit_id]" class="form-control input-sm sub_unit">
                            @foreach($purchase_line->sub_units_options as $sub_units_key => $sub_units_value)
                            <option value="{{$sub_units_key}}" data-multiplier="{{$sub_units_value['multiplier']}}"
                                @if($sub_units_key==$purchase_line->sub_unit_id) selected @endif>
                                {{$sub_units_value['name']}}
                            </option>
                            @endforeach
                        </select>
                        @else
                        <input type="text" class="form-control" value="{{ $purchase_line->product->unit->short_name }}">
                        
                        @endif
    
                        <input type="hidden" name="purchases[{{$loop->index}}][product_unit_id]"
                            value="{{$purchase_line->product->unit->id}}">
    
                        <input type="hidden" class="base_unit_selling_price"
                            value="{{$purchase_line->variations->sell_price_inc_tax}}">
                        
                        <br>
                         @if ($enable_free_qty)
                        
                            {!! Form::text('purchases[' . $loop->index . '][free_qty]',
                            number_format($purchase_line->bonus_qty, $quantity_precision, $currency_details->decimal_separator,
                            $currency_details->thousand_separator),
                            ['class' => 'form-control input-sm purchase_bonus_qty input_number mousetrap', 'required',
                            'data-rule-abs_digit' => $check_decimal, 'data-msg-abs_digit' =>
                            __('lang_v1.decimal_value_not_allowed')]); !!}
                        
                        @endif
                </td>
                
               

                <td>
                    @php
                        \Log::debug("$purchase_line->pp_without_discount / $purchase->exchange_rate", ["answer"=>($purchase_line->pp_without_discount/$purchase->exchange_rate)]);
                    @endphp
                    {!! Form::text('purchases[' . $loop->index . '][pp_without_discount]',
                    number_format(($purchase_line->pp_without_discount/$purchase->exchange_rate), 6, $currency_details->decimal_separator,
                            ''), ['class' => 'form-control input-sm
                    purchase_unit_cost_without_discount input_number', 'required']); !!}
                </td>
                <td>
                    {!! Form::text('purchases[' . $loop->index . '][discount_percent]',
                    number_format($purchase_line->discount_percent, $currency_precision,
                    $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' =>
                    'form-control input-sm inline_discounts input_number', 'required']); !!} <b>%</b>
                </td>
                <td>
                    {!! Form::text('purchases[' . $loop->index . '][purchase_price_display]',
                    number_format(($purchase_line->purchase_price/$purchase->exchange_rate), 6, $currency_details->decimal_separator,
                            ''), ['class' => 'form-control input-sm
                    purchase_unit_cost input_number', 'required']); !!}
                    {!! Form::hidden('purchases[' . $loop->index . '][purchase_price]',
                    $purchase_line->purchase_price/$purchase->exchange_rate, ['class' => 'pp_exc_tax purchase_unit_cost', 'required']); !!}
                </td>
                <td class="{{$hide_tax}}">
                    <span class="row_subtotal_before_tax">
                        {{number_format($purchase_line->quantity * $purchase_line->purchase_price/$purchase->exchange_rate, 6, $currency_details->decimal_separator, $currency_details->thousand_separator)}}
                    </span>
                    <input type="hidden" class="row_subtotal_before_tax_hidden"
                        value="{{($purchase_line->quantity * $purchase_line->purchase_price/$purchase->exchange_rate)}}">
                </td>

                <td style="line-height: 2px !important;" class="{{$hide_tax}}">
                    <div class="form-group">
                        <select name="purchases[{{ $loop->index }}][purchase_line_tax_id]"
                            class="form-control purchase_line_tax_id" placeholder="'Please Select'">
                            <option value="" data-tax_amount="0" @if( empty( $purchase_line->tax_id ) )
                                selected @endif >@lang('lang_v1.none')</option>
                            @foreach($taxes as $tax)
                            <option value="{{ $tax->id }}" data-tax_amount="{{ $tax->amount }}" @if( $purchase_line->
                                tax_id == $tax->id) selected @endif >{{ $tax->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" hidden>
                        <span class="input-group-addon purchase_product_unit_tax_text">
                            {{number_format($purchase_line->item_tax/$purchase->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}
                        </span>
                        {!! Form::hidden('purchases[' . $loop->index . '][item_tax]',
                        number_format($purchase_line->item_tax/$purchase->exchange_rate, $currency_precision,
                        $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' =>
                        'purchase_product_unit_tax']); !!}
                    </div>
                </td>
                <td class="{{$hide_tax}}">
                    {!! Form::text('purchases[' . $loop->index . '][purchase_price_inc_tax]',
                    $purchase_line->purchase_price_inc_tax/$purchase->exchange_rate, ['class' =>
                    'form-control input-sm purchase_unit_cost_after_tax input_number',  'required']); !!}
                </td>
                <td>
                    <span class="row_subtotal_after_tax">
                        {{number_format($purchase_line->purchase_price_inc_tax * $purchase_line->quantity/$purchase->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}
                    </span>
                    <input type="hidden" class="row_subtotal_after_tax_hidden"
                        value="{{number_format($purchase_line->purchase_price_inc_tax * $purchase_line->quantity/$purchase->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}">
                </td>

                <td class="@if(!session('business.enable_editing_product_from_purchase')) hide @endif">
                    @php
                    $pp = $purchase_line->purchase_price_inc_tax;
                    $sp = $purchase_line->variations->sell_price_inc_tax;
                    if(!empty($purchase_line->sub_unit->base_unit_multiplier)) {
                    $sp = $sp * $purchase_line->sub_unit->base_unit_multiplier;
                    }
                    if($pp == 0){
                    $profit_percent = 100;
                    } else {
                    $profit_percent = (($sp - $pp) * 100 / $pp);
                    }
                    @endphp

                    {!! Form::text('purchases[' . $loop->index . '][profit_percent]',
                    number_format($profit_percent, $currency_precision, $currency_details->decimal_separator,
                    $currency_details->thousand_separator),
                    ['class' => 'form-control input-sm input_number profit_percent', 'required']); !!}
                </td>

                <td>
                    @if(session('business.enable_editing_product_from_purchase'))
                    {!! Form::text('purchases[' . $loop->index . '][default_sell_price]', number_format($sp,
                    6, $currency_details->decimal_separator, $currency_details->thousand_separator),
                    ['class' => 'form-control input-sm input_number default_sell_price', 'required']); !!}
                    @else
                    {{number_format($sp, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}
                    @endif

                </td>
                @if(session('business.enable_lot_number'))
                <td>
                    {!! Form::text('purchases[' . $loop->index . '][lot_number]', $purchase_line->lot_number, ['class'
                    => 'form-control input-sm']); !!}
                </td>
                @endif

                @if(session('business.enable_product_expiry'))
                <td style="text-align: left;">
                    @php
                    $expiry_period_type = !empty($purchase_line->product->expiry_period_type) ?
                    $purchase_line->product->expiry_period_type : 'month';
                    @endphp
                    @if(!empty($expiry_period_type))
                    <input type="hidden" class="row_product_expiry"
                        value="{{ $purchase_line->product->expiry_period }}">
                    <input type="hidden" class="row_product_expiry_type" value="{{ $expiry_period_type }}">

                    @if(session('business.expiry_type') == 'add_manufacturing')
                    @php
                    $hide_mfg = false;
                    @endphp
                    @else
                    @php
                    $hide_mfg = true;
                    @endphp
                    @endif

                    <b class="@if($hide_mfg) hide @endif"><small>@lang('product.mfg_date'):</small></b>
                    @php
                    $mfg_date = null;
                    $exp_date = null;
                    if(!empty($purchase_line->mfg_date)){
                    $mfg_date = $purchase_line->mfg_date;
                    }
                    if(!empty($purchase_line->exp_date)){
                    $exp_date = $purchase_line->exp_date;
                    }
                    @endphp
                    <div class="input-group @if($hide_mfg) hide @endif">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text('purchases[' . $loop->index . '][mfg_date]', !empty($mfg_date) ?
                        @format_date($mfg_date) : null, ['class' => 'form-control input-sm expiry_datepicker mfg_date']); !!}
                    </div>
                    <b><small>@lang('product.exp_date'):</small></b>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text('purchases[' . $loop->index . '][exp_date]', !empty($exp_date) ?
                        @format_date($exp_date) : null, ['class' => 'form-control input-sm expiry_datepicker exp_date']); !!}
                    </div>
                    @else
                    <div class="text-center">
                        @lang('product.not_applicable')
                    </div>
                    @endif
                </td>
                @endif

                <td></td>
                <input type="hidden" name="is_fuel_category"
                    value="@if(!empty($purchase_line->product->category->name) && $purchase_line->product->category->name == 'Fuel'){{1}}@else{{0}}@endif">
            </tr>
            <?php $row_count = $loop->index + 1 ; ?>
            @endforeach
        </tbody>
    </table>
</div>
<input type="hidden" id="row_count" value="{{ $row_count }}">

<div class="payment_details_div @if( $payment_line['method'] !== 'card' ) {{ 'hide' }} @endif" data-type="card" >

	
	<div class="col-md-4">
		<div class="form-group">			
			{!! Form::label("card_transaction_number_$row_index",__('lang_v1.card_transaction_no')) !!}

			@if(isset($view_page) || isset($edit_page))
				{!! Form::text("payment[$row_index][card_transaction_number]",$data[0]->t_card_transaction_number ?? null, ['class' => 'form-control', 'placeholder' => __('lang_v1.card_transaction_no'), 'id' => "card_transaction_number_$row_index", !empty($edit) ? 'disabled' : '',isset($view_page) ? "disabled" : '']); !!}
			@else
				{!! Form::text("payment[$row_index][card_transaction_number]",$payment_line['card_transaction_number'] ?? null, ['class' => 'form-control', 'placeholder' => __('lang_v1.card_transaction_no'), 'id' => "card_transaction_number_$row_index", !empty($edit) ? 'disabled' : '']); !!}
			@endif
		</div>
	</div>
</div>

<div class="payment_details_div cheque_payment_details  @if( $payment_line['method'] !== 'cheque' ) {{ 'hide' }} @endif" data-type="cheque">
	@if(!empty($payment->cheque_date) && !empty($payment->method) && ($payment->method == 'bank_transfer' || $payment->method == 'cheque'))
	<input type="hidden" name="payment_edit_cheque" id="payment_edit_cheque" value="{{@format_date($payment->cheque_date)}}" , @if(!empty($edit)) {{ 'disabled' }} @endif > <!-- used for set cheque date to current date if empty in app.js line 1533 -->
	@endif
	
	<div class="col-md-3">
    		<div class="form-group">
    			{!! Form::label("bank_name_$row_index",__('lang_v1.bank_name')) !!}
    			@if(isset($view_page) || isset($edit_page))
    				{!! Form::text("payment[$row_index][bank_name]",!empty($data[0]->t_bank_name)?$data[0]->t_bank_name: '', ['class' => 'form-control bank_name', 'placeholder' => __('lang_v1.bank_name'),isset($view_page) ? "disabled" : '']); !!}
    			@else
					{!! Form::text("payment[$row_index][bank_name]",!empty($payment->bank_name)?$payment->bank_name: '', ['class' => 'form-control bank_name', 'placeholder' => __('lang_v1.bank_name')]); !!}
    			@endif
    		</div>
    	</div>
	
</div>

<div class="payment_details_div @if( $payment_line['method'] !== 'custom_pay_1' ) {{ 'hide' }} @endif" data-type="custom_pay_1" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_1_$row_index", __('lang_v1.transaction_no')) !!}
			{!! Form::text("payment[$row_index][transaction_no_1]",!empty($payment->transaction_no_1)?$payment->transaction_no_1: $payment_line['transaction_no'] ?? null, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no'), 'id' => "transaction_no_1_$row_index", !empty($edit) ? 'disabled' : '']); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'custom_pay_2' ) {{ 'hide' }} @endif" data-type="custom_pay_2" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_2_$row_index", __('lang_v1.transaction_no')) !!}
			{!! Form::text("payment[$row_index][transaction_no_2]", !empty($payment->transaction_no_2)?$payment->transaction_no_2:$payment_line['transaction_no'] ?? null, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no'), 'id' => "transaction_no_2_$row_index", !empty($edit) ? 'disabled' : '']); !!}
		</div>
	</div>
</div>
<div class="payment_details_div @if( $payment_line['method'] !== 'custom_pay_3' ) {{ 'hide' }} @endif" data-type="custom_pay_3" >
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("transaction_no_3_$row_index", __('lang_v1.transaction_no')) !!}
			{!! Form::text("payment[$row_index][transaction_no_3]", !empty($payment->transaction_no_3)?$payment->transaction_no_3:$payment_line['transaction_no'] ?? null, ['class' => 'form-control', 'placeholder' => __('lang_v1.transaction_no'), 'id' => "transaction_no_3_$row_index", !empty($edit) ? 'disabled' : '']); !!}
		</div>
	</div>
</div>

<div class="payment_details_div cheque_payment_details_only  hide">
    @if(empty($edit))
    	<div class="col-md-6">
    	     <div class="form-group">
                  {!! Form::label('transaction_date_range_cheque_deposit', __('report.date_range') . ':') !!}
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    {!! Form::text('transaction_date_range_cheque_deposit', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('report.date_range'), !empty($edit) ? 'disabled' : '']) !!}
                  </div>
            </div>
    	</div>
    	<div class="col-md-12">
    	    <table class="table table-bordered table-striped" id="cheque_list_table">
                <thead>
                 <tr>
                 <th>@lang('account.select')</th>
                 <th>@lang('lang_v1.name')</th>
                 <th>@lang('account.cheque_no')</th>
                 <th>@lang('account.cheque_date')</th>
                 <th>@lang('account.bank')</th>
                 <th>@lang('account.amount')</th>
                </tr>
              </thead>
              <tbody></tbody>
          </table>
    	</div>
    	@endif
</div>

<script>
       
</script>

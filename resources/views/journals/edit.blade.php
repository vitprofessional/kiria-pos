@php

use App\Account;
$cash_account_id = Account::getAccountByAccountName('Cash')->id;

@endphp

<div class="modal-dialog modal-md" role="document" style="width: 60%;">
    <div class="modal-content">


        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('account.edit_journal')</h4>
        </div>


        <div class="modal-body">
            {!! Form::open(['url' => action('JournalController@update', $journal->id), 'method' => 'put' ]) !!}
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('journal_id', __('account.journal_no')) !!}
                                {!! Form::text('journal_id', $journal->journal_id, ['class' => 'form-control
                                journal_id',
                                'required', 'readonly'])
                                !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('date', __('account.date')) !!}
                                {!! Form::text('date', null, ['class' => 'form-control
                                journal_date',
                                'required'])
                                !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('select_location', __('account.select_location')) !!}
                                {!! Form::select('location_id', $locations, $journal->location_id, [ 'class' =>
                                'form-control select2','style' => 'width:100%', 'id' => 'location_id',
                                'required' , 'placeholder'
                                =>
                                'Please select']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('is_opening_balance', __('account.opening_balance')) !!}
                                {!! Form::select('is_opening_balance',['yes' => 'Yes', 'no' => 'No'], $journal->is_opening_balance, [ 'class' =>
                                'form-control select2','style' => 'width:100%', 'id' => 'is_opening_balance',
                                'required' , 'placeholder', 'disabled'
                                =>
                                'Please select']) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('note', __('account.note')) !!}
                                {!! Form::textarea('note', $journal->note, ['class' => 'form-control', 'rows' => 2,
                                'cols' => 10,'required'])
                                !!}
                            </div>
                        </div>
                    </div>
                </div>

                @php
                $i = 0;
                @endphp

                <div class="col-md-12 journal_rows">
                    <div class="col-md-3">
                        {!! Form::label('account_type_id', __('account.select_account_type')) !!}
                    </div>
                    <div class="col-md-3">
                        {!! Form::label('account_id', __('account.select_account')) !!}
                    </div>
                    <div class="col-md-3">
                        {!! Form::label('amount', __('account.debit_amount')) !!}
                    </div>
                    <div class="col-md-3">
                        {!! Form::label('amount', __('account.credit_amount')) !!}
                    </div>
                    <div class="clearfix"></div>
                    <div class="dynamic_rows"></div>
                    @foreach ($journals as $item)
                    <input type="hidden" name="journal[{{$i}}][id]" value="{{$item->id}}">
                    <div class="row journal_row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::select('journal['.$i.'][account_type_id]', $account_types,  $item->account_type_id, [ 'class' =>
                                'form-control select2 account_type_ids','style' => 'width:100%',
                                'required' , 'placeholder'
                                =>
                                'Please select']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::select('journal['.$i.'][account_id]', $accounts, $item->account_id, [ 'class'
                                =>
                                'form-control select2 account_ids','style' => 'width:100%',
                                'required' , 'placeholder'
                                =>
                                'Please select']) !!}
                            </div>
                        </div>

                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::text('journal['.$i.'][debit_amount]', $item->debit_amount, [ 'class' =>
                                'form-control debit-top
                                debit debit_amount'.$i,
                                'required' , 'placeholder' => __('account.amount')]) !!}
                            </div>
                        </div>


                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::text('journal['.$i.'][credit_amount]', $item->credit_amount, [ 'class' =>
                                'form-control
                                credit credit-top credit_amount'.$i,
                                'required' , 'placeholder' => __('account.amount')]) !!}
                            </div>
                        </div>
                        <div class="col-md-2">
                            @if($i==0)
                            <button class="btn btn-xs btn-primary add_row" data-index="{{$i}}"
                                style="margin-top: 7px;">+</button>
                            @endif
                        </div>
                    </div>
                    @php
                    $i++;
                    @endphp
                    @endforeach
                </div>
                <input type="hidden" id="index" name="index" value="{{$i-1}}">

                <div class="modal-footer">
                    <div class="col-md-6">
                        <h4>@lang('account.total')</h4>
                    </div>
                    <div class="col-md-2">
                        {!! Form::text('debit_total', null, [ 'class' => 'form-control debit_total', 'readonly', 'style'
                        =>
                        'width:100%;',
                        'required']) !!}
                    </div>
                    <div class="col-md-2">
                        {!! Form::text('credit_total', null, [ 'class' => 'form-control credit_total', 'readonly',
                        'style'
                        => 'width:100%;',
                        'required']) !!}
                    </div>
                    
                    <div class="clearfix"></div>

                    <button type="submit" class="btn btn-primary add_btn">@lang( 'messages.update' )</button>

                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close'
                        )</button>
                </div>

                {!! Form::close() !!}

            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->

        <script>
        $('.journal_date').datepicker().datepicker("setDate", "{{@format_date($journal->date)}}");
        $('.account_ids').select2();
        
        $(document).ready(function(){
            calculate_total();
            update_disables();
        })
        
        function update_disables(){
            $('.journal_row').each(function(index) {
                var debit_top = $(this).find('.debit-top');
                var credit_top = $(this).find('.credit-top');
                
                if (debit_top.val()) {
                    credit_top.attr('disabled', 'disabled');
                } else if (credit_top.val()) {
                    debit_top.attr('disabled', 'disabled');
                } else {
                    debit_top.attr('disabled', false);
                    credit_top.attr('disabled', false);
                }
                
            });
        }
        

        $('body').on('click', '.remove_row', function(e) {
            e.preventDefault();
            $(this).closest('div.row').remove();
            calculate_total();
        });


        </script>
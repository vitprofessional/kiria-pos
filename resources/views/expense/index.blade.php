@extends('layouts.app')
@section('title', __('expense.expenses'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header" style="padding-top: 0px !important;padding-bottom: 0px !important;">
    <h1>@lang('expense.expenses')</h1>
</section>

<!-- Main content -->
<section class="content" style="padding-top:0px !important;">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('expense_for', __('expense.expense_for').':') !!}
                    {!! Form::select('expense_for', $users, null, ['class' => 'form-control select2']); !!}
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('payee_name', __('expense.payee_name').':') !!}
                    {!! Form::select('payee_name', $payee_names, null, ['class' => 'form-control select2','placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('expense_category_id',__('expense.expense_category').':') !!}
                    {!! Form::select('expense_category_id', $categories, null, ['placeholder' =>
                    __('report.all'), 'class' => 'form-control select2', 'style' => 'width:100%', 'id' =>
                    'expense_category_id']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('expense_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last
                    day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>
                    'form-control', 'id' => 'expense_date_range', 'readonly']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('expense_payment_status', __('purchase.payment_status') . ':') !!}
                    {!! Form::select('expense_payment_status', ['paid' => __('lang_v1.paid'), 'due' =>
                    __('lang_v1.due'), 'partial' => __('lang_v1.partial')], null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @component('components.widget')
            @can('expense.create')
            @slot('tool')
            <div class="box-tools pull-right">
                <a class="btn btn-primary" href="{{action('ExpenseController@create')}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
            </div>
            @endslot
            @endcan
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="expense_table">
                    <thead>
                        <tr>
                            <th class="notexport">@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('expense.payee_name')</th>
                            <th>@lang('expense.expense_category')</th>
                            <th>@lang('business.location')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('product.tax')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('purchase.payment_due')
                            <th>@lang('expense.payment_method')
                            <th>@lang('expense.expense_for')</th>
                            <th>@lang('expense.expense_note')</th>
                            <th>@lang('lang_v1.added_by')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="5"></td>
                            <td><strong>@lang('sale.total'):</strong></td>
                            <td id="footer_payment_status_count"></td>
                            <td></td>
                            <td><span class="display_currency" id="footer_expense_total"
                                    data-currency_symbol="true"></span></td>
                            <td><span class="display_currency" id="footer_total_due" data-currency_symbol="true"></span>
                            </td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
@stop
@section('javascript')
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script>
    var body = document.getElementsByTagName("body")[0];
    body.className += " sidebar-collapse";
</script>
@if(session('status.auto_print_transection_id'))
<script>
    show_print_box();
    function show_print_box() {
        var container = $('.payment_modal');

        $.ajax({
            url: '{{ action("TransactionPaymentController@print", [session("status.auto_print_transection_id")])}}',
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    }
</script>
@endif
@endsection
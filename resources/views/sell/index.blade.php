@extends('layouts.app')
@section('title', __( 'lang_v1.all_sales'))

@section('content')


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang( 'sale.sells')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">Sales</a></li>
                    <li><span>@lang( 'sale.sells')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner no-print">
    @component('components.filters', ['title' => __('report.filters')])
        @include('sell.partials.sell_list_filters')
        @if($is_woocommerce)
            <div class="col-md-4">
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                          {!! Form::checkbox('only_woocommerce_sells', 1, false, 
                          [ 'class' => 'input-icheck', 'id' => 'synced_from_woocommerce']); !!} {{ __('lang_v1.synced_from_woocommerce') }}
                        </label>
                    </div>
                </div>
            </div>
        @endif
    @endcomponent
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.all_sales'), 'date' => ''])
        @can('sell.create')
            @slot('tool')
                <div class="box-tools pull-right">
                    <a class="btn btn-primary" href="{{action('SellController@create')}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan
        @if(auth()->user()->can('direct_sell.access') ||  auth()->user()->can('view_own_sell_only'))
            <div class="clearfix"></div>
            <div class="table-responsive" style="margin-top: 10px;">
                <table class="table table-bordered table-striped table-responsive ajax_view" id="sell_table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sale.invoice_no')</th>
                            <th>@lang('sale.customer_name')</th>
                            <th>@lang('lang_v1.contact_no')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('sale.payment_status')</th>
                            <th>@lang('lang_v1.payment_method')</th>
                            <th>@lang('sale.total_amount')</th>
                            <th>@lang('sale.total_paid')</th>
                            <th>@lang('lang_v1.sell_return_due')</th>
                            <th>@lang('lang_v1.sell_due')</th>
                            <th>@lang('lang_v1.shipping_status')</th>
                            <th>@lang('lang_v1.total_items')</th>
                            <th>@lang('lang_v1.types_of_service')</th>
                            <th>@lang('lang_v1.third_party_order_id')</th>
                            <th>@lang('lang_v1.added_by')</th>
                            <th>@lang('sale.sell_note')</th>
                            <th>@lang('sale.staff_note')</th>
                            <th>@lang('sale.shipping_details')</th>
                            <th>@lang('restaurant.table')</th>
                            <th>@lang('restaurant.service_staff')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 footer-total text-center">
                            <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                            <td id="footer_payment_status_count"></td>
                            <td id="payment_method_count"></td>
                            <td><span class="display_currency" id="footer_sale_total" data-currency_symbol ="true"></span></td>
                            <td><span class="display_currency" id="footer_total_paid" data-currency_symbol ="true"></span></td>
                            <td><span class="display_currency" id="footer_total_sell_return_due" data-currency_symbol ="true"></span></td>
                            <td><span class="display_currency" id="footer_total_remaining" data-currency_symbol ="true"></span></td>
                            <td colspan="2"></td>
                            <td id="service_type_count"></td>
                            <td colspan="7"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    @endcomponent
</section>
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<!-- This will be printed -->
<!-- <section class="invoice print_section" id="receipt_section">
</section> -->

@stop

@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    //Date range as a button
    $('#sell_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#sell_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            $("#report_date_range").text("Date Range: "+ $("#sell_list_filter_date_range").val());   
            sell_table.ajax.reload();
        }
    );
    
    
    $('#sell_list_filter_date_range').data('daterangepicker').setStartDate(moment().startOf('month'));

    $('#sell_list_filter_date_range').data('daterangepicker').setEndDate(moment().endOf('month'));
        
    
    // $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
    //     $('#sell_list_filter_date_range').val('');
    //     sell_table.ajax.reload();
    // });
    $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $('#sell_list_filter_date_range').val('');
        $("#report_date_range").text("Date Range: - ");
        sell_table.ajax.reload();
    });
    $('#sell_list_filter_date_range').on('apply.daterangepicker', function(ev, picker) {
        if (picker.chosenLabel === 'Custom Date Range') {
            $('#target_custom_date_input').val('sell_list_filter_date_range');
            $('.custom_date_typing_modal').modal('show');
        }
    });
    $('#custom_date_apply_button').on('click', function() {
        debugger;
        if($('#target_custom_date_input').val() == "sell_list_filter_date_range"){
            let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
            let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

            if (startDate.length === 10 && endDate.length === 10) {
                let formattedStartDate = moment(startDate).format(moment_date_format);
                let formattedEndDate = moment(endDate).format(moment_date_format);

                $('#sell_list_filter_date_range').val(
                    formattedStartDate + ' ~ ' + formattedEndDate
                );

                $('#sell_list_filter_date_range').data('daterangepicker').setStartDate(moment(startDate));
                $('#sell_list_filter_date_range').data('daterangepicker').setEndDate(moment(endDate));

                $('.custom_date_typing_modal').modal('hide');
            } else {
                alert("Please select both start and end dates.");
            }
        }
    });
    var buttons = [
        {
            extend: 'csv',
            text: '<i class="fa fa-file-text-o" aria-hidden="true"></i> ' + LANG.export_to_csv,
            className: 'btn-sm',
            exportOptions: { columns: ':visible:not(:eq(0))' },
            footer: true,
        },
        {
            extend: 'excel',
            text: '<i class="fa fa-file-excel-o" aria-hidden="true"></i> ' + LANG.export_to_excel,
            className: 'btn-sm',
            exportOptions: { columns: ':visible:not(:eq(0))'  },
            footer: true,
        },
        {
            extend: 'print',
            text: '<i class="fa fa-print" aria-hidden="true"></i> ' + LANG.print,
            className: 'btn-sm',
            exportOptions: { columns: ':visible:not(:eq(0))', stripHtml: true },
            footer: true,
            customize: function (win) {
                if ($('.print_table_part').length > 0) {
                    $($('.print_table_part').html()).insertBefore(
                        $(win.document.body).find('table')
                    );
                }
                __currency_convert_recursively($(win.document.body).find('table'));
            },
        },
        {
            extend: 'colvis',
            text: '<i class="fa fa-columns" aria-hidden="true"></i> ' + LANG.col_vis,
            className: 'btn-sm',
        },{
            extend: 'pdf',
            text: '<i class="fa fa-file-pdf-o" aria-hidden="true"></i> ' + LANG.export_to_pdf,
            className: 'btn-sm',
            exportOptions: { columns: ':visible:not(:eq(0))' },
            footer: true,
        }
    ];
    sell_table = $('#sell_table').DataTable({
        buttons,
        processing: true,
        serverSide: true,
        searching: true,
        // aaSorting: [[1, 'desc']],
        "ajax": {
            "url": "/sales",
            "data": function ( d ) {
                if($('#sell_list_filter_date_range').val()) {
                    var start = $('#sell_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#sell_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
                d.is_direct_sale = 1;

                d.location_id = $('#sell_list_filter_location_id').val();
                d.customer_id = $('#sell_list_filter_customer_id').val();
                d.payment_status = $('#sell_list_filter_payment_status').val();
                d.invoice_no = $('#sell_list_filter_invoice_no').val();
                d.created_by = $('#created_by').val();
                d.sales_cmsn_agnt = $('#sales_cmsn_agnt').val();
                d.service_staffs = $('#service_staffs').val();
                
                @if($is_woocommerce)
                    if($('#synced_from_woocommerce').is(':checked')) {
                        d.only_woocommerce_sells = 1;
                    }
                @endif
            }
        },
        columns: [
            { data: 'action', name: 'action', orderable: false, searchable: false},
            { data: 'transaction_date', name: 'transaction_date', searchable: false  },
            { data: 'invoice_no', name: 'invoice_no', searchable: false},
            { data: 'name', name: 'contacts.name', searchable: false},
            { data: 'mobile', name: 'contacts.mobile', searchable: false},
            { data: 'business_location', name: 'bl.name', searchable: false},
            { data: 'payment_status', name: 'payment_status', searchable: false},
            { data: 'payment_methods', orderable: false, searchable: false},
            { data: 'final_total', name: 'final_total', searchable: false},
            { data: 'total_paid', name: 'total_paid', searchable: false},
            { data: 'return_due', orderable: false, searchable: false},
            { data: 'total_remaining', name: 'total_remaining', searchable: false},
            { data: 'shipping_status', name: 'shipping_status', searchable: false},
            { data: 'total_items', name: 'total_items', searchable: false},
            { data: 'types_of_service_name', name: 'tos.name', searchable: false},
            { data: 'service_custom_field_1', name: 'service_custom_field_1', searchable: false},
            { data: 'added_by', name: 'u.first_name', searchable: false},
            { data: 'additional_notes', name: 'additional_notes', searchable: false},
            { data: 'staff_note', name: 'transactions.staff_note', searchable: false},
            { data: 'shipping_details', name: 'shipping_details', searchable: false},
            { data: 'table_name', name: 'tables.name', searchable: false, @if(empty($is_tables_enabled)) visible: false @endif },
            { data: 'waiter', name: 'ss.first_name', searchable: false, @if(empty($is_service_staff_enabled)) visible: false @endif },
        ],
        "fnDrawCallback": function (oSettings) {

            $('#footer_sale_total').text(sum_table_col($('#sell_table'), 'final-total'));
            
            $('#footer_total_paid').text(sum_table_col($('#sell_table'), 'total-paid'));

            // $('#footer_total_remaining').text(sum_table_col($('#sell_table'), '.payment_due'));

            $('#footer_total_sell_return_due').text(sum_table_col($('#sell_table'), 'sell_return_due'));

            $('#footer_payment_status_count').html(__sum_status_html($('#sell_table'), 'payment-status-label'));

            $('#service_type_count').html(__sum_status_html($('#sell_table'), 'service-type-label'));
            $('#payment_method_count').html(__sum_status_html($('#sell_table'), 'payment-method'));

            __currency_convert_recursively($('#sell_table'));
        },
        createdRow: function( row, data, dataIndex ) {
            $( row ).find('td:eq(6)').attr('class', 'clickable_td');
        }
    });

    $(document).on('change', '#sell_list_filter_location_id, #sell_list_filter_customer_id, #sell_list_filter_payment_status, #sell_list_filter_invoice_no, #created_by, #sales_cmsn_agnt, #service_staffs',  function() {
        sell_table.ajax.reload();
    });
    @if($is_woocommerce)
        $('#synced_from_woocommerce').on('ifChanged', function(event){
            sell_table.ajax.reload();
        });
    @endif
});
</script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
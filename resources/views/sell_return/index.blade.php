@extends('layouts.app')
@section('title', __('lang_v1.sell_return'))

@section('content')


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('lang_v1.sell_return')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">Sales</a></li>
                    <li><span>@lang('lang_v1.sell_return')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner no-print">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.sell_return'), 'date' => ''])
        @can('sell.view')
            <div class="form-group">
                <div class="input-group">
                  <button type="button" class="btn btn-primary" id="daterange-btn">
                    <span>
                      <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                    </span>
                    <i class="fa fa-caret-down"></i>
                  </button>
                </div>
            </div>
            @include('sell_return.partials.sell_return_list')
        @endcan
    @endcomponent
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>
</section>

<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script>
    $(document).ready(function(){
        //Date range as a button
        $("#report_date_range").text("Date Range: "+ $('#daterange-btn span').text());
        $('#daterange-btn').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#daterange-btn span').html(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                $("#report_date_range").text("Date Range: "+ $('#daterange-btn span').text());
                sell_return_table.ajax.url( '/sell-return?start_date=' + start.format('YYYY-MM-DD') + '&end_date=' + end.format('YYYY-MM-DD') ).load();
            }
        );
        $('#daterange-btn').on('cancel.daterangepicker', function(ev, picker) {
            sell_return_table.ajax.url( '/sell-return').load();
            $('#daterange-btn span').html('<i class="fa fa-calendar"></i> {{ __("messages.filter_by_date") }}');
            $("#report_date_range").text("Date Range: - ");
        });
        $('#daterange-btn').on('apply.daterangepicker', function(ev, picker) {
            if (picker.chosenLabel === 'Custom Date Range') {
                $('#target_custom_date_input').val('daterange-btn');
                $('.custom_date_typing_modal').modal('show');
            }
        });
        $('#custom_date_apply_button').on('click', function() {
            debugger;
            if($('#target_custom_date_input').val() == "daterange-btn"){
                let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

                if (startDate.length === 10 && endDate.length === 10) {
                    let formattedStartDate = moment(startDate).format(moment_date_format);
                    let formattedEndDate = moment(endDate).format(moment_date_format);

                    $('#daterange-btn').val(
                        formattedStartDate + ' ~ ' + formattedEndDate
                    );

                    $('#daterange-btn').data('daterangepicker').setStartDate(moment(startDate));
                    $('#daterange-btn').data('daterangepicker').setEndDate(moment(endDate));

                    $('.custom_date_typing_modal').modal('hide');
                } else {
                    alert("Please select both start and end dates.");
                }
            }
        });
        sell_return_table = $('#sell_return_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            "ajax": {
                "url": "/sell-return",
                "data": function ( d ) {
                    var start = $('#daterange-btn').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    var end = $('#daterange-btn').data('daterangepicker').endDate.format('YYYY-MM-DD');
                    d.start_date = start;
                    d.end_date = end;
                }
            },
            columnDefs: [ {
                "targets": [7, 8],
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'transaction_date', name: 'transaction_date'  },
                { data: 'invoice_no', name: 'invoice_no'},
                { data: 'parent_sale', name: 'T1.invoice_no'},
                { data: 'name', name: 'contacts.name'},
                { data: 'business_location', name: 'bl.name'},
                { data: 'payment_status', name: 'payment_status'},
                { data: 'final_total', name: 'final_total'},
                { data: 'payment_due', name: 'payment_due'},
                { data: 'action', name: 'action'}
            ],
            "fnDrawCallback": function (oSettings) {
                var total_sell = sum_table_col($('#sell_return_table'), 'final_total');
                $('#footer_sell_return_total').text(total_sell);
                
                $('#footer_payment_status_count_sr').html(__sum_status_html($('#sell_return_table'), 'payment-status-label'));

                var total_due = sum_table_col($('#sell_return_table'), 'payment_due');
                $('#footer_total_due_sr').text(total_due);

                __currency_convert_recursively($('#sell_return_table'));
            },
            createdRow: function( row, data, dataIndex ) {
                $( row ).find('td:eq(2)').attr('class', 'clickable_td');
            }
        });
    })
</script>
	
@endsection
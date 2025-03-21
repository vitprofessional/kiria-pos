@extends('layouts.app')
@section('title', __( 'lang_v1.subscriptions'))

@section('content')



<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang( 'lang_v1.subscriptions') @show_tooltip(__('lang_v1.recurring_invoice_help'))</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">Sales</a></li>
                    <li><span>@lang( 'lang_v1.subscriptions') @show_tooltip(__('lang_v1.recurring_invoice_help'))</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner no-print">
	<div class="box">
        <div class="box-header">
        	<!-- <h3 class="box-title"></h3> -->
        </div>
        <div class="box-body">
            @can('sell.view')
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <div class="input-group">
                              <button type="button" class="btn btn-primary" id="sell_date_filter">
                                <span>
                                  <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                                </span>
                                <i class="fa fa-caret-down"></i>
                              </button>
                            </div>
                          </div>
                    </div>
                    <div class="col-sm-8">
                        <span id="report_date_range">
                            Date Range: {{ date('m/01/Y') }} ~ {{ date('m/t/Y') }}
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
            	<table class="table table-bordered table-striped" id="sell_table">
            		<thead>
            			<tr>
            				<th>@lang('messages.date')</th>
                            <th>@lang('lang_v1.subscription_no')</th>
    						<th>@lang('sale.customer_name')</th>
                            <th>@lang('sale.location')</th>
                            <th>@lang('lang_v1.subscription_interval')</th>
    						<th>@lang('lang_v1.no_of_repetitions')</th>
                            <th>@lang('lang_v1.generated_invoices')</th>
                            <th>@lang('lang_v1.last_generated')</th>
                            <th>@lang('lang_v1.upcoming_invoice')</th>
    						<th>@lang('messages.action')</th>
            			</tr>
            		</thead>
            	</table>
                </div>
            @endcan
        </div>
    </div>
</section>
@stop

@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    //Date range as a button
    $("#report_date_range").text("Date Range: "+ $("#sell_date_filter span").text());
    $('#sell_date_filter').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#sell_date_filter span').html(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            $("#report_date_range").text("Date Range: "+ $("#sell_date_filter span").text());
            sell_table.ajax.reload();
        }
    );
    $('#sell_date_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#sell_date_filter').html('<i class="fa fa-calendar"></i> {{ __("messages.filter_by_date") }}');
        sell_table.ajax.reload();
        $("#report_date_range").text("Date Range: "+ $("#sell_date_filter span").text());
    });
    $('#sell_date_filter').on('apply.daterangepicker', function(ev, picker) {
        if (picker.chosenLabel === 'Custom Date Range') {
            $('#target_custom_date_input').val('sell_date_filter');
            $('.custom_date_typing_modal').modal('show');
        }
    });
    $('#custom_date_apply_button').on('click', function() {
        debugger;
        if($('#target_custom_date_input').val() == "sell_date_filter"){
            let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2').val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() + "-" + $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $('#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
            let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $('#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $('#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $('#custom_date_to_date1').val() + $('#custom_date_to_date2').val();

            if (startDate.length === 10 && endDate.length === 10) {
                let formattedStartDate = moment(startDate).format(moment_date_format);
                let formattedEndDate = moment(endDate).format(moment_date_format);

                $('#sell_date_filter').val(
                    formattedStartDate + ' ~ ' + formattedEndDate
                );

                $('#sell_date_filter').data('daterangepicker').setStartDate(moment(startDate));
                $('#sell_date_filter').data('daterangepicker').setEndDate(moment(endDate));

                $('.custom_date_typing_modal').modal('hide');
            } else {
                alert("Please select both start and end dates.");
            }
        }
    });
    sell_table = $('#sell_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']], 
        "ajax": {
            "url": "/sells/subscriptions",
            "data": function ( d ) {
                var start = $('#sell_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                var end = $('#sell_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                d.start_date = start;
                d.end_date = end;
            }
        },
        columnDefs: [ {
            "targets": 9,
            "orderable": false,
            "searchable": false
        } ],
        columns: [
            { data: 'transaction_date', name: 'transaction_date'  },
            { data: 'subscription_no', name: 'subscription_no'},
            { data: 'name', name: 'contacts.name'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'recur_interval', name: 'recur_interval'},
            { data: 'recur_repetitions', name: 'recur_repetitions'},
            { data: 'subscription_invoices', searchable: false, orderable: false},
            { data: 'last_generated', searchable: false, orderable: false},
            { data: 'upcoming_invoice', searchable: false, orderable: false},
            { data: 'action', name: 'action'}
        ],
        "fnDrawCallback": function (oSettings) {
            __currency_convert_recursively($('#sell_table'));
        }
    });
});

$(document).on( 'click', 'a.toggle_recurring_invoice', function(e){
    e.preventDefault();
    $.ajax({
        method: "GET",
        url: $(this).attr('href'),
        dataType: "json",
        success: function(data){
            if(data.success == true){   
                toastr.success(data.msg);
                sell_table.ajax.reload();
            } else {
                toastr.error(data.msg);
            }
        }
    });
});

</script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
@endsection
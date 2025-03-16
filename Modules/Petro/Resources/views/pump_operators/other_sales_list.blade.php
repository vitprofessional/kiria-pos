@extends('layouts.'.$layout)
@section('title', __('petro::lang.list_other_sales'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    
    <div class="col-md-12">
        <h1 class="pull-left">@lang('petro::lang.list_other_sales')</h1>
        <h2 style="color: red; text-align: center;">Shift_NO: {{$shift_number}}</h2>
    </div>
    
    <a href="{{action('Auth\PumpOperatorLoginController@logout')}}" class="btn btn-flat btn-lg pull-right"
    style=" background-color: orange; color: #fff; margin-left: 5px;">@lang('petro::lang.logout')</a>
    <a href="{{action('\Modules\Petro\Http\Controllers\PumpOperatorController@dashboard')}}"
        class="btn btn-flat btn-lg pull-right"
        style="color: #fff; background-color:#810040;">@lang('petro::lang.dashboard')
    </a>

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="@if(empty($only_pumper)) col-md-3 @else col-md-6 @endif px-4" >
                <div class="form-group">
                    {!! Form::label('shift_id',  __('petro::lang.shift') . ':') !!}
                    <select class="form-control select2" style = 'width:100%' id="pump_operators_list_other_sales_shift_id">
                        @foreach($shifts as $shift)
                            <option value="{{$shift->id}}">{{$shift->name." (".@format_date($shift->shift_date)}} to {{!empty($shift->closed_time) ? @format_datetime($shift->closed_time) : 'Open'}})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    @component('components.widget', ['class' => 'box-primary', 'title' =>
        __('petro::lang.list_other_sales')])
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="pump_operators_list_other_sales_table"
                style="width: 100%;">
                <thead>
                    <tr>
                        <th>@lang('petro::lang.time_and_date')</th>
                        <th>@lang('petro::lang.product')</th>
                        <th>@lang('petro::lang.price')</th>
                        <th>@lang('petro::lang.quantity')</th>
                        <th>@lang('petro::lang.balance_stock')</th>
                        <th>@lang('petro::lang.discount')</th>
                        <th>@lang('petro::lang.sub_total')</th>
                        <th>@lang('petro::lang.shift_number')</th>
                    </tr>
                </thead>
    
                <tfoot>
                    <tr class="bg-gray font-17 footer-total">
                        <td colspan="6" class="text-right" style="color:brown">
                            <strong>@lang('sale.total'):</strong></td>
                        <td style="color:brown"><span class="display_currency" id="footer_list_other_sales_amount" data-currency_symbol="true"></span>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endcomponent
</section>
<div class="clearfix"></div>



@endsection
@section('javascript')
<script type="text/javascript">
    var body = document.getElementsByTagName("body")[0];
    body.className += " sidebar-collapse";
    
$(document).ready( function(){
    pump_operators_list_other_sales_table = $('#pump_operators_list_other_sales_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        ajax: {
            url: "{{action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@otherSalesList')}}",
            data: function(d) {
                d.shift_id = $("#pump_operators_list_other_sales_shift_id").val();
                console.log("print_other_sale_ids", [@json($print_other_sale_ids)]);
                d.print_other_sale_ids = @json($print_other_sale_ids);
                d.print = {{ ($print) ? 1 : 0 }};
            },
        },
        columnDefs: [ {
            "targets": 0,
            "orderable": false,
            "searchable": false
        }],
        columns: [
            { 
                data: 'created_at',
                name: 'created_at',
                width: '10%',
            },
            { 
                data: 'product_name',
                name: 'products.name',
                width: '10%',
            },
            { 
                data: 'price',
                name: 'price',
                width: '10%',
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).addClass('text-right');
                }
            },
            { 
                data: 'quantity',
                name: 'quantity',
                width: '6%',
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).addClass('text-right');
                }
            },
            { 
                data: 'balance_stock',
                name: 'balance_stock',
                width: '6%',
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).addClass('text-right');
                }
            },
            { 
                data: 'discount',
                name: 'discount',
                width: '6%',
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).addClass('text-right');
                }
            },
            { 
                data: 'sub_total',
                name: 'sub_total',
                width: '7%',
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).addClass('text-right');
                }
            },
            { 
                data: 'shift_number',
                name: 'shift_number',
                width: '5%',
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).addClass('text-right');
                }
            }
        ],
        fnDrawCallback: function(oSettings) {
            var footer_list_other_sales_amount = sum_table_col($('#pump_operators_list_other_sales_table'), 'sub_total');
            $('#footer_list_other_sales_amount').text(footer_list_other_sales_amount);
          
            __currency_convert_recursively($('#pump_operators_list_other_sales_table'));
        },
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fa fa-file"></i> Export to CSV',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport')
                            ? true
                            : false;
                    },
                },
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport')
                            ? true
                            : false;
                    },
                },
            },
            {
                extend: 'colvis',
                text: '<i class="fa fa-columns"></i> Column Visibility',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport')
                            ? true
                            : false;
                    },
                },
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf-o"></i> Export to PDF',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport')
                            ? true
                            : false;
                    },
                },
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                className: 'btn btn-default btn-sm',
                exportOptions: {
                    columns: function (idx, data, node) {
                        return $(node).is(':visible') && !$(node).hasClass('notexport')
                            ? true
                            : false;
                    },
                },
                action: function(e, dt, button, config) {
                    try {
                        // Attempt to use DataTables' default print functionality
                        $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, button, config);
                    } catch (err) {
                        console.warn("Print popup blocked, falling back to custom print function.", err);
                        executeCustomPrint(); // Call fallback print function
                    }
                }
            }
        ],
        initComplete: function() {
            if ({{ $print ? 1 : 0 }}) {
                // tryPrintWithFallback();
                pump_operators_list_other_sales_table.button('.buttons-print').trigger();
            }
        }
    });
    
     $(document).on('change', '#pump_operators_list_other_sales_shift_id', function(){
        pump_operators_list_other_sales_table.ajax.reload();
    });

    function tryPrintWithFallback() {
        let isPopupBlocked = true;

        // Attempt to trigger the DataTables print button
        pump_operators_list_other_sales_table.button('.buttons-print').trigger();

        // Check if the print dialog opens within 1 second
        const checkPopup = setTimeout(() => {
            if (isPopupBlocked) {
                console.warn("Print popup blocked, falling back to custom function.");
                executeCustomPrint();
            }
        }, 1000);

        // Listen for the user interacting with the print dialog
        window.addEventListener('focus', () => {
            isPopupBlocked = false;
            clearTimeout(checkPopup);
        }, { once: true });
    }

    function executeCustomPrint() {
        // Add your custom fallback print logic here
        console.log("Executing custom print...");
        window.print(); // Example: Simple fallback print
    }
});

</script>
@endsection
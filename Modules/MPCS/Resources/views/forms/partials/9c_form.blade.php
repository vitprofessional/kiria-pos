<!-- Main content -->
<section class="content">
    <style>
        #form_f15_table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            color: #333;
            margin-top: 20px;
        }

        #form_f15_table thead {
            color: #333;
            font-weight: bold;
        }

        #form_f15_table th,
        #form_f15_table td {
            padding: 12px 15px;
            text-align: center;
            border: 1px solid #ddd;
        }

        #form_f15_table th {}

        @media (max-width: 768px) {
            #form_f15_table th,
            #form_f15_table td {
                font-size: 12px;
                padding: 8px 10px;
            }
        }

        .total-row {
            background-color: #f1f1f1;
            font-weight: bold;
            color: #fff;
        }

        .card-sale-header {
            font-style: italic;
            background-color: #f9f9f9;
        }

        .card-sale-detail {
            font-style: italic;
            background-color: #f9f9f9;
            padding-left: 50px;
        }

        .total-row td {
            text-align: right;
        }

        #form_f15_table td {
            font-size: 14px;
        }

        #form_f15_table th,
        #form_f15_table td {
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        #form_f15_table tbody tr {
            border-bottom: 2px solid #f0f0f0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td {
            height: 10px;
        }

        table td[align="right"] {
            text-align: right;
            padding-right: 10px;
        }

        .no-border-table {
            border-collapse: collapse;
            width: 100%;
        }

        .no-border-table tr {
            border: none !important;
        }

        .no-border-table td {
            border: none !important;
            padding: 8px;
        }

        .no-border-table td,
        .no-border-table th {
            border-bottom: none !important;
        }

        .note-container {
            width: 100%;
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
        }

        .dataTables_filter,
        .dataTables_info {
            display: none;
        }

        .dots {
            display: flex;
            justify-content: space-between;
            width: 100%;
        }

        .dots::before {
            content: "";
            flex-grow: 1;
            border-bottom: 1px dotted black;
            margin: 0 5px;
        }

        /* Styling for print */
        @media print {
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
            }

            /* Hide non-printable elements */
            #printButton,
            .dataTables_filter,
            .dataTables_info,
            .table-responsive .no-print {
                display: none;
            }

            /* Adjust table layout */
            .table-responsive {
                width: 100%;
                margin: 0;
            }

            /* Style adjustments for tables */
            .no-border-table {
                width: 100%;
                margin-top: 20px;
            }

            /* Ensure all the content fits within the page */
            @page {
                margin: 20mm;
            }

            .no-print,
            .no-print * {
                display: none !important;
            }

            .header-area,
            .header-area * {
                display: none !important;
            }

            .nav,
            .nav-tabs * {
                display: none !important;
            }

            .page-title-area * {
                display: none !important;
            }

            .dots {
                display: flex;
                justify-content: space-between;
                width: 100%;
            }

            .dots::before {
                content: "";
                flex-grow: 1;
                border-bottom: 1px dotted black;
                margin: 0 5px;
            }
        }

        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            color: white;
            font-size: 16px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-width: 250px;
            max-width: 400px;
        }

        .custom-alert.success {
            background-color: #28a745;
        }

        .custom-alert.error {
            background-color: #dc3545;
        }

        .custom-alert button {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            margin-left: 10px;
            cursor: pointer;
        }
    </style>

    <div class="row">
    <div class="col-md-12">
        @component('components.widget', ['class' => 'box-primary'])
            <div class="col-md-12">
                <div class="row" style="margin-top: 20px;" id="print_content">
                    <table width="100%" style="margin-top: 0px;" class="no-border-table">
                        <tr style="border: none;">
                            <td align="right" width="40%" style="border: none;"> </td>
                            <td align="left" width="35%" style="border: none;">
                                <h3></h3>
                            </td>
                            <td align="left" width="15%" style="border: none;">
                                <h2 style="color: gray;">Form 9 C</h2>
                            </td>
                            <td align="right" width="10%" style="border: none;">
                                <div class="box-tools">
                                    <!-- Standard Print button -->
                                    <button class="btn btn-primary print_report pull-right" id="print_div">
                                        <i class="fa fa-print"></i> @lang('messages.print')</button>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <table width="100%" style="margin-top: 10px;" class="no-border-table">
                        <tr style="border: none;">
                            <td align="right" width="40%" style="border: none;"> </td>
                            <td align="left" width="35%" style="border: none;">
                                <h3>{{ $business_location_name }}</h3>
                            </td>
                            <td align="center" width="20%" style="border: none;">
                                <h3>Form No:{{ $total_days }}<h3>
                            </td>
                            <td align="right" width="5%" style="border: none;">
                            </td>
                        </tr>
                    </table>
                    <div class="col-md-12" style="margin-top: 20px;">
                        <div class="col-md-12 d-flex justify-content-center">
                            <h3>@lang('mpcs::lang.cash_sales_details')</h3>
                        </div>
                        <table width="100%" style="margin-top: 0px;" class="no-border-table">
                            <tr style="border: none;">
                                <td align="center" width="70%" style="border: none;"></td>
                                <td align="center" width="5%" style="border: none;">Date:</td>
                                <td align="right" width="25%" style="border: none;">
                                    <div class="form-group">
                                        <input v-model="filter.date" class="form-control" ref="datepicker" name="form_9c_date" type="text" readonly>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="form_9b_table">
                                        <thead class="align-middle">
                                            <tr class="align-middle text-center">
                                                <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.bill_no')
                                                </th>
                                                <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.product_name')
                                                </th>
                                                <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.qty')
                                                </th>
                                                <th class="align-middle text-center" rowspan="2">@lang('mpcs::lang.page')
                                                </th>
                                                <th class="align-middle text-center" colspan="2">Total Amount</th>
                                                <th class="align-middle text-center" colspan="2">Goods</th>
                                                <th class="align-middle text-center" colspan="2">Loading</th>
                                                <th class="align-middle text-center" colspan="2">Empty</th>
                                                <th class="align-middle text-center" colspan="2">Transport</th>
                                                <th class="align-middle text-center" colspan="2">Others</th>
                                            </tr>
                                            <tr class="align-middle" style="text-align: center;">
                                                <td>Rs.</td>
                                                <td>Cents</td>
                                                <td>Rs.</td>
                                                <td>Cents</td>
                                                <td>Rs.</td>
                                                <td>Cents</td>
                                                <td>Rs.</td>
                                                <td>Cents</td>
                                                <td>Rs.</td>
                                                <td>Cents</td>
                                                <td>Rs.</td>
                                                <td>Cents</td>
                                            </tr>
                                        </thead>
                                       <tbody>
                                        <!-- Add rows for each product sub-category -->
                                        @if(isset($sales_data) && count($sales_data) > 0)
                                            @foreach($sales_data as $sub_category_id => $data)
                                                <tr>
                                                    <td colspan="3">{{ $sub_category_id }}</td>
                                                    <td>{{ $data['total_amount'] }}</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="16" class="text-center">No data available</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    </table>
                                </div>
                                <table width="100%" style="margin-top: 20px;" class="no-border-table">
                                    <tr style="border: none;">
                                        <td align="center" width="25%" style="border: none;">Entered in the Book
                                        </td>
                                        <td align="center" width="25%" style="border: none;">
                                            .............................. <br> Checked By</td>
                                        <td align="center" width="25%" style="border: none;">
                                            .............................. <br> Manager</td>
                                        <td align="center" width="25%" style="border: none;">
                                           
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endcomponent
    </div>
</div>
</section>
<!-- /.content -->

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize the date picker
        const datepicker = $('input[name="form_9c_date"]').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            opens: 'center',
            drops: 'down',
            locale: {
                format: 'YYYY-MM-DD',
                cancelLabel: 'Clear',
                applyLabel: 'Apply',
                customRangeLabel: 'Custom Date',
            },
            ranges: {
                'Today': [moment(), moment()],
                'Tomorrow': [moment().add(1, 'days'), moment().add(1, 'days')],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Custom Date': [moment(), moment()],
            },
            autoUpdateInput: false, // Disable auto-update
            showCustomRangeLabel: true,
            alwaysShowCalendars: true,
            startDate: moment(), // Set initial date to today
        });

        // Set initial date and display
        const today = moment();
        $('input[name="form_9c_date"]').val('Today');
        get9CForm(today.format('YYYY-MM-DD'));

        // Handle date selection
        $('input[name="form_9c_date"]').on('apply.daterangepicker', (ev, picker) => {
            const selectedDate = picker.startDate.format('YYYY-MM-DD');
            updateDateDisplay(picker.startDate);
            get9CForm(selectedDate);
        });

        $('input[name="form_9c_date"]').on('cancel.daterangepicker', () => {
            $('input[name="form_9c_date"]').val('');
            get9CForm('');
        });

        // Fetch data based on the selected date
        function get9CForm(selected_date) {
            $.ajax({
                method: 'get',
                url: '/mpcs/get-9c-form',
                data: {
                    selected_date
                },
                contentType: 'html',
                success: function(result) {
                    if (result) {
                        $('#cash_sales').text(Number(result.cash_sale));
                        $('#card_sales').text(Number(result.card_sale));
                        $('#total_cash_sale').text(Number(result.card_sale) + Number(result.cash_sale));
                        $('#total_credit_sale').text(Number(result.credit_sale));
                        $('#total_sale').text(Number(result.card_sale) + Number(result.cash_sale) +
                            Number(result.credit_sale));
                        $('#total_sale_pre_day').text(Number(result.total_sale_pre_day));
                        $('#total_sale_today').text(Number(result.card_sale) + Number(result.cash_sale) +
                            Number(result.credit_sale) + Number(result.total_sale_pre_day));
                    } else {
                        $('#cash_sales').text("");
                        $('#card_sales').text("");
                        $('#total_cash_sale').text("");
                        $('#total_credit_sale').text("");
                        $('#total_sale').text("");
                        $('#total_sale_pre_day').text("");
                        $('#total_sale_today').text("");
                    }
                },
            });
        }

        // Update the input field display
        function updateDateDisplay(date) {
            const today = moment(); // Get today's date
            const tomorrow = moment().add(1, 'days'); // Get tomorrow's date

            let displayValue;
            if (date.isSame(today, 'day')) {
                displayValue = 'Today';
            } else if (date.isSame(tomorrow, 'day')) {
                displayValue = 'Tomorrow';
            } else {
                displayValue = date.format('YYYY-MM-DD');
            }

            // Update the input field
            $('input[name="form_9c_date"]').val(displayValue);
            console.log('Updated input field with:', displayValue); // Debugging
        }

        // Print functionality
        $("#print_div").click(function() {
            printDiv();
        });

        function printDiv() {
            var w = window.open('', '_self');
            var html = `
                <html>
                    <head>
                        <style>
                            @page {
                                size: landscape;
                            }
                            body {
                                width: 100%;
                                margin: 0;
                                padding: 0;
                            }
                            @media print {
                                html, body {
                                    width: 100%;
                                    overflow: visible !important;
                                }
                                * {
                                    font-size: 8pt;
                                }
                            }
                        </style>
                    </head>
                    <body>
                        ${document.getElementById("print_content").innerHTML}
                    </body>
                </html>
            `;
            $(w.document.body).html(html);
            w.print();
            w.close();
            window.location.href = "{{ URL::to('/') }}/mpcs/form-9c";
        }
    });
</script>
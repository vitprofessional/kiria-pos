<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('16a_location_id', __('purchase.business_location') . ':') !!}
                    
             {!! Form::select('16a_location_id', $business_locations, $business_locations->keys()->first(), [
      'id' => '16a_location_id',
      'class' => 'form-control select2',
      'style' => 'width:100%', 
      'placeholder' => __('lang_v1.all')
]) !!}


                </div>
            </div>
            <div class="col-md-3">
   <div class="form-group">
    {!! Form::label('form_16a_date', __('report.date') . ':') !!}
    <div class="dropdown">
        {{-- Single text input that triggers the dropdown --}}
        {!! Form::text('form_16a_date', @format_date(date('Y-m-d')), [
            'class' => 'form-control dropdown-toggle input_number customer_transaction_date',
            'id' => 'form_16a_date',
            'data-toggle' => 'dropdown',
            'readonly',
            'required'
        ]) !!}

        {{-- Dropdown menu with four items --}}
        <ul class="dropdown-menu" aria-labelledby="form_16a_date">
            <li><a href="#" id="today_btn">Today</a></li>
            <li><a href="#" id="yesterday_btn">Yesterday</a></li>
            <!-- Opens a modal for date-range selection -->
            <li><a href="#" data-toggle="modal" data-target="#customDateRangeModal">Custom Date Range</a></li>
            <!-- Opens a modal for single custom date selection -->
            <li><a href="#" data-toggle="modal" data-target="#customDateModal">Custom Date</a></li>
        </ul>
    </div>
</div>

{{-- Modal for Custom Date Range --}}
<div class="modal fade" id="customDateRangeModal" tabindex="-1" role="dialog" aria-labelledby="customDateRangeModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customDateRangeModalLabel">Select Custom Date Range</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="range_from_date">From</label>
          <input type="text" id="range_from_date" class="form-control" placeholder="YYYY-MM-DD"/>
        </div>
        <div class="form-group">
          <label for="range_to_date">To</label>
          <input type="text" id="range_to_date" class="form-control" placeholder="YYYY-MM-DD"/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="apply_date_range_btn" class="btn btn-primary">Apply</button>
      </div>
    </div>
  </div>
</div>

{{-- Modal for a Single Custom Date --}}
<div class="modal fade" id="customDateModal" tabindex="-1" role="dialog" aria-labelledby="customDateModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="customDateModalLabel">Select Custom Date</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label for="single_custom_date">Date</label>
          <input type="text" id="single_custom_date" class="form-control" placeholder="YYYY-MM-DD"/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="apply_single_date_btn" class="btn btn-primary">Apply</button>
      </div>
    </div>
  </div>
</div>


            </div>
            

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('type', __('mpcs::lang.F16a_from_no') . ':') !!}
                    {!! Form::text('F16a_from_no', $F16a_from_no, ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>


            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            @php 
            $totPurchasePreValue = optional($settings)->total_purchase_price_with_vat ?? '0.00';
            $totSalePreValue = optional($settings)->total_sale_price_with_vat ?? '0.00';
            @endphp 
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-4 text-red" style="margin-top: 14px;">
                        <b>@lang('petro::lang.date'): <span class="from_date"></span></b>
                    </div>
                    <div class="col-md-5">
                        <div class="text-center">
                            <h5 style="font-weight: bold;">{{request()->session()->get('business.name')}} <br>
                                <span class="f16a_location_name">@lang('petro::lang.all')</span></h5>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center pull-left">
                            <h5 style="font-weight: bold;" class="text-red">@lang('mpcs::lang.16A_form')
                                @lang('mpcs::lang.form_no') : {{$F16a_from_no}}</h5>
                        </div>
                    </div>
                    <button type="button" class="btn btn-primary" id="print_form_16a_btn">
    <i class="fa fa-print"></i> Print
</button>
                </div>
                <div class="row" style="margin-top: 20px;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="form_16a_table" style="width:100%">
                            <thead>
                                <tr>
                                   <th colspan="4"></th>
                                   <th colspan="2" class="text-center" style="width: 150px;">@lang('mpcs::lang.purchase_price_with_vat')</th>
                                   <th colspan="2" class="text-center" style="width: 150px;">@lang('mpcs::lang.sale_price_with_vat')</th> 
                                </tr>
                                <tr>
                                    <th>@lang('mpcs::lang.purchase_order_no')</th>
                                    <th>@lang('mpcs::lang.product')</th>
                                    <th>@lang('mpcs::lang.location')</th>
                                    <th>@lang('mpcs::lang.received_qty')</th>
                                    <th>@lang('mpcs::lang.unit')</th>
                                    <th>@lang('mpcs::lang.total')</th>
                                    <th>@lang('mpcs::lang.unit')</th>
                                    <th>@lang('mpcs::lang.total')</th>
                                    <th>@lang('mpcs::lang.p_invoice_no')</th>
                                    <th>@lang('mpcs::lang.stock_book_no')</th>

                                </tr>
                            </thead>
                            <tfoot class="bg-gray">
                                <tr>
                                    <td class="text-red text-bold" colspan="5">@lang('mpcs::lang.total_this_page')</td>
                                    <td class="text-red text-bold" id="footer_F16A_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold" colspan="3" id="footer_F16A_total_sale_price"></td>
                                </tr>
                                <tr>
                                    <td class="text-red text-bold" colspan="5">@lang('mpcs::lang.total_previous_page')
                                    </td>
                                    <td class="text-red text-bold" id="pre_F16A_total_purchase_price">{{ $totPurchasePreValue }}</td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold" colspan="3" id="pre_F16A_total_sale_price">{{ $totSalePreValue }}</td>
                                </tr>
                                <tr>
                                    <td class="text-red text-bold" colspan="5">@lang('mpcs::lang.grand_total')</td>
                                    <td class="text-red text-bold" id="grand_F16A_total_purchase_price"></td>
                                    <td>&nbsp;</td>
                                    <td class="text-red text-bold" colspan="3" id="grand_F16A_total_sale_price"></td>
                                </tr>
                                <input type="hidden" name="total_this_p" id="total_this_p" value="0">
                                <input type="hidden" name="total_this_s" id="total_this_s" value="0">
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->
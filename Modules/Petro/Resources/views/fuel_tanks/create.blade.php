<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\Petro\Http\Controllers\FuelTankController@store'), 'method' => 'post', 'id' =>
        'customer_reference_add_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close close-this-modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petro::lang.add_fuel_tank' )</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                <div class="card" style="background-color: #F7BCF1 !important">
                    <div class="col-md-3">
                        <h5><span style="font-weight: bold; color: red">@lang('petro::lang.allowed_tanks'): <span id="allowed"> {{ $max_tanks == 0 ? "Infinite" : $max_tanks }} </span></span></h5>
                    </div>
                    <div class="col-md-3">
                        <h5><span style="font-weight: bold; color: red">@lang('petro::lang.added'):  <span id="current">{{ $tanks_added }}</span></span></h5>
                    </div>
                    <div class="col-md-3">
                        <h5><span style="font-weight: bold; color: red">@lang('petro::lang.can_add'):  <span id="remaining">{{ $max_tanks == 0 ? "Infinite" : ($max_tanks-$tanks_added) }} </span></span></h5>
                    </div>
                </div>
                
            </div>
        
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('fuel_tank_number', __( 'petro::lang.fuel_tank_number' ) . ':*') !!}
                    {!! Form::text('fuel_tank_number', null, ['class' => 'form-control fuel_tank_number', 'required',
                    'placeholder' => __(
                    'petro::lang.fuel_tank_number' ) ]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('location_id', __( 'petro::lang.branch' ) . ':*') !!}
                    {!! Form::select('location_id', $locations, null , ['class' => 'form-control select2
                    fuel_tank_location', 'required',
                    'placeholder' => __(
                    'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('product_id', __( 'petro::lang.product' ) . ':*') !!}
                    {!! Form::select('product_id', $products, null , ['class' => 'form-control select2
                    fuel_tank_product', 'required',
                    'placeholder' => __(
                    'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('storage_volume', __( 'petro::lang.storage_volume' ) . ':*') !!}
                    {!! Form::text('storage_volume', null, ['class' => 'form-control storage_volume input_number', 'required',
                    'placeholder' => __(
                    'petro::lang.storage_volume' ) ]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('current_balance', __( 'petro::lang.current_balance' ) . ':*') !!}
                    {!! Form::text('current_balance', null, ['class' => 'form-control current_balance input_number', 'required',
                    'placeholder' => __(
                    'petro::lang.current_balance' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('transaction_date', __( 'petro::lang.transaction_date' ) . ':*') !!}
                    {!! Form::text('transaction_date', date('m/d/Y'), ['class' => 'form-control fuel_tank_date',
                    'required', 'placeholder' => __(
                    'petro::lang.transaction_date' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('bulk_tank', __( 'petro::lang.bulk_tank' ) . ':*') !!}@if(!empty($help_explanations['bulk_tank'])) @show_tooltip($help_explanations['bulk_tank']) @endif
                    {!! Form::select('bulk_tank', ['1' => 'Yes', '0' => 'No'], null,['class' => 'form-control bulk_tank',
                    'required', 'placeholder' => __(
                    'petro::lang.please_select' ) ]); !!}
                </div>
            </div>
            @if($tank_dip_chart_permission)
                @if(!$multiple)
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('tank_dip_chart_id', __( 'petro::lang.sheet_name' ) . ':*') !!}
                            {!! Form::select('tank_dip_chart_id', $sheet_names, null,['class' => 'form-control tank_dip_chart_id',
                            'required', 'placeholder' => __(
                            'petro::lang.please_select' ) ]); !!}
                        </div>
                    </div>
                @endif
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('tank_manufacturer', __( 'petro::lang.tank_manufacturer' ) . ':*') !!}
                    {!! Form::text('tank_manufacturer', null, ['class' => 'form-control tank_manufacturer', (($multiple) ? '' : 'readonly'),
                    'required', 'placeholder' => __(
                    'petro::lang.tank_manufacturer' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('tank_capacity', __( 'petro::lang.tank_capacity' ) . ':*') !!}
                    {!! Form::text('tank_capacity', null, ['class' => 'form-control input_number tank_capacity',
                    'required', 'placeholder' => __(
                    'petro::lang.tank_capacity' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('unit_name', __( 'petro::lang.unit' ) . ':*') !!}
                    {!! Form::text('unit_name', null, ['class' => 'form-control input_number unit_name', (($multiple) ? '' : 'readonly'),
                    'required', 'placeholder' => __(
                    'petro::lang.unit_name' ) ]); !!}
                </div>
            </div>
            @endif
            <div class="clearfix"></div>
            <div class="modal-footer">
                @if($max_tanks != 0 && $max_tanks <= $tanks_added)
                    <div class="alert alert-danger">
                       @lang('petro::lang.max_tanks_reached')
                    </div>
                @else
                  <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang( 'messages.save' )</button>
                @endif
        
                
                <button type="button" class="btn btn-default close-this-modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
        $('.fuel_tank_location').select2();
        $('.fuel_tank_product').select2();
        $('.fuel_tank_date').datepicker();

        $('#tank_dip_chart_id').change(function(){
            let id = $(this).val();

            $.ajax({
                method: 'get',
                url: '/superadmin/tank-dip-chart/get-by-id/'+id,
                data: {  },
                success: function(result) {
                    $('.tank_manufacturer').val(result.tank_manufacturer);
                    $('.tank_capacity').val(result.tank_capacity);
                    $('.unit_name').val(result.actual_name);
                },
            });
        });
        $(document).on('click', '.close-this-modal', function() {
            $(this).closest('.modal').modal('hide');
        });
    </script>

@if ($ajax)
    <script>
        $("form#customer_reference_add_form").submit(function(e){
            e.preventDefault();
            $('.add_fuel_tank_btn').attr("disabled", true);
            $('.add_fuel_tank_btn').html("Saving...");
            var url = $(this).attr("action");
            var data = $(this).serialize();
            $.ajax({
                method: "POST",
                dataType: "json",
                data: data,
                url: url,
                success:function(result){
                    if( result.success == true){
                        $(".fuel_tank_modal").modal('hide');
                        var tankOption = `<option value="${result.data.id}" 
                            data-tankname="${result.data.tankname}" 
                            data-manufacturer="${result.data.manufacturer}" 
                            data-manufacturerphone="${result.data.manufacturerphone}" 
                            data-capacity="${result.data.capacity}">
                            ${result.data.tankname}
                        </option>`;
                        if($('#add_dip_chart_tank_id')){
                            $('#add_dip_chart_tank_id').append(tankOption);
                        }
                        if($('#tank_ids')){
                            var prev_tank_ids = $('#tank_ids').val();
                            if(prev_tank_ids == ''){
                                $('#tank_ids').val(result.data.id);
                            } else {
                                $('#tank_ids').val(prev_tank_ids + ',' + result.data.id);
                            }
                            var prev_tank_names = $('#tank_names').val();
                            if(prev_tank_names == ''){
                                $('#tank_names').val(result.data.tankname);
                            } else {
                                $('#tank_names').val(prev_tank_names + ', ' + result.data.tankname);
                            }
                        }
                        toastr.success(result.msg);
                    }else{
                        toastr.error(result.msg);
                    }
                }
            });
        });
    </script>
@endif
{!! Form::open(['action' => '\Modules\AutoRepairServices\Http\Controllers\RepairSettingsController@store', 'method' => 'post']) !!}
<div class="row">
    <!-- <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('barcode_id', @trans( 'barcode.barcode_setting' ) . ':') !!}
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fa fa-cog"></i>
                </span>
                {!! Form::select('barcode_id', $barcode_settings, !empty($repair_settings['barcode_id']) ? $repair_settings['barcode_id'] : null, ['class' => 'form-control select2']); !!}
            </div>
        </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('barcode_type', __('product.barcode_type') . ':') !!}
          {!! Form::select('barcode_type', $barcode_types, !empty($repair_settings['barcode_type']) ? $repair_settings['barcode_type'] : null, ['class' => 'form-control select2', 'required']); !!}
      </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            {!! Form::label('search_product', __('autorepairservices::lang.search_default_product') . ':') !!} @show_tooltip(__('autorepairservices::lang.default_product_tooltip'))
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-search"></i>
                    </span>
                    <input type="hidden" value="" id="variation_id">
                    {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder')]); !!} 
                    {!! Form::hidden('default_product', !empty($repair_settings['default_product']) ? $repair_settings['default_product'] : null, ['id' => 'default_product']); !!}
                </div>
                <p class="help-block">
                    <strong>@lang('autorepairservices::lang.selected_default_product'):</strong>
                    <span id="selected_default_product">{{$default_product_name}}</span>
                    <br>
                </p>
        </div>
    </div>-->
</div>
<div class="row">    
    <div class="col-md-3">
        <div class="form-group">
            <label for="repair_status_id">
                {{__('autorepairservices::lang.default_job_sheet_status') . ':'}}
                @show_tooltip(__('autorepairservices::lang.default_job_sheet_status_tooltip'))
            </label>
            <div class="input-group">
                <span class="input-group-addon">
                    <i class="fas fa-info-circle"></i>
                </span>
                <select name="default_status" class="form-control" id="repair_status_id"></select>
            </div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="form-group">
            {!! Form::label('job_sheet_prefix', __('autorepairservices::lang.job_sheet_prefix') . ':') !!}
            {!! Form::text('job_sheet_prefix', !empty($repair_settings['job_sheet_prefix'])? $repair_settings['job_sheet_prefix'] : '', ['class' => 'form-control', 'placeholder' => __('autorepairservices::lang.job_sheet_prefix')]); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('default_repair_checklist', __('autorepairservices::lang.default_repair_checklist') . ':') !!}
             @show_tooltip(__('autorepairservices::lang.repair_checklist_tooltip'))
            {!! Form::textarea('default_repair_checklist', !empty($repair_settings['default_repair_checklist'])? $repair_settings['default_repair_checklist'] : ''
            , ['class' => 'form-control ', 'id' => 'default_repair_checklist', 'rows' => '3']); !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('product_configuration', __('autorepairservices::lang.product_configuration') . ':') !!}
            @show_tooltip(__('autorepairservices::lang.product_configuration_tooltip'))
           {!! Form::textarea('product_configuration', !empty($repair_settings['product_configuration'])? $repair_settings['product_configuration'] : null, ['class' => 'form-control', 'rows' => 4]); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('problem_reported_by_customer', __('autorepairservices::lang.problem_reported_by_customer') . ':') !!}
            @show_tooltip(__('autorepairservices::lang.problem_reported_by_customer_tooltip'))
            {!! Form::textarea('problem_reported_by_customer', !empty($repair_settings['problem_reported_by_customer'])? $repair_settings['problem_reported_by_customer'] : null, ['class' => 'form-control', 'rows' => 4]); !!}
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            {!! Form::label('product_condition', __('autorepairservices::lang.condition_of_product') . ':') !!}
            @show_tooltip(__('autorepairservices::lang.product_condition_tooltip'))
            {!! Form::textarea('product_condition', !empty($repair_settings['product_condition']) ? $repair_settings['product_condition'] : null, ['class' => 'form-control', 'rows' => 4]); !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group">
            {!! Form::label('repair_tc_condition', __('autorepairservices::lang.repair_tc_conditions') . ':') !!}
            {!! Form::textarea('repair_tc_condition',!empty($repair_settings['repair_tc_condition'])? $repair_settings['repair_tc_condition'] : '', ['class' => 'form-control ', 'id' => 'repair_tc_condition']); !!}
        </div>
    </div>
</div>
<hr>
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('job_sheet_custom_field_1', __('autorepairservices::lang.label_for_job_sheet_custom_field', ['number' => 1]) . ':') !!}
            {!! Form::text('job_sheet_custom_field_1',!empty($repair_settings['job_sheet_custom_field_1'])? $repair_settings['job_sheet_custom_field_1'] : '', ['class' => 'form-control ', 'id' => 'job_sheet_custom_field_1']); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('job_sheet_custom_field_2', __('autorepairservices::lang.label_for_job_sheet_custom_field', ['number' => 2]) . ':') !!}
            {!! Form::text('job_sheet_custom_field_2',!empty($repair_settings['job_sheet_custom_field_1'])? $repair_settings['job_sheet_custom_field_2'] : '', ['class' => 'form-control ', 'id' => 'job_sheet_custom_field_2']); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('job_sheet_custom_field_3', __('autorepairservices::lang.label_for_job_sheet_custom_field', ['number' => 3]) . ':') !!}
            {!! Form::text('job_sheet_custom_field_3',!empty($repair_settings['job_sheet_custom_field_3'])? $repair_settings['job_sheet_custom_field_3'] : '', ['class' => 'form-control ', 'id' => 'job_sheet_custom_field_3']); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('job_sheet_custom_field_4', __('autorepairservices::lang.label_for_job_sheet_custom_field', ['number' => 4]) . ':') !!}
            {!! Form::text('job_sheet_custom_field_4',!empty($repair_settings['job_sheet_custom_field_1'])? $repair_settings['job_sheet_custom_field_4'] : '', ['class' => 'form-control ', 'id' => 'job_sheet_custom_field_4']); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('job_sheet_custom_field_5', __('autorepairservices::lang.label_for_job_sheet_custom_field', ['number' => 5]) . ':') !!}
            {!! Form::text('job_sheet_custom_field_5',!empty($repair_settings['job_sheet_custom_field_5'])? $repair_settings['job_sheet_custom_field_5'] : '', ['class' => 'form-control ', 'id' => 'job_sheet_custom_field_5']); !!}
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="form-group pull-right">
        {{Form::submit('update', ['class'=>"btn btn-danger"])}}
        </div>
    </div>
</div>
{!! Form::close() !!}
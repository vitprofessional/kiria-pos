<style>
   .rows {
    padding: 0 !important; 
    margin: 0 !important;
}
.full-width-input {
    width: 100% !important; 
    box-sizing: border-box; 
    display: block; 
    padding: 5px; 
    margin: 0;
    border: 1px solid #ccc;
    height: 100%; 
}

.table tbody tr td.rows {
    padding: 0 !important;
    vertical-align: middle !important;
}

</style>
<!-- Main content -->
<section class="content">
{!! Form::open(['id' => 'f21c_form']) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('16a_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('16a_location_id', $business_locations, null, ['class' => 'form-control select2',
                    'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('form_16a_date', __('report.date') . ':') !!}
                    {!! Form::text('form_16a_date', @format_date(date('Y-m-d')), ['class' => 'form-control input_number customer_transaction_date', 'id' =>
                      'form_16a_date','required','readonly']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('type', __('mpcs::lang.F16a_from_no') . ':') !!}
                    {!! Form::text('F21c_from_no', $F21c_from_no ?? '', ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>


            @endcomponent
        </div>
    </div>


</section>
<!-- /.content -->

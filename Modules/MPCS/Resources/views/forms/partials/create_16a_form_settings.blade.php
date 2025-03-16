<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\MPCS\Http\Controllers\FormsSettingController@store16aFormSetting'), 'method' => 'post', 'id' => 'add_16a_form_settings' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'mpcs::lang.add_16_a_form_settings' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12"><br />

                <!-- Date and Time -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.date')</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="datepicker" name="datepicker" data-date-format="yyyy/mm/dd" disabled>
                            <div class="input-group-addon">
                                <i class="fa fa-calendar-o"></i>
                            </div>
                        </div>
                    </div>
                </div>

                  <!-- Date and Time -->
                  <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.time')</label>
                        <div class="input-group">
                            <input class="form-control timepicker" id="time" name="time" type="time" value="12:00" readonly>
                            <div class="input-group-addon">
                                <i class="fa fa-clock-o" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Starting Number -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.form_starting_number') <span class="required" aria-required="true">*</span></label>
                        <input type="text" name="starting_number" class="form-control" required>
                    </div>
                </div>

                <!-- Ref Previous Form Number -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.ref_previous_form_number') <span class="required" aria-required="true">*</span></label>
                        <input type="text" name="ref_pre_form_number" class="form-control" required>
                    </div>
                </div>

                <!-- Total Sale up to Previous Day -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.total_previous_total_purchase_with_vat') <span class="required" aria-required="true">*</span></label>
                        <input type="number" step="0.01" id="total_purchase_price_with_vat" name="total_purchase_price_with_vat" class="form-control" required>
                    </div>
                </div>

                <!-- Previous Day Cash Sale -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.total_previous_total_sale_with_vat') <span class="required" aria-required="true">*</span></label>
                        <input type="number" step="0.01" id="total_sale_price_with_vat" name="total_sale_price_with_vat" class="form-control" required>
                    </div>
                </div>
                
                <!-- Other fields remain unchanged -->

            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>

$(document).ready( function(){
    $('#datepicker').datepicker('setDate',new Date());

     // Get current time in HH:MM format
     let now = new Date();
        let hours = String(now.getHours()).padStart(2, "0");
        let minutes = String(now.getMinutes()).padStart(2, "0");
        let currentTime = `${hours}:${minutes}`;

        // Set the value of the time input field
        document.getElementById("time").value = currentTime;
});
    
</script>

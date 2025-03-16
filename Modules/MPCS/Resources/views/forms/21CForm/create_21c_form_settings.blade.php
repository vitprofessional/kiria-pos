<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\MPCS\Http\Controllers\F21FormController@store21cFormSettings'), 'method' => 'post', 'id' => 'add_21c_form_settings' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'mpcs::lang.add_21_c_form_settings' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12"><br />

                <!-- Date and Time -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.date')</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="datepicker" name="datepicker" data-date-format="yyyy/mm/dd" readonly />
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

                <!-- Receipt Section Previous Day Amount -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.receipt_section_previous_day_amount') <span class="required" aria-required="true">*</span></label>
                        <input type="number" step="0.01" id="rec_sec_prev_day_amt" name="rec_sec_prev_day_amt" class="form-control" required>
                    </div>
                </div>

                <!-- Receipt Section Opening Stock Amount -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.receipt_section_opening_stock_amount') <span class="required" aria-required="true">*</span></label>
                        <input type="number" step="0.01" id="rec_sec_opn_stock_amt" name="rec_sec_opn_stock_amt" class="form-control" required>
                    </div>
                </div>
                
                <!-- Issue Section Previous Day Amount --> 
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.issue_section_previous_day_amount') <span class="required" aria-required="true">*</span></label>
                        <input type="number" step="0.01" id="issue_section_previous_day_amount" name="issue_section_previous_day_amount" class="form-control" required>
                    </div>
                </div>

                 <!-- Manager Name --> 
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.manager_name') <span class="required" aria-required="true">*</span></label>
                        <input type="text" id="manager_name" name="manager_name" class="form-control" required>
                    </div>
                </div>


                <div class="col-md-12">
                    <h4 class="text-center">Meter Section Pump Last Meter</h4>
                    <div class="table-responsive">

                        <table class="table table-bordered" id="pump_last_meter">
                            <thead>
                                <tr>
                                    <td>@lang('mpcs::lang.product_sub_category')</td>
                                    <td>@lang('mpcs::lang.pump_name')</td>
                                    <td>@lang('mpcs::lang.meter')</td>
                                    <td></td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                    {!! Form::select('category[]', $fuelCategory, null, [
                                        'class' => 'form-control select2 category_select', 
                                        'style' => 'width: 100% !important;', 
                                        'required',
                                        'onchange' => 'loadPump(this)', 
                                        'placeholder' => __('mpcs::lang.please_select')
                                    ]) !!}
                                    </td>
                                    <td>
                                        {!! Form::select('pump[]', [], null, ['class' => 'form-control select2 pump_select', 'style' => 'width: 100% !important;', 'id' => 'pump', 'required',
                                            'placeholder' => __('mpcs::lang.please_select')]); !!}
                                    </td>
                                    <td>
                                    <input type="number" step="0.001" name="meter[]" class="form-control meter" required>
                                    </td>
                                    <td>
                                        <span class="input-group-btn">
                                            <button type="button" class="btn btn-default bg-white btn-flat" id="add_new_pump_row">
                                                <i class="fa fa-plus-circle text-primary fa-md"></i>
                                            </button>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>

                </div>
                <!-- Other fields remain unchanged -->

            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" id="close_21c_modal" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>
        {!! Form::close() !!}
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>

if (typeof hiddenCategories === 'undefined') {
    var hiddenCategories = new Set();
}

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
    

function loadPump(obj) {
    const selectedSubCategory = $(obj).val(); 
    const row = $(obj).closest('tr'); 
    const pumpSelect = row.find('.pump_select'); 

    pumpSelect.html('<option value="">@lang("messages.please_select")</option>');

    let selectedPumps = [];
    $('#pump_last_meter tbody .pump_select').each(function() {
        const pumpValue = $(this).val();
        if (pumpValue) {
            selectedPumps.push(pumpValue);
        }
    });

    if (selectedSubCategory) {
        $.get('/mpcs/get-subcategory-pump/' + selectedSubCategory, function (data) {
            $.each(data, function (id, name) {
                if (!selectedPumps.includes(id.toString())) { // Avoid already selected pumps
                    pumpSelect.append(new Option(name, id)); 
                }
            });
        }).fail(function () {
            toastr.error('No Pumps Found!');
        });
    }
}

$('#add_new_pump_row').on('click', function(){
    let lastRow = $('#pump_last_meter tbody tr:last'); // Get the last row
    let categorySelect = lastRow.find('.category_select'); 
    let pumpSelect = lastRow.find('.pump_select');
    
    let category = categorySelect.val();
    let pump = pumpSelect.val();
    let meter = lastRow.find('.meter').val();

    if (!category || !pump || !meter) {
        toastr.error('Please Enter All the Fields');
        return;
    }

    $.ajax({
        method: 'POST',
        url: '/mpcs/add-newpump-row',
        dataType: 'json',
        success: function(result) {
            let newRow = $(result.html); // Store new row
            $('#pump_last_meter tbody').append(newRow);

            let newCategorySelect = newRow.find('.category_select'); 
            let newPumpSelect = newRow.find('.pump_select'); 

            // **Check if only one pump option remains in the last row**
            if (pumpSelect.find('option').length <= 2) { 
                hiddenCategories.add(category); // Add to global hidden list
            }

            // **Remove already hidden categories from the new row**
            hiddenCategories.forEach(function(cat) {
                newCategorySelect.find('option[value="' + cat + '"]').remove();
            });
        },
    });
});

    // Remove row functionality
$(document).on('click', '.remove_row', function() {
    $(this).closest('tr').remove();
});

$(document).off('change', '.category_select', function () {
    loadPump(this);
});

$(document).on('submit', 'form#add_21c_form_settings', function(e) {
    e.preventDefault();

    // Disable the submit button to prevent multiple clicks
    $(this).find('button[type="submit"]').attr('disabled', true);

    // Serialize the form data
    var data = $(this).serializeArray();

    // Prepare dynamic data for pump_last_meter (category, pump, and meter)
    let categories = [];
    let pumps = [];
    let meters = [];

    $('#pump_last_meter tbody tr').each(function() {
        let category = $(this).find('.category_select').val();
        let pump = $(this).find('.pump_select').val();
        let meter = $(this).find('.meter').val();

        // Add category, pump, and meter as separate arrays
        categories.push(category);
        pumps.push(pump);
        meters.push(meter);
    });

    // Add pump_last_meter data as arrays to the serialized form data
    data.push({ name: 'categories', value: JSON.stringify(categories) });
    data.push({ name: 'pumps', value: JSON.stringify(pumps) });
    data.push({ name: 'meters', value: JSON.stringify(meters) });

    // Send the form data via AJAX
    $.ajax({
        method: $(this).attr('method'),
        url: '/mpcs/store-21c-form-setting',  // URL to send the data
        dataType: 'json',
        data: data,
        success: function(result) {
            if (result.success === true) {
                toastr.success(result.msg);
                $('div#form_16_a_settings_modal').modal('hide');
            } else {
                toastr.success(result.msg);
                $('#form_21c_settings_table').DataTable().ajax.reload(null, false);
                $('div#form_16_a_settings_modal').modal('hide');
            }
        },
        error: function() {
            toastr.error('An error occurred while submitting the form.');
        },
        complete: function() {
            $(this).find('button[type="submit"]').attr('disabled', false);
        }
    });
});

</script>

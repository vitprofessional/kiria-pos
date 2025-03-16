<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">
    {!! Form::open(['url' => action([\Modules\MPCS\Http\Controllers\F21FormController::class, 'mpcs21Update'], [$settings->id]), 'method' => 'post', 'id' => 'update_21c_form_settings' ]) !!}

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
                            <input type="text" class="form-control" id="datepicker" name="datepicker" value="{{ $settings->date }}" data-date-format="yyyy/mm/dd" readonly />
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
                            <input class="form-control timepicker" id="time" name="time" type="time"  value="{{ $settings->time }}" readonly>
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
                        <input type="text" name="starting_number" class="form-control" value="{{ $settings->starting_number }}" required>
                    </div>
                </div>

                <!-- Ref Previous Form Number -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.ref_previous_form_number') <span class="required" aria-required="true">*</span></label>
                        <input type="text" name="ref_pre_form_number" class="form-control" value="{{ $settings->ref_pre_form_number }}" required>
                    </div>
                </div>

                <!-- Receipt Section Previous Day Amount -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.receipt_section_previous_day_amount') <span class="required" aria-required="true">*</span></label>
                        <input type="number" step="0.01" id="rec_sec_prev_day_amt" name="rec_sec_prev_day_amt" class="form-control" value="{{ $settings->rec_sec_prev_day_amt }}" required>
                    </div>
                </div>

                <!-- Receipt Section Opening Stock Amount -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.receipt_section_opening_stock_amount') <span class="required" aria-required="true">*</span></label>
                        <input type="number" step="0.01" id="rec_sec_opn_stock_amt" name="rec_sec_opn_stock_amt" class="form-control" value="{{ $settings->rec_sec_opn_stock_amt }}" required>
                    </div>
                </div>
                
                <!-- Issue Section Previous Day Amount --> 
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.issue_section_previous_day_amount') <span class="required" aria-required="true">*</span></label>
                        <input type="number" step="0.01" id="issue_section_previous_day_amount" name="issue_section_previous_day_amount" class="form-control" value="{{ $settings->issue_section_previous_day_amount }}" required>
                    </div>
                </div>

                 <!-- Manager Name --> 
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.manager_name') <span class="required" aria-required="true">*</span></label>
                        <input type="text" id="manager_name" name="manager_name" class="form-control" value="{{ $settings->manager_name }}" required>
                    </div>
                </div>


                <div class="col-md-12">
                    <h4 class="text-center">Meter Section Pump Last Meter</h4>
                    <div class="table-responsive">

                    <table class="table table-bordered" id="pump_last_meter">
    <thead>
        <tr>
            <td>@lang('mpcs::lang.product_category')</td>
            <td>@lang('mpcs::lang.pump_name')</td>
            <td>@lang('mpcs::lang.meter')</td>
            <td></td>
        </tr>
    </thead>
    <tbody id="pump_meter_body">
        @if (!empty($settings->categories) && !empty($settings->pumps) && !empty($settings->meters))
            @php
                $categories = json_decode($settings->categories, true);
                $pumps = json_decode($settings->pumps, true);
                $meters = json_decode($settings->meters, true);
            @endphp

            @foreach ($categories as $index => $category)

                @php

                    if (auth()->user()->can('superadmin')) {
                        $allPumps = \DB::table('products')
                            ->leftJoin('pumps', 'products.id', '=', 'pumps.product_id')
                            ->where('products.sub_category_id', $category)
                            ->whereNotNull('pumps.id')
                            ->pluck('pumps.pump_name', 'pumps.id');
                    } else {
                        $allPumps = \DB::table('products')
                            ->leftJoin('pumps', 'products.id', '=', 'pumps.product_id')
                            ->where('products.business_id', $business_id)
                            ->where('products.sub_category_id', $category)
                            ->whereNotNull('pumps.id')
                            ->pluck('pumps.pump_name', 'pumps.id');
                    }


                @endphp

                <tr>
                    <td>
                        {!! Form::select('category[]', $fuelCategory, $category, [
                            'class' => 'form-control select2 category_select', 
                            'style' => 'width: 100% !important;', 
                            'required',
                            'onchange' => 'loadPump(this)', 
                            'placeholder' => __('mpcs::lang.please_select')
                        ]) !!}
                    </td>
                    <td>
                        {!! Form::select('pump[]', $allPumps, $pumps[$index] ?? null, [
                            'class' => 'form-control select2 pump_select', 
                            'style' => 'width: 100% !important;', 
                            'required',
                            'placeholder' => __('mpcs::lang.please_select')
                        ]) !!}
                    </td>
                    <td>
                        <input type="number" step="0.001" name="meter[]" class="form-control meter" 
                               value="{{ $meters[$index] ?? '' }}" required>
                    </td>
                    <td>
                        @if ($index == 0)
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat" id="add_new_pump_row">
                                    <i class="fa fa-plus-circle text-primary fa-md"></i>
                                </button>
                            </span>
                        @else
                            <button type="button" class="btn btn-danger remove_row">
                                <i class="fa fa-minus-circle"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
        @else
            {{-- If no previous entries exist, show one empty row --}}
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
                    {!! Form::select('pump[]', [], null, [
                        'class' => 'form-control select2 pump_select', 
                        'style' => 'width: 100% !important;', 
                        'required',
                        'placeholder' => __('mpcs::lang.please_select')
                    ]) !!}
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
        @endif
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

function initializeHiddenCategories() {
    $('#pump_last_meter tbody tr').each(function () {
        let row = $(this);
        let categorySelect = row.find('.category_select');
        let pumpSelect = row.find('.pump_select');

        let category = categorySelect.val();

        // **If only one pump is available and it's selected, store the category**
        if (pumpSelect.find('option').length <= 2) { 
            hiddenCategories.add(category);
        }
    });

    // **Hide already selected subcategories in all rows**
    // $('#pump_last_meter tbody .category_select').each(function () {
    //     let categorySelect = $(this);
    //     hiddenCategories.forEach(function (cat) {
    //         categorySelect.find('option[value="' + cat + '"]').remove();
    //     });
    // });
}

$(document).ready(function () {
    initializeHiddenCategories();
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

                console.log(result);

                $('#pump_last_meter tbody').append(newRow);

                //initializeHiddenCategories();

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

$(document).on('submit', 'form#update_21c_form_settings', function(e) {
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
        url:  $(this).attr('action'),
        dataType: 'json', // URL to send the data
        data: data,
        success: function(result) {
            if (result.success === true) {
                toastr.success(result.msg);
                $('#form_21c_settings_table').DataTable().ajax.reload(null, false);
                $('div#update_form_16_a_settings_modal').modal('hide');
            } else {
                toastr.success(result.msg);
                $('#form_21c_settings_table').DataTable().ajax.reload(null, false);
                $('div#update_form_16_a_settings_modal').modal('hide');
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

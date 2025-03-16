<!-- Main content -->
<section class="content">
   
<div class="row">
        <div class="col-md-3 text-red">
            <b>@lang('mpcs::lang.date_and_time'): <span class="9c_from_date">{{$date}}</span> </b>
        </div>
        <div class="col-md-3 text-red">
            <b>@lang('mpcs::lang.ref_previous_form_number'): <span class="9c_from_date">{{$form_number}}</span> </b>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <h5 style="font-weight: bold;">@lang('mpcs::lang.user_added'): {{$userAdded}} <br>
            </div>
        </div>
    </div>


<div class="row">
        <div class="box-tools pull-right" style="margin: 14px 20px 14px 0;">
            <button type="button" class="btn btn-primary btn-modal" data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@get16AFormSetting')}}" data-container=".form_16_a_settings_modal" @if($setting)  disabled @endif
            >
                <i class="fa fa-plus"></i> @lang( 'mpcs::lang.add_16_a_form_settings' )</button>
        </div>
    </div>


<div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="col-md-12">
                <div class="box-body" style="margin-top: 20px;">
                    <div class="row">
                        <div class="col-md-12">
                            
                            <div id="msg"></div>

                            <table id="form_16a_settings_table" class="table table-striped table-bordered" cellspacing="0"
                                width="100%">
                                <thead>
                                    <tr>
                                        <th>@lang('mpcs::lang.action')</th>
                                        <th>@lang('mpcs::lang.date_and_time')</th>
                                        <th>@lang('mpcs::lang.form_starting_number')</th>
                                        <th>@lang('mpcs::lang.total_previous_total_purchase_with_vat')</th>
                                        <th>@lang('mpcs::lang.total_previous_total_sale_with_vat')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            @endcomponent
        </div>
    </div>

  
    <div class="modal fade form_16_a_settings_modal" id="form_16_a_settings_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade update_form_16_a_settings_modal" id="update_form_16_a_settings_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>


</section>
<!-- /.content -->

<script type="text/javascript">

 $(document).ready(function(){

 //form 9a list
 form_16a_settings_table = $('#form_16a_settings_table').DataTable({
            processing: true,
            serverSide: true,
            paging: false, 
            ajax: {
                "type": "get",
                "url": "/mpcs/16aformsettings",
            },
            columns: [
                { data: 'action', name: 'action', searchable: false, orderable: false },
                { data: 'date', name: 'date' },
                { data: 'starting_number', name: 'starting_number' },
                { data: 'total_purchase_price_with_vat', name: 'total_purchase_price_with_vat' },
                { data: 'total_sale_price_with_vat', name: 'total_sale_price_with_vat' },
            ]
        });

 });   


            //form 9a section
        $(document).on('submit', 'form#add_16a_form_settings', function(e) {
        
        e.preventDefault();
        $(this).find('button[type="submit"]').attr('disabled', true);
        var data = $(this).serialize();
        
        $.ajax({
            method: $(this).attr('method'),
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            success: function(result) {
                if (result.success == true) {
                   toastr.success(result.msg);
                   form_16a_settings_table.ajax.reload();
                    $('div#form_16_a_settings_modal').modal('hide');

                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });   
    
    
    
        //update form 9a section
        $(document).on('submit', 'form#update_16a_form_settings', function(e) {

        e.preventDefault();
        $(this).find('button[type="submit"]').attr('disabled', true);
        var data = $(this).serialize();

        $.ajax({
            method: $(this).attr('method'),
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            success: function(result) {
                if (result.success == true) {
                    
                    toastr.success(result.msg);
                    form_16a_settings_table.ajax.reload();
                    $('div#update_form_16_a_settings_modal').modal('hide');
                } else {
                    toastr.error(result.msg);
                }
            },
        });
        });

</script>

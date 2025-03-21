@extends('layouts.app')
@section('title',__('installment::lang.installment_plan'))

@section('content')
    <style>
        .table-striped th{
            background-color: #626161;
            color: #ffffff;
        }
    </style>
    <section class="content-header">
        <h1>@lang('installment::lang.installment_plan')</h1>
    </section>

    <section class="content">
        @component('components.widget', ['class' => 'box-primary', 'title' =>''])
            @can('installment.view')
                @slot('tool')
                    <div class="box-tools pull-right">

                @if(auth()->user()->can('installment.system_add'))
                            <button type="button" class="btn  btn-primary add_button" >
                                <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                        @endif
                    </div>
                @endslot
            @endcan
            @can('installment.view')

                <div class="table-responsive">
                    <table class="table table-bordered table-striped " id="data_table">
                        <thead>
                        <tr>
                            <th>Installment Plan</th>
                            <th>Number of Installments</th>
                            <th>Payment Period </th>
                            <th>Type </th>
                            <th>Interest Rate</th>
                            <th>Interest Type </th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                        </thead>

                    </table>
                </div>


            @endcan
        @endcomponent



    </section>

    <div class="modal fade div_modal" tabindex="-1" role="dialog"
         aria-labelledby="gridSystemModalLabel">
    </div>
@endsection


@section('javascript')
 <script type="text/javascript">
$(document).ready(function () {
    // $('.modal').modal({
    //     backdrop: 'static',
    //     keyboard: false
    // });
    var data_table = $('#data_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/installment/system',
        columnDefs: [
            {
                targets: 7,
                orderable: false,
                searchable: false,
            },
        ],
    });

    $(document).on('click', 'button.delete_button', function () {
        swal({
            title: LANG.sure,
            text: 'This system will be deleted ',
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                var data = $(this).serialize();
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function (result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            data_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });

    });

    $(document).on('click', 'button.edit_button', function () {
        $('div.div_modal').load($(this).data('href'), function() {
            $(this).modal({backdrop: 'static', keyboard: false},'show');
            $('form#edit_installment_system').submit(function(e) {
                e.preventDefault();
                var form = $(this);
                var data = form.serialize();

                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    dataType: 'json',
                    data: data,
                    beforeSend: function(xhr) {
                        __disable_submit_button(form.find('button[type="submit"]'));
                    },
                    success: function(result) {
                        if (result.success == true) {
                            $('div.div_modal').modal('hide');

                            toastr.success(result.msg);
                            data_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
        });

    });





    $(document).on('click', 'button.add_button', function () {
        $.ajax({
            method: 'GET',
            url: '/installment/system/create',
            dataType: 'html',
            success: function (result) {
                $(".div_modal").html(result).modal({backdrop: 'static', keyboard: false},'show');
                data_table.ajax.reload();
            },
        });

    });

    $(document).on('submit', 'form#add_installment_system', function(e) {
        e.preventDefault();
        var form = $(this);
        var data = form.serialize()
        $.ajax({
            method: 'POST',
            url: '/installment/system',
            dataType: 'json',
            data: data,
            beforeSend: function (xhr) {
                __disable_submit_button(form.find('button[type="submit"]'));
            },
            success: function (result) {
                if (result.success == true) {
                    $('div.div_modal').modal('hide');
                    toastr.success(result.msg);
                    data_table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });



});
</script>

@endsection
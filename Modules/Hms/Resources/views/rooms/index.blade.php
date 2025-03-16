@extends('layouts.app')
@section('title', __('hms::lang.rooms'))
@section('content')
    
    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black"> @lang('hms::lang.rooms')
        </h1>
        <p><i class="fa fa-info-circle"></i> @lang('hms::lang.rooms_help_text') </p>
    </section>

    <!-- Main content btn btn-primary pull-right all-p-btn   //tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full pull-right-->
    <section class="content">

        @component('components.widget')
            <div class="box-tools tw-flex tw-justify-end tw-gap-2.5 tw-mb-4">
                @can('hms.add_booking')
                        <a class="btn btn-primary pull-right all-p-btn"
                         href="{{ action([\Modules\Hms\Http\Controllers\RoomController::class, 'create']) }}">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang('messages.add')
                        </a>
                @endcan
            </div>
            <table class="table table-bordered table-striped" id="rooms_table">
                <thead>
                    <tr>
                        <th style="width: 15%; text-align: center;">
                            @lang('hms::lang.type')
                        </th>
                        <th style="width: 10%; text-align: center;">
                            @lang('hms::lang.max_no_of_adult')
                        </th>
                        <th style="width: 10%; text-align: center;">
                            @lang('hms::lang.max_no_of_child')
                        </th>
                        <th style="width: 10%; text-align: center;">
                            @lang('hms::lang.max_occupancy')
                        </th>
                        <th style="width: 25%; text-align: center;">
                            @lang('hms::lang.description')
                        </th>
                        <th style="width: 10%; text-align: center;">
                            @lang('lang_v1.created_at')
                        </th>
                        <th style="width: 20%; text-align: center;">
                            @lang('messages.action')
                        </th>
                    </tr>
                </thead>
            </table>
        @endcomponent

    </section>
    <!-- /.content -->

@endsection

@section('javascript')

    <script type="text/javascript">
        $(document).ready(function() {
            superadmin_business_table = $('#rooms_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                ajax: {
                    url: "{{ action([\Modules\Hms\Http\Controllers\RoomController::class, 'index']) }}",
                },
                aaSorting: [
                    [5, 'desc']
                ],
                columns: [{
                        data: 'type',
                        name: 'hms_room_types.type'
                    },
                    {
                        data: 'no_of_adult',
                        name: 'hms_room_types.no_of_adult'
                    },
                    {
                        data: 'no_of_child',
                        name: 'hms_room_types.no_of_child'
                    },
                    {
                        data: 'max_occupancy',
                        name: 'hms_room_types.max_occupancy'
                    },
                    {
                        data: 'description',
                        name: 'hms_room_types.description'
                    },
                    {
                        data: 'created_at',
                        name: 'hms_room_types.created_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        sorting: false,
                    },
                ]
            });

            $(document).on('click', 'a.delete_room_confirmation', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    text: "Once deleted, you will not be able to recover this Room !",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((confirmed) => {
                    if (confirmed) {
                        window.location.href = $(this).attr('href');
                    }
                });
            });
        });
    </script>

@endsection

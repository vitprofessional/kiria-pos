<!-- Main content -->
<section class="content">
    {{-- <div class="row">
      <div class="col-md-12">
          @component('components.filters', ['title' => __('report.filters')])
          <div class="row">
          <div class="col-md-3">
              <div class="form-group">
                  {!! Form::label('district_id', __( 'member::lang.district' )) !!}
                  {!! Form::select('district_id',$districts, null, ['class' => 'form-control select2',
                  'required',
                  'placeholder' => __(
                  'shipping::lang.please_select' ), 'id' => 'district_id']);
                  !!}
              </div>
          </div>
          </div>
          @endcomponent
        </div>
    </div> --}}
  
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __(
            'member::lang.all_staff_members')])
            @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-primary btn-modal pull-right mb-12" id="add_electrorate_btn"
                    data-href="{{action('\Modules\Member\Http\Controllers\MemberStaffController@create')}}"
                    data-container=".common_model">
                    <i class="fa fa-plus"></i> @lang( 'member::lang.add' )</button>
            </div>
            @endslot

            <div class="row">
                <div class="col-md-12 table-responsive">
                    <table class="table table-striped table-bordered" id="staff_assign_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang( 'messages.action' )</th>
                                <th>@lang( 'member::lang.date' )</th>
                                <th>@lang( 'member::lang.staff_name' )</th>
                                <th>@lang( 'member::lang.designation' )</th>
                                <th>@lang( 'member::lang.status' )</th>
                                <th>@lang( 'member::lang.add_by' )</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->
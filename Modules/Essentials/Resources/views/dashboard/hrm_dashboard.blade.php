@extends('layouts.app')
@section('title', __('essentials::lang.hrm'))

@section('content')
@include('essentials::layouts.nav_hrm')
<style>
    /* Add this CSS to your existing styles or create a new style block */
.row-custom {
    display: flex;
    flex-wrap: wrap;
}

.container-flex {
    display: flex;
    flex-wrap: wrap;
}

.container-flex > div[class^="col-"] {
    display: flex;
    flex-direction: column;
}

.col-custom {
    flex: 1;
}

</style>
<!-- Main content -->
<section class="content">
	<div class="row row-custom">
    <div class="container-flex" style="width: 100%">
		<div class="col-md-4 col-sm-6 col-xs-12 col-custom">
			<div class="card bg-light text-dark"  style="border: 1px solid #D9D8D8;padding: 20px;height: 100%">
				<div class="box-header with-border">
	                
	                <h4 class="box-title"><i class="fas fa-sign-out-alt"></i>&nbsp;@lang('essentials::lang.my_leaves')</h4>
	            </div>
                <div class="box-body p-10">
                    <table class="table no-margin">
                    	<thead>
                    		@forelse($users_leaves as $user_leave)
                    			<tr>
                    				<td>
                    					{{@format_date($user_leave->start_date)}}
                    					- {{@format_date($user_leave->end_date)}}
                    				</td>
                    				<td>
                    					{{$user_leave->leave_type->leave_type}}
                    				</td>
                    			</tr>
                    		@empty
                    			<tr>
	                    			<td colspan="2" class="text-center">
	                    				@lang('lang_v1.no_data')
	                    			</td>
	                    		</tr>
                    		@endforelse
                    	</thead>
                    </table>
                </div>
	        </div>
		</div>
		<div class="col-md-4 col-sm-6 col-xs-12 col-custom">
			<div class="card bg-light text-dark"  style="border: 1px solid #D9D8D8;padding: 20px;height: 100%">
				<div class="box-header with-border">
	                
	                <h4 class="box-title"><i class="fas fa-bullseye"></i>&nbsp;@lang('essentials::lang.my_sales_targets')</h4>
	            </div>
                <div class="box-body p-10">
                    <table class="table no-margin">
                    	<thead>
                    		<tr>
                    			<td>
                    				<strong>@lang('essentials::lang.target_achieved_last_month'):
                    				</strong>
                    				<h4 class="text-success">@format_currency($target_achieved_last_month)</h4>
                    			</td>
                    			<td>
                    				<strong>@lang('essentials::lang.target_achieved_this_month'):
                    				</strong>
                    				<h4 class="text-success">@format_currency($target_achieved_this_month)</h4>
                    			</td>
                    		</tr>
                    		<tr>
                    			<th>
                    				@lang('essentials::lang.targets')
                    			</th>
                    			<th>
                    				@lang('essentials::lang.commission_percent')
                    			</th>
                    		</tr>
                    		@forelse($sales_targets as $target)
                    			<tr>
                    				<td>
                    					@format_currency($target->target_start)
                    					- @format_currency($target->target_end)
                    				</td>
                    				<td>
                    				    @if(is_numeric($target->commission_percent) && floor($target->commission_percent) != $target->commission_percent)
                                            {{ rtrim(number_format($target->commission_percent, 10), '0') }}
                                        @else
                                            {{ number_format($target->commission_percent, 0) }}
                                        @endif
                    					%
                    				</td>
                    			</tr>
                    		@empty
                    			<tr>
	                    			<td colspan="2" class="text-center">
	                    				@lang('lang_v1.no_data')
	                    			</td>
	                    		</tr>
                    		@endforelse
                    	</thead>
                    </table>
                </div>
	        </div>
		</div>
		@include('essentials::dashboard.birthdays')
		@if(!$is_admin)
        	@include('essentials::dashboard.holidays')
     	@endif
     </div>
     </div>
     <div class="row row-custom" style="margin-top: 20px">
        <div class="col-md-4 col-sm-6 col-xs-12 text-center">
            <a href="{{action([\Modules\Essentials\Http\Controllers\PayrollController::class, 'getMyPayrolls'])}}"
                class="btn btn-lg btn-success">
                <i class="fas fa-coins"></i>
                @lang('essentials::lang.my_payrolls')
            </a>
        </div>
	</div>
	@if($is_admin)
	<hr>
	@endif
	<div class="row row-custom">
	    <div class="container-flex" style="width: 100%">
		@can('user.view')
	    <div class="col-md-4 col-sm-6 col-xs-12 col-custom">
	        <div class="card bg-light text-dark"  style="border: 1px solid #D9D8D8;padding: 20px;height: 100%">
	            <div class="box-body p-10">
                	<div class="info-box info-box-new-style">

		            	<div class="info-box-content">
		            	    <span class="info-box-icon bg-aqua"><i class="fas fa-users"></i></span>
		              		<span class="info-box-text">{{ __('user.users') }}</span>
		              		<span class="info-box-number">{{$users->count()}}</span>
		            	</div>
		            	<!-- /.info-box-content -->
		          	</div>
	                <table class="table no-margin">
	                    <thead>
	                        <tr>
	                            <th>{{ __('essentials::lang.department') }}</th>
	                            <th>{{ __('sale.total') }}</th>
	                        </tr>
	                    </thead>
	                    <tbody>
	                        @forelse($departments as $department)
	                            <tr>
	                                <td>{{$department->name}}</td>
	                                <td>@if(!empty($users_by_dept[$department->id])){{count($users_by_dept[$department->id])}} @else 0 @endif</td>
	                            </tr>
	                        @empty
	                            <tr>
	                                <td colspan="2" class="text-center">@lang('lang_v1.no_data')</td>
	                            </tr>
	                        @endforelse
	                    </tbody>
	                </table>
            	</div>
        	</div>
    	</div>
    	@endcan
        @can('essentials.approve_leave')
    	<div class="col-md-4 col-sm-6 col-xs-12 col-custom">
            <div class="card bg-light text-dark"  style="border: 1px solid #D9D8D8;padding: 20px;height: 100%">
                <div class="box-header with-border">
                    
                    <h4 class="box-title"><i class="fas fa-user-times"></i>&nbsp;@lang('essentials::lang.leaves')</h4>
                </div>
                <div class="box-body p-10">
                    <table class="table no-margin">
                        <tr>
                            <th class="bg-light-gray" colspan="2">@lang('home.today')</th>
                        </tr>
                        @forelse($todays_leaves as $leave)
                			<tr>
                				<td>
                					{{@format_date($leave->start_date)}}
                					- {{@format_date($leave->end_date)}}
                				</td>
                				<td>
                					{{$leave->leave_type->leave_type}}
                				</td>
                			</tr>
                		@empty
                			<tr>
                    			<td colspan="2" class="text-center">
                    				@lang('lang_v1.no_data')
                    			</td>
                    		</tr>
                		@endforelse
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <th class="bg-light-gray" colspan="2">@lang('lang_v1.upcoming')</th>
                        </tr>
                        @forelse($upcoming_leaves as $leave)
                			<tr>
                				<td>
                					{{@format_date($leave->start_date)}}
                					- {{@format_date($leave->end_date)}}
                				</td>
                				<td>
                					{{$leave->leave_type->leave_type}}
                				</td>
                			</tr>
                		@empty
                			<tr>
                    			<td colspan="2" class="text-center">
                    				@lang('lang_v1.no_data')
                    			</td>
                    		</tr>
                		@endforelse
                    </table>
                </div>
            </div>
        </div>
        @endcan
        @if($is_admin)
        	@include('essentials::dashboard.holidays')
     	@endif
     	</div>
    </div>
    <div class="row row-custom" style="margin-top: 20px;">
        <div class="container-flex" style="width: 100%">
    	@if($is_admin)
    		<div class="col-md-4 col-sm-6 col-xs-12 col-custom">
	        	<div class="card bg-light text-dark"  style="border: 1px solid #D9D8D8;padding: 20px;height: 100%">
	        		<div class="box-header with-border">
	                    
	                    <h4 class="box-title"><i class="fas fa-user-check"></i>&nbsp;@lang('essentials::lang.todays_attendance')</h4>
	                </div>
	                <div class="box-body p-10">
	                    <table class="table no-margin">
	                    	<thead>
	                    		<tr>
	                    			<th>
	                    				@lang('essentials::lang.employee')
	                    			</th>
	                    			<th>
	                    				@lang('essentials::lang.clock_in')
	                    			</th>
	                    			<th>
	                    				@lang('essentials::lang.clock_out')
	                    			</th>
	                    		</tr>
	                    	</thead>
	                        <tbody>
		                        @forelse($todays_attendances as $attendance)
	                                <tr>
	                                    <td>{{$attendance->employee->user_full_name}}</td>
	                                    <td>
	                                    	{{@format_datetime($attendance->clock_in_time)}}

	                                    	@if(!empty($attendance->clock_in_note))
	                                    		<br><small>{{$attendance->clock_in_note}}</small>
	                                    	@endif
	                                    </td>
	                                    <td>
	                                    	@if(!empty($attendance->clock_out_time))
	                                    		{{@format_datetime($attendance->clock_out_time)}}
	                                    	@endif

	                                    	@if(!empty($attendance->clock_out_note))
	                                    		<br><small>{{$attendance->clock_out_note}}</small>
	                                    	@endif
	                                   	</td>
	                                </tr>
	                            @empty
	                                <tr>
	                                    <td colspan="3" class="text-center">@lang('lang_v1.no_data')</td>
	                                </tr>
	                            @endforelse
	                        </tbody>
	                    </table>
	                </div>
	            </div>
	        </div>

	        <div class="col-md-8 col-sm-12 col-xs-12">
	        	<div class="card bg-light text-dark"  style="border: 1px solid #D9D8D8;padding: 20px;height: 100%">
	        		<div class="box-header with-border">
	                    
	                    <h4 class="box-title"><i class="fas fa-bullseye"></i>&nbsp;@lang('essentials::lang.sales_targets')</h4>
	                </div>
	                <div class="box-body">
	                	<table class="table" id="sales_targets_table" style="width: 100%;">
	                		<thead>
	                			<tr>
	                				<th>@lang('report.user')</th>
	                				<th>@lang('essentials::lang.target_achieved_last_month')</th>
	                				<th>@lang('essentials::lang.target_achieved_this_month')</th>
	                			</tr>
	                		</thead>
	                	</table>
	                </div>
	            </div>
	        </div>
    	@endif
        </div>
    </div>
    
</section>
@stop
@section('javascript')
<script type="text/javascript">
	$(document).ready( function(){
		if ($('#sales_targets_table').length) {
			var sales_targets_table = $('#sales_targets_table').DataTable({
		        processing: true,
		        serverSide: true,
		        searching: false,
		        scrollY:        "75vh",
		        scrollX:        true,
		        scrollCollapse: true,
		        dom: 'Btirp',
		        fixedHeader: false,
		        ajax: "{{action([\Modules\Essentials\Http\Controllers\DashboardController::class, 'getUserSalesTargets'])}}"
		    });
		}
	});
</script>
@endsection


	<button type="button" class="btn btn-sm pull-right btn-primary btn-modal" data-href="{{action('\App\Http\Controllers\TaxonomyController@create')}}?type=auto-device" data-container=".category_modal" id="category">
		<i class="fa fa-plus"></i>
		@lang( 'messages.add' )
	</button>
<br><br>
<div class="table-responsive">
    <table class="table table-bordered table-striped" id="device_table" style="width: 100%">
        <thead>
            <tr>
                <th>@lang('repair::lang.device')</th>
                  <th>@lang( 'lang_v1.description' )</th>
                <th>@lang('messages.action')</th>
                 
            </tr>
        </thead>
    </table>
</div>
<div class="modal fade category_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
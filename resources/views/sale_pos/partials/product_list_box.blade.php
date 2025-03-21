<div class="box box-widget">
	<div class="box-header with-border w-100">
		@php
		//  temp cat id and brand id if there is any temp data
			$cat_id_suggestion = !empty($temp_data->cat_id_suggestion)?$temp_data->cat_id_suggestion:0;
			$brand_id_suggestion = !empty($temp_data->brand_id_suggestion)?$temp_data->brand_id_suggestion:0;
		@endphp
	@if(!empty($categories))
	<div class="form-group col-md-12" style="width: 100% !important">
		<select class="select2" id="product_category" style="width:45% !important">

			<option value="all">@lang('lang_v1.all_category')</option>

			@foreach($categories as $category)
				<option value="{{$category['id']}}" @if($category['id'] == $cat_id_suggestion) selected @endif>{{$category['name']}}</option>
			@endforeach

			@foreach($categories as $category)
				@if(!empty($category['sub_categories']))
					<optgroup label="{{$category['name']}}">
						@foreach($category['sub_categories'] as $sc)
							<i class="fa fa-minus"></i> <option value="{{$sc['id']}}">{{$sc['name']}}</option>
						@endforeach
					</optgroup>
				@endif
			@endforeach
		</select>
	</div>	
	@endif
	
	@if(!empty($brands))
	<div class="form-group col-md-12" style="width: 100% !important">
	
		&nbsp;
		{!! Form::select('size', $brands, !empty($brand_id_suggestion)?$brand_id_suggestion:null, ['id' => 'product_brand', 'class' => 'select2', 'name' => null, 'style' => 'width:45% !important']) !!}
	</div>	
	@endif
	

	<div class="box-tools pull-right">
		<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
	</div>

	<!-- /.box-tools -->
	</div>
	<!-- /.box-header -->
	<input type="hidden" id="suggestion_page" value="1">
	<div class="box-body">
	<div class="row">
		<div class="col-md-12">
			<div class="eq-height-row" id="product_list_body"></div>
		</div>
		<div class="col-md-12 text-center" id="suggestion_page_loader" style="display: none;">
			<i class="fa fa-spinner fa-spin fa-2x"></i>
		</div>
	</div>
	</div>
	<!-- /.box-body -->
</div>
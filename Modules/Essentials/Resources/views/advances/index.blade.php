@extends('layouts.app')
@section('title', __('essentials::lang.hrm_advance'))

@section('content')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>


    <div class="row" id="app">
        <div class="col-md-12">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item active">
                        <a href="#salary_payment" class="salary_payment" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('essentials::lang.hrm_tab_salary_payment')</strong>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#employee_advance" class="employee_advance" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('essentials::lang.hrm_tab_employee_advance')</strong>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#employee_advance_settings" class="employee_advance_settings" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('essentials::lang.hrm_tab_employee_advance_settings')</strong>
                        </a>
                    </li>
                    
                </ul>
                <div class="tab-content">
					<div class="tab-pane active" id="salary_payment" >
					<section class="container">
					
						<div class="row">
							<div class="col-md-12">
								<div class="" style="margin-bottom: 5px !important;">
									<br />
									<div id="accordion1" class="according  ">
										<div class="card">
											<div class="card-header" style="cursor: pointer;">
												<a class="card-link" data-toggle="collapse" style="padding-top: 5px !important; padding-bottom: 5px !important" href="#accordion11" aria-expanded="true"> <i class="fa fa-filter" aria-hidden="true"></i>  Filters </a>
											</div>
											<div id="accordion11" class="collapse show" data-parent="#accordion1" style="">
												<div class="card-body">
													<div class="row">
														<div class="col-md-2">
															<div class="form-group">
																<label for="date">@lang( 'essentials::lang.hrm_advance_label_date' )</label>
																<input v-model="payment.date" class="form-control" ref="date" name="date" type="text">
															</div>
														</div>
														<div class="col-md-2">
															<div class="form-group">
																<label for="period">@lang( 'essentials::lang.hrm_advance_label_period' )</label>
																<input v-model="payment.period" class="form-control" ref="daterange" name="period" type="text" />
															</div>
														</div>
														<div class="col-md-2">
															<div class="form-group">
																<label for="period">@lang( 'essentials::lang.hrm_advance_label_type' )</label>
																<br />
																<select v-model="payment.payment_type_id" class="form-select filter-select" aria-label="Default select example" >
																  <option value=""></option>
																  <template v-for="setting, in settings">
																	<option :value="setting.id">@{{ setting.payment_type }}</option>
																  </template>
																</select>
															</div>
														</div>
														<div class="col-md-2">
															<div class="form-group">
																<label for="period">@lang( 'essentials::lang.hrm_advance_label_account' )</label>
																<br />
																<select v-model="payment.account" class="form-select filter-select" aria-label="Default select example" >
																  <option value=""></option>
																  <template v-for="account, in accounts[1]">
																	<option :value="account.id">@{{ account.name }}</option>
																  </template>
																</select>
															</div>
														</div>
														<div class="col-md-2">
															<div class="form-group">
																<label for="paid">@lang( 'essentials::lang.hrm_advance_label_paid' )</label>
																<br />
																<select v-model="payment.paid" class="form-select filter-select" aria-label="Default select example" >
																	<option value=""></option>
																	<option value="yes">
																		@lang( 'essentials::lang.hrm_advance_label_paid_yes' )
																	</option>
																	<option value="no">
																		@lang( 'essentials::lang.hrm_advance_label_paid_no' )
																	</option>
																</select>
															</div>
														</div>
														<div class="col-md-2" v-if="showCheck">
															<div class="form-group">
																<label for="check">@lang( 'essentials::lang.hrm_advance_label_check' ):</label>
																<input v-model="payment.check" class="form-control" name="check" type="text" />
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>        
							</div>
						</div>
							
						@component('components.widget', ['class' => 'box-solid'])
	
							<div class="table-responsive">
								<div width="100%" class="d-flex" style="justify-content: end;"   >
									<p class="text-danger" style="margin-right:30px;"><b>Total:</b></p><p class="text-danger"> @{{ formatNumber(totalAmount,business.currecy_precision) }}</p>
								</div>
								<table class="table table-bordered table-striped w-100" id="employees_table">
									<thead>
										<tr>
											<th width="5%">@lang( 'essentials::lang.employee_no' )</th>
											<th width="20%">@lang( 'essentials::lang.employee_name' )</th>
											<th width="20%">@lang( 'essentials::lang.hrm_advance_label_type' )</th>
											<th width="20%">@lang( 'essentials::lang.hrm_advance_title_amount' )</th>
											<th width="25%" v-show="showPaidAmount">@lang( 'essentials::lang.hrm_advance_title_paid' )</th>
										</tr>
									</thead>
									<tbody>
										
										<tr v-for="(emp,ind) in employees">
											<td>@{{ emp.employee_no }}</td>
											<td>@{{ emp.name }}</td>
											<td class="text-danger" >
												<template v-if="payment.payment_type_id != undefined && payment.payment_type_id != null">
													@{{ settings.find(item => item.id == payment.payment_type_id).payment_type }}
												</template>
											</td>
											<td class="text-danger">
												<input v-model="emp.amount" class="form-control" @input="syncAmountPaid(emp)"/>
											</td>
											<td class="text-danger" v-show="showPaidAmount">
												<input v-model="emp.amount_paid" class="form-control"/>
											</td>
											
										</tr>
										
									</tbody>
									<tfoot>
										<tr>
											<th class="text-danger" colspan="3" align="right">Total</th>
											<th class="text-danger" >@{{ formatNumber(totalAmount,business.currecy_precision) }}</th>
											<th class="text-danger" v-show="showPaidAmount">@{{ formatNumber(totalAmountPaid,business.currecy_precision) }}</th>
										</tr>
									</tfoot>
								</table>
								
							</div>
							<div class="row"  style="display: flex; align-items: center; justify-content: flex-end;">
								<button class="btn btn-primary btn-sm" style="margin-left: 10px;" type="submit" @click="saveAdvancePayments">Save</button>
							</div>
						@endcomponent
					</section>
					</div>
                        
					<div class="tab-pane" id="employee_advance">
						<section class="content">
						
						
						@component('components.widget', ['class' => 'box-solid'])

							<div class="table-responsive">
								<br />
								<table class="table table-bordered table-striped w-100" id="advances_table">
									<thead>
										<tr>
											<th>@lang( 'messages.action' )</th>
											<th width="2%">#</th>
											<th width="3%">@lang( 'essentials::lang.employee_no' )</th>
											<th width="20%">@lang( 'essentials::lang.employee_name' )</th>
											<th width="15%">@lang( 'essentials::lang.hrm_advance_title_amount' )</th>
											<th width="15%" >@lang( 'essentials::lang.hrm_advance_title_paid' )</th>
											<th width="15%" >@lang( 'essentials::lang.hrm_advance_title_date' )</th>
											<th width="15%" >@lang( 'essentials::lang.hrm_advance_title_period_start' )</th>
											<th width="15%" >@lang( 'essentials::lang.hrm_advance_title_period_end' )</th>
										</tr>
									</thead>
									<tbody>
										
										<tr v-for="(adv,ind) in advances">
											<td>
												
												<div class="btn-group open">
													<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">Actions<span class="caret"></span><span class="sr-only">Toggle Dropdown
														</span>
													</button>
													<ul class="dropdown-menu dropdown-menu-left" role="menu" x-placement="top-start" style="position: absolute; transform: translate3d(0px, -140px, 0px); top: 0px; left: 0px; will-change: transform;">
														<li>
															<a @click="showView(adv)">
																<i class="fa fa-eye"></i> View
															</a>
														</li>
														<li>
															<a @click="showEdit(adv)">
																<i class="fa fa-edit"></i> Edit
															</a>
														</li>
														
													</ul>
												</div>
												
											</td>
											<td>@{{ ind }}</td>
											<td>@{{ adv.employee_no }}</td>
											<td>@{{ adv.name }}</td>
											<td>@{{ adv.amount }}</td>
											<td>@{{ adv.amount_paid }}</td>
											<td>@{{ adv.datetime_entered }}</td>
											<td>@{{ adv.salary_period_start }}</td>
											<td>@{{ adv.salary_period_end }}</td>
											
										</tr>
										
									</tbody>
									
								</table>
								
							</div>
						@endcomponent
						</section>
					</div>
					
					<div class="tab-pane" id="employee_advance_settings">
						<section class="content">
						@component('components.widget', ['class' => 'box-solid'])
							<div class="row" style="display: flex; align-items: center; justify-content: flex-end;">
								<button class="btn btn-primary btn-sm" type="submit" @click="showSettingsForm({id:''})">Add Employee Payment Settings</button>
							</div>
							<hr />
							<div class="table-responsive">
								<br />
								<table class="table table-bordered table-striped w-100" id="settings_table">
									<thead>
										<tr>
											<th>@lang( 'messages.action' )</th>
											<th width="21%">@lang( 'essentials::lang.hrm_payment_settings_title_date' )</th>
											<th width="21%" >@lang( 'essentials::lang.hrm_payment_settings_title_type' )</th>
											<th width="21%" >@lang( 'essentials::lang.hrm_payment_settings_title_account' )</th>
											<th width="21%" >@lang( 'essentials::lang.hrm_payment_settings_title_remarks' )</th>
											<th width="21%" >@lang( 'essentials::lang.hrm_payment_settings_title_user' )</th>
										</tr>
									</thead>
									<tbody>
										
										<tr v-for="(setting,ind) in settings">
											<td>
												
												<div class="btn-group open">
													<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">Actions<span class="caret"></span><span class="sr-only">Toggle Dropdown
														</span>
													</button>
													<ul class="dropdown-menu dropdown-menu-left" role="menu" x-placement="top-start" style="position: absolute; transform: translate3d(0px, -140px, 0px); top: 0px; left: 0px; will-change: transform;">
														<li>
															<a @click="showSettingsForm(setting)">
																<i class="fa fa-edit"></i> Edit
															</a>
														</li>
														<li>
															<a @click="deletePaymentSetting(ind)">
																<i class="fa fa-trash"></i> Delete
															</a>
														</li>
														
													</ul>
												</div>
												
											</td>
											<td>@{{ setting.datetime_entered }}</td>
											<td>@{{ setting.payment_type }}</td>
											<td>@{{ accounts[0].find(item => item.id == setting.liability_account_id).name }}</td>
											<td>@{{ setting.remarks }}</td>
											<td>
												<template v-if="setting.user!= null && setting.user.id != undefined">
													@{{ setting.user.first_name }}
													@{{ setting.user.last_name }}
												</template>
											</td>
											
										</tr>
										
									</tbody>
									
								</table>
								
							</div>
						@endcomponent
						</section>
					</div>
                </div>
            </div>
        </div>
		
		
		<div id="editModal" class="modal fade" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" @click="closeEdit">&times;</button>
						<h4 class="modal-title">
							Edit Employee Advance
						</h4>
					</div>
					<div class="modal-body">
						<b>EMPLOYEE: @{{ advance.name }}</b>
						<hr />
						<div class="form-group">
							<label for="paid">@lang( 'essentials::lang.hrm_advance_title_amount' )</label>
							<input v-model="advance.amount" class="form-control" />
						</div>
						<div class="form-group">
							<label for="paid">@lang( 'essentials::lang.hrm_advance_label_paid' )</label>
							<select v-model="advance.paid" class="form-select filter-select">
								<option value=""></option>
								<option value="yes">@lang( 'essentials::lang.hrm_advance_label_paid_yes' )</option>
								<option value="no">@lang( 'essentials::lang.hrm_advance_label_paid_no' )</option>
							</select>
						</div>
						<div class="form-group">
							<label for="paid">@lang( 'essentials::lang.hrm_advance_title_paid' )</label>
							<input v-model="advance.amount_paid" class="form-control" />
						</div>
						<div class="form-group">
							<label for="paid">@lang( 'essentials::lang.hrm_advance_label_remarks' )</label>
							<input v-model="advance.remarks" class="form-control" />
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" @click="closeEdit">Close</button> 
						<button type="button" class="btn btn-primary" @click="saveAdvance">Save</button>
					</div>
				</div>
			</div>
		</div>
		
		<div id="settingsFormModal" class="modal fade" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" @click="closeSettingsForm">&times;</button>
						<h4 class="modal-title">
							Employee Payment Setting
						</h4>
					</div>
					<div class="modal-body">
						<div class="form-group">
							<label for="date">@lang( 'essentials::lang.hrm_payment_settings_title_date' )</label>
							<input v-model="setting.date" class="form-control" ref="datetime" name="datetime" type="text">
						</div>
						<div class="form-group">
							<label for="paid">@lang( 'essentials::lang.hrm_payment_settings_title_type' )</label>
							<input v-model="setting.payment_type" class="form-control" />
						</div>
						<div class="form-group">
							<label for="paid">@lang( 'essentials::lang.hrm_payment_settings_title_account' )</label>
							<select v-model="setting.liability_account_id" ref="select2" class="form-select filter-select" aria-label="Default select example" >
							  <option value=""></option>
							  <template v-for="account, in accounts[0]">
								<option :value="account.id">@{{ account.name }}</option>
							  </template>
							</select>
						</div>
						<div class="form-group">
							<label for="paid">@lang( 'essentials::lang.hrm_payment_settings_title_remarks' )</label>
							<input v-model="setting.remarks" class="form-control" />
						</div>
					</div>
					<div class="modal-footer">
					    <input type="checkbox" class="create_another" id="create_another" name="create_another" value="1">create another</a> 
						<button type="button" class="btn btn-default" @click="closeSettingsForm">Close</button> 
						<button type="button" class="btn btn-primary" @click="savePaymentSetting">Save</button>
					</div>
				</div>
			</div>
		</div>
		
		<div id="viewModal" class="modal fade" tabindex="-1" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" @click="closeView">&times;</button>
						<h4 class="modal-title">
							View Employee Advance
						</h4>
					</div>
					<div class="modal-body">
						
						<div class="card bg-light text-dark" style="border: 1px solid #D9D8D8;padding: 20px;height: 100%">
							<div class="box-header with-border">
								
								<h4 class="box-title"><i class="fas fa-account"></i> <b>EMPLOYEE: @{{ advance.name }}</b> </h4>
							</div>
							<div class="box-body p-10">
								<table class="table no-margin">
									<thead>
										<tr>
											<td>
												<strong>Amount:
												</strong>
												<h4 class="text-success">
													@{{ advance.amount }}
												</h4>
											</td>
											<td>
												<strong>Amount Paid:
												</strong>
												<h4 class="text-success">
													@{{ advance.amount_paid }}
												</h4>
											</td>
										</tr><tr>
											<td>
												<strong>Salary Period Form:
												</strong>
												<h4 class="text-success">
													@{{ advance.salary_period_start }}
												</h4>
											</td>
											<td>
												<strong>Salary Period To:
												</strong>
												<h4 class="text-success">
													@{{ advance.salary_period_end }}
												</h4>
											</td>
										</tr><tr>
											<td>
												<strong>Date:
												</strong>
												<h4 class="text-success">
													@{{ advance.datetime_entered }}
												</h4>
											</td>
											<td>
												<strong>paid Now?:
												</strong>
												<h4 class="text-success">
													@{{ advance.status?'yes':'no' }}
												</h4>
											</td>
										</tr>
									</thead>
								</table>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" @click="closeView">Close</button> 
					</div>
				</div>
			</div>
		</div>
    </div>
	
	

@endsection
@section('javascript')
<script>
    new Vue({
        el: '#app',
        data() {
            return {
				advance: {},
                setting: {date:'',payment_type:'',liability_account_id:'',remarks:''},
                advances: {!! json_encode($advances) !!},
                employees: {!! json_encode($employees) !!},
                business: {!! json_encode($business) !!},
                payment: {period:'{{ $startdate }} to {{ $enddate }}',date:'{{ $today }}',paid:"yes",check:'',account:'',payment_type_id:"3"},
				accounts: {!! json_encode($accounts) !!},
				accounts_with_check: {!! json_encode($accounts_with_check) !!},
				settings: {!! json_encode($settings) !!}
				
            }
        },
		computed: {
			totalAmount() {
			  return this.employees.reduce((acc, curr) => acc + parseFloat(curr.amount), 0);
			},
			totalAmountPaid() {
			  return this.employees.reduce((acc, curr) => acc + parseFloat(curr.amount_paid), 0);
			},
			showPaidAmount() {
			  return this.payment.paid == "yes";
			},
			showCheck() {
			  return this.accounts_with_check.includes(this.payment.account);
			}
		},
        mounted() {
            $(document).ready(() => {
                    
                
				$('.nav-tabs a').click(function (e) {
					e.preventDefault();
					$(this).tab('show');
				});

                $(this.$refs.daterange).daterangepicker(dateRangeSettings, (start, end) => {
                  this.payment.salary_period = `${start.format('YYYY-MM-DD')} to ${end.format('YYYY-MM-DD')}`;
                    console.log( this.payment.salary_period);

                });
				$(this.$refs.date).datepicker();
                $(this.$refs.datetime).datetimepicker({
					format: 'YYYY-MM-DD HH:mm:ss'
				}).on("dp.change", (e) => {
					let dateString = e.date.format('YYYY-MM-DD HH:mm:ss');
					this.setting.date = dateString;
				   
				});
				$(this.$refs.select2).select2().on("change", (e) => {
					this.setting.liability_account_id = $(e.target).val();
				   
				});
                
				$('#employees_table').DataTable({
					columns: [
						{ data: 'employee_no' },    
						{ data: 'name' },
						null,
						null,null
						
					]
				});
				
				$('#settings_table').DataTable({
					columns: [
						null,
						null,
						null,
						null,
						null,
						null
					]
				});
				$('#advances_table').DataTable({
					columns: [
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null,
						null
					]
				});
			});
        },
        methods: {
			showSettingsForm(setting){
				if(setting != undefined){
					this.setting = setting;
				}
				this.$nextTick(() => {
                    $('#settingsFormModal').modal('show'); 
                });
			},
			closeSettingsForm(){
				$('#settingsFormModal').modal('hide'); 
			},
			updateDate(event) {
				this.setting.date = event.target.value; 
			},
			showEdit(adv){
				this.advance = adv;
				
				this.$nextTick(() => {
                    $('#editModal').modal('show'); 
                });
			},
			showView(adv){
				this.advance = adv;
				
				this.$nextTick(() => {
                    $('#viewModal').modal('show'); 
                });
			},
			closeEdit(){
				this.advance = {};
				console.log('closeEdit');
				$('#editModal').modal('hide'); 
            },
			closeView(){
				this.advance = {};
				$('#viewModal').modal('hide'); 
            },
			manualUpdateAmount(ind){
				console.log(ind);
			},
			syncAmountPaid(emp) {
			  if (emp.amount_paid !== emp.amount) {
				emp.amount_paid = emp.amount;
			  }
			},
			savePaymentSetting() {
				axios.post('/hrm/advances/save-payment-settings',this.setting).then(res => {
                    if(res.status == 200){
						if(this.setting.id == undefined || this.setting.id == ''){
							this.settings.push(res.data.setting);
						}
						
					if (!$('#create_another').prop('checked')) {
                        // Call the closeSettingsForm() function if the checkbox is not checked
                        this.closeSettingsForm();
                    }

					}
                }).catch(err => {
                    console.log(err);
                });
				
			},
			deletePaymentSetting(index) {
				axios.post('/hrm/advances/remove-payment-settings',{id:this.settings[index].id}).then(res => {
                    if(res.status == 200){
						this.settings.splice(index,1);
					}
                }).catch(err => {
                    console.log(err);
                });
				
			},
			saveAdvancePayments() {
				axios.post('/hrm/advances/save-advance-payments',{advances:this.employees,payment:this.payment}).then(res => {
                    if(res.status == 200){
                        
                        this.clearTable();
                        
                    }
                }).catch(err => {
                    console.log(err);
                });
			},
			saveAdvance() {
				axios.post('/hrm/advances/save-advance',this.advance).then(res => {
                    if(res.status == 200){
                        
                        this.closeEdit();
                        
                    }
                }).catch(err => {
                    console.log(err);
                });
			},
			clearTable(){
				for(let i in this.employees){
					this.employees[i].amount = 0;
					this.employees[i].amount_paid = 0;
				}
			},
			formatNumber(number, decimals = 2) {
			  let [integer, decimal] = number.toFixed(decimals).split('.');

			  integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

			  return `${integer}.${decimal}`;
			},
			showAddSettings(){
				
			}
        }
	});
    
</script>
<style>
	.filter-select{
        background-color: #fff;
        border: 1px solid #aaa;
        border-radius: 4px;
        height: 35px;
        width:100%;
    }
</style>
@endsection
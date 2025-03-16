<?php

namespace Modules\SMS\Http\Controllers;

use App\Member;
use App\Utils\BusinessUtil;
use App\Utils\TransactionUtil;
;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Member\Entities\Balamandalaya;
use Modules\Member\Entities\GramasevaVasama;
use Modules\Superadmin\Entities\Subscription;
use Modules\SMS\Entities\SmsCampaign;
use Modules\SMS\Entities\SmsGroup;
use Yajra\DataTables\Facades\DataTables;
use App\Business;
use App\ContactGroup;
use App\Contact;
use Illuminate\Support\Facades\DB;
use App\SmsLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;



use Maatwebsite\Excel\Facades\Excel;

class SmsSendController extends Controller
{
    protected $businessUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param Util $businessUtil
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, TransactionUtil $transactionUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil =  $transactionUtil;
    }
    
public function smsCampaign() {
    $business_id = request()->session()->get('business.id');
    
    // Fetch sms settings, non-delivery setting, and subscription details in one query
    $business = Business::where('id', $business_id)->select('sms_settings', 'sms_non_delivery')->first();
    $smsSettings = $business->sms_settings;
    $smsNonDelivery = $business->sms_non_delivery;

    // Fetch SMS groups and subscription package details
    $sms_group = SmsGroup::where('business_id', $business_id)
    ->select('id', 'group_name', \DB::raw("JSON_LENGTH(members) as member_count"))
    ->get();

    $subscription = Subscription::where('business_id', $business_id)->select('package_details')->first();
    $package_details = $subscription->package_details;

    // Determine if Customer Group is enabled based on package details
    $isCustomerGroupEnabled = (
        $package_details['contact_module'] == 1 &&
        $package_details['shipping_module'] == 1 &&
        $package_details['airline_module'] == 1
    );

    // Fetch contact groups for dropdown
    $contact_grps = ContactGroup::forDropdown($business_id, false);

    // Return view with compacted variables
    return view('sms::send_sms.sms_campaign')->with(compact(
        'contact_grps', 'isCustomerGroupEnabled', 'sms_group', 'smsNonDelivery', 'smsSettings'
    ));
}

    
    public function smsFromFile(){
        $business_id = request()->session()->get('business.id');
        $smsSettings = Business::where('id', $business_id)->value('sms_settings');
        
        return view('sms::send_sms.sms_from_file')->with(compact('smsSettings'));
    }
    
    public function submitSmsFile(Request $request){
        $business_id = request()->session()->get('business.id');
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            
            $data = array();
            if ($request->hasFile('file')) {
                $file = $request->file('file');

                $parsed_array = Excel::toArray([], $file);
                
                $original_parsed_array = $parsed_array;
                
                
                //Remove header row
                $data['imported_data'] = array_splice($parsed_array[0], 1);
                
                $tags = array();
                foreach($parsed_array[0][0] as $tag){
                    $tags["{".str_replace(' ','',strtolower($tag))."}"] = $tag;
                }
                
                $data['tags'] = $tags;
                
                $data['schedule_campaign'] = $request->schedule_campaign;
                $data['name'] = $request->name;
                $data['send_time'] = $request->send_time;
                
            }
            
            if(empty($data['imported_data'])){
                $output = [
                    'success' => false,
                    'msg' => __('sms::lang.file_is_empty')
                ];
                
                return redirect()->back()->with('status', $output);
            }
            
            
            return view('sms::send_sms.sms_from_file_final')->with(compact('data'));
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
            
            return redirect()->back()->with('status', $output);
        }

        
    }
     private function str_replace_array($search, array $replace, $subject) {
        foreach ($replace as $item) {
            $subject = preg_replace('/' . preg_quote($search, '/') . '/', $item, $subject, 1);
        }
        return $subject;
    }
    
    public function submitsmsCampaign(Request $request) {
        $business_id = request()->session()->get('business.id');
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            DB::beginTransaction();
            
            $input = $request->except('_token');
            
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
    
            $customerGroupId = $input['customer_group_id'] ?? null;
            $smsGroupIds = $input['sms_group'] ?? null;
    
           $sms_group_ids = array_filter(array_map('trim', explode(',', $smsGroupIds)), 'is_numeric');
           $members = [];
           $phone_nos = [];
            
            if (empty($input['send_time']) && $input['frequency'] == 'One Time' && !array_key_exists('dSpeed', $input)) {
                $contactsQuery = Contact::whereRaw('LENGTH(mobile) = 11');
            
                if (!empty($customerGroupId)) {
                    $contactsQuery->where('customer_group_id', $customerGroupId);
                    $contacts = $contactsQuery->select('mobile')->get();
                    $phone_nos = $contacts->pluck('mobile')->toArray();
                }

                if (!empty($sms_group_ids)) {
                    $query = SmsGroup::whereIn('id', $sms_group_ids);
                    $sql = $this->str_replace_array('?', $sms_group_ids, $query->toSql());
                    $smsgroups = $query->get();
 
                    foreach ($smsgroups as $smsGroup) {
                        $groupMembers = json_decode($smsGroup->members, true);

                        if (is_array($groupMembers)) {
                            $members = array_merge($members, $groupMembers);
                        }
                    }
                }

                // Merge contacts with members
                $allPhoneNumbers = array_merge($phone_nos, $members);
            
                // Optionally remove duplicates
                $allPhoneNumbers = array_unique($allPhoneNumbers);
                
                $no_of_sms = $this->transactionUtil->__getNumberOfSms($input['message']);
                $unit_cost = $this->transactionUtil->__businessSMSUnitCost($business_id);
                $date = date('Y-m-d');
                $balance = $this->transactionUtil->__getSMSBalance($date, $business_id, 'business');
                $total_cost = $no_of_sms * $unit_cost * count($allPhoneNumbers);

                if ($total_cost > $balance) {
                    $output = [
                        'success' => false,
                        'msg' => __('sms::lang.insuffucient_balance')
                    ];
                    Log::warning('Insufficient balance for SMS campaign');
                    return redirect()->back()->with('status', $output);
                }
    
                foreach ($allPhoneNumbers as $contact) {
                    $msg = $input['message'];
                    $validatedNumbers = $this->transactionUtil->validateNos($contact); // Pass the number directly
                
                    $correct_phones = $validatedNumbers['valid'];
                    $incorrect_phones = $validatedNumbers['invalid'];
                
                    if (!empty($sms_settings)) {
                        $no_of_sms = $this->transactionUtil->__getNumberOfSms($msg);
                        $unit_cost = $sms_settings['cost_per_sms'] ?? 0; // Assuming you have a way to get unit cost
                        $sms_log_template = [
                            'business_id' => $business_id,
                            'message' => $msg,
                            'no_of_characters' => strlen($msg),
                            'no_of_sms' => $no_of_sms,
                            'sms_type' => $input['name'],
                            'unit_cost' => $unit_cost,
                            'total_cost' => $no_of_sms * $unit_cost,
                            'sms_status' => 'Scheduled',
                            'schedule_time' => $input['send_time'],
                            'business_type' => 'business',
                            'username' => auth()->user()->username,
                            'default_gateway' => $sms_settings['default_gateway'],
                            'uuid' => rand(11111111111, 99999999999),
                            'sender_name' => $sms_settings['hutch_mask']
                        ];
                
                        // Handle incorrect phone numbers
                        if (!empty($incorrect_phones)) {
                            foreach ($incorrect_phones as $ph) {
                                $sms_log = array_merge($sms_log_template, [
                                    'sms_status' => 'Failed',
                                    'recipient' => $ph,
                                ]);
                                SmsLog::create($sms_log);
                                Log::info('SMS log created for incorrect phone', ['phone' => $ph]);
                            }
                        }
                
                        // Handle correct phone numbers
                        if (!empty($correct_phones)) {
                            foreach ($correct_phones as $ph) {
                                $sms_log = array_merge($sms_log_template, [
                                    'sms_status' => 'Delivered',
                                    'recipient' => $ph,
                                    'sms_settings' => $sms_settings,
                                    'mobile_number' => $ph,
                                    'sms_body' => $msg,
                                ]);
                
                                Log::info('Attempting to create SMS log', ['sms_log' => $sms_log]);
                                $this->transactionUtil->sendSms($sms_log, $input['name']);
                            }
                
                            // Send the SMS in bulk for correct phones
                            $data = [
                                'sms_settings' => $sms_settings,
                                'mobile_number' => implode(',', $correct_phones),
                                'sms_body' => $msg
                            ];
                            // $this->transactionUtil->sendSms($data, 'Campaign');
                        }
                    }
                }

    
            } else {
                $data = [
                    'business_id' => $business_id,
                    'frequency' => $input['frequency'] ?? 1,
                    'name' => $input['name'],
                    'message' => $input['message'],
                    'customer_group' => $input['customer_group'] ?? null,
                    'sms_group' => $smsGroupIds,
                    'next_date' => $input['send_time'],
                    'end_date' => $input['end_time']
                ];
    
                SmsCampaign::create($data);
                Log::info('SMS campaign created for non-one-time frequency');
            }
    
            DB::commit();
            $output = [
                'success' => true,
                'msg' =>  __('lang_v1.msg_sent_successfully')
            ];
        } catch (\Exception $e) {
            // Rollback any database changes
            DB::rollback();
        
            // Log the error with detailed context
            Log::emergency('An error occurred during the process.', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(), // Optional: adds full stack trace for deeper debugging
            ]);
        
            // Prepare output message for the response
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

    
        return redirect()->back()->with('status', $output);
    }
    
        
    public function executeCampaign(){
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            DB::beginTransaction();
            
            $campaigns = SmsCampaign::where('next_date','<=',date('Y-m-d H:i'))->get();
            
            foreach($campaigns as $ca){
                $business_id = $ca->business_id; 
                
                $business = Business::where('id', $business_id)->first();
                $sms_settings = $business->sms_settings;
                
                $contacts = Contact::where('customer_group_id',$ca->customer_group)->whereRaw('LENGTH(mobile) = 11')->select('name','mobile','address','email')->get();
                $phone_nos = Contact::where('customer_group_id',$ca->customer_group)->whereRaw('LENGTH(mobile) = 11')->pluck('mobile')->toArray();
                
                $no_of_sms = $this->transactionUtil->__getNumberOfSms($ca->message);
                $unit_cost = $this->transactionUtil->__businessSMSUnitCost($business_id);
                
                $date = date('Y-m-d');
                $balance = $this->transactionUtil->__getSMSBalance($date, $business_id, 'business');
                $total_cost = $no_of_sms * $unit_cost * sizeof($phone_nos); 
                
                if($total_cost < $balance){
                    foreach($contacts as $contact){
                        $msg = $ca->message;
                        $msg = str_replace('{name}',$contact->name,$msg);
                        $msg = str_replace('{phone}',$contact->mobile,$msg);
                        $msg = str_replace('{email}',$contact->email,$msg);
                        $msg = str_replace('{address}',$contact->address,$msg);
                        
                        $correct_phones = $this->transactionUtil->validateNos($contact->mobile)['valid'];
                        $incorrect_phones = $this->transactionUtil->validateNos($contact->mobile)['invalid'];
                        
                        if(!empty($sms_settings)){
                            $no_of_sms = $this->transactionUtil->__getNumberOfSms($msg);
                            $sms_log = array(
                                'business_id' => $business_id,
                                'message' => $msg,
                                'no_of_characters' => strlen($msg),
                                'no_of_sms' => $no_of_sms,
                                'sms_type' => $ca->name,
                                'unit_cost' => $unit_cost,
                                'sms_type_' => $this->transactionUtil->__smsType($msg),
                                'total_cost' => $no_of_sms * $unit_cost * 1,
                                'sms_status' => 'Scheduled',
                                'schedule_time' => $ca->next_date,
                                'business_type' => 'business',
                                'username' => null,
                                'default_gateway' => $sms_settings['default_gateway'],
                                'uuid' => rand(11111111111,99999999999),
                                'sender_name' => $sms_settings['ultimate_sender_id']
                            );
                    
                            if(!empty($incorrect_phones)){
                                foreach($incorrect_phones as $ph){
                                    $sms_log['sms_status'] = 'Delivered';
                                    $sms_log['recipient'] = $ph;
                                    
                                }
                            }
                            
                            if(!empty($correct_phones)){
                                foreach($correct_phones as $ph){
                                    $sms_log['recipient'] = $ph;
                                    
                                }
                            }
                            
                            
                            SmsLog::create($sms_log);
                            
                            
                        }
                    }
                    
                    if(!empty($sms_settings)){
                        if($ca->frequency == 'One Time'){
                            $ca->delete();
                        }else{
                            $nextTime = Carbon::parse($ca->next_date);
                            
                            if ($ca->frequency == 'daily') {
                                $ca->next_date = $nextTime->addDay();
                            } elseif ($ca->frequency == 'monthly') {
                                $ca->next_date = $nextTime->addMonth();
                            } elseif ($ca->frequency == 'yearly') {
                                $ca->next_date = $nextTime->addYear();
                            }
                            
                            $ca->save();
                        }
                    }
                }
                
                
            }
            
            DB::commit();
        
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    
    public function sendMessages(){
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            DB::beginTransaction();
            
            $campaigns = SmsLog::where('sms_status','Scheduled')->where(function ($query){
                return $query->where('schedule_time','<=',date('Y-m-d H:i'))->orWhere('schedule_time');
            })->get();
            
            
            foreach($campaigns as $ca){
                $business_id = $ca->business_id; 
                
                $business = Business::where('id', $business_id)->first();
                $sms_settings = $business->sms_settings;
                
                if(!empty($sms_settings)){
                    $data = [
                        'sms_settings' => $sms_settings,
                        'mobile_number' => $ca->recipient,
                        'sms_body' => $ca->message
                    ];
                    
                    
                    $this->transactionUtil->superadminTransactionalSms($data);
                    
                    $ca->sms_status = 'Delivered';
                    $ca->save();
                }
                
                
            }
            
            DB::commit();
        
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
    

    
    public function quickSend(){
        $business_id = request()->session()->get('business.id');
        $smsSettings = Business::where('id', $business_id)->value('sms_settings');
        return view('sms::send_sms.quick_send')->with(compact('smsSettings'));
    }
    
    public function submitQuickSend(Request $request){
        $business_id = request()->session()->get('business.id');
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            
            DB::beginTransaction();
            $input = $request->except('_token');
            
            $no_of_sms = $this->transactionUtil->__getNumberOfSms($input['message']);
            $unit_cost = $this->transactionUtil->__businessSMSUnitCost($business_id);
            
            $date = date('Y-m-d');
            $balance = $this->transactionUtil->__getSMSBalance($date, $business_id, 'business');
            $total_cost = $no_of_sms * $unit_cost * sizeof(explode(',',$input['phone_nos'])); 
            
            if($total_cost > $balance){
                
                $output = [
                    'success' => false,
                    'msg' => __('sms::lang.insuffucient_balance')
                ];
                
                 return redirect()->back()->with('status', $output);
            }
            
            $correct_phones = $this->transactionUtil->validateNos($input['phone_nos'])['valid'];
            $incorrect_phones = $this->transactionUtil->validateNos($input['phone_nos'])['invalid'];
            
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            
            if(!empty($sms_settings)){
                
                if(!empty($incorrect_phones)){
                    foreach($incorrect_phones as $ph){
                        $sms_log = array(
                            'business_id' => $business_id,
                            'message' => $input['message'],
                            'no_of_characters' => strlen($input['message']),
                            'no_of_sms' => $no_of_sms,
                            'sms_type' => 'Quick Send',
                            'unit_cost' => $unit_cost,
                            'sms_type_' => $this->transactionUtil->__smsType($input['message']),
                            'total_cost' => $no_of_sms * $unit_cost * 1,
                            'sms_status' => 'Delivered',
                            'business_type' => 'business',
                            'username' => auth()->user()->username,
                            'default_gateway' => $sms_settings['default_gateway']
                        );
                        
                        $sms_log['recipient'] = $ph;
                        $sms_log['uuid'] = rand(11111111111,99999999999);
                        $sms_log['sender_name'] = $sms_settings['ultimate_sender_id'];
                       
                        SmsLog::create($sms_log);
                    }
                }
                
                if(!empty(!empty($correct_phones))){
                    $data = [
                    'sms_settings' => $sms_settings,
                        'mobile_number' => implode(',',$correct_phones),
                        'sms_body' => $input['message']
                    ];
                    
                    $this->transactionUtil->sendSms($data,'Quick Send');
                }
                
            }
            
            
            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }
    
    public function submitSmsFileFinal(Request $request){
        $business_id = request()->session()->get('business.id');
        try {
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            
            DB::beginTransaction();
            $input = $request->except('_token');
            $input['data'] = json_decode($input['data'],true);
            
            $no_of_sms = $this->transactionUtil->__getNumberOfSms($input['message']);
            $unit_cost = $this->transactionUtil->__businessSMSUnitCost($business_id);
            
            $date = date('Y-m-d');
            $balance = $this->transactionUtil->__getSMSBalance($date, $business_id, 'business');
            $total_cost = $no_of_sms * $unit_cost * sizeof($input['data']['imported_data']); 
            
            if($total_cost > $balance){
                
                $output = [
                    'success' => false,
                    'msg' => __('sms::lang.insuffucient_balance')
                ];
                
                 return redirect('/smsmodule/sms-from-file')->with('status', $output);
            }
            
            
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            
            if(!empty($sms_settings)){
                
                foreach($input['data']['imported_data'] as $ca){
                    $msg = $input['message'];
                    
                    $correct_phones = $this->transactionUtil->validateNos($ca[0])['valid'];
                    $incorrect_phones = $this->transactionUtil->validateNos($ca[0])['invalid'];
                    
                    $i=0;
                    foreach($input['data']['tags'] as $key => $tag){
                        $msg = str_replace($key,$ca[$i],$msg);
                        $i++;
                    }
                    
                    $no_of_sms = $this->transactionUtil->__getNumberOfSms($msg);
                    $sms_log = array(
                        'business_id' => $business_id,
                        'message' => $msg,
                        'no_of_characters' => strlen($msg),
                        'no_of_sms' => $no_of_sms,
                        'sms_type' => $input['data']['name'],
                        'unit_cost' => $unit_cost,
                        'sms_type_' => $this->transactionUtil->__smsType($msg),
                        'total_cost' => $no_of_sms * $unit_cost * 1,
                        'sms_status' => 'Scheduled',
                        'schedule_time' => $input['data']['send_time'],
                        'business_type' => 'business',
                        'username' => auth()->user()->username,
                        'default_gateway' => $sms_settings['default_gateway'],
                        'uuid' => rand(11111111111,99999999999),
                        'sender_name' => $sms_settings['ultimate_sender_id']
                    );
            
                    if(!empty($incorrect_phones)){
                        foreach($incorrect_phones as $ph){
                            $sms_log['sms_status'] = 'Delivered';
                            $sms_log['recipient'] = $ph;
                            
                        }
                    }
                    
                    if(!empty($correct_phones)){
                        foreach($correct_phones as $ph){
                            $sms_log['recipient'] = $ph;
                            
                        }
                    }
                    
                    SmsLog::create($sms_log);
                
                }
            }
            
            
            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect('/smsmodule/sms-from-file')->with('status', $output);
    }

    
}

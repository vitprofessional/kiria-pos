<?php



namespace Modules\Essentials\Http\Controllers;



use App\AccountTransaction;

use App\AccountGroup;
use App\Account;

use App\Business;
use App\BusinessLocation;

use App\Category;

use App\Events\TransactionPaymentAdded;

use App\Transaction;

use App\TransactionPayment;

use App\User;

use App\Utils\BusinessUtil;

use App\Utils\ModuleUtil;

use App\Utils\TransactionUtil;

use App\Utils\Util;

use DB;

use Illuminate\Http\Request;

use Illuminate\Http\Response;

use Illuminate\Routing\Controller;

use Illuminate\Support\Facades\View;

use Modules\Essentials\Entities\EssentialsAllowanceAndDeduction;

use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsEmployeeAdvance;
use Modules\Essentials\Entities\EssentialsEmployeePaymentSetting;
use Modules\Essentials\Entities\EssentialsUserSalesTarget;

use Modules\Essentials\Entities\PayrollGroup;

use Modules\Essentials\Notifications\PayrollNotification;

use Modules\Essentials\Utils\EssentialsUtil;

use Yajra\DataTables\Facades\DataTables;

use Modules\Essentials\Entities\EssentialsEmployee;
use Illuminate\Support\Facades\Auth;


class AdvancesController extends Controller

{

    /**

     * All Utils instance.

     */

    protected $moduleUtil;



    protected $essentialsUtil;



    protected $commonUtil;



    protected $transactionUtil;



    protected $businessUtil;



    /**

     * Constructor

     *

     * @param  ProductUtils  $product

     * @return void

     */

    public function __construct(ModuleUtil $moduleUtil, EssentialsUtil $essentialsUtil, Util $commonUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)

    {

        $this->moduleUtil = $moduleUtil;

        $this->essentialsUtil = $essentialsUtil;

        $this->commonUtil = $commonUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

    }



    /**

     * Display a listing of the resource.

     *

     * @return Response

     */

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();
		$employees = EssentialsEmployee::where('business_id', $business_id)
			->select('id','name','employee_no')
			->get();
			
			
			
		$advances = EssentialsEmployeeAdvance::join('essentials_employees', 'essentials_employee_advances.employee_id', '=', 'essentials_employees.id')
			->where('essentials_employees.business_id', $business_id)
			->select(
				'essentials_employee_advances.id',
				'essentials_employees.name',
				'essentials_employees.employee_no',
				'essentials_employee_advances.amount',
				'essentials_employee_advances.datetime_entered',
				'essentials_employee_advances.payment_type_id',
				'essentials_employee_advances.salary_period_start',
				'essentials_employee_advances.salary_period_end',
				'essentials_employee_advances.amount_paid'
			)
			->get();
		
		$settings = EssentialsEmployeePaymentSetting::with('user')->where('status',1)
			->get();
		
		foreach($employees as $employee){
			$employee->amount = 0;
			$employee->amount_paid = 0;
		}
		$today = date('m/d/Y');
        $startdate = date('Y-m-01');
        $enddate = date('Y-m-t');
		
		$assettypes = [28,33,34]; // assets
		$liabilitytypes = [29,35,36]; // liabilities

		$rawaccounts = Account::whereIn('account_type_id',array_merge($assettypes, $liabilitytypes))->where('business_id',$business_id)->get();
		$accounts = [
			0 => [], // Liabilities
			1 => []  // Assets
		];
		foreach($rawaccounts as $account){
			if(in_array($account->account_type_id,$liabilitytypes)){
				$accounts[0][] = $account->toArray();
			}else{
				$accounts[1][] = $account->toArray();
			}
		}
		
		
		$accounts_with_check = [];
		foreach($accounts[1] as $account){
			
			if (strpos($account['name'], 'Bank') !== false || strpos($account['name'], 'Cheque') !== false || strpos($account['name'], 'Check') !== false) {
				$accounts_with_check[] = $account['id'];
			}

		}
		// dd($advances);
		
        return view('essentials::advances.index')->with(compact('employees','advances','settings','business','today','startdate','enddate','accounts','accounts_with_check'));

    }
	
	public function removePaymentSettings(Request $request){
		
		$result = true;
		$message = "";
		$user = Auth::user();
		
		$business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
		
		$data = $request->all();
		
		$setting = null;
		if($is_admin){
			$id = $request->input('id');
			$setting = EssentialsEmployeePaymentSetting::find($id);
			if($setting){
				$setting->status = 0;
			}
			
			if($setting->save()){
				
			}else{
				$result = false;
			}
		}
		return compact('result','message');
	}
	public function savePaymentSettings(Request $request){
		
		$result = true;
		$message = "";
		$user = Auth::user();
		
		$business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
		
		$data = $request->all();
		
		$setting = null;
		if($is_admin){
			if(isset($data['id']) && strlen($data['id'])){
				$setting = EssentialsEmployeePaymentSetting::find($data['id']);
			}else{
				$setting = new EssentialsEmployeePaymentSetting();
			}
			$setting->liability_account_id = $data['liability_account_id'];
			$setting->payment_type = $data['payment_type'];
			if(isset($data['remarks'])){
				$setting->remarks = $data['remarks'];
			}
			if(isset($data['date'])){
				$setting->datetime_entered = date('Y-m-d H:i:s',strtotime($data['date']));
			}else{
				$setting->datetime_entered = date('Y-m-d H:i:s');
			}
			$setting->user_id = $user->id;
			if($setting->save()){
				
			}else{
				$result = false;
				$message = "Unable to save setting. Please try again.";
			}
		}
		
		$setting = EssentialsEmployeePaymentSetting::with('user')->find($setting->id);
		
		return compact('setting','result','message');
	}
	
	public function saveAdvance(Request $request){
		$result = true;
		$message = "";
		
		$business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
		
		$data = $request->all();
		
		if(isset($data['id']) && strlen($data['id'])){
			
			$advance = EssentialsEmployeeAdvance::find($data['id']);
			
			$advance->amount = $data['amount'];
			$advance->amount_paid = $data['amount_paid'];
			$advance->remarks = $data['remarks'];
			$advance->status = $data['paid'] == 'yes'?1:0;
			if($advance->save()){
				
			}else{
				$result = false;
				$message = "Unable to save Employee Advance.";
			}
			
			
		}
		
		return compact('result','message');
	}
	
	public function saveAdvancePayments(Request $request){
	    
		$result = true;
		$message = "";
		
		$business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
		$data = [];
		if($is_admin){
			$data = $request->all();
			$total = 0;
			if(isset($data['advances'])){
				foreach($data['advances'] as $advance){
					if($advance['amount'] > 0){
						
						$input = [
							'employee_id' => $advance['id'],
							'amount' => $advance['amount'],
							'amount_paid' => $advance['amount_paid'],
							'payment_type_id' => isset($data['payment']['payment_type_id'])?$data['payment']['payment_type_id']:null,
							'payment_status' => EssentialsEmployeeAdvance::PAYMENT_STATUS_NEW,
						];
						
						$total += $advance['amount'];
						if(isset($data['payment']['check'])){
							$input['check_no'] = $data['payment']['check'];
						}
						if(isset($data['payment']['account'])){
							// todo: need to confirm what should happen here. perform debit/credit on accounts
							//$input['account'] = $data['payment']['account'];
							
						}
						if(isset($data['payment']['date'])){
							$input['datetime_entered'] = date('Y-m-d',strtotime($data['payment']['date']));
						}
						if(isset($data['payment']['period'])){
							$periods = explode(" to ",$data['payment']['period']);
							if(count($periods) == 2){
								$input['salary_period_start'] = $periods[0];
								$input['salary_period_end'] = $periods[1];
							}
						}
						
						EssentialsEmployeeAdvance::create($input);
					}
				}
				
				$debitaccountids = [$data['payment']['account']];
				
				$wagesaccountno = 404;
				
				$wagesaccount = Account::where('business_id',$business_id)->where('account_number',$wagesaccountno)->first();
				if($wagesaccount){
					$debitaccountids[] = $wagesaccount->id;
				}
				
				foreach($debitaccountids as $debitaccountid){
				    
				    if(isset($debitaccountid)){
				        
				        	$accountTransaction = new AccountTransaction();
        					$accountTransaction->account_id = $debitaccountid;
        					
        					$accountTransaction->amount = $total;
        					$accountTransaction->type = 'debit';
        					$accountTransaction->journal_deleted = 0;
        					$accountTransaction->reconcile_status = 0;
        					$accountTransaction->postdated_transafer_status = 0;
        					$accountTransaction->type = 'debit';
        					$accountTransaction->operation_date = date('Y-m-d H:i:s');
        					$accountTransaction->created_by = Auth::user()->id;
        					$accountTransaction->business_id = $business_id;
        					if(!$accountTransaction->save()){
        						$result = false;
        						$message = "Unable to save account transaction.";
        						break;
        					}
				        
				    }
				    // else{
				        
				    //     	$result = false;
        // 						$message = "Unable to save account transaction.";
        // 						break;
				        
				    // }
					
				
				}
				
			}
		}
		
		return compact('result','message','is_admin','business_id','data');
	}

}


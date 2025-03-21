<?php

namespace Modules\Spreadsheet\Http\Controllers;

use App\Category;
use App\User;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Spreadsheet\Entities\Spreadsheet;
use Modules\Spreadsheet\Entities\SpreadsheetShare;
use Modules\Spreadsheet\Notifications\SpreadsheetShared;
use Notification;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class SpreadsheetController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param CommonUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        // if ((($this->moduleUtil->hasThePermissionInSubscription($business_id, 'spreadsheet_module') && auth()->user()->can('access.spreadsheet')))) {
        //     abort(403, 'Unauthorized action.');
        // }

        
        if (request()->ajax()) {
            
            $user_id = request()->session()->get('user.id');
            $role_id = User::find($user_id)->roles()->first()->id;
    
            $module_data = $this->moduleUtil->getModuleData('getAssignedTaskForUser', $user_id);
            if (! empty($module_data['Essentials'])) {
                $todo_ids = $module_data['Essentials'];
            }
            
    
            $query = Spreadsheet::with(['createdBy', 'shares'])
                        ->leftJoin('categories','sheet_spreadsheets.folder_id','=','categories.id')
                        ->where('sheet_spreadsheets.business_id', $business_id)
                        ->leftJoin('sheet_spreadsheet_shares as SSS', 'sheet_spreadsheets.id', '=', 'SSS.sheet_spreadsheet_id');
    
            if (! auth()->user()->can('superadmin')) {
                $query->where('sheet_spreadsheets.created_by', auth()->user()->id)
                        ->orWhere(function ($query) use ($role_id) {
                            $query->where('SSS.shared_id', '=', $role_id)
                                ->where('SSS.shared_with', '=', 'role');
                        })
                        ->orwhere(function ($query) use ($user_id) {
                            $query->where('SSS.shared_id', '=', $user_id)
                                ->where('SSS.shared_with', '=', 'user');
                        });
    
                if (! empty($todo_ids)) {
                    $query->orwhere(function ($query) use ($todo_ids) {
                        $query->whereIn('SSS.shared_id', $todo_ids)
                                ->where('SSS.shared_with', '=', 'todo');
                    });
                }
            }
    
            $spreadsheets = $query->select('sheet_spreadsheets.id','sheet_spreadsheets.status', 'sheet_spreadsheets.name as sname','categories.name as fname', 'sheet_spreadsheets.updated_at',
                                'sheet_spreadsheets.created_by', 'folder_id','sheet_spreadsheets.last_updated_by','sheet_spreadsheets.last_opened_on')
                            ->orderByDesc('sheet_spreadsheets.updated_at')
                            ->groupBy('sheet_spreadsheets.id')
                            ->get();
    
            $todos = [];
            if (! empty($module_data['Essentials'])) {
                $todos = $module_data['Essentials'];
            }
    
            $users = User::forDropdown($business_id, false);
            $roles = $this->moduleUtil->getDropdownForRoles($business_id);
    
            foreach ($spreadsheets as $key => $value) {
                if (count($value->shares) > 0) {
                    $shered_users = [];
                    $shared_roles = [];
                    $shared_todos = [];
    
                    foreach ($value->shares as $share) {
                        if ($share->shared_with == 'user') {
                            $shered_users[] = $users[$share->shared_id] ?? '';
                        } elseif ($share->shared_with == 'role') {
                            $shared_roles[] = $roles[$share->shared_id] ?? '';
                        } elseif ($share->shared_with == 'role') {
                            $shared_todos[] = $todos[$share->shared_id] ?? '';
                        }
                    }
    
                    $spreadsheets[$key]->shered_users = $shered_users;
                    $spreadsheets[$key]->shared_roles = $shared_roles;
                    $spreadsheets[$key]->shared_todos = $shared_todos;
                }
            }
            
            
            return DataTables::of($spreadsheets)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '';
                        if(auth()->user()->can('access.spreadsheet')){
                        $html .= '<a class="btn btn-xs btn-default text-success view-sheet" 
															title="'.__('messages.view').'"
															href="'.action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'show'], [$row->id]).'" target="_blank">
															<i class="fas fa-eye text-primary"></i></a>';
                        }
															
															
															
					    if(auth()->user()->id == $row->created_by){
					        if(auth()->user()->can('delete.spreadsheet')){
    							$html .= '<a 
    								class="btn btn-modal btn-xs btn-default delete-sheet" title="'.__('messages.delete').'"
    								data-href="'.action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'destroy'], [$row->id]).'"
    								href="'.action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'destroy'], [$row->id]).'">
    								<i class="fas fa-trash-alt text-danger"></i>
    							</a>';
					        }
					        
					        
							$html .= '<a 
								data-href="'.action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'getShareSpreadsheet'], ['id' => $row->id]).'" 
								title="'.__('spreadsheet::lang.share').'" 
								class="btn btn-default btn-xs share_excel">
								<i class="fa fa-share text-success"></i>
							</a>';
                            
                            if(auth()->user()->can('edit.spreadsheet')){
    							$html .= '<a 
    								href="#" 
    								title="'.__('spreadsheet::lang.move_to_another_folder').'" 
    								class="btn btn-default btn-xs move_to_another_folder"
    								data-spreadsheet_id="'.$row->id.'">
    								<i class="fas fa-external-link-alt text-warning"></i>
    							</a>';
							
                            }
							
							if(auth()->user()->can('toggle.spreadsheet')){
    							if($row->status == 0){
        							    $html .= '<a 
        								class="btn btn-modal btn-xs btn-default enable-disable" title="Enable"
        								data-href="'.action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'enableStatus'], [$row->id]).'"
        								>
        								<i class="fas fa-toggle-on text-success"></i>
        							</a>';
    							
    							}else{
        							    $html .= '<a 
        								class="btn btn-modal btn-xs btn-default enable-disable" title="Disable"
        								data-href="'.action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'disableStatus'], [$row->id]).'"
        								>
        								<i class="fas fa-toggle-off text-danger"></i>
        								</a>';
    							   
    							}
							}
					    }
                        
                        return $html;
                    }
                )
                ->addColumn(
                    'shared_with',
                    function ($row) {
                        $html =  '';
                        if(auth()->user()->id == $row->created_by){
                            $html .= '<a 
								data-href="'.action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'showShared'], ['id' => $row->id]).'" 
								title="'.__('spreadsheet::lang.share').'d" 
								class="btn btn-default btn-xs share_excel">
								<i class="fa fa-eye text-success"></i> view
							</a>';
                        }
                        
                        
    					return $html;
                    }
                )
                
                ->editColumn(
                    'status',
                    function ($row) {
                        $html = "";
                        if($row->status == 0){
                            $html.= "<span class='badge bg-danger'>disabled</span>";
                        }else{
                            $html.= "<span class='badge bg-primary'>enabled</span>";
                        }
                        return $html;
                    }
                )
                ->editColumn(
                    'fname',
                    function ($row) {
                        $html = "<span>$row->fname</span>&nbsp;";
                        
                        if(auth()->user()->can('edit.folder')){
                            $html .= '<a class="btn btn-xs btn-default edit_folder" 
												title="'.__('spreadsheet::lang.edit_folder').'"
												href="#" data-name="'.$row->fname.'" data-id="'.$row->folder_id.'">
												<i class="fas fa-edit text-primary"></i></a>';
                        }
                        
                        return $html;
                    }
                )
                ->addColumn(
                    'last_updated',
                    function ($row) {
                        $html = '';
                        $user = User::where('id',$row->last_updated_by)->select('username')->first();
                        if(!empty($user)){
                            $html .= "<span><b>".$user->username."</b> (".$this->moduleUtil->format_date($row->updated_at,true).")";
                        }
                        return $html;
                    }
                )
                ->editColumn('last_opened_on', '{{@format_datetime($last_opened_on)}}')
                ->editColumn('created_by', '{{$created_by["username"]}}')
                ->removeColumn('id')
                ->rawColumns(['action','status','last_updated','shared_with','fname'])
                ->make(true);
                
        }
        
        

        $folders = Category::where('business_id', $business_id)
                        ->where('category_type', 'spreadsheet')
                        ->where('created_by',auth()->user()->id)
                        ->orderBy('name', 'asc')
                        ->get();

        return view('spreadsheet::sheet.index')
            ->with(compact('folders'));
    }
    
    public function showShared($id){
        $shares = DB::table('sheet_spreadsheet_shares')
                    ->leftJoin('users', function ($join) {
                        $join->on('sheet_spreadsheet_shares.shared_with', '=', DB::raw("'user'"))
                             ->on('users.id', '=', 'sheet_spreadsheet_shares.shared_id');
                    })
                    ->leftJoin('roles', function ($join) {
                        $join->on('sheet_spreadsheet_shares.shared_with', '=', DB::raw("'role'"))
                             ->on('roles.id', '=', 'sheet_spreadsheet_shares.shared_id');
                    })
                    ->where('sheet_spreadsheet_shares.sheet_spreadsheet_id',$id)
                    ->select(
                        'sheet_spreadsheet_shares.*',
                        DB::raw("IF(sheet_spreadsheet_shares.shared_with = 'user', users.username, roles.name) AS shareName")
                    )->get();
        return view('spreadsheet::sheet.partials.shared_with')
            ->with(compact('shares'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        // if (! (($this->moduleUtil->hasThePermissionInSubscription($business_id, 'spreadsheet_module') && auth()->user()->can('create.spreadsheet')))) {
        //     abort(403, 'Unauthorized action.');
        // }
        $folder_id = request()->input('folder_id', null);
        $file_name = request()->input('filename', null);

        return view('spreadsheet::sheet.create')->with(compact('folder_id','file_name'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        // if (! (($this->moduleUtil->hasThePermissionInSubscription($business_id, 'spreadsheet_module') && auth()->user()->can('create.spreadsheet')))) {
        //     abort(403, 'Unauthorized action.');
        // }

        try {
            $input['name'] = ! empty($request->input('name')) ? $request->input('name') : 'My Spreasheet';
            $input['sheet_data'] = $request->input('sheet_data');
            $input['business_id'] = $business_id;
            $input['created_by'] = auth()->user()->id;
            $input['folder_id'] = $request->input('folder_id');
            DB::beginTransaction();

            Spreadsheet::create($input);

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (Exception $e) {
            DB::rollBack();

            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()
            ->action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');

        // if (! (auth()->user()->can('create.spreadsheet') || auth()->user()->can('access.spreadsheet'))) {
        //     abort(403, 'Unauthorized action.');
        // }

        $user_id = request()->session()->get('user.id');
        $role_id = User::find($user_id)->roles()->first()->id;

        $module_data = $this->moduleUtil->getModuleData('getAssignedTaskForUser', $user_id);
        if (! empty($module_data['Essentials'])) {
            $todo_ids = $module_data['Essentials'];
        }

        $query = Spreadsheet::where('business_id', $business_id)
                    ->where('sheet_spreadsheets.id', $id)
                    ->leftJoin('sheet_spreadsheet_shares as SSS', function($join) use($id){
                        $join->on('sheet_spreadsheets.id', '=', 'SSS.sheet_spreadsheet_id')
                            ->where('SSS.sheet_spreadsheet_id', '=', $id);
                    });
       $viewedOn =  Spreadsheet::where('id', $id)
                    ->update([
                        'last_opened_on' => date('Y-m-d H:i:s')
                    ]);

        if (! auth()->user()->can('superadmin')) {
            $query->where('created_by', auth()->user()->id)
                    ->orWhere(function ($query) use ($role_id) {
                        $query->where('SSS.shared_id', '=', $role_id)
                            ->where('SSS.shared_with', '=', 'role');
                    })
                    ->orwhere(function ($query) use ($user_id) {
                        $query->where('SSS.shared_id', '=', $user_id)
                            ->where('SSS.shared_with', '=', 'user');
                    });

            if (! empty($todo_ids)) {
                $query->orwhere(function ($query) use ($todo_ids) {
                    $query->whereIn('SSS.shared_id', $todo_ids)
                            ->where('SSS.shared_with', '=', 'todo');
                });
            }
        }

        $spreadsheet = $query
                        ->select('sheet_spreadsheets.business_id', 'sheet_spreadsheets.id', 'sheet_spreadsheets.name', 'sheet_spreadsheets.sheet_data', 'sheet_spreadsheets.created_by')
                        ->firstOrFail();
                        
        return view('spreadsheet::sheet.show')
            ->with(compact('spreadsheet'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $viewedOn =  Spreadsheet::where('id', $id)
                    ->update([
                        'last_opened_on' => date('Y-m-d H:i:s')
                    ]);
        return view('spreadsheet::edit');
    }
    
    public function enableStatus($id){
        
        $user = Spreadsheet::find($id); // get the user by ID
        $user->status =  1; 
        $updated = $user->save(); 

        $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
            
        return $output;
    }
    
    public function disableStatus($id){
        
        $user = Spreadsheet::find($id); // get the user by ID
        $user->status = 0; 
        $updated = $user->save(); 

        $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
            
        return $output;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        // if (! (auth()->user()->can('create.spreadsheet'))) {
        //     abort(403, 'Unauthorized action.');
        // }

        try {
            $input['name'] = ! empty($request->input('name')) ? $request->input('name') : 'My Spreasheet';
            $input['sheet_data'] = json_encode($request->input('sheet_data'));

            $user_id = request()->session()->get('user.id');
            $role_id = User::find($user_id)->roles()->first()->id;

            $module_data = $this->moduleUtil->getModuleData('getAssignedTaskForUser', $user_id);
            if (! empty($module_data['Essentials'])) {
                $todo_ids = $module_data['Essentials'];
            }

            DB::beginTransaction();
            $query = Spreadsheet::where('business_id', $business_id)
                        ->where('sheet_spreadsheets.id', $id)
                        ->leftJoin('sheet_spreadsheet_shares as SSS', 'sheet_spreadsheets.id', '=', 'SSS.sheet_spreadsheet_id');

            if (! auth()->user()->can('superadmin')) {
                $query->where('created_by', auth()->user()->id)
                            ->orWhere(function ($query) use ($role_id) {
                                $query->where('SSS.shared_id', '=', $role_id)
                                    ->where('SSS.shared_with', '=', 'role');
                            })
                            ->orwhere(function ($query) use ($user_id) {
                                $query->where('SSS.shared_id', '=', $user_id)
                                    ->where('SSS.shared_with', '=', 'user');
                            });

                if (! empty($todo_ids)) {
                    $query->orwhere(function ($query) use ($todo_ids) {
                        $query->whereIn('SSS.shared_id', $todo_ids)
                                    ->where('SSS.shared_with', '=', 'todo');
                    });
                }
            }

            $spreadsheet = $query
                                ->select('sheet_spreadsheets.business_id', 'sheet_spreadsheets.id', 'sheet_spreadsheets.name', 'sheet_spreadsheets.sheet_data')
                                ->firstOrFail();

            if (! empty($spreadsheet)) {
                Spreadsheet::where('business_id', $business_id)
                        ->where('id', $id)
                        ->update($input);
            }

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (Exception $e) {
            DB::rollBack();

            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()
            ->action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'show'], [$id])
            ->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        // if (! (auth()->user()->can('create.spreadsheet'))) {
        //     abort(403, 'Unauthorized action.');
        // }

        if (request()->ajax()) {
            try {
                DB::beginTransaction();

                Spreadsheet::where('business_id', $business_id)
                    ->where('created_by', auth()->user()->id)
                    ->where('id', $id)
                    ->delete();

                DB::commit();
                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (Exception $e) {
                DB::rollBack();

                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }
        }

        return $output;
    }

    public function getShareSpreadsheet(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');

        // if (! (($this->moduleUtil->hasThePermissionInSubscription($business_id, 'spreadsheet_module')))) {
        //     abort(403, 'Unauthorized action.');
        // }

        if ($request->ajax()) {
            $module_data = $this->moduleUtil->getModuleData('getTodosDropdown', $business_id);

            $todos = [];
            if (! empty($module_data['Essentials'])) {
                $todos = $module_data['Essentials'];
            }

            $users = User::forDropdown($business_id, false);
            $roles = $this->moduleUtil->getDropdownForRoles($business_id);

            $shared_spreadsheets = SpreadsheetShare::where('sheet_spreadsheet_id', $id)
                                    ->get()
                                    ->groupBy('shared_with');

            $shared_todos = [];
            $shared_roles = [];
            $shared_users = [];
            if (! empty($shared_spreadsheets['todo'])) {
                $shared_todos = $shared_spreadsheets['todo']->pluck('shared_id')->toArray();
            }

            if (! empty($shared_spreadsheets['role'])) {
                $shared_roles = $shared_spreadsheets['role']->pluck('shared_id')->toArray();
            }

            if (! empty($shared_spreadsheets['user'])) {
                $shared_users = $shared_spreadsheets['user']->pluck('shared_id')->toArray();
            }

            return view('spreadsheet::sheet.partials.share_sheet')
                ->with(compact('todos', 'id', 'shared_roles', 'roles', 'users', 'shared_todos', 'shared_users'));
        }
    }

    public function postShareSpreadsheet(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        // if (! (($this->moduleUtil->hasThePermissionInSubscription($business_id, 'spreadsheet_module')))) {
        //     abort(403, 'Unauthorized action.');
        // }

        if ($request->ajax()) {
            try {
                $sheet_id = $request->input('sheet_id');
                $shares = $request->input('share');
                DB::beginTransaction();

                //Update shared spreadsheets based on selected data
                    $notify_to_shared_ids = []; //send notification to users
                    if (! empty($shares)) {
                        foreach ($shares as $shared_with => $shared_ids) {
                            $existing_shared_ids = [];
                            foreach ($shared_ids as $id) {
                                $existing_shared_ids[] = $id;
                                $data = [
                                    'sheet_spreadsheet_id' => $sheet_id,
                                    'shared_with' => $shared_with,
                                    'shared_id' => $id,
                                ];

                                $sheet = SpreadsheetShare::firstOrCreate($data, $data);

                                if ($sheet->shared_with == 'user' && $sheet->wasRecentlyCreated) {
                                    $notify_to_shared_ids[] = $sheet->shared_id;
                                }

                                //get users of the role to notify
                                if ($sheet->shared_with == 'role' && $sheet->wasRecentlyCreated) {
                                    $role = Role::where('business_id', $business_id)
                                        ->find($sheet->shared_id);
                                    $users = User::role($role->name)->get()->pluck('id')->toArray();
                                    $notify_to_shared_ids = array_unique(array_merge($notify_to_shared_ids, $users));
                                }
                            }
                            SpreadsheetShare::where('sheet_spreadsheet_id', $sheet_id)
                                ->where('shared_with', $shared_with)
                                ->whereNotIn('shared_id', $existing_shared_ids)
                                ->delete();
                        }
                    }

                //if shared with is empty then delete whole rows of given sheet & shared with
                if (empty($shares['todo'])) {
                    SpreadsheetShare::where('sheet_spreadsheet_id', $sheet_id)
                            ->where('shared_with', 'todo')
                            ->delete();
                }

                if (empty($shares['user'])) {
                    SpreadsheetShare::where('sheet_spreadsheet_id', $sheet_id)
                            ->where('shared_with', 'user')
                            ->delete();
                }

                if (empty($shares['role'])) {
                    SpreadsheetShare::where('sheet_spreadsheet_id', $sheet_id)
                            ->where('shared_with', 'role')
                            ->delete();
                }

                if (! empty($notify_to_shared_ids)) {
                    $this->notifyUsersOfSharedSheets($business_id, $sheet_id, $notify_to_shared_ids);
                }
                DB::commit();
                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (Exception $e) {
                DB::rollBack();

                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    public function notifyUsersOfSharedSheets($business_id, $sheet_id, $shared_ids)
    {
        $users = User::where('business_id', $business_id)
                    ->find($shared_ids);

        Notification::send($users, new SpreadsheetShared($sheet_id));
    }

    public function addFolder(Request $request)
    {
        try {
            $input = $request->only(['name']);
            if (! empty($request->input('folder_id'))) {
                Category::where('business_id', $request->session()->get('user.business_id'))
                        ->where('id', $request->input('folder_id'))
                        ->where('category_type', 'spreadsheet')
                        ->update($input);
            } else {
                $input['parent_id'] = 0;
                $input['business_id'] = $request->session()->get('user.business_id');
                $input['created_by'] = $request->session()->get('user.id');
                $input['category_type'] = 'spreadsheet';

                Category::create($input);
            }

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()
                ->action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'index'])
                ->with('status', $output);
    }

    public function moveToFolder(Request $request)
    {
        if (! empty($request->input('move_to_folder'))) {
            $business_id = $request->session()->get('user.business_id');

            Spreadsheet::where('business_id', $business_id)
                    ->where('created_by', auth()->user()->id)
                    ->where('id', $request->input('spreadsheet_id'))
                    ->update(['folder_id' => $request->input('move_to_folder')]);

            $output = ['success' => true,
                'msg' => __('lang_v1.success'),
            ];

            return redirect()
                ->action([\Modules\Spreadsheet\Http\Controllers\SpreadsheetController::class, 'index'])
                ->with('status', $output);
        }
    }
}

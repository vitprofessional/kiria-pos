<?php

namespace Modules\Loan\Http\Controllers;

use Modules\Accounting\Services\FlashService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Loan\Entities\LoanCollateralType;
use Yajra\DataTables\Facades\DataTables;

class LoanCollateralTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $perPage = $request->per_page ?: 20;
        $orderBy = $request->order_by;
        $orderByDir = $request->order_by_dir;
        $search = $request->s;
        $data = LoanCollateralType::forBusiness()
            ->when($orderBy, function (Builder $query) use ($orderBy, $orderByDir) {
                $query->orderBy($orderBy, $orderByDir);
            })
            ->when($search, function (Builder $query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->paginate($perPage)
            ->appends($request->input());
        return view('loan::loan_collateral_type.index', compact('data'));
    }

    public function get_collateral_types()
    {
        $query = LoanCollateralType::query();
        return DataTables::of($query)->editColumn('action', function ($data) {
            $action = '<div class="btn-group"><button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="true"><i class="fa fa-navicon"></i></button> <ul class="dropdown-menu dropdown-menu-right" role="menu">';
            if (Auth::user()->hasPermissionTo('loan.loans.collateral_types.edit')) {
                $action .= '<li><a href="' . url('contact_loan/collateral_type/' . $data->id . '/edit') . '" class="">' . trans_choice('accounting::core.edit', 2) . '</a></li>';
            }
            if (Auth::user()->hasPermissionTo('loan.loans.collateral_types.destroy')) {
                $action .= '<li><a href="' . url('contact_loan/collateral_type/' . $data->id . '/destroy') . '" class="confirm">' . trans_choice('accounting::core.delete', 2) . '</a></li>';
            }
            $action .= "</ul></li></div>";
            return $action;
        })->editColumn('id', function ($data) {
            return '<a href="' . url('contact_loan/collateral_type/' . $data->id . '/show') . '">' . $data->id . '</a>';
        })->rawColumns(['id', 'action'])->make(true);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('loan::loan_collateral_type.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
        ]);
        $loan_collateral_type = new LoanCollateralType();
        $loan_collateral_type->name = $request->name;
        $loan_collateral_type->business_id = session('business.id');
        $loan_collateral_type->save();
        // activity()->on($loan_collateral_type)
        //     ->withProperties(['id' => $loan_collateral_type->id])
        //     ->log('Create Loan Collateral Type');
        // (new FlashService())->onSave();
        return redirect('contact_loan/collateral_type');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $loan_collateral_type = LoanCollateralType::find($id);
        return view('loan::loan_collateral_type.show', compact('loan_collateral_type'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $loan_collateral_type = LoanCollateralType::find($id);
        return view('loan::loan_collateral_type.edit', compact('loan_collateral_type'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => ['required'],
        ]);
        $loan_collateral_type = LoanCollateralType::find($id);
        $loan_collateral_type->name = $request->name;
        $loan_collateral_type->business_id = session('business.id');
        $loan_collateral_type->save();
        // activity()->on($loan_collateral_type)
        //     ->withProperties(['id' => $loan_collateral_type->id])
        //     ->log('Update Loan Collateral Type');
        // (new FlashService())->onSave();
        return redirect('contact_loan/collateral_type');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $loan_collateral_type = LoanCollateralType::find($id);
        $loan_collateral_type->delete();
        // activity()->on($loan_collateral_type)
        //     ->withProperties(['id' => $loan_collateral_type->id])
        //     ->log('Delete Loan Collateral Type');
        // (new FlashService())->onDelete();
        return redirect()->back();
    }
}

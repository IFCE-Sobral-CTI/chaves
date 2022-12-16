<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        $this->authorize('reports.viewAny');

        $request->validate([
            'start' => 'nullable|date|before:'.now()->format('Y-m-d'),
            'end' => 'nullable|date|after:start',
            'employee' => 'nullable|exists:employees,id',
            'user' => 'nullable|exists:users,id',
            'situation' => 'nullable|in:1,2,3'
        ], [
            'start.before' => 'A data de início deve ser anterior a hoje.'
        ]);

        return Inertia::render('Keys/Report/Index', array_merge(Borrow::ReportByDate($request), [
            'users' => User::select('id', 'name')->where('status', User::ACTIVE)->get(),
            'employees' => Employee::select('id', 'registry', 'name')->get(),
            'filters' => [
                'start' => $request->start,
                'end' => $request->end,
                'employee' => $request->employee,
                'user' => $request->user,
                'situation' => $request->situation,
            ]
        ]));
    }
}

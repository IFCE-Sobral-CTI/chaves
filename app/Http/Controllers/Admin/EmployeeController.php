<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('employees.viewAny', Employee::class);

        $result = Employee::search($request);

        return Inertia::render('Keys/Employee/Index', array_merge($result, [
            'can' => [
                'create' => Auth::user()->can('blocks.create'),
                'view' => Auth::user()->can('blocks.view'),
            ]
        ]));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('employees.create', Employee::class);

        return Inertia::render('Keys/Employee/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreEmployeeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEmployeeRequest $request)
    {
        $this->authorize('employees.create', Employee::class);

        try {
            $employee = Employee::create($request->validated());
            return redirect()->route('employees.show', $employee)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('employees.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function show(Employee $employee)
    {
        $this->authorize('employees.view', $employee);

        return Inertia::render('Keys/Employee/Show', [
            'employee' => $employee,
            'can' => [
                'update' => Auth::user()->can('blocks.update'),
                'delete' => Auth::user()->can('blocks.delete'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function edit(Employee $employee)
    {
        $this->authorize('employees.update', $employee);

        return Inertia::render('Keys/Employee/Edit', [
            'employee' => $employee,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEmployeeRequest  $request
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('employees.update', $employee);

        try {
            $employee->update($request->validated());
            return redirect()->route('employees.show', $employee)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('employees.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function destroy(Employee $employee)
    {
        $this->authorize('employees.view', $employee);

        try {
            $employee->delete();
            return redirect()->route('employees.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('employees.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

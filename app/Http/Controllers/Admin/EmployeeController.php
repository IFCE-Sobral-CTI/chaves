<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
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
     * @return Response
     * @throws AuthorizationException
     */
    public function create(): Response
    {
        $this->authorize('employees.create', Employee::class);

        return Inertia::render('Keys/Employee/Create', [
            'employeeType' => [
                ['value' => Employee::EMPLOYEE, 'label' => 'Servidor'],
                ['value' => Employee::COLLABORATOR, 'label' => 'Colaborador'],
                ['value' => Employee::STUDENT, 'label' => 'Discente'],
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreEmployeeRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
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
     * @param Employee $employee
     * @return Response
     * @throws AuthorizationException
     */
    public function show(Employee $employee): Response
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
     * @param Employee $employee
     * @return Response
     * @throws AuthorizationException
     */
    public function edit(Employee $employee): Response
    {
        $this->authorize('employees.update', $employee);

        return Inertia::render('Keys/Employee/Edit', [
            'employee' => $employee,
            'employeeType' => [
                ['value' => Employee::EMPLOYEE, 'label' => 'Servidor'],
                ['value' => Employee::COLLABORATOR, 'label' => 'Colaborador'],
                ['value' => Employee::STUDENT, 'label' => 'Discente'],
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateEmployeeRequest $request
     * @param Employee $employee
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
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
     * @param Employee $employee
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Employee $employee): RedirectResponse
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

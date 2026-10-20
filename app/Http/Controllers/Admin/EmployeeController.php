<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Key;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        $this->authorize('employees.viewAny', Employee::class);

        $result = Employee::search($request);

        return Inertia::render('Keys/Employee/Index', array_merge($result, [
            'can' => [
                'create' => $request->user()->can('employees.create'),
                'view' => $request->user()->can('employees.view'),
            ],
        ]));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     */
    public function create(): Response
    {
        $this->authorize('employees.create', Employee::class);

        return Inertia::render('Keys/Employee/Create', [
            'employeeType' => Employee::TYPES,
            'keys' => Key::getForSelect(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
     */
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $this->authorize('employees.create', Employee::class);

        try {
            $data = $request->validated();
            $keyIds = $data['borrowable_key_ids'];
            unset($data['borrowable_key_ids']);

            $employee = DB::transaction(function () use ($data, $keyIds): Employee {
                $this->lockBorrowableKeys($keyIds);
                $employee = Employee::create($data);
                $employee->borrowable_keys()->sync($keyIds);

                return $employee;
            });

            return redirect()->route('employees.show', $employee)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->operationFailed($e, 'employees.index');
        }
    }

    /**
     * Display the specified resource.
     *
     * @throws AuthorizationException
     */
    public function show(Request $request, Employee $employee): Response
    {
        $this->authorize('employees.view', $employee);

        return Inertia::render('Keys/Employee/Show', [
            'employee' => $employee->load(['borrowable_keys' => ['room']]),
            'keys' => Key::getForSelect(),
            'can' => [
                'update' => $request->user()->can('employees.update'),
                'delete' => $request->user()->can('employees.delete'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @throws AuthorizationException
     */
    public function edit(Employee $employee): Response
    {
        $this->authorize('employees.update', $employee);

        return Inertia::render('Keys/Employee/Edit', [
            'employee' => $employee->load('borrowable_keys'),
            'employeeType' => Employee::TYPES,
            'keys' => Key::getForSelect(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('employees.update', $employee);

        try {
            $data = $request->validated();
            $keyIds = $data['borrowable_key_ids'];
            unset($data['borrowable_key_ids']);
            $previousKeyIds = $employee->borrowable_keys()->pluck('keys.id')->map(fn ($id) => (int) $id)->all();
            sort($previousKeyIds, SORT_NUMERIC);

            DB::transaction(function () use ($employee, $data, $keyIds, $previousKeyIds): void {
                $lockIds = $this->lockBorrowableKeys([...$previousKeyIds, ...$keyIds]);
                $lockedEmployee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
                $currentKeyIds = $lockedEmployee->borrowable_keys()->pluck('keys.id')->map(fn ($id) => (int) $id)->all();
                sort($currentKeyIds, SORT_NUMERIC);
                if ($currentKeyIds !== $previousKeyIds || array_diff($currentKeyIds, $lockIds)) {
                    throw ValidationException::withMessages(['borrowable_key_ids' => 'As autorizações deste mutuário foram alteradas. Recarregue o cadastro e tente novamente.']);
                }

                $lockedEmployee->update($data);
                $lockedEmployee->borrowable_keys()->sync($keyIds);
            });

            return redirect()->route('employees.show', $employee)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            return $this->operationFailed($e, 'employees.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws AuthorizationException
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('employees.delete', $employee);

        try {
            $employee->delete();

            return redirect()->route('employees.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return $this->deletionFailed($e, 'employees.index');
        }
    }

    /** @param array<int, int|string> $keyIds
     * @return array<int, int>
     */
    private function lockBorrowableKeys(array $keyIds): array
    {
        $keyIds = array_values(array_unique(array_map('intval', $keyIds)));
        sort($keyIds, SORT_NUMERIC);
        $lockedIds = Key::whereIn('id', $keyIds)->orderBy('id')->lockForUpdate()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (count($lockedIds) !== count($keyIds)) {
            throw ValidationException::withMessages(['borrowable_key_ids' => 'Uma das chaves autorizadas não está mais cadastrada. Recarregue o cadastro.']);
        }

        return $keyIds;
    }
}

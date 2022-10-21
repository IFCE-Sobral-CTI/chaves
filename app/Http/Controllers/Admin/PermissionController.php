<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\StoreUpdatePermissionsRulesRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;
use App\Models\Rule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $this->authorize('permissions.viewAny', Permission::class);

        $result = Permission::search($request->term);

        return Inertia::render('Permission/Index', [
            'permissions' => $result['data'],
            'count' => $result['count'],
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
            'can' => [
                'viewAny' => Auth::user()->can('permissions.viewAny'),
                'view' => Auth::user()->can('permissions.view'),
                'create' => Auth::user()->can('permissions.create'),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $this->authorize('permissions.create', Permission::class);

        return Inertia::render('Permission/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePermissionRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StorePermissionRequest $request)
    {
        $this->authorize('permissions.create', Permission::class);

        $data = $request->validated();

        try {
            $permission = Permission::create($data);
            return redirect()->route('permissions.show', $permission)->with('flash', ['status' => 'success', 'message' => 'Registro salvo com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('permissions.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Inertia\Response
     */
    public function show(Permission $permission)
    {
        $this->authorize('permissions.view', $permission);

        return Inertia::render('Permission/Show', [
            'permission' => Permission::with('rules')->find($permission->id),
            'can' => [
                'delete' => Auth::user()->can('permissions.delete'),
                'update' => Auth::user()->can('permissions.update'),
                'rules' => Auth::user()->can('permissions.rules'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Inertia\Response
     */
    public function edit(Permission $permission)
    {
        $this->authorize('permissions.update', $permission);

        return Inertia::render('Permission/Edit', [
            'permission' => $permission,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePermissionRequest  $request
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $this->authorize('permissions.update', $permission);

        $data = $request->validated();

        try {
            $permission->update($data);
            return redirect()->route('permissions.show', $permission)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('permissions.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Permission  $permission
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Permission $permission)
    {
        $this->authorize('permissions.delete', $permission);

        try {
            $permission->delete();
            return redirect()->route('permissions.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('permissions.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    public function rules(Permission $permission)
    {
        $this->authorize('permissions.rules', $permission);

        $rules = Rule::orderBy('description', 'ASC')->get();
        $permission = Permission::with('rules')->find($permission->id);

        return Inertia::render('Permission/Rules', [
            'rules' => $rules,
            'permission' => $permission,
        ]);
    }

    public function syncRules(StoreUpdatePermissionsRulesRequest $request, Permission $permission)
    {
        $this->authorize('permissions.rules', $permission);

        try {
            $permission->rules()->sync($request->rules);
            return redirect()->route('permissions.show', $permission)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('permissions.show', $permission)->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

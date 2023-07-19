<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\StoreUpdatePermissionsRulesRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Rule;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
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
        $this->authorize('permissions.viewAny', Permission::class);

        $result = Permission::search($request->term);

        return Inertia::render('Permission/Index', [
            'permissions' => $result['data'],
            'count' => $result['count'],
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
            'can' => [
                'viewAny' => $request->user()->can('permissions.viewAny'),
                'view' => $request->user()->can('permissions.view'),
                'create' => $request->user()->can('permissions.create'),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     * @return Response
     */
    public function create(): Response
    {
        $this->authorize('permissions.create', Permission::class);

        return Inertia::render('Permission/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws AuthorizationException
     * @param StorePermissionRequest $request
     * @return RedirectResponse
     */
    public function store(StorePermissionRequest $request): RedirectResponse
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
     * @throws AuthorizationException
     * @param Permission $permission
     * @return Response
     */
    public function show(Request $request, Permission $permission): Response
    {
        $this->authorize('permissions.view', $permission);

        return Inertia::render('Permission/Show', [
            'permission' => Permission::with('rules')->find($permission->id),
            'can' => [
                'delete' => $request->user()->can('permissions.delete'),
                'update' => $request->user()->can('permissions.update'),
                'rules' => $request->user()->can('permissions.rules'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @throws AuthorizationException
     * @param Permission $permission
     * @return Response
     */
    public function edit(Permission $permission): Response
    {
        $this->authorize('permissions.update', $permission);

        return Inertia::render('Permission/Edit', [
            'permission' => $permission,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     * @param UpdatePermissionRequest $request
     * @param Permission $permission
     * @return RedirectResponse
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
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
     * @throws AuthorizationException
     * @param Permission $permission
     * @return RedirectResponse
     */
    public function destroy(Permission $permission): RedirectResponse
    {
        $this->authorize('permissions.delete', $permission);

        try {
            $permission->delete();
            return redirect()->route('permissions.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('permissions.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing rules the specified resource.
     *
     * @param Permission $permission
     * @return Response
     * @throws AuthorizationException
     */
    public function rules(Permission $permission): Response
    {
        $this->authorize('permissions.rules', $permission);

        $permission = Permission::with('rules')->find($permission->id);

        return Inertia::render('Permission/Rules', [
            'groups' => Group::with('rules')->get(),
            'permission' => $permission,
        ]);
    }

    /**
     * Update rules the specified resource in storage.
     *
     * @throws AuthorizationException
     * @param StoreUpdatePermissionsRulesRequest $request
     * @param Permission $permission
     * @return RedirectResponse
     */
    public function syncRules(StoreUpdatePermissionsRulesRequest $request, Permission $permission): RedirectResponse
    {
        $this->authorize('permissions.rules', $permission);

        try {
            $permission->rules()->sync($request->rules);
            $properties = function(Request $request) {
                $rules = Rule::with('group')->whereIn('id', $request->rules)->get()->map(function($item) {
                    return $item->group->description.' - '.$item->description;
                });
                return ['rules' => implode('|', $rules->toArray())];
            };
            activity()
                ->by($request->user())
                ->on($permission)
                ->withProperty('attributes', $properties($request))
                ->log('updated');
            return redirect()->route('permissions.show', $permission)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('permissions.show', $permission)->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

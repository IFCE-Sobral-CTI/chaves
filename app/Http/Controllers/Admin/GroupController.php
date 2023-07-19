<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GroupController extends Controller
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
        $this->authorize('groups.viewAny', Group::class);

        return Inertia::render('Group/Index', array_merge(
            Group::search($request),
            [
                'can' => [
                    'create' => $request->user()->can('groups.create'),
                    'view' => $request->user()->can('groups.view'),
                ],
            ])
        )
        ;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     * @throws AuthorizationException
     */
    public function create(): Response
    {
        $this->authorize('groups.create', Group::class);

        return Inertia::render('Group/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreGroupRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->authorize('groups.create', Group::class);

        try {
            $group = Group::create($request->validated());
            return redirect()->route('groups.show', $group)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('groups.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Group $group
     * @return Response
     * @throws AuthorizationException
     */
    public function show(Request $request, Group $group): Response
    {
        $this->authorize('groups.view', $group);

        return Inertia::render('Group/Show', [
            'group' => $group,
            'can' => [
                'update' => $request->user()->can('groups.update'),
                'delete' => $request->user()->can('groups.delete'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Group $group
     * @return Response
     * @throws AuthorizationException
     */
    public function edit(Group $group): Response
    {
        $this->authorize('groups.update', $group);

        return Inertia::render('Group/Edit', [
            'group' => $group
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateGroupRequest $request
     * @param Group $group
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('groups.update', $group);

        try {
            $group->update($request->validated());
            return redirect()->route('groups.show', $group)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('groups.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Group $group
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Group $group): RedirectResponse
    {
        $this->authorize('groups.delete', $group);

        try {
            $group->delete();
            return redirect()->route('groups.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('groups.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

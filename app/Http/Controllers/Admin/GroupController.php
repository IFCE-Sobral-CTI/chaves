<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupRequest;
use App\Http\Requests\UpdateGroupRequest;
use App\Models\Group;
use Exception;
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
     * @return Response
     */
    public function index(Request $request): Response
    {
        $this->authorize('groups.viewAny', Group::class);

        return Inertia::render('Group/Index', array_merge(
            Group::search($request),
            [
                'can' => [
                    'create' => Auth::user()->can('groups.create'),
                    'view' => Auth::user()->can('groups.view'),
                ],
            ])
        )
        ;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create(): Response
    {
        $this->authorize('groups.create', Group::class);

        return Inertia::render('Group/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreGroupRequest  $request
     * @return RedirectResponse
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
     * @param  Group  $group
     * @return Response
     */
    public function show(Group $group): Response
    {
        $this->authorize('groups.view', $group);

        return Inertia::render('Group/Show', [
            'group' => $group,
            'can' => [
                'update' => Auth::user()->can('groups.update'),
                'delete' => Auth::user()->can('groups.delete'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(Group $group)
    {
        $this->authorize('groups.update', $group);

        return Inertia::render('Group/Edit', [
            'group' => $group
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateGroupRequest  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateGroupRequest $request, Group $group)
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
     * @param  Group  $group
     * @return RedirectResponse
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

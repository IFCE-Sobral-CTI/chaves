<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKeyRequest;
use App\Http\Requests\UpdateKeyRequest;
use App\Models\Key;
use App\Models\Room;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class KeyController extends Controller
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
        $this->authorize('keys.viewAny', Key::class);

        return Inertia::render('Keys/Key/Index', array_merge(Key::search($request), [
            'can' => [
                'create' => $request->user()->can('keys.create'),
                'view' => $request->user()->can('keys.view'),
            ],
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
        $this->authorize('keys.create', Key::class);

        return Inertia::render('Keys/Key/Create', [
            'rooms' => Room::select('id', 'description')->orderBy('description', 'ASC')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreKeyRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(StoreKeyRequest $request): RedirectResponse
    {
        $this->authorize('keys.create', Key::class);

        try {
            $key = Key::create($request->validated());
            return redirect()->route('keys.show', $key)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('keys.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Key $key
     * @return Response
     * @throws AuthorizationException
     */
    public function show(Request $request, Key $key): Response
    {
        $this->authorize('keys.view', $key);

        return Inertia::render('Keys/Key/Show', [
            '_key' => Key::with('room')->find($key->id),
            'can' => [
                'update' => $request->user()->can('keys.update'),
                'delete' => $request->user()->can('keys.delete'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Key $key
     * @return Response
     * @throws AuthorizationException
     */
    public function edit(Key $key): Response
    {
        $this->authorize('keys.update', $key);

        return Inertia::render('Keys/Key/Edit', [
            '_key' => $key,
            'rooms' => Room::select('id', 'description')->orderBy('description', 'ASC')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateKeyRequest $request
     * @param Key $key
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(UpdateKeyRequest $request, Key $key): RedirectResponse
    {
        $this->authorize('keys.update', $key);

        try {
            $key->update($request->validated());
            return redirect()->route('keys.show', $key)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('keys.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Key $key
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Key $key): RedirectResponse
    {
        $this->authorize('keys.delete', $key);
        try {
            $key->delete();
            return redirect()->route('keys.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('keys.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKeyRequest;
use App\Http\Requests\UpdateKeyRequest;
use App\Models\Key;
use App\Models\Room;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class KeyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('keys.viewAny', Key::class);

        return Inertia::render('Keys/Key/Index', array_merge(Key::search($request), [
            'can' => [
                'create' => Auth::user()->can('keys.create'),
                'view' => Auth::user()->can('keys.view'),
            ],
        ]));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('keys.create', Key::class);

        return Inertia::render('Keys/Key/Create', [
            'rooms' => Room::select('id', 'description')->orderBy('description', 'ASC')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreKeyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreKeyRequest $request)
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
     * @param  \App\Models\Key  $key
     * @return \Illuminate\Http\Response
     */
    public function show(Key $key)
    {
        $this->authorize('keys.view', $key);

        return Inertia::render('Keys/Key/Show', [
            '_key' => Key::with('room')->find($key->id),
            'can' => [
                'update' => Auth::user()->can('keys.update'),
                'delete' => Auth::user()->can('keys.delete'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Key  $key
     * @return \Illuminate\Http\Response
     */
    public function edit(Key $key)
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
     * @param  \App\Http\Requests\UpdateKeyRequest  $request
     * @param  \App\Models\Key  $key
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateKeyRequest $request, Key $key)
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
     * @param  \App\Models\Key  $key
     * @return \Illuminate\Http\Response
     */
    public function destroy(Key $key)
    {
        try {
            $key->delete();
            return redirect()->route('keys.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('keys.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

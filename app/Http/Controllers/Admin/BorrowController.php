<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBorrowRequest;
use App\Http\Requests\UpdateBorrowRequest;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BorrowController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('borrows.viewAny', Borrow::class);

        return Inertia::render('Keys/Borrow/Index', array_merge(Borrow::search($request), [
            'can' => [
                'create' => Auth::user()->can('borrows.create'),
                'view' => Auth::user()->can('borrows.view'),
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
        $this->authorize('borrows.create', Borrow::class);

        return Inertia::render('Keys/Borrow/Create', [
            'employees' => Employee::select('id', 'name')->orderBy('name', 'ASC')->get(),
            'keys' => Key::with('room')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreBorrowRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBorrowRequest $request)
    {
        $this->authorize('borrows.create', Borrow::class);

        try {
            $borrow = Borrow::create($request->validated());
            $borrow->keys()->sync($request->keys);
            return redirect()->route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('borrows.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Borrow  $borrow
     * @return \Illuminate\Http\Response
     */
    public function show(Borrow $borrow)
    {
        $this->authorize('borrows.view', $borrow);

        $borrow = Borrow::with(['employee', 'keys' => ['room']])->find($borrow->id);

        return Inertia::render('Keys/Borrow/Show', [
            'borrow' => $borrow,
            'can' => [
                'update' => Auth::user()->can('borrows.update'),
                'delete' => Auth::user()->can('borrows.delete'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Borrow  $borrow
     * @return \Illuminate\Http\Response
     */
    public function edit(Borrow $borrow)
    {
        $this->authorize('borrows.update', $borrow);

        $borrowKey = $borrow->keys->pluck("id")->toArray();
        $borrow = Borrow::with(['employee', 'keys' => ['room']])->find($borrow->id);

        return Inertia::render('Keys/Borrow/Edit', [
            'borrow' => $borrow,
            'employees' => Employee::select('id', 'name')->orderBy('name')->get(),
            'borrowKeys' => $borrowKey,
            'keys' => Key::with('room')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBorrowRequest  $request
     * @param  \App\Models\Borrow  $borrow
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBorrowRequest $request, Borrow $borrow)
    {
        $this->authorize('borrows.update', $borrow);

        try {
            $borrow->update($request->validated());
            $borrow->keys()->sync($request->keys);
            return redirect()->route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('borrows.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Borrow  $borrow
     * @return \Illuminate\Http\Response
     */
    public function destroy(Borrow $borrow)
    {
        $this->authorize('borrows.delete', $borrow);

        try {
            $borrow->delete();
            return redirect()->route('borrows.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('borrows.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBorrowRequest;
use App\Http\Requests\UpdateBorrowRequest;
use App\Models\Borrow;
use App\Models\BorrowKey;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Received;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BorrowController extends Controller
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
     * @throws AuthorizationException
     * @return Response
     */
    public function create(): Response
    {
        $this->authorize('borrows.create', Borrow::class);

        $keysInBorrows = Key::whereHas('borrows', function($query) {
            return $query->where('devolution', null);
        })->pluck('id')->toArray();

        return Inertia::render('Keys/Borrow/Create', [
            'employees' => Employee::select('id', 'registry', 'name')->where('valid_until', '>=', now())->orWhere('valid_until', null)->orderBy('name', 'ASC')->get(),
            'keys' => Key::with('room')->whereNotIn('id', $keysInBorrows)->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBorrowRequest $request
     * @throws AuthorizationException
     *
     * @return RedirectResponse
     */
    public function store(StoreBorrowRequest $request)
    {
        $this->authorize('borrows.create', Borrow::class);

        try {
            $borrow = Auth::user()->borrows()->create($request->validated());
            $borrow->keys()->sync($request->keys);
            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            return to_route('borrows.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Borrow $borrow
     * @throws AuthorizationException
     * @return Response
     */
    public function show(Borrow $borrow): Response
    {
        $this->authorize('borrows.view', $borrow);

        $borrow = Borrow::with(['employee', 'user', 'keys' => ['room'], 'received' => ['keys' => ['room'], 'user']])->find($borrow->id);

        return Inertia::render('Keys/Borrow/Show', [
            'borrow' => $borrow,
            'can' => [
                'update' => Auth::user()->can('borrows.update'),
                'delete' => Auth::user()->can('borrows.delete'),
                'receive' => Auth::user()->can('borrows.receive'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Borrow $borrow
     * @return Response
     *@throws AuthorizationException
     */
    public function edit(Borrow $borrow): Response
    {
        $this->authorize('borrows.update', $borrow);

        $borrowKeys = $borrow->keys->pluck("id")->toArray();
        $borrow = Borrow::with(['employee', 'keys' => ['room']], 'receivedBy')->find($borrow->id);

        $borrowed = Key::borrowed()->pluck('id')->toArray();

        return Inertia::render('Keys/Borrow/Edit', [
            'borrow' => $borrow,
            'employees' => Employee::select('id', 'registry', 'name')->orderBy('name')->get(),
            'borrowKeys' => $borrowKeys,
            'keys' => Key::with('room')->whereNotIn('id', array_diff($borrowed, $borrowKeys))->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateBorrowRequest $request
     * @param Borrow $borrow
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(UpdateBorrowRequest $request, Borrow $borrow): RedirectResponse
    {
        $this->authorize('borrows.update', $borrow);

        if (is_null($request->devolution) && $borrow->devolution) {
            $this->keysBorrowed($request, $borrow);
        }

        try {
            // Verifica se foi adicionado ou removido alguma chave
            // if (array_diff($request->keys, $borrow->keys()->get()->pluck('id')->toArray()))
            //     $borrow->update(array_merge($request->except(['devolution', 'received_by']), ['devolution' => null, 'received_by' => null]));
            // else
            $borrow->update($request->validated());

            $borrow->keys()->sync($request->keys);
            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return to_route('borrows.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param Request $request
     * @param Borrow $borrow
     * @return RedirectResponse|void
     */
    private function keysBorrowed(Request $request, Borrow $borrow): ?RedirectResponse
    {
        /*
         * Verifica se as chaves foi retirada na data de devolu????o
         * e se as chaves que voltar??o a ser emprestas j?? est??o emprestadas
         */
        $borrowed = Key::borrowed()->pluck('id')->toArray();

        $filtered = array_reduce($borrowed, function($carry, $item) use ($request) {
            if (in_array($item, $request->keys))
                return ++$carry;
            return $carry;
        });

        if ($filtered)
            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => 'Alguma das chaves j?? est??o emprestada.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Borrow $borrow
     * @throws AuthorizationException
     * @return RedirectResponse
     */
    public function receive(Borrow $borrow, Request $request): RedirectResponse
    {
        $this->authorize('borrows.receive', $borrow);

        $request->validate([
            'returned_by' => 'nullable|min:2',
            'keys' => 'array',
            'keys.*' => 'exists:keys,id',
        ]);

        try {
            $received = Received::create([
                'receiver' => $request->returned_by,
                'user_id' => Auth::user()->id,
                'borrow_id' => $borrow->id,
            ]);
            $received->keys()->sync($request->keys);
        } catch (Exception $e) {
            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }



        try {
            if ($borrow->keys()->count() == $borrow->received->keys()->count())
                $borrow->update(['devolution' => now(), 'received_by' => Auth::user()->id, 'returned_by' => $request->returned_by]);

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Borrow $borrow
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Borrow $borrow): RedirectResponse
    {
        $this->authorize('borrows.delete', $borrow);

        try {
            $borrow->delete();
            return to_route('borrows.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return to_route('borrows.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

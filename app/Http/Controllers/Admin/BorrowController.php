<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBorrowRequest;
use App\Http\Requests\UpdateBorrowRequest;
use App\Models\Borrow;
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
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        $this->authorize('borrows.viewAny', Borrow::class);

        return Inertia::render('Keys/Borrow/Index', array_merge(Borrow::search($request), [
            'can' => [
                'create' => $request->user()->can('borrows.create'),
                'view' => $request->user()->can('borrows.view'),
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
        $this->authorize('borrows.create', Borrow::class);

        // dd(Key::with('room')->whereNotIn('id', Borrow::KeysReceived())->get());

        return Inertia::render('Keys/Borrow/Create', [
            'employees' => fn () => Employee::getActiveEmployees(),
            'keys' => fn () => Key::with('room')->whereNotIn('id', Borrow::KeysNotReceived())->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @return RedirectResponse
     *
     * @throws AuthorizationException
     */
    public function store(StoreBorrowRequest $request)
    {
        $this->authorize('borrows.create', Borrow::class);

        try {
            $borrow = $request->user()->borrows()->create($request->validated());
            $borrow->keys()->sync($request->keys);

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            report($e);

            return to_route('borrows.index')->with('flash', ['status' => 'danger', 'message' => 'Ocorreu um erro ao processar a solicitação.']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @throws AuthorizationException
     */
    public function show(Request $request, Borrow $borrow): Response
    {
        $this->authorize('borrows.view', $borrow);

        $borrow = Borrow::with(['employee', 'user', 'keys' => ['room'], 'received' => ['keys' => ['room'], 'user']])->find($borrow->id);
        $received = $borrow->receivedKeys();

        return Inertia::render('Keys/Borrow/Show', [
            'borrow' => $borrow,
            'received' => $borrow->receivedKeys(),
            'can' => [
                'update' => $request->user()->can('borrows.update'),
                'delete' => $request->user()->can('borrows.delete'),
                'receive' => $request->user()->can('borrows.receive'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @throws AuthorizationException
     */
    public function edit(Borrow $borrow): Response
    {
        $this->authorize('borrows.update', $borrow);

        $borrowKeys = $borrow->keys->pluck('id')->toArray();
        $borrow = Borrow::with(['employee', 'keys' => ['room']])->find($borrow->id);

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
     * @throws AuthorizationException
     */
    public function update(UpdateBorrowRequest $request, Borrow $borrow): RedirectResponse
    {
        $this->authorize('borrows.update', $borrow);

        $result = $this->keysBorrowed($request, $borrow);
        if ($result) {
            return $result;
        }

        try {
            // Verifica se foi adicionado ou removido alguma chave
            // if (array_diff($request->keys, $borrow->keys()->get()->pluck('id')->toArray()))
            //     $borrow->update(array_merge($request->except(['devolution', 'received_by']), ['devolution' => null, 'received_by' => null]));
            // else
            $borrow->update($request->validated());

            $borrow->keys()->sync($request->keys);
            // Cria um log separado para alterações nas chaves
            if ($request->keys) {
                $properties = function (Request $request) {
                    $keys = Key::whereIn('id', $request->keys)->get()->map(function ($item) {
                        return $item->number;
                    })->toArray();

                    return ['Chaves' => implode(' - ', $keys)];
                };

                activity()
                    ->by(Auth::user())
                    ->on($borrow)
                    ->withProperty('attributes', $properties($request))
                    ->log('updated');
            }

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            report($e);

            return to_route('borrows.index')->with('flash', ['status' => 'danger', 'message' => 'Ocorreu um erro ao processar a solicitação.']);
        }
    }

    /**
     * @return RedirectResponse|void
     */
    private function keysBorrowed(Request $request, Borrow $borrow)
    {
        /*
         * Verifica se as chaves a serem emprestadas já estão emprestadas
         * por outro empréstimo ativo (excluindo as chaves do próprio empréstimo)
         */
        $borrowed = Key::borrowed()->pluck('id')->toArray();
        $ownKeys = $borrow->keys()->pluck('keys.id')->toArray();
        $foreignBorrowed = array_diff($borrowed, $ownKeys);

        $filtered = array_reduce($foreignBorrowed, function ($carry, $item) use ($request) {
            if (in_array($item, $request->keys)) {
                return ++$carry;
            }

            return $carry;
        });

        if ($filtered) {
            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => 'Alguma das chaves já estão emprestada.']);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
    public function receive(Borrow $borrow, Request $request, string $keys): RedirectResponse
    {
        $this->authorize('borrows.receive', $borrow);

        $request->validate([
            'returned_by' => 'required|min:2',
        ], [], [
            'returned_by' => 'devolução',
        ]);

        // Garante que as chaves recebidas pertencem a este empréstimo (evita IDOR:
        // os IDs vêm crus da URL e não podem ser confiados).
        $requestedKeyIds = array_filter(array_map('intval', explode('|', $keys)));
        $borrowKeyIds = $borrow->keys->pluck('id')->all();
        $invalidKeyIds = array_diff($requestedKeyIds, $borrowKeyIds);

        if (empty($requestedKeyIds) || ! empty($invalidKeyIds)) {
            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => 'Chaves inválidas para este empréstimo.']);
        }

        try {
            $received = Received::create([
                'receiver' => $request->returned_by,
                'user_id' => Auth::user()->id,
                'borrow_id' => $borrow->id,
            ]);
            $received->keys()->sync($requestedKeyIds);
        } catch (Exception $e) {
            report($e);

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => 'Não foi possível registrar a devolução.']);
        }

        try {
            if ($borrow->keys()->count() == count($borrow->receivedKeys())) {
                $borrow->update(['devolution' => now(), 'received_by' => Auth::user()->id, 'returned_by' => $request->returned_by]);
            }

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            report($e);

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => 'Ocorreu um erro ao processar a solicitação.']);
        }
    }

    public function receiveDestroy(Borrow $borrow, Received $received): RedirectResponse
    {
        $this->authorize('borrows.received.delete', $borrow);

        try {
            $received->delete();

            $borrow->refresh();

            if ($borrow->keys()->count() != count($borrow->receivedKeys())) {
                $borrow->update(['devolution' => null, 'received_by' => null, 'returned_by' => null]);
            }

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            report($e);

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => 'Ocorreu um erro ao processar a solicitação.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws AuthorizationException
     */
    public function destroy(Borrow $borrow): RedirectResponse
    {
        $this->authorize('borrows.delete', $borrow);

        try {
            $borrow->delete();

            return to_route('borrows.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            report($e);

            return to_route('borrows.index')->with('flash', ['status' => 'danger', 'message' => 'Ocorreu um erro ao processar a solicitação.']);
        }
    }
}

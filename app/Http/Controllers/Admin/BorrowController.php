<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Borrows\CreateBorrow;
use App\Actions\Borrows\DeleteBorrow;
use App\Actions\Borrows\DeleteReceived;
use App\Actions\Borrows\ReceiveKeys;
use App\Actions\Borrows\UpdateBorrow;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiveKeysRequest;
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
use Illuminate\Validation\ValidationException;
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

        return Inertia::render('Keys/Borrow/Create', [
            'employees' => fn () => Employee::getActiveEmployees(),
            'keys' => fn () => Key::with('room')->whereNotIn('id', Borrow::pendingKeyIds())->get(),
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
    public function store(StoreBorrowRequest $request, CreateBorrow $action)
    {
        $this->authorize('borrows.create', Borrow::class);

        try {
            $borrow = $action->handle($request->user(), $request->validated());

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (ValidationException $e) {
            throw $e;
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

        $borrow->load(['employee', 'user', 'keys.room', 'received.keys.room', 'received.user']);
        $received = $borrow->receivedKeys();

        return Inertia::render('Keys/Borrow/Show', [
            'borrow' => $borrow,
            'received' => $received,
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

        $borrow->load(['employee', 'keys.room']);
        $borrowKeys = $borrow->keys->pluck('id')->toArray();

        return Inertia::render('Keys/Borrow/Edit', [
            'borrow' => $borrow,
            'employees' => Employee::select('id', 'registry', 'name')->orderBy('name')->get(),
            'borrowKeys' => $borrowKeys,
            'keys' => Key::with('room')->where(function ($query) use ($borrow, $borrowKeys) {
                $query->whereNotIn('id', Borrow::where('id', '!=', $borrow->id)->pendingKeyIds())
                    ->orWhereIn('id', $borrowKeys);
            })->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
    public function update(UpdateBorrowRequest $request, Borrow $borrow, UpdateBorrow $action): RedirectResponse
    {
        $this->authorize('borrows.update', $borrow);

        try {
            $action->handle($borrow, $request->user(), $request->validated());

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            report($e);

            return to_route('borrows.index')->with('flash', ['status' => 'danger', 'message' => 'Ocorreu um erro ao processar a solicitação.']);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws AuthorizationException
     */
    public function receive(Borrow $borrow, ReceiveKeysRequest $request, ReceiveKeys $action): RedirectResponse
    {
        $this->authorize('borrows.receive', $borrow);

        $data = $request->validated();

        try {
            $action->handle($borrow, $request->user(), $data['keys'], $data['returned_by']);

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (ValidationException $e) {
            return to_route('borrows.show', $borrow)->withErrors($e->errors())
                ->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            report($e);

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => 'Não foi possível registrar a devolução.']);
        }
    }

    public function receiveDestroy(Borrow $borrow, Received $received, Request $request, DeleteReceived $action): RedirectResponse
    {
        $this->authorize('borrows.received.delete', $borrow);
        abort_unless($received->borrow_id === $borrow->id, 404);

        try {
            $action->handle($borrow, $received, $request->user());

            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (ValidationException $e) {
            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
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
    public function destroy(Borrow $borrow, DeleteBorrow $action): RedirectResponse
    {
        $this->authorize('borrows.delete', $borrow);

        try {
            $action->handle($borrow);

            return to_route('borrows.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (ValidationException $e) {
            return to_route('borrows.show', $borrow)->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            report($e);

            return to_route('borrows.index')->with('flash', ['status' => 'danger', 'message' => 'Ocorreu um erro ao processar a solicitação.']);
        }
    }
}

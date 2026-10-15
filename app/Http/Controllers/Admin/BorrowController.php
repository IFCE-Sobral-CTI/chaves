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

    public function createEmployeeOptions(Request $request)
    {
        $this->authorize('borrows.create', Borrow::class);

        return $this->employeeOptions($request);
    }

    public function editEmployeeOptions(Borrow $borrow, Request $request)
    {
        $this->authorize('borrows.update', $borrow);

        return $this->employeeOptions($request);
    }

    private function employeeOptions(Request $request)
    {
        $filters = $request->validate([
            'term' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $term = $filters['term'] ?? '';

        return Employee::query()->select('id', 'registry', 'name')
            ->where(fn ($query) => $query->where('valid_until', '>=', now())->orWhereNull('valid_until'))
            ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$term}%")->orWhere('registry', 'like', "%{$term}%")))
            ->orderBy('name')->paginate(20);
    }

    public function createKeyOptions(Request $request)
    {
        $this->authorize('borrows.create', Borrow::class);

        return $this->keyOptions($request);
    }

    public function editKeyOptions(Borrow $borrow, Request $request)
    {
        $this->authorize('borrows.update', $borrow);

        return $this->keyOptions($request, $borrow);
    }

    private function keyOptions(Request $request, ?Borrow $borrow = null)
    {
        $filters = $request->validate([
            'term' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $term = $filters['term'] ?? '';
        $query = Key::query()->select('id', 'number', 'room_id')->with('room:id,description')
            ->where(function ($query) use ($borrow) {
                $query->whereNotIn('keys.id', Borrow::query()
                    ->when($borrow, fn ($borrows) => $borrows->where('borrows.id', '!=', $borrow->id))
                    ->pendingKeyIds());
                if ($borrow) {
                    $query->orWhereIn('keys.id', $borrow->keys()->select('keys.id'));
                }
            })
            ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term) {
                $query->where('number', 'like', "%{$term}%")
                    ->orWhereHas('room', fn ($room) => $room->where('description', 'like', "%{$term}%"));
            }));

        return $query->orderBy('number')->paginate(20);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @throws AuthorizationException
     */
    public function create(): Response
    {
        $this->authorize('borrows.create', Borrow::class);

        $keys = Key::with('room:id,description')->whereNotIn('id', Borrow::pendingKeyIds())->orderBy('number')->paginate(20);

        return Inertia::render('Keys/Borrow/Create', [
            'employees' => [],
            'keys' => $keys->items(),
            'keysLastPage' => $keys->lastPage(),
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
        $availableKeys = Key::with('room:id,description')
            ->whereNotIn('id', Borrow::pendingKeyIds())->orderBy('number')->paginate(20);

        return Inertia::render('Keys/Borrow/Edit', [
            'borrow' => $borrow,
            'employees' => Employee::select('id', 'registry', 'name')->whereKey($borrow->employee_id)->get(),
            'borrowKeys' => $borrowKeys,
            'keys' => collect($availableKeys->items())->merge($borrow->keys)->unique('id')->values(),
            'keysLastPage' => $availableKeys->lastPage(),
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

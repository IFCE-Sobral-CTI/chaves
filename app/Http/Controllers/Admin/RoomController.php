<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Block;
use App\Models\Employee;
use App\Models\Room;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
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
        $this->authorize('rooms.viewAny', Room::class);

        return Inertia::render('Keys/Room/Index', array_merge(Room::search($request), [
            'can' => [
                'create' => $request->user()->can('rooms.create'),
                'view' => $request->user()->can('rooms.view'),
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
        $this->authorize('rooms.create', Room::class);

        return Inertia::render('Keys/Room/Create', [
            'blocks' => Block::select('id', 'description')->orderBy('description', 'ASC')->get(),
            'employees' => Employee::where('type', Employee::EMPLOYEE)->orderBy('name')->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRoomRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $this->authorize('rooms.create', Room::class);

        try {
            $room = Room::create($request->validated());
            $room->employees()->sync($request->employees);
            return redirect()->route('rooms.show', $room)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rooms.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Room $room
     * @return Response
     * @throws AuthorizationException
     */
    public function show(Request $request,Room $room): Response
    {
        $this->authorize('rooms.view', $room);

        return Inertia::render('Keys/Room/Show', [
            'room' => Room::with(['block', 'employees'])->find($room->id),
            'can' => [
                'update' => $request->user()->can('rooms.update'),
                'delete' => $request->user()->can('rooms.delete'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Room $room
     * @return Response
     * @throws AuthorizationException
     */
    public function edit(Room $room): Response
    {
        $this->authorize('rooms.update', $room);

        return Inertia::render('Keys/Room/Edit', [
            'room' => Room::with('employees')->find($room->id),
            'blocks' => Block::select('id', 'description')->orderBy('description', 'ASC')->get(),
            'employees' => Employee::where('type', Employee::EMPLOYEE)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRoomRequest $request
     * @param Room $room
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $this->authorize('rooms.update', $room);

        try {
            $room->update($request->validated());
            $room->employees()->sync($request->employees);
            // Cria um log caso haja algum relacionamento com um servidor
            if ($request->employees) {
                $properties = function(Request $request) {
                    $employees = Employee::whereIn('id', $request->employees)->get()->map(function($item) {
                        return $item->name;
                    })->toArray();
                    return ['Servidores' => implode(' - ', $employees)];
                };

                activity()
                    ->by(Auth::user())
                    ->on($room)
                    ->withProperty('attributes', $properties($request))
                    ->log('updated');
            }
            return redirect()->route('rooms.show', $room)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rooms.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Room $room
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Room $room): RedirectResponse
    {
        $this->authorize('rooms.delete', $room);

        try {
            $room->delete();
            return redirect()->route('rooms.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rooms.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

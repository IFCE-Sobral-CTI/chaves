<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Block;
use App\Models\Room;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('rooms.viewAny', Room::class);

        return Inertia::render('Keys/Room/Index', array_merge(Room::search($request), [
            'can' => [
                'create' => Auth::user()->can('rooms.create'),
                'view' => Auth::user()->can('rooms.view'),
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
        $this->authorize('rooms.create', Room::class);

        return Inertia::render('Keys/Room/Create', [
            'blocks' => Block::select('id', 'description')->orderBy('description', 'ASC')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRoomsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRoomRequest $request)
    {
        $this->authorize('rooms.create', Room::class);

        try {
            $room = Room::create($request->validated());
            return redirect()->route('rooms.show', $room)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rooms.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Rooms  $rooms
     * @return \Illuminate\Http\Response
     */
    public function show(Room $room)
    {
        $this->authorize('rooms.view', $room);

        return Inertia::render('Keys/Room/Show', [
            'room' => Room::with('block')->find($room->id),
            'can' => [
                'update' => Auth::user()->can('rooms.update'),
                'delete' => Auth::user()->can('rooms.delete'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Rooms  $rooms
     * @return \Illuminate\Http\Response
     */
    public function edit(Room $room)
    {
        $this->authorize('rooms.update', $room);

        return Inertia::render('Keys/Room/Edit', [
            'room' => $room,
            'blocks' => Block::select('id', 'description')->orderBy('description', 'ASC')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRoomsRequest  $request
     * @param  \App\Models\Rooms  $rooms
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        $this->authorize('rooms.update', $room);

        try {
            $room->update($request->validated());
            return redirect()->route('rooms.show', $room)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rooms.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Rooms  $rooms
     * @return \Illuminate\Http\Response
     */
    public function destroy(Room $room)
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

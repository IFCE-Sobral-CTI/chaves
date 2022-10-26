<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlockRequest;
use App\Http\Requests\UpdateBlockRequest;
use App\Models\Block;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BlockController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('blocks.viewAny', Block::class);

        $result = Block::search($request->term);

        return Inertia::render('Keys/Block/Index', [
            'blocks' => $result['data'],
            'count' => $result['count'],
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
            'can' => [
                'create' => Auth::user()->can('blocks.create'),
                'view' => Auth::user()->can('blocks.view'),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('blocks.create', Block::class);

        return Inertia::render('Keys/Block/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreBlockRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBlockRequest $request)
    {
        $this->authorize('blocks.create', Block::class);

        try {
            $block = Block::create($request->validated());
            return redirect()->route('blocks.show', $block)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('blocks.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Block  $block
     * @return \Illuminate\Http\Response
     */
    public function show(Block $block)
    {
        $this->authorize('blocks.view', $block);

        return Inertia::render('Keys/Block/Show', [
            'block' => $block,
            'can' => [
                'update' => Auth::user()->can('blocks.update'),
                'delete' => Auth::user()->can('blocks.delete'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Block  $block
     * @return \Illuminate\Http\Response
     */
    public function edit(Block $block)
    {
        $this->authorize('blocks.update', $block);

        return Inertia::render('Keys/Block/Edit', [
            'block' => $block
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBlockRequest  $request
     * @param  \App\Models\Block  $block
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBlockRequest $request, Block $block)
    {
        $this->authorize('blocks.update', $block);

        try {
            $block->update($request->validated());
            return redirect()->route('blocks.show', $block)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('blocks.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Block  $block
     * @return \Illuminate\Http\Response
     */
    public function destroy(Block $block)
    {
        $this->authorize('blocks.delete', $block);

        try {
            $block->delete();
            return redirect()->route('blocks.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('blocks.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

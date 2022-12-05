<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlockRequest;
use App\Http\Requests\UpdateBlockRequest;
use App\Models\Block;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BlockController extends Controller
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
        $this->authorize('blocks.viewAny', Block::class);

        $result = Block::search($request);

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
     * @return Response
     * @throws AuthorizationException
     */
    public function create(): Response
    {
        $this->authorize('blocks.create', Block::class);

        return Inertia::render('Keys/Block/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreBlockRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(StoreBlockRequest $request): RedirectResponse
    {
        $this->authorize('blocks.create', Block::class);

        try {
            $block = Block::create($request->validated());
            return to_route('blocks.show', $block)->with('flash', ['status' => 'success', 'message' => 'Registro criado com sucesso.']);
        } catch (Exception $e) {
            return to_route('blocks.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Block $block
     * @return Response
     * @throws AuthorizationException
     */
    public function show(Block $block): Response
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
     * @param Block $block
     * @return Response
     * @throws AuthorizationException
     */
    public function edit(Block $block): Response
    {
        $this->authorize('blocks.update', $block);

        return Inertia::render('Keys/Block/Edit', [
            'block' => $block
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateBlockRequest $request
     * @param Block $block
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(UpdateBlockRequest $request, Block $block): RedirectResponse
    {
        $this->authorize('blocks.update', $block);

        try {
            $block->update($request->validated());
            return to_route('blocks.show', $block)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return to_route('blocks.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Block $block
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Block $block): RedirectResponse
    {
        $this->authorize('blocks.delete', $block);

        try {
            $block->delete();
            return to_route('blocks.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return to_route('blocks.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

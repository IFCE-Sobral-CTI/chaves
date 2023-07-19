<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRuleRequest;
use App\Http\Requests\UpdateRuleRequest;
use App\Models\Group;
use App\Models\Rule;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RuleController extends Controller
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
        $this->authorize('rules.viewAny', Rule::class);

        $result = Rule::search($request->term);

        return Inertia::render('Rule/Index', [
            'rules' => $result['data'],
            'count' => $result['count'],
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
            'can' => [
                'viewAny' => $request->user()->can('rules.viewAny'),
                'view' => $request->user()->can('rules.view'),
                'create' => $request->user()->can('rules.create'),
            ],
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
        $this->authorize('rules.create', Rule::class);

        return Inertia::render('Rule/Create', [
            'groups' => Group::select('id', 'description')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreRuleRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(StoreRuleRequest $request): RedirectResponse
    {
        $this->authorize('rules.create', Rule::class);

        $data = $request->validated();

        try {
            $rule = Rule::create($data);
            return redirect()->route('rules.show', $rule)->with('flash', ['status' => 'success', 'message' => 'Registro salvo com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rules.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Rule $rule
     * @return Response
     * @throws AuthorizationException
     */
    public function show(Request $request, Rule $rule): Response
    {
        $this->authorize('rules.view', $rule);

        return Inertia::render('Rule/Show', [
            'rule' => Rule::with('group')->find($rule->id),
            'can' => [
                'update' => $request->user()->can('rules.update'),
                'delete' => $request->user()->can('rules.delete'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Rule $rule
     * @return Response
     * @throws AuthorizationException
     */
    public function edit(Request $request, Rule $rule): Response
    {
        $this->authorize('rules.update', $rule);

        return Inertia::render('Rule/Edit', [
            'rule' => $rule,
            'groups' => Group::select('id', 'description')->get(),
            'can' => [
                'rules_update' => $request->user()->can('rules.update'),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateRuleRequest $request
     * @param Rule $rule
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(UpdateRuleRequest $request, Rule $rule): RedirectResponse
    {
        $this->authorize('rules.update', $rule);

        $data = $request->validated();

        try {
            $rule->update($data);
            return redirect()->route('rules.show', $rule)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rules.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Rule $rule
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Rule $rule): RedirectResponse
    {
        $this->authorize('rules.delete', $rule);

        try {
            $rule->delete();
            return redirect()->route('rules.index',)->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rules.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

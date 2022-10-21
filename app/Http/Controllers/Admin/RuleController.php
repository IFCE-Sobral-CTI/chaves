<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRuleRequest;
use App\Http\Requests\UpdateRuleRequest;
use App\Models\Rule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class RuleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $this->authorize('rules.viewAny', Rule::class);

        $result = Rule::search($request->term);

        return Inertia::render('Rule/Index', [
            'rules' => $result['data'],
            'count' => $result['count'],
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
            'can' => [
                'viewAny' => Auth::user()->can('rules.viewAny'),
                'view' => Auth::user()->can('rules.view'),
                'create' => Auth::user()->can('rules.create'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $this->authorize('rules.create', Rule::class);

        return Inertia::render('Rule/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRuleRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRuleRequest $request)
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
     * @param  \App\Models\Rule  $rule
     * @return \Inertia\Response
     */
    public function show(Rule $rule)
    {
        $this->authorize('rules.view', $rule);

        return Inertia::render('Rule/Show', [
            'rule' => $rule,
            'can' => [
                'update' => Auth::user()->can('rules.update'),
                'delete' => Auth::user()->can('rules.delete'),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Rule  $rule
     * @return \Inertia\Response
     */
    public function edit(Rule $rule)
    {
        $this->authorize('rules.update', $rule);

        return Inertia::render('Rule/Edit', [
            'rule' => $rule,
            'can' => [
                'rules_update' => Auth::user()->can('rules.update'),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRuleRequest  $request
     * @param  \App\Models\Rule  $rule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRuleRequest $request, Rule $rule)
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
     * @param  \App\Models\Rule  $rule
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Rule $rule)
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

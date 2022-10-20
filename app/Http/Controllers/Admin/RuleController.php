<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRuleRequest;
use App\Http\Requests\UpdateRuleRequest;
use App\Models\Rule;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RuleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('rules.showAll');
        
        $result = Rule::search($request->term);

        return Inertia::render('Rule/Index', [
            'rules' => $result['data'],
            'count' => $result['count'],
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Rule/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreRuleRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRuleRequest $request)
    {
        $data = $request->validated();

        try {
            $rule = Rule::create($data);
            return redirect()->route('rules.show', $rule)->with('flash', ['status' => 'success', 'message' => 'Registro salvo com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rules.index')->with('flash', ['status' => 'danger', 'message' => $e->message]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Rule  $rule
     * @return \Illuminate\Http\Response
     */
    public function show(Rule $rule)
    {
        return Inertia::render('Rule/Show', [
            'rule' => $rule,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Rule  $rule
     * @return \Illuminate\Http\Response
     */
    public function edit(Rule $rule)
    {
        return Inertia::render('Rule/Edit', [
            'rule' => $rule,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRuleRequest  $request
     * @param  \App\Models\Rule  $rule
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRuleRequest $request, Rule $rule)
    {
        $data = $request->validated();

        try {
            $rule->update($data);
            return redirect()->route('rules.show', $rule)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rules.index')->with('flash', ['status' => 'danger', 'message' => $e->message]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Rule  $rule
     * @return \Illuminate\Http\Response
     */
    public function destroy(Rule $rule)
    {
        try {
            $rule->delete();
            return redirect()->route('rules.index',)->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('rules.index')->with('flash', ['status' => 'danger', 'message' => $e->message]);
        }
    }
}

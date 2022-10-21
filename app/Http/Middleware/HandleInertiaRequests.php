<?php

namespace App\Http\Middleware;

use App\Models\Rule;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed[]
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user(),
            ],
            'title' => config('app.name'),
            'ziggy' => function () use ($request) {
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'flash' => function () use ($request) {
                return ['flash' => fn () => $request->session()->get('flash')];
            },
            'authorizations' => function () use ($request) {
                if (!$request->user())
                    return [];

                $rules = [];

                if ($request->user()->isAdmin()) {
                    foreach(Rule::where('control', 'like', '%viewAny%')->get() as $rule) {
                        $rules[str_replace('.', '_', $rule->control)] = true;
                    }
                } else {
                    foreach($request->user()->permission->rules()->where('control', 'like', '%viewAny%')->get() as $rule) {
                        $rules[str_replace('.', '_', $rule->control)] = true;
                    }
                }

                return $rules;
            },
        ]);
    }
}

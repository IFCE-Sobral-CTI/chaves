<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity as ModelsActivity;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        $this->authorize('activities.showAny', Activity::class);

        return Inertia::render('Activity/Index', array_merge(ModelsActivity::search($request), [
            'can' => [
                'view' => Auth::user()->can('activities.view'),
            ]
        ]));
    }

    /**
     * Display the specified resource.
     *
     * @param Activity $activity
     * @return Response
     * @throws AuthorizationException
     */
    public function show(Activity $activity): Response
    {
        $this->authorize('activities.view', $activity);

        $activity = Activity::with(['causer:id,name', 'subject'])->find($activity->id);

        //dd($activity);

        return Inertia::render('Activity/Show', [
            'activity' => $activity,
            'can' => [
                'delete' => Auth::user()->can('activities.delete'),
            ]
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Activity  $activity
     * @return Response
     */
    public function destroy(Activity $activity)
    {
        try {
            $activity->delete();
        } catch (Exception $e) {
            return to_route('activities.show', $activity)->with('flash', ['status' => 'error', 'message' => $e->getMessage()]);
        }

        return to_route('activities.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso.']);
    }
}

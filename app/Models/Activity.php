<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity as ModelsActivity;

class Activity extends ModelsActivity
{
    use CreatedAndUpdatedTimezone, HasFactory;

    public function scopeSearch(Builder $query, Request $request)
    {
        $query->with('causer')->where(function (Builder $q) use ($request) {
            $q->whereHas('causer', function ($query) use ($request) {
                return $query->where('name', 'LIKE', '%'.$request->term.'%');
            })
                ->orWhere('subject_type', 'LIKE', '%'.$request->term.'%');
        });

        $paginator = $query->orderBy('created_at', 'DESC')->paginate(config('app.pagination'))->appends(['term' => $request->term]);

        return [
            'count' => $paginator->total(),
            'activities' => $paginator,
            'page' => $request->page ?? 1,
            'termSearch' => $request->term,
        ];
    }
}

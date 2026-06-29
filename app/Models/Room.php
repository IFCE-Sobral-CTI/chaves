<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Room extends Model
{
    use CreatedAndUpdatedTimezone, HasFactory, LogsActivity;

    protected $fillable = [
        'description',
        'observation',
        'block_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
                'observation',
                'block.description',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function keys(): HasMany
    {
        return $this->hasMany(Key::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class);
    }

    public function scopeSearch($query, Request $request)
    {
        $query->with('block')
            ->where(function (Builder $q) use ($request) {
                $q->whereHas('employees', function (Builder $query) use ($request) {
                    return $query->where('name', 'like', "%{$request->term}%");
                })
                    ->orWhereHas('block', function (Builder $query) use ($request) {
                        return $query->where('description', 'like', "%{$request->term}%");
                    })
                    ->orWhere('description', 'like', "%{$request->term}%");
            });

        $paginator = $query->orderBy('description', 'ASC')->paginate(config('app.pagination'))->appends(['term' => $request->term, 'page' => $request->page]);

        return [
            'count' => $paginator->total(),
            'rooms' => $paginator,
            'page' => $request->page ?? 1,
            'termSearch' => $request->term,
        ];
    }
}

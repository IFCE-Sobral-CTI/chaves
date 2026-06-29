<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Key extends Model
{
    use CreatedAndUpdatedTimezone, HasFactory, LogsActivity;

    protected $fillable = [
        'description',
        'number',
        'observation',
        'room_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
                'number',
                'observation',
                'room.description',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function borrows(): BelongsToMany
    {
        return $this->belongsToMany(Borrow::class);
    }

    public function received(): BelongsToMany
    {
        return $this->belongsToMany(Received::class);
    }

    public function scopeBorrowed(Builder $keys): Builder
    {
        return $keys->whereIn('id', Borrow::KeysNotReceived());
    }

    public function scopeSearch($query, Request $request): array
    {
        $query->with('room')
            ->where(function (Builder $q) use ($request) {
                $q->whereHas('room', function (Builder $query) use ($request) {
                    $query->where('description', 'like', "%{$request->term}%")
                        ->orWhereHas('employees', function (Builder $query) use ($request) {
                            $query->where('name', 'like', "%{$request->term}%");
                        })
                        ->orWhereHas('block', function (Builder $query) use ($request) {
                            $query->where('description', 'like', "%{$request->term}%");
                        });
                })
                    ->orWhere('number', 'like', "%{$request->term}%")
                    ->orWhere('description', 'like', "%{$request->term}%");
            });

        $paginator = $query->orderBy('number', 'ASC')->paginate(config('app.pagination'))->appends(['term' => $request->term, 'page' => $request->page]);

        return [
            'count' => $paginator->total(),
            'keys' => $paginator,
            'page' => $request->page ?? 1,
            'termSearch' => $request->term,
        ];
    }

    public function borrowable_keys(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'borrowable_keys', 'key_id', 'employee_id');
    }

    public function scopeGetForSelect(Builder $query): Collection
    {
        return $query->with('room')->get()->map(function ($key) {
            return [
                'id' => $key->id,
                'name' => $key->number.' - '.$key->room->description,
            ];
        });
    }
}

<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Key extends Model
{
    use HasFactory, CreatedAndUpdatedTimezone, LogsActivity;

    protected $fillable = [
        'description',
        'number',
        'observation',
        'room_id',
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
                'number',
                'observation',
                'room.description'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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
        $keys_id = Key::get()->pluck('id')->toArray();

        return $keys->whereHas('borrows', function($borrows) use ($keys_id) {
            return $borrows->where('devolution', null)->whereHas('received', function($received) use ($keys_id) {
                return $received->whereHas('keys', function($k) use ($keys_id) {
                    return $k->whereIn('key_id', $keys_id);
                });
            });
        });
    }

    public function scopeSearch($query, Request $request): array
    {
        $query->with('room')
            ->whereHas('room', function(Builder $query) use ($request) {
                $query->where('description', 'like', "%{$request->term}%")
                    ->orWhereHas('employees', function(Builder $query) use ($request) {
                        $query->where('name', 'like', "%{$request->term}%");
                    })
                    ->orWhereHas('block', function(Builder $query) use ($request) {
                        $query->where('description', 'like', "%{$request->term}%");
                    });
            })
            ->orWhere('number', 'like', "%{$request->term}%")
            ->orWhere('description', 'like', "%{$request->term}%");

        return [
            'count' => $query->count(),
            'keys' => $query->orderBy('number', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $request->term, 'page' => $request->page]),
            'page' => $request->page?? 1,
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
                'name' => $key->number . ' - ' . $key->room->description,
            ];
        });
    }
}

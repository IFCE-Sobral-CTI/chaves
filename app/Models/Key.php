<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;

class Key extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'number',
        'observation',
        'room_id',
    ];

    protected $casts = [
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'created_at' => 'datetime:d/m/Y H:i:s',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function borrows(): BelongsToMany
    {
        return $this->belongsToMany(Borrow::class);
    }

    public function scopeBorrowed(Builder $query): Builder
    {
        return $query->whereHas('borrows', function($query) {
            return $query->where('devolution', null);
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
}

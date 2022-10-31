<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'observation',
        'block_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

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
            ->whereHas('employees', function(Builder $query) use ($request) {
                return $query->where('name', 'like', "%{$request->term}%");
            })
            ->orWhereHas('block', function(Builder $query) use ($request) {
                return $query->where('description', 'like', "%{$request->term}%");
            })
            ->orWhere('description', 'like', "%{$request->term}%");

        return [
            'count' => $query->count(),
            'rooms' => $query->orderBy('description', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $request->term, 'page' => $request->page]),
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
        ];
    }
}

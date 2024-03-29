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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Room extends Model
{
    use HasFactory, CreatedAndUpdatedTimezone, LogsActivity;

    protected $fillable = [
        'description',
        'observation',
        'block_id',
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
                'observation',
                'block.description'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
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

<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Spatie\Activitylog\LogOptions;

/**
 * @method search(string $term)
 * @property string description
 */
class Block extends Model
{
    use HasFactory, CreatedAndUpdatedTimezone;

    /**
     * @var array $fillable
     */
    protected $fillable = [
        'description'
    ];

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return HasMany
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * Search for description in resource
     */
    public function scopeSearch(Builder $query, Request $request): array
    {
        $query->where('description', 'like', "%{$request->term}%");

        return [
            'count' => $query->count(),
            'data' => $query->orderBy('description', 'ASC')
                            ->select('id', 'description')
                            ->paginate(env('APP_PAGINATION'))
                            ->appends(['term' => $request->term]),
        ];
    }
}

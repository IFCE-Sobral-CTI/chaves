<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @method search(string $term)
 *
 * @property string description
 */
class Block extends Model
{
    use CreatedAndUpdatedTimezone, HasFactory;

    /**
     * @var array
     */
    protected $fillable = [
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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

        $paginator = $query->orderBy('description', 'ASC')
            ->select('id', 'description')
            ->paginate(config('app.pagination'))
            ->appends(['term' => $request->term]);

        return [
            'count' => $paginator->total(),
            'data' => $paginator,
        ];
    }
}

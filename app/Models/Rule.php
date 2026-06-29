<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @method search(mixed $term)
 *
 * @property string description
 * @property string control
 * @property string group
 */
class Rule extends Model
{
    use CreatedAndUpdatedTimezone, HasFactory, LogsActivity;

    protected $fillable = [
        'description',
        'control',
        'group_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
                'control',
                'group.description',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function scopeSearch($query, $term): array
    {
        $query->with('group')
            ->where(function ($q) use ($term) {
                $q->where('description', 'like', "%{$term}%")
                    ->orWhere('control', 'like', "%{$term}%");
            });

        $paginator = $query->orderBy('control', 'ASC')->paginate(config('app.pagination'))->appends(['term' => $term]);

        return [
            'count' => $paginator->total(),
            'data' => $paginator,
        ];
    }

    public function scopeHasControl($query, $control): bool
    {
        return (bool) $query->where('control', $control)->exists();
    }
}

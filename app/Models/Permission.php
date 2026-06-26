<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @method static search(mixed $term)
 *
 * @property string description
 */
class Permission extends Model
{
    use CreatedAndUpdatedTimezone, HasFactory, LogsActivity;

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

    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(Rule::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeSearch($query, $term): array
    {
        $query->where('description', 'like', "%{$term}%");

        return [
            'count' => $query->count(),
            'data' => $query->orderBy('description', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $term]),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @method search(mixed $term)
 * @property string description
 * @property string control
 * @property string group
 */
class Rule extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'control',
        'group_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    /**
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * @return BelongsTo
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * @param $query
     * @param $term
     * @return array
     */
    public function scopeSearch($query, $term): array
    {
        $query->with('group')
            ->where('description', 'like', "%{$term}%")
            ->orWhere('control', 'like', "%{$term}%");

        return [
            'count' => $query->count(),
            'data' => $query->orderBy('control', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $term]),
        ];
    }

    /**
     * @param $query
     * @param $control
     * @return bool
     */
    public function scopeHasControl($query, $control): bool
    {
        return (bool) $query->where('control', $control)->count();
    }
}

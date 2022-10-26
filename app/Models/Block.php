<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method search(string $term)
 * @property string description
 */
class Block extends Model
{
    use HasFactory;

    protected $fillable = [
        'description'
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y h:i:s',
        'updated_at' => 'datetime:d/m/Y h:i:s',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function scopeSearch($query, $term = ''): array
    {
        $query->where('description', 'like', "%{$term}%");

        return [
            'count' => $query->count(),
            'data' => $query->orderBy('description', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $term]),
        ];
    }
}

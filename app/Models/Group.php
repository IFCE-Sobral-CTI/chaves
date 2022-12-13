<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class Group extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'description',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    /**
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    /**
     * @param Builder $query
     * @param Request $request
     * @return array
     */
    public function scopeSearch(Builder $query, Request $request): array
    {
        $query->where('description', 'like', "%{$request->term}%");

        return [
            'count' => $query->count(),
            'groups' => $query->orderBy('description', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $request->term]),
            'termSearch' => $request->term,
            'page', $request->page,
        ];
    }
}

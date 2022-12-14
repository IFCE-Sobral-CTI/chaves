<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method search(string $term)
 * @property string name
 * @property string email
 * @property int registry
 */
class Employee extends Model
{
    use HasFactory;

    const EMPLOYEE = 1;
    const COLLABORATOR = 2;
    const STUDENT = 3;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'registry',
        'email',
        'alternative_email',
        'tel',
        'valid_until',
        'observation',
        'type',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
        'valid_until' => 'datetime:d/m/Y',
    ];

    /**
     * @return HasMany
     */
    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    /**
     * @return BelongsToMany
     */
    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }

    /**
     * @param $query
     * @param $request
     * @return array
     */
    public function scopeSearch($query, $request)
    {
        $query->where('name', 'like', "%{$request->term}%")->orWhere('registry', 'like', "%{$request->term}%");

        return [
            'count' => $query->count(),
            'employees' => $query->orderBy('name', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $request->term]),
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
        ];
    }
}

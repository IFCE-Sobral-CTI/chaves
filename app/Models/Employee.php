<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected $fillable = [
        'name',
        'email',
        'registry',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

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

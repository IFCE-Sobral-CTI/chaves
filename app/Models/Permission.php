<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static search(mixed $term)
 * @property string description
 */
class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    public function rules()
    {
        return $this->belongsToMany(Rule::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function scopeSearch($query, $term)
    {
        $query->where('description', 'like', "%{$term}%");

        return [
            'count' => $query->count(),
            'data' => $query->orderBy('description', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $term]),
        ];
    }
}

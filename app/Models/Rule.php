<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rule extends Model
{
    use HasFactory;

    protected $fillable = [
        'description',
        'control',
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function scopeSearch($query, $term)
    {
        $query->where('description', 'like', "%{$term}%")
            ->orWhere('control', 'like', "%{$term}%");

        return [
            'count' => $query->count(),
            'data' => $query->orderBy('description', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $term]),
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;

class Borrow extends Model
{
    use HasFactory;

    protected $fillable = [
        'devolution',
        'observation',
        'employee_id',
    ];

    protected $casts = [
        'devolution' => 'datetime:d/m/Y H:i:s',
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function keys(): BelongsToMany
    {
        return $this->belongsToMany(Key::class);
    }

    public function scopeSearch(Builder $query, Request $request): array
    {
        $query->with('employee')->whereHas('employee', function(Builder $query) use ($request) {
            $query->where('name', 'like', "%{$request->term}%")
                ->orWhere('registry', 'like', "%{$request->term}%");
        });

        return [
            'count' => $query->count(),
            'borrows' => $query->orderBy('id', 'desc')->paginate(env('APP_PAGINATION'))->appends(['term' => $request->term]),
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
        ];
    }
}

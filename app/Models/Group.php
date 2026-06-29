<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Group extends Model
{
    use CreatedAndUpdatedTimezone, HasFactory, LogsActivity;

    /**
     * @var string[]
     */
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

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class);
    }

    public function scopeSearch(Builder $query, Request $request): array
    {
        $query->where('description', 'like', "%{$request->term}%");

        $paginator = $query->orderBy('description', 'ASC')->paginate(config('app.pagination'))->appends(['term' => $request->term ?? '', 'page' => $request->page ?? 1]);

        return [
            'count' => $paginator->total(),
            'groups' => $paginator,
            'termSearch' => $request->term,
            'page' => $request->page ?? 1,
        ];
    }
}

<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @method search(string $term)
 *
 * @property string name
 * @property string email
 * @property int registry
 */
class Employee extends Model
{
    use CreatedAndUpdatedTimezone, HasFactory, LogsActivity;

    const EMPLOYEE = 1;

    const COLLABORATOR = 2;

    const STUDENT = 3;

    const EXTERNAL = 4;

    const TYPES = [
        ['value' => Employee::EMPLOYEE, 'label' => 'Servidor'],
        ['value' => Employee::COLLABORATOR, 'label' => 'Colaborador'],
        ['value' => Employee::STUDENT, 'label' => 'Discente'],
        ['value' => Employee::EXTERNAL, 'label' => 'Externo'],
    ];

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'registry',
                'email',
                'alternative_email',
                'tel',
                'valid_until',
                'observation',
                'type',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getValidUntilAttribute(?string $date = null): ?string
    {
        if (is_null($date)) {
            return null;
        }

        return Carbon::parse($date)->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s');
    }

    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }

    /**
     * @return array
     */
    public function scopeSearch(Builder $query, Request $request)
    {
        $query->with(['borrowable_keys' => ['room']])
            ->where(function (Builder $q) use ($request) {
                $q->where('name', 'like', "%{$request->term}%")
                    ->orWhere('registry', 'like', "%{$request->term}%");
            });

        $paginator = $query->orderBy('name', 'ASC')->paginate(config('app.pagination'))->appends(['term' => $request->term]);

        return [
            'count' => $paginator->total(),
            'employees' => $paginator,
            'page' => $request->page ?? 1,
            'termSearch' => $request->term,
        ];
    }

    public function borrowable_keys(): BelongsToMany
    {
        return $this->belongsToMany(Key::class, 'borrowable_keys', 'employee_id', 'key_id');
    }

    public function scopeGetActiveEmployees(Builder $query)
    {
        return $query->where('valid_until', '>=', now())
            ->orWhere('valid_until', null)
            ->orderBy('name', 'ASC')
            ->get();
    }
}

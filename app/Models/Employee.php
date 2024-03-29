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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @method search(string $term)
 * @property string name
 * @property string email
 * @property int registry
 */
class Employee extends Model
{
    use HasFactory, CreatedAndUpdatedTimezone, LogsActivity;

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

    /**
     * @return LogOptions
     */
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
            ->dontSubmitEmptyLogs();
    }

    /**
     * @param string $date
     * @return string
     */
    public function getValidUntilAttribute(string $date = null): ?string
    {
        if (is_null($date))
            return null;

        return Carbon::parse($date)->setTimezone(env('APP_TIMEZONE'))->format('d/m/Y H:i:s');
    }

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
    public function scopeSearch(Builder $query, Request $request)
    {
        $query->with(['borrowable_keys' => ['room']])->where('name', 'like', "%{$request->term}%")
            ->orWhere('registry', 'like', "%{$request->term}%");

        return [
            'count' => $query->count(),
            'employees' => $query->orderBy('name', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $request->term]),
            'page' => $request->page?? 1,
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

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\CausesActivity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class User extends Authenticatable
{
    use CausesActivity, CreatedAndUpdatedTimezone, HasApiTokens, HasFactory, LogsActivity, Notifiable;

    const ACTIVE = 1;

    const INACTIVE = 0;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'registry',
        'status',
        'permission_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Returns the date in the defined timezone
     */
    public function getEmailVerifiedAtAttribute(?string $date = null): ?string
    {
        if (is_null($date)) {
            return null;
        }

        return Carbon::parse($date)->setTimezone('America/Fortaleza')->format('d/m/Y H:i:s');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'email',
                'registry',
                'permission.name',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->permission?->is_admin;
    }

    public function hasRule($rule): bool
    {
        return (bool) $this->permission?->rules()->hasControl($rule);
    }

    /**
     * @param  string  $term
     */
    public function scopeSearch($query, $term = ''): array
    {
        $query->with('permission')
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });

        $paginator = $query->orderBy('name', 'ASC')->paginate(config('app.pagination'))->appends(['term' => $term]);

        return [
            'count' => $paginator->total(),
            'data' => $paginator,
        ];
    }
}

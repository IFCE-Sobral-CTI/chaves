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

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CreatedAndUpdatedTimezone;

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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime:d/m/Y H:i:s',
    ];

    /**
     * Returns the date in the defined timezone
     */
    public function getEmailVerifiedAtAttribute(string $date): string
    {
        return Carbon::parse($date)->setTimezone('America/Fortaleza')->format('d/m/Y H:i:s');
    }


    public function borrows(): HasMany
    {
        return $this->hasMany(Borrow::class);
    }

    /**
     * @return BelongsTo
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->permission->description === 'Administrador';
    }

    /**
     * @param $rule
     * @return bool
     */
    public function hasRule($rule): bool
    {
        return $this->permission->rules()->hasControl($rule);
    }

    /**
     * @param $query
     * @param string $term
     * @return array
     */
    public function scopeSearch($query, $term = ''): array
    {
        $query->with('permission')
            ->where('name', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%");

        return [
            'count' => $query->count(),
            'data' => $query->orderBy('name', 'ASC')->paginate(env('APP_PAGINATION'))->appends(['term' => $term]),
        ];
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'created_at' => 'datetime:d/m/Y H:i:s',
        'updated_at' => 'datetime:d/m/Y H:i:s',
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

    public function isAdmin()
    {
        return $this->permission->description === 'Administrador';
    }

    public function hasRule($rule)
    {
        return $this->permission->rules()->get()->contains($rule);
    }

    public function scopeSearch($query, $term = '')
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

<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Received extends Model
{
    use CreatedAndUpdatedTimezone, HasFactory, LogsActivity;

    protected $fillable = [
        'receiver',
        'borrow_id',
        'user_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'keys',
                'receiver',
                'borrow.employee.name',
                'user.name',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function borrow(): BelongsTo
    {
        return $this->belongsTo(Borrow::class);
    }

    public function keys(): BelongsToMany
    {
        return $this->belongsToMany(Key::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receivedKeys()
    {
        $list = collect([]);

        $this->keys->map(function ($key) use ($list) {
            $list->push($key->id);
        });

        return $list->unique()->toArray();
    }
}

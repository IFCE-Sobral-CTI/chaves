<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    ];

    public function getCreatedAtAttribute($date)
    {
        return Carbon::parse($date)->setTimezone('America/Fortaleza')->format('d/m/Y H:i:s');
    }

    public function getUpdatedAtAttribute($date)
    {
        return Carbon::parse($date)->setTimezone('America/Fortaleza')->format('d/m/Y H:i:s');
    }

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

    public function scopeDataChart(Builder $query): array
    {
        $data = [];
        foreach($query->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))->groupBy('date')->take(5)->get() as $borrow) {
            $data[] = [Carbon::parse($borrow->date)->format('d/m/y'), $borrow->count];
        }

        return array_merge([['Datas', 'Empréstimos']], $data);
    }

    public function scopeDataChart2(Builder $query): array
    {
        $data = [];
        $start = now()->subDays(6)->startOfDay();
        $end = now()->subDays(6)->endOfDay();

        for($i = $count = 0; $i <= 6; $i++, $start->addDay(), $end->addDay(), $count = 0) {
            foreach($query->whereBetween('created_at', [$start, $end])->get() as $borrow) {
                $count += $borrow->keys->count();
            }

            $data[] = [$start->format('d/m'), $count];
        }

        return array_merge([['Datas', 'Chaves']], $data);
    }
}

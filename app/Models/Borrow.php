<?php

namespace App\Models;

use App\Http\Traits\CreatedAndUpdatedTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @method static search(Request $request)[]
 * @method dataChart(): array
 * @method dataChart2(): array
 * @method reportByDate(Request $request): array
 *
 * @property Carbon devolution
 * @property string observation
 * @property Employee employee
 * @property User user
 * @property User received_by
 */
class Borrow extends Model
{
    use HasFactory, CreatedAndUpdatedTimezone;

    /**
     * @var array $fillable
     */
    protected $fillable = [
        'devolution',
        'observation',
        'employee_id',
        'user_id',
    ];

    /**
     * Returns the date in the defined timezone
     */
    public function getDevolutionAttribute(string $date): string
    {
        return Carbon::parse($date)->setTimezone('America/Fortaleza')->format('d/m/Y H:i:s');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsToMany
     */
    public function keys(): BelongsToMany
    {
        return $this->belongsToMany(Key::class);
    }

    /**
     * @return HasMany
     */
    public function received(): HasMany
    {
        return $this->hasMany(Received::class);
    }


    /**
     * Get loan data with optional search
     *
     * @param Builder $query
     * @param Request $request
     *
     * @return array
     */
    public function scopeSearch(Builder $query, Request $request): array
    {
        $query->with(['employee', 'user', 'received'])->whereHas('employee', function(Builder $query) use ($request) {
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

    /**
     * Get data for chart 2
     *
     * @param Builder $query
     *
     * @return array
     */
    public function scopeDataChart(Builder $query): array
    {
        $data = [];

        foreach($query->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))->groupBy('date')->take(5)->get() as $borrow) {
            $data[] = [Carbon::parse($borrow->date)->format('d/m/y'), $borrow->count];
        }

        return array_merge([['Datas', 'Empréstimos']], $data);
    }

    /**
     * Get data for chart 2
     *
     * @param Builder $query
     *
     * @return array
     */
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

    /**
     * Get data of borrow by filters
     *
     * @param Builder $query
     * @param Request $request
     *
     * @return array
     */
    public function scopeReportByDate(Builder $query, Request $request): array
    {
        $query->with(['employee', 'keys', 'user', 'received' => ['keys', 'user']]);

        $this->filterByDate($query, $request);
        $this->filterBySituation($query, $request);
        $this->filterByEmployee($query, $request);
        $this->filterByUser($query, $request);

        return [
            'count' => $query->count(),
            'borrows' => $query->orderBy('created_at', 'desc')->paginate(env('APP_PAGINATION'))->appends($request->all()),
            'page' => $request->page?? 1,
            'filter' => ($request->has('start') || $request->has('end') || $request->has('employee') || $request->has('user') || $request->has('situation')),
        ];
    }

    /**
     * Filter by user for borrow query
     *
     * @param Builder $query (pointer for query)
     * @param Request $request
     */
    private function filterByUser(Builder &$query, Request $request): void
    {
        $query->when(!is_null($request->user), function($query) use ($request) {
            return $query->whereHas('user', function($query) use ($request) {
                return $query->where('id', $request->user);
            });
        });
    }

    /**
     * Filter by employee for borrow query
     *
     * @param Builder $query (pointer for query)
     * @param Request $request
     *
     * @return void
     */
    private function filterByEmployee(Builder &$query, Request $request): void
    {
        $query->when(!is_null($request->employee), function($query) use ($request) {
            return $query->whereHas('employee', function($query) use ($request) {
                return $query->where('id', $request->employee);
            });
        });
    }

    /**
     * Filter by situation for borrow query
     *
     * @param Builder $query (pointer for query)
     * @param Request $request
     *
     * @return void
     */
    private function filterBySituation(Builder &$query, Request $request): void
    {
        switch($request->situation) {
            case 1:
                $query->where('devolution', '!=', null);
                break;
            case 2:
                $query->where('devolution', null)->where('created_at', '>=', now()->subDay());
                break;
            case 3:
                $query->where('devolution', null)->where('created_at', '<', now()->subDay());
                break;
            default:
                break;
        }
    }

    /**
     * Filter by date for borrow query
     *
     * @param Builder $query (pointer for query)
     * @param Request $request
     *
     * @return void
     */
    private function filterByDate(Builder &$query, Request $request): void
    {
        if (!is_null($request->start))
            $start = Carbon::parse($request->start)->startOfDay();

        if (!is_null($request->end))
            $end = Carbon::parse($request->end)->endOfDay();

        if (isset($start))
            $query->whereBetween('created_at', [$start, $end?? now()])->get();
    }

    public function receivedKeys(): array
    {
        $list = collect([]);

        $this->received->map(function($item) use (&$list) {
            $list = $list->merge($item->receivedKeys());
        });

        return $list->unique()->toArray();
    }

    public function scopeKeysInBorrow(Builder $query): array
    {
        $keysInBorrow = collect([]);
        $keysReceived = collect([]);

        $query->get()->map(function($borrow) use (&$keysInBorrow, &$keysReceived) {
            $keysReceived = $keysReceived->merge($borrow->receivedKeys());
            $borrow->keys->map(function($key) use (&$keysInBorrow) {
                $keysInBorrow->push($key->id);
            });
        });

        return $keysInBorrow->unique()->diff($keysReceived->unique()->all())->all();
    }
}

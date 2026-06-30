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
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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
    use CreatedAndUpdatedTimezone, HasFactory, LogsActivity;

    const OVERDUE_AFTER_HOURS = 24;

    /**
     * @var array
     */
    protected $fillable = [
        'devolution',
        'observation',
        'employee_id',
        'user_id',
        'received_by',
        'returned_by',
    ];

    protected $appends = ['situation'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'devolution',
                'observation',
                'employee.name',
                'user.name',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Returns the date in the defined timezone
     */
    public function getDevolutionAttribute(?string $date = null): ?string
    {
        if (is_null($date)) {
            return null;
        }

        return Carbon::parse($date)->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s');
    }

    /**
     * Get situation: devolvido, aberto, or atrasado.
     */
    public function getSituationAttribute(): string
    {
        $devolution = $this->getRawOriginal('devolution');
        if ($devolution !== null) {
            return 'devolvido';
        }

        $createdAt = Carbon::parse($this->getRawOriginal('created_at'));
        if ($createdAt->addHours(self::OVERDUE_AFTER_HOURS)->isPast()) {
            return 'atrasado';
        }

        return 'aberto';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function keys(): BelongsToMany
    {
        return $this->belongsToMany(Key::class);
    }

    public function received(): HasMany
    {
        return $this->hasMany(Received::class);
    }

    /**
     * Get loan data with optional search
     */
    public function scopeSearch(Builder $query, Request $request): array
    {
        if (! $request->term) {
            $query->where('devolution', null);
        }

        $query->with(['employee', 'user', 'received', 'keys' => ['room']])
            ->whereHas('employee', function (Builder $query) use ($request) {
                $query->where(function (Builder $q) use ($request) {
                    $q->where('name', 'like', "%{$request->term}%")
                        ->orWhere('registry', 'like', "%{$request->term}%");
                });
            });

        $paginator = $query->orderBy('id', 'desc')->paginate(config('app.pagination'))->appends(['term' => $request->term]);

        return [
            'count' => $paginator->total(),
            'borrows' => $paginator,
            'page' => $request->page ?? 1,
            'termSearch' => $request->term,
        ];
    }

    /**
     * Get borrow counts per day for the last 7 days.
     * Returns array of objects: [{ label, value }, ...]
     */
    public function scopeDataChart(Builder $query): array
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->endOfDay();

        $borrows = $query->clone()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->pluck('count', 'date');

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $dateKey = $date->format('Y-m-d');
            $data[] = [
                'label' => $date->format('d/m'),
                'value' => (int) ($borrows[$dateKey] ?? 0),
            ];
        }

        return $data;
    }

    /**
     * Get key counts loaned per day for the last 7 days.
     * Returns array of objects: [{ label, value }, ...]
     */
    public function scopeDataChart2(Builder $query): array
    {
        $start = now()->subDays(6)->startOfDay();
        $end = now()->endOfDay();

        $borrowsWithKeys = $query->clone()
            ->withCount('keys')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $dayStart = now()->subDays($i)->startOfDay();
            $dayEnd = now()->subDays($i)->endOfDay();

            $count = $borrowsWithKeys
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->sum('keys_count');

            $data[] = [
                'label' => $dayStart->format('d/m'),
                'value' => (int) $count,
            ];
        }

        return $data;
    }

    /**
     * Get borrow counts grouped by employee type.
     * Returns array of objects: [{ label, value }, ...]
     */
    public function scopeBorrowsByEmployeeType(Builder $query): array
    {
        $counts = $query->clone()
            ->join('employees', 'borrows.employee_id', '=', 'employees.id')
            ->select('employees.type', DB::raw('COUNT(*) as count'))
            ->groupBy('employees.type')
            ->pluck('count', 'type');

        $data = [];
        foreach (Employee::TYPES as $type) {
            $data[] = [
                'label' => $type['label'],
                'value' => (int) ($counts[$type['value']] ?? 0),
            ];
        }

        return $data;
    }

    /**
     * Get top 5 most borrowed rooms.
     * Returns array of objects: [{ label, value }, ...]
     */
    public function scopeTopRooms(Builder $query): array
    {
        $results = $query->clone()
            ->join('borrow_key', 'borrows.id', '=', 'borrow_key.borrow_id')
            ->join('keys', 'borrow_key.key_id', '=', 'keys.id')
            ->join('rooms', 'keys.room_id', '=', 'rooms.id')
            ->select('rooms.description as label', DB::raw('COUNT(*) as value'))
            ->groupBy('rooms.id', 'rooms.description')
            ->orderByDesc('value')
            ->limit(5)
            ->get();

        return $results->map(fn ($item) => [
            'label' => $item->label,
            'value' => (int) $item->value,
        ])->toArray();
    }

    /**
     * Apply all report filters to a query builder.
     */
    public function scopeApplyReportFilters(Builder $query, Request $request): Builder
    {
        $this->filterByDate($query, $request);
        $this->filterBySituation($query, $request);
        $this->filterByEmployee($query, $request);
        $this->filterByUser($query, $request);
        $this->filterByBlock($query, $request);
        $this->filterByRoom($query, $request);
        $this->filterByKey($query, $request);

        return $query;
    }

    /**
     * Get data of borrow by filters
     */
    public function scopeReportByDate(Builder $query, Request $request): array
    {
        $query->with(['employee', 'keys' => ['room' => ['block']], 'user', 'received' => ['keys', 'user']]);

        $this->scopeApplyReportFilters($query, $request);

        $paginator = $query->orderBy('created_at', 'desc')->paginate(config('app.pagination'))->appends($request->all());

        return [
            'count' => $paginator->total(),
            'borrows' => $paginator,
            'page' => $request->page ?? 1,
            'filter' => ($request->has('start') || $request->has('end') || $request->has('employee') || $request->has('user') || $request->has('situation') || $request->has('block') || $request->has('room') || $request->has('key')),
        ];
    }

    /**
     * Get summary counts for the report filters.
     */
    public function scopeReportSummary(Builder $query, Request $request): array
    {
        $this->filterByDate($query, $request);
        $this->filterByEmployee($query, $request);
        $this->filterByUser($query, $request);
        $this->filterByBlock($query, $request);
        $this->filterByRoom($query, $request);
        $this->filterByKey($query, $request);

        $total = $query->clone()->count();
        $returned = $query->clone()->where('devolution', '!=', null)->count();
        $open = $query->clone()->where('devolution', null)->where('created_at', '>=', now()->subHours(self::OVERDUE_AFTER_HOURS))->count();
        $overdue = $query->clone()->where('devolution', null)->where('created_at', '<', now()->subHours(self::OVERDUE_AFTER_HOURS))->count();
        $keysMoved = $query->clone()
            ->join('borrow_key', 'borrows.id', '=', 'borrow_key.borrow_id')
            ->count('borrow_key.key_id');

        return [
            'total' => $total,
            'returned' => $returned,
            'open' => $open,
            'overdue' => $overdue,
            'keysMoved' => $keysMoved,
        ];
    }

    /**
     * Filter by user for borrow query
     *
     * @param  Builder  $query  (pointer for query)
     */
    private function filterByUser(Builder &$query, Request $request): void
    {
        $query->when(! is_null($request->user), function ($query) use ($request) {
            return $query->whereHas('user', function ($query) use ($request) {
                return $query->where('id', $request->user);
            });
        });
    }

    /**
     * Filter by employee for borrow query
     *
     * @param  Builder  $query  (pointer for query)
     */
    private function filterByEmployee(Builder &$query, Request $request): void
    {
        $query->when(! is_null($request->employee), function ($query) use ($request) {
            return $query->whereHas('employee', function ($query) use ($request) {
                return $query->where('id', $request->employee);
            });
        });
    }

    /**
     * Filter by situation for borrow query
     *
     * @param  Builder  $query  (pointer for query)
     */
    private function filterBySituation(Builder &$query, Request $request): void
    {
        switch ($request->situation) {
            case 1:
                $query->where('devolution', '!=', null);
                break;
            case 2:
                $query->where('devolution', null)->where('borrows.created_at', '>=', now()->subHours(self::OVERDUE_AFTER_HOURS));
                break;
            case 3:
                $query->where('devolution', null)->where('borrows.created_at', '<', now()->subHours(self::OVERDUE_AFTER_HOURS));
                break;
            default:
                break;
        }
    }

    /**
     * Filter by date for borrow query
     *
     * @param  Builder  $query  (pointer for query)
     */
    private function filterByDate(Builder &$query, Request $request): void
    {
        $start = null;
        $end = null;

        if (! is_null($request->start)) {
            $start = Carbon::parse($request->start)->startOfDay();
        }

        if (! is_null($request->end)) {
            $end = Carbon::parse($request->end)->endOfDay();
        }

        if ($start && $end) {
            $query->whereBetween('borrows.created_at', [$start, $end]);
        } elseif ($start) {
            $query->where('borrows.created_at', '>=', $start);
        } elseif ($end) {
            $query->where('borrows.created_at', '<=', $end);
        }
    }

    /**
     * Filter by block for borrow query
     */
    private function filterByBlock(Builder &$query, Request $request): void
    {
        $query->when(! is_null($request->block), function ($query) use ($request) {
            return $query->whereHas('keys.room.block', function ($query) use ($request) {
                return $query->where('blocks.id', $request->block);
            });
        });
    }

    /**
     * Filter by room for borrow query
     */
    private function filterByRoom(Builder &$query, Request $request): void
    {
        $query->when(! is_null($request->room), function ($query) use ($request) {
            return $query->whereHas('keys.room', function ($query) use ($request) {
                return $query->where('rooms.id', $request->room);
            });
        });
    }

    /**
     * Filter by key for borrow query
     */
    private function filterByKey(Builder &$query, Request $request): void
    {
        $query->when(! is_null($request->key), function ($query) use ($request) {
            return $query->whereHas('keys', function ($query) use ($request) {
                return $query->where('keys.id', $request->key);
            });
        });
    }

    public function receivedKeys(): array
    {
        $list = collect([]);

        $this->received->map(function ($item) use (&$list) {
            $list = $list->merge($item->keys->pluck('id'));
        });

        return $list->unique()->toArray();
    }

    /**
     * Get keys that have not been returned.
     * Returns key IDs that are currently on open borrows
     * and have not yet been marked as received.
     */
    public function scopeKeysNotReceived(Builder $query): array
    {
        $activeBorrowIds = $query->where('devolution', null)->pluck('id');

        if ($activeBorrowIds->isEmpty()) {
            return [];
        }

        $receivedKeyIds = DB::table('key_received')
            ->join('receiveds', 'key_received.received_id', '=', 'receiveds.id')
            ->whereIn('receiveds.borrow_id', $activeBorrowIds)
            ->pluck('key_received.key_id');

        return DB::table('borrow_key')
            ->whereIn('borrow_id', $activeBorrowIds)
            ->whereNotIn('key_id', $receivedKeyIds)
            ->pluck('key_id')
            ->unique()
            ->values()
            ->toArray();
    }
}

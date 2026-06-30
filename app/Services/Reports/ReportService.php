<?php

namespace App\Services\Reports;

use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Received;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * R3 — Room usage report.
     *
     * @return array{rooms: LengthAwarePaginator, chart: array<int, array{label: string, value: int}>}
     */
    public function rooms(Request $request): array
    {
        $start = $request->start ? Carbon::parse($request->start)->startOfDay() : null;
        $end = $request->end ? Carbon::parse($request->end)->endOfDay() : null;

        $query = Borrow::query()
            ->join('borrow_key', 'borrows.id', '=', 'borrow_key.borrow_id')
            ->join('keys', 'borrow_key.key_id', '=', 'keys.id')
            ->join('rooms', 'keys.room_id', '=', 'rooms.id')
            ->join('blocks', 'rooms.block_id', '=', 'blocks.id')
            ->select(
                'rooms.id',
                'rooms.description as room',
                'blocks.description as block',
                DB::raw('COUNT(DISTINCT borrows.id) as borrow_count'),
                DB::raw('COUNT(borrow_key.key_id) as key_count')
            )
            ->groupBy('rooms.id', 'rooms.description', 'blocks.description')
            ->orderByDesc('borrow_count');

        if ($start && $end) {
            $query->whereBetween('borrows.created_at', [$start, $end]);
        } elseif ($start) {
            $query->where('borrows.created_at', '>=', $start);
        } elseif ($end) {
            $query->where('borrows.created_at', '<=', $end);
        }

        $paginator = $query->paginate(config('app.pagination'))->appends($request->all());

        $chart = collect($paginator->items())->map(fn ($item) => [
            'label' => $item->room,
            'value' => (int) $item->borrow_count,
        ])->toArray();

        return [
            'rooms' => $paginator,
            'chart' => $chart,
        ];
    }

    /**
     * R4 — Employee usage report.
     *
     * @return array{employees: LengthAwarePaginator, chart: array<int, array{label: string, value: int}>}
     */
    public function employees(Request $request): array
    {
        $start = $request->start ? Carbon::parse($request->start)->startOfDay() : null;
        $end = $request->end ? Carbon::parse($request->end)->endOfDay() : null;

        $query = Borrow::query()
            ->join('employees', 'borrows.employee_id', '=', 'employees.id')
            ->leftJoin('borrow_key', 'borrows.id', '=', 'borrow_key.borrow_id')
            ->select(
                'employees.id',
                'employees.name',
                'employees.type',
                'employees.valid_until',
                DB::raw('COUNT(DISTINCT borrows.id) as borrow_count'),
                DB::raw('COUNT(borrow_key.key_id) as key_count'),
                DB::raw('SUM(CASE WHEN borrows.devolution IS NULL AND borrows.created_at < ? THEN 1 ELSE 0 END) as overdue_count')
            )
            ->addBinding(now()->subHours(Borrow::OVERDUE_AFTER_HOURS), 'select')
            ->groupBy('employees.id', 'employees.name', 'employees.type', 'employees.valid_until')
            ->orderByDesc('borrow_count');

        if ($start && $end) {
            $query->whereBetween('borrows.created_at', [$start, $end]);
        } elseif ($start) {
            $query->where('borrows.created_at', '>=', $start);
        } elseif ($end) {
            $query->where('borrows.created_at', '<=', $end);
        }

        if ($request->type) {
            $query->where('employees.type', $request->type);
        }

        $paginator = $query->paginate(config('app.pagination'))->appends($request->all());

        $employees = $paginator->through(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'type' => collect(Employee::TYPES)->firstWhere('value', $item->type)['label'] ?? 'Desconhecido',
                'valid_until' => $item->valid_until,
                'borrow_count' => (int) $item->borrow_count,
                'key_count' => (int) $item->key_count,
                'overdue_count' => (int) $item->overdue_count,
            ];
        });

        // Chart data by employee type (uses the same date filter)
        $chartQuery = Borrow::query()
            ->join('employees', 'borrows.employee_id', '=', 'employees.id')
            ->select('employees.type', DB::raw('COUNT(*) as count'))
            ->groupBy('employees.type');

        if ($start && $end) {
            $chartQuery->whereBetween('borrows.created_at', [$start, $end]);
        } elseif ($start) {
            $chartQuery->where('borrows.created_at', '>=', $start);
        } elseif ($end) {
            $chartQuery->where('borrows.created_at', '<=', $end);
        }

        if ($request->type) {
            $chartQuery->where('employees.type', $request->type);
        }

        $counts = $chartQuery->pluck('count', 'type');
        $chart = [];
        foreach (Employee::TYPES as $type) {
            $chart[] = [
                'label' => $type['label'],
                'value' => (int) ($counts[$type['value']] ?? 0),
            ];
        }

        return [
            'employees' => $employees,
            'chart' => $chart,
        ];
    }

    /**
     * R5 — Staff productivity report.
     *
     * @return array{staff: LengthAwarePaginator}
     */
    public function staff(Request $request): array
    {
        $start = $request->start ? Carbon::parse($request->start)->startOfDay() : null;
        $end = $request->end ? Carbon::parse($request->end)->endOfDay() : null;

        $borrowsQuery = Borrow::query()
            ->select('user_id', DB::raw('COUNT(*) as delivery_count'))
            ->groupBy('user_id');

        $receivedQuery = Received::query()
            ->select('user_id', DB::raw('COUNT(*) as receipt_count'))
            ->groupBy('user_id');

        if ($start && $end) {
            $borrowsQuery->whereBetween('created_at', [$start, $end]);
            $receivedQuery->whereBetween('created_at', [$start, $end]);
        } elseif ($start) {
            $borrowsQuery->where('created_at', '>=', $start);
            $receivedQuery->where('created_at', '>=', $start);
        } elseif ($end) {
            $borrowsQuery->where('created_at', '<=', $end);
            $receivedQuery->where('created_at', '<=', $end);
        }

        $borrowCounts = $borrowsQuery->pluck('delivery_count', 'user_id');
        $receiptCounts = $receivedQuery->pluck('receipt_count', 'user_id');

        $userIds = $borrowCounts->keys()->merge($receiptCounts->keys())->unique()->values();

        $users = User::whereIn('id', $userIds)
            ->orderBy('name')
            ->paginate(config('app.pagination'))
            ->appends($request->all());

        $staff = $users->through(function ($user) use ($borrowCounts, $receiptCounts) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'delivery_count' => (int) ($borrowCounts[$user->id] ?? 0),
                'receipt_count' => (int) ($receiptCounts[$user->id] ?? 0),
            ];
        });

        return [
            'staff' => $staff,
        ];
    }

    /**
     * R6 — Turnaround report.
     *
     * @return array{turnaround: array, summary: array}
     */
    public function turnaround(Request $request): array
    {
        $start = $request->start ? Carbon::parse($request->start)->startOfDay() : null;
        $end = $request->end ? Carbon::parse($request->end)->endOfDay() : null;

        $query = Borrow::query()
            ->whereNotNull('devolution')
            ->join('employees', 'borrows.employee_id', '=', 'employees.id')
            ->leftJoin('borrow_key', 'borrows.id', '=', 'borrow_key.borrow_id')
            ->leftJoin('keys', 'borrow_key.key_id', '=', 'keys.id')
            ->leftJoin('rooms', 'keys.room_id', '=', 'rooms.id')
            ->select(
                'borrows.id',
                'borrows.created_at',
                'borrows.devolution',
                'employees.type as employee_type',
                'rooms.description as room'
            );

        if ($start && $end) {
            $query->whereBetween('borrows.created_at', [$start, $end]);
        } elseif ($start) {
            $query->where('borrows.created_at', '>=', $start);
        } elseif ($end) {
            $query->where('borrows.created_at', '<=', $end);
        }

        $rows = $query->get();

        $byRoom = [];
        $byType = [];
        $totalHours = 0;
        $count = 0;
        $within24h = 0;

        foreach ($rows as $row) {
            $created = Carbon::parse($row->getRawOriginal('created_at'));
            $devolution = Carbon::parse($row->getRawOriginal('devolution'));
            $hours = $created->diffInHours($devolution);

            $totalHours += $hours;
            $count++;

            if ($hours <= Borrow::OVERDUE_AFTER_HOURS) {
                $within24h++;
            }

            $typeLabel = collect(Employee::TYPES)->firstWhere('value', $row->employee_type)['label'] ?? 'Desconhecido';

            if (! isset($byType[$typeLabel])) {
                $byType[$typeLabel] = ['hours' => 0, 'count' => 0];
            }
            $byType[$typeLabel]['hours'] += $hours;
            $byType[$typeLabel]['count']++;

            $roomName = $row->room ?? 'Não informada';
            if (! isset($byRoom[$roomName])) {
                $byRoom[$roomName] = ['hours' => 0, 'count' => 0];
            }
            $byRoom[$roomName]['hours'] += $hours;
            $byRoom[$roomName]['count']++;
        }

        $turnaround = [];
        foreach ($byType as $type => $data) {
            $turnaround[] = [
                'category' => 'Tipo de mutuário',
                'dimension' => $type,
                'avg_hours' => $data['count'] > 0 ? round($data['hours'] / $data['count'], 1) : 0,
                'count' => $data['count'],
            ];
        }
        foreach ($byRoom as $room => $data) {
            $turnaround[] = [
                'category' => 'Sala',
                'dimension' => $room,
                'avg_hours' => $data['count'] > 0 ? round($data['hours'] / $data['count'], 1) : 0,
                'count' => $data['count'],
            ];
        }

        $summary = [
            'total' => $count,
            'avg_hours' => $count > 0 ? round($totalHours / $count, 1) : 0,
            'within_24h' => $within24h,
            'overdue' => $count - $within24h,
        ];

        return [
            'turnaround' => $turnaround,
            'summary' => $summary,
        ];
    }
}

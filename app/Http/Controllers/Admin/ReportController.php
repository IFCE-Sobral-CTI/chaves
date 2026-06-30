<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Room;
use App\Models\User;
use App\Services\Reports\ReportService;
use App\Support\CsvExporter;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
    {
        $this->authorize('reports.viewAny');

        return Inertia::render('Reports/Index');
    }

    /**
     * @throws AuthorizationException
     */
    public function borrows(Request $request): mixed
    {
        $this->authorize('reports.viewAny');

        $validated = $request->validate([
            'start' => 'nullable|date|before_or_equal:today',
            'end' => 'nullable|date|after_or_equal:start',
            'employee' => 'nullable|exists:employees,id',
            'user' => 'nullable|exists:users,id',
            'situation' => 'nullable|in:1,2,3',
            'block' => 'nullable|exists:blocks,id',
            'room' => 'nullable|exists:rooms,id',
            'key' => 'nullable|exists:keys,id',
        ], [
            'start.before_or_equal' => 'A data de início não pode ser futura.',
            'end.after_or_equal' => 'A data final deve ser posterior ou igual à data de início.',
        ]);

        if ($request->boolean('export_csv')) {
            return $this->exportBorrowsCsv($request);
        }

        $summary = Borrow::reportSummary($request);

        return Inertia::render('Reports/Borrows/Index', array_merge(Borrow::ReportByDate($request), [
            'summary' => $summary,
            'users' => User::select('id', 'name')->where('status', User::ACTIVE)->orderBy('name')->get(),
            'employees' => Employee::select('id', 'registry', 'name')->orderBy('name')->get(),
            'blocks' => Block::select('id', 'description')->orderBy('description')->get(),
            'rooms' => Room::select('id', 'description')->orderBy('description')->get(),
            'keys' => Key::select('id', 'number')->orderBy('number')->get(),
            'filters' => [
                'start' => $request->start,
                'end' => $request->end,
                'employee' => $request->employee,
                'user' => $request->user,
                'situation' => $request->situation,
                'block' => $request->block,
                'room' => $request->room,
                'key' => $request->key,
            ],
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function overdue(Request $request): mixed
    {
        $this->authorize('reports.viewAny');

        if ($request->boolean('export_csv')) {
            return $this->exportOverdueCsv($request);
        }

        $query = Borrow::with(['employee', 'user', 'keys' => ['room' => ['block']]])
            ->where('devolution', null)
            ->orderBy('created_at', 'asc');

        $paginator = $query->paginate(config('app.pagination'))->appends($request->all());

        $summary = [
            'total' => Borrow::where('devolution', null)->count(),
            'overdue' => Borrow::where('devolution', null)->where('created_at', '<', now()->subHours(Borrow::OVERDUE_AFTER_HOURS))->count(),
        ];

        return Inertia::render('Reports/Overdue/Index', [
            'count' => $paginator->total(),
            'borrows' => $paginator,
            'page' => $request->page ?? 1,
            'summary' => $summary,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function expiringAccess(Request $request): mixed
    {
        $this->authorize('reports.viewAny');

        $validated = $request->validate([
            'window' => 'nullable|integer|min:1',
            'type' => 'nullable|in:3,4',
        ]);

        $window = $request->integer('window', 30);
        $types = $request->has('type') ? [(int) $request->type] : [Employee::STUDENT, Employee::EXTERNAL];

        $query = Employee::whereNotNull('valid_until')
            ->where('valid_until', '<=', now()->addDays($window))
            ->whereIn('type', $types)
            ->orderBy('valid_until', 'asc');

        if ($request->boolean('export_csv')) {
            $employees = $query->get();
            $rows = $employees->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'registry' => $e->registry,
                'valid_until' => $e->valid_until,
                'type' => collect(Employee::TYPES)->firstWhere('value', $e->type)['label'] ?? 'Desconhecido',
            ])->toArray();

            return CsvExporter::download($rows, [
                'id' => 'ID',
                'name' => 'Nome',
                'registry' => 'Matrícula',
                'valid_until' => 'Válido até',
                'type' => 'Tipo',
            ], 'relatorio-permissoes-expirando.csv');
        }

        $paginator = $query->paginate(config('app.pagination'))->appends($request->all());

        $employees = $paginator->through(function ($employee) {
            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'registry' => $employee->registry,
                'valid_until' => $employee->valid_until,
                'type' => collect(Employee::TYPES)->firstWhere('value', $employee->type)['label'] ?? 'Desconhecido',
            ];
        });

        return Inertia::render('Reports/ExpiringAccess/Index', [
            'count' => $paginator->total(),
            'employees' => $employees,
            'page' => $request->page ?? 1,
            'filters' => [
                'window' => $window,
                'type' => $request->type,
            ],
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function rooms(Request $request): mixed
    {
        $this->authorize('reports.viewAny');

        $request->validate([
            'start' => 'nullable|date|before_or_equal:today',
            'end' => 'nullable|date|after_or_equal:start',
        ], [
            'start.before_or_equal' => 'A data de início não pode ser futura.',
            'end.after_or_equal' => 'A data final deve ser posterior ou igual à data de início.',
        ]);

        $service = new ReportService;
        $data = $service->rooms($request);

        if ($request->boolean('export_csv')) {
            $rows = collect($data['rooms']->items())->map(fn ($item) => [
                'room' => $item->room,
                'block' => $item->block,
                'borrow_count' => $item->borrow_count,
                'key_count' => $item->key_count,
            ])->toArray();

            return CsvExporter::download($rows, [
                'room' => 'Sala',
                'block' => 'Bloco',
                'borrow_count' => 'Empréstimos',
                'key_count' => 'Chaves movimentadas',
            ], 'relatorio-uso-salas.csv');
        }

        return Inertia::render('Reports/Rooms/Index', array_merge($data, [
            'filters' => [
                'start' => $request->start,
                'end' => $request->end,
            ],
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function employees(Request $request): mixed
    {
        $this->authorize('reports.viewAny');

        $validated = $request->validate([
            'start' => 'nullable|date|before_or_equal:today',
            'end' => 'nullable|date|after_or_equal:start',
            'type' => 'nullable|in:1,2,3,4',
        ], [
            'start.before_or_equal' => 'A data de início não pode ser futura.',
            'end.after_or_equal' => 'A data final deve ser posterior ou igual à data de início.',
        ]);

        $service = new ReportService;
        $data = $service->employees($request);

        if ($request->boolean('export_csv')) {
            $rows = collect($data['employees']->items())->map(fn ($item) => [
                'name' => $item['name'],
                'type' => $item['type'],
                'borrow_count' => $item['borrow_count'],
                'key_count' => $item['key_count'],
                'overdue_count' => $item['overdue_count'],
                'valid_until' => $item['valid_until'],
            ])->toArray();

            return CsvExporter::download($rows, [
                'name' => 'Nome',
                'type' => 'Tipo',
                'borrow_count' => 'Empréstimos',
                'key_count' => 'Chaves movimentadas',
                'overdue_count' => 'Atrasos',
                'valid_until' => 'Válido até',
            ], 'relatorio-uso-mutuarios.csv');
        }

        return Inertia::render('Reports/Employees/Index', array_merge($data, [
            'filters' => [
                'start' => $request->start,
                'end' => $request->end,
                'type' => $request->type,
            ],
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function staff(Request $request): mixed
    {
        $this->authorize('reports.viewAny');

        $validated = $request->validate([
            'start' => 'nullable|date|before_or_equal:today',
            'end' => 'nullable|date|after_or_equal:start',
        ], [
            'start.before_or_equal' => 'A data de início não pode ser futura.',
            'end.after_or_equal' => 'A data final deve ser posterior ou igual à data de início.',
        ]);

        $service = new ReportService;
        $data = $service->staff($request);

        if ($request->boolean('export_csv')) {
            $rows = collect($data['staff']->items())->map(fn ($item) => [
                'name' => $item['name'],
                'delivery_count' => $item['delivery_count'],
                'receipt_count' => $item['receipt_count'],
            ])->toArray();

            return CsvExporter::download($rows, [
                'name' => 'Nome',
                'delivery_count' => 'Entregas',
                'receipt_count' => 'Recebimentos',
            ], 'relatorio-produtividade-recepcionistas.csv');
        }

        return Inertia::render('Reports/Staff/Index', array_merge($data, [
            'filters' => [
                'start' => $request->start,
                'end' => $request->end,
            ],
        ]));
    }

    /**
     * @throws AuthorizationException
     */
    public function turnaround(Request $request): mixed
    {
        $this->authorize('reports.viewAny');

        $validated = $request->validate([
            'start' => 'nullable|date|before_or_equal:today',
            'end' => 'nullable|date|after_or_equal:start',
        ], [
            'start.before_or_equal' => 'A data de início não pode ser futura.',
            'end.after_or_equal' => 'A data final deve ser posterior ou igual à data de início.',
        ]);

        $service = new ReportService;
        $data = $service->turnaround($request);

        if ($request->boolean('export_csv')) {
            return CsvExporter::download($data['turnaround'], [
                'category' => 'Categoria',
                'dimension' => 'Dimensão',
                'avg_hours' => 'Tempo médio (horas)',
                'count' => 'Quantidade',
            ], 'relatorio-tempo-medio-devolucao.csv');
        }

        return Inertia::render('Reports/Turnaround/Index', array_merge($data, [
            'filters' => [
                'start' => $request->start,
                'end' => $request->end,
            ],
        ]));
    }

    private function exportBorrowsCsv(Request $request): mixed
    {
        $query = Borrow::with(['employee', 'keys' => ['room' => ['block']], 'user', 'received' => ['user']]);

        $query->applyReportFilters($request);

        $borrows = $query->orderBy('created_at', 'desc')->get();

        $rows = $borrows->map(function ($borrow) {
            $keys = $borrow->keys->pluck('number')->implode(', ');
            $rooms = $borrow->keys->pluck('room.description')->unique()->implode(', ');
            $receivedBy = $borrow->received->pluck('user.name')->unique()->implode(', ');
            $returnedBy = $borrow->received->pluck('receiver')->unique()->implode(', ');

            return [
                'id' => $borrow->id,
                'created_at' => $borrow->created_at,
                'devolution' => $borrow->devolution,
                'employee' => $borrow->employee->name ?? '',
                'user' => $borrow->user->name ?? '',
                'received_by' => $receivedBy,
                'returned_by' => $returnedBy,
                'keys' => $keys,
                'rooms' => $rooms,
                'situation' => $borrow->situation,
            ];
        })->toArray();

        return CsvExporter::download($rows, [
            'id' => 'ID',
            'created_at' => 'Data Entrega',
            'devolution' => 'Data Devolução',
            'employee' => 'Mutuário',
            'user' => 'Entregue por',
            'received_by' => 'Recebido por',
            'returned_by' => 'Devolvida por',
            'keys' => 'Chaves',
            'rooms' => 'Salas',
            'situation' => 'Situação',
        ], 'relatorio-emprestimos.csv');
    }

    private function exportOverdueCsv(Request $request): mixed
    {
        $borrows = Borrow::with(['employee', 'user', 'keys' => ['room' => ['block']]])
            ->where('devolution', null)
            ->orderBy('created_at', 'asc')
            ->get();

        $rows = $borrows->map(function ($borrow) {
            $createdAtRaw = $borrow->getRawOriginal('created_at');
            $isOverdue = Carbon::parse($createdAtRaw)->addHours(Borrow::OVERDUE_AFTER_HOURS)->isPast();
            $hoursOut = now()->diffInHours(Carbon::parse($createdAtRaw));

            return [
                'id' => $borrow->id,
                'created_at' => $borrow->created_at,
                'employee' => $borrow->employee->name ?? '',
                'user' => $borrow->user->name ?? '',
                'keys' => $borrow->keys->pluck('number')->implode(', '),
                'rooms' => $borrow->keys->pluck('room.description')->unique()->implode(', '),
                'hours_out' => (int) $hoursOut,
                'overdue' => $isOverdue ? 'Sim' : 'Não',
            ];
        })->toArray();

        return CsvExporter::download($rows, [
            'id' => 'ID',
            'created_at' => 'Data Entrega',
            'employee' => 'Mutuário',
            'user' => 'Entregue por',
            'keys' => 'Chaves',
            'rooms' => 'Salas',
            'hours_out' => 'Horas fora',
            'overdue' => 'Atrasado',
        ], 'relatorio-chaves-atraso.csv');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $canViewBorrows = $request->user()->can('borrows.viewAny');

        $countKeys = Key::count();
        $keysOut = count(Borrow::keysNotReceived());
        $openBorrows = Borrow::whereNull('devolution')->count();
        $overdueCount = Borrow::whereNull('devolution')->where('created_at', '<', now()->subDay())->count();

        $recentBorrows = [];
        $overdueList = [];
        $expiringEmployees = [];

        if ($canViewBorrows) {
            $recentBorrows = Borrow::with('employee')
                ->withCount('keys')
                ->orderBy('id', 'DESC')
                ->take(5)
                ->get()
                ->map(function ($borrow) {
                    return [
                        'id' => $borrow->id,
                        'created_at' => $borrow->created_at,
                        'employee' => ['name' => $borrow->employee->name],
                        'keys_count' => $borrow->keys_count,
                        'situation' => $this->situation($borrow),
                    ];
                });

            $overdueList = Borrow::with('employee')
                ->withCount('keys')
                ->whereNull('devolution')
                ->where('created_at', '<', now()->subDay())
                ->orderBy('created_at', 'asc')
                ->take(10)
                ->get()
                ->map(function ($borrow) {
                    return [
                        'id' => $borrow->id,
                        'created_at' => $borrow->created_at,
                        'employee' => ['name' => $borrow->employee->name],
                        'keys_count' => $borrow->keys_count,
                        'situation' => 'atrasado',
                    ];
                });

            $expiringEmployees = Employee::whereNotNull('valid_until')
                ->whereBetween('valid_until', [now(), now()->addDays(30)])
                ->whereIn('type', [Employee::STUDENT, Employee::EXTERNAL])
                ->orderBy('valid_until', 'asc')
                ->take(10)
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'valid_until' => $employee->valid_until,
                        'type' => collect(Employee::TYPES)->firstWhere('value', $employee->type)['label'] ?? 'Desconhecido',
                    ];
                });
        }

        return Inertia::render('Dashboard', [
            'kpis' => [
                'keysOut' => $keysOut,
                'keysAvailable' => $countKeys - $keysOut,
                'openBorrows' => $openBorrows,
                'overdueBorrows' => $overdueCount,
            ],
            'totals' => [
                'countRooms' => Room::count(),
                'countBlocks' => Block::count(),
                'countKeys' => $countKeys,
                'countEmployees' => Employee::count(),
                'countBorrows' => Borrow::count(),
            ],
            'charts' => [
                'borrowsPerDay' => Borrow::dataChart(),
                'keysPerDay' => Borrow::dataChart2(),
                'byEmployeeType' => Borrow::borrowsByEmployeeType(),
                'topRooms' => Borrow::topRooms(),
            ],
            'recentBorrows' => $recentBorrows,
            'overdueList' => $overdueList,
            'expiringEmployees' => $expiringEmployees,
            'can' => [
                'borrowView' => $request->user()->can('borrows.view'),
            ],
        ]);
    }

    private function situation(Borrow $borrow): string
    {
        if ($borrow->devolution !== null) {
            return 'devolvido';
        }

        if ($borrow->created_at < now()->subDay()) {
            return 'atrasado';
        }

        return 'aberto';
    }
}

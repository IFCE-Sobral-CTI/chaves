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
        // O dashboard é a landing page pós-login, acessível a qualquer usuário
        // autenticado. Apenas contagens agregadas são expostas a todos; a lista
        // de empréstimos recentes só aparece para quem pode visualizá-los.
        $canViewBorrows = $request->user()->can('borrows.viewAny');

        return Inertia::render('Dashboard', [
            'borrows' => $canViewBorrows
                ? Borrow::with('employee')->orderBy('id', 'DESC')->take(5)->get()
                : [],
            'countRooms' => Room::count(),
            'countBlocks' => Block::count(),
            'countKeys' => Key::count(),
            'countEmployees' => Employee::count(),
            'countBorrows' => Borrow::count(),
            'dataBorrow' => Borrow::dataChart(),
            'dataKeys' => Borrow::dataChart2(),
            'can' => [
                'borrowView' => $request->user()->can('borrows.view'),
            ],
        ]);
    }
}

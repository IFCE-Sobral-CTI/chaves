<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Block;
use App\Models\Borrow;
use App\Models\Employee;
use App\Models\Key;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        // dd(Borrow::dataChart());

        return Inertia::render('Dashboard', [
            'borrows' => Borrow::with('employee')->orderBy('id', 'DESC')->take(5)->get(),
            'countRooms' => Room::count(),
            'countBlocks' => Block::count(),
            'countKeys' => Key::count(),
            'countEmployees' => Employee::count(),
            'countBorrows' => Borrow::count(),
            'dataBorrow' => Borrow::dataChart(),
            'can' => [
                'borrowView' => Auth::user()->can('borrows.view'),
            ]
        ]);
    }
}

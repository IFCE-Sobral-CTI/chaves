<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Permission;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
    * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $this->authorize('users.viewAny', User::class);

        $result = User::search($request->term);

        return Inertia::render('User/Index', [
            'users' => $result['data'],
            'count' => $result['count'],
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
            'can' => [
                'create' => Auth::user()->can('users.create'),
                'view' => Auth::user()->can('users.view'),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $this->authorize('users.create', User::class);

        return Inertia::render('User/Create', [
            'permissions' => Permission::select('id', 'description')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Qwer@1234
     * @param  StoreUserRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('users.create', User::class);

        $data = $request->validated();
        $data['password'] = Hash::make($request->password);

        try {
            $user = User::create($data);
            return redirect()->route('users.show', $user)->with('flash', ['status' => 'success', 'message' => 'Registro salvo com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  User $user
     * @return \Inertia\Response
     */
    public function show(User $user)
    {
        $this->authorize('users.view', $user);

        return Inertia::render('User/Show', [
            'user' => User::with('permission')->find($user->id),
            'can' => [
                'update' => Auth::user()->can('users.update'),
                'delete' => Auth::user()->can('users.delete'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  User $user
     * @return \Inertia\Response
     */
    public function edit(User $user)
    {
        $this->authorize('users.update', $user);

        return Inertia::render('User/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing password the specified resource.
     *
     * @param  User $user
     * @return \Inertia\Response
     */
    public function editPassword(User $user)
    {
        $this->authorize('users.update.password', $user);

        return Inertia::render('User/EditPassword', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('users.update', $user);

        $data = $request->validated();

        try {
            $user->update($data);
            return redirect()->route('users.show', $user)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso!']);
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update password the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePassword(UpdateUserPasswordRequest $request, User $user)
    {
        $this->authorize('users.update.password', $user);

        $data = $request->validated();

        try {
            $user->update($data);
            return redirect()->route('users.show', $user)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso!']);
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(User $user)
    {
        $this->authorize('users.delete', $user);

        try {
            $user->delete();
            return redirect()->route('users.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso!']);
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('flash', ['status' => 'danger', 'message' => $e->getMessage()]);
        }
    }
}

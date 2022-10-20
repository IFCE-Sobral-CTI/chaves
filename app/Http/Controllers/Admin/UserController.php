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
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $result = User::search($request->term);

        return Inertia::render('User/Index', [
            'users' => $result['data'],
            'count' => $result['count'],
            'page' => $request->page?? 1,
            'termSearch' => $request->term,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('User/Create', [
            'permissions' => Permission::select('id', 'description')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreUserRequest  $request
     * @return Response
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($request->password);

        try {
            $user = User::create($data);
            return redirect()->route('users.show', $user)->with('flash', ['status' => 'success', 'message' => 'Registro salvo com sucesso.']);
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('flash', ['status' => 'danger', 'message' => $e->message]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return Inertia::render('User/Show', [
            'user' => User::with('permission')->find($user->id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return Inertia::render('User/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing password the specified resource.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function editPassword(User $user)
    {
        return Inertia::render('User/EditPassword', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        try {
            $user->update($data);
            return redirect()->route('users.show', $user)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso!']);
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('flash', ['status' => 'danger', 'message' => $e->message]);
        }
    }

    /**
     * Update password the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(UpdateUserPasswordRequest $request, User $user)
    {
        $data = $request->validated();

        try {
            $user->update($data);
            return redirect()->route('users.show', $user)->with('flash', ['status' => 'success', 'message' => 'Registro atualizado com sucesso!']);
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('flash', ['status' => 'danger', 'message' => $e->message]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        try {
            $user->delete();
            return redirect()->route('users.index')->with('flash', ['status' => 'success', 'message' => 'Registro apagado com sucesso!']);
        } catch (Exception $e) {
            return redirect()->route('users.index')->with('flash', ['status' => 'danger', 'message' => $e->message]);
        }
    }
}

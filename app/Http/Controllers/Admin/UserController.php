<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Permission;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     * @throws AuthorizationException
     */
    public function index(Request $request): Response
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
     * @return Response
     * @throws AuthorizationException
     */
    public function create(): Response
    {
        $this->authorize('users.create', User::class);

        return Inertia::render('User/Create', [
            'permissions' => Permission::select('id', 'description')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreUserRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(StoreUserRequest $request): RedirectResponse
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
     * @param User $user
     * @return Response
     * @throws AuthorizationException
     */
    public function show(User $user): Response
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
     * Display profile user.
     *
     * @return Response
     * @throws AuthorizationException
     */
    public function profile(): Response
    {
        $this->authorize('users.profile', User::class);

        $user = Auth::user();

        return Inertia::render('User/Profile', [
            'user' => User::with('permission')->find($user->id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param User $user
     * @return Response
     * @throws AuthorizationException
     */
    public function edit(User $user): Response
    {
        $this->authorize('users.update', $user);

        return Inertia::render('User/Edit', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing password the specified resource.
     *
     * @param User $user
     * @return Response
     * @throws AuthorizationException
     */
    public function editPassword(User $user): Response
    {
        $this->authorize('users.update.password', $user);

        return Inertia::render('User/EditPassword', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateUserRequest $request
     * @param User $user
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
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
     * @param UpdateUserPasswordRequest $request
     * @param User $user
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function updatePassword(UpdateUserPasswordRequest $request, User $user): RedirectResponse
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
     * @param User $user
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(User $user): RedirectResponse
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

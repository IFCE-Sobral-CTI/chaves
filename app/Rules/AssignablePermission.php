<?php

namespace App\Rules;

use App\Models\Permission;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * Impede que um usuário não-administrador atribua a permissão de administrador
 * (a si mesmo ou a outros), fechando o vetor de escalonamento de privilégio.
 */
class AssignablePermission implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $permission = Permission::find($value);

        if ($permission && $permission->is_admin && ! Auth::user()?->isAdmin()) {
            $fail('Você não tem autorização para atribuir a permissão de administrador.');
        }
    }
}

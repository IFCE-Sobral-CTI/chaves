# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

- **Backend**: Laravel 13, PHP 8.4
- **Frontend**: React 19 + Inertia.js v3, Tailwind CSS 4, Vite 8
- **Database**: MySQL 8.0 + Redis
- **Dev environment**: Laravel Sail (Docker)

## Commands

```bash
# Start services
./vendor/bin/sail up -d

# Frontend dev server (HMR)
npm run dev
# or
composer dev

# Build frontend
npm run build

# Run all tests
./vendor/bin/sail artisan test

# Run a single test file
./vendor/bin/sail artisan test tests/Feature/Auth/AuthenticationTest.php

# Run specific test method
./vendor/bin/sail artisan test --filter=test_method_name

# Code style (Pint)
./vendor/bin/sail php vendor/bin/pint

# Reset DB with seed
./vendor/bin/sail artisan migrate:fresh --seed
```

## Domain

**Sistema de Empréstimos de Chaves** — usado pela recepção do IFCE Sobral para gerenciar empréstimos de chaves de salas do campus.

### Mutuários (`Employee`)

Quatro tipos, definidos como constantes no model:

| Constante | Valor | Label |
|-----------|-------|-------|
| `EMPLOYEE` | 1 | Servidor |
| `COLLABORATOR` | 2 | Colaborador |
| `EXTERNAL` | 4 | Externo |
| `STUDENT` | 3 | Discente |

- **Servidores e Colaboradores**: sem validade (`valid_until = null`)
- **Discentes e Externos**: precisam de permissão explícita com data de validade (`valid_until`)
- `scopeGetActiveEmployees` filtra quem pode tomar empréstimo: `valid_until >= now() OR valid_until IS NULL`
- A relação `borrowable_keys` (pivot `borrowable_keys`) registra quais chaves específicas um mutuário tem permissão de pegar — usada principalmente para discentes/externos

### Fluxo de empréstimo

1. Recepcionista cria um `Borrow` escolhendo mutuário + uma ou mais chaves disponíveis
2. Chaves disponíveis = chaves que **não** estão em empréstimos abertos sem devolução (`Borrow::keysNotReceived()`)
3. Devolução pode ser parcial: cada devolução cria um `Received` com as chaves devolvidas naquele momento
4. Um empréstimo está **encerrado** quando `devolution` não é `null`

### Hierarquia física

```
Block (Bloco)
  └── Room (Sala) — pertence a um bloco, tem N responsáveis (M2M com Employee)
        └── Key (Chave) — pertence a uma sala, identificada por número
```

## Architecture

### Authorization model

The system uses a custom RBAC without Laravel Policies. The chain is:

```
User → Permission → Rule[]
```

- `Rule` has a `control` field like `borrows.viewAny`, `borrows.create`, etc.
- `User::hasRule($control)` delegates to `permission->rules()->hasControl($control)`
- `User::isAdmin()` checks if the permission description is `"Administrador"` — admins bypass all rule checks
- Controllers use `$this->authorize('borrows.viewAny', Borrow::class)` which maps to Gate definitions

In `HandleInertiaRequests`, the `authorizations` shared prop is populated with all `viewAny` rules the current user has, made available to React as `usePage().props.authorizations`.

### Inertia data flow

All pages live under `resources/js/Pages/`. Controllers return `Inertia::render('ComponentPath', $props)`. The `@` alias maps to `/resources/js`.

Shared props (available on every page via `usePage()`):
- `auth.user` — authenticated user
- `authorizations` — map of `rule_control` → `true` for rules the user has
- `ziggy` — route helper for named routes in JS
- `flash.flash` — session flash messages

### Model conventions

- All models use the `CreatedAndUpdatedTimezone` trait which formats `created_at`/`updated_at` using `APP_TIMEZONE` (default: `America/Fortaleza`)
- Models expose a `scopeSearch(Builder $query, Request $request): array` that returns a plain array (not a Builder), so call it as `Model::search($request)` — not as a chainable query
- Activity logging via Spatie (`spatie/laravel-activitylog`) is configured per-model in `getActivitylogOptions()`

### Borrow lifecycle

A `Borrow` represents a loan event:
1. Created with one or more `Key` records (via `borrow_key` pivot)
2. Keys are returned individually via `Received` records (each `Received` has its own `key_received` pivot)
3. A borrow is considered open when `devolution` is `null`
4. `Borrow::keysNotReceived()` scope returns key IDs currently on open borrows that haven't been received yet — used to filter available keys when creating new borrows

### Form requests

All store/update operations go through `app/Http/Requests/` classes (e.g., `StoreBorrowRequest`, `UpdateKeyRequest`). Validation lives there, not in controllers.

### Routes

All application routes sit under the `/admin` prefix with `auth` + `verified` middleware. The root `/` redirects to `/admin`. HTTPS is forced in non-local environments via `URL::forceScheme('https')`.

### Testing

Tests use a separate `testing` database (configured in `phpunit.xml`). No real database connection — `DB_DATABASE=testing`. Run feature tests against the app layer; unit tests in `tests/Unit/`.

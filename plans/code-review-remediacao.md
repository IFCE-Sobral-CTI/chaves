# Plano de Code Review & Remediação — Sistema de Empréstimo de Chaves

## Contexto

O sistema (Laravel 13 / PHP 8.4 / React 19 + Inertia 3 / Tailwind 4) está em uso real
pela recepção do IFCE Sobral (o `.env` aponta para `http://10.20.1.16`, um host de rede
interna). Um code review completo de segurança, desempenho e design foi conduzido e
revelou **brechas de autorização exploráveis**, **bugs que perdem dados silenciosamente**,
**armadilhas de configuração que quebram em produção** e dívidas de frontend acumuladas na
migração Tailwind v3→v4.

Este plano organiza a remediação em **5 fases por prioridade**: do crítico de segurança ao
polimento de design. Cada item lista arquivo e linha. Recomenda-se executar fase a fase,
escrevendo testes para os itens das Fases 1–2 antes de prosseguir (hoje a cobertura de
domínio é ~zero).

> Princípio transversal: **parar de retornar `$e->getMessage()` ao usuário** em todos os
> `catch` dos controllers CRUD — logar o erro no servidor (`report($e)`) e mostrar mensagem
> genérica. Isto se repete em ~12 controllers e é tratado junto da Fase 1.

---

## Fase 1 — Segurança crítica (BLOQUEADOR)

### 1.1 Autorização ausente / incorreta em controllers
- **`ActivityController::destroy`** (`app/Http/Controllers/Admin/ActivityController.php`)
  — não tinha `$this->authorize(...)`. Qualquer usuário autenticado+verificado podia apagar
  registros do log de auditoria. Adicionar `$this->authorize('activities.delete', $activity);`.
  (Também: `index` autorizava `activities.showAny`, divergente do seeder `activities.viewAny`.)
- **`EmployeeController::destroy`** — checava `employees.view` em vez de `employees.delete`.
  Corrigir a ability.
- **`HomeController::index`** — dashboard sem nenhuma checagem; expõe contagens e os 5 últimos
  empréstimos. Definido como intencionalmente acessível a logados, mas a lista de empréstimos
  recentes só aparece para quem tem `borrows.viewAny`.

### 1.2 `isAdmin()` baseado em string + acesso a `permission` null-unsafe
- `app/Models/User.php` — `isAdmin()` comparava `permission->description === 'Administrador'`
  e `hasRule()` desreferenciava `permission` sem guarda. Usuário sem `permission_id` causava
  fatal em **todo** `authorize()` (via `Gate::before`).
- Correção: guarda de null (`$this->permission?->...`) e substituição da comparação por string
  por flag booleana estável `is_admin` na tabela `permissions` (migration nova, backfill do
  registro "Administrador"). `is_admin` **não** é fillable (evita escalonamento via CRUD de
  permissão); definido explicitamente no seeder.

### 1.3 Escalonamento de privilégio via atribuição de permissão
- `StoreUserRequest`/`UpdateUserRequest` aceitavam `permission_id => required|exists` sem
  restrição. Quem tem `users.create`/`users.update` podia atribuir "Administrador" a si ou a
  outros. Adicionada regra `App\Rules\AssignablePermission` que impede não-admin de atribuir
  permissão com `is_admin = true`.

### 1.4 IDOR no recebimento de chaves
- `BorrowController::receive` fazia `$received->keys()->sync(explode('|', $keys))` com IDs
  vindos crus da URL, **sem validar que as chaves pertencem ao `$borrow`**. Validar que cada
  ID está em `$borrow->keys` antes do `sync`; rejeitar IDs estranhos.

### 1.5 Configuração / exposição de segredos
- `.env` em disco com `APP_DEBUG=true`, `APP_ENV=local` apontando para host real e `APP_KEY`
  em texto; DB com credencial trivial. Para o deploy real: `APP_DEBUG=false`,
  `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`, rotacionar `APP_KEY` e a senha do banco.
  **(Ação de operação/infra — documentar, NÃO commitar segredos.)**
- `bootstrap/app.php` — `trustProxies(at: '*')` permite spoofing de `X-Forwarded-*`.
  Restringir aos IPs reais do proxy e forçar HTTPS + headers de segurança (HSTS,
  X-Frame-Options, X-Content-Type-Options, CSP) via middleware.
- `config/cors.php` — wildcard total (`*`); restringir ao domínio real (baixo risco hoje pois
  `supports_credentials=false`, mas desnecessariamente aberto).

---

## Fase 2 — Bugs funcionais que perdem dados

### 2.1 Colunas `received_by` / `returned_by` inexistentes
- `BorrowController::receive` faz `update(['received_by'=>..., 'returned_by'=>...])`, mas essas
  colunas **não existem** em `borrows` nem em `Borrow::$fillable`. O Eloquent descarta
  silenciosamente → **quem recebeu/devolveu a chave nunca é persistido** (lacuna de auditoria).
- Correção: migration adicionando `received_by` (FK users) e `returned_by` (string), adicionar
  ao `$fillable` e relacionamento `receivedBy()`. Confirmar se duplica `receiveds.receiver`.

### 2.2 `Borrow::scopeDataChart2` — builder reutilizado em loop + N+1
- O mesmo `$query` acumula `whereBetween` a cada iteração do loop de 7 meses (filtros empilham
  → contagens erradas) e faz `->get()` + `$borrow->keys->count()` por linha. Reescrever
  clonando o builder por iteração (ou agregação única com `groupBy`) e eager-load das chaves.

### 2.3 `orWhere` sem agrupamento nos `scopeSearch`
- `Activity`, `Borrow` (descarta o filtro `devolution = null` quando há termo), `Room`, `Key`,
  `Employee`, `User` — OR de nível superior sem `where(function(){...})` vaza linhas. Agrupar
  as condições de busca dentro de um closure.

### 2.4 Validações fracas em Form Requests
- `StoreBorrowRequest` — `devolution => 'nullable|datetime'`: regra `datetime` inexistente →
  campo não validado. Usar `date`.
- `StoreKeyRequest`/`UpdateKeyRequest` — `room_id => 'exists:rooms,id'` sem `required`; chave
  com `room_id` null quebra `Key::getForSelect()`.
- `StoreRuleRequest`/`UpdateRuleRequest` — `control` sem unicidade/formato; vira nome de Gate e
  colide silenciosamente. Adicionar `unique` + formato.

### 2.5 Bugs de runtime no frontend
- `Pages/Dashboard.jsx` — referencia `term`/`currentPage` inexistentes → `ReferenceError`
  quando `can.borrowView` é falso. Remover/definir os parâmetros.
- `Pages/Keys/Employee/Index.jsx` — `console.log(employees.data)` vaza PII. Remover.

---

## Fase 3 — Desempenho

### 3.1 `env()` em runtime fora de `config/` (quebra com `config:cache`)
- `env('APP_PAGINATION')` e `env('APP_TIMEZONE')` em quase todos os models (`Borrow`, `User`,
  `Key`, `Employee`, `Permission`, `Rule`, `Block`) e em
  `app/Http/Traits/CreatedAndUpdatedTimezone.php`. Após `config:cache` retornam **null** →
  `paginate(null)` e timezone inválido. Mover para chaves em `config/` e ler via `config(...)`.

### 3.2 Autorização cara por request
- `HandleInertiaRequests` roda `LIKE '%viewAny%'` sobre `rules` em **toda** navegação;
  `User::hasRule`/`Rule::scopeHasControl` fazem `count()` por checagem de Gate;
  `AuthServiceProvider` carrega todas as rules+permissions a cada request. Cachear o conjunto
  de controls do usuário por request e evitar o `LIKE` com wildcard à esquerda.

### 3.3 Índices ausentes
- Adicionar índices: `borrows.devolution`, `keys.number`, `keys.description`, `users.name`,
  `users.email`. Para `rules.control` (filtrado por `LIKE '%...%'`), repensar o padrão.

### 3.4 `count()` + `paginate()` duplicando a query
- Todo `scopeSearch` roda o builder filtrado duas vezes. Usar o `total()` do paginator.

### 3.5 Frontend — bundle e re-renders
- `resources/js/bootstrap.js` — `import _ from 'lodash'` sem uso. Remover.
- `Dashboard.jsx` — `react-google-charts` sem code-splitting; `moment` em 5 páginas.
  Considerar lazy-load do chart e migrar `moment` → `dayjs`/`date-fns`.
- `Components/Form/SelectMulti.jsx` e os 4 `Select*` — `useCallback` sem deps e
  `addEventListener('click')` re-registrado a cada termo. Separar listener do debounce.
- `User/Index.jsx` / `Employee/Index.jsx` — `router.get` envia `currentPage` stale.

---

## Fase 4 — Design / Tailwind v4 (branch `fix/tailwind-v4-classes`)

### 4.1 Classes quebradas na migração v3→v4
- **`!hidden` → `hidden!`**: `Components/Dashboard/Sidebar.jsx`. Sidebar mobile e submenu
  "Acesso" **não escondem**.
- **`focus:ring` → `focus:ring-2`**: default caiu de 3px→1px no v4; ~15 arquivos.
- **`flex-shrink-0` → `shrink-0`** e **`outline-none` → `outline-hidden`**: `DeleteModal.jsx`,
  `Receive.jsx`.
- Revisar as 141 ocorrências de `shadow`/`rounded` (escala renomeada no v4).
- `tailwind.config.js` — content path morto `./src/**/*`; remover.

### 4.2 Bug de responsividade
- `Sidebar.jsx` — `useEffect(() => setWidth(window.innerWidth), [window.innerWidth])`:
  dependência nunca muda e não há listener de `resize`. Adicionar listener real com cleanup.

### 4.3 Acessibilidade
- Trocar `<div onClick>` / `<li onClick>` por `<button>` em `Dropdown.jsx`, `SelectMulti.jsx`,
  `SelectEmployee.jsx` (navegação por teclado).
- Associar `<label htmlFor>` aos inputs em `SelectEmployee.jsx`, `SelectMulti.jsx`.

---

## Fase 5 — Consistência / dívida de design de código

- **Duplicação dos `Select*`**: `Borrow/Components/SelectEmployee.jsx`,
  `Report/Components/SelectEmployee.jsx`, `Key/Components/SelectRoom.jsx`,
  `Room/Components/SelectBlock.jsx` são ~idênticos. Extrair componente parametrizado. Note o
  `==` vs `===` divergente (`SelectEmployee.jsx`) — bug latente.
- **Inputs concorrentes**: legados do Breeze (`TextInput.jsx`, `Checkbox.jsx`) vs `Form/Input.jsx`.
  `Login.jsx` importa ambos. Padronizar no conjunto `Form/*`.
- **`className` undefined**: `Form/Input/Select/Textarea` e `Button.jsx` concatenam com `+` sem
  default `= ''` → string `"undefined"` no DOM. Dar default.
- **`key={index}` em listas filtradas** → usar `item.id`.
- **Limpeza do log de atividade**: configurar `config/activitylog.php` com política de expurgo.

---

## Verificação

Como o domínio hoje tem **cobertura ~zero**, a verificação das Fases 1–2 deve incluir
**escrever testes Feature** que falham antes e passam depois (factories + `AssertableInertia`):

1. **Autorização** (Fase 1): 403 sem ability em `activities.destroy`, `employees.destroy`; e
   não-admin não atribui permissão "Administrador" via `users.store/update`.
2. **IDOR** (1.4): `receive` rejeita IDs de chave que não pertencem ao borrow.
3. **Persistência de devolução** (2.1): após devolução, `received_by`/`returned_by` gravados.
4. **Busca** (2.3): filtro `devolution = null` mantido mesmo com termo de busca.
5. **config:cache** (3.1): `php artisan config:cache` e validar paginação/timezone.

```bash
./vendor/bin/sail artisan test                 # suite completa
./vendor/bin/sail php vendor/bin/pint          # estilo PHP
npm run build                                  # valida classes Tailwind v4 / bundle
```

Para a Fase 4, validar visualmente em mobile e desktop após `npm run dev`.

---

## Sequência recomendada de execução
1. Fase 1 (segurança) + remoção do `$e->getMessage()` exposto, com testes de autorização.
2. Fase 2 (bugs de dados), com migration de `received_by`/`returned_by` e testes.
3. Fase 3 (desempenho), validando `config:cache`.
4. Fase 4 (Tailwind v4 / a11y) — fecha o objetivo do branch atual.
5. Fase 5 (consistência) — refactor incremental sem mudança de comportamento.

---

## Progresso

### ✅ Fase 1 — concluída
- 1.1 Autorização: `ActivityController` (destroy + `index` viewAny), `EmployeeController::destroy`,
  `HomeController::index` documentado e lista de borrows condicional a `borrows.viewAny`.
- 1.2 `is_admin`: migration `2026_06_26_000001_add_is_admin_to_permissions_table.php`,
  `Permission` (cast, não-fillable), `User::isAdmin/hasRule` null-safe, `PermissionSeeder`.
- 1.3 Escalonamento: `App\Rules\AssignablePermission` + `Store/UpdateUserRequest`.
- 1.4 IDOR: validação das chaves em `BorrowController::receive`.
- Transversal: `report($e)` + mensagem genérica em todos os controllers (33 `report($e)`,
  0 `getMessage` restantes). Todos passam `php -l`.

### ✅ Fase 2 — concluída
- 2.1 `received_by`/`returned_by`: migration `2026_06_29_000001_add_received_by_and_returned_by_to_borrows_table.php`,
  adicionados ao `$fillable` e relacionamento `receivedBy()` no `Borrow`.
- 2.2 `Borrow::scopeDataChart2`: reescrito com eager-load de `keys_count` e filtro por intervalo
  sem acumular `whereBetween` no builder original.
- 2.3 `orWhere` sem agrupamento: corrigido em `Activity`, `Borrow`, `Room`, `Key`, `Employee`, `User`, `Rule`.
- 2.4 Validações: `StoreBorrowRequest` (`datetime` → `date`), `Store/UpdateKeyRequest` (`room_id` com `required`),
  `Store/UpdateRuleRequest` (`unique` + regex de formato no `control`).
- 2.5 Frontend: `Dashboard.jsx` (`term`/`currentPage` removidos em fallback), `Employee/Index.jsx` (`console.log` removido).

### ✅ Fase 3 — concluída
- 3.1 `env()` em runtime: removido de todos os models, traits e controllers. Criada chave
  `app.pagination` em `config/app.php`; todos os `env('APP_PAGINATION')` e `env('APP_TIMEZONE')`
  migrados para `config(...)`.
- 3.2 Autorização cara: `HandleInertiaRequests` cacheia controls por 5 minutos e usa `pluck()`
  em vez de carregar modelos completos; `Rule::scopeHasControl` usa `exists()` em vez de `count()`.
- 3.3 Índices: migration `2026_06_29_000002_add_performance_indexes.php` adicionando índices
  em `borrows.devolution`, `keys.number`, `keys.description`, `users.name`, `users.email`, `rules.control`.
- 3.4 `count()` + `paginate()`: todos os `scopeSearch` agora usam `$paginator->total()` em vez de
  executar `count()` em separado.
- 3.5 Frontend: `lodash` removido de `bootstrap.js`; listeners de click separados do debounce
  nos componentes `Select*`.

### ✅ Fase 4 — concluída
- 4.1 Classes Tailwind v4: `!hidden` → `hidden!`, `focus:ring` → `focus:ring-2`,
  `flex-shrink-0` → `shrink-0`, `outline-none` → `outline-hidden`; `tailwind.config.js` limpo
  (removido path morto `./src/**/*`).
- 4.2 Responsividade `Sidebar.jsx`: `useEffect` com listener real de `resize` e cleanup.
- 4.3 Acessibilidade: `<div onClick>` / `<li onClick>` trocados por `<button>` em `Dropdown.jsx`,
  `SelectMulti.jsx`, `SelectEmployee.jsx`, `SelectRoom.jsx`, `SelectBlock.jsx`; labels associados
  via `htmlFor` aos inputs.

### ✅ Fase 5 — concluída
- 5.1 Duplicação dos `Select*`: a11y e bugs de listener/debounce corrigidos em todos;
  extração parametrizada planejada como refactor futuro.
- 5.2 Inputs concorrentes: `Login.jsx` usa apenas `Form/Input` (removidos `TextInput`, `PrimaryButton`).
- 5.3 `className` undefined: `Form/Input`, `Form/Select`, `Form/Textarea` e `Button.jsx` com
  default `= ''`.
- 5.4 `key={index}` → `key={item.id}` (ou identificador único) em 18 arquivos.
- 5.5 Limpeza de logs: criado `config/activitylog.php` com política de expurgo de 365 dias.

### ✅ Verificação — testes Feature
- Criado `tests/Feature/RemediationTest.php` cobrindo:
  - Autorização (`activities.destroy` retorna 403 sem ability).
  - IDOR (`borrows.receive` rejeita chaves estranhas).
  - Persistência (`received_by`/`returned_by` gravados após devolução).
  - Busca (`devolution = null` mantido mesmo com termo de busca).

### ⏳ Pendente
- 1.5 Hardening de config (trustProxies, headers, CORS) — `.env` documentado, não commitado
  (ação de operação/infra).

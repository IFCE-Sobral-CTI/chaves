# Plano: Revamp da Dashboard (dados, KPIs, gráficos e tabelas)

## Objetivo

Reformular a página inicial pós-login (`resources/js/Pages/Dashboard.jsx` + `HomeController`)
para exibir os dados **mais importantes** do Sistema de Empréstimos de Chaves, combinando
indicadores **operacionais** (o que a recepção precisa agora) com **análises gerenciais**
(tendências e rankings), usando KPIs, gráficos, tabelas e listas.

## Decisões já tomadas

- **Foco:** misto — cards operacionais no topo + gráficos analíticos abaixo.
- **Biblioteca de gráficos:** migrar de `react-google-charts` para **Recharts** (React nativo,
  sem dependência de carregamento externo do Google, melhor integração com Tailwind v4).
- **Bugs:** corrigir os defeitos conhecidos da dashboard atual no mesmo esforço.

---

## Parte A — Backend (`HomeController` + scopes nos models)

Toda a agregação fica em métodos de model/scope (mantém controller fino, segue o padrão do
projeto). O controller monta os props e respeita autorização: contagens agregadas para todos
os autenticados; listas/rankings detalhados só para quem tem `borrows.viewAny`.

### A1. KPIs operacionais (sempre visíveis)

| KPI | Origem |
|-----|--------|
| Chaves em poder agora | `count(Borrow::keysNotReceived())` |
| Chaves disponíveis | `Key::count() - chavesEmPoder` |
| Empréstimos abertos | `Borrow::whereNull('devolution')->count()` |
| Empréstimos em atraso | abertos com `created_at < now()->subDay()` |

> Reaproveita a lógica de `status()` hoje feita no front (aberto = dentro de 24h, atrasado =
> passou de 24h sem devolução). Centralizar essa regra no backend evita divergência.

### A2. KPIs de cadastro (mantidos, secundários)

Manter Salas, Blocos, Chaves, Servidores, Empréstimos — porém rebaixados visualmente para uma
faixa secundária, já que os operacionais (A1) passam a ser o destaque.

### A3. Gráficos analíticos (novos scopes em `Borrow`)

1. **Empréstimos por dia (últimos 7 dias)** — reescrever `scopeDataChart` (ver bug B1).
   Retornar série completa de 7 dias com zeros para dias sem movimento.
2. **Chaves emprestadas por dia (últimos 7 dias)** — corrigir `scopeDataChart2` (bug B2).
3. **Distribuição por tipo de mutuário** — novo `scopeBorrowsByEmployeeType`: contagem de
   empréstimos agrupada por `Employee::TYPES` (Servidor/Colaborador/Discente/Externo).
   Bom para gráfico de pizza/donut.
4. **Top 5 salas mais emprestadas** — novo `scopeTopRooms`: join `borrow_key → keys → rooms`,
   contagem por sala, ordenado desc, limit 5. Bom para gráfico de barras horizontais.

### A4. Tabelas / listas

1. **Últimos empréstimos** (já existe) — manter, mas incluir contagem de chaves do empréstimo
   e situação calculada no backend.
2. **Empréstimos em atraso** (nova lista) — abertos há mais de 24h, ordenados pelo mais antigo;
   só para quem tem `borrows.viewAny`. Inclui link para `borrows.show`.
3. **Permissões vencendo** (nova lista) — `Employee` discente/externo com
   `valid_until` entre hoje e +30 dias. Alerta proativo para a recepção renovar acessos.
   Usa `whereNotNull('valid_until')->whereBetween('valid_until', [now(), now()->addDays(30)])`.

### A5. Forma final dos props do `HomeController@index`

```php
return Inertia::render('Dashboard', [
    // operacional (todos)
    'kpis' => [
        'keysOut'        => $keysOut,
        'keysAvailable'  => $countKeys - $keysOut,
        'openBorrows'    => $openBorrows,
        'overdueBorrows' => $overdueCount,
    ],
    // cadastro (todos)
    'totals' => compact('countRooms','countBlocks','countKeys','countEmployees','countBorrows'),
    // analítico (todos — agregados, sem dado sensível)
    'charts' => [
        'borrowsPerDay'    => Borrow::dataChart(),
        'keysPerDay'       => Borrow::dataChart2(),
        'byEmployeeType'   => Borrow::borrowsByEmployeeType(),
        'topRooms'         => Borrow::topRooms(),
    ],
    // detalhado (apenas borrows.viewAny)
    'recentBorrows'    => $canViewBorrows ? ... : [],
    'overdueList'      => $canViewBorrows ? ... : [],
    'expiringEmployees'=> $canViewBorrows ? ... : [],
    'can' => ['borrowView' => $request->user()->can('borrows.view')],
];
```

> Formato dos gráficos: padronizar para arrays de objetos `[{ label, value }, ...]` (formato
> nativo do Recharts via `dataKey`), em vez do formato matriz do Google Charts. Ajustar os
> scopes para devolver esse shape.

---

## Parte B — Correção dos bugs conhecidos

- **B1 — `scopeDataChart` impreciso:** usa `->take(5)` (deveria ser 7 dias) e `groupBy('date')`
  sem preencher dias sem movimento, gerando série incompleta e fora de ordem. Reescrever para
  gerar os 7 dias fixos (hoje−6 … hoje) com `0` onde não há empréstimo.
- **B2 — `scopeDataChart2` mutação do builder:** documentado na auditoria
  (`Borrow::scopeDataChart2 Mutates Builder in Loop`). A versão atual já usa `$query->clone()`
  parcialmente; garantir que nenhuma mutação vaze e validar o intervalo de datas (7 dias).
- **B3 — Variáveis indefinidas / props mortos no `Dashboard.jsx`:** a auditoria registrou
  referências a `term`/`currentPage` indefinidas e navegação por ternário quebrada. Como o
  componente será reescrito, garantir que nenhum identificador indefinido permaneça.
- **B4 — `useEffect([])` que renderiza gráficos:** o padrão atual guarda JSX em `useState` e
  monta via `useTransition` no mount. Com Recharts os gráficos são declarativos — remover o
  `useState/useEffect/useTransition` e renderizar direto, eliminando o estado intermediário.

---

## Parte C — Frontend (`Dashboard.jsx` + componentes)

### C1. Dependência

```bash
npm install recharts
```

Remover o uso de `react-google-charts` na dashboard. Manter o pacote no `package.json` apenas
se for usado em outras páginas (verificar com grep antes de remover da dependência).

### C2. Novos componentes (em `resources/js/Components/Dashboard/`)

Seguindo a regra do projeto (arquivos pequenos, coesos, < 800 linhas):

- `KpiCard.jsx` — card de KPI com título, valor, ícone e cor/variante (ex.: atraso em vermelho).
  Substitui os 5 `div`s repetidos atuais por um componente reutilizável (DRY).
- `ChartCard.jsx` — wrapper `Panel` + título + `ResponsiveContainer` do Recharts.
- `BorrowsBarChart.jsx` — barras de empréstimos/dia (Recharts `BarChart`).
- `KeysAreaChart.jsx` — chaves/dia (Recharts `AreaChart` ou `LineChart`).
- `EmployeeTypePie.jsx` — donut por tipo de mutuário (Recharts `PieChart`).
- `TopRoomsBarChart.jsx` — barras horizontais top 5 salas.
- `BorrowsTable.jsx` — tabela genérica reutilizada por "últimos" e "em atraso".
- `ExpiringList.jsx` — lista de permissões vencendo.

### C3. Layout da página (composição misto)

```
┌──────────────────────────────────────────────────────────┐
│  KPIs OPERACIONAIS (4 cards, destaque)                    │
│  Chaves em poder · Disponíveis · Abertos · Em atraso      │
├──────────────────────────────────────────────────────────┤
│  Faixa de totais de cadastro (5 chips menores)            │
├───────────────────────────────┬──────────────────────────┤
│  Empréstimos/dia (barras)     │  Chaves/dia (área)        │
├───────────────────────────────┼──────────────────────────┤
│  Tipo de mutuário (donut)     │  Top 5 salas (barras H)   │
├───────────────────────────────┴──────────────────────────┤
│  Últimos empréstimos (tabela)                             │
├──────────────────────────────────────────────────────────┤
│  Em atraso (tabela)        │  Permissões vencendo (lista) │
└──────────────────────────────────────────────────────────┘
```

- Blocos detalhados (atraso, permissões vencendo, listas) só renderizam quando
  `authorizations['borrows.viewAny']` estiver presente — coerente com o backend.
- Grid responsivo Tailwind: `grid-cols-1 md:grid-cols-2 lg:grid-cols-4` para KPIs;
  gráficos em `md:grid-cols-2`.
- Estados vazios: cada gráfico/lista exibe mensagem amigável quando não há dados
  (ex.: "Nenhum empréstimo em atraso 🎉").

### C4. Acessibilidade / qualidade visual

- Cores semânticas: atraso = vermelho, aberto = âmbar, disponível/devolvido = verde.
- `ResponsiveContainer` para os gráficos se adaptarem ao container.
- Manter paleta atual do projeto (emerald/teal/indigo/amber/sky) nos KPIs.
- Usar `key` estável (id) nas listas — a auditoria apontou uso de índice como key.

---

## Parte D — Testes (Feature, Inertia)

Seguindo `php/testing.md` e o padrão Inertia do projeto (`assertInertia`):

1. `HomeControllerTest`:
   - Usuário **sem** `borrows.viewAny`: recebe KPIs/totais/charts, mas `recentBorrows`,
     `overdueList` e `expiringEmployees` vazios.
   - Usuário **com** `borrows.viewAny`: recebe as listas detalhadas preenchidas.
   - `keysOut` + `keysAvailable` == `countKeys` (consistência).
   - `overdueBorrows` conta apenas abertos há mais de 24h.
2. `Borrow` scopes (Unit/Feature com factory):
   - `dataChart` retorna exatamente 7 pontos, incluindo dias com `0`.
   - `borrowsByEmployeeType` agrupa corretamente pelos 4 tipos.
   - `topRooms` ordena desc e limita a 5.
   - `expiringEmployees` traz só discentes/externos com `valid_until` em até 30 dias.

> O projeto está com cobertura zero no domínio (auditoria, obs. 59). Estes testes começam a
> cobrir o fluxo da dashboard. Usar factories; banco de teste `testing` já configurado.

---

## Ordem de execução

1. **B + A (backend):** corrigir/reescrever scopes (`dataChart`, `dataChart2`), criar novos
   scopes (`borrowsByEmployeeType`, `topRooms`, lista de atraso, permissões vencendo) e
   reorganizar `HomeController@index` com o novo shape de props.
2. **D (testes backend):** escrever testes dos scopes e do controller (TDD onde fizer sentido).
3. **C1:** instalar Recharts.
4. **C2–C3 (frontend):** extrair `KpiCard`/`ChartCard`, criar gráficos, reescrever
   `Dashboard.jsx` com o novo layout e remover o estado intermediário (B4).
5. **Verificação:** `sail artisan test`, `npm run build`, conferência visual da página.

## Riscos / pontos de atenção

- `keysNotReceived()` faz join em `key_received`/`receiveds`; reusar o scope existente em vez de
  reimplementar a contagem de chaves em poder.
- Confirmar se `react-google-charts` é usado em outras páginas antes de removê-lo do
  `package.json` (provável remoção só do uso na dashboard).
- Charts devem receber arrays já no formato do Recharts — alinhar shape backend↔front.
- Respeitar a regra de autorização: dados sensíveis (nomes de mutuários nas listas) só para
  quem tem `borrows.viewAny`; agregados podem ir para todos.
- Manter consistência de fuso (`CreatedAndUpdatedTimezone`) nas datas exibidas.
```

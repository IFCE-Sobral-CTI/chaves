# Plano: Revamp dos Relatórios (análise, reformulação e novos relatórios)

## Objetivo

Reformular a área de **Relatórios** do Sistema de Empréstimos de Chaves. Hoje existe um único
relatório: uma lista bruta e paginada de empréstimos com filtros. O objetivo é:

1. **Reformular** o relatório atual (corrigir bugs, centralizar regras de negócio, adicionar
   exportação e melhorar UX).
2. **Gerar novos relatórios** que aproveitem os dados que o sistema já produz (empréstimos,
   devoluções, chaves, salas/blocos, mutuários, usuários/recepcionistas, log de atividades),
   cobrindo necessidades **operacionais** (recepção) e **gerenciais** (tendências e rankings).

> Este documento é **somente plano**. Nada deve ser implementado a partir dele sem aprovação.

---

## Parte 0 — Diagnóstico do estado atual

### O que existe hoje

- Rota única: `GET /admin/reports/index` → `ReportController@index` → `Keys/Report/Index.jsx`.
- Autorização via `reports.viewAny`.
- Filtros: intervalo de datas (`start`/`end`), mutuário, usuário (entregue por), situação
  (1 devolvido / 2 aberto / 3 atrasado).
- Tabela por empréstimo: Entrega, Recebida, Mutuário, Entregue por, Recebido por, Devolvida por,
  Chaves, Situação. Com paginação.
- Agregação no model `Borrow`: `scopeReportByDate` + filtros privados
  (`filterByDate`, `filterBySituation`, `filterByEmployee`, `filterByUser`).
- A revamp da dashboard já adicionou scopes reaproveitáveis: `scopeDataChart`,
  `scopeDataChart2`, `scopeBorrowsByEmployeeType`, `scopeTopRooms`, `scopeKeysNotReceived`.

### Problemas identificados (a corrigir nesta entrega)

| # | Problema | Local | Severidade |
|---|----------|-------|-----------|
| P1 | **Sem exportação** (CSV/PDF/impressão). Relatório só visualizável na tela. | Report/Index.jsx | Alta |
| P2 | **`filterByDate` ignora o filtro quando só `end` é informado** (só aplica `whereBetween` se `start` existir) e contém `->get()` morto que executa a query e descarta o resultado. | `Borrow.php:305-318` | Alta |
| P3 | **Regra de "atraso" (janela de 24h) duplicada e hardcoded** em dois lugares: front (`status()` com moment) e back (`filterBySituation` com `now()->subDay()`). Risco de divergência. | `Report/Index.jsx:30-43`, `Borrow.php:283-298` | Média |
| P4 | **`<option>` placeholder sem `value`** nos selects de Usuário e Situação (envia o texto do label). E o placeholder da Situação está com label errado ("Selecione um Usuário"). | `Report/Index.jsx:112,127` | Média |
| P5 | **React key por índice** (`key={i}`) nas linhas e nas listas internas. | `Report/Index.jsx:46,54,62,70` | Baixa |
| P6 | **Sem agregação/resumo** no relatório (totais, contagens por situação) — é apenas lista crua. | Report | Média |
| P7 | **Apenas 1 tipo de relatório** para um domínio rico em dados. | Geral | Alta (escopo) |
| P8 | **Mensagem de validação inconsistente**: regra `before_or_equal` com mensagem `start.before`. | `ReportController.php:24-31` | Baixa |
| P9 | **Possível redundância de dados de devolução**: colunas novas `borrows.received_by` (FK user) e `borrows.returned_by` (string) coexistem com `Received.user` / `Received.receiver`. Definir a fonte canônica antes de exibir em relatório. | migration `2026_06_29_000001`, `Received.php` | Média (decisão) |

> **Resolução P9 (decidida):** a investigação confirmou que `borrows.received_by`/`returned_by`
> **estão em uso ativo e testado** (`BorrowController::receive` grava `received_by = Auth::id()` e
> `returned_by = request` no fechamento total; `RemediationTest` valida). Não são colunas mortas.
> Fonte canônica definida:
> - **Relatórios** usam `Received.user.name` (recepcionista que registrou cada devolução) e
>   `Received.receiver` (quem fisicamente devolveu) — detalhe completo, inclusive devoluções
>   parciais.
> - `borrows.received_by`/`returned_by` permanecem como **resumo de fechamento** do empréstimo
>   (quem fechou / quem devolveu na devolução final).
>
> Não há contradição: o relatório mostra o detalhe (todos os `Received`), as colunas guardam o
> resumo. **Nada a remover.**

---

## Parte 1 — Arquitetura proposta

### 1.1 Hub de Relatórios

Transformar `reports.index` em um **índice/hub** (`Reports/Index.jsx`) com cards para cada
relatório disponível, respeitando autorização. Cada relatório vira uma sub-rota própria.

```
/admin/reports                      → Reports/Index (hub)
/admin/reports/borrows              → R1 Empréstimos (reformulado, era reports.index)
/admin/reports/overdue              → R2 Chaves em atraso / em poder
/admin/reports/rooms                → R3 Uso por sala/bloco
/admin/reports/employees            → R4 Uso por mutuário
/admin/reports/staff                → R5 Produtividade por recepcionista
/admin/reports/turnaround           → R6 Tempo médio de devolução
/admin/reports/expiring-access      → R7 Permissões expirando (discentes/externos)
```

> Alternativa de menor esforço: manter uma página única com abas (tabs). **Recomendado:** rotas
> separadas — cada relatório tem filtros e exportação próprios, e a URL vira estado compartilhável
> (alinha com a regra "URL as state").

### 1.2 Camada de agregação

Manter controllers finos. Toda agregação fica em **scopes de model** (padrão do projeto) ou,
quando a query cruzar muitos models, em um **serviço dedicado** `App\Services\Reports\*` para não
inchar o model `Borrow`. Decisão por relatório indicada abaixo.

### 1.3 Centralização da regra de negócio (resolve P3)

Criar fonte única para o status do empréstimo. Opções:

- Constante `Borrow::OVERDUE_AFTER_HOURS = 24` (substitui os `now()->subDay()` e o `add(1,'d')`).
- Accessor `getSituationAttribute()` no model retornando `devolvido|aberto|atrasado`, calculado no
  backend. O front passa a **exibir** o status vindo do backend em vez de recalcular com moment.

Isso elimina a duplicação front/back e o uso de `moment` para lógica (mantendo-o só para exibição
se necessário).

### 1.4 Exportação (resolve P1)

- **CSV**: implementação nativa via `StreamedResponse` (sem nova dependência). Cada relatório
  expõe `?export=csv` que reusa os mesmos filtros e devolve o dataset **completo** (sem paginação).
- **Impressão**: layout `@media print` + botão "Imprimir" (custo baixo, cobre PDF via "salvar como
  PDF" do navegador).
- **PDF nativo (opcional / fase 2)**: só se a recepção exigir PDF com identidade visual. Aí avaliar
  `barryvdh/laravel-dompdf`. Não incluir na primeira entrega para evitar dependência nova.

Padronizar a montagem de CSV num helper único (`App\Support\CsvExporter` ou trait) para DRY entre
os 7 relatórios.

### 1.5 Autorização

- `reports.viewAny` libera o hub e o relatório de empréstimos (comportamento atual preservado).
- Avaliar regras mais granulares por relatório (ex.: `reports.viewAny` cobre tudo, ou criar
  `reports.analytics` para os gerenciais). **Recomendado:** manter `reports.viewAny` única na
  primeira entrega; granularidade só se houver requisito. Registrar a decisão antes de codar.

---

## Parte 2 — Relatórios

### R1. Empréstimos (reformulado) — operacional/auditoria

Evolução do relatório atual.

- **Filtros:** intervalo de datas, mutuário, recepcionista, situação, **+ bloco/sala** e
  **+ chave** (novos). Corrigir P2 (filtro só com `end`) e P4 (placeholders).
- **Resumo no topo (resolve P6):** total de empréstimos no filtro, nº por situação
  (devolvidos / abertos / atrasados), total de chaves movimentadas.
- **Status vindo do backend** (resolve P3).
- **Exportação CSV + impressão** (resolve P1).
- **Keys estáveis** nas listas (resolve P5).
- Fonte: `Borrow::scopeReportByDate` corrigido e estendido.

### R2. Chaves em atraso / em poder agora — operacional

O relatório mais útil para a recepção no dia a dia.

- Lista de **chaves atualmente emprestadas e não devolvidas**, com: chave, sala/bloco, mutuário,
  recepcionista que entregou, data/hora da entrega, **há quanto tempo está fora**, flag de atraso
  (> 24h).
- Ordenável por tempo fora (mais atrasadas primeiro).
- Resumo: total em poder, total em atraso.
- Fonte: reaproveitar `scopeKeysNotReceived` + join para detalhes (novo scope/serviço).
- Exportação CSV + impressão.

### R3. Uso por sala / bloco — gerencial

- Ranking de salas (e agregação por bloco) por **nº de empréstimos** e **nº de chaves
  movimentadas** no período.
- Filtro de intervalo de datas.
- Visualização: tabela + barra (Recharts, já disponível). Reusar/generalizar `scopeTopRooms`
  (hoje fixo em top 5 e sem filtro de data) para aceitar período e retornar todos paginados.
- Exportação CSV.

### R4. Uso por mutuário — gerencial

- Ranking de mutuários por nº de empréstimos no período, com **tipo** (Servidor/Colaborador/
  Discente/Externo), nº de empréstimos, chaves movimentadas, **nº de atrasos**, e
  `valid_until` quando aplicável.
- Filtro: intervalo de datas e tipo de mutuário.
- Reaproveitar lógica de `scopeBorrowsByEmployeeType` para o agregado por tipo (gráfico pizza).
- Exportação CSV.

### R5. Produtividade por recepcionista — gerencial

- Por usuário do sistema: nº de entregas (empréstimos criados) e nº de recebimentos/devoluções
  registradas no período.
- Filtro: intervalo de datas.
- Fonte: `Borrow` (entregas via `user_id`) + `Received` (recebimentos via `user_id`). Novo
  serviço de relatório (cruza dois models) — manter fora do model.
- Exportação CSV.

### R6. Tempo médio de devolução (turnaround) — gerencial

- Tempo médio entre entrega (`borrows.created_at`) e devolução (`borrows.devolution`/`Received`)
  no período, geral e por tipo de mutuário e/ou por sala.
- % de empréstimos devolvidos dentro de 24h vs. em atraso.
- Filtro: intervalo de datas.
- Fonte: novo scope/serviço. Atenção a empréstimos com **devolução parcial** (múltiplos
  `Received`): definir critério (data da última devolução = fechamento). Documentar a definição.
- Exportação CSV.

### R7. Permissões de acesso expirando — operacional

- Discentes/externos com `valid_until` próximo do vencimento ou vencido (reaproveita a lista de
  "expirando" já existente na dashboard, agora como relatório filtrável/exportável).
- Filtro: janela de dias (ex.: próximos 30 dias) e tipo.
- Fonte: `Employee::scopeGetActiveEmployees` / consulta por `valid_until`.
- Exportação CSV.

---

## Parte 3 — Frontend

- **Hub** (`Reports/Index.jsx`): grid de cards (título, descrição curta, ícone, link), filtrando
  por autorização. Evitar visual de template genérico (seguir as regras de design-quality:
  hierarquia, estados de hover/focus intencionais).
- **Componentes compartilhados:**
  - `Reports/Components/ReportFilters.jsx` — barra de filtros reutilizável (datas, selects),
    corrigindo placeholders (P4) e keys estáveis (P5).
  - `Reports/Components/ReportSummary.jsx` — faixa de totais/KPIs do relatório.
  - `Reports/Components/ExportButtons.jsx` — CSV + Imprimir (passa filtros atuais na URL).
  - Reuso dos componentes de gráfico da dashboard (Recharts) para R3/R4/R6.
- **Estado na URL:** filtros sempre via query string (compartilhável, alinhado às regras web).
- **Impressão:** estilos `@media print` ocultando navegação/sidebar.

---

## Parte 4 — Testes (Feature, seguindo o padrão do projeto)

Para cada relatório:

- **Autorização:** sem `reports.viewAny` → 403; com → 200 e componente Inertia correto
  (`assertInertia`).
- **Filtros:** validar agregações com dataset de factory conhecido (datas, situação, tipo,
  sala/chave). Incluir **caso de regressão do P2** (filtro só com `end`).
- **Status/atraso:** validar a regra centralizada (limite 24h) em backend.
- **Exportação CSV:** content-type, cabeçalho de colunas, nº de linhas = dataset sem paginação,
  e que os filtros são respeitados.
- **Turnaround (R6):** caso com devolução parcial (vários `Received`).

Meta de cobertura conforme regra do projeto (80%+ no domínio tocado).

---

## Parte 5 — Faseamento sugerido

1. **Fase 1 — Fundação + R1**
   - Centralizar regra de situação (1.3), corrigir P2/P4/P5/P8.
   - Helper de CSV (1.4) + impressão.
   - Reformular R1 com resumo e exportação.
   - Criar o hub (`Reports/Index`) já apontando para R1.
2. **Fase 2 — Operacionais**
   - R2 (chaves em atraso) e R7 (permissões expirando).
3. **Fase 3 — Gerenciais**
   - R3 (salas/blocos), R4 (mutuários), R5 (recepcionistas), R6 (turnaround) com gráficos.
4. **Fase 4 — Polimento**
   - Decisão P9 (fonte canônica de devolução) aplicada.
   - Avaliar PDF nativo (dompdf) se exigido.
   - Revisão de design dos cards/impressão.

---

## Decisões (resolvidas)

1. **P9** — ✅ `Received.*` é a fonte canônica dos relatórios; `borrows.received_by`/`returned_by`
   permanecem como resumo de fechamento (ver Resolução P9 acima). Nada removido.
2. **Autorização** — ✅ `reports.viewAny` única para todos os relatórios (primeira entrega).
3. **PDF** — ✅ CSV nativo (`App\Support\CsvExporter`, sem dependência) + impressão via `@media
   print`. PDF nativo (dompdf) fica para fase futura, se exigido.
4. **R6** — ✅ devolução parcial fechada pela data de `borrows.devolution` (registrada quando a
   última chave é recebida). Turnaround agora rotula cada linha com `category`
   (Tipo de mutuário / Sala) para evitar ambiguidade.
5. **Hub vs. abas** — ✅ rotas separadas + hub de cards.

## Refinamentos pós-revisão aplicados

- **R6**: cada linha do turnaround passou a ter `category` ("Tipo de mutuário" | "Sala"), exposta
  na tabela e no CSV; `key` de lista estável.
- **`reportSummary.keysMoved`**: troca de `pluck('id') + whereIn` por `join` em `borrow_key` com
  `count`, eliminando a materialização de IDs.
- **Validação**: `start` passou a exigir `before_or_equal:today` em todos os relatórios com
  intervalo de datas (borrows, rooms, employees, staff, turnaround), com mensagem dedicada.

## Riscos

- Queries de agregação sobre joins (R3/R5/R6) podem ser pesadas sem índices. Há observações de
  índices ausentes em colunas muito consultadas — avaliar índices antes/junto dos relatórios.
- Exportação completa (sem paginação) de períodos grandes: usar `StreamedResponse`/cursor para
  não estourar memória.

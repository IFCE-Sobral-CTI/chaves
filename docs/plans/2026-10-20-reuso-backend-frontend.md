# Plano de execução: reuso de código no backend e frontend

Data: 20/10/2026.
Status: oito etapas implementadas; validação automatizada e em navegador concluída.
Método: Ponytail full. Implementação distribuída entre três subagentes GPT-6-Luna, conforme alternativa autorizada pelo usuário, com integração, revisão e validação pelo agente principal.

## Objetivo e limites

Reduzir duplicação que já causa divergência ou exige corrigir vários arquivos para o mesmo comportamento. Executar mudanças pequenas, com consumidores migrados na mesma entrega e critérios de aceite próprios.

- Preservar autorização, validação, auditoria, transações e bloqueios das operações de empréstimo.
- Reutilizar componentes, ações e dependências existentes. Este plano não exige dependências ou migrações de banco.
- Manter as propriedades de coleção usadas pelas páginas (`blocks`, `employees`, `borrows` etc.). Padronizar metadados não exige renomeá-las para uma propriedade genérica.
- Manter consultas e regras de negócio explícitas por domínio. Evitar controller CRUD genérico, repositório genérico, gerador de formulário ou sistema de tabelas configurável.
- Tratar correções de comportamento identificadas como critérios explícitos, especialmente paginação da busca e seleção do grupo na edição de regra.
- As evidências da análise inicial são estáticas; os resultados reais da implementação e da validação em navegador estão registrados ao final.

## Evidências e oportunidades

Os caminhos das evidências são relativos à raiz do repositório; as linhas indicam o estado observado nesta revisão.

| ID | Prioridade | Evidência | Recomendação |
| --- | --- | --- | --- |
| F1 | Alta | Nove listagens repetem estado, debounce e visita: `Pages/Activity/Index.jsx:10`, `Pages/User/Index.jsx:9`, `Pages/Group/Index.jsx:9`, `Pages/Permission/Index.jsx:9`, `Pages/Rule/Index.jsx:9` e `Pages/Keys/{Block,Employee,Key,Room}/Index.jsx:9`, sob `resources/js/`. | Compartilhar a busca usando como referência `Pages/Keys/Borrow/Index.jsx:13`. Ao mudar o termo, as nove páginas enviam a página anterior logo após `setCurrentPage(1)`; também fazem visita extra ao montar. |
| F2 | Alta | `resources/js/Pages/Keys/Employee/Create.jsx:28` e `Edit.jsx:29` repetem máscara, limpeza de autorizações, campos e erros. | Um formulário do domínio de mutuários, preservando inicialização e envio específicos das páginas. |
| B1 | Alta | `app/Http/Requests/StoreEmployeeRequest.php:20` e `UpdateEmployeeRequest.php:20` duplicam toda a preparação e quase todas as regras. | Reusar a request de criação na atualização, como já ocorre em `UpdateBorrowRequest`, sobrescrevendo somente as regras de unicidade. |
| B2 | Média | `app/Models/Group.php:40`, `Permission.php:52` e os outros oito `scopeSearch` têm formatos e entradas distintos; parte da montagem das props reaparece nos controllers. | Padronizar a entrada em `Request` e compartilhar a montagem dos metadados, mantendo os filtros de cada model e as props públicas atuais. |
| B3 | Média | Controllers administrativos repetem `report($e)` e o mesmo redirect com flash de falha. `app/Http/Controllers/Controller.php:17` já centraliza parte disso em `deletionFailed`. | Centralizar somente a falha comum; conservar os tratamentos específicos e os redirects de cada operação. |
| F3 | Média | `resources/js/Pages/Keys/Room/Components/SelectBlock.jsx:4` e `Pages/Keys/Key/Components/SelectRoom.jsx:4` têm cerca de 99 linhas cada e diferenças de nome, rótulo e estado vazio. | Um seletor local para itens `{id, description}`, aplicado aos quatro formulários de salas e chaves. |
| F4 | Média | `resources/js/Pages/Reports/Rooms/Index.jsx:38`, `Staff/Index.jsx:35` e `Turnaround/Index.jsx:40` repetem filtros de período e ações. `Employees/Index.jsx:42` acrescenta vínculo. | Compartilhar primeiro os três formulários equivalentes, com componente específico para período de relatório. |
| F5 | Média | `resources/js/Pages/Reports/Borrows/Index.jsx:34` e `Components/Dashboard/BorrowsTable.jsx:4` repetem labels e cores de situação; `Pages/Keys/Borrow/Index.jsx:31` recalcula situação com Moment. | Um badge que recebe `situation`. O model `Borrow` já inclui esse campo em `$appends` e a busca retorna o model completo: aproveitar o contrato existente também na listagem. |
| F6 | Baixa | Pares Create/Edit de Room, Key, Group, Rule e Permission repetem seus próprios campos. | Formulários por domínio, depois das extrações prioritárias; manter apenas extrações que eliminem duplicação suficiente para compensar a nova interface. |
| F7 | Baixa | `resources/js/Pages/Keys/Employee/Index.jsx:21` e `Show.jsx:9` repetem o mapeamento de vínculos. | Um mapa no domínio de mutuários, se ainda houver repetição após extrair o formulário. |
| F8 | Baixa | `resources/js/Pages/Keys/Report/Index.jsx` é o relatório antigo. O controller atual renderiza `Reports/*` e a URL antiga é redirecionada em `routes/web.php:69`. | Confirmar ausência de consumidores antes de excluir a página. O seletor em `Keys/Report/Components/SelectEmployee.jsx` continua sendo importado pelo relatório novo e precisa ser preservado ou movido com seu consumidor. |

## Ordem e dependências

Executar na ordem abaixo, com uma entrega revisável por etapa. As etapas 1 e 2 trazem o maior benefício imediato. As etapas 3 e 4 podem ser implementadas independentemente das extrações visuais. A etapa 8 depende das etapas 2 e 5 para reaproveitar os padrões estabelecidos.

| Etapa | Entrega | IDs | Risco principal |
| --- | --- | --- | --- |
| 1 | Busca paginada compartilhada | F1 | Visitas duplicadas, página incorreta ou resposta antiga |
| 2 | Reuso no cadastro de mutuários, em ambas as camadas | B1, F2 | Perda de seleções, validação de unicidade e normalização |
| 3 | Contrato de busca e metadados do backend | B2 | Quebra de props, filtros ou links de paginação |
| 4 | Tratamento comum de falhas | B3 | Ocultar validação ou mudar o destino de erro |
| 5 | Seletor local de blocos e salas | F3 | Perder seleção inicial ou acessibilidade |
| 6 | Situação de empréstimo compartilhada | F5 | Divergência no limite de atraso |
| 7 | Filtros de período dos relatórios | F4 | Divergência entre tela, paginação e CSV |
| 8 | Formulários restantes e remoção de duplicação menor | F6, F7, F8 | Generalização excessiva ou remoção de consumidor ativo |

Antes de iniciar cada etapa, conferir o diff atual, todos os consumidores e as regras locais aplicáveis. Confirmar as versões instaladas antes de usar APIs de pacotes; consultar Context7 e Laravel Boost quando a implementação depender dessas APIs. Criar ou ajustar somente testes que cubram comportamento ou falhas relevantes, reaproveitando a cobertura existente.

## Etapa 1 — Busca paginada compartilhada

- [x] Extrair um hook pequeno, por exemplo `resources/js/Components/Dashboard/useIndexSearch.js`, próximo dos componentes existentes, recebendo a rota e o termo confirmado pelo servidor.
- [x] Usar o comportamento de `Keys/Borrow/Index.jsx`: debounce de 300 ms, nenhuma visita na montagem, nova busca sempre com `page: 1`, preservação de estado, substituição da URL e cancelamento na limpeza do efeito.
- [x] Migrar primeiro Borrow e Block; depois Activity, User, Group, Permission, Rule, Employee, Key e Room. São dez consumidores ao todo.
- [x] Retirar o estado duplicado de página quando ele apenas copia a prop. Preservar os links e as tabelas específicos de cada tela.
- [x] Ajustar `tests/Frontend/BorrowSearch.test.mjs`: atualmente extrai o texto do efeito até `const status`; deve passar a exercitar o código compartilhado, sem depender desse delimitador da página.

**Aceite:** abrir a listagem não produz GET extra; buscar ou limpar a partir da página 7 envia página 1; digitação rápida aguarda o debounce; mudança de termo ou saída cancela a visita anterior; paginação e navegação de retorno mantêm termo, URL e página coerentes. Exercitar pelo menos Borrow e Block como integrações reais do hook e verificar as demais migrações.

**Dependência:** não depende de renomear props nem da etapa 3.

## Etapa 2 — Cadastro de mutuários

### Backend

- [x] Fazer `UpdateEmployeeRequest` reutilizar `StoreEmployeeRequest`, seguindo o padrão existente de `UpdateBorrowRequest`.
- [x] Herdar a preparação de telefone e a limpeza de `borrowable_key_ids` para perfis sem restrição.
- [x] Reutilizar as regras comuns e sobrescrever email, email alternativo e matrícula para ignorar somente o mutuário identificado pela rota na atualização.
- [x] Preservar a autorização atual e a validação da lista de IDs existentes e distintos. Manter transações e ordem de bloqueios no `EmployeeController`.

### Frontend

- [x] Extrair `resources/js/Pages/Keys/Employee/Components/Form.jsx`, no padrão já existente de `Keys/Block/Components/Form.jsx`.
- [x] Compartilhar campos, máscara via `phone.js`, limpeza por mudança de vínculo, seletor de chaves, erros e botões.
- [x] Manter `useForm`, valores iniciais, conversão da validade, POST/PUT, títulos e destino de retorno nas páginas Create/Edit.
- [x] Garantir que erros de `borrowable_key_ids` e dos elementos da lista continuem visíveis.

**Aceite:** edição carrega validade e chaves atuais; salvar sem mudar email ou matrícula funciona; valores pertencentes a outro mutuário são rejeitados; telefone vazio, formatado, inválido e legado preservam as regras existentes; trocar para servidor/colaborador limpa autorizações; perfis restritos mantêm seleção e mensagens de validação.

**Cobertura existente:** `tests/Feature/EmployeeProfileTest.php` e `tests/Frontend/EmployeePhone.test.mjs`. Incluir apenas os casos faltantes de unicidade e do formulário compartilhado, especialmente edição com seleções prévias e limpeza por mudança de vínculo. Os testes de permissões da tela de empréstimo protegem outro fluxo e não substituem essa cobertura.

## Etapa 3 — Contrato de busca no backend

- [x] Padronizar os dez `scopeSearch` de Activity, Block, Borrow, Employee, Group, Key, Permission, Room, Rule e User para entrada `Request` e retorno `array`, conforme a convenção documentada do projeto.
- [x] Atualizar User, Permission e Rule e seus controllers, que atualmente passam apenas o termo, além dos testes e demais consumidores encontrados pela busca de referências.
- [x] Introduzir um único montador pequeno em `app/Support`, por exemplo `SearchResult`, para receber o paginador pronto, o nome da coleção e o termo, e devolver coleção, `count`, `page` e `termSearch`. Não colocar consultas, ordenação ou autorização nesse montador.
- [x] Migrar as buscas e retirar dos controllers a remontagem equivalente. Manter os nomes das coleções e o conteúdo de `can`.
- [x] Obter `count` e página do paginador; preservar termo nos links e usar a página efetivamente resolvida por ele. Cobrir o tipo numérico da página caso consumidores dependam disso.
- [x] Preservar filtros e eager loading próprios, especialmente a regra de empréstimos abertos quando não há termo. Manter relatórios e endpoints de opções com seus contratos específicos.

**Aceite:** cada listagem conserva componente Inertia, coleção, permissões, total, tamanho de página e links filtrados; busca vazia e não vazia produzem os resultados anteriores; a segunda página não perde termo; o agrupamento das condições de busca não muda.

**Verificação:** reaproveitar `EmployeeProfileTest` e `RemediationTest`; adicionar cobertura de contrato e paginação para as listagens sem proteção existente, com asserções sobre resultados e props públicas. Não criar testes que apenas reproduzam a estrutura do montador.

## Etapa 4 — Tratamento comum de falhas

- [x] Adicionar ao `Controller` um método protegido para reportar a exceção e retornar o redirect com o flash genérico já usado, aceitando rota e parâmetros necessários aos destinos de detalhes.
- [x] Migrar somente os blocos equivalentes em Block, Group, Key, Room, Employee, User, Rule, Permission e Borrow.
- [x] Fazer o ramo genérico de `deletionFailed` reutilizar esse método, mantendo o tratamento de integridade referencial existente.
- [x] Preservar os `catch` específicos de `ValidationException`, as mensagens de devolução e o comportamento próprio de Activity. Cada controller continua declarando a operação e o tratamento específico.
- [x] Manter mensagens de sucesso e autorização em seus consumidores; não criar um executor genérico de operações CRUD.

**Aceite:** falha inesperada é reportada uma vez, retorna à mesma rota e exibe o mesmo flash; destinos de detalhes mantêm o ID; erros de validação mantêm sua resposta atual; exclusões protegidas conservam a mensagem específica e o histórico.

**Verificação:** testes focados de falha e redirect, mais `BorrowHistoryDeletionTest` e os casos afetados de `BorrowTransactionTest` e `EmployeeProfileTest`. Não alterar a política global de exceções nessa etapa.

## Etapa 5 — Seletor local compartilhado

- [x] Extrair de SelectBlock/SelectRoom um componente em `resources/js/Components/Form` para itens `{id, description}`. Limitar a interface aos dados, valor, mudança, erro, identificador, rótulo e texto vazio já necessários aos dois consumidores.
- [x] Migrar `Keys/Room/{Create,Edit}.jsx` e `Keys/Key/{Create,Edit}.jsx`, eliminando as implementações duplicadas após a migração.
- [x] Preservar seleção inicial com IDs numéricos ou string, busca local, fechamento e foco. Substituir o acionador em `div` por controle operável por teclado e manter associação do rótulo e da mensagem de erro.
- [x] Manter os seletores remotos de mutuários e chaves e o seletor de relatório fora dessa extração: seus contratos, paginação e cancelamento são diferentes.

**Aceite:** os quatro formulários mostram a seleção inicial correta, filtram e selecionam; o controle abre por teclado, fecha com Escape e clique externo, mantém foco utilizável e comunica erros.

**Verificação:** cobertura pequena da seleção e filtragem compartilhadas; conferir os quatro consumidores e a operação por teclado no navegador.

## Etapa 6 — Badge de situação

- [x] Criar um `BorrowSituationBadge` junto dos componentes de dashboard existentes, com o código de situação e fallback para valor desconhecido.
- [x] Migrar `Components/Dashboard/BorrowsTable.jsx`, `Pages/Reports/Borrows/Index.jsx` e `Pages/Keys/Borrow/Index.jsx`.
- [x] Usar `borrow.situation` já serializado pelo backend na listagem, removendo seu cálculo local e o import de Moment somente onde perder o último uso.
- [x] Compartilhar labels e cores; preservar diferenças de espaçamento que forem necessárias ao layout, sem criar variações especulativas.

**Aceite:** aberto, atrasado, devolvido e fallback são consistentes; listagem, dashboard e relatório usam o mesmo código do servidor. Congelar o relógio para verificar imediatamente antes, no instante e depois do limite de 24 horas, além de devolução e timezone. O relógio segue sendo atualizado quando há nova resposta do servidor, como nos demais consumidores de `situation`.

**Verificação:** aproveitar o teste de situação em `ReportControllerTest.php`, completar limites temporais se faltarem e testar o mapeamento do badge. Coordenar com a etapa 1 porque o teste atual de busca depende da função `status` que será removida.

## Etapa 7 — Período dos relatórios

- [x] Extrair um formulário específico em `resources/js/Pages/Reports/Components`, com Início, Final, Buscar e Limpar, para Rooms, Staff e Turnaround.
- [x] Manter dados iniciais, `useForm`, submissão, rota, filtros aplicados e exportação nas páginas; compartilhar os campos, erros e ações equivalentes.
- [x] Reutilizar os dois campos de período em Employees e Borrows somente se isso reduzir a duplicação sem introduzir uma lista de opções para acomodar filtros distintos.
- [x] Continuar usando `ExportButtons` e `ReportSummary`; manter explícita a distinção entre filtros digitados e filtros efetivamente aplicados na consulta.

**Aceite:** buscar e limpar conservam destinos e resultados; erro de período continua visível; paginação e CSV mantêm os filtros aplicados; botões não enviam formulário duplicado; a impressão preserva o comportamento atual.

**Verificação:** cenários pertinentes de `tests/Feature/ReportControllerTest.php` e conferência das URLs e estados da interface. Adicionar cobertura frontend somente para lógica nova de montagem ou sincronização dos filtros.

## Etapa 8 — Reuso restante e limpeza

- [x] Extrair campos e ações comuns por domínio em Room, Key, Group, Rule e Permission, com inicialização e submissão mantidas nas páginas. Aplicar separadamente por domínio; não criar um componente universal de formulário.
- [x] Na edição de Rule, corrigir explicitamente `data.group` e `errors.group` para o contrato `group_id` ao migrar os campos; conferir seleção inicial de grupo não padrão e validação pelo backend. Registrar essa correção junto da extração, com cobertura de regressão.
- [x] Compartilhar o mapa de labels de vínculos entre Employee/Index e Employee/Show, aproveitando o contrato de tipos existente quando disponível. Evitar criar um novo campo de backend apenas para eliminar dois switches pequenos.
- [x] Confirmar em imports, renders, rotas e testes que `Keys/Report/Index.jsx` não tem consumidores. Remover somente a página comprovadamente inativa, preservando o redirecionamento legado.
- [x] Preservar `Keys/Report/Components/SelectEmployee.jsx`, usado por `Reports/Borrows/Index.jsx`, ou movê-lo para os componentes do relatório atual e atualizar o import na mesma entrega.

**Aceite:** criar e editar em cada domínio conserva payload, seleções, erros, permissões, processamento e retorno; edição de regra mantém o grupo salvo; a rota antiga continua chegando ao relatório atual; build não contém imports quebrados.

**Decisões após a análise:** conservar os seletores remotos, os formulários Create/Edit de empréstimo e os modais de devolução/exclusão com seus comportamentos próprios. As diferenças atuais não justificam unificação neste plano. Componentes já úteis (`Input`, `InputError`, `Button`, `Textarea`, `Select`, `Pagination`, `Panel` e `DeleteModal`) continuam sendo a primeira opção de reuso.

## Verificação e conclusão de cada entrega

Durante a implementação, ler a skill `testing-best-practices` antes de alterar cobertura. Usar os comandos existentes pelo Sail:

```bash
# Escolher o arquivo afetado; não executar toda a lista a cada etapa.
./vendor/bin/sail artisan test --compact tests/Feature/EmployeeProfileTest.php
./vendor/bin/sail artisan test --compact tests/Feature/RemediationTest.php
./vendor/bin/sail artisan test --compact tests/Feature/BorrowHistoryDeletionTest.php
./vendor/bin/sail artisan test --compact tests/Feature/BorrowTransactionTest.php
./vendor/bin/sail artisan test --compact tests/Feature/ReportControllerTest.php

# Mudanças em PHP e frontend, respectivamente.
./vendor/bin/sail bin pint --dirty --format agent
./vendor/bin/sail npm run test:frontend
./vendor/bin/sail npm run build
```

Executar E2E apenas nos fluxos atingidos pelas mudanças de busca, seleção ou envio, usando `tests/e2e/borrow-create.spec.js` quando pertinente. Conferir no navegador teclado, seleção inicial, feedback de erro e navegação nas páginas alteradas. Os comandos executados e seus resultados estão registrados abaixo.

As caixas de implementação indicam código extraído e consumidores migrados. As verificações automatizadas e os cenários de interação em navegador foram concluídos, conforme evidências abaixo.

## Estado de execução

- [x] Revisão do backend e identificação dos contratos afetados.
- [x] Análise do frontend por subagente e consolidação das oportunidades.
- [x] Conferência da serialização existente de `Borrow.situation` e dos consumidores do relatório antigo.
- [x] Etapa 1 — Busca paginada.
- [x] Etapa 2 — Cadastro de mutuários.
- [x] Etapa 3 — Contrato de busca no backend.
- [x] Etapa 4 — Tratamento comum de falhas.
- [x] Etapa 5 — Seletor local.
- [x] Etapa 6 — Badge de situação.
- [x] Etapa 7 — Período dos relatórios.
- [x] Etapa 8 — Formulários restantes e limpeza.


## Resultados da implementação

- Etapas 1–8 implementadas, com buscas e componentes migrados e código duplicado removido.
- Backend: `SearchResult` organiza os metadados das dez buscas; os nomes públicos das coleções e permissões foram preservados. `UpdateEmployeeRequest` herda a preparação e regras comuns; `operationFailed` centraliza o reporte e o redirect genéricos.
- Frontend: hook de busca em dez listagens; formulários por domínio para Employee, Room, Key, Group, Rule e Permission; `FormActions`, `LocalSelect`, `DateRangeFields`, `PeriodFilterForm` e `BorrowSituationBadge` compartilhados. O mapa de vínculos está junto do domínio Employee.
- A busca agora envia página 1 ao alterar o termo e cancela a visita anterior. O campo de grupo e seu erro na edição de Rule usam `group_id`; erros de autorização de chaves de qualquer índice são mostrados no formulário de Employee.
- Os cinco relatórios alterados exportam os filtros aplicados na consulta, mantendo rascunhos não enviados fora do CSV. A navegação Voltar na criação de chaves foi corrigida de `rooms.index` para `keys.index` durante a extração.
- O relatório antigo `Keys/Report/Index.jsx` foi removido após conferência das referências. Seu seletor ainda usado por `Reports/Borrows/Index.jsx` e o redirecionamento legado foram preservados.
- Um cenário preexistente em `BorrowPermissions.test.mjs` esperava ação de devolução sem chave pendente; o fixture foi corrigido para representar empréstimo parcialmente devolvido, mantendo as asserções de autorização.
- Os testes novos de busca usam descrições únicas no fixture e o teste de falha usa `RefreshDatabase`, evitando colisão de dados entre cenários.

## Evidências de validação

| Verificação | Resultado |
| --- | --- |
| `npm run test:frontend` pelo Sail | 31 testes passaram, sem falhas. |
| `SharedLabels.test.mjs` após mover o mapa para o domínio | 2 testes passaram; badge valida cor correspondente a cada situação e fallback. |
| SearchPaginationTest, OperationFailureTest, EmployeeProfileTest e ReportControllerTest | 39 testes sem falhas, 558 asserções, execução serial pelo Sail. |
| Casos afetados de RemediationTest, BorrowHistoryDeletionTest e BorrowTransactionTest | 7 testes sem falhas, 224 asserções: busca de abertos, exclusões protegidas, rollback e validação das devoluções. |
| `npm run build` pelo Sail | Build de produção concluído. |
| `pint --dirty --format agent` | Passou após formatação dos arquivos PHP. |
| `git diff --check` | Sem erros de whitespace. |

Os 46 testes backend concluíram com código de saída zero e avisos de depreciação preexistentes: `PDO::MYSQL_ATTR_SSL_CA` em PHP 8.5 (`config/database.php:62`) e schema antigo do `phpunit.xml`. O build também informa um aviso preexistente sobre carregamento futuro da configuração Vite. Os testes frontend existentes podem informar conflito da porta WebSocket 24678, sem falha nas asserções. Essas configurações não foram alteradas neste trabalho.

## Validação em navegador e ambiente

- [x] Conferir capturas dos formulários, seleções iniciais e validar navegação de retorno e paginação nas páginas alteradas.
- [x] Confirmar no Chromium teclado, Escape, clique externo e retorno de foco do seletor local nos quatro consumidores.
- [x] Instalar Chromium e bibliotecas de sistema no Sail; automatizar criação do administrador e autenticação de teste.
- [x] Executar os cenários E2E das dez listagens, busca a partir da página 7, histórico, resposta antiga, formulários, badges, cinco relatórios/CSV e URL legada.

O navegador encontrou dois defeitos corrigidos: o dashboard calculava atraso usando a data formatada, e a URL legada dos relatórios redirecionava para um caminho inexistente. O dashboard agora reutiliza `Borrow.situation`; a rota legada redireciona pela rota nomeada. Os testes backend correspondentes passaram: seis testes de HomeController (91 asserções) e um teste de redirecionamento (duas asserções).

A inspeção das capturas também corrigiu o título da edição de regras (antes “Editar Usuário”) e as descrições ausentes nos breadcrumbs de regras e permissões. A suíte completa passou com 26 testes (incluindo autenticação), e os 31 testes frontend passaram novamente. Build de produção, Pint e `git diff --check` concluíram sem erros. Permanecem os avisos preexistentes de depreciação PHP/PHPUnit, configuração Vite e porta WebSocket dos testes frontend.

Para reproduzir após recriar o container:

```bash
./vendor/bin/sail exec -u root laravel.test npx playwright install-deps chromium
./vendor/bin/sail npx playwright install chromium
./vendor/bin/sail npm run build
./vendor/bin/sail npm run test:e2e -- --project=chromium
```

A suíte usa servidor próprio em `127.0.0.1:18082`, administrador de teste e cookies ignorados pelo Git. O setup recusa configuração em cache ou banco diferente de `testing` antes de executar `migrate:fresh`. Cada execução recria esse banco; não executar simultaneamente com testes PHP que também o utilizem. O relatório HTML fica em `playwright-report/index.html`, e capturas/traces em `test-results/`. A cobertura atual usa Chromium desktop; outros navegadores e dispositivos móveis não foram executados.

O Sail foi iniciado para os testes. Outro projeto ocupa a porta 5173; o container da aplicação usa temporariamente `VITE_PORT=15173`, sem alteração do `.env`. As portas originais dos serviços auxiliares foram restauradas após a integração. Nenhuma alteração de schema da aplicação ou nova dependência faz parte desta entrega; os testes de banco foram executados em `testing`.

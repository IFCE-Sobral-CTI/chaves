# Acompanhamento da correção de empréstimos e devoluções

Plano: [correcao-emprestimos-devolucoes](2026-10-14-correcao-emprestimos-devolucoes.md).  
Última atualização: 16/10/2026.

## Estados

- `[PENDENTE]`: tarefa ainda não iniciada.
- `[EM_ANDAMENTO]`: implementação ou investigação iniciada.
- `[EM_VALIDACAO]`: código pronto; faltam verificações para concluir.
- `[CONCLUIDO]`: entrega e critérios de aceite verificados, dentro do escopo indicado.
- `[BLOQUEADO]`: depende de acesso, informação ou condição externa; registrar a dependência.

As tarefas são independentes de implantação. Código validado localmente não significa publicado em produção. O fechamento de uma etapa exige todos os critérios do plano, inclusive os operacionais.

## Etapa 1 — Medição e diagnóstico dos dados

- [EM_VALIDACAO] E1.1 Instrumentar duração, consultas, payload, resultado e identificador de requisição sem dados pessoais. Gravações, isolamento entre requisições, privacidade e falha do logger validados em testes HTTP na entrega 13; amostragem de GETs e runtime operacional ainda pendentes.
- [EM_VALIDACAO] E1.2 Separar métricas do POST e do GET após redirecionamento por método/rota. POST e status de validação verificados localmente na entrega 13; GET após redirecionamento e observação operacional ainda pendentes.
- [PENDENTE] E1.3 Medir renderização e tempo até interação em equipamento representativo.
- [PENDENTE] E1.4 Confirmar migrations e índices reais do ambiente operacional.
- [CONCLUIDO] E1.5 Criar diagnóstico somente de leitura das inconsistências, com relatório por IDs. Comando `borrows:diagnose` validado em MySQL `testing`; execução operacional ainda pendente.
- [PENDENTE] E1.6 Examinar planos de execução e registrar baseline p50/p95, queries e payload.

## Etapa 2 — Disponibilidade e validação em lote

- [CONCLUIDO] E2.1 Correlacionar devoluções por empréstimo/chave usando subconsulta SQL.
- [CONCLUIDO] E2.2 Reutilizar a consulta em criação, edição e `Key::borrowed`, preservando o wrapper de IDs para os demais consumidores.
- [CONCLUIDO] E2.3 Validar existência e disponibilidade em lote e remover a checagem duplicada no controller.
- [CONCLUIDO] E2.4 Exigir lista não vazia de IDs inteiros distintos e usar apenas IDs validados para associações.
- [CONCLUIDO] E2.5 Decisão do domínio em 15/10/2026: sem limite de quantidade de chaves por operação. Mantida validação em lote, sem máximo artificial de itens.
- [CONCLUIDO] E2.6 Exigir validade e permissão de cada chave para discentes e externos no backend.
- [CONCLUIDO] E2.7 Decisão do domínio em 15/10/2026: validade inclui todo o último dia no fuso da aplicação. Backend, opções e lista de vencimentos ajustados e validados por HTTP; evidências na entrega 16.
- [CONCLUIDO] E2.8 Testar regressão A/X/Y → devolução X → B/X, entradas inválidas, elegibilidade, edição e número constante de consultas.

## Etapa 3 — Atomicidade e concorrência

- [CONCLUIDO] E3.1 Extrair ações de aplicação para cada transição.
- [CONCLUIDO] E3.2 Transacionar gravações, associações, encerramento e auditoria.
- [CONCLUIDO] E3.3 Documentar e aplicar a ordem de bloqueio no código: chaves por ID crescente, empréstimo existente, mutuário e estado de recebimentos/pivots. Edição e cadastro de autorizações também bloqueiam chaves antes do mutuário; teste verifica a ordem SQL. O teste concorrente de edição obsoleta passou.
- [CONCLUIDO] E3.4 Revalidar disponibilidade e elegibilidade dentro da transação após os bloqueios.
- [CONCLUIDO] E3.5 Tratar conflitos e deadlocks com respostas previsíveis e tentativas limitadas.
- [CONCLUIDO] E3.6 Auditar escritores do repositório e avaliar restrição estrutural. Nenhum script/import foi encontrado gravando diretamente os pivots; o comando de reparo usa `BorrowTransaction`. FKs restritivas estão aplicadas no MySQL local e protegem exclusões históricas. Não há escritor interno fora do protocolo que exija unicidade estrutural de ocupação. Integrações externas não acessíveis e produção ficam como validação operacional de E6.8.
- [CONCLUIDO] E3.7 Testar disputa entre conexões MySQL independentes, devolução concorrente e rollback com falha injetada.

## Etapa 4 — Segurança e coerência

- [CONCLUIDO] E4.1 Restringir exclusão ao recebimento pertencente ao empréstimo autorizado.
- [CONCLUIDO] E4.2 Criar Form Request de devolução e migrar IDs para lista no corpo da requisição.
- [CONCLUIDO] E4.3 Rejeitar chaves já devolvidas e empréstimos encerrados; definir reenvio/idempotência. Reenvio rejeitado como conflito, sem novo recebimento.
- [CONCLUIDO] E4.4 Consultar IDs distintos recebidos e retornar array JSON com índices contíguos.
- [CONCLUIDO] E4.5 Definir e aplicar edição segura preservando o histórico.
- [CONCLUIDO] E4.6 Substituir encerramento manual comum por transição coerente e correção administrativa explícita, se necessária. Encerramento manual removido; não foi criada operação administrativa de correção.
- [CONCLUIDO] E4.7 Impedir reabertura por exclusão quando a chave já estiver emprestada em outro registro.
- [CONCLUIDO] E4.8 Determinar encerramento pelo conjunto pendente, inclusive em cenários legados. Comparação e transições cobertas por testes; diagnóstico local executado em 16/10/2026. As inconsistências encontradas foram registradas para reconciliação explícita em E6.1, sem alteração automática dos dados.
- [CONCLUIDO] E4.9 Auditoria das operações atuais validada por HTTP: estados anterior/posterior, IDs, executor e preservação dos eventos após exclusão, além dos testes de rollback. Motivo será exigido caso seja criada uma operação administrativa explícita; não é pendência do fluxo comum. Evidências na entrega 15.
- [CONCLUIDO] E4.10 Autorização, vínculo, reenvio e conflitos concorrentes validados localmente. Matriz das sete permissões e recusas HTTP na entrega 19; quatro disputas reais de edição com criação/devolução/outra edição na entrega 20, além dos casos anteriores de exclusão e reenvio. Validação operacional continua pendente nas etapas 1 e 6.

## Etapa 5 — Consultas e interface

- [CONCLUIDO] E5.1 Reutilizar model resolvido pela rota e evitar cálculo repetido de recebimentos.
- [CONCLUIDO] E5.2 Eliminar N+1 na agregação das chaves recebidas, com teste da quantidade de consultas.
- [EM_VALIDACAO] E5.3 Removida relação não usada da listagem e carga repetida de chaves na edição. Revisão do custo das consultas de auditoria ainda requer volume representativo.
- [EM_VALIDACAO] E5.4 Implementar seleção autorizada com campos mínimos, busca limitada e paginação. Autorização, paginação, elegibilidade, campos retornados e seleção entre páginas validados por testes; navegação real no navegador segue pendente por falta de credenciais E2E.
- [CONCLUIDO] E5.5 Preservar seleção entre buscas e corrigir efeitos e IDs React no seletor. IDs do formulário permanecem selecionados ao mudar termo/página; objetos das chaves escolhidas ficam em cache no componente.
- [CONCLUIDO] E5.6 Evitar busca inicial redundante, reiniciar na página 1 e tratar respostas antigas. Validado localmente com temporizadores e visitas controlados; navegação real no navegador pendente.
- [EM_VALIDACAO] E5.7 Busca no histórico preservada; filtros vazios removidos das consultas de seleção e listagem. Os índices atuais não atendem busca por substring; plano de execução com volume representativo pendente.
- [PENDENTE] E5.8 Comparar tempo, queries e payload antes/depois com baseline e volume representativo; disponibilidade segue revalidada no banco.

## Etapa 6 — Dados, schema e implantação

- [BLOQUEADO] E6.1 Reconciliar conflitos com confirmação física da recepção, sem escolha automática do registro correto. O diagnóstico local encontrou 14 empréstimos sem vínculos e quatro divergências de encerramento; não há acesso aos registros nem confirmação física do ambiente operacional.
- [EM_ANDAMENTO] E6.2 Reparo explícito de pivots duplicados implementado com simulação, transação, motivo, administrador e reexecução segura (entrega 18). Reparos que mudam devoluções, encerramento ou ocupação física continuam pendentes de reconciliação e definição explícita.
- [BLOQUEADO] E6.3 Unicidade aprovada pelo domínio em 15/10/2026 para `(borrow_id, key_id)` e `(received_id, key_id)`. Implementar após reconciliação/limpeza e testar migrations com volume representativo; não eliminar duplicatas automaticamente.
- [CONCLUIDO] E6.4 Remoção de `borrow_key.received` autorizada em 15/10/2026 após verificação de uso. Nenhum consumidor encontrado no código/integrações versionadas nem em triggers, views ou rotinas do banco local. Migration de remoção validada e aplicada localmente; campo retirado do `$fillable`. Evidências na entrega 17. Integrações externas não acessíveis não puderam ser inspecionadas; implantação em produção não realizada.
- [CONCLUIDO] E6.5 Entregar incrementos revisáveis e executar formatação e testes afetados em cada entrega; validações locais registradas nas entregas anteriores e nesta entrega.
- [EM_VALIDACAO] E6.6 Executar suíte completa e build quando necessário antes de publicar o conjunto. Build passou; suíte executada com 38 falhas fora dos testes de empréstimos, detalhadas na entrega 2.
- [BLOQUEADO] E6.7 Confirmar backup/restauração e estratégia para evitar escritores de versões incompatíveis; depende de acesso à infraestrutura e ao procedimento operacional.
- [BLOQUEADO] E6.8 Implantar em janela apropriada e observar erros, conflitos, deadlocks e latência; depende de acesso e janela de implantação no ambiente operacional.
- [EM_ANDAMENTO] E6.9 Preparar reversão segura do código e reconciliação específica dos reparos de dados. Para a entrega 5, preservar FKs restritivas ao reverter código; executar o `down` reabre o risco das cascatas e de perda do responsável final. Estratégia operacional completa ainda pendente.
- [BLOQUEADO] E6.10 Verificar metas de desempenho e observar período representativo de atendimento após implantação.

## Registro das entregas

### Entrega 1 — Disponibilidade e validação em lote

Estado: `[CONCLUIDO]`.

- `Borrow::pendingKeyIds()` usa subconsulta correlacionada por empréstimo e chave; `keysNotReceived()` mantém compatibilidade com consumidores que precisam de array.
- Criação, edição e `Key::borrowed()` usam consultas SQL sem carregar listas globais de disponibilidade no PHP.
- Requests compartilham a validação em lote de existência, disponibilidade e elegibilidade. Autorização ocorre antes da validação e continua no controller.
- `receivedKeys()` usa uma consulta distinta, com lista de IDs inteiros e índices contíguos.
- Controllers reutilizam modelos e resultados; removida verificação duplicada de disponibilidade e separação de atributos de empréstimo e IDs do pivot.
- Adicionados testes funcionais e de quantidade de consultas. Corrigida pré-condição aleatória de empréstimo aberto em testes existentes.
- Ainda não há transações/bloqueios, diagnóstico de produção ou implantação. Não considerar os riscos concorrentes resolvidos por esta entrega.

Validação concluída:

- `./vendor/bin/sail artisan test --compact tests/Feature/BorrowAvailabilityTest.php tests/Feature/RemediationTest.php tests/Feature/BorrowScopeTest.php`: exit code 0, 15 testes sem falhas e 65 assertions, em MySQL com banco `testing`; duração de 121,45 s. O PHPUnit classificou os testes como `deprecated` e avisou sobre o schema XML antigo; não foram apresentados como testes sem avisos.
- Pint: passou nos sete arquivos PHP alterados/novos.
- `git diff --check`: sem erros.
- Confirmada uma consulta de disponibilidade na gravação para 1 e 5 chaves; agregação de recebimentos em uma consulta mesmo com histórico duplicado.
- A primeira execução dos testes existentes revelou um cenário aleatório em `RemediationTest`; ajustado para iniciar explicitamente com `devolution = null`.
- Suíte completa, medição de latência e implantação continuam pendentes; não houve mudanças de schema ou frontend nesta entrega.

### Entrega 2 — Transações e proteção contra concorrência

Estado: `[EM_VALIDACAO]` para o conjunto; os itens concluídos acima têm validação local. Commit de implementação: `3c77eb7`, enviado para `origin/main` em 15/10/2026. Isso não comprova implantação em produção.

- Ações em `app/Actions/Borrows/` centralizam criação, edição, devolução e exclusões em transações, incluindo pivots, encerramento e auditoria.
- `BorrowTransaction` bloqueia chaves em ordem crescente de ID, depois empréstimo e demais registros. Reconfere os vínculos atuais e limita tentativas de transação a três; conflitos de disponibilidade e erros MySQL 1205/1213 recebem mensagens previsíveis.
- Leituras com bloqueio revalidam disponibilidade e elegibilidade após espera, inclusive com snapshot anterior em `REPEATABLE READ`.
- Exclusão verifica o vínculo do recebimento e impede reabrir chave pendente em outro empréstimo. Reenvios de devolução não criam segundo recebimento.
- Edição preserva chaves já recebidas, proíbe alteração de chaves em empréstimo encerrado e impede encerramento pela remoção da última pendência. Chaves históricas continuam visíveis mesmo após novo empréstimo.
- Encerramento manual retirado do formulário e proibido nos requests. Erros de devolução aparecem uma única vez no modal.
- IDs da devolução ainda chegam pela URL, com validação estrita no controller; Form Request e migração para o corpo continuam pendentes em E4.2.

Validação realizada:

- `./vendor/bin/sail artisan test --compact`: 82 testes, 192 assertions, duração de 608,09 s; resultado do runner: 43 `deprecated`, 38 falhas e 1 sucesso sem avisos. Os 26 testes de `BorrowAvailabilityTest`, `BorrowConcurrencyTest` e `BorrowTransactionTest` terminaram sem falhas; avisos de depreciação permanecem.
- Concorrência validada em MySQL `testing`, com processos/conexões independentes: duas criações, devoluções simultâneas, novo empréstimo após devolução total/parcial e exclusão de recebimento disputando a chave com novo empréstimo.
- Falhas injetadas verificam rollback de empréstimo, recebimento, pivots, edição, exclusões, encerramento e auditoria.
- As 38 falhas da suíte ocorreram em cadastro público, expectativa de HTTP 200 na raiz que redireciona e colisões de unicidade nos seeders de outros testes. A suíte completa não está aprovada.
- `./vendor/bin/sail npm run build`: passou, com aviso sobre o carregamento futuro da configuração Vite.
- `./vendor/bin/sail php vendor/bin/pint --dirty --test`: passou. `git diff --check`: sem erros.
- Não foram validados navegador, latência operacional, dados reais, implantação, auditoria de scripts/imports ou todos os casos de autorização/edição concorrente. Sem novas dependências ou mudanças de schema.

### Entrega 3 — Contrato de entrada da devolução

Estado: `[CONCLUIDO]` no escopo de E4.2, com validação local em 15/10/2026. Implantação não realizada.

- `ReceiveKeysRequest` autoriza antes da validação e exige nome de quem devolveu como string de 2 a 255 caracteres e lista não vazia de IDs inteiros positivos distintos.
- `borrows.receive` passa a aceitar `POST /admin/borrows/{borrow}/receive/keys`, com `keys` e `returned_by` no corpo. Removidos o parâmetro de IDs da URL e seu parser no controller.
- A existência e a pendência das chaves continuam verificadas em lote sob bloqueio pela ação transacional. A validação da estrutura ocorre antes de qualquer consulta dessa ação, inclusive para entradas aninhadas.
- O formulário usa `useForm` como estado único da seleção, envia a lista no corpo e exibe erros gerais e de cada item. Os consumidores da rota e os testes de regressão foram migrados juntos.
- Frontend e backend precisam ser implantados juntos; telas abertas com o formulário antigo precisam ser recarregadas. A URL anterior não foi mantida como rota de compatibilidade.

Validação realizada:

- `./vendor/bin/sail artisan test --compact tests/Feature/BorrowTransactionTest.php tests/Feature/RemediationTest.php`: 20 testes sem falhas, classificados como `deprecated`, e uma falha na asserção do novo teste de entradas inválidas, corrigida de `keys` para `keys.0`; 114 assertions, duração de 138,96 s.
- Reexecução do caso corrigido: `./vendor/bin/sail artisan test --compact tests/Feature/BorrowTransactionTest.php --filter=test_return_validates_body_without_writing_receipts_or_audit`: exit code 0, 1 teste `deprecated`, 64 assertions, duração de 15,66 s. Cobertos 15 corpos inválidos sem recebimento, pivot, encerramento ou auditoria parcial.
- Os 21 testes afetados foram validados entre essas execuções, incluindo autorização, chave de outro empréstimo, reenvio, devolução parcial/total, responsáveis e rollback.
- `./vendor/bin/sail npm run build`: passou, com o aviso já registrado sobre o carregamento futuro da configuração Vite.
- `./vendor/bin/sail php vendor/bin/pint --dirty --test`: passou. `git diff --check`: sem erros.
- Suíte completa e concorrência entre processos não foram repetidas nesta entrega: a ação e o protocolo de bloqueio não mudaram. As 38 falhas da última suíte completa permanecem como pendência de E6.6. Navegador e produção não foram validados.

### Entrega 4 — Diagnóstico de inconsistências e auditoria dos escritores

Estado: `[CONCLUIDO]` no escopo da ferramenta de E1.5, validada localmente em 15/10/2026. A auditoria de E3.6 permanece em andamento; nenhuma correção automática ou execução em produção foi realizada.

- Adicionado `app/Console/Commands/DiagnoseBorrows.php`, registrado automaticamente como `borrows:diagnose`.
- O comando executa seis consultas de leitura em uma transação, sem `FOR UPDATE`, gravação de auditoria ou alteração dos registros. A consistência do snapshot entre as consultas depende do isolamento da conexão; em MySQL `REPEATABLE READ`, as consultas compartilham o snapshot da transação.
- Saída em JSON Lines: uma linha por ocorrência, com `issue` e apenas os IDs necessários (`borrow_id`, `key_id`, `received_id`, `pivot_id`) ou o indicador `closed`; a última linha contém `summary` com a quantidade de ocorrências de cada categoria. Uma chave com dois empréstimos pendentes produz duas ocorrências, uma por vínculo.
- Categorias: `multiple_pending_borrows`, `duplicate_returns`, `empty_receipts`, `empty_borrows`, `foreign_returned_keys` e `closure_mismatches`.
- Devoluções são correlacionadas por empréstimo/chave. Histórico de devolução parcial seguido de novos empréstimos não gera falso conflito. Pendências de empréstimos já encerrados também são examinadas, sem depender do filtro de disponibilidade usado no atendimento.
- Duplicatas em um mesmo recebimento e em recebimentos distintos são identificadas pelos IDs dos pivots. Não há nomes, matrículas, contatos, observações ou nome de quem devolveu no relatório.

Uso e interpretação:

```bash
./vendor/bin/sail artisan borrows:diagnose --no-ansi
```

O comando usa a conexão configurada do ambiente em que for executado; os testes usam exclusivamente `testing`. Exit code 0 indica diagnóstico executado, inclusive quando encontra inconsistências. A linha final `summary` confirma que todas as categorias foram processadas; zero em todas as categorias significa ausência dos seis tipos examinados naquele snapshot. O relatório por IDs deve ter acesso restrito. As consultas percorrem o histórico; custo e duração no volume operacional ainda não foram medidos.

Auditoria estática do repositório:

- As gravações diretas do fluxo HTTP de empréstimos passam pelas ações transacionais. Não foram encontrados imports ou scripts operacionais adicionais de gravação de empréstimos; `BorrowSeeder`/factories escrevem dados de exemplo, e o worker de concorrência é exclusivo dos testes.
- Exclusões em `KeyController`, `RoomController`, `BlockController`, `EmployeeController`, `UserController` e `PermissionController` têm caminhos de cascata até empréstimos, recebimentos ou pivots, conforme as migrations. Esses caminhos ainda não seguem o protocolo de bloqueio das ações e podem remover histórico sem a auditoria de cada registro filho. Precisam de tratamento antes de declarar todos os escritores protegidos.
- Um índice único histórico por `(borrow_id, key_id)` não impede dupla ocupação entre empréstimos diferentes. A decisão de restrição estrutural fica pendente da revisão das cascatas, dos escritores externos e da reconciliação dos dados; nenhuma migration foi adicionada.
- `borrow_key.received` aparece na migration e no `$fillable` de `BorrowKey`, sem consumidores desse model encontrados no código da aplicação. A coluna será preservada até confirmar integrações/SQL externos e preparar remoção compatível; o diagnóstico usa o histórico de recebimentos como fonte de verdade.

Validação realizada:

- `./vendor/bin/sail artisan test --compact tests/Feature/BorrowDiagnosisTest.php`: exit code 0, 2 testes `deprecated`, 54 assertions, duração de 22,75 s, em MySQL `testing`.
- Cobertos os seis tipos de anomalia, duplicatas no mesmo recebimento e em recebimentos distintos, devolução parcial, novo empréstimo após devolução, encerramento prematuro e empréstimo totalmente recebido ainda aberto. Conferidos SQL somente de leitura, ausência de locks de escrita, campos da saída e preservação das contagens dos dados e da auditoria.
- A primeira execução revelou dependência do ID fixo 1 na factory; a fixture nova foi corrigida para escolher um usuário existente, sem alterar a factory compartilhada.
- `./vendor/bin/sail php vendor/bin/pint --dirty --test`: passou. `git diff --check`: sem erros.
- Sem alterações de frontend, schema ou ações transacionais; build, suíte completa, testes de concorrência e execução operacional não foram repetidos. Permanecem os avisos de depreciação e as falhas da última suíte completa registradas em E6.6.

### Entrega 5 — Preservação do histórico nas exclusões de cadastros

Estado: `[CONCLUIDO]` no escopo local da proteção de referências históricas, validado em 15/10/2026. Nenhuma migration foi aplicada no banco da aplicação ou em produção, apenas em `testing`. E3.6 continua em andamento pelas pendências operacionais e de escritores externos.

- A migration `2026_10_15_000000_protect_borrow_history_from_cascading_deletes.php` substitui por `RESTRICT` seis FKs: `borrow_key.key_id`, `key_received.key_id`, `borrows.employee_id`, `borrows.user_id`, `borrows.received_by` e `receiveds.user_id`.
- Chaves, mutuários, criadores, recebedores e responsáveis finais com histórico não podem ser excluídos, mesmo por SQL direto. A restrição também impede as cascatas originadas por exclusão de salas, blocos e permissões. Empréstimos abertos e encerrados são preservados; chaves de recebimentos legados fora do vínculo atual também ficam protegidas.
- Cadastros sem histórico continuam excluíveis, inclusive suas cascatas sem uso. A exclusão explícita de empréstimos e recebimentos pelas ações existentes continua permitida; suas FKs para os respectivos pivots não foram alteradas.
- Os seis controllers de cadastros usam uma resposta compartilhada para o erro MySQL 1451, explicando que existe histórico de empréstimos/devoluções. Outros erros continuam reportados, com resposta genérica, sem expor detalhes SQL.
- A migration adiciona cada FK substituta antes de remover a anterior, sem desativar `foreign_key_checks` ou remover dados. Mantém `ON UPDATE CASCADE`; o `down` restaura cinco `ON DELETE CASCADE` e o `SET NULL` original de `received_by`.

Aplicação e reversão:

- A proteção só vale depois que a migration for aplicada no ambiente. Antes da implantação, confirmar os nomes das FKs e migrations reais (E1.4), backup/restauração e janela sem escritores incompatíveis (E6.7/E6.8).
- As alterações de FKs executam DDL por tabela e não formam uma transação atômica para toda a migration no MySQL. Podem copiar tabelas e bloquear operações; duração com volume operacional ainda não foi medida. Suspender as escritas na janela da alteração.
- Preferir reverter apenas o código e manter as FKs restritivas. O `down` existe para reversibilidade técnica, mas restaura o comportamento que permite perda de histórico e de `received_by`; não executar indiscriminadamente sobre dados operacionais.
- Esta restrição preserva referências históricas; ela não cria exclusividade estrutural de chave emprestada entre dois empréstimos. Escritores externos ainda precisam respeitar as ações/protocolo ou ser tratados em E3.6.

Validação:

- `./vendor/bin/sail artisan test --compact tests/Feature/BorrowHistoryDeletionTest.php tests/Feature/BorrowTransactionTest.php`: exit code 0, 22 testes `deprecated`, 320 assertions, duração de 162,00 s.
- `./vendor/bin/sail artisan test --compact tests/Feature/BorrowConcurrencyTest.php tests/Feature/BorrowHistoryDeletionTest.php --filter='test_key_deletion_waits_for_creation_and_preserves_committed_history|test_history_constraint_rollback_and_reapply_preserve_data|test_unused_descendants_can_still_be_cascade_deleted'`: exit code 0, 3 testes `deprecated`, 31 assertions, duração de 132,40 s. São 24 testes distintos validados nas duas execuções, com um caso repetido após a recriação do banco.
- Cobertas as seis exclusões por HTTP com empréstimo aberto e encerrado, sem perda de pivots, recebimentos ou auditoria; exclusões permitidas de cadastros/cascatas sem histórico; SQL direto sobre chave de recebimento legado, usuário recebedor e usuário presente apenas em `received_by`; e exclusão explícita de empréstimo com seus recebimentos/pivots.
- Em processos MySQL independentes, a exclusão da chave aguarda a criação concorrente e recebe conflito, preservando chave e empréstimo confirmado. Conferidos os seis `RESTRICT`, os seis `ON UPDATE CASCADE` e a restauração exata das regras anteriores no `down`, com dados preservados após `down/up`.
- Corrigida a fixture nova para registrar os Gates após o seed, inclusive quando a classe de concorrência anterior recria e desfaz o schema. Não houve alteração da autorização da aplicação.
- `./vendor/bin/sail php vendor/bin/pint --dirty --test`: passou. `git diff --check`: sem erros.
- Sem alterações de frontend; não há build novo nesta entrega. Suíte completa, volume operacional, implantação e medição em produção permanecem pendentes. Os avisos de depreciação e as 38 falhas da última suíte completa continuam registrados em E6.6.

### Entrega 6 — Seleção de chaves controlada pelo formulário

Estado: `[CONCLUIDO]` no escopo do seletor local de E5.5, validado em 15/10/2026. Sem implantação ou alteração de backend/schema.

- `Search` usa `values` do formulário como fonte única da seleção. Removidas as cópias locais da lista e dos IDs e o efeito que notificava `onChange` ao montar ou apenas pesquisar.
- A lista disponível é derivada dos dados, termo e seleção atuais a cada renderização. Os selecionados continuam separados do filtro textual; a busca local não altera seus IDs. Removido o atraso artificial de 300 ms do filtro local, sem introduzir requisições.
- Linhas usam `item.id` como chave React. `SearchItem` renderiza o ícone diretamente conforme a ação, sem estado/efeito que prendia o ícone à primeira montagem.
- Incluído botão nativo com `type="button"`, nome acessível indicando ação/chave/sala e foco visível. A ativação continua pelo evento da linha, inclusive o clique gerado pelo botão via teclado. Campo de busca recebeu rótulo associado ao seu ID.
- Criação e edição já passam `data.keys` e atualizam essa lista via `setData`; ambas reutilizam o componente sem mudança de contrato. A validação transacional do backend continua sendo a verificação final da disponibilidade.
- E5.4 permanece pendente: os dados ainda são carregados integralmente. Preservação dos objetos selecionados entre páginas de uma futura busca remota deverá acompanhar essa entrega. Busca da listagem, requisição inicial e respostas antigas continuam em E5.6.

Validação realizada:

- Adicionado `npm run test:frontend`, usando o runner nativo do Node, React e Vite já instalados, sem novas dependências. O Vite de testes usa configuração própria e cache separado do servidor de desenvolvimento; não carrega o plugin Laravel nem altera `public/hot`.
- `./vendor/bin/sail npm run test:frontend`: exit code 0, 3 testes aprovados, 0 falhas; duração final de 568,29 ms.
- Cobertos renderização imediata da seleção e das ações, renderizações com dados atualizados/reordenados e seleção alterada/limpa pelo formulário, encaminhamento do ID na ativação da linha e botão que não submete o formulário.
- São testes de renderização no servidor e chamada do handler, não testes de montagem/atualização no DOM, digitação ou navegação real por teclado. Esses fluxos ainda não foram exercitados no navegador.
- `./vendor/bin/sail npm run build`: passou, com o aviso já registrado sobre o futuro carregamento nativo da configuração Vite. `git diff --check`: sem erros.
- Suíte PHP completa, concorrência MySQL, navegador e medições de desempenho não foram repetidos; a entrega não altera ações, rotas ou schema. As falhas da última suíte completa continuam pendentes em E6.6.

### Entrega 7 — Busca da listagem sem requisição redundante

Estado: `[CONCLUIDO]` no escopo local de E5.6, validado em 15/10/2026. Sem implantação.

- A listagem não agenda uma visita quando o termo local já corresponde ao termo recebido do servidor, inclusive na montagem e após a resposta da pesquisa.
- Pesquisa e limpeza do campo enviam explicitamente `page: 1` após 300 ms. Os links das linhas usam a página atual recebida do servidor, sem cópia em estado local.
- A limpeza do efeito remove o temporizador e cancela sua visita pelo token do Inertia quando o termo muda ou a página é desmontada, impedindo que uma busca anterior ainda pendente substitua a atual.
- Mantidos `preserveState` e `replace`, sem alterar consultas, autorização, rotas ou seletores de criação/edição.

Validação realizada:

- `./vendor/bin/sail npm run test:frontend`: 7 testes aprovados, 0 falhas. Os quatro novos testes executam o efeito extraído do componente com hooks, temporizadores e router controlados: montagem com/sem termo, pesquisa/limpeza na página 1, limpeza do debounce, cancelamento de visita e resposta reconhecida sem nova busca.
- Essa verificação não monta o componente no DOM nem comprova navegação real ou comportamento de rede do Inertia. O contrato de `onCancelToken` foi conferido na documentação v3 via Context7.
- `./vendor/bin/sail npm run build`: passou; permanece o aviso existente sobre o futuro carregamento nativo da configuração Vite. `git diff --check`: sem erros.
- Não repetidos testes PHP, concorrência MySQL, navegador ou medições operacionais. As pendências de E5.4, E6.6 e de implantação permanecem.

### Entrega 8 — Remoção de carregamentos redundantes

Estado: `[EM_ANDAMENTO]` para E5.3; otimizações locais concluídas, revisão do custo da auditoria ainda pendente.

- `Borrow::search()` deixa de carregar `received` na listagem de empréstimos. A página `Keys/Borrow/Index` não consome essa relação; relatórios e detalhes usam caminhos próprios e preservados.
- A edição carrega uma vez `employee` e `keys.room` e deriva os IDs das chaves desse conjunto carregado, evitando a consulta de chaves anterior ao `load()`.
- A auditoria relacional de alterações ainda não foi medida no volume operacional e permanece pendente em E5.3.
- Não rodei testes ou build nesta alteração; o diff foi conferido. Sem mudanças de contrato, schema ou dados.

### Entrega 9 — Busca limitada para seleção de mutuário e chaves

Estado: `[EM_VALIDACAO]` no escopo de E5.4/E5.5, implementação local em 15/10/2026. Sem implantação.

- Formulários de criação e edição recebem no máximo 20 chaves disponíveis, mais as chaves já vinculadas no empréstimo em edição. A lista inicial de mutuários foi removida; a edição recebe somente o mutuário atual.
- Novas rotas JSON buscam mutuários ativos por nome/matrícula e chaves disponíveis por número/sala, em páginas de 20, com projeções mínimas (`id`, identificação e relação necessária da sala). A edição pode consultar as mesmas opções, mantendo as chaves vinculadas ao próprio empréstimo elegíveis para exibição.
- Cada rota exige `borrows.create` ou `borrows.update` para o empréstimo resolvido. Termo e página passam por validação; a gravação continua validando elegibilidade/permissão e disponibilidade transacionalmente.
- Ambos os seletores carregam mais resultados, cancelam consultas antigas e mantêm a seleção no formulário; a lista de chaves retém os dados das selecionadas para continuar exibindo-as quando resultados mudam.
- `npm run build`, `php -l` no controller e rotas, e `git diff --check` passaram. A consulta de `route:list` não pôde iniciar porque o MySQL configurado como `mysql` está indisponível no ambiente.
- Testes HTTP de autorização/paginação, teste de regressão para seleção entre páginas e validação no navegador continuam pendentes. Não alterar o limite de 20 sem revisar o fluxo de paginação e a experiência da recepção.

### Entrega 10 — Filtros vazios e revisão inicial dos índices de busca

Estado: `[EM_ANDAMENTO]` para E5.7; otimização segura implementada em 15/10/2026, análise de plano operacional pendente.

- Quando o termo está vazio, as consultas da lista de empréstimos e dos seletores deixam de aplicar `LIKE '%%'` ou `whereHas` de pesquisa. Com termo, a busca por substring e os resultados permanecem iguais.
- A migration existente cria índice B-tree em `keys.number`, mas a busca por chave usa `LIKE '%termo%'`; esse padrão não aproveita o índice B-tree. Não foi acrescentado índice sem `EXPLAIN` e volume representativo.
- `./vendor/bin/pint --dirty`, `php -l` em `BorrowController.php` e `Borrow.php`, e `git diff --check` passaram. Não rodei testes; MySQL local continua indisponível para planos de execução.
- E5.7 segue aberto até confirmar demanda de busca em históricos, examinar planos reais e avaliar índices com dados representativos. E5.8 e medições de produção também permanecem pendentes.

### Entrega 11 — Estados anterior e posterior na auditoria do empréstimo

Estado: `[EM_ANDAMENTO]` para E4.9; transições cobertas no código em 15/10/2026, revisão dos registros e testes pendentes.

- Atualização registra mutuário, chaves totais, chaves pendentes e encerramento antes/depois quando muda mutuário ou conjunto de chaves.
- Devolução registra pendências e encerramento/responsável antes/depois junto ao recebimento; exclusão de devolução registra o mesmo estado e o ID do recebimento removido.
- Exclusão de empréstimo grava evento transacional `borrow_deleted` com estado anterior e ausência posterior, incluindo executor no caminho HTTP. O parâmetro do executor é opcional para preservar chamadas internas existentes.
- Os motivos não foram adicionados a operações comuns. Não há fluxo de correção administrativa que possa solicitar e validar esse motivo.
- Na exclusão de recebimento, o registro do recebimento é bloqueado antes de ler os pivots para capturar o estado anterior, mantendo a ordem de bloqueios documentada.
- `./vendor/bin/pint --dirty`, `php -l` nos arquivos alterados e `git diff --check` passaram. Não rodei testes; inspeção de `activity_log` no MySQL e validação de rollback seguem pendentes.

### Entrega 12 — Métricas de requisições de empréstimo

Estado: `[EM_VALIDACAO]` para E1.1/E1.2; instrumentação local implementada em 15/10/2026. Ainda não observada em ambiente operacional.

- Middleware registra em `borrow.request_metrics` o ID da requisição, nome da rota, método, status, duração no servidor, tamanho do corpo da resposta, quantidade de consultas e tempo acumulado no banco. POST e GET após redirecionamento aparecem como eventos separados.
- Registra todas as rotas de empréstimo que não são GET e amostra 10% dos GETs. A amostra é fixa no código.
- Listener de consultas registra apenas duração e contagem; não grava SQL, bindings, URL, parâmetros de busca ou dados pessoais. O ID também retorna no cabeçalho `X-Request-ID` das respostas medidas.
- Coletor é scoped por ciclo de requisição para não acumular consultas entre requisições em workers persistentes. Falha no logger não altera o resultado da operação.
- `php -l` nos arquivos alterados, `./vendor/bin/pint --dirty` e `git diff --check` passaram. Não rodei testes nem medi overhead; MySQL não está disponível no ambiente para validar o runtime.
- E1.3 (navegador), volume/privacidade operacional e calibragem da amostra permanecem pendentes; os tempos registrados são apenas do servidor e não substituem TTI.

### Entrega 13 — Validação HTTP das opções e métricas

Estado: `[CONCLUIDO]` no escopo dos testes HTTP e das correções locais descritas, em 15/10/2026. E5.4, E1.1 e E1.2 permanecem em validação pelas verificações no navegador e no ambiente operacional. Sem implantação ou alteração de schema/dependências nesta entrega.

- Corrigido o filtro de mutuários nas duas rotas de opções: servidores e colaboradores seguem a elegibilidade das ações; discentes e externos exigem validade não nula e não expirada. Antes, validade nula permitia exibir discentes/externos que a gravação rejeitaria.
- A paginação das opções desempata por ID, inclusive com nomes iguais. As listas iniciais de chaves disponíveis na criação/edição passam a selecionar apenas `id`, `number`, `room_id` e a identificação necessária da sala, sem observações da chave.
- `BorrowOptionsTest` cobre as quatro rotas: requisições sem autenticação, permissões distintas de criação/edição, empréstimo inexistente, filtros inválidos, elegibilidade por tipo, busca por matrícula/sala, projeções mínimas e páginas de 20 resultados.
- A regressão de disponibilidade confirma devolução parcial seguida de novo empréstimo, sem liberar a chave em outra consulta de criação. A edição continua exibindo suas próprias chaves vinculadas, inclusive as já devolvidas e emprestadas novamente.
- O caso de igualdade da validade fixa o relógio na meia-noite, que a coluna `DATE` consegue armazenar. As primeiras tentativas com horário corrente/precisão de segundos revelaram a premissa incorreta da fixture; não foi alterada a semântica temporal do domínio para fazer o teste passar.
- `BorrowRequestMetricsTest` valida requisições de escrita no middleware real: ID no cabeçalho correspondente ao log, contadores reiniciados, consultas fora da coleta desconsideradas, tamanho da resposta e status 422 para falha de validação.
- O teste verifica uma lista fechada de campos nas métricas e ausência de dados privados enviados no corpo, termo de busca, mensagem de validação e bindings SQL. Também verifica que rotas fora de `borrows.*` não geram métricas e que falha no logger preserva a resposta da operação. Foi preservada a correção local já existente de inicialização do coletor no middleware.

Validação realizada:

- Versões confirmadas via Sail/Composer: PHP 8.5.11, Laravel 13.35.0, Inertia Laravel 3.5.1, Activitylog 5.1.1 e PHPUnit 11.5.57. Schema da coluna `employees.valid_until` conferido via Boost, somente leitura.
- `./vendor/bin/sail bin phpunit tests/Feature/BorrowOptionsTest.php tests/Feature/BorrowRequestMetricsTest.php`: exit code 0, 11 testes, 182 assertions, duração de 80,916 s. Permanecem um aviso de depreciação do runtime e um do PHPUnit; não foram tratados como execução sem avisos.
- `./vendor/bin/sail npm run test:frontend`: 7 testes aprovados, 0 falhas, duração de 524,02 ms. São as verificações locais de renderização e do efeito da busca; não substituem validação de interação no navegador.
- `./vendor/bin/sail npm run build`: passou; permanece o aviso existente de carregamento futuro da configuração Vite.
- `./vendor/bin/sail bin pint --dirty --format agent`: passou, ajustando a ordem dos imports dos testes novos. `git diff --check`: sem erros.
- O PHPUnit foi executado diretamente pelo Sail porque o wrapper `artisan test` encaminha `--no-interaction` ao runner, que não aceita essa opção.
- Não repetidos suíte completa, concorrência MySQL, navegador, distribuição da amostra de GETs, overhead da instrumentação ou medições em produção. Auditoria das transições (entrega 11) e validação das otimizações da listagem (entrega 8) seguem com as pendências registradas. Alterações preexistentes de ferramentas, dependências, ambiente e E2E no workspace foram preservadas.

### Entrega 14 — Exibição dos estados da auditoria

Estado: `[CONCLUIDO]` no escopo da exibição local dos registros, em 15/10/2026. E4.9 permanece em andamento para conferir o conteúdo persistido de cada transição e as demais pendências já registradas.

- Corrigida a tela de detalhes: alterações automáticas do Activitylog v5 passam a usar `attribute_changes`, mantendo compatibilidade com `properties.attributes/old` dos registros antigos.
- Estados manuais `before/after` e metadados como `key_ids` e `received_id` agora aparecem. Exclusões com `after: null`, listas vazias, objetos, zero e booleanos preservam seu conteúdo; strings continuam escapadas pelo React.
- Cinco testes novos de renderização cobrem formatos atual/legado, metadados, exclusão, valores estruturados, escape de conteúdo e registros sem propriedades. A suíte frontend passou com 12 testes, 0 falhas (525,76 ms); houve aviso de porta WebSocket 24678 já ocupada.
- Build de produção via Sail aprovado; permanece o aviso existente sobre o futuro carregador nativo da configuração Vite. Sem mudanças de schema, dependências ou escritores de auditoria.
- Validação visual no navegador, conferência dos snapshots gravados por transição, custo das consultas de auditoria e suíte completa não executados nesta entrega.
- Regressão transacional: `./vendor/bin/sail bin phpunit tests/Feature/BorrowTransactionTest.php` passou (exit code 0): 17 testes, 144 assertions, 1 min 56,043 s. Inclui os casos existentes de rollback da auditoria junto com empréstimos, pivots e recebimentos; permanece uma depreciação do runtime e uma do PHPUnit. Esses casos não conferem o conteúdo de cada snapshot persistido.
- `git diff --check` passou. Não houve alteração de PHP, portanto Pint não se aplica a este incremento.

### Entrega 15 — Validação dos snapshots persistidos

Escopo: E4.9, validado por requisições HTTP e leitura dos eventos persistidos no banco de testes.

- `BorrowAuditTest` acrescenta sete casos: criação com IDs das chaves; edição de mutuário/chaves preservando chave já devolvida; devoluções parcial e final; exclusão de recebimento que reabre o empréstimo; exclusão do empréstimo com cascata; e edição apenas da observação com `attribute_changes` do Activitylog v5.
- Os casos de alteração usam executor diferente do criador. Conferem tipo/ID do sujeito, um único evento específico da operação, autoria e propriedades presentes com valores/tipos esperados, inclusive listas vazias e campos nulos. Exclusões verificam a persistência da auditoria mesmo após remoção do sujeito e dos pivots.
- A primeira execução revelou formatos diferentes para `devolution`: o snapshot imediatamente após encerrar serializava o Carbon em ISO UTC; leituras posteriores do banco usavam data/hora textual no fuso da aplicação. A ação `ReceiveKeys` passa a atribuir `now()->toDateTimeString()`, mantendo o horário persistido e o formato consistente, sem consulta adicional nem alteração do schema.
- O plano original exige motivo apenas quando houver correção administrativa. Como essa operação não existe, não foi acrescentado campo obrigatório ao fluxo comum.

Validação realizada em 15/10/2026:

- Primeira execução de `BorrowAuditTest`: 7 testes, 111 assertions e 1 falha no formato de `after.devolution`, que motivou a correção descrita acima.
- Após a correção, `./vendor/bin/sail bin phpunit tests/Feature/BorrowAuditTest.php tests/Feature/BorrowTransactionTest.php`: exit code 0, 24 testes, 257 assertions, duração de 2 min 39,480 s. Permanecem uma depreciação do runtime e uma do PHPUnit. A regressão confirma rollback de operações e auditoria após falhas injetadas.
- `./vendor/bin/sail bin pint --dirty --format agent` e `git diff --check` passaram.
- E4.9 concluído para as operações atuais. Não foram repetidos navegador, concorrência entre conexões, suíte completa ou validação operacional. Não há frontend alterado nesta entrega; build e testes frontend não repetidos. Os registros antigos da auditoria não foram reescritos e podem conservar os formatos anteriores de data. Planos de melhorias de mutuários preexistentes no workspace foram preservados.

### Entrega 16 — Decisões do domínio e validade até o fim do dia

- Decisões confirmadas pelo responsável em 15/10/2026: sem máximo de chaves por operação; validade inclui todo o último dia; unicidade dos dois pivots aprovada após limpeza. Plano original atualizado para refletir essas decisões.
- A elegibilidade compara dias no fuso da aplicação, tanto no preflight quanto na revalidação transacional. Opções de mutuários, consulta legada de ativos e lista de vencimentos do painel passam a incluir a data de hoje durante todo o dia.
- Testes de opções e empréstimo agora usam o último instante do dia, em vez de meia-noite ou validade futura. O caso de empréstimo verifica também a rejeição de edição à meia-noite do dia seguinte. Adicionado caso HTTP do painel para incluir validade de hoje e excluir a de ontem.
- `borrow_key.received` é uma marcação booleana antiga. A fonte atual das devoluções são `receiveds` e `key_received`. Sua remoção não foi aprovada nesta conversa; manter a coluna até confirmar os consumidores externos. Não houve limpeza de dados nem aplicação de restrições de unicidade nesta entrega.

Validação: `./vendor/bin/sail bin phpunit tests/Feature/BorrowAvailabilityTest.php tests/Feature/BorrowOptionsTest.php tests/Feature/HomeControllerTest.php` passou (exit code 0): 19 testes, 244 assertions, 2 min 14,927 s. Permanecem uma depreciação do runtime e uma do PHPUnit. Pint (`--dirty --format agent`) e `git diff --check` passaram. E2.5 e E2.7 concluídos; E6.3 segue pendente da limpeza e implementação dos índices. Não repetidos suíte completa, navegador ou validação em produção; sem migration ou alteração de dados existentes.

### Entrega 17 — Remoção do campo legado de devolução

- Autorizada a remoção após verificação de uso em 15/10/2026. Busca no repositório não encontrou consumidores do campo nem uso do model `BorrowKey`: apenas a declaração em `$fillable` e a migration histórica. A relação `Borrow::received` e as tabelas `receiveds`/`key_received` são o histórico atual e foram preservadas.
- Schema do MySQL local conferido via Boost: nenhuma trigger em `borrow_key`, nenhuma view ou rotina encontrada no ambiente inspecionado. Scripts e bancos externos fora deste ambiente não puderam ser verificados.
- Retirado `received` do `$fillable` de `BorrowKey`. Nova migration `2026_10_15_135738_drop_received_from_borrow_key_table.php` remove somente essa coluna; a migration de 2022 permanece intacta para manter o histórico de instalação.
- Teste da migration exercita remoção sobre vínculos existentes e um empréstimo parcialmente devolvido: mantém chaves vinculadas, recebimento e disponibilidade correta mesmo com a marcação antiga incoerente. O rollback recria a coluna com `false`, sem recuperar seus valores antigos; o histórico real de recebimentos continua preservado.
- Migration aplicada apenas no banco local, por `--path`, com sucesso (28,61 ms); schema conferido após aplicação, sem a coluna. A migration anterior de proteção do histórico, que já estava pendente nesse banco, não foi aplicada nesta operação. Nenhuma execução em produção.
- Validação: `./vendor/bin/sail bin phpunit tests/Feature/RemoveLegacyBorrowReceivedTest.php tests/Feature/BorrowTransactionTest.php` passou (exit code 0), com 18 testes, 154 assertions e duração de 3 min 7,973 s. Persistem uma depreciação do runtime e uma do PHPUnit. O teste de schema usa `DatabaseMigrations`, sem DDL dentro das transações do `RefreshDatabase`.
- Pint (`--dirty --format agent`) e `git diff --check` passaram. Não repetidos suíte completa, navegador, testes de concorrência ou build, pois não houve mudança de frontend. Alterações das entregas anteriores e planos de mutuários no workspace foram preservados.

### Entrega 18 — Reparo explícito de pivots duplicados

- Novo comando `borrows:remove-duplicate EMPRESTIMO_ID TABELA PIVOT_ID --user=ADMIN_ID --reason="Motivo conferido"`. Aceita somente `borrow_key` ou `key_received`. Sem `--apply`, apenas simula e apresenta JSON com o ID exato e os estados anterior/posterior do vínculo; não grava nem cria auditoria.
- A aplicação exige `--apply`, administrador responsável e motivo não vazio. Usa `BorrowTransaction`, com os mesmos bloqueios ordenados das ações de empréstimo, e revalida o vínculo e a redundância dentro da transação. Remove somente o ID escolhido quando outro vínculo do mesmo par permanece.
- Recusa vínculo de outro empréstimo, chave externa ao empréstimo e remoção do único vínculo do par. Recebimentos diferentes da mesma chave não são tratados como vínculos intercambiáveis: sua reconciliação exige decisão sobre o histórico real. Não escolhe automaticamente entre empréstimos concorrentes.
- Registra `borrow_duplicate_removed` no empréstimo, com administrador, motivo, tabela, ID do pivot e seu estado anterior/posterior. Auditoria e exclusão são atômicas; falha injetada na gravação da auditoria deve restaurar o vínculo. Reexecutar um ID já ausente é uma operação sem mudanças e sem novo evento de sucesso.
- `borrows:diagnose` agora também informa `duplicate_borrow_keys`, com os IDs de todos os vínculos repetidos do par empréstimo/chave. O relatório passa de seis para sete tipos de inconsistência; mantém JSON Lines e o resumo, sem escrita ou locks de escrita.
- A ajuda do comando foi conferida via Sail. Nenhum reparo foi aplicado fora do banco isolado de testes; não há migration ou mudança de dependências nesta entrega.
- E6.2 permanece em andamento: este comando cobre redundância estrutural de pivots. Não corrige automaticamente encerramentos, recebimentos vazios, chaves devolvidas sem vínculo ou conflitos de ocupação. E6.1 e E6.3 continuam pendentes da reconciliação/limpeza e das restrições de unicidade.

Validação realizada:

- Primeira execução: 11 testes, 123 assertions, 4 erros de fixture por referência a uma `ReceivedFactory` inexistente. Corrigido para `Received::create`, como nos testes vizinhos, sem acrescentar uma factory ou dependência.
- Execução final de `./vendor/bin/sail bin phpunit tests/Feature/RemoveDuplicateBorrowPivotTest.php tests/Feature/BorrowDiagnosisTest.php`: exit code 0, 12 testes, 180 assertions, duração de 1 min 34,158 s. Persistem uma depreciação do runtime e uma do PHPUnit.
- Cobertos os dois pivots em simulação/aplicação/reexecução, recusa do único vínculo, vínculo de outro empréstimo, chave externa, devoluções em recebimentos diferentes, entradas inválidas, administrador/motivo obrigatórios e rollback após falha na auditoria. Conferidas a preservação de disponibilidade/devoluções e ausência de escrita na simulação. Diagnóstico verifica os sete tipos, IDs e ausência de alterações.
- `./vendor/bin/sail bin pint --dirty --format agent`, ajuda do comando e `git diff --check` passaram. Não repetidos suíte completa, concorrência entre conexões ou navegador; não há frontend alterado neste incremento. Sem reparos ou migrations aplicados no banco local/produção. Alterações paralelas de mutuários no workspace foram preservadas.

### Entrega 19 — Autorização da exclusão de devoluções

- Corrigida divergência entre o controller (`borrows.received.delete`, inexistente no cadastro padrão) e a regra cadastrada/rota (`borrows.receive.destroy`). O bypass administrativo escondia a falha; agora um usuário não administrador com a regra correta consegue excluir uma devolução.
- A página recebe `can.deleteReceived`, independente de `can.receive` e de `can.delete`. Cabeçalho e controles de exclusão de recebimentos usam essa autorização, inclusive em empréstimos encerrados; a ação existente continua revalidando a reabertura e recusando conflitos de ocupação. Permissão de receber ou de apagar empréstimos não concede exclusão de devoluções.
- `BorrowAuthorizationTest` cobre a matriz das sete regras (cada regra concede somente sua habilidade), usuário sem regras e bypass de administrador. Nove rotas exigem autenticação/autorização, inclusive antes de validar corpos vazios. Snapshots de atributos, pivots, recebimentos e contagens verificam ausência de efeitos e de auditoria nas recusas.
- Casos específicos verificam exclusão parcial/final por não administrador, vínculo de recebimento de outro empréstimo recusado com 404, autoria da exclusão e os indicadores de permissão da página.
- Três testes frontend exercitam a árvore de componentes da página para conferir que cada controle usa sua própria permissão, sem depender de permissão de recebimento ou de empréstimo aberto para excluir uma devolução. Não são testes de interação com o modal no navegador.
- A reprodução inicial confirmou 403 com a regra correta. Também revelou a premissa incorreta de `user_id = 1` da factory genérica nas fixtures novas; o helper passou a criar o usuário explicitamente, sem alterar a factory do projeto.

Validação realizada:

- `./vendor/bin/sail bin phpunit tests/Feature/BorrowAuthorizationTest.php tests/Feature/BorrowTransactionTest.php`: exit code 0, 24 testes, 269 assertions, duração de 2 min 44,663 s. Persistem uma depreciação do runtime e uma do PHPUnit.
- `./vendor/bin/sail npm run test:frontend`: 17 testes aprovados, 0 falhas, 923,46 ms, incluindo os testes de telefone presentes no workspace. Houve avisos de porta WebSocket 24678 ocupada. `npm run build` via Sail passou; permanece o aviso existente do futuro carregador nativo da configuração Vite.
- Pint (`--dirty --format agent`) e `git diff --check` passaram. O Pint também ordenou imports no teste paralelo `EmployeeProfileTest.php`; não alterou sua lógica. As demais alterações paralelas de mutuários foram preservadas.
- Não repetidos navegador, suíte completa, concorrência entre conexões ou validação em produção. Sem migration, alteração de dependências ou reparo de dados nesta entrega. E4.10 permanece em andamento pelos cenários de edição concorrente.

### Entrega 20 — Edição concorrente em MySQL

- O worker de concorrência existente passou a executar `UpdateBorrow`. Mantidos os processos/conexões independentes, barreiras nos bloqueios das chaves, snapshot anterior do concorrente em `REPEATABLE READ` e guarda que exige exclusivamente o banco MySQL `testing`.
- Acrescentados quatro cenários: edição reserva uma chave antes de uma criação concorrente; devolução vence e impede edição antiga de remover a chave recebida; edição substitui a chave e impede devolução antiga de recebê-la; duas edições disputam o mesmo empréstimo e a antiga não pode remover uma chave recém-adicionada que não estava no conjunto inicial de bloqueios.
- Os casos verificam resultado de sucesso/conflito, campo de validação, vínculos finais, observação, recebimentos, encerramento e quantidade de eventos específicos de edição. O cenário de criação também verifica ausência de segundo empréstimo/ocupação da chave.
- Nenhuma mudança no código da aplicação, schema, permissões ou interface foi necessária neste incremento. A execução usa somente fixtures no banco isolado de testes; não há reparo de dados locais ou operacionais.

Validação realizada em 15/10/2026:

- `./vendor/bin/sail bin phpunit tests/Feature/BorrowConcurrencyTest.php --filter='test_edit_reserving|test_edit_after_a_concurrent|test_return_after_a_concurrent|test_stale_edit'`: exit code 0, quatro testes, 54 assertions, duração de 3 min 59,590 s. Persistem uma depreciação do runtime e uma do PHPUnit.
- Os quatro cenários passaram usando as ações reais, sem modificar a implementação para acomodar os testes. Confirmado isolamento `REPEATABLE-READ` no MySQL `testing`; fixtures com commits visíveis aos workers, sem `RefreshDatabase` envolvendo o cenário em transação invisível aos processos filhos.
- `./vendor/bin/sail bin pint --dirty --format agent` e `git diff --check` passaram.
- E4.10 concluído no escopo local do plano, somando os casos desta entrega às evidências anteriores de autorização, vínculo, reenvio e exclusões concorrentes. E4.8 continua dependendo da validação abrangente dos dados legados; não implica conclusão operacional da etapa 4.
- Não repetidos os demais casos antigos da classe de concorrência, suíte completa, navegador ou build; somente testes e worker foram alterados. Não houve implantação nem execução de reparos fora do banco de testes.

### Entrega 21 — Fechamento local da etapa 3

- `BorrowTransaction` documenta a ordem de bloqueio; edição de empréstimo agora trava o mutuário antes do estado de recebimentos/pivots. Cadastro e edição de autorizações de chaves travam as linhas de chave em ordem crescente antes do mutuário e conferem alterações concorrentes antes de sincronizar.
- Auditoria estática dos escritores do repositório: transições de empréstimo usam `BorrowTransaction`; `borrows:remove-duplicate` também usa esse protocolo; `borrows:diagnose` é somente leitura. Não foram encontrados scripts/imports que gravem diretamente nos pivots.
- `./vendor/bin/sail artisan migrate:status --no-interaction` confirmou a migration `2026_10_15_000000_protect_borrow_history_from_cascading_deletes` aplicada ao banco MySQL local (batch 3). Isso protege referências históricas contra exclusões por cascata neste schema.
- Testes afetados via Sail passaram: ordem SQL de bloqueio no endpoint de autorizações, sincronização existente, rollback de edição e edição concorrente obsoleta (4 testes, 39 assertions). Pint e `git diff --check` passaram.
- E3.3 e E3.6 concluídos no escopo do código e do banco local. Não foi possível inspecionar integrações externas nem confirmar schema ou escritores em produção; esses itens permanecem no acompanhamento operacional E6.8.

### Entrega 22 — Fechamento local da etapa 4

- E4.8 validado com comparação pelo conjunto de chaves pendentes. Os testes de diagnóstico cobrem empréstimo encerrado prematuramente e devolução completa sem encerramento, além de confirmar que o comando é somente leitura. O teste da edição impede manter aberto um empréstimo sem chaves pendentes.
- `borrows:diagnose` executado no banco local em 16/10/2026. Resumo: 14 `empty_borrows` e 4 `closure_mismatches` (empréstimos 6, 9, 12 e 14, todos abertos sem chaves pendentes); demais cinco categorias zeradas. Nenhum dado foi alterado.
- `php artisan test --compact tests/Feature/BorrowDiagnosisTest.php tests/Feature/BorrowTransactionTest.php`: exit code 0, 19 testes, 206 assertions. PHPUnit marcou os testes como deprecated e exibiu aviso sobre o schema XML.
- E4.1–E4.10 concluídos localmente. Os 14 empréstimos sem vínculos, incluindo os quatro com divergência de encerramento, precisam de reconciliação em E6.1 antes de qualquer reparo. Diagnóstico ou estado calculado não foi usado para escolher ou alterar registros. Validação de produção permanece operacional.

### Entrega 23 — Validação local da etapa 5

- `BorrowOptionsTest.php` e `BorrowScopeTest.php` passaram: 16 testes e 182 assertions. A suíte frontend passou: 20 testes, sem falhas. PHPUnit sinalizou 16 testes como deprecated e o runtime mostrou aviso de schema XML; Vite registrou a porta WebSocket 24678 ocupada.
- O banco local contém 15 empréstimos, 501 chaves e 1.001 mutuários. Esse volume não representa histórico operacional suficiente para comparar planos, p50/p95, quantidade de consultas e payload antes/depois.
- Credenciais E2E não estão configuradas no container; navegação real e seleção através das páginas não foram verificadas no navegador. Os testes HTTP e de componentes continuam cobrindo autorização, paginação e preservação da seleção.
- E5.3 e E5.7 permanecem em validação pela análise de consultas com volume representativo; E5.4 pela verificação no navegador; E5.8 ainda não executado. A etapa 5 não pode ser encerrada com a evidência local disponível. Sem mudanças de aplicação ou dados nesta entrega.

### Entrega 24 — Verificações locais da etapa 6

- `migrate:status` confirmou todas as 30 migrations aplicadas no MySQL local. Isso não confirma o schema de produção.
- `borrows:diagnose` repetido no banco local: zero duplicidades de pivots, zero devoluções duplicadas, zero recebimentos vazios, zero chaves devolvidas fora do empréstimo e zero ocupações duplas. Persistem 14 `empty_borrows` e quatro `closure_mismatches` (IDs 6, 9, 12 e 14). Nenhum reparo foi aplicado.
- `RemoveDuplicateBorrowPivotTest.php`, `RemoveLegacyBorrowReceivedTest.php`, `BorrowHistoryDeletionTest.php` e `BorrowDiagnosisTest.php` passaram: 18 testes, 366 assertions; depreciações PHPUnit/runtime permanecem.
- `npm run build` passou em 906 ms. Permanece o aviso existente sobre o carregador nativo da configuração Vite. A última suíte completa registrada teve 38 falhas fora dos testes de empréstimos e não está aprovada.
- E6.5 concluído no processo local. E6.1, E6.3, E6.7, E6.8 e E6.10 dependem de confirmação física ou acesso operacional; E6.9 ainda precisa de estratégia operacional de reversão. E6.2 permanece limitada ao reparo explícito de pivots duplicados; divergências de encerramento e empréstimos vazios não foram alterados. A etapa 6 não pode ser concluída com segurança até essas dependências serem resolvidas.

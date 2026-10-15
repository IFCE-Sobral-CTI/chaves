# Acompanhamento da correção de empréstimos e devoluções

Plano: [correcao-emprestimos-devolucoes](2026-10-14-correcao-emprestimos-devolucoes.md).  
Última atualização: 15/10/2026.

## Estados

- `[PENDENTE]`: tarefa ainda não iniciada.
- `[EM_ANDAMENTO]`: implementação ou investigação iniciada.
- `[EM_VALIDACAO]`: código pronto; faltam verificações para concluir.
- `[CONCLUIDO]`: entrega e critérios de aceite verificados, dentro do escopo indicado.
- `[BLOQUEADO]`: depende de acesso, informação ou condição externa; registrar a dependência.

As tarefas são independentes de implantação. Código validado localmente não significa publicado em produção. O fechamento de uma etapa exige todos os critérios do plano, inclusive os operacionais.

## Etapa 1 — Medição e diagnóstico dos dados

- [PENDENTE] E1.1 Instrumentar duração, consultas, payload, resultado e identificador de requisição sem dados pessoais.
- [PENDENTE] E1.2 Separar métricas do POST e do GET após redirecionamento.
- [PENDENTE] E1.3 Medir renderização e tempo até interação em equipamento representativo.
- [PENDENTE] E1.4 Confirmar migrations e índices reais do ambiente operacional.
- [CONCLUIDO] E1.5 Criar diagnóstico somente de leitura das inconsistências, com relatório por IDs. Comando `borrows:diagnose` validado em MySQL `testing`; execução operacional ainda pendente.
- [PENDENTE] E1.6 Examinar planos de execução e registrar baseline p50/p95, queries e payload.

## Etapa 2 — Disponibilidade e validação em lote

- [CONCLUIDO] E2.1 Correlacionar devoluções por empréstimo/chave usando subconsulta SQL.
- [CONCLUIDO] E2.2 Reutilizar a consulta em criação, edição e `Key::borrowed`, preservando o wrapper de IDs para os demais consumidores.
- [CONCLUIDO] E2.3 Validar existência e disponibilidade em lote e remover a checagem duplicada no controller.
- [CONCLUIDO] E2.4 Exigir lista não vazia de IDs inteiros distintos e usar apenas IDs validados para associações.
- [PENDENTE] E2.5 Definir limite operacional de itens sem restringir o atendimento indevidamente.
- [CONCLUIDO] E2.6 Exigir validade e permissão de cada chave para discentes e externos no backend.
- [PENDENTE] E2.7 Confirmar com o domínio se validade representa instante ou fim de dia. A entrega inicial preserva comparação por instante.
- [CONCLUIDO] E2.8 Testar regressão A/X/Y → devolução X → B/X, entradas inválidas, elegibilidade, edição e número constante de consultas.

## Etapa 3 — Atomicidade e concorrência

- [CONCLUIDO] E3.1 Extrair ações de aplicação para cada transição.
- [CONCLUIDO] E3.2 Transacionar gravações, associações, encerramento e auditoria.
- [EM_ANDAMENTO] E3.3 Documentar e aplicar ordem única de bloqueio das chaves e demais registros em todos os escritores. Aplicada às ações do fluxo; exclusões indiretas protegidas pelas FKs da entrega 5 após aplicação da migration. Confirmação operacional dos escritores segue em E3.6.
- [CONCLUIDO] E3.4 Revalidar disponibilidade e elegibilidade dentro da transação após os bloqueios.
- [CONCLUIDO] E3.5 Tratar conflitos e deadlocks com respostas previsíveis e tentativas limitadas.
- [EM_ANDAMENTO] E3.6 Auditar scripts/imports e avaliar necessidade de restrição estrutural de ocupação. Auditoria estática na entrega 4 e proteção das exclusões na entrega 5; implantação da migration e confirmação de escritores externos ainda pendentes. Exclusividade de ocupação segue dependente do protocolo das ações.
- [CONCLUIDO] E3.7 Testar disputa entre conexões MySQL independentes, devolução concorrente e rollback com falha injetada.

## Etapa 4 — Segurança e coerência

- [CONCLUIDO] E4.1 Restringir exclusão ao recebimento pertencente ao empréstimo autorizado.
- [CONCLUIDO] E4.2 Criar Form Request de devolução e migrar IDs para lista no corpo da requisição.
- [CONCLUIDO] E4.3 Rejeitar chaves já devolvidas e empréstimos encerrados; definir reenvio/idempotência. Reenvio rejeitado como conflito, sem novo recebimento.
- [CONCLUIDO] E4.4 Consultar IDs distintos recebidos e retornar array JSON com índices contíguos.
- [CONCLUIDO] E4.5 Definir e aplicar edição segura preservando o histórico.
- [CONCLUIDO] E4.6 Substituir encerramento manual comum por transição coerente e correção administrativa explícita, se necessária. Encerramento manual removido; não foi criada operação administrativa de correção.
- [CONCLUIDO] E4.7 Impedir reabertura por exclusão quando a chave já estiver emprestada em outro registro.
- [EM_ANDAMENTO] E4.8 Determinar encerramento pelo conjunto pendente, inclusive em cenários legados. Comparação por conjuntos implementada; diagnóstico e validação abrangente dos dados legados pendentes.
- [EM_ANDAMENTO] E4.9 Completar auditoria anterior/posterior com usuário e motivo de correção. Eventos transacionais com IDs e executor implementados; cobertura completa anterior/posterior e motivo de correção pendentes.
- [EM_ANDAMENTO] E4.10 Testar autorização, vínculo, reenvio, edição e exclusão concorrentes. Cobertos vínculo, reenvio, edição, autorização da devolução antes da validação e disputas de novo empréstimo com exclusão de recebimento ou de chave; matriz completa de autorização e disputas de edição pendente.

## Etapa 5 — Consultas e interface

- [CONCLUIDO] E5.1 Reutilizar model resolvido pela rota e evitar cálculo repetido de recebimentos.
- [CONCLUIDO] E5.2 Eliminar N+1 na agregação das chaves recebidas, com teste da quantidade de consultas.
- [PENDENTE] E5.3 Remover relações não usadas e revisar custo das consultas da auditoria.
- [PENDENTE] E5.4 Implementar seleção autorizada com campos mínimos, busca limitada e paginação.
- [CONCLUIDO] E5.5 Preservar seleção entre buscas e corrigir efeitos e IDs React no seletor local atual. Seleção controlada pelo formulário e linhas identificadas por `item.id`; adaptação à busca/paginação remota acompanha E5.4.
- [CONCLUIDO] E5.6 Evitar busca inicial redundante, reiniciar na página 1 e tratar respostas antigas. Validado localmente com temporizadores e visitas controlados; navegação real no navegador pendente.
- [PENDENTE] E5.7 Avaliar pesquisa no histórico e índices com planos de execução.
- [PENDENTE] E5.8 Comparar tempo, queries e payload antes/depois; disponibilidade segue revalidada no banco.

## Etapa 6 — Dados, schema e implantação

- [PENDENTE] E6.1 Reconciliar conflitos com confirmação física da recepção, sem escolha automática do registro correto.
- [PENDENTE] E6.2 Criar reparos explícitos com simulação, transação, auditoria e reexecução segura.
- [PENDENTE] E6.3 Avaliar unicidade dos pivots após limpeza e testar migrations com volume representativo.
- [EM_ANDAMENTO] E6.4 Auditar consumidores de `borrow_key.received` e planejar sua eventual remoção compatível. Encontrado apenas no model `BorrowKey` e na migration no repositório; confirmação de consumidores externos pendente, sem remoção de coluna.
- [EM_ANDAMENTO] E6.5 Entregar incrementos revisáveis e executar formatação e testes afetados em cada entrega.
- [EM_VALIDACAO] E6.6 Executar suíte completa e build quando necessário antes de publicar o conjunto. Build passou; suíte executada com 38 falhas fora dos testes de empréstimos, detalhadas na entrega 2.
- [PENDENTE] E6.7 Confirmar backup/restauração e estratégia para evitar escritores de versões incompatíveis.
- [PENDENTE] E6.8 Implantar em janela apropriada e observar erros, conflitos, deadlocks e latência.
- [EM_ANDAMENTO] E6.9 Preparar reversão segura do código e reconciliação específica dos reparos de dados. Para a entrega 5, preservar FKs restritivas ao reverter código; executar o `down` reabre o risco das cascatas e de perda do responsável final. Estratégia operacional completa ainda pendente.
- [PENDENTE] E6.10 Verificar metas de desempenho e observar período representativo de atendimento.

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

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
- [PENDENTE] E1.5 Criar diagnóstico somente de leitura das inconsistências, com relatório por IDs.
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
- [EM_ANDAMENTO] E3.3 Documentar e aplicar ordem única de bloqueio das chaves e demais registros em todos os escritores. Aplicada às ações do fluxo; auditoria dos demais escritores segue em E3.6.
- [CONCLUIDO] E3.4 Revalidar disponibilidade e elegibilidade dentro da transação após os bloqueios.
- [CONCLUIDO] E3.5 Tratar conflitos e deadlocks com respostas previsíveis e tentativas limitadas.
- [PENDENTE] E3.6 Auditar scripts/imports e avaliar necessidade de restrição estrutural de ocupação.
- [CONCLUIDO] E3.7 Testar disputa entre conexões MySQL independentes, devolução concorrente e rollback com falha injetada.

## Etapa 4 — Segurança e coerência

- [CONCLUIDO] E4.1 Restringir exclusão ao recebimento pertencente ao empréstimo autorizado.
- [PENDENTE] E4.2 Criar Form Request de devolução e migrar IDs para lista no corpo da requisição.
- [CONCLUIDO] E4.3 Rejeitar chaves já devolvidas e empréstimos encerrados; definir reenvio/idempotência. Reenvio rejeitado como conflito, sem novo recebimento.
- [CONCLUIDO] E4.4 Consultar IDs distintos recebidos e retornar array JSON com índices contíguos.
- [CONCLUIDO] E4.5 Definir e aplicar edição segura preservando o histórico.
- [CONCLUIDO] E4.6 Substituir encerramento manual comum por transição coerente e correção administrativa explícita, se necessária. Encerramento manual removido; não foi criada operação administrativa de correção.
- [CONCLUIDO] E4.7 Impedir reabertura por exclusão quando a chave já estiver emprestada em outro registro.
- [EM_ANDAMENTO] E4.8 Determinar encerramento pelo conjunto pendente, inclusive em cenários legados. Comparação por conjuntos implementada; diagnóstico e validação abrangente dos dados legados pendentes.
- [EM_ANDAMENTO] E4.9 Completar auditoria anterior/posterior com usuário e motivo de correção. Eventos transacionais com IDs e executor implementados; cobertura completa anterior/posterior e motivo de correção pendentes.
- [EM_ANDAMENTO] E4.10 Testar autorização, vínculo, reenvio, edição e exclusão concorrentes. Cobertos vínculo, reenvio, edição e disputa entre exclusão de recebimento e novo empréstimo; matriz completa de autorização e disputas de edição pendente.

## Etapa 5 — Consultas e interface

- [CONCLUIDO] E5.1 Reutilizar model resolvido pela rota e evitar cálculo repetido de recebimentos.
- [CONCLUIDO] E5.2 Eliminar N+1 na agregação das chaves recebidas, com teste da quantidade de consultas.
- [PENDENTE] E5.3 Remover relações não usadas e revisar custo das consultas da auditoria.
- [PENDENTE] E5.4 Implementar seleção autorizada com campos mínimos, busca limitada e paginação.
- [PENDENTE] E5.5 Preservar seleção entre buscas e corrigir efeitos e IDs React.
- [PENDENTE] E5.6 Evitar busca inicial redundante, reiniciar na página 1 e tratar respostas antigas.
- [PENDENTE] E5.7 Avaliar pesquisa no histórico e índices com planos de execução.
- [PENDENTE] E5.8 Comparar tempo, queries e payload antes/depois; disponibilidade segue revalidada no banco.

## Etapa 6 — Dados, schema e implantação

- [PENDENTE] E6.1 Reconciliar conflitos com confirmação física da recepção, sem escolha automática do registro correto.
- [PENDENTE] E6.2 Criar reparos explícitos com simulação, transação, auditoria e reexecução segura.
- [PENDENTE] E6.3 Avaliar unicidade dos pivots após limpeza e testar migrations com volume representativo.
- [PENDENTE] E6.4 Auditar consumidores de `borrow_key.received` e planejar sua eventual remoção compatível.
- [EM_ANDAMENTO] E6.5 Entregar incrementos revisáveis e executar formatação e testes afetados em cada entrega.
- [EM_VALIDACAO] E6.6 Executar suíte completa e build quando necessário antes de publicar o conjunto. Build passou; suíte executada com 38 falhas fora dos testes de empréstimos, detalhadas na entrega 2.
- [PENDENTE] E6.7 Confirmar backup/restauração e estratégia para evitar escritores de versões incompatíveis.
- [PENDENTE] E6.8 Implantar em janela apropriada e observar erros, conflitos, deadlocks e latência.
- [PENDENTE] E6.9 Preparar reversão segura do código e reconciliação específica dos reparos de dados.
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

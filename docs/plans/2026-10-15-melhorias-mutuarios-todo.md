# Acompanhamento das melhorias de mutuários

Plano: [melhorias de mutuários](2026-10-15-melhorias-mutuarios.md).
Worktree: `/tmp/chaves-mutuarios`.
Branch: `feature/mutuarios`.
Última atualização: 19/10/2026.
Status geral: etapas 1 a 6 implementadas e validadas localmente; implantação não realizada.

## Estados

- `[PENDENTE]`: tarefa ainda não iniciada.
- `[EM_ANDAMENTO]`: implementação ou investigação iniciada.
- `[EM_VALIDACAO]`: implementação pronta; faltam verificações.
- `[CONCLUIDO]`: tarefa e critérios aplicáveis verificados.
- `[BLOQUEADO]`: depende de informação, acesso ou condição externa; registrar o motivo na tarefa.

Atualizar os estados conforme o trabalho avançar. Registrar evidências de validação ao concluir uma etapa. Conclusão local não significa implantação em produção.

## Preparação

- [CONCLUIDO] P.1 Registrar o plano funcional e técnico.
- [CONCLUIDO] P.2 Remover a branch inicial e criar worktree isolado em `/tmp/chaves-mutuarios`, na branch `feature/mutuarios`, baseado no `main` atual.
- [CONCLUIDO] P.3 Criar este acompanhamento e vinculá-lo ao plano no worktree.
- [CONCLUIDO] P.4 Conferir regras locais, versões instaladas, schema, consumidores e padrões existentes para os campos, pesquisa e formulários alterados.
- [CONCLUIDO] P.5 Conferir a semântica atual da validade e os formatos de telefone legados para preservar o comportamento e os dados existentes; validade é comparada por dia e testes cobrem números legados longos.

## Etapa 1 — Dados, validação e persistência

- [CONCLUIDO] E1.1 Criar migração para `usual_name` e `occupation`, opcionais, sem modificar cadastros existentes. Aplicada com sucesso em MySQL 8.0 recém-criado no worktree; migration terminou com status `DONE`.
- [CONCLUIDO] E1.2 Incluir os novos campos na persistência, validação e auditoria de `Employee`; teste de persistência passou.
- [CONCLUIDO] E1.3 Validar IDs existentes e distintos; a lista vazia é permitida para discentes e externos. Testes de validação passaram.
- [CONCLUIDO] E1.4 Cadastro/edição e sincronização de `borrowable_keys` estão na mesma transação; testes de persistência passaram.
- [CONCLUIDO] E1.5 Servidores e colaboradores salvam sem autorizações e limpam vínculos anteriores; teste passou.
- [CONCLUIDO] E1.6 A edição bloqueia as chaves em ordem crescente e depois a linha do mutuário durante update/sync, na mesma ordem das ações de empréstimo; teste de ordem dos bloqueios passou.
- [CONCLUIDO] E1.7 Normalizar telefones formatados para dígitos e rejeitar entrada sem dígitos; preservar valores legados acima de 11 dígitos na interface. Testes da normalização passaram; no navegador, celular formatado foi salvo no banco apenas com dígitos.

## Etapa 2 — Cadastro, edição e detalhes

- [CONCLUIDO] E2.1 Adicionar Nome usual e Função/ocupação aos formulários de criação e edição como campos opcionais. Campos preenchidos e persistidos no fluxo do navegador.
- [CONCLUIDO] E2.2 Seleção múltipla exibida apenas para discentes/externos; opções identificam número e sala e incluem chaves emprestadas.
- [CONCLUIDO] E2.3 Edição carrega autorizações atuais e permite adicionar/remover chaves; persistência coberta pelo teste de perfil do mutuário.
- [CONCLUIDO] E2.4 Exibir orientação sobre validade vigente e ausência de chaves autorizadas para perfis restritos.
- [CONCLUIDO] E2.5 Exibir os novos campos nos detalhes do mutuário e preservar a apresentação das autorizações. Conferido no navegador.
- [CONCLUIDO] E2.6 Aplicar máscara brasileira de telefone fixo e celular, aceitando colagem com ou sem pontuação e preservando formatos legados. Testes frontend cobrem fixo, celular, colagem e números legados longos.
- [CONCLUIDO] E2.7 Reutilizar componentes existentes e manter acessibilidade, mensagens de validação e comportamento dos formulários. Seletor operável por teclado, com rótulos acessíveis nos controles de pesquisa e remoção.

## Etapa 3 — Opções de chaves no empréstimo

- [CONCLUIDO] E3.1 Disponibilizar novas chaves somente após selecionar o mutuário. A tela inicia sem opções e consulta após a seleção; build e teste frontend passaram.
- [CONCLUIDO] E3.2 Enviar e validar o mutuário nas consultas de opções de chaves, preservando as permissões das rotas de criação e edição. Testes de autorização, validação e filtros passaram.
- [CONCLUIDO] E3.3 Combinar autorização e disponibilidade antes de paginar, incluindo opções iniciais e pesquisas. Testes de autorização, validade, disponibilidade e paginação passaram.
- [CONCLUIDO] E3.4 Retornar lista vazia e mostrar mensagem para discentes e externos sem validade vigente ou sem chaves autorizadas. Testes de endpoint e frontend passaram.
- [CONCLUIDO] E3.5 Manter todas as chaves disponíveis para servidores e colaboradores. Teste para ambos os perfis passou.
- [CONCLUIDO] E3.6 Limpar chaves selecionadas, busca e paginação ao trocar o mutuário na criação. Teste frontend confirmou a limpeza e remontagem do seletor.
- [CONCLUIDO] E3.7 Descartar respostas antigas após troca de mutuário para impedir apresentação de opções anteriores. O seletor aborta as consultas no desmontar e ignora respostas abortadas; teste frontend passou.
- [CONCLUIDO] E3.8 Revalidar todas as chaves ao criar o empréstimo, inclusive em requisições manipuladas ou após mudança de permissões. Teste confirmou rejeição após remoção da autorização.
- [CONCLUIDO] E3.9 Preservar projeções mínimas nos endpoints de opções, sem expor observações do mutuário. Testes confirmaram os campos retornados.

## Etapa 4 — Edição do empréstimo e histórico

- [CONCLUIDO] E4.1 Manter visíveis as chaves já vinculadas, inclusive após retirada de autorização; opções de edição sempre incluem as chaves do empréstimo.
- [CONCLUIDO] E4.2 Preservar chaves com devolução registrada e as demais restrições existentes de edição e encerramento; teste cobre vínculo e histórico da devolução e rejeição da remoção.
- [CONCLUIDO] E4.3 Validar novas inclusões pelas permissões e validade atuais quando o mutuário permanecer o mesmo; teste cobre autorização ausente, validade expirada e inclusão autorizada.
- [CONCLUIDO] E4.4 Permitir editar observações sem conceder novas chaves quando a validade ou autorização tiver sido retirada; teste cobre ambos os casos.
- [CONCLUIDO] E4.5 Validar todas as chaves para o novo mutuário quando houver troca na edição, preservando restrições relativas às devoluções; teste cobre chave não autorizada, histórico e remoção bloqueada.
- [CONCLUIDO] E4.6 Garantir que mudanças de validade ou autorização não impeçam devoluções nem modifiquem o histórico; teste registra devolução normalmente após retirada das permissões.

## Etapa 5 — Pesquisa e identificação

- [CONCLUIDO] E5.1 Incluir observações nos resultados da listagem com quebra de texto e confirmar exibição na pesquisa no navegador.
- [CONCLUIDO] E5.2 Ampliar a busca na gestão de mutuários para nome usual, função/ocupação e observações, preservando nome e matrícula. Testes feature cobrem os cinco campos.
- [CONCLUIDO] E5.3 Manter paginação e controle de acesso da gestão de mutuários; teste confirma limite configurado e bloqueio sem `employees.viewAny`.
- [CONCLUIDO] E5.4 Permitir busca por nome usual e exibi-lo junto ao nome completo na seleção de mutuário do empréstimo; opções de criação/edição e teste frontend cobrem a seleção.
- [CONCLUIDO] E5.5 Manter observações fora dos seletores de empréstimo e preservar os consumidores do nome completo, incluindo relatórios e exportações; projeção inclui somente nome usual adicional e teste verifica ausência de observações.

## Etapa 6 — Cobertura e validação

- [CONCLUIDO] E6.1 Cobrir criação e edição dos novos campos, incluindo valores vazios; cadastro, edição e seleção de múltiplas chaves cobertos por testes feature.
- [CONCLUIDO] E6.2 Cobrir sincronização de autorizações, IDs inexistentes ou repetidos, lista vazia, mudança de perfil e rollback após falha na sincronização.
- [CONCLUIDO] E6.3 Cobrir opções de chaves por perfil, validade, autorização, disponibilidade, busca, paginação e controle de acesso, incluindo paginação da edição com perfil restrito.
- [CONCLUIDO] E6.4 Cobrir envio manual de chave não autorizada e revogação após carregar o formulário; teste simula revogação após o preflight e confirma rejeição na transação.
- [CONCLUIDO] E6.5 Cobrir troca de mutuário, limpeza da seleção e reinício da paginação pelo remount, além de descartar resposta antiga; teste frontend executa efeitos de busca com respostas fora de ordem.
- [CONCLUIDO] E6.6 Cobrir edição de observações, inclusão de chaves e troca de mutuário após mudanças de validade ou autorização, preservando histórico e devoluções; testes focados da etapa 4 passaram.
- [CONCLUIDO] E6.7 Cobrir máscara, colagem, normalização e preservação de telefones legados. Testes frontend passaram para telefone fixo, celular, colagem e preservação de número legado; normalização também foi coberta pelo teste de perfil.
- [CONCLUIDO] E6.8 Cobrir busca pelos campos previstos e ausência de observações nos endpoints de opções de empréstimo; testes feature passaram.
- [CONCLUIDO] E6.9 Executar os testes afetados via Sail e registrar comandos, resultados e limitações. `./vendor/bin/sail artisan test --compact tests/Feature/EmployeeProfileTest.php tests/Feature/BorrowAvailabilityTest.php tests/Feature/BorrowOptionsTest.php tests/Feature/BorrowTransactionTest.php --filter='test_authorized_keys_are_validated_and_synchronized_for_restricted_profiles|test_employee_creation_rolls_back_when_authorization_sync_fails|test_valid_restricted_borrower_without_authorized_keys_cannot_borrow|test_valid_student_can_borrow_and_describe_existing_loan_after_expiry|test_authorization_is_rechecked_if_revoked_after_preflight|test_edit_key_options_keep_existing_keys_and_filter_new_keys_for_selected_borrower'` passou (6 testes, 53 assertions); `./vendor/bin/sail npm run test:frontend -- --run` passou (22 testes); Pint passou. PHPUnit exibiu avisos de depreciação.
- [CONCLUIDO] E6.10 Build do frontend executado via Sail em 19/10/2026; sem erros. Mantido o aviso existente do Vite sobre `configLoader: 'native'`.
- [CONCLUIDO] E6.11 Validar no Chromium do container Sail a criação de mutuário, os campos Nome usual e Função/ocupação, máscara de telefone, persistência, pré-preenchimento da máscara na edição e busca por observação. Console sem erros e sem respostas HTTP 5xx; teclado e rótulos dos controles foram verificados e formatos legados são cobertos pelos testes frontend.
- [CONCLUIDO] E6.12 Conferir todos os critérios de aceite do plano; todos foram satisfeitos e marcados no plano. Execução local validada; implantação permanece fora do escopo.

## Evidências e pendências

- Etapas 1 a 6 implementadas e validadas localmente; migração, perfil, telefone, pesquisa, opções por elegibilidade, preservação de histórico e cobertura estão concluídos.
- A implantação em produção não foi realizada.
- Migration aplicada em banco MySQL 8.0 isolado; schema foi exercitado pelo cadastro e edição bem-sucedidos.
- Build do frontend passou no Sail; permaneceu o aviso existente do Vite sobre `configLoader: 'native'`.
- Playwright no container Sail confirmou criação, campos visíveis, máscara `(88) 99999-8888`, persistência como `88999998888`, pré-preenchimento no formulário de edição e busca pela observação. Nenhum erro JS ou HTTP 5xx nos fluxos testados.
- Testes de cadastro, sincronização, validação de IDs e telefone: `./vendor/bin/sail artisan test --compact tests/Feature/EmployeeProfileTest.php` passou em 16/10/2026 (6 testes, 85 assertions).
- Nenhum commit ou push realizado nesta preparação.
- Etapa 1 conferida no código: migration com colunas opcionais; campos incluídos em `$fillable` e auditoria; Form Requests validam IDs existentes e distintos e normalizam telefone; controller cria/atualiza mutuário e sincroniza autorizações na mesma transação, limpando vínculos para servidor/colaborador. Atualizações bloqueiam chaves em ordem crescente e depois o mutuário, na mesma ordem usada pelo fluxo de empréstimo.
- A primeira tentativa de `EmployeeProfileTest` foi interrompida com o Sail parado e o banco de testes inconsistente. Após iniciar os serviços, o teste passou; não foi necessário reset destrutivo nem criar factories/seeders adicionais.
- Etapa 3 concluída: `./vendor/bin/sail artisan test --compact tests/Feature/BorrowOptionsTest.php tests/Feature/BorrowAvailabilityTest.php` passou (19 testes, 207 assertions); `./vendor/bin/sail npm run test:frontend` passou (20 testes); Pint e `./vendor/bin/sail npm run build` passaram. O build manteve o aviso existente do Vite sobre `configLoader: 'native'`.
- Etapa 2 concluída em 19/10/2026: formulários de criação/edição orientam sobre validade e autorizações; seletor acessível identifica remoção e pesquisa e os detalhes mostram número e sala. `./vendor/bin/sail npm run test:frontend` passou (20 testes) e `./vendor/bin/sail npm run build` passou. Para executar, foi necessário corrigir a propriedade de pastas geradas `node_modules/.cache/frontend-tests` e `public/build`, que estavam como `root` e impediam limpeza pelo processo do frontend.
- Etapa 4 concluída em 19/10/2026: edição mantém visíveis as chaves vinculadas mesmo após revogação/expiração; novas inclusões exigem elegibilidade atual; troca de mutuário valida todas as chaves; devoluções e histórico seguem preservados. `./vendor/bin/sail artisan test --compact tests/Feature/BorrowAvailabilityTest.php tests/Feature/BorrowOptionsTest.php --filter='test_edit_keeps_existing_keys_and_allows_observation_updates_after_eligibility_is_removed|test_same_borrower_must_be_currently_eligible_for_new_keys|test_changing_borrower_validates_all_keys_and_preserves_return_history|test_return_remains_available_after_borrower_eligibility_is_removed|test_edit_key_options_keep_existing_keys_and_filter_new_keys_for_selected_borrower'` passou (5 testes, 39 assertions; PHPUnit exibiu avisos de depreciação). `./vendor/bin/sail npm run test:frontend` passou (5 testes); `./vendor/bin/sail npm run build` e Pint passaram. `git diff --check` passou. Build manteve o aviso existente do Vite sobre `configLoader: 'native'`.
- Etapa 5 concluída em 19/10/2026: gestão pesquisa por nome, matrícula, nome usual, ocupação e observações; lista mantém paginação e autorização e mostra observações com quebra de texto. Seletores de empréstimo pesquisam e apresentam nome usual junto ao nome completo sem receber observações. `./vendor/bin/sail artisan test --compact tests/Feature/EmployeeProfileTest.php tests/Feature/BorrowOptionsTest.php` passou (21 testes, 315 assertions; avisos de depreciação do PHPUnit); `npm run test:frontend` passou (5 testes); Pint e `./vendor/bin/sail npm run build` passaram. O build manteve o aviso existente do Vite sobre `configLoader: 'native'`.
- Etapa 6 concluída em 19/10/2026: ampliados testes para rollback de cadastro, múltiplas autorizações, perfil restrito sem chaves, filtros paginados de edição, revogação de autorização entre preflight e transação e resposta frontend obsoleta após trocar o mutuário. O conjunto focado via Sail passou (6 testes, 53 assertions); testes frontend passaram (22 testes); Pint passou. O build da etapa 5 continua válido; PHPUnit mostrou avisos de depreciação. Uma execução ampla encontrou uma expectativa antiga que contradizia a etapa 4 (permitir editar observação após expiração); a expectativa foi atualizada e o caso passou na execução focada.
- O workspace principal em `main` manteve as alterações locais que já existiam antes da criação do worktree.
- Premissas funcionais e cuidados com histórico e telefones legados permanecem descritos no plano.

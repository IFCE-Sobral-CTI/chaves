# Acompanhamento das melhorias de mutuários

Plano: [melhorias de mutuários](2026-10-15-melhorias-mutuarios.md).
Worktree: `/tmp/chaves-mutuarios`.
Branch: `feature/mutuarios`.
Última atualização: 15/10/2026.
Status geral: implementação parcial; validação local pendente porque o Docker/Sail está parado.

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
- [PENDENTE] P.5 Conferir a semântica atual da validade e os formatos de telefone legados para preservar o comportamento e os dados existentes.

## Etapa 1 — Dados, validação e persistência

- [CONCLUIDO] E1.1 Criar migração para `usual_name` e `occupation`, opcionais, sem modificar cadastros existentes. Aplicada com sucesso em MySQL 8.0 recém-criado no worktree; migration terminou com status `DONE`.
- [EM_VALIDACAO] E1.2 Incluir os novos campos na persistência, validação e auditoria de `Employee`; testes de persistência e pesquisa escritos, ainda sem execução.
- [EM_VALIDACAO] E1.3 Validar IDs existentes e distintos; a lista vazia é permitida para discentes e externos. Testes escritos, ainda sem execução.
- [EM_VALIDACAO] E1.4 Cadastro/edição e sincronização de `borrowable_keys` estão na mesma transação; testes de persistência escritos, ainda sem execução.
- [EM_VALIDACAO] E1.5 Servidores e colaboradores salvam sem autorizações e limpam vínculos anteriores; teste escrito, ainda sem execução.
- [EM_VALIDACAO] E1.6 A edição bloqueia a linha do mutuário durante update/sync, compartilhando o bloqueio das ações de empréstimo; concorrência ainda sem teste específico.
- [EM_VALIDACAO] E1.7 Normalizar telefones formatados para dígitos e rejeitar entrada sem dígitos; preservar valores legados acima de 11 dígitos na interface. No navegador, celular formatado foi salvo no banco apenas com dígitos.

## Etapa 2 — Cadastro, edição e detalhes

- [CONCLUIDO] E2.1 Adicionar Nome usual e Função/ocupação aos formulários de criação e edição como campos opcionais. Campos preenchidos e persistidos no fluxo do navegador.
- [EM_VALIDACAO] E2.2 Seleção múltipla exibida apenas para discentes/externos; opções identificam número e sala e incluem chaves emprestadas.
- [EM_VALIDACAO] E2.3 Edição carrega autorizações atuais e permite adicionar/remover chaves; teste de persistência escrito, ainda sem execução.
- [PENDENTE] E2.4 Exibir orientação sobre validade vigente e ausência de chaves autorizadas para perfis restritos.
- [CONCLUIDO] E2.5 Exibir os novos campos nos detalhes do mutuário e preservar a apresentação das autorizações. Conferido no navegador.
- [EM_VALIDACAO] E2.6 Aplicar máscara brasileira de telefone fixo e celular, aceitando colagem com ou sem pontuação e preservando formatos legados. Celular mascarado na criação e edição conferidos no navegador; teste de formatos legados ainda não executado.
- [PENDENTE] E2.7 Reutilizar componentes existentes e manter acessibilidade, mensagens de validação e comportamento dos formulários.

## Etapa 3 — Opções de chaves no empréstimo

- [EM_VALIDACAO] E3.1 Disponibilizar novas chaves somente após selecionar o mutuário. A tela inicia sem opções e consulta após a seleção; build pendente.
- [EM_VALIDACAO] E3.2 Enviar e validar o mutuário nas consultas de opções de chaves, preservando as permissões das rotas de criação e edição. Autorização permanece na rota e há validação de existência; teste via Sail pendente.
- [EM_VALIDACAO] E3.3 Combinar autorização e disponibilidade antes de paginar, incluindo opções iniciais e pesquisas. Filtragem implementada no endpoint; testes de autorização/validade/paginação escritos, aguardando execução via Sail.
- [PENDENTE] E3.4 Retornar lista vazia e mostrar mensagem para discentes e externos sem validade vigente ou sem chaves autorizadas.
- [PENDENTE] E3.5 Manter todas as chaves disponíveis para servidores e colaboradores.
- [EM_VALIDACAO] E3.6 Limpar chaves selecionadas, busca e paginação ao trocar o mutuário na criação. A seleção é limpa e o componente de busca remonta por mutuário; validação automatizada aguarda execução via Sail.
- [PENDENTE] E3.7 Descartar respostas antigas após troca de mutuário para impedir apresentação de opções anteriores.
- [PENDENTE] E3.8 Revalidar todas as chaves ao criar o empréstimo, inclusive em requisições manipuladas ou após mudança de permissões.
- [PENDENTE] E3.9 Preservar projeções mínimas nos endpoints de opções, sem expor observações do mutuário.

## Etapa 4 — Edição do empréstimo e histórico

- [PENDENTE] E4.1 Manter visíveis as chaves já vinculadas, inclusive após retirada de autorização.
- [PENDENTE] E4.2 Preservar chaves com devolução registrada e as demais restrições existentes de edição e encerramento.
- [PENDENTE] E4.3 Validar novas inclusões pelas permissões e validade atuais quando o mutuário permanecer o mesmo.
- [PENDENTE] E4.4 Permitir editar observações sem conceder novas chaves quando a validade ou autorização tiver sido retirada.
- [PENDENTE] E4.5 Validar todas as chaves para o novo mutuário quando houver troca na edição, preservando restrições relativas às devoluções.
- [PENDENTE] E4.6 Garantir que mudanças de validade ou autorização não impeçam devoluções nem modifiquem o histórico.

## Etapa 5 — Pesquisa e identificação

- [CONCLUIDO] E5.1 Incluir observações nos resultados da listagem com quebra de texto e confirmar exibição na pesquisa no navegador.
- [EM_VALIDACAO] E5.2 Ampliar a busca na gestão de mutuários para nome usual, função/ocupação e observações, preservando nome e matrícula. Testes de busca escritos, ainda sem execução.
- [PENDENTE] E5.3 Manter paginação e controle de acesso da gestão de mutuários.
- [PENDENTE] E5.4 Permitir busca por nome usual e exibi-lo junto ao nome completo na seleção de mutuário do empréstimo.
- [PENDENTE] E5.5 Manter observações fora dos seletores de empréstimo e preservar os consumidores do nome completo, incluindo relatórios e exportações.

## Etapa 6 — Cobertura e validação

- [EM_VALIDACAO] E6.1 Cobrir criação e edição dos novos campos, incluindo valores vazios. Cadastro e edição com campos preenchidos validados no navegador; teste de feature escrito, ainda sem execução.
- [PENDENTE] E6.2 Cobrir sincronização de autorizações, IDs inexistentes ou repetidos, lista vazia, mudança de perfil e rollback de gravações parciais.
- [PENDENTE] E6.3 Cobrir opções de chaves por perfil, validade, autorização, disponibilidade, busca, paginação e controle de acesso.
- [PENDENTE] E6.4 Cobrir envio manual de chave não autorizada e mudança de permissão após carregar o formulário, incluindo concorrência com atualização de autorizações.
- [PENDENTE] E6.5 Cobrir troca de mutuário, limpeza da seleção e paginação e descarte de respostas antigas no frontend.
- [PENDENTE] E6.6 Cobrir edição de observações, inclusão de chaves e troca de mutuário após mudanças de validade ou autorização, preservando histórico e devoluções.
- [EM_VALIDACAO] E6.7 Cobrir máscara, colagem, normalização e preservação de telefones legados. Mascaramento e normalização validados no navegador; testes frontend e preservação de número legado ainda sem execução.
- [EM_VALIDACAO] E6.8 Cobrir busca pelos campos previstos e ausência de observações nos endpoints de opções de empréstimo. Pesquisa por observação validada no navegador; teste de busca escrito, ainda sem execução. Nenhum endpoint de empréstimo foi alterado nesta etapa.
- [PENDENTE] E6.9 Executar os testes afetados via Sail e registrar comandos, resultados e limitações.
- [PENDENTE] E6.10 Executar a formatação exigida para PHP e o build do frontend quando aplicáveis; registrar resultados.
- [CONCLUIDO] E6.11 Validar no Chromium do container Sail a criação de mutuário, os campos Nome usual e Função/ocupação, máscara de telefone, persistência, pré-preenchimento da máscara na edição e busca por observação. Console sem erros e sem respostas HTTP 5xx nos fluxos percorridos; acessibilidade e formatos legados permanecem para validação completa.
- [PENDENTE] E6.12 Conferir todos os critérios de aceite do plano e registrar pendências antes de concluir a implementação.

## Evidências e pendências

- Implementação parcial: migration de campos, perfil, telefone, busca e seleção de chaves autorizadas para discentes/externos, com validação, sincronização transacional e limpeza para servidores/colaboradores.
- Filtragem de chaves e revalidação no fluxo de empréstimos, além do nome usual na seleção de mutuário do empréstimo, seguem pendentes.
- Migration aplicada em banco MySQL 8.0 isolado; schema foi exercitado pelo cadastro e edição bem-sucedidos.
- Build do frontend passou no Sail; permaneceu o aviso existente do Vite sobre `configLoader: 'native'`.
- Playwright no container Sail confirmou criação, campos visíveis, máscara `(88) 99999-8888`, persistência como `88999998888`, pré-preenchimento no formulário de edição e busca pela observação. Nenhum erro JS ou HTTP 5xx nos fluxos testados.
- Testes de cadastro, sincronização, validação de IDs e telefone foram escritos, mas não executados: `./vendor/bin/sail artisan test --compact tests/Feature/EmployeeProfileTest.php` não iniciou porque Docker/Podman está parado. `git diff --check` passou.
- Nenhum commit ou push realizado nesta preparação.
- E3.3/E3.6: `git diff --check` passou. Pint e `BorrowOptionsTest` não puderam ser executados porque Docker/Podman está parado; a verificação via Sail permanece pendente.
- O workspace principal em `main` manteve as alterações locais que já existiam antes da criação do worktree.
- Premissas funcionais e cuidados com histórico e telefones legados permanecem descritos no plano.

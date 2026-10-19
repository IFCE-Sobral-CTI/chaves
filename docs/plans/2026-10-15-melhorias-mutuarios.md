# Plano de melhorias no cadastro e empréstimos de mutuários

Data: 15/10/2026
Status: etapas 1–6 implementadas e validadas localmente; implantação não realizada.

Acompanhamento: [tarefas e status](2026-10-15-melhorias-mutuarios-todo.md).
Implementação isolada: worktree `/tmp/chaves-mutuarios`, branch `feature/mutuarios`.

## Objetivo

Permitir configurar as chaves autorizadas de discentes e externos no cadastro, apresentar somente opções elegíveis no empréstimo e melhorar a identificação e a pesquisa de mutuários.

## Situação atual

- A relação `Employee::borrowable_keys()` e a tabela `borrowable_keys` já existem.
- `EnsureBorrowerEligible` já verifica validade e autorização para todas as chaves ao criar e editar empréstimos.
- Cadastro e edição de mutuários ainda não permitem salvar essas autorizações.
- As opções de chaves do empréstimo ainda não são filtradas pelo mutuário selecionado.
- O cadastro possui telefone e observações, mas não nome usual ou função/ocupação.
- A pesquisa de mutuários considera nome e matrícula.

## Regras e premissas

1. Nome usual e função/ocupação são campos opcionais para todos os perfis. Nome usual complementa o nome completo.
2. Discentes e externos precisam de validade vigente e autorização explícita para cada chave emprestada.
3. É permitido salvar um cadastro sem chaves autorizadas; nesses perfis, isso impede novos empréstimos.
4. Servidores e colaboradores podem selecionar qualquer chave disponível.
5. A autorização pode ser concedida mesmo quando a chave está emprestada; disponibilidade é verificada no empréstimo.
6. Alterar autorizações não modifica empréstimos existentes nem impede devoluções.
7. Observações na pesquisa significa exibir a informação nos resultados e permitir buscar pelo conteúdo.
8. A máscara de telefone atende números brasileiros com DDD, fixos e celulares. Números legados fora desse formato exigem tratamento que preserve os dados, sem truncamento silencioso.

## Etapas de implementação

### 1. Dados e validação do mutuário

- Adicionar colunas opcionais `usual_name` e `occupation` em `employees`, sem preencher ou substituir dados existentes.
- Incluir os campos na persistência, validação e auditoria do model.
- Validar a lista de chaves como IDs existentes, sem duplicatas.
- Reutilizar `borrowable_keys`, sem criar uma nova estrutura de permissões.
- Salvar cadastro e sincronização das chaves na mesma transação.
- Para servidores e colaboradores, desconsiderar autorizações específicas e limpar vínculos anteriores ao salvar a mudança de perfil, evitando autorizações antigas em uma futura volta ao perfil restrito.
- Preservar o protocolo de bloqueios usado pelo empréstimo para que alterações de permissões não concorram com a concessão de novas chaves sem validação consistente.

### 2. Cadastro, edição e detalhes

- Adicionar Nome usual e Função/ocupação aos formulários de criação e edição.
- Mostrar seletor de múltiplas chaves somente para discentes e externos, identificando número e sala.
- Carregar as autorizações existentes na edição e permitir inclusão e remoção.
- Orientar que os perfis restritos precisam de validade vigente para emprestar.
- Exibir os novos campos nos detalhes e manter a exibição das chaves autorizadas.
- Aplicar máscara `(88) 3333-4444` ou `(88) 99999-8888` no telefone, permitindo colagem com ou sem pontuação.
- Persistir somente dígitos, preservando o valor de números legados que não se encaixem na máscara.
- Reutilizar componentes existentes e não adicionar dependências para a máscara.

### 3. Opções de chaves no empréstimo

- Exigir a seleção de mutuário antes de disponibilizar novas chaves.
- Enviar o mutuário selecionado nas consultas de chaves e validar esse parâmetro no servidor.
- Aplicar o filtro de autorização junto ao de disponibilidade, antes da paginação, tanto na busca quanto nas opções iniciais.
- Para discentes e externos sem validade vigente ou sem autorizações, retornar lista vazia com mensagem apropriada no formulário.
- Ao trocar o mutuário na criação, limpar as chaves selecionadas e reiniciar busca e paginação.
- Descartar respostas de consultas anteriores após a troca de mutuário.
- Manter a validação de todas as chaves no servidor ao salvar, inclusive para requisições manipuladas ou permissões alteradas após abrir o formulário.
- Preservar os campos mínimos retornados pelas consultas, sem expor observações do mutuário nos endpoints de opções de empréstimo.

### 4. Edição de empréstimos e preservação do histórico

- Manter visíveis as chaves já vinculadas ao empréstimo, inclusive as que perderam autorização.
- Não remover automaticamente chaves com devolução registrada ou alterar o histórico.
- Validar novas inclusões pelas permissões atuais.
- Quando houver troca de mutuário, validar todas as chaves do empréstimo para o novo mutuário e preservar as restrições existentes relativas às devoluções.
- Ajustar a validação atual, que verifica todas as chaves em qualquer edição, para permitir alterações de observação quando o mutuário permanece o mesmo, sem conceder novas chaves após perda de validade ou autorização.
- Manter devoluções disponíveis independentemente da validade ou autorização atual.

### 5. Pesquisa e identificação

- Incluir observações nos resultados da listagem, com apresentação legível para textos longos.
- Ampliar a busca textual para nome usual, função/ocupação e observações, além de nome e matrícula.
- Manter paginação e permissões de acesso existentes.
- Permitir localizar por nome usual na seleção de mutuário do empréstimo e apresentá-lo junto ao nome completo.
- Limitar a pesquisa por observações à gestão de mutuários; não ampliar a exposição de dados nos seletores de empréstimo.

## Pontos do código envolvidos

| Área | Arquivos e locais |
| --- | --- |
| Dados | Nova migração em `database/migrations/`, `app/Models/Employee.php` e factory de mutuários, quando necessário |
| Cadastro | `app/Http/Controllers/Admin/EmployeeController.php`, `StoreEmployeeRequest.php` e `UpdateEmployeeRequest.php` |
| Interface do mutuário | `resources/js/Pages/Keys/Employee/Create.jsx`, `Edit.jsx`, `Show.jsx` e `Index.jsx` |
| Opções de empréstimo | `app/Http/Controllers/Admin/BorrowController.php` |
| Validação do empréstimo | `app/Actions/Borrows/EnsureBorrowerEligible.php`, `CreateBorrow.php` e `UpdateBorrow.php` |
| Interface do empréstimo | `resources/js/Pages/Keys/Borrow/Create.jsx`, `Edit.jsx` e componentes de formulário, busca e seleção |
| Cobertura | Testes de mutuários e testes existentes de opções, disponibilidade, transações e seleção de empréstimos |

Verificar os consumidores dos campos e das consultas antes de implementar. Relatórios e exportações não recebem novas colunas neste escopo; devem continuar funcionando com o nome completo existente.

## Critérios de aceite e cobertura

- [x] Criar e editar mutuários com nome usual e função/ocupação, aceitando campos vazios.
- [x] Criar e editar discentes e externos com múltiplas chaves autorizadas e carregar a seleção salva.
- [x] Rejeitar IDs de chaves inexistentes e impedir gravação parcial do cadastro e das permissões.
- [x] Salvar perfil restrito sem chaves e impedir novos empréstimos para ele.
- [x] Exibir somente chaves autorizadas e disponíveis em todas as páginas de opções dos perfis restritos.
- [x] Exibir todas as chaves disponíveis para servidores e colaboradores.
- [x] Impedir novos empréstimos com validade ausente ou vencida nos perfis restritos.
- [x] Rejeitar envio manual de chave não autorizada e alterações de permissão ocorridas após carregar o formulário.
- [x] Limpar seleção e paginação ao trocar mutuário na criação, sem reaproveitar respostas antigas.
- [x] Preservar histórico, chaves devolvidas e possibilidade de devolução após retirada de autorização.
- [x] Permitir editar observações de empréstimo existente sem autorizar novas chaves indevidamente.
- [x] Validar todas as chaves para o novo mutuário quando houver troca na edição.
- [x] Formatar telefone fixo e celular, aceitar colagem e preservar números legados sem truncamento.
- [x] Encontrar mutuários por nome, matrícula, nome usual, função/ocupação e observações na gestão de mutuários.
- [x] Exibir observações na listagem e nome usual na seleção de mutuário do empréstimo.
- [x] Preservar controle de acesso e não expor observações nos endpoints de opções de empréstimo.
- [x] Executar os testes afetados e a formatação exigida para os arquivos modificados durante a implementação.

## Ordem de entrega

1. Migração, validação e persistência dos campos e autorizações.
2. Formulários e detalhes do mutuário, incluindo telefone.
3. Filtros de empréstimo e comportamento da edição diante de mudanças de autorização.
4. Pesquisa, apresentação dos dados e validação dos cenários afetados.

## Limites e cuidados

- Não conceder autorizações automaticamente a cadastros existentes.
- Não alterar empréstimos históricos durante a migração.
- Não criar dependências, novos relatórios ou um novo sistema de permissões.
- A análise inicial deste plano foi estática; testes e validação visual da implementação estão registrados no acompanhamento.
- As premissas acima orientam a implementação e podem ser ajustadas antes de iniciá-la.

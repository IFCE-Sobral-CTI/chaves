# Plano de correção de empréstimos e devoluções

Data: 14/10/2026  
Escopo: criação, edição, consulta, devolução parcial/total e exclusão de recebimentos de chaves.  
Status: implementação iniciada; acompanhar entregas e estados na [lista de tarefas](2026-10-14-correcao-emprestimos-devolucoes-todo.md).

## Objetivo e limites da análise

Reduzir a latência percebida pela recepção, impedir empréstimos simultâneos da mesma chave e garantir que disponibilidade, recebimentos e encerramento representem o mesmo estado operacional.

Este plano parte da revisão estática do repositório. Os padrões de consultas repetidas e as lacunas de validação foram identificados no código; sua contribuição para a lentidão em produção ainda não foi medida. Não se deve atribuir um tempo de resposta aos achados sem instrumentação. A gravidade considera também segurança e integridade, independentemente da latência.

## Prioridades

| Prioridade | Problema | Gravidade | Resultado esperado |
| --- | --- | --- | --- |
| P0 | Disponibilidade exclui devoluções globalmente, sem correlacionar empréstimo/chave | Alta | Uma devolução anterior nunca libera uma chave pendente em outro empréstimo |
| P0 | Gravações sem atomicidade e proteção contra concorrência | Alta | Uma chave tem no máximo um empréstimo pendente e falhas não deixam gravações parciais |
| P0 | Exclusão de recebimento sem comprovar vínculo com o empréstimo | Alta | A autorização e a operação incidem sobre o mesmo conjunto de registros |
| P0 | Elegibilidade e permissões específicas não verificadas no backend | Alta | Toda gravação respeita validade e chaves autorizadas do mutuário |
| P1 | Disponibilidade recalculada por chave e novamente no controller | Alta | Validação em lote, com custo independente da quantidade de itens em termos de consultas de leitura |
| P1 | Devoluções duplicadas e edição que diverge do histórico | Alta | Operações preservam o histórico e o estado das chaves |
| P1 | Consultas N+1 nas devoluções | Média | Consultas de leitura não crescem por recebimento histórico |
| P2 | Listas completas de mutuários/chaves e renderização sem limite | Média | Transferência e renderização limitadas por página de resultados |
| P2 | Busca inicial redundante e paginação com estado anterior | Baixa a média | Uma requisição inicial e busca iniciando na página 1 |
| P2 | Buscas duplicadas de models, cálculos repetidos e código obsoleto | Baixa a média | Fluxo menor, verificações centralizadas e sem redundância |

P0 e P1 devem ser tratados de forma coordenada: otimizar a consulta incorreta antes de corrigir sua semântica manteria o risco de integridade.

## Invariantes obrigatórias

1. Uma chave está indisponível quando existe um empréstimo aberto que a contém e não existe recebimento dessa chave nesse mesmo empréstimo.
2. Uma chave não pode pertencer a dois empréstimos pendentes simultaneamente.
3. Uma devolução contém somente chaves vinculadas ao empréstimo e ainda pendentes naquele momento.
4. O encerramento ocorre quando não existem chaves pendentes. Datas e responsáveis devem acompanhar essa transição.
5. Uma operação que falha não deixa empréstimo sem associações, recebimento vazio ou encerramento parcialmente atualizado.
6. Discentes e externos precisam de permissão válida e autorização para cada chave solicitada. Servidores e colaboradores seguem as regras do domínio para seus tipos.
7. Exclusão de recebimento e edição não podem reabrir uma chave já emprestada a outra pessoa.
8. Toda transição relevante deixa trilha de auditoria suficiente para reconstruir o estado anterior e posterior.

## Etapa 1 — Medição e diagnóstico dos dados

### Implementação

- Medir separadamente o POST de criação/devolução e o GET de detalhes após o redirecionamento. Medir também abertura do formulário e busca de empréstimos.
- Registrar duração total, quantidade de consultas, tempo acumulado no banco, tamanho da resposta e resultado da operação, usando um identificador de requisição.
- Medir no navegador tempo até interação e custo de renderização das listas em um equipamento representativo da recepção.
- Usar métricas agregadas e amostragem limitada. Não registrar nomes, matrículas, observações, nomes de quem devolveu, cookies, tokens ou bindings SQL com dados pessoais.
- Confirmar migrations aplicadas e índices reais. Existe migration para `borrows.devolution`; não criar índice duplicado por suposição.
- Executar diagnóstico somente de leitura para identificar: chaves pendentes em múltiplos empréstimos, recebimentos duplicados, recebimentos sem chaves, empréstimos sem chaves, recebimentos de chaves fora do vínculo atual e divergências entre pendências e `devolution`.
- Verificar planos de execução das consultas de disponibilidade e busca com volume representativo, sem executar análise invasiva na produção em horário de atendimento.

### Entregáveis e aceite

- Baseline de p50/p95, volume de dados, consultas por operação e tamanho das respostas, com ambiente e método documentados.
- Relatório de inconsistências por IDs, com acesso restrito e sem correção automática.
- Confirmação de índices e migrations implantadas.

## Etapa 2 — Corrigir disponibilidade e validação em lote

### Implementação

- Substituir `Borrow::scopeKeysNotReceived()` por uma consulta que correlacione `borrow_key.borrow_id`, `receiveds.borrow_id` e `key_received.key_id`. Usar subconsultas/`EXISTS` para evitar listas globais de IDs no PHP.
- Centralizar a semântica de disponibilidade e reutilizá-la em criação, edição, seleção de chaves e validação final da gravação.
- Validar a existência e disponibilidade de todas as chaves selecionadas em lote. Remover a consulta global dentro de `keys.*` e a verificação duplicada em `keysBorrowed()`.
- Exigir `keys` como lista não vazia de IDs inteiros distintos. Definir limite operacional de itens coerente com a quantidade de chaves do campus, sem número arbitrário que bloqueie o trabalho real.
- Validar elegibilidade do mutuário e permissão de cada chave no servidor. A lista filtrada da interface não substitui essa verificação.
- Tratar validade conforme o tipo e a semântica temporal existente; documentar se a validade é um instante ou o fim de um dia antes de alterar sua interpretação.
- Usar apenas dados validados nas operações. Separar atributos do empréstimo dos IDs destinados à associação.

### Testes e aceite

- Regressão: A empresta X e Y, devolve X, permanece aberto por Y; B empresta X. X continua indisponível para C.
- Devolução parcial libera somente a chave devolvida no empréstimo correspondente.
- Lista vazia, IDs inexistentes e duplicados são rejeitados sem gravação.
- Mutuário expirado ou sem permissão para uma chave é rejeitado, inclusive em requisição direta.
- A quantidade de consultas de disponibilidade não cresce linearmente com as chaves selecionadas.

## Etapa 3 — Atomicidade e concorrência

### Implementação

- Criar ações de aplicação para criar empréstimo, editar empréstimo, devolver chaves e excluir recebimento. Manter controllers responsáveis por autorização, entrada e resposta, sem duplicar regras de negócio.
- Executar cada transição em transação, incluindo associações, atualização do encerramento e auditoria persistida no banco.
- Definir um protocolo único de bloqueio para todos os caminhos de escrita. Bloquear as linhas das chaves afetadas em ordem crescente de ID, inclusive na criação, quando ainda não existe empréstimo pendente para bloquear.
- Considerar os vínculos anteriores e os novos ao editar; ao excluir recebimento, considerar as chaves que voltariam a ficar pendentes.
- Revalidar disponibilidade e elegibilidade dentro da transação, após os bloqueios. Aplicar protocolo consistente aos bloqueios de empréstimo e mutuário quando necessários; documentar a ordem para evitar deadlocks.
- Adotar tentativas limitadas para deadlocks e resposta clara de conflito quando outra operação tiver ocupado a chave. Não apresentar uma disputa de disponibilidade como erro genérico de infraestrutura.
- Auditar todos os caminhos capazes de modificar vínculos ou encerramento, incluindo scripts e imports existentes. Bloqueio só funciona se todos os escritores respeitarem o protocolo.
- Avaliar uma restrição estrutural de ocupação no banco caso existam escritores que não possam seguir o protocolo. Um índice único no pivot histórico `(borrow_id, key_id)` impede vínculo duplicado no mesmo empréstimo, mas não impede dois empréstimos ativos da mesma chave.

### Testes e aceite

- Duas conexões reais tentam emprestar a mesma chave simultaneamente: exatamente uma operação conclui; a outra recebe conflito e não deixa registros parciais.
- Devolução e novo empréstimo simultâneos mantêm estado consistente.
- Falhas injetadas após criar o empréstimo, após criar o recebimento e antes de atualizar o encerramento fazem rollback completo.
- Executar os testes de concorrência com MySQL e conexões/processos independentes; um teste sequencial ou apenas SQLite não comprova o bloqueio em produção.

## Etapa 4 — Segurança e coerência das devoluções e edições

### Implementação

- Resolver o recebimento a excluir pela relação do empréstimo, ou usar binding explicitamente escopado. Rejeitar IDs de outro empréstimo antes de qualquer mutação.
- Mover a validação de devolução para um Form Request específico, com nome de quem devolveu como string e limites compatíveis com a coluna.
- Receber IDs de chaves no corpo da requisição como lista validada; migrar a interface junto com a rota para evitar incompatibilidade.
- Bloquear novas devoluções de empréstimos encerrados e devoluções de chaves já recebidas.
- Definir comportamento para reenvio: rejeitar conflito de forma previsível ou adotar chave de idempotência persistida na mesma transação. O aceite exige ausência de um segundo recebimento para a mesma operação.
- Consultar os IDs distintos recebidos no banco ou carregar relações antecipadamente. Retornar lista JSON com índices contíguos, evitando o objeto gerado por `unique()->toArray()` com lacunas.
- Definir edição segura: campos descritivos podem ser alterados; alterações nas chaves devem preservar recebimentos existentes e passar pelas mesmas verificações de disponibilidade.
- Retirar o encerramento manual do fluxo comum de edição. Eventual correção administrativa de data/estado precisa de operação explícita, autorização e auditoria, mantendo as invariantes.
- Ao excluir recebimento, impedir reabertura quando uma chave já estiver pendente em outro empréstimo. Exibir conflito e manter os dados intactos.
- Comparar conjuntos de chaves pendentes, em vez de depender apenas da igualdade entre contagens, especialmente ao lidar com dados legados inconsistentes.
- Registrar IDs das chaves, estado anterior/posterior, usuário executor e motivo quando houver correção administrativa. Evitar logs de sucesso antes do commit.

### Testes e aceite

- Exclusão com recebimento de outro empréstimo não altera nenhum registro.
- Usuário sem a permissão específica não pode criar, devolver, editar ou excluir recebimentos.
- Reenvio e devoluções simultâneas não duplicam recebimentos.
- Devoluções parciais e totais encerram o empréstimo apenas quando não há pendências.
- Excluir devolução não cria uma segunda ocupação da mesma chave.
- A interface recebe `received` como array e funciona após históricos com duplicatas legadas.
- Edição não remove vínculo de chave já recebida nem adiciona pendência mantendo o empréstimo encerrado.

## Etapa 5 — Reduzir consultas e custo da interface

### Implementação

- Reutilizar o model resolvido pela rota com carregamento das relações necessárias, evitando nova busca por ID.
- Calcular `receivedKeys()` uma vez na exibição; eliminar N+1 no processamento da devolução e de sua exclusão.
- Remover carregamentos não usados pela listagem, após conferir consumidores das props.
- Revisar o custo da auditoria síncrona de atributos relacionais. Preservar a auditoria; otimizar consultas e payload antes de considerar processamento assíncrono.
- Introduzir busca autorizada de mutuários e chaves com limite de resultados, projeção de campos e paginação. Preservar itens selecionados entre buscas.
- Separar filtragem e atualização da seleção no componente de chaves. Corrigir dependências dos efeitos e usar IDs estáveis como chaves React.
- Evitar requisição de busca na montagem inicial da listagem; ao mudar o termo, enviar página 1 explicitamente e impedir que respostas antigas substituam resultados novos.
- Rever a pesquisa `LIKE '%termo%'`, que percorre histórico quando existe termo. Confirmar a necessidade de pesquisar empréstimos encerrados e manter o comportamento esperado; qualquer mudança deve ser explícita na interface.
- Avaliar índices com base nos planos de execução. Índices B-tree comuns não resolvem automaticamente pesquisa por substring com curinga inicial.
- Não armazenar disponibilidade em cache como fonte de verdade. A seleção é indicativa; a gravação sempre revalida no banco.

### Testes e aceite

- Uma única requisição de listagem na abertura, fora de redirecionamentos necessários.
- Busca a partir de qualquer página começa na página 1 e mantém a seleção do formulário.
- Quantidade de consultas de leitura da devolução permanece estável com o aumento do histórico.
- Respostas de seleção respeitam o limite contratado e não incluem dados pessoais desnecessários.
- Comparação de latência, payload e renderização antes/depois com o mesmo conjunto de dados.

## Etapa 6 — Dados legados, schema e implantação

### Dados e migrations

- Separar correção de código da reconciliação dos dados. Não inferir automaticamente qual empréstimo é correto quando a mesma chave aparece pendente em dois registros; a recepção precisa confirmar a situação física.
- Criar ferramenta de diagnóstico com modo de simulação e relatório por IDs. Reparos devem ser explícitos, auditados, reexecutáveis e protegidos por transação.
- Após reconciliação, avaliar índices únicos para os pares `(borrow_id, key_id)` e `(received_id, key_id)`. O segundo não impede duplicatas em recebimentos diferentes; a regra de devolução ainda depende do fluxo transacional.
- Verificar o campo legado `borrow_key.received`, atualmente separado do histórico de recebimentos. Definir uma fonte de verdade e só remover o campo após checar todos os consumidores e garantir compatibilidade.
- Testar migrations com volume semelhante ao real e avaliar duração e bloqueios antes da produção.

### Pipeline e entrega

- Organizar mudanças em entregas revisáveis: diagnóstico; consulta/validação; atomicidade; devolução/edição segura; interface; migrations adicionais.
- Cada entrega passa por análise de diff, formatação, testes afetados e build quando houver alterações frontend. Rodar a suíte completa antes de publicar o conjunto de mudanças do fluxo.
- Usar banco de testes isolado, dados sintéticos ou anonimizados e credenciais de menor privilégio. Nunca executar testes destrutivos no banco operacional.
- Atualizar a documentação das ações, protocolo de bloqueio e decisões sobre edição e reenvio.
- Garantir backup e procedimento de restauração verificado antes de reparar dados ou alterar schema.
- Implantar em janela de menor uso e monitorar erros, conflitos, deadlocks, duração de consultas e latência p95 durante e após a entrega.
- Evitar versões antigas e novas escrevendo simultaneamente durante a introdução do protocolo de bloqueio. Usar janela breve de suspensão das escritas ou outra estratégia que assegure versão uniforme dos escritores.

### Reversão

- Preferir migrations aditivas compatíveis com a versão anterior na primeira entrega; adiar remoções de campos/rotas até terminar a transição.
- Se houver regressão, suspender temporariamente as escritas afetadas, preservar evidências e implantar correção ou versão segura.
- Não reverter indiscriminadamente para a implementação que permite dupla ocupação. O plano de rollback deve identificar previamente uma versão que preserve as invariantes ou manter as escritas suspensas até a correção.
- Reparos de dados não são revertidos com rollback de código. Manter registro de alterações e plano específico de restauração/reconciliação para cada reparo.

## Metas de desempenho e critérios de conclusão

As metas abaixo são propostas iniciais e devem ser confirmadas após o baseline, considerando infraestrutura e volume reais:

- Reduzir em pelo menos 50% a latência p95 dos fluxos que apresentarem o gargalo de consultas repetidas, sob carga e dados equivalentes.
- Buscar p95 de até 1 segundo no servidor para criação e devolução em carga normal; acompanhar separadamente o GET posterior e a experiência completa no navegador.
- Manter quantidade de consultas de leitura aproximadamente constante para 1, 5 e 10 chaves e para diferentes quantidades de recebimentos. Escritas nos pivots podem crescer com os itens; esse crescimento não deve ser confundido com N+1 de leitura.
- Não introduzir novas inconsistências nos cenários concorrentes, reenvios e falhas injetadas.
- Não haver gravação ou exclusão fora do escopo autorizado.
- Registrar as exceções de dados legados e reconciliá-las antes de habilitar restrições que dependam de dados limpos.

Concluir o plano exige evidências dos testes, comparação antes/depois, ausência de regressões funcionais e observação de pelo menos um período representativo de atendimento. Se uma meta de latência não for atingida, investigar a parcela medida de banco, servidor, rede ou navegador antes de ampliar a otimização.

## Responsabilidades sugeridas

| Frente | Responsabilidade |
| --- | --- |
| Engenharia backend | Consulta de disponibilidade, ações transacionais, validação, autorização e testes concorrentes |
| Engenharia frontend | Seleção limitada, busca, feedback de conflitos e preservação de estado |
| DevSecOps | Instrumentação com privacidade, pipeline, rollout, backup e reversão |
| Responsável pelos dados/recepção | Confirmação física das chaves, reconciliação de conflitos e validação operacional |

Não estimar prazo fechado antes do diagnóstico dos dados e da medição. O caminho crítico é corrigir a semântica de disponibilidade, estabelecer o protocolo de concorrência e validar a reconciliação necessária.

## Referências no repositório

- `app/Models/Borrow.php`: disponibilidade, agregação de recebimentos e pesquisa.
- `app/Http/Controllers/Admin/BorrowController.php`: gravações, devoluções, exclusões e carregamento de dados.
- `app/Http/Requests/StoreBorrowRequest.php` e `UpdateBorrowRequest.php`: validações por chave e elegibilidade.
- `app/Models/Employee.php`: validade e permissões específicas.
- `app/Models/Received.php`: relações e auditoria dos recebimentos.
- `resources/js/Pages/Keys/Borrow/`: busca, seleção e formulário de devolução.
- `database/migrations/2026_06_29_000002_add_performance_indexes.php`: índices já previstos.
- `tests/Feature/RemediationTest.php` e `BorrowScopeTest.php`: cobertura existente a complementar.

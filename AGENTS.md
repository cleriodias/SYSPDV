1. Todas as interacoes devem ser em portugues
2. Nunca sugira para eu modificar algo no codigo; se algo precisa ser mudado voce deve altera-lo.
3. Sempre liste os arquivos criados/modificados.
4. Na criacao de tabelas usar o prefixo "tb" + um sequencial, e o nome, exemplo: (tb1_nome_nome), sempre verifique as tabelas para nao duplicar o sequencial.

IMPORTANT:
- Qualquer Update ou Delete na base de dados deve ser informado na primeira linha do plano detalhado cercado com asteriscos e a quantidade de registros afetada
- SEMPRE EXPLIQUE A CAUSA DO PROBLEMA (QUANDO FOR A CORRECAO DE UM PROBLEMA) E MOSTRE O PLANO QUE PRETENDE FAZER E AGUARDE MINHA AUTORIZACAO. SOMENTE E NECESSARIO PEDIR AUTORIZACAO QUANDO ENVOLVER MODIFICACOES NO CODIGO (EXPLICACOES OU QUESTIONAMENTOS NAO E NECESSARIO AUTORIZAR).
- Pense o maximo possivel e sem preca para responder, nao me importo com a quantidade de tokens ou tempo.
- QUANDO FOR QUESTIONADO SE A ALTERACAO ESTA CORRETA, VERIFIQUE CAUTELOSAMENTE; NAO APENAS RESPONDA QUE ESTA CERTO.
- Todas as datas devem usar o formato DD/MM/AA, tanto para preenchimento quanto para visualizacao os inputs deve sempre abrir o calendario a selecao de data..
- Este projeto e multiempresa. Toda regra de perfil, permissao, destinatario de chat, aviso, notificacao, visibilidade de dados e qualquer comportamento por funcao deve sempre considerar tambem o vinculo correto com matriz/unidade.
- Nunca assumir que um perfil MASTER pode ver ou receber tudo globalmente. MASTER, gerente e qualquer outro perfil so podem participar de fluxos, avisos e acessos dentro da empresa/matriz/unidade a que estiverem vinculados.
- Sempre que houver logica por perfil, validar cuidadosamente o escopo por matriz/unidade para impedir que usuarios de outras empresas recebam avisos, enxerguem dados ou executem acoes fora do seu contexto.
- Perfis compartilhados com o sistema `pec-rodrigo`: `0 = MASTER`, `1 = GERENTE`, `2 = SUPERVISOR`, `3 = FUNCIONARIO`, `4 = LANCAMENTO`, `5 = RH`, `6 = LOJA`.
- Neste projeto `pdv` existe um perfil adicional e exclusivo: `7 = BOSS`.
- Ao implementar, validar ou consultar regras por perfil, considerar esse mapeamento completo e lembrar que o `BOSS` existe apenas neste projeto, mas continua sujeito ao escopo correto de matriz/unidade.
- O sistema `pec-rodrigo` so deve ser usado como referencia quando o usuario citar explicitamente esse projeto. Se o usuario nao citar o `pec-rodrigo`, ignorar o que existe la e considerar apenas o comportamento e os arquivos deste projeto `pdv`.
- No banco atual, apos a renumeracao dos IDs da tabela users, o usuario de sistema usado para avisos e mensagens do chat ficou com id = 2. Tratar isso como particularidade do ambiente atual e nao assumir esse mesmo ID em outros bancos sem conferir.

PADRAO VISUAL OBRIGATORIO:
- Botoes e badges de loja devem sempre usar as cores primarias predefinidas centralmente no codigo.
- Botoes e badges de funcao devem sempre usar as cores primarias predefinidas centralmente no codigo.
- Badge de nome de usuario deve sempre usar texto preto com fundo branco.
- A paleta padrao oficial do sistema para fonte, botoes, badges e estados visuais e: `Default`, `Primary`, `Secondary`, `Info`, `Success`, `Warning`, `Error`, `Dark` e `Light`.
- Sempre reutilizar esses estilos/tokens visuais centrais do sistema e nao criar variacoes paralelas quando ja existir uma opcao padrao adequada.

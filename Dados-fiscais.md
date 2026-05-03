Assumindo seguro auto tradicional no Brasil e olhando para cadastro/ERP/tela de venda, os dados fiscais mais importantes são estes:

1. O que normalmente existe no produto/operação

Grupo	Campo	Obrigatório	Observação
Classificação fiscal	Natureza da receita	Sim	Separar prêmio de seguro de corretagem/intermediação
Tributo federal	Incidência de IOF	Sim	Em seguro auto, normalmente sim
Tributo federal	Alíquota de IOF	Sim	Parametrizável; para seguro de danos, hoje costuma ser 7,38%
Base tributável	Base de cálculo do IOF	Sim	Em regra, o prêmio/base tributável da operação
Documento da operação	Tipo de documento	Sim	Proposta, apólice, endosso, certificado, parcela/carnê
Partes	Seguradora emissora	Sim	CNPJ e dados cadastrais
Partes	Corretora/intermediário	Condicional	CNPJ, inscrição municipal, canal, comissão
Partes	Tomador/pagador	Sim	CPF/CNPJ, nome, endereço
Partes	Segurado	Sim	CPF/CNPJ, nome, endereço
Localidade	Município/UF do tomador	Sim	Relevante para cadastros e, se houver NFS-e, para ISS
Localidade	Município/UF da corretora	Condicional	Relevante para ISS/NFS-e
Financeiro fiscal	Prêmio líquido	Sim	Antes/sem outros componentes, conforme regra interna
Financeiro fiscal	IOF destacado	Sim	Valor separado no cálculo
Financeiro fiscal	Prêmio total	Sim	Valor final cobrado
Financeiro fiscal	Comissão	Condicional	Quando houver intermediação
Financeiro fiscal	Repasse/comissão do parceiro	Condicional	Se houver canal/parceiro

2. O ponto mais importante de regra

Seguro auto é tratado como seguro de danos, e a SUSEP enquadra esse tipo dentro de seguros de danos.
Para IOF, a Receita informa que o recolhimento nas operações de seguro é feito pela seguradora.
Na redação vigente do regulamento do IOF, há referência a 0,38% para seguros de vida/acidentes/trabalho e 7,38% nas demais operações de seguro. Na prática, seguro auto costuma cair em “demais operações de seguro”, então o sistema deve suportar IOF 7,38% parametrizável.

3. O que normalmente NÃO entra como fiscal do produto de seguro em si

NCM: em geral não se aplica ao produto de seguro, porque não é mercadoria.
CFOP: em geral não é o campo central do produto de seguro; costuma não ser tratado como venda de mercadoria.
CST/CSOSN: podem ser relevantes para a empresa emissora de documento fiscal de serviço, mas não como campo padrão do “produto seguro auto” em si.

4. Quando entra ISS e NFS-e
Aqui vale separar duas coisas:

Prêmio do seguro: normalmente o foco fiscal principal é IOF.
Corretagem/intermediação: aqui pode haver NFS-e/ISS, porque a LC 116 lista em 10.01 o agenciamento/corretagem/intermediação de seguros.

Então, se sua operação tem corretora cobrando ou faturando intermediação, o sistema deve suportar também:

código do serviço da NFS-e
item da lista de serviços: LC 116, item 10.01
município de incidência
alíquota de ISS
CNPJ/CPF do tomador do serviço
inscrição municipal do prestador
número da NFS-e / DPS / status de emissão

5. Estrutura mínima recomendada no sistema
Se eu fosse modelar isso no cadastro do produto/processo de seguro auto, eu teria estes blocos:

Bloco fiscal do produto

ramo fiscal: seguro de danos
incide IOF: sim
alíquota IOF padrão
permite override manual da alíquota: sim/não
regra de base do IOF
destacar IOF na proposta/apólice: sim

Bloco fiscal da intermediação

há corretagem: sim/não
gera NFS-e: sim/não
item da lista de serviço LC 116
município de incidência do ISS
alíquota ISS
prestador da NFS-e
tomador da NFS-e

6. Alerta de implementação

Não recomendo fixar NCM, CFOP ou enquadramento municipal de ISS no produto sem validação fiscal.
O sistema deve suportar os campos, mas a definição final de ISS, documento fiscal e tomador da NFS-e depende do modelo operacional da corretora/seguradora e da legislação municipal.

Fontes

Receita Federal, visão geral do IOF: https://www.gov.br/receitafederal/pt-br/assuntos/orientacao-tributaria/tributos/IOF
Decreto nº 6.306/2007 e alterações, regulamento do IOF: https://www.planalto.gov.br/ccivil_03/_Ato2007-2010/2007/Decreto/D6306.htm
Decreto nº 6.339/2008, com referência a 7,38% nas demais operações de seguro: https://www.planalto.gov.br/ccivil_03/_ato2007-2010/2008/decreto/d6339.htm
SUSEP, seguros de danos: https://www.gov.br/susep/pt-br/copy_of_planos-e-produtos/seguros/seguro-de-danos
LC 116/2003, item 10.01 para corretagem/intermediação de seguros: https://www.planalto.gov.br/ccivil_03/leis/lcp/lcp116.htm
Portal NFS-e, campos e regras gerais: https://www.gov.br/nfse/pt-br/saiba-mais/como-a-nfs-e-e-gerada/o-que-e-dps
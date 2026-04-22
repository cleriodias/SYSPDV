# Mudar Funcao e Loja

## Objetivo

Aplicar na `main` a funcionalidade unificada de troca de unidade e funcao, com sessao temporaria e sem gravar a funcao alterada no banco.

## Regra funcional

1. O botao/atalho deve se chamar `Trocar`.
2. A tela deve mostrar duas secoes abertas ao mesmo tempo:
   - `Trocar Unidade`
   - `Trocar Funcao`
3. O usuario escolhe unidade, funcao, ou as duas, e depois clica em `Atualizar sessao`.
4. Ao atualizar a sessao, o usuario deve ser redirecionado para o `dashboard`.
5. A troca de funcao e temporaria de sessao. Nao pode gravar a nova funcao em `users.funcao`.
6. Quem pode acessar:
   - `MASTER` (0)
   - `GERENTE` (1)
   - `SUB-GERENTE` (2)
   - `CAIXA` (3)
7. `LANCHONETE` (4), `FUNCIONARIO` (5) e `CLIENTE` (6) nao podem acessar.
8. A funcao temporaria pode apenas manter ou diminuir em relacao a funcao de origem:
   - Master: 0 a 6
   - Gerente: 1 a 6
   - Sub-Gerente: 2 a 6
   - Caixa: 3 a 6
9. Mesmo rebaixado, o usuario deve continuar vendo a opcao `Trocar`, porque a permissao para abrir essa tela depende da funcao de origem.

## Causa do problema original

1. `Trocar Unidade` e `Trocar Funcao` estavam separadas.
2. A troca de funcao gravava `funcao` direto no banco.
3. Parte das autorizacoes e menus ainda olhava para `funcao_original` ou para a funcao persistida, o que impedia a sessao temporaria de funcionar corretamente.
4. O layout da tela ainda estava em formato de cards com legenda, e nao em botoes operacionais rapidos.

## Backend

### 1. Criar middleware para funcao ativa de sessao

Criar [app/Http/Middleware/ApplyActiveRole.php](c:/xampp/htdocs/pec-rodrigo/app/Http/Middleware/ApplyActiveRole.php)

Responsabilidade:

1. Ler o usuario autenticado.
2. Descobrir a funcao de origem:
   - `funcao_original ?? funcao`
3. Ler `active_role` da sessao.
4. Se `active_role` estiver invalida, ausente, acima da origem ou maior que `6`, voltar para a funcao de origem.
5. Aplicar a funcao ativa apenas no objeto do usuario da requisicao:
   - `setAttribute('funcao', (int) $activeRole)`

Regra importante:

- a funcao temporaria deve viver em sessao
- nao salvar no banco

### 2. Registrar middleware no fluxo web

Alterar [bootstrap/app.php](c:/xampp/htdocs/pec-rodrigo/bootstrap/app.php)

Adicionar `\App\Http\Middleware\ApplyActiveRole::class` no grupo `web`, antes dos pontos que compartilham dados com o front.

Objetivo:

- menus, controllers e layouts devem enxergar a funcao ativa da sessao na mesma requisicao

### 3. Unificar a logica de troca no controller principal

Alterar [app/Http/Controllers/UnitSwitchController.php](c:/xampp/htdocs/pec-rodrigo/app/Http/Controllers/UnitSwitchController.php)

Aplicar estes pontos:

1. Manter a rota principal de troca em `reports.switch-unit`.
2. No `index()`:
   - validar acesso pela funcao de origem `[0,1,2,3]`
   - carregar unidades permitidas do usuario
   - carregar opcoes de funcao conforme a hierarquia de origem
   - enviar para o front:
     - `units`
     - `roles`
     - `currentUnitId`
     - `currentRole`
     - `currentRoleLabel`
     - `originalRole`
     - `originalRoleLabel`
3. No `update()`:
   - validar `unit_id`
   - validar `role`
   - garantir que a unidade pertence ao conjunto permitido
   - garantir que a funcao escolhida esta dentro da faixa permitida pela origem
   - gravar na sessao:
     - `active_unit`
     - `active_role`
   - redirecionar para `dashboard`
   - enviar flash `Sessao atualizada com sucesso!`

### 4. Redirecionar o fluxo antigo de funcao

Alterar [app/Http/Controllers/RoleSwitchController.php](c:/xampp/htdocs/pec-rodrigo/app/Http/Controllers/RoleSwitchController.php)

Deixar apenas como redirecionamento para `reports.switch-unit`.

Objetivo:

- nao quebrar links antigos
- concentrar tudo em uma tela unica

### 5. Ajustar autorizacoes para usar funcao ativa

Revisar pontos que ainda olhavam para Master original em vez da funcao ativa da sessao.

Arquivos ajustados:

- [routes/web.php](c:/xampp/htdocs/pec-rodrigo/routes/web.php)
- [app/Http/Controllers/DatabaseToolsController.php](c:/xampp/htdocs/pec-rodrigo/app/Http/Controllers/DatabaseToolsController.php)
- [app/Http/Controllers/NoticeController.php](c:/xampp/htdocs/pec-rodrigo/app/Http/Controllers/NoticeController.php)
- [app/Http/Controllers/SupplierController.php](c:/xampp/htdocs/pec-rodrigo/app/Http/Controllers/SupplierController.php)
- [app/Http/Controllers/SalesDisputeController.php](c:/xampp/htdocs/pec-rodrigo/app/Http/Controllers/SalesDisputeController.php)
- [app/Http/Controllers/UserController.php](c:/xampp/htdocs/pec-rodrigo/app/Http/Controllers/UserController.php)

Regra aplicada:

- autorizacoes normais devem usar a funcao ativa da sessao (`user->funcao`)
- somente a permissao para abrir a tela `Trocar` continua usando a funcao de origem

## Sessao

### `active_unit`

Estrutura usada:

```php
[
    'id' => $unit->tb2_id,
    'name' => $unit->tb2_nome,
    'address' => $unit->tb2_endereco,
    'cnpj' => $unit->tb2_cnpj,
]
```

### `active_role`

Valor inteiro da funcao ativa da sessao.

## Frontend

### 1. Tela unica

Alterar [resources/js/Pages/Reports/SwitchUnit.jsx](c:/xampp/htdocs/pec-rodrigo/resources/js/Pages/Reports/SwitchUnit.jsx)

Aplicar:

1. Titulo da pagina: `Trocar`
2. Mostrar as duas secoes abertas na mesma tela:
   - `Trocar Unidade`
   - `Trocar Funcao`
3. Formulario unico com:
   - `unit_id`
   - `role`
4. Enviar para `reports.switch-unit.update`
5. Ao concluir, redirecionamento vem do backend para `dashboard`

### 2. Layout visual final

O layout final desta branch ficou assim:

1. Remover legendas/subtitulos dos itens selecionaveis.
2. Usar botoes em vez de cards explicativos.
3. Aplicar o mesmo ciclo de cores nas duas secoes, na mesma ordem.

Exemplo de ordem:

- azul
- preto
- amarelo
- verde
- vermelho
- roxo
- ciano

4. Destacar o item atual com estado visual mais forte e selo `Atual`.
5. Deixar os botoes menores.
6. Organizar em grade com ate `4 por linha` em telas maiores.

### 3. Ajustar atalhos e menus

Alterar:

- [resources/js/Layouts/AuthenticatedLayout.jsx](c:/xampp/htdocs/pec-rodrigo/resources/js/Layouts/AuthenticatedLayout.jsx)
- [resources/js/Pages/Settings/Menu.jsx](c:/xampp/htdocs/pec-rodrigo/resources/js/Pages/Settings/Menu.jsx)
- [resources/js/Pages/Settings/Config.jsx](c:/xampp/htdocs/pec-rodrigo/resources/js/Pages/Settings/Config.jsx)
- [resources/js/Pages/Settings/ProfileAccess.jsx](c:/xampp/htdocs/pec-rodrigo/resources/js/Pages/Settings/ProfileAccess.jsx)
- [resources/js/Pages/Settings/MenuOrder.jsx](c:/xampp/htdocs/pec-rodrigo/resources/js/Pages/Settings/MenuOrder.jsx)
- [resources/js/Pages/Lanchonete/Terminal.jsx](c:/xampp/htdocs/pec-rodrigo/resources/js/Pages/Lanchonete/Terminal.jsx)

Aplicar:

1. Trocar texto `Trocar Unidade` por `Trocar`
2. Remover item isolado `Trocar Funcao`
3. Manter apenas `switch_unit` como chave funcional no menu
4. Atualizar atalhos antigos para a tela unificada

## Ordem recomendada para reaplicar na main

1. Criar `ApplyActiveRole`
2. Registrar middleware em `bootstrap/app.php`
3. Ajustar `UnitSwitchController`
4. Ajustar `RoleSwitchController`
5. Ajustar rotas e autorizacoes backend
6. Ajustar `AuthenticatedLayout`
7. Ajustar tela `SwitchUnit.jsx`
8. Ajustar menus, atalhos, organizacao e permissao de menu
9. Validar rotas e build

## Validacao tecnica

Executar:

```powershell
php -l app\Http\Middleware\ApplyActiveRole.php
php -l app\Http\Controllers\UnitSwitchController.php
php -l app\Http\Controllers\RoleSwitchController.php
php -l app\Http\Controllers\DatabaseToolsController.php
php -l app\Http\Controllers\NoticeController.php
php -l app\Http\Controllers\SupplierController.php
php -l app\Http\Controllers\SalesDisputeController.php
php -l app\Http\Controllers\UserController.php
php -l routes\web.php
php artisan route:list --name=switch
cmd /c npm run build
```

## Checklist funcional

1. `Master` entra em `Trocar`, escolhe outra unidade e funcao menor, atualiza sessao e cai no dashboard.
2. `Master` rebaixado perde acessos altos do menu, mas continua vendo `Trocar`.
3. `Master` consegue voltar para `MASTER` pela tela `Trocar`.
4. `Gerente` consegue trocar para `Sub-Gerente`, `Caixa`, `Lanchonete`, `Funcionario` e `Cliente`, mas nao para `Master`.
5. `Sub-Gerente` nao sobe para `Gerente` nem `Master`.
6. `Caixa` nao sobe para `Sub-Gerente`, `Gerente` ou `Master`.
7. `Lanchonete`, `Funcionario` e `Cliente` nao acessam a opcao.
8. O botao `Atualizar sessao` redireciona para o dashboard.
9. A tela mostra botoes menores, sem legenda, com o mesmo padrao de cor em unidade e funcao, e ate 4 por linha.

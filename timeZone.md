# Manual de Correcao de Timezone para Main

## Objetivo

Garantir que a aplicacao:

1. grave data/hora sempre em `America/Sao_Paulo`
2. use sessao MySQL em `-03:00`
3. exiba data/hora no frontend sempre no horario de Brasilia/Sao Paulo
4. nao dependa do fuso da maquina, navegador ou regiao local

## Causa do problema

O problema apareceu em duas camadas:

1. Backend:
   alguns fluxos ainda usavam `Carbon::now()` diretamente, o que fazia a gravacao depender do timezone efetivo do processo PHP.

2. Frontend:
   varias telas usavam `new Date(...).toLocaleString('pt-BR')` e `toLocaleDateString('pt-BR')` sem fixar `America/Sao_Paulo`.
   Isso fazia a exibicao variar conforme o navegador e o sistema operacional.

## O que precisa existir no .env

Garantir estas chaves:

```env
APP_TIMEZONE=America/Sao_Paulo
DB_TIMEZONE=-03:00
```

## Alteracoes de backend que devem ir para a main

### 1. Fixar timezone do app no boot

Aplicar em [app/Providers/AppServiceProvider.php](c:\xampp\htdocs\pec-rodrigo\app\Providers\AppServiceProvider.php):

- importar:
  - `Carbon\Carbon`
  - `Illuminate\Database\DatabaseManager`
  - `Illuminate\Support\Facades\Config`
  - `Illuminate\Support\Facades\Date`
  - `Throwable`

- no metodo `boot()` adicionar:

```php
$appTimezone = env('APP_TIMEZONE', 'America/Sao_Paulo');
$dbTimezone = env('DB_TIMEZONE', '-03:00');

Config::set('app.timezone', $appTimezone);
date_default_timezone_set($appTimezone);

Date::useCallable(function ($date) use ($appTimezone) {
    return $date instanceof Carbon
        ? $date->copy()->setTimezone($appTimezone)
        : $date;
});

$this->app->afterResolving('db', function (DatabaseManager $databaseManager) use ($dbTimezone) {
    try {
        $connection = $databaseManager->connection();

        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $connection->statement("SET time_zone = '{$dbTimezone}'");
    } catch (Throwable $exception) {
        report($exception);
    }
});
```

### 2. Garantir timezone da conexao MySQL

Aplicar em [config/database.php](c:\xampp\htdocs\pec-rodrigo\config\database.php):

nos blocos `mysql` e `mariadb`, em `options`, incluir:

```php
PDO::MYSQL_ATTR_INIT_COMMAND => sprintf(
    "SET time_zone = '%s'",
    env('DB_TIMEZONE', '-03:00')
),
```

Isso deve coexistir com:

```php
PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
```

### 3. Trocar gravacoes diretas que usavam Carbon::now()

Substituir `Carbon::now()` por `now()` nos fluxos que gravam horario.

Arquivos ja ajustados nesta branch:

- [app/Http/Controllers/SaleController.php](c:\xampp\htdocs\pec-rodrigo\app\Http\Controllers\SaleController.php)
- [app/Http/Controllers/BoletoController.php](c:\xampp\htdocs\pec-rodrigo\app\Http\Controllers\BoletoController.php)
- [app/Http/Controllers/SalaryAdvanceController.php](c:\xampp\htdocs\pec-rodrigo\app\Http\Controllers\SalaryAdvanceController.php)
- [app/Http/Controllers/UserController.php](c:\xampp\htdocs\pec-rodrigo\app\Http\Controllers\UserController.php)

Pontos principais:

- `SaleController`
  - `data_hora`
  - data da venda principal
  - atualizacao de itens de comanda

- `BoletoController`
  - `paid_at`

- `SalaryAdvanceController`
  - referencias ao mes atual

- `UserController`
  - referencias de mes atual e datas de agrupamento

## Alteracoes de frontend que devem ir para a main

### 1. Criar utilitario unico de data

Criar [resources/js/Utils/date.js](c:\xampp\htdocs\pec-rodrigo\resources\js\Utils\date.js) com:

- `BRAZIL_TIME_ZONE = 'America/Sao_Paulo'`
- `formatBrazilDate()`
- `formatBrazilDateTime()`
- `getBrazilTodayInputValue()`

Esse utilitario:

1. fixa `timeZone: 'America/Sao_Paulo'`
2. normaliza datas `YYYY-MM-DD`
3. normaliza timestamps SQL `YYYY-MM-DD HH:MM:SS`
4. normaliza ISO sem offset

### 2. Substituir formatadores locais nas telas

Trocar chamadas deste tipo:

```js
new Date(value).toLocaleString('pt-BR')
new Date(value).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' })
new Date(value).toLocaleDateString('pt-BR')
new Date().toISOString().slice(0, 10)
```

por:

```js
formatBrazilDate(value)
formatBrazilDateTime(value)
getBrazilTodayInputValue()
```

Arquivos principais ja ajustados nesta branch:

- [resources/js/Pages/Dashboard.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Dashboard.jsx)
- [resources/js/Pages/Cashier/Close.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Cashier\Close.jsx)
- [resources/js/Pages/Finance/BoletoIndex.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Finance\BoletoIndex.jsx)
- [resources/js/Pages/Finance/ExpenseIndex.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Finance\ExpenseIndex.jsx)
- [resources/js/Pages/Finance/SalaryAdvanceCreate.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Finance\SalaryAdvanceCreate.jsx)
- [resources/js/Pages/Finance/SalaryAdvanceIndex.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Finance\SalaryAdvanceIndex.jsx)
- [resources/js/Pages/Products/ProductDiscard.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Products\ProductDiscard.jsx)
- [resources/js/Pages/Reports/Advances.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Advances.jsx)
- [resources/js/Pages/Reports/CashClosure.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\CashClosure.jsx)
- [resources/js/Pages/Reports/CashDiscrepancies.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\CashDiscrepancies.jsx)
- [resources/js/Pages/Reports/Discards.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Discards.jsx)
- [resources/js/Pages/Reports/Expenses.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Expenses.jsx)
- [resources/js/Pages/Reports/Hoje.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Hoje.jsx)
- [resources/js/Pages/Reports/Lanchonete.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Lanchonete.jsx)
- [resources/js/Pages/Reports/Refeicao.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Refeicao.jsx)
- [resources/js/Pages/Reports/SalesDetailed.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\SalesDetailed.jsx)
- [resources/js/Pages/Reports/SalesToday.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\SalesToday.jsx)
- [resources/js/Pages/Reports/Suppliers.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Suppliers.jsx)
- [resources/js/Pages/Reports/Vale.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Vale.jsx)
- [resources/js/Pages/Settings/DatabaseTools.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Settings\DatabaseTools.jsx)
- [resources/js/Pages/Settings/DisputaVendas.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Settings\DisputaVendas.jsx)
- [resources/js/Pages/Supplier/Disputes.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Supplier\Disputes.jsx)
- [resources/js/Pages/Users/UserShow.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Users\UserShow.jsx)

## Sequencia recomendada para aplicar na main

1. ajustar `.env`
2. ajustar `config/database.php`
3. ajustar `AppServiceProvider.php`
4. trocar `Carbon::now()` por `now()` nos controladores que gravam data/hora
5. criar `resources/js/Utils/date.js`
6. atualizar as telas para usar o utilitario
7. limpar caches
8. rebuildar o frontend
9. validar com uma gravacao real

## Comandos de validacao

### PHP

```powershell
php artisan about
php -l app\Providers\AppServiceProvider.php
php -l app\Http\Controllers\SaleController.php
php -l app\Http\Controllers\BoletoController.php
php -l app\Http\Controllers\SalaryAdvanceController.php
php -l app\Http\Controllers\UserController.php
```

Esperado:

- `Timezone = America/Sao_Paulo`
- sem erro de sintaxe

### Frontend

```powershell
cmd /c npm run build
```

### Cache

```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

## Validacao funcional

Depois de aplicar na `main`, validar estes fluxos:

1. registrar uma venda
2. adicionar item em comanda
3. fechar caixa
4. baixar boleto
5. abrir telas de relatorio e conferir exibicao

Conferir:

1. valor salvo no banco
2. valor retornado em JSON
3. valor exibido no navegador

Todos devem refletir horario de Brasilia/Sao Paulo.

## Observacao importante

Como o banco e compartilhado com producao, a validacao final ideal deve ser feita com:

1. um registro novo criado apos a correcao
2. leitura imediata desse mesmo registro no banco
3. comparacao com a hora oficial de Sao Paulo no momento da operacao

Se houver divergencia depois disso, o proximo lugar para revisar e o fluxo especifico que ainda estiver gravando por fora desse padrao.

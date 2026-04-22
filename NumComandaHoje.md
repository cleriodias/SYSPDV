# Manual de Correcao da Coluna Comanda em Hoje

## Objetivo

Adicionar a coluna `Comanda` na opcao do menu:

1. `Hoje`
2. `Cupons do dia`

Regra esperada:

1. se o cupom for de comanda, mostrar o numero da comanda
2. se nao houver comanda, mostrar `--`

## Causa do problema

O backend da tela `Hoje` ja carregava a informacao de comanda dentro dos itens do cupom, mas a tabela principal da listagem nao recebia essa informacao no nivel do registro.

Ou seja:

1. a comanda existia em `receipt.items[].comanda`
2. a grade principal nao tinha a chave `record.comanda`
3. o frontend nao possuia a coluna `Comanda`

## Arquivos que devem ser ajustados na main

- [app/Http/Controllers/SalesReportController.php](c:\xampp\htdocs\pec-rodrigo\app\Http\Controllers\SalesReportController.php)
- [resources/js/Pages/Reports/Hoje.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Hoje.jsx)

## Alteracao no backend

### Arquivo

[SalesReportController.php](c:\xampp\htdocs\pec-rodrigo\app\Http\Controllers\SalesReportController.php)

### Metodo

`hoje(Request $request)`

### O que fazer

No `map()` que monta `records`, incluir a chave `comanda` no nivel principal do registro.

Trecho a incluir:

```php
'comanda' => $sales->pluck('id_comanda')->filter()->unique()->implode(', '),
```

### Posicao esperada

Fica junto de:

```php
'id' => $payment->tb4_id,
'date' => $saleDateTime?->format('d/m/Y'),
'time' => $saleDateTime?->format('H:i'),
'comanda' => $sales->pluck('id_comanda')->filter()->unique()->implode(', '),
'total' => round((float) $payment->valor_total, 2),
```

### Motivo tecnico

Isso sobe a informacao da comanda para o mesmo nivel da linha da tabela.

Com isso, o frontend nao precisa procurar a comanda item por item para exibir a coluna.

## Alteracao no frontend

### Arquivo

[Hoje.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Hoje.jsx)

### O que fazer na tabela

Adicionar uma nova coluna no `thead`:

```jsx
<th className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">
    Comanda
</th>
```

Adicionar a celula correspondente no `tbody`:

```jsx
<td className="px-3 py-2 text-gray-700 dark:text-gray-200">
    {record.comanda || '--'}
</td>
```

### Ordem sugerida das colunas

1. ID
2. Data
3. Hora
4. Comanda
5. Valor
6. Cupom

## Observacao adicional

Nesta branch, [Hoje.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Reports\Hoje.jsx) tambem foi ajustado para usar o formatador central:

```jsx
import { formatBrazilDateTime } from '@/Utils/date';
```

e:

```jsx
const formatDateTime = (value) => formatBrazilDateTime(value);
```

Se a `main` ainda nao tiver essa padronizacao de timezone no frontend, verificar antes se deseja levar tambem esse ajuste junto.

## Sequencia recomendada para aplicar na main

1. ajustar o backend em `SalesReportController.php`
2. ajustar a tabela em `Hoje.jsx`
3. validar sintaxe do PHP
4. rebuildar o frontend
5. testar a tela `Hoje > Cupons do dia`

## Comandos de validacao

### PHP

```powershell
php -l app\Http\Controllers\SalesReportController.php
```

### Frontend

```powershell
cmd /c npm run build
```

## Cenarios de teste

### Cupom com comanda

1. abrir `Hoje > Cupons do dia`
2. localizar um cupom originado de comanda
3. confirmar que a coluna `Comanda` mostra o numero da comanda

### Cupom sem comanda

1. abrir um cupom comum
2. confirmar que a coluna `Comanda` mostra `--`

## Resultado esperado

Depois da correcao aplicada na `main`:

1. a tela `Hoje > Cupons do dia` exibira a coluna `Comanda`
2. cupons originados de comanda mostrarao o numero correspondente
3. cupons comuns exibirao `--`

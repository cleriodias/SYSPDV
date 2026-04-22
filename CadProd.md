# Manual de Correcao de Cadastro de Produto para Main

## Objetivo

Ajustar o cadastro e a edicao de produto para que o tipo `Balança` siga esta regra:

1. produto de balanca nao usa codigo de barras
2. o usuario informa manualmente o `tb1_id`
3. se o `tb1_id` ja existir, o sistema nao insere
4. se o `tb1_id` ja existir, o sistema mostra os dados ja cadastrados
5. na edicao, produto de balanca nao usa codigo de barras
6. na edicao, o `tb1_id` nao pode ser alterado

## Causa do problema

O fluxo atual tratava todos os produtos da mesma forma:

1. sempre exigia `tb1_codbar`
2. nao permitia informar `tb1_id` manual no cadastro
3. o model nao aceitava `tb1_id` no `create()`
4. a tela de edicao tambem seguia exigindo codigo de barras

Isso conflita com a regra de produto de balanca.

## Regras de negocio que devem existir na main

### Cadastro

Quando `tb1_tipo = 1`:

1. `tb1_codbar` nao deve ser obrigatorio
2. `tb1_id` deve ser obrigatorio
3. se `tb1_id` ja existir em `tb1_produto`, bloquear a insercao
4. a mensagem deve trazer:
   - ID
   - nome
   - tipo
   - status
5. se o ID nao existir, inserir normalmente usando esse `tb1_id`

### Edicao

Quando `tb1_tipo = 1`:

1. o produto nao usa codigo de barras
2. o `tb1_id` deve ser exibido
3. o `tb1_id` nao pode ser alterado
4. o backend deve recusar tentativa de mudanca do `tb1_id`

### Produtos comuns

Quando `tb1_tipo != 1`:

1. `tb1_codbar` continua obrigatorio
2. `tb1_id` manual nao entra no payload de gravacao

## Arquivos que devem ser ajustados na main

- [app/Http/Controllers/ProductController.php](c:\xampp\htdocs\pec-rodrigo\app\Http\Controllers\ProductController.php)
- [app/Models/Produto.php](c:\xampp\htdocs\pec-rodrigo\app\Models\Produto.php)
- [resources/js/Pages/Products/ProductCreate.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Products\ProductCreate.jsx)
- [resources/js/Pages/Products/ProductEdit.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Products\ProductEdit.jsx)

## Alteracao no model

Em [Produto.php](c:\xampp\htdocs\pec-rodrigo\app\Models\Produto.php), incluir `tb1_id` em `fillable`.

Trecho esperado:

```php
protected $fillable = [
    'tb1_id',
    'tb1_nome',
    'tb1_vlr_custo',
    'tb1_vlr_venda',
    'tb1_codbar',
    'tb1_tipo',
    'tb1_status',
    'tb1_favorito',
    'tb1_vr_credit',
];
```

## Alteracoes no controller

### 1. Importar excecao de validacao

Em [ProductController.php](c:\xampp\htdocs\pec-rodrigo\app\Http\Controllers\ProductController.php), garantir:

```php
use Illuminate\Validation\ValidationException;
```

### 2. Ajustar store e update

Depois da validacao e da normalizacao de `tb1_vr_credit`, passar os dados por um preparador:

```php
$data = $this->prepareProductData($data);
```

e no update:

```php
$data = $this->prepareProductData($data, $product);
```

### 3. Reescrever validateProduct()

O metodo deve:

1. exigir `tb1_id` apenas no cadastro de balanca
2. exigir `tb1_codbar` apenas quando o tipo nao for balanca
3. bloquear mudanca de `tb1_id` na edicao
4. bloquear cadastro quando o `tb1_id` ja existir
5. devolver mensagem com dados do produto existente

Estrutura esperada da validacao:

```php
'tb1_id' => [
    Rule::requiredIf(fn () => (int) $request->input('tb1_tipo') === 1 && $product === null),
    'nullable',
    'integer',
    'min:1',
],
'tb1_codbar' => [
    Rule::requiredIf(fn () => (int) $request->input('tb1_tipo') !== 1),
    'nullable',
    'string',
    'max:64',
    Rule::unique('tb1_produto', 'tb1_codbar')->ignore($product?->tb1_id, 'tb1_id'),
],
```

Regra de protecao na edicao:

```php
if ($product && $requestedId !== (int) $product->tb1_id) {
    throw ValidationException::withMessages([
        'tb1_id' => 'Nao e permitido alterar o ID de um produto ja cadastrado.',
    ]);
}
```

Regra de bloqueio no cadastro:

```php
$existingProduct = Produto::query()->find($requestedId);
```

Se existir, retornar mensagem no formato:

```php
'O ID %d ja esta cadastrado. Nome: %s | Tipo: %s | Status: %s.'
```

### 4. Criar prepareProductData()

Esse metodo deve:

1. se for balanca:
   - gravar `tb1_codbar` como string vazia
2. se nao for balanca:
   - remover `tb1_id` manual
   - normalizar `tb1_codbar`
3. no update:
   - remover sempre `tb1_id` do payload final

Estrutura esperada:

```php
private function prepareProductData(array $data, ?Produto $product = null): array
{
    $type = (int) ($data['tb1_tipo'] ?? $product?->tb1_tipo ?? 0);

    if ($type === 1) {
        $data['tb1_codbar'] = '';
    } else {
        unset($data['tb1_id']);
        $data['tb1_codbar'] = trim((string) ($data['tb1_codbar'] ?? ''));
    }

    if ($product) {
        unset($data['tb1_id']);
    }

    return $data;
}
```

### 5. Criar helper de mensagem do produto existente

Criar um metodo privado para centralizar a mensagem:

```php
private function existingProductMessage(Produto $product): string
```

## Alteracoes no formulario de cadastro

Em [ProductCreate.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Products\ProductCreate.jsx):

### 1. Adicionar `tb1_id` ao form

```js
tb1_id: "",
```

### 2. Criar flag

```js
const isBalanceProduct = Number(data.tb1_tipo) === 1;
```

### 3. Trocar o campo fixo de codigo de barras por condicional

Se for balanca:

1. mostrar campo `tb1_id`
2. esconder `tb1_codbar`
3. mostrar texto de ajuda explicando que:
   - nao usa codigo de barras
   - usa `tb1_id`
   - se o ID existir, o cadastro sera bloqueado

Se nao for balanca:

1. continuar mostrando `tb1_codbar`

## Alteracoes no formulario de edicao

Em [ProductEdit.jsx](c:\xampp\htdocs\pec-rodrigo\resources\js\Pages\Products\ProductEdit.jsx):

### 1. Criar flag

```js
const isBalanceProduct = Number(data.tb1_tipo) === 1;
```

### 2. Campo condicional

Se for balanca:

1. mostrar `tb1_id`
2. `readOnly`
3. informar que o ID nao pode ser alterado
4. nao mostrar `tb1_codbar`

Se nao for balanca:

1. continuar mostrando `tb1_codbar`

## Observacao importante

O `tb1_id` e chave primaria e ja participa de relacoes com:

1. vendas
2. descartes
3. relatorios

Por isso a edicao nao deve permitir troca livre do ID.

## Sequencia recomendada para aplicar na main

1. ajustar `Produto.php`
2. ajustar `ProductController.php`
3. ajustar `ProductCreate.jsx`
4. ajustar `ProductEdit.jsx`
5. validar PHP
6. rebuildar frontend
7. testar fluxo funcional

## Comandos de validacao

### PHP

```powershell
php -l app\Http\Controllers\ProductController.php
php -l app\Models\Produto.php
```

### Frontend

```powershell
cmd /c npm run build
```

## Cenarios que devem ser testados

### Cadastro de balanca

1. cadastrar produto de balanca com `tb1_id` novo
2. confirmar que gravou
3. confirmar que `tb1_codbar` ficou vazio

### Bloqueio de duplicidade

1. tentar cadastrar outro produto de balanca com mesmo `tb1_id`
2. confirmar que o sistema nao insere
3. confirmar que a mensagem mostra os dados ja existentes

### Edicao de balanca

1. abrir produto de balanca
2. confirmar que aparece `tb1_id`
3. confirmar que o campo esta bloqueado
4. confirmar que nao aparece campo de codigo de barras

### Produto comum

1. cadastrar produto normal
2. confirmar que continua exigindo codigo de barras
3. editar produto normal
4. confirmar que continua exibindo codigo de barras

## Resultado esperado

Depois da correcao aplicada na `main`:

1. produto de balanca usa `tb1_id` informado manualmente
2. nao exige codigo de barras
3. nao duplica cadastro por ID
4. informa ao usuario quando o ID ja existe
5. edicao de balanca respeita a mesma regra sem permitir troca de chave primaria

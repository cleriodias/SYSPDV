# Deploy Azure do PDV

Este projeto deve ser publicado como um ambiente novo, separado do `PEC83`.

## 1. Criar recursos novos

- Novo repositório GitHub
- Novo `Resource Group` na Azure
- Novo `App Service` Linux com `PHP 8.2`
- Novo banco e novas configurações de aplicação

Nada aqui deve reaproveitar o nome do `PEC83`.

## 2. Variáveis do repositório GitHub

Em `GitHub -> Settings -> Secrets and variables -> Actions -> Variables`, criar:

- `AZURE_RESOURCE_GROUP`
- `AZURE_WEBAPP_NAME`
- `AZURE_APP_SERVICE_PLAN_ID`
- `AZURE_LOCATION`

Exemplo:

- `AZURE_RESOURCE_GROUP=rg-pdv-padaria`
- `AZURE_WEBAPP_NAME=pdv-padaria-de-verdade`
- `AZURE_APP_SERVICE_PLAN_ID=/subscriptions/.../resourceGroups/rg-pdv-padaria/providers/Microsoft.Web/serverfarms/asp-pdv-padaria`
- `AZURE_LOCATION=Brazil South`

## 3. Secrets do repositório GitHub

Em `GitHub -> Settings -> Secrets and variables -> Actions -> Secrets`, criar:

- `AZURE_CLIENT_ID`
- `AZURE_TENANT_ID`
- `AZURE_SUBSCRIPTION_ID`
- `LARAVEL_APP_KEY`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## 4. Fluxo de publicação

1. Inicializar o Git local.
2. Conectar ao novo repositório remoto.
3. Subir a branch `main`.
4. Executar o workflow `Deploy to Azure Web App`.

## 5. Comandos locais

```powershell
git init -b main
git remote add origin https://github.com/SEU_USUARIO/SEU_REPOSITORIO.git
git add .
git commit -m "Estrutura inicial do PDV para deploy Azure"
git push -u origin main
```

## 6. Observações

- O workflow usa `/.github/workflows/deploy.yml`.
- O provisionamento da App Service usa `infra/main.bicep`.
- O startup do Laravel/Nginx usa `start.sh`.
- Arquivos sensíveis locais como `.env`, `.sql`, `.pfx` e `bkp/` não devem ir para o Git.

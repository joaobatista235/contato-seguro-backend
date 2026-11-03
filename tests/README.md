# Como ler os testes

Comece pelos métodos que começam com `test` em `Integration/Controller`:

- `CategoryControllerTest.php`: cadastro, edição, exclusão, consultas e validação dos dados de categorias.
- `CategoryTranslationsTest.php`: cadastro de traduções, duplicidades, rollback do lote, idioma nas consultas e fallback para inglês.
- `ProductControllerTest.php`: consultas e gravações de produtos, categorias, isolamento por empresa e transações.
- `ProductFiltersTest.php`: filtros por ativo/inativo e categoria, ordenação por data, combinações e parâmetros inválidos.
- `ProductStockTest.php`: estoque inicial opcional, atualização, preservação quando omitido, filtro mínimo e validações.
- `CompanyControllerTest.php`: consultas de empresas e isolamento de acesso.
- `CommentControllerTest.php`: comentários, respostas em vários níveis, remoção pelo autor, curtidas, permissões e integração com a exclusão de produtos.
- `ReportControllerTest.php`: logs do mais recente para o mais antigo, formatação e proteção do HTML.
- `AdminPermissionTest.php`: cabeçalhos e permissões de acesso de todas as 19 rotas da API.
- `ErrorHandlingTest.php`: respostas JSON para rotas inexistentes e métodos HTTP não permitidos.

Os testes de integração exercitam as rotas e verificam o que o consumidor da API
recebe: status, cabeçalhos, campos retornados e efeitos das operações. Não chamam
services ou repositories diretamente, nem exigem uma classe interna específica.
As verificações internas da migration foram retiradas desta suíte; a regressão
de identidade e histórico de produtos é verificada por cadastro, exclusão,
recadastro e consulta do relatório pela API.

Cada método prepara seu cenário, executa uma operação e confere o resultado.
Por exemplo:

```php
$response = $this->request('DELETE', '/categories/2');

$this->assertSame(200, $response->getStatusCode());
```

## Arquivos de apoio

- A migration `CreateMysqlSchema` define a estrutura real usada também pelos testes.
- `Fixtures/categories.sql`, `products.sql`, `product_filters.sql`, `product_stock.sql`, `comments.sql` e `report.sql` contêm os dados de cada funcionalidade.
- `Support/IntegrationTestCase.php` usa a conexão MySQL exclusiva de testes, limpa os dados de cada cenário e carrega os middlewares e as rotas reais.
- `bootstrap.php` carrega o Composer e a classe de apoio.

O `setUp()` de cada classe carrega seus dados. Os cenários são independentes: as
gravações de um teste não passam para o próximo, e a conexão anterior é restaurada
ao terminar. O banco `backend_test` usa um usuário sem acesso ao banco da aplicação. Os arquivos SQL de testes não são migrations da aplicação. A classe
de apoio cria a estrutura MySQL pela migration real uma vez por execução e limpa
as tabelas e triggers antes de carregar os dados de cada cenário.

## Requisições e verificações

`request()` recebe método HTTP, caminho, corpo opcional e administrador opcional.
O administrador padrão dos cenários é `7`.

```php
$this->request('GET', '/categories');
$this->request('POST', '/categories', ['title' => 'Books', 'active' => true]);
$this->request('GET', '/categories', adminUserId: '9');
$this->request('GET', '/categories', adminUserId: null);
```

`adminUserId:` é um argumento nomeado do PHP: permite escolher o administrador
sem preencher os parâmetros anteriores. `null` simula a ausência do cabeçalho.
O corpo é enviado como JSON e processado pelo Slim. As requisições são executadas
na aplicação dentro do processo de teste, sem abrir uma conexão HTTP externa.

`responseData()` lê o JSON da resposta como array. `assertSame()` confere valor e
tipo. Para requisições inválidas e operações que falham, os testes comparam as
consultas antes e depois da operação para garantir que os dados não mudaram.
O HTML do relatório é conferido como resposta da rota; a coluna de logs vazia é
identificada com DOM/XPath para não depender dos estilos ou da indentação do HTML.

Os `dataProvider`s agrupam pequenas variações do mesmo cenário, como os valores
permitidos para `active`. Cada conjunto aparece com um nome na saída do PHPUnit.

Os testes de operações que falham criam um trigger apenas no banco MySQL de testes
para simular um problema de gravação. Depois conferem a resposta de erro e
consultam novamente as rotas para verificar se os produtos, categorias e relatório
permaneceram iguais. O SQL é usado para preparar a falha, sem testar métodos
internos nem o estado da transação. O banco de desenvolvimento não recebe esse
trigger.

## Executar

```sh
docker compose exec -e XDEBUG_MODE=off app composer test
docker compose exec -e XDEBUG_MODE=off app php vendor/bin/phpunit --filter CategoryControllerTest
```

Os três testes originais do model `Product`, fornecidos pelo case, foram
preservados em `Unit/Model`.

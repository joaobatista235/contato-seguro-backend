# Como ler os testes

Comece pelos métodos que começam com `test` em `Integration/Controller`:

- `CategoryControllerTest.php`: cadastro, edição, exclusão, consultas e permissões de categorias.
- `ProductControllerTest.php`: categorias dos produtos e agrupamento da listagem.
- `ReportControllerTest.php`: formatação dos logs e proteção do HTML.

Cada método prepara seu cenário, executa uma operação e confere o resultado.
Por exemplo:

```php
$response = $this->request('DELETE', '/categories/2');

$this->assertSame(200, $response->getStatusCode());
```

## Arquivos de apoio

- `Fixtures/schema.sql` define uma única estrutura de banco para os testes.
- `Fixtures/categories.sql`, `products.sql` e `report.sql` contêm os dados de cada funcionalidade.
- `Support/IntegrationTestCase.php` cria um SQLite em memória para cada teste, carrega a estrutura e prepara as rotas com o processamento de JSON e o middleware de permissão das categorias.
- `bootstrap.php` carrega o Composer e a classe de apoio.

O `setUp()` de cada classe carrega seus dados. Os cenários são independentes: as
gravações de um teste não passam para o próximo, e a conexão anterior é restaurada
ao terminar. Os arquivos SQL de testes não são migrations da aplicação.

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
tipo; `assertContainsOnlyInstancesOf()` confere o tipo dos objetos retornados.

Os `dataProvider`s agrupam pequenas variações do mesmo cenário, como os valores
permitidos para `active`. Cada conjunto aparece com um nome na saída do PHPUnit.

## Executar

```sh
docker compose exec -e XDEBUG_MODE=off app composer test
docker compose exec -e XDEBUG_MODE=off app php vendor/bin/phpunit --filter CategoryControllerTest
```

Os testes isolados do model `Product` continuam em `Unit/Model`.

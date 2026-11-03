# DESAFIO BACKEND

## Sobre o teste
O objetivo do teste é avaliar suas habilidades como programador **backend PHP**.

Você receberá uma aplicação que necessita de ajustes descritos pelo cliente e correções de bugs apontadas, deve resolvê-los com a maior qualidade e organização de código possível. Serão avaliados: domínio da linguagem, resolução de problemas, performance, segurança e organização.

Existe uma seção ao fim do _README_ chamada **"Suas Respostas, Dúvidas e Observações"** reservada para documentação do processo de desenvolvimento, mudanças na API e lógica também devem ser documentadas.

## Configuração do Ambiente

### Requisitos
- Git;
- Docker com Docker Compose;
- Porta `8000` disponível.

PHP 8.3, Composer e a extensão PDO MySQL são fornecidos pelo container da aplicação. O MySQL 8.4 roda em um serviço separado.

### Instalação

Após clonar o repositório, execute na raiz do projeto:

```sh
docker compose up -d --build
```

A API fica disponível em [http://localhost:8000](http://localhost:8000). As dependências, o banco inicial e as migrations são preparados automaticamente quando o container inicia. Os comandos de testes e manutenção estão em **Suas Respostas, Dúvidas e Observações**.

## Sobre a entrega
>[!CAUTION]
> A entrega deve ser realizada em um repositório **_PRIVADO_** do **GitHub**;
> 
> Você deve adicionar os usuários [`pedrosobucki`](https://github.com/pedrosobucki) e [`aloefflerj`](https://github.com/aloefflerj) como colaboradores do repositório com permissão de leitura para que seu teste possa ser avaliado.

- A primeira etapa é realizar um commit inicial com o código sem nenhuma modificação;
- As modificações devem estar separadas por commits coerentes com as funcionalidades e mudanças realizadas ao longo do processo, não um único commit com todas as modificaçẽs;
- As soluções elaboradas e implementadas por você devem ser apresentadas na seção **Suas Respostas, Dúvidas e Observações** ao fim do _README_.

## Sobre o Projeto
- O cliente XPTO Ltda. contratou seu serviço para realizar alguns ajustes em seu sistema de cadastro de produtos;
- O sistema permite o cadastro, edição e remoção de _produtos_ e _categorias de produtos_ para uma _empresa_;
- Para que sejam possíveis os cadastros, alterações e remoções é necessário um usuário administrador;
- O sistema possui categorias padrão que pertencem a todas as empresas, bem como categorias personalizadas dedicadas a uma dada empresa. As categorias padrão são: (`clothing`, `phone`, `computer` e `house`) e **devem** aparecer para todas as _empresas_;
- O sistema tem um relatório de dados dedicado ao cliente.

## Sobre a API
As rotas estão divididas em:
  - Consultas de _empresas_;
  - _CRUD_ de _categorias_;
  - Cadastro de _traduções_ de categorias;
  - _CRUD_ de _produtos_;
  - _Comentários_, respostas e curtidas de produtos;
  - Rota de busca de um _relatório_ que retorna um _html_.

Deve ser utilizado o [Postman](https://www.postman.com/) para desenvolvimento e documentação, o arquivo para importação das rotas se encontra em `docs/postman-api.json`.

> [!WARNING]
> É importante que se adicione o _header_ `admin_user_id` com o id do usuário desejado ao acessar as rotas para simular o uso de um usuário no sistema.

A coleção atualizada da API está em `docs/postman-api.json`; `docs/api-docs.pdf` contém a documentação inicial do case.
  - A documentação assume que a url base é `localhost:8000` mas você pode usar qualquer outra url ao configurar o servidor;
  - O _header_ `admin_user_id` na documentação está indicado com valor `1` mas pode ser usado o id de qualquer outro usuário caso deseje (_pesquisando no banco de dados é possível ver os outros id's de usuários_).

## Sobre o Banco de Dados
- A aplicação usa MySQL 8.4, com os dados originais do case carregados por migration;
- `db/db-backup.sqlite3` preserva o backup SQLite original apenas como referência. A carga MySQL usa a exportação versionada `db/data/initial_data.json`.

### Migrations
- Funcionalidades que exijam modificações no banco de dados (seja nos dados ou estrutura) **devem estar contidas em _migrations_**, não enviadas diretamente com o banco;
- **Bancos e volumes locais não serão utilizados para avaliação** do teste, por isso é importante persistir a estrutura e os dados iniciais em migrations;
- A biblioteca utilizada para migrations foi o [**_Phinx_**](https://book.cakephp.org/phinx/0/en/index.html);
- As migrations criadas **devem poder ser revertidas** (método `down()`);
Comandos do Composer, executados dentro do container:

- Criar nova migration: `composer create-migration NomeDaMigration`;
- Rodar migrations: `composer migrate`;
- Reverter migration: `composer rollback-migration`.

# Demandas
Abaixo, as solicitações do cliente:

## Alterações
Modificações requisitadas pelo cliente em funcionalidades já existentes

### Categorias
- [x] A categoria está vindo errada na listagem de produtos para alguns casos (_exemplo: produto `blue trouser` está vindo na categoria `phone`_);
- [x] Alguns produtos estão vindo com a categoria `null` ao serem pesquisados individualmente (_exemplo: produto `iphone 8`_);
- [x] Cadastrei o produto `king size bed` em mais de uma categoria, mas ele aparece **apenas** na categoria `furniture` na busca individual do produto.

### Filtros e Ordenamento
Para a listagem de produtos:
- [x] Gostaria de poder filtrar os produtos ativos ou inativos;
- [x] Gostaria de poder filtrar os produtos por categoria;
- [x] Gostaria de poder ordenar os produtos por data de cadastro.

### Relatório
- [x] O relatório não está mostrando a coluna de logs corretamente, se possível, gostaria de trazer no seguinte formato:
  (Nome do usuário, Tipo de alteração e Data),
  (Nome do usuário, Tipo de alteração e Data),
  (Nome do usuário, Tipo de alteração e Data)
  Exemplo:
  (John Doe, Criação, 01/12/2023 12:50:30),
  (Jane Doe, Atualização, 11/12/2023 13:51:40),
  (Joe Doe, Remoção, 21/12/2023 14:52:50)

### Logs
- [ ] Gostaria de saber qual usuário mudou o preço do produto `iphone 8` por último.

Investigação realizada; a alteração específica do preço não pode ser comprovada pelos logs disponíveis. Veja a seção **6. Investigação do iphone 8**.

### Correção de bug
- [x] Ao rodar os teste unitários com `composer test` são apontados erros. Eles precisam ser resolvidos, com documentação sobre a causa e a solução.

## Features
Novas funcionalidades requisitadas pelo cliente

> [!WARNING]
> Preste atenção, funcionalidades que exijam mudanças no banco de dados devem conter tais modificações em uma ou mais **migrations**.

### Traduções
- [x] Quero disponibilizar meu sistema para fora do país, crie uma funcionalidade de cadastro de traduções para as categorias que segue o seguinte contrato:
```
POST "$base_url/categories/:id"

{
  "translations": [
    {
      "lang_code": "en",
      "label": "home"
    },
    {
      "lang_code": "pt",
      "label": "casa"
    }
  ]
}
```
- [x] Não deve ser possível cadastrar traduções repetidas, se uma única tradução repetida foi enviada, nenhuma deve persistir;
- [x] Ao buscar por produtos/categorias, o parâmetro adicional opcional "_`lang`_" pode ser passado para determinar a linguagem em que a categoria deve ser retornada;
- [x] Caso não haja categoria correspondente ou não seja especificado por parâmetro, retornar em inglês;
- [x] Inclua a rota e as modificações na coleção do Postman no repositório.

### Estoque
Além das informações já disponíveis do produto, desejo acrescentar também uma contagem de estoque para cada, a qual deve seguir algumas regras:
- [x] Posso cadastrar a quantidade do estoque assim que cadastro um produto, mas se não for informado assumo que o estoque é _0_;
- [x] Posso atualizar o estoque de um produto;
- [x] Ao buscar um produto, posso filtrar por uma quantidade mínima em estoque.

### Comentários
Quero que os usuários do sistema possam discutir sobre os produtos em uma área de comentários.

Para isso, novas rotas devem ser criadas:
- [x] Criar um novo comentário no produto
- [x] Responder um comentário já realizado (todo comentário pode ser diretamente respondido)
- [x] Remover um comentário feito por mim
- [x] Curtir um comentário
- [x] Listar todos os comentários de um produto em um objeto com hierarquia de comentários

##
**Seu trabalho é atender às demandas solicitadas pelo cliente.**

Caso julgue necessário, podem ser adicionadas ou modificadas as rotas da api. Caso altere, por favor, explique o porquê e indique as alterações nesse `README`.

Sinta-se a vontade para refatorar o que achar pertinente, considerando questões como arquitetura, padrões de código, padrões restful, _segurança_ e quaisquer outras boas práticas. Levaremos em conta essas mudanças.

## Docker
Você deve servir a aplicação por meio de um ambiente docker

Para efetuar a criação do ambiente docker, partimos de algumas premissas:
- O ambiente tem o **objetivo de ser utilizado por outros devs**, então deve funcionar sem necessidade de alteração de arquivos para inserção de dados específicos de máquina (quando baixo o repositório quero subir o container sem que fazer edições no ambiente docker);
- Levando o ponto anterior em conta, é inteligente não deixar a criação do container para o final;
- A funcionalidade de **PHP** deve rodar na porta **8000** do host.

### Itens obrigatórios
- [x] Criar um ambiente docker que sobe a aplicação **PHP** na porta **8000**;
- [x] Possibilitar que as **_migrations_ possam ser executadas/criadas por docker** (especificar comando);
- [x] Possibilitar que os **_testes unitários_ sejam executados por docker** (especificar comando).

### Desafios
- [x] Substituir o banco serverless **SQLite** por um banco como **MySQL**/**PostgreSQL**/outro e servir por container;
- [ ] Escrever **novos testes unitários** para funcionalidades faltantes;
- [x] Implementar um **Linter** e disponibilizar por docker (especificar comando);
- [x] Implementar **análise estática** e disponibilizar por docker (especificar comando);
- [x] Escrever um script "_`check_deploy.sh`_" que faz todas as validações implementadas como uma pipeline e determina se o código está pronto para produção.

As novas funcionalidades são cobertas por testes de integração das rotas. Os três testes unitários originais foram preservados; o desafio opcional de adicionar novos testes unitários permanece desmarcado.

## Suas Respostas, Dúvidas e Observações

### 1. Ambiente e comandos

Requisito: Docker com Compose.

```sh
docker compose up -d --build
```

Aplicação: [http://localhost:8000](http://localhost:8000). O Compose inicia o MySQL, aguarda o healthcheck, instala as dependências e aplica as migrations antes de iniciar a API. Os dados ficam no volume Docker `mysql`; `docker compose down` preserva esse volume.

| Ação | Comando |
|---|---|
| Executar os testes sem interromper no debugger | `docker compose exec -e XDEBUG_MODE=off app composer test` |
| Verificar o padrão do código | `docker compose exec -e XDEBUG_MODE=off app composer lint` |
| Corrigir automaticamente a formatação possível | `docker compose exec -e XDEBUG_MODE=off app composer lint:fix` |
| Executar a análise estática | `docker compose exec -e XDEBUG_MODE=off app composer analyse` |
| Executar todas as validações | `docker compose exec app composer check` |
| Aplicar migrations | `docker compose exec app composer migrate` |
| Criar uma migration | `docker compose exec app composer create-migration NomeDaMigration` |
| Reverter a última migration | `docker compose exec app composer rollback-migration` |
| Abrir o cliente MySQL | `docker compose exec db mysql -u backend -p backend` |
| Encerrar o ambiente | `docker compose down` |

O Xdebug está configurado para o VS Code, sem parâmetro na URL. Instale a extensão **PHP Debug**, selecione **Escutar Xdebug (Docker)** e pressione `F5`.

A instalação do zero usa duas migrations MySQL: criação da estrutura e carga dos dados originais. A suíte mantém **192 testes, com 808 verificações**, e executa as rotas contra o banco exclusivo `backend_test`, sem alterar o banco de desenvolvimento `backend`. A senha local do cliente MySQL é `backend_dev`.

#### Qualidade do código

- [PHP_CodeSniffer](https://github.com/PHPCSStandards/PHP_CodeSniffer) verifica o padrão PSR-12 em `src`, `public`, `tests` e `db/migrations`. As classes globais das migrations têm exceção apenas para a exigência de namespace do padrão.
- [PHPStan](https://phpstan.org/user-guide/getting-started) analisa essas mesmas pastas no nível 5, sem baseline ou lista de erros ignorados. O cache fica em `.cache/phpstan`, fora do Git.
- `check_deploy.sh` valida Composer e lockfile, sintaxe PHP, linter, análise estática e testes, nessa ordem. Desativa o Xdebug e retorna erro assim que uma etapa falha. Pode ser executado com `composer check` ou `docker compose exec app sh check_deploy.sh`.

O script verifica a qualidade do código sem aplicar migrations ou realizar deploy. A auditoria separada `docker compose exec -e XDEBUG_MODE=off app composer audit` apontou oito alertas em quatro dependências já existentes (`cakephp/database`, `phpunit/phpunit`, `slim/slim` e `symfony/yaml`); a atualização dessas bibliotecas permanece pendente para produção.

#### Postman

Importe `docs/postman-api.json`. As 19 rotas usam variáveis da coleção; `base_url` já inclui o protocolo (`http://localhost:8000`), e `admin_user_id` identifica o administrador simulado.

Os valores iniciais apontam para a empresa `1`, produto `1` e categoria personalizada `4` do backup original. Ajuste `company_id`, `product_id` e `category_id` conforme o cenário. Após cadastrar um comentário, copie o ID retornado para `comment_id` antes de responder, curtir ou remover. Para produtos e categorias recém-criados, consulte a listagem para obter o ID. As operações de atualização e exclusão alteram os registros indicados nessas variáveis.

### 2. Correções realizadas

| Problema | Solução |
|---|---|
| Categoria incorreta na listagem | Corrigido o relacionamento para `c.id = pc.cat_id`. |
| Categorias padrão ausentes | Incluídas as categorias com `company_id IS NULL`. |
| Produto retornava uma única categoria | Todas as categorias disponíveis são retornadas em uma lista, com um registro por produto. |
| Preço perdia os centavos | A conversão de `price` em `hydrateByFetch()` passou de `int` para `float`. |
| Logs apareciam como array no relatório | Formatação com nome, ação e data, do mais recente para o mais antigo. |
| Categoria excluída deixava vínculos antigos | Exclusão da categoria e de suas associações na mesma transação. |
| Produto novo herdava histórico antigo | Migration impede a reutilização de IDs de produtos. |
| Erros retornavam HTML com status 200 | Tratamento central de exceções com status HTTP e corpo JSON. |

O relatório usa o formato `(Nome, Criação/Atualização/Remoção, dd/mm/aaaa HH:mm:ss)`, com logs em ordem decrescente de data e ID. Usuários ausentes aparecem como `Usuário #ID`; produtos sem logs têm a coluna vazia. Os textos são escapados no HTML, e as datas mantêm o horário armazenado no banco.

### 3. API e validações

- Todas as rotas exigem o header `admin_user_id` com um inteiro positivo. O administrador deve existir e pertencer a uma empresa.
- Produtos, empresas e categorias personalizadas são restritos à empresa do administrador. Categorias padrão são compartilhadas e não podem ser editadas ou removidas por essas rotas.
- `GET /products` e `GET /products/{id}` retornam `category` como lista: `["furniture", "house"]` ou `[]`.
- A listagem de produtos mantém `company_id` e `created_at`; a consulta individual mantém `companyId` e `createdAt`.
- `GET /categories/{id}` mantém a resposta em lista. Sem resultado, retorna `[]` com status 200.
- Cadastro e atualização de categorias exigem `title` e `active`.
- Cadastro e atualização de produtos exigem `company_id`, `title`, `price`, `active` e `category_id`. A empresa deve ser a do administrador e a categoria deve estar ativa e disponível para ele.
- Títulos são textos não vazios, salvos sem espaços nas extremidades. O preço deve ser finito e não negativo; `active` aceita booleano ou inteiro `0`/`1`; IDs devem ser inteiros positivos.
- A atualização de produto recebe um único `category_id` e substitui os vínculos anteriores por essa categoria.

#### Filtros e ordenação de produtos

Parâmetros opcionais de `GET /products`:

| Parâmetro | Valores | Comportamento |
|---|---|---|
| `active` | `1`, `0`, `true`, `false` | Filtra ativos ou inativos. Sem o parâmetro, retorna ambos. |
| `category_id` | Inteiro positivo | Filtra pela categoria, mantendo todas as categorias disponíveis no produto retornado. |
| `min_stock` | Inteiro maior ou igual a zero | Retorna produtos com estoque igual ou superior ao mínimo. |
| `order` | `asc`, `desc` | Ordena por `created_at`, do mais antigo ou mais recente. Aceita maiúsculas e minúsculas. |

```http
GET /products?active=0
GET /products?category_id=2
GET /products?active=1&category_id=2&order=desc
GET /products?min_stock=5&active=1&order=desc
```

Os parâmetros podem ser combinados. Sem `order`, mantém a ordem por ID; datas iguais usam o ID crescente como desempate. Categorias inativas, inexistentes ou de outra empresa não retornam produtos. Sem correspondências, a resposta é `[]` com status 200; parâmetros inválidos retornam 400. A ordenação usa opções fixas permitidas, e os valores dos filtros são vinculados com `bindValue()`.

#### Estoque

`stock` é um inteiro não negativo, retornado na listagem e na consulta individual. No `POST /products`, é opcional e assume `0`. No `PUT /products/{id}`, o valor informado substitui o estoque atual; omitir o campo preserva a quantidade. Os demais campos obrigatórios do produto continuam exigidos. Valores inválidos retornam 400.

Exemplo de corpo para cadastro ou atualização:

```json
{
  "company_id": 1,
  "title": "Produto",
  "price": 123.4,
  "active": true,
  "category_id": 1,
  "stock": 10
}
```

#### Traduções de categorias

`POST /categories/{id}` cadastra traduções de uma categoria ativa padrão ou da empresa do administrador:

```json
{
  "translations": [
    {"lang_code": "en", "label": "home"},
    {"lang_code": "pt", "label": "casa"}
  ]
}
```

- Sucesso retorna 200. Idioma repetido no lote ou já cadastrado na categoria retorna 400, sem salvar nenhum item do lote.
- `translations` deve ser uma lista não vazia; cada item exige `lang_code` e `label` não vazio. Códigos como `pt`, `en` e `pt-BR` são normalizados para minúsculas; `pt` e `PT` representam o mesmo idioma.
- Categorias inexistentes, inativas ou de outra empresa retornam 404. Traduções de categorias padrão são compartilhadas entre empresas.
- `GET /categories`, `GET /categories/{id}`, `GET /products` e `GET /products/{id}` aceitam `?lang=pt`. IDs e formato das respostas permanecem iguais; apenas o título das categorias muda.
- Sem `lang` ou sem tradução nesse idioma, retorna a tradução `en`. Se ela não existir, usa o título original da categoria, considerado o texto em inglês. Cada categoria aplica esse fallback individualmente. Um `lang` inválido retorna 400.
- `lang` pode ser combinado com os filtros e a ordenação de produtos. O relatório continua usando inglês.

#### Comentários de produtos

Todas as rotas usam `admin_user_id` e respeitam a empresa do produto:

| Método | Rota | Ação |
|---|---|---|
| POST | `/products/{id}/comments` | Cria um comentário. |
| POST | `/products/{id}/comments/{comment_id}/replies` | Responde um comentário ou uma resposta. |
| GET | `/products/{id}/comments` | Lista a discussão em hierarquia. |
| DELETE | `/products/{id}/comments/{comment_id}` | Remove o comentário do próprio autor. |
| POST | `/products/{id}/comments/{comment_id}/likes` | Curte o comentário. |

Cadastro e resposta recebem `{"content": "Texto do comentário"}` e retornam 201 com `{"id": 1}`. O texto deve ser uma string não vazia e é salvo sem espaços nas extremidades. O autor vem do header; o produto e o comentário respondido vêm da rota.

A listagem retorna `{"product_id": 1, "comments": [...]}`. Cada comentário contém `id`, `parent_id`, `admin_user_id`, `user_name`, `content`, `created_at`, `deleted`, `likes` e `replies`. As respostas ficam dentro de `replies`, inclusive em níveis seguintes. A ordem é por data de criação e ID; produtos sem comentários retornam `comments: []`.

- A remoção retorna 200. Apenas o autor pode remover; outro autor da mesma empresa recebe 403.
- Para preservar respostas de outros autores, o comentário removido permanece como marcador com `deleted: true`, `content: null` e `likes: 0`. Não aceita novas respostas ou curtidas.
- Curtidas retornam 200 com `{"liked": true}`. Cada administrador conta uma vez por comentário; repetir a requisição mantém a contagem. Também é possível curtir respostas e comentários próprios.
- Produto indisponível, comentário inexistente, removido ou pertencente a outro produto retorna 404. IDs e conteúdo inválidos retornam 400.
- Excluir um produto remove seus comentários e curtidas na mesma transação da exclusão.

| HTTP | Uso |
|---|---|
| 200 | Operação concluída. |
| 201 | Comentário ou resposta criado; o JSON informa o ID. |
| 400 | Cabeçalho, ID ou corpo inválido. |
| 403 | Administrador inexistente, sem empresa ou tentando remover comentário de outro autor. |
| 404 | Rota inexistente ou recurso indisponível ao administrador. |
| 405 | Método HTTP não permitido; o header `Allow` informa os métodos aceitos. |
| 500 | Falha interna, com a mensagem genérica `Internal server error`. |

Erros usam `{"error": "mensagem"}`. Detalhes internos ficam nos logs do servidor. O relatório retorna HTML; as demais respostas com conteúdo retornam JSON.

### 4. Arquitetura e padrões

```text
Controller → Service → Repository
```

- **Controller:** entrada da requisição e resposta HTTP.
- **Service:** validações, regras e coordenação das operações.
- **Repository:** SQL em `$query`, parâmetros com `bindValue()` e tipos PDO explícitos.
- **Models:** registros da tabela retornados com `FETCH_CLASS`. Consultas com JOIN retornam arrays associativos.
- **AdminPermissionMiddleware:** valida o administrador e disponibiliza seu ID como atributo da requisição.
- **ReportService:** compõe os dados e gera o HTML com heredoc e escape dos valores.
- **Utilitários:** `IntegerValidator` para IDs e `JsonResponse` para respostas JSON.

As dependências são criadas nos construtores. O código usa `strict_types`, parâmetros e retornos tipados e PHPDocs somente com tipos. Gravações de produtos, vínculos e logs são executadas em transação.

### 5. Banco e migrations

O MySQL 8.4 usa InnoDB, `utf8mb4` e horário UTC. `phinx.php` e `DB.php` leem as variáveis `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`. O Compose fornece valores locais de desenvolvimento, sem edição de arquivos. As credenciais são exemplos para desenvolvimento; o banco não publica uma porta no host.

| Migration | Responsabilidade |
|---|---|
| [CreateMysqlSchema](db/migrations/20261103030000_create_mysql_schema.php) | Cria tabelas, índices e relacionamentos de todas as funcionalidades. O `down()` remove essa estrutura. |
| [ImportInitialData](db/migrations/20261103040000_import_initial_data.php) | Importa `db/data/initial_data.json`, preservando IDs, preços e datas. Reserva também os IDs de produtos encontrados nos logs. O `down()` remove os registros importados. |

Os logs preservam referências históricas a produtos e administradores ausentes, sem chaves estrangeiras que impeçam esses registros ou a exclusão de produtos. Nas demais relações, as chaves estrangeiras estão ativas. Preços usam `DOUBLE` para manter o contrato numérico existente, sem introduzir arredondamento obrigatório para duas casas.

As migrations SQLite anteriores estão em `db/legacy/migrations`, fora do caminho executado pelo Phinx. O arquivo `db/db-backup.sqlite3` permanece como referência; o banco SQLite local e suas cópias de segurança ficam ignorados no Git. Novas instalações não precisam ler o SQLite para iniciar.

O banco `backend_test` tem usuário próprio, sem permissão no banco da aplicação. A conexão de testes rejeita o banco de desenvolvimento e exige um nome terminado em `_test`. A estrutura real é criada uma vez por execução; os dados e triggers são limpos entre os cenários. O serviço local desabilita o binlog para permitir os triggers de simulação de falhas com o usuário de testes.

O cadastro de traduções bloqueia a categoria durante a transação e verifica os idiomas existentes; uma repetição desfaz o lote inteiro. Curtidas usam `ON DUPLICATE KEY UPDATE` para manter uma ocorrência por administrador. As consultas usam prepared statements nativos e parâmetros distintos. Atualizações sem alteração de valores continuam retornando sucesso.

### 6. Investigação do iphone 8

No backup original, a última atualização do produto `4` foi feita por **corin** (`admin_user_id = 3`), em **22/12/2023 às 18:12:10**, no log `30`.

**Limitação:** o histórico registra apenas `update`, sem campo alterado ou valores anteriores. Portanto, é possível identificar a última atualização, mas não comprovar que ela mudou o preço.

```sql
SELECT
    pl.id, au.name, pl.action, pl.timestamp
FROM product_log pl
LEFT JOIN admin_user au ON au.id = pl.admin_user_id
WHERE
    pl.product_id = 4 AND
    pl.action = 'update'
ORDER BY pl.timestamp DESC, pl.id DESC
LIMIT 1;
```

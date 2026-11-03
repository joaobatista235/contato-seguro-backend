#!/bin/sh
set -eu

cd "$(dirname "$0")"
export XDEBUG_MODE=off

printf 'Validando composer.json e composer.lock...\n'
composer validate --no-check-publish

printf '\nVerificando a sintaxe do PHP...\n'
find src public tests db/migrations -type f -name '*.php' -print0 | xargs -0 -n 1 php -l

printf '\nVerificando o padrão do código...\n'
composer lint

printf '\nExecutando a análise estática...\n'
composer analyse

printf '\nExecutando os testes...\n'
composer test

printf '\nTodas as validações passaram.\n'

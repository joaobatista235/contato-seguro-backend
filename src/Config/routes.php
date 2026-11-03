<?php

declare(strict_types=1);

use Contatoseguro\TesteBackend\Controller\CategoryController;
use Contatoseguro\TesteBackend\Controller\CompanyController;
use Contatoseguro\TesteBackend\Controller\ProductController;
use Contatoseguro\TesteBackend\Controller\ReportController;
use Contatoseguro\TesteBackend\Middleware\AdminPermissionMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

/** @var App $app*/

$app->group('/companies', function (RouteCollectorProxy $group): void {
    $group->get('', [CompanyController::class, 'getAll']);
    $group->get('/{id}', [CompanyController::class, 'getOne']);
})->add(AdminPermissionMiddleware::class);

$app->group('/products', function (RouteCollectorProxy $group): void {
    $group->get('', [ProductController::class, 'getAll']);
    $group->get('/{id}', [ProductController::class, 'getOne']);
    $group->post('', [ProductController::class, 'insertOne']);
    $group->put('/{id}', [ProductController::class, 'updateOne']);
    $group->delete('/{id}', [ProductController::class, 'deleteOne']);
})->add(AdminPermissionMiddleware::class);

$app->group('/categories', function (RouteCollectorProxy $group): void {
    $group->get('', [CategoryController::class, 'getAll']);
    $group->get('/{id}', [CategoryController::class, 'getOne']);
    $group->post('', [CategoryController::class, 'insertOne']);
    $group->post('/{id}', [CategoryController::class, 'insertTranslations']);
    $group->put('/{id}', [CategoryController::class, 'updateOne']);
    $group->delete('/{id}', [CategoryController::class, 'deleteOne']);
})->add(AdminPermissionMiddleware::class);

$app->get('/report', [ReportController::class, 'generate'])->add(AdminPermissionMiddleware::class);

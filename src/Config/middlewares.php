<?php

declare(strict_types=1);

use Contatoseguro\TesteBackend\Middleware\JsonResponseMiddleware;
use Contatoseguro\TesteBackend\Error\JsonErrorRenderer;
use Slim\App;

/** @var App $app*/
$app->addBodyParsingMiddleware();
$app->add(JsonResponseMiddleware::class);

$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->registerErrorRenderer('application/json', JsonErrorRenderer::class);
$errorHandler->forceContentType('application/json');

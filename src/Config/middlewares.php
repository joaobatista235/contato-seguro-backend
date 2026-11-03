<?php

declare(strict_types=1);

use Contatoseguro\TesteBackend\Middleware\JsonResponseMiddleware;
use Contatoseguro\TesteBackend\Error\JsonErrorRenderer;
use Slim\App;
use Slim\Handlers\ErrorHandler;

/** @var App $app*/
$app->addBodyParsingMiddleware();
$app->add(JsonResponseMiddleware::class);

$errorMiddleware = $app->addErrorMiddleware(false, true, true);
/** @var ErrorHandler $errorHandler */
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->registerErrorRenderer('application/json', JsonErrorRenderer::class);
$errorHandler->forceContentType('application/json');

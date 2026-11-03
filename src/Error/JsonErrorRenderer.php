<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Error;

use Slim\Exception\HttpException;
use Slim\Interfaces\ErrorRendererInterface;
use Throwable;

class JsonErrorRenderer implements ErrorRendererInterface
{
    /**
     * @param Throwable $exception
     * @param bool $displayErrorDetails
     * @return string
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $message = $exception instanceof HttpException ? $exception->getMessage() : 'Internal server error';

        return json_encode(['error' => $message], JSON_THROW_ON_ERROR);
    }
}

<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Util;

use Psr\Http\Message\ResponseInterface;

final class JsonResponse
{
    /**
     * @param ResponseInterface $response
     * @param array<array-key, mixed> $data
     * @param int $status
     * @return ResponseInterface
     */
    public static function create(ResponseInterface $response, array $data, int $status): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}

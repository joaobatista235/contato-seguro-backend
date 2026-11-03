<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Util;

use InvalidArgumentException;

final class LanguageCode
{
    /**
     * @param mixed $value
     * @return string
     */
    public static function normalize(mixed $value): string
    {
        if (!is_string($value) || !preg_match('/\A[a-z]{2,3}(?:-[a-z0-9]{2,8})*\z/i', $value)) {
            throw new InvalidArgumentException('lang must be a language code such as en, pt or pt-BR');
        }

        return strtolower($value);
    }
}

<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Util;

final class IntegerValidator
{
    /**
     * @param mixed $value
     * @return int|null
     */
    public static function positive(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $validated = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $validated !== false ? $validated : null;
    }
}

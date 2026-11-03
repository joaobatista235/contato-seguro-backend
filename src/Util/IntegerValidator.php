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
        return self::validate($value, 1);
    }

    /**
     * @param mixed $value
     * @return int|null
     */
    public static function nonNegative(mixed $value): ?int
    {
        return self::validate($value, 0);
    }

    /**
     * @param mixed $value
     * @param int $minimum
     * @return int|null
     */
    private static function validate(mixed $value, int $minimum): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $validated = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimum]]);

        return $validated !== false ? $validated : null;
    }
}

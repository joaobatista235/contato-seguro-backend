<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Model;

class Category
{
    /**
     * @var int
     */
    public int $id;

    /**
     * @var int|null
     */
    public ?int $company_id;

    /**
     * @var string
     */
    public string $title;

    /**
     * @var int
     */
    public int $active;
}

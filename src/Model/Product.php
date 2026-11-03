<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Model;

class Product
{
    /**
     * @var string[]|string
     */
    public array|string $category = [];

    /**
     * @param int $id
     * @param int $companyId
     * @param string $title
     * @param float $price
     * @param bool $active
     * @param string $createdAt
     */
    public function __construct(
        public int $id = 0,
        public int $companyId = 0,
        public string $title = '',
        public float $price = 0.0,
        public bool $active = false,
        public string $createdAt = ''
    ) {
    }

    /**
     * @param object $fetch
     * @return self
     */
    public static function hydrateByFetch(object $fetch): self
    {
        return new self(
            (int)$fetch->id,
            (int)$fetch->company_id,
            (string)$fetch->title,
            (float)$fetch->price,
            (bool)$fetch->active,
            (string)$fetch->created_at
        );
    }

    /**
     * @param string[]|string $category
     * @return void
     */
    public function setCategory(array|string $category): void
    {
        $this->category = $category;
    }
}

<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddProductStock extends AbstractMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $query = "ALTER TABLE product ADD COLUMN stock INTEGER NOT NULL DEFAULT 0";
        $this->execute($query);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $query = "ALTER TABLE product DROP COLUMN stock";
        $this->execute($query);
    }
}

<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class PreventProductIdReuse extends AbstractMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $this->rebuildProductTable(true);

        $query = "DELETE FROM sqlite_sequence WHERE name = 'product'";
        $this->execute($query);

        $query = "
            INSERT INTO sqlite_sequence (name, seq)
            SELECT
                'product', COALESCE(MAX(used_id), 0)
            FROM (
                SELECT id AS used_id FROM product
                UNION ALL
                SELECT product_id AS used_id FROM product_log
            )
        ";
        $this->execute($query);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->rebuildProductTable(false);
    }

    /**
     * @param bool $autoIncrement
     * @return void
     */
    private function rebuildProductTable(bool $autoIncrement): void
    {
        $query = "
            SELECT
                sql
            FROM sqlite_master
            WHERE
                tbl_name = 'product' AND
                type IN ('index', 'trigger') AND
                sql IS NOT NULL
        ";
        $objects = $this->fetchAll($query);
        $identity = $autoIncrement ? 'AUTOINCREMENT' : '';

        $query = "
            CREATE TABLE product_rebuilt (
                id INTEGER PRIMARY KEY {$identity},
                company_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                price FLOAT,
                active TINYINT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (company_id) REFERENCES company(id)
            )
        ";
        $this->execute($query);

        $query = "
            INSERT INTO product_rebuilt (id, company_id, title, price, active, created_at)
            SELECT
                id, company_id, title, price, active, created_at
            FROM product
        ";
        $this->execute($query);

        $this->execute('DROP TABLE product');
        $this->execute('ALTER TABLE product_rebuilt RENAME TO product');

        foreach ($objects as $object) {
            $this->execute($object['sql']);
        }
    }
}

<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class ImportInitialData extends AbstractMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $data = $this->readData();
        $lastProductId = 0;

        foreach ($data as $table => $rows) {
            if (!empty($rows)) {
                $this->table($table)->insert($rows)->saveData();
            }
        }

        foreach ($data['product'] as $product) {
            $lastProductId = max($lastProductId, (int)$product['id']);
        }

        foreach ($data['product_log'] as $log) {
            $lastProductId = max($lastProductId, (int)$log['product_id']);
        }

        $nextProductId = $lastProductId + 1;
        $query = "ALTER TABLE product AUTO_INCREMENT = {$nextProductId}";
        $this->execute($query);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $data = array_reverse($this->readData(), true);

        foreach ($data as $table => $rows) {
            $ids = [];

            foreach ($rows as $row) {
                $ids[] = (int)$row['id'];
            }

            if (!empty($ids)) {
                $values = implode(', ', $ids);
                $query = "DELETE FROM {$table} WHERE id IN ({$values})";
                $this->execute($query);
            }
        }
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function readData(): array
    {
        $path = dirname(__DIR__) . '/data/initial_data.json';
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException('Could not read initial data');
        }

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}

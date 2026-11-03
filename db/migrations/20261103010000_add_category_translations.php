<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddCategoryTranslations extends AbstractMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $query = "
            CREATE TABLE category_translation (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                lang_code TEXT NOT NULL COLLATE NOCASE,
                label TEXT NOT NULL,
                UNIQUE (category_id, lang_code),
                FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
            )
        ";
        $this->execute($query);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $query = "DROP TABLE category_translation";
        $this->execute($query);
    }
}

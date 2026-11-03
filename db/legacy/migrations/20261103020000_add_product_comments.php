<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class AddProductComments extends AbstractMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $query = "
            CREATE TABLE product_comment (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                parent_id INTEGER,
                admin_user_id INTEGER NOT NULL,
                content TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at TEXT,
                FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
                FOREIGN KEY (parent_id) REFERENCES product_comment(id) ON DELETE CASCADE,
                FOREIGN KEY (admin_user_id) REFERENCES admin_user(id)
            )
        ";
        $this->execute($query);

        $query = "CREATE INDEX product_comment_product_date ON product_comment (product_id, created_at, id)";
        $this->execute($query);

        $query = "
            CREATE TABLE product_comment_like (
                comment_id INTEGER NOT NULL,
                admin_user_id INTEGER NOT NULL,
                PRIMARY KEY (comment_id, admin_user_id),
                FOREIGN KEY (comment_id) REFERENCES product_comment(id) ON DELETE CASCADE,
                FOREIGN KEY (admin_user_id) REFERENCES admin_user(id)
            )
        ";
        $this->execute($query);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $query = "DROP TABLE product_comment_like";
        $this->execute($query);

        $query = "DROP TABLE product_comment";
        $this->execute($query);
    }
}

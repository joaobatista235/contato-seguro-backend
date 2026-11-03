<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

class CreateMysqlSchema extends AbstractMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        $queries = [
            "CREATE TABLE company (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL UNIQUE,
                active TINYINT NOT NULL
            )",
            "CREATE TABLE admin_user (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT NULL,
                email VARCHAR(320) NOT NULL UNIQUE,
                name TEXT NOT NULL,
                FOREIGN KEY (company_id) REFERENCES company(id)
            )",
            "CREATE TABLE category (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT NULL,
                title TEXT NOT NULL,
                active TINYINT NOT NULL,
                FOREIGN KEY (company_id) REFERENCES company(id)
            )",
            "CREATE TABLE product (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                company_id INT NOT NULL,
                title TEXT NOT NULL,
                price DOUBLE NULL,
                active TINYINT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                stock INT NOT NULL DEFAULT 0,
                FOREIGN KEY (company_id) REFERENCES company(id)
            )",
            "CREATE TABLE product_category (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                cat_id INT NOT NULL,
                FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
                FOREIGN KEY (cat_id) REFERENCES category(id) ON DELETE CASCADE
            )",
            "CREATE TABLE product_log (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                admin_user_id INT NOT NULL,
                action TEXT NOT NULL,
                timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX product_log_product_date (product_id, timestamp, id)
            )",
            "CREATE TABLE category_translation (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                category_id INT NOT NULL,
                lang_code VARCHAR(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
                label TEXT NOT NULL,
                UNIQUE KEY category_language (category_id, lang_code),
                FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE
            )",
            "CREATE TABLE product_comment (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                parent_id INT NULL,
                admin_user_id INT NOT NULL,
                content TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at DATETIME NULL,
                INDEX product_comment_product_date (product_id, created_at, id),
                FOREIGN KEY (product_id) REFERENCES product(id) ON DELETE CASCADE,
                FOREIGN KEY (parent_id) REFERENCES product_comment(id) ON DELETE SET NULL,
                FOREIGN KEY (admin_user_id) REFERENCES admin_user(id)
            )",
            "CREATE TABLE product_comment_like (
                comment_id INT NOT NULL,
                admin_user_id INT NOT NULL,
                PRIMARY KEY (comment_id, admin_user_id),
                FOREIGN KEY (comment_id) REFERENCES product_comment(id) ON DELETE CASCADE,
                FOREIGN KEY (admin_user_id) REFERENCES admin_user(id)
            )",
        ];

        foreach ($queries as $query) {
            $this->execute($query . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs');
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $tables = [
            'product_comment_like', 'product_comment', 'category_translation', 'product_log',
            'product_category', 'product', 'category', 'admin_user', 'company',
        ];

        foreach ($tables as $table) {
            $query = "DROP TABLE {$table}";
            $this->execute($query);
        }
    }
}

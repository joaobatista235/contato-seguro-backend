<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Repository;

use Contatoseguro\TesteBackend\Config\DB;
use PDO;
use Throwable;

class CategoryTranslationRepository
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    /**
     * @param array<int, int|null> $categoryIds
     * @param string $lang
     * @return array<int, string>
     */
    public function findLabels(array $categoryIds, string $lang): array
    {
        $uniqueIds = [];

        foreach ($categoryIds as $categoryId) {
            if (!empty($categoryId)) {
                $uniqueIds[$categoryId] = $categoryId;
            }
        }

        if (empty($uniqueIds)) {
            return [];
        }

        $bind = [':lang' => [$lang, PDO::PARAM_STR]];
        $parameters = [];

        foreach ($uniqueIds as $index => $categoryId) {
            $parameter = ':category_' . $index;
            $parameters[] = $parameter;
            $bind[$parameter] = [$categoryId, PDO::PARAM_INT];
        }

        $ids = implode(', ', $parameters);
        $query = "
            SELECT
                c.id, COALESCE(ct.label, english.label, c.title) AS title
            FROM category c
            LEFT JOIN category_translation ct ON ct.category_id = c.id AND ct.lang_code = :lang
            LEFT JOIN category_translation english ON english.category_id = c.id AND english.lang_code = 'en'
            WHERE
                c.id IN ({$ids})
        ";

        $stm = $this->pdo->prepare($query);

        foreach ($bind as $parameter => [$value, $type]) {
            $stm->bindValue($parameter, $value, $type);
        }

        $stm->execute();

        return array_column($stm->fetchAll(PDO::FETCH_ASSOC), 'title', 'id');
    }

    /**
     * @param int $categoryId
     * @param array<int, array{lang_code: string, label: string}> $translations
     * @return bool
     */
    public function insertTranslations(int $categoryId, array $translations): bool
    {
        $this->pdo->beginTransaction();

        try {
            $query = "SELECT id FROM category WHERE id = :category_id FOR UPDATE";
            $stm = $this->pdo->prepare($query);
            $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $stm->execute();

            if ($stm->fetchColumn() === false) {
                $this->pdo->rollBack();
                return false;
            }

            $query = "
                INSERT INTO category_translation (category_id, lang_code, label)
                VALUES (:category_id, :lang_code, :label)
            ";
            $stm = $this->pdo->prepare($query);
            $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);

            foreach ($translations as $translation) {
                if ($this->existsForCategory($categoryId, $translation['lang_code'])) {
                    $this->pdo->rollBack();
                    return false;
                }

                $stm->bindValue(':lang_code', $translation['lang_code'], PDO::PARAM_STR);
                $stm->bindValue(':label', $translation['label'], PDO::PARAM_STR);
                $stm->execute();
            }

            $this->pdo->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    /**
     * @param int $categoryId
     * @param string $lang
     * @return bool
     */
    private function existsForCategory(int $categoryId, string $lang): bool
    {
        $query = "
            SELECT
                id
            FROM category_translation
            WHERE
                category_id = :category_id AND
                lang_code = :lang
        ";

        $stm = $this->pdo->prepare($query);
        $stm->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stm->bindValue(':lang', $lang, PDO::PARAM_STR);
        $stm->execute();

        return $stm->fetchColumn() !== false;
    }
}

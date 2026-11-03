<?php

declare(strict_types=1);

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Repository\CategoryRepository;
use Contatoseguro\TesteBackend\Repository\CategoryTranslationRepository;
use Contatoseguro\TesteBackend\Util\LanguageCode;
use InvalidArgumentException;

class CategoryTranslationService
{
    /**
     * @var CategoryTranslationRepository
     */
    private CategoryTranslationRepository $repository;

    /**
     * @var CategoryRepository
     */
    private CategoryRepository $categoryRepository;

    /**
     * @var AdminUserService
     */
    private AdminUserService $adminUserService;

    public function __construct()
    {
        $this->repository = new CategoryTranslationRepository();
        $this->categoryRepository = new CategoryRepository();
        $this->adminUserService = new AdminUserService();
    }

    /**
     * @param array<int, int|null> $categoryIds
     * @param string $lang
     * @return array<int, string>
     */
    public function getLabels(array $categoryIds, string $lang): array
    {
        return $this->repository->findLabels($categoryIds, $lang);
    }

    /**
     * @param int $categoryId
     * @param array<string, mixed> $body
     * @param int $adminUserId
     * @return bool
     */
    public function insertTranslations(int $categoryId, array $body, int $adminUserId): bool
    {
        $translations = $this->validateTranslations($body);
        $companyId = $this->adminUserService->getCompanyId($adminUserId);

        if ($companyId === null) {
            return false;
        }

        $categories = $this->categoryRepository->findOneForCompany($categoryId, $companyId);

        if (empty($categories)) {
            return false;
        }

        if (!$this->repository->insertTranslations($categoryId, $translations)) {
            throw new InvalidArgumentException('a translation already exists for this category and language');
        }

        return true;
    }

    /**
     * @param array<string, mixed> $body
     * @return array<int, array{lang_code: string, label: string}>
     */
    private function validateTranslations(array $body): array
    {
        if (empty($body['translations']) || !is_array($body['translations']) || !array_is_list($body['translations'])) {
            throw new InvalidArgumentException('translations must be a non-empty list');
        }

        $translations = [];

        foreach ($body['translations'] as $translation) {
            if (!is_array($translation) || !isset($translation['label']) || !is_string($translation['label']) || trim($translation['label']) === '') {
                throw new InvalidArgumentException('each translation must contain lang_code and a non-empty label');
            }

            $lang = LanguageCode::normalize($translation['lang_code'] ?? null);

            if (isset($translations[$lang])) {
                throw new InvalidArgumentException('translations must not repeat a language code');
            }

            $translations[$lang] = ['lang_code' => $lang, 'label' => trim($translation['label'])];
        }

        return array_values($translations);
    }
}

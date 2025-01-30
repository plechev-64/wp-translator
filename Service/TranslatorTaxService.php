<?php

namespace Src\Module\Translator\Service;

use Doctrine\ORM\EntityManagerInterface;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;

class TranslatorTaxService
{

    public function __construct(
        private readonly TranslatorEntityService $entityService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function initHooks(): void
    {
        add_action('create_term', [$this, 'createTaxEntityIntoRestRequest']);
        add_filter('wp_unique_term_slug', [$this, 'editCreatedTranslatedTermSlug'], 10);
    }

    /**
     * Принудительно изменяем слаг у создаваемого перевода тега,
     * тк слаг может формировать таким же как у источника и тег не создается
     */
    public function editCreatedTranslatedTermSlug(string $slug): string
    {

        if ( ! empty($_POST['action']) && $_POST['action'] === 'add-tag' && ! empty($_POST['trl_group_id'])) {
            $langCode = $_POST['trl_code'];
            $slug     .= '-' . $langCode;
        }

        return $slug;

    }

    public function createTaxEntityIntoRestRequest(int $termId): void
    {

        $isRestRequest = defined('REST_REQUEST') && REST_REQUEST;
        if ( ! $isRestRequest) {
            return;
        }

        $this->entityService->updateEntityByObject(
            new TranslatorEntityObject($termId, EntityType::Tax),
        );

        $this->entityManager->flush();
    }
}
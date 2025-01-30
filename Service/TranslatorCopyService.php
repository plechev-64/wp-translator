<?php

namespace Src\Module\Translator\Service;

use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\TranslatorFilterSql;

class TranslatorCopyService
{

    public function __construct(
        private readonly TranslatorStateService  $stateService,
        private readonly TranslatorEntityService $translatorEntityService,
        private readonly TranslatorFilterSql     $translatorFilterSql
    )
    {
    }

    public function copyTranslatedTerms(int $postId, int $sourceId): void
    {

        $source = get_post($sourceId);

        if (!$this->translatorEntityService->isSupportEntityType(EntityType::Post, $source->post_type)) {
            return;
        }

        $state = $this->stateService->getState();

        $postTaxonomies = get_object_taxonomies($source->post_type);

        remove_filter('terms_clauses', [$this->translatorFilterSql, 'editTermsClauses']);

        foreach ($postTaxonomies as $taxonomy) {

            if (!$this->translatorEntityService->isSupportEntityType(EntityType::Tax, $taxonomy)) {
                continue;
            }

            $postTerms = wp_get_object_terms($source->ID, $taxonomy);

            $terms = [];
            $numTerms = count($postTerms);
            for ($i = 0; $i < $numTerms; $i++) {

                $translatedTerm = $this->translatorEntityService->getTranslateEntityByCodeAndObject(
                    $state->getCurrentLanguageCode(),
                    new TranslatorEntityObject($postTerms[$i]->term_id, EntityType::Tax)
                );

                if (!$translatedTerm) {
                    continue;
                }

                $terms[] = $translatedTerm->getEntityId();
            }

            wp_set_object_terms($postId, $terms, $taxonomy);

        }

        add_filter('terms_clauses', [$this->translatorFilterSql, 'editTermsClauses'], 10, 2);

    }

    public function copyMediaFromOriginal(int $postId, int $sourceId): void
    {
        $thumbnailId = get_post_thumbnail_id($sourceId);
        if ($thumbnailId) {
            set_post_thumbnail($postId, $thumbnailId);
        }
    }

    public function copyTemplateFromOriginal(int $postId, int $sourceId): void {
        $template = get_post_meta($sourceId, '_wp_page_template', 1);
        if($template){
            update_post_meta($postId, '_wp_page_template',  $template);
        }
    }

}
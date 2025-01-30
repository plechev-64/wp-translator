<?php

namespace Src\Module\Translator;

use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\Service\TranslatorStateService;

class TranslatorFront {

    public function __construct(
        private readonly TranslatorStateService $translatorService,
        private readonly TranslatorEntityService $translatorEntityService
    ) {
    }

    public function init(): void {

        $this->translatorService->changeState();


        add_filter('body_class', function($classes){
            return $this->filterBodyClasses($classes);
        });

        add_filter( 'theme_mod_nav_menu_locations', function ( array $menuLocations ) {
            return $this->modifyMenuLocations( $menuLocations );
        }, 10 );

    }

    private function filterBodyClasses(array $classes): array {
        if($this->translatorService->getState()->getCurrentLanguage()->isRtl){
            $classes[] = 'rtl';
        }
        if($this->translatorService->getState()->isChanged()){
            $classes[] = 'language-changed';
        }
        return $classes;
    }

    private function modifyMenuLocations( array $menuLocations ): array {

        if ( empty( $menuLocations ) ) {
            return $menuLocations;
        }

        return $this->modifyMenuLocationsForCurrentLanguage( $menuLocations );

    }

    private function modifyMenuLocationsForCurrentLanguage( array $menuLocations ): array {
        $entities = $this->translatorEntityService->getPairsEntitiesWithCurrentAndDefaultLanguages( $menuLocations, EntityType::Tax );
        if ( ! $entities ) {
            return $menuLocations;
        }
        $entityLocations = [];
        foreach ( $menuLocations as $location => $menuId ) {
            /** @var TranslatorEntity $entity */
            foreach ( $entities as $entity ) {
                if ( $entity->getEntityId() === $menuId ) {
                    $entityLocations[ $location ] = $entity;
                }
            }
        }

        $currentLangCode = $this->translatorService->getState()->getCurrentLanguage()->code;

        foreach ( $entityLocations as $location => $menuEntity ) {
            /** @var TranslatorEntity $entity */
            foreach ( $entities as $entity ) {
                if ( $entity->getGroupId() === $menuEntity->getGroupId() && $entity->getCodeLang() === $currentLangCode ) {
                    $menuLocations[ $location ] = $entity->getEntityId();
                }
            }
        }

        return $menuLocations;
    }

}
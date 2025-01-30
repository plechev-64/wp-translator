<?php

namespace Src\Module\Translator\Service;

use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\LanguageCodeResolver;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\TranslatorState;

class TranslatorStateService
{

    public function __construct(
        private readonly TranslatorLanguageService $translatorLanguageService,
        private readonly TranslatorEntityService $translatorEntityService,
        private readonly TranslatorCookieService $cookie,
        private readonly TranslatorState $state,
        private readonly LanguageCodeResolver $languageCodeResolver
    ) {
    }

    /**
     * @return TranslatorState
     */
    public function getState(): TranslatorState
    {
        return $this->state;
    }

    public function setCurrentLanguageByCode(string $languageCode, ?bool $isAdmin = null): void
    {

        if ($isAdmin !== null) {
            $this->state->setIsAdmin($isAdmin);
        }

        /** @var TranslatorLanguageDTO $language */
        foreach ($this->translatorLanguageService->getUsingWithAllLanguagesModels() as $language) {
            if ($languageCode === $language->code) {

                if ($this->cookie->getCookieLanguageCode() !== $languageCode) {
                    $this->cookie->setCookieLanguageCode($languageCode);
                }

                $this->state->setCurrentLanguage($language);
                break;
            }
        }

    }

    public function changeState(): void
    {

        $changerCode = $this->languageCodeResolver->getChanger();

        if ($changerCode) {
            $languageCode = $changerCode->change();
            if ($languageCode && $languageCode !== $this->state->getCurrentLanguage()->code) {
                $this->state->setIsChanged(true);
                $this->setCurrentLanguageByCode($languageCode);
            }
        }

    }

    public function initState(): void
    {

        if (stripos($_SERVER['REQUEST_URI'], 'wp-admin') !== false) {
            $this->state->setIsAdmin(true);
        }

        $cookieCode = $this->cookie->getCookieLanguageCode();

        /** @var TranslatorLanguageDTO $language */
        foreach ($this->translatorLanguageService->getUsingWithAllLanguagesModels() as $language) {
            if ($cookieCode === $language->code) {
                $this->state->setCurrentLanguage($language);
                break;
            }
        }

        $this->state->setHomePages(
            $this->getHomePages()
        );
    }

    /**
     * @return TranslatorEntity[]|null
     */
    private function getHomePages(): ?array
    {

        $pageId = get_option('page_on_front');

        if ( ! $pageId) {
            return null;
        }

        /** @var TranslatorEntity[] $entities */
        $entities = $this->translatorEntityService->getTranslateEntitiesByPostObject(
            new TranslatorEntityObject($pageId, EntityType::Post), 'page'
        );

        return $entities;

    }

}
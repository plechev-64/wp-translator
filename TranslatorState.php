<?php

namespace Src\Module\Translator;

use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Service\TranslatorLanguageService;

class TranslatorState {

	private TranslatorLanguageDTO $currentLanguage;
	private string $currentLanguageCode;
	private bool $isAdmin = false;
	private bool $isChanged = false;
    private ?array $homePages = null;
    private ?array $homePageIds = null;

	public function __construct(
        TranslatorLanguageService $languageService
	) {
        $this->setCurrentLanguage(
            $languageService->getAllLanguageDTO()
        );
	}

	/**
	 * @return TranslatorLanguageDTO
	 */
	public function getCurrentLanguage(): TranslatorLanguageDTO {
		return $this->currentLanguage;
	}

	/**
	 * @param TranslatorLanguageDTO $currentLanguage
	 */
	public function setCurrentLanguage( TranslatorLanguageDTO $currentLanguage ): void {
		$this->currentLanguage     = $currentLanguage;
		$this->currentLanguageCode = $this->currentLanguage->code;
	}

	/**
	 * @return string|null
	 */
	public function getCurrentLanguageCode(): ?string {
		return $this->currentLanguageCode;
	}

	/**
	 * @return bool
	 */
	public function isAdmin(): bool {
		return $this->isAdmin;
	}

	/**
	 * @param bool $isAdmin
	 */
	public function setIsAdmin( bool $isAdmin ): void {
		$this->isAdmin = $isAdmin;
	}

	/**
	 * @return bool
	 */
	public function isChanged(): bool {
		return $this->isChanged;
	}

	/**
	 * @param bool $isChanged
	 */
	public function setIsChanged( bool $isChanged ): void {
		$this->isChanged = $isChanged;
	}

    /**
     * @return TranslatorEntity[]|null
     */
    public function getHomePages(): ?array
    {
        return $this->homePages;
    }

    /**
     * @param TranslatorEntity[]|null $homePages
     */
    public function setHomePages(?array $homePages): void
    {
        $this->homePages = $homePages;

        $this->homePageIds = array_map(function(TranslatorEntity $entity){
            return $entity->getEntityId();
        }, $this->homePages);
    }

    /**
     * @return array|null
     */
    public function getHomePageIds(): ?array
    {
        return $this->homePageIds;
    }

}
<?php

namespace Src\Module\Translator\Service;

use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Core\Exception\NotFoundEntityException;
use Src\Module\Translator\Config;
use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Module\Translator\Model\LanguageFlag;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Core\Transformer\Exception\TransformerException;
use Src\Core\Transformer\TransformerManager;
use Src\Module\Translator\Repository\TranslatorLangRefRepository;

class TranslatorLanguageService {

	public function __construct(
		private readonly TransformerManager $transformerManager,
		private readonly TranslatorLangRefRepository $langRefRepository
	) {
	}

	public function getUsingLanguages(): array {
		return $this->langRefRepository->getUsingLanguages();
	}

	/**
	 * @throws TransformerException
	 */
	public function getUsingLanguagesModels(): array {
        if (!$this->getUsingLanguages()) {
            return [];
        }

		return $this->transformerManager->transformArray(
			$this->getUsingLanguages(), TranslatorLanguageDTO::class
		);
	}

	/**
	 * @throws NotFoundEntityException
	 */
	public function getEntityLanguage( TranslatorEntityObject $entity ): TranslatorLanguage {

		/** @var TranslatorLanguage|null $language */
		$language = $this->langRefRepository->findEntityLanguage( $entity );

		if ( ! $language ) {
			throw new NotFoundEntityException( 'Не удалось получить язык сущности: ' . $entity->id );
		}

		return $language;

	}

    public function getUsingWithAllLanguagesModels(): array {
        $usingLanguages = $this->getUsingLanguagesModels();
        $usingLanguages[] = $this->getAllLanguageDTO();
        return $usingLanguages;
    }

    public function getAllLanguageDTO(): TranslatorLanguageDTO {
        $allLanguageDTO              = new TranslatorLanguageDTO();
        $allLanguageDTO->code        = Config::ALL_LANGUAGES_CODE;
        $allLanguageDTO->locale      = $allLanguageDTO->code;
        $allLanguageDTO->englishName = 'All languages';
        $allLanguageDTO->nativeName  = $allLanguageDTO->englishName;
        $allLanguageDTO->flag        = $this->getDefaultFlagByCode( Config::ALL_LANGUAGES_CODE );
        return $allLanguageDTO;
    }

	public function getDefaultFlagByCode( string $langCode ): LanguageFlag {

		$flag        = new LanguageFlag();
		$flag->path  = sprintf( '%s/images/flags/%s.png', Config::getAssetsDirUri(), $langCode );
		$flag->url   = sprintf( '%s/images/flags/%s.png', Config::getAssetsDirUri(), $langCode );
		$flag->image = '<img class="trl-flag"  
					width="20"
					height="12" 
					src="' . $flag->url . '" 
					alt="' . $langCode . '"
				/>';

		return $flag;
	}

}
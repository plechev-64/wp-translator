<?php

namespace Src\Module\Translator\LanguageCodeChanger;

use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Module\Translator\Service\TranslatorLanguageService;

class LanguageCodeAdminChanger implements LanguageCodeChangerInterface{

	public function __construct(
		private readonly TranslatorLanguageService $translatorLanguageService
	) {
	}

	public function isSupport(): bool {
		return is_admin() && !empty( $_GET['lang'] );
	}

	public function change(): ?string {

		$newCodeLang = $_GET['lang'];

		/** @var TranslatorLanguageDTO $usingLanguage */
		foreach ( $this->translatorLanguageService->getUsingWithAllLanguagesModels() as $usingLanguage ) {
			if ( $usingLanguage->code === $newCodeLang ) {
				return $newCodeLang;
			}
		}

		return null;

	}

}
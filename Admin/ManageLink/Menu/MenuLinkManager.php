<?php

namespace Src\Module\Translator\Admin\ManageLink\Menu;

use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Admin\ManageLink\AbstractLinkManager;
use Src\Module\Translator\Admin\ManageLink\LinkManagerIncomeObject;
use Src\Module\Translator\Service\TranslatorLanguageService;

class MenuLinkManager extends AbstractLinkManager {
	public function __construct(
		private readonly TranslatorLanguageService $translatorLanguageService
	) {
	}

	/**
	 * @param MenuLinkManagerIncomeObject $incomeObject
	 *
	 * @return array
	 */
	public function getLinks( LinkManagerIncomeObject $incomeObject ): array {
		$data = [];
		/** @var TranslatorLanguage $language */
		foreach ( $this->translatorLanguageService->getUsingLanguages() as $language ) {

			$link = new TranslateMenuManageLink( $incomeObject->term->term_id, $language->getCode() );

			$this->setUsingLanguageLinkByEntity( $link, $language, $incomeObject->translateEntities );

			$data[] = $link;
		}

		return $data;

	}

}
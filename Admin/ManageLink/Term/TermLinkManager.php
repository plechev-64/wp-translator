<?php

namespace Src\Module\Translator\Admin\ManageLink\Term;

use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Admin\ManageLink\AbstractLinkManager;
use Src\Module\Translator\Admin\ManageLink\LinkManagerIncomeObject;
use Src\Module\Translator\Service\TranslatorLanguageService;

class TermLinkManager extends AbstractLinkManager{
	public function __construct(
		private readonly TranslatorLanguageService $translatorLanguageService
	) {
	}

	/**
	 * @param TermLinkManagerIncomeObject $incomeObject
	 *
	 * @return array
	 */
	public function getLinks(LinkManagerIncomeObject $incomeObject): array {
		$term = $incomeObject->term;
		$data = [];
		/** @var TranslatorLanguage $language */
		foreach ( $this->translatorLanguageService->getUsingLanguages() as $language ) {

			$link = new TranslateTermManageLink($term->term_id, $term->taxonomy, $incomeObject->postType, $language->getCode());

			$this->setUsingLanguageLinkByEntity($link, $language, $incomeObject->translateEntities);

			$data[] = $link;
		}

		return $data;

	}

}
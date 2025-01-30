<?php

namespace Src\Module\Translator\Admin\ManageLink;

use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorLanguage;

abstract class AbstractLinkManager implements LinkManagerInterface {

	abstract function getLinks(LinkManagerIncomeObject $incomeObject): array;

	/**
	 * @param ManageLinkInterface $link
	 * @param TranslatorLanguage $language
	 * @param array $translateEntities
	 *
	 * @return void
	 */
	protected function setUsingLanguageLinkByEntity(
		ManageLinkInterface $link,
		TranslatorLanguage $language,
		array $translateEntities
	): void {

		if (!$translateEntities) {
			return;
		}

		/** @var TranslatorEntity $translateEntity */
		foreach ($translateEntities as $translateEntity) {
			if ($language->getCode() === $translateEntity->getCodeLang()) {
				$link->setCurrent($translateEntity->getEntityId());
				break;
			}
		}

	}

}
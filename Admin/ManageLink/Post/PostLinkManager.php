<?php

namespace Src\Module\Translator\Admin\ManageLink\Post;

use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Admin\ManageLink\AbstractLinkManager;
use Src\Module\Translator\Admin\ManageLink\LinkManagerIncomeObject;
use Src\Module\Translator\Service\TranslatorLanguageService;

class PostLinkManager extends AbstractLinkManager{
	public function __construct(
		private readonly TranslatorLanguageService $translatorLanguageService
	) {
	}

	/**
	 * @param PostLinkManagerIncomeObject $incomeObject
	 *
	 * @return array
	 */
	public function getLinks(LinkManagerIncomeObject $incomeObject): array {
		$post = $incomeObject->post;
		$translateEntities = $incomeObject->translateEntities;
		$data = [];
		/** @var TranslatorLanguage $language */
		foreach ( $this->translatorLanguageService->getUsingLanguages() as $language ) {

			$link = new TranslatePostManageLink($post->ID, $post->post_type, $language->getCode());

			$this->setUsingLanguageLinkByEntity($link, $language, $translateEntities);

			$data[] = $link;
		}

		return $data;

	}

}
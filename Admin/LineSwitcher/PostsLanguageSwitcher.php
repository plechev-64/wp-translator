<?php

namespace Src\Module\Translator\Admin\LineSwitcher;

use Src\Module\Translator\Model\PostsFilter;

class PostsLanguageSwitcher extends AbstractLineSwitcher
{

	public function getCounters(): array {

		$filter = new PostsFilter();
		$filter->postType = $this->postType;
		$filter->statuses = $this->postStatus;
		$filter->authorId = $_GET['author'] ?? null;

		return $this->translatorEntityService->getCountersTranslationsByPostFilter($filter);
	}

}
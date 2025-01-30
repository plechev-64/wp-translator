<?php

namespace Src\Module\Translator\Admin\LineSwitcher;

use Src\Module\Translator\Model\TermsFilter;

class TermLanguageSwitcher extends AbstractLineSwitcher
{

    public function getCounters(): array {

        $filter = new TermsFilter();
        $filter->taxonomy = $_GET['taxonomy']?: null;

        return $this->translatorEntityService->getCountersTranslationsByTermFilter($filter);

    }
}
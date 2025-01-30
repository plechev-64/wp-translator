<?php

namespace Src\Module\Translator;

use Src\Module\Translator\Admin\LanguageColumn\PostsLanguageColumn;
use Psr\Container\ContainerInterface;
use Src\Module\Translator\Admin\Metabox\TermLanguageMetabox;

class TranslatorAjaxLoader {

	public function __construct(
		private readonly ContainerInterface $container
	) {
	}

	public function init(): void {

		if($_POST['action'] === 'add-tag'){
			$this->container->get( TermLanguageMetabox::class )->init();
		}

        if($_POST['action'] === 'inline-save'){
            $this->container->get( PostsLanguageColumn::class )->init();
        }

	}

}
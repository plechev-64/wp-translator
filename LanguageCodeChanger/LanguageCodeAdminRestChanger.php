<?php

namespace Src\Module\Translator\LanguageCodeChanger;

use Src\Module\Translator\Service\TranslatorCookieService;
use Src\Module\Translator\TranslatorState;

class LanguageCodeAdminRestChanger implements LanguageCodeChangerInterface {

	public function __construct(
		private readonly TranslatorCookieService $cookie,
		private readonly TranslatorState $state,
	) {
	}

	public function isSupport(): bool {
		return ! empty( $GLOBALS['wp']->query_vars['rest_route'] ) && ( $_REQUEST['_locale'] ?? '' ) === 'user';
	}

	public function change(): string {
		$this->state->setIsAdmin( true );
		return $this->cookie->getCookieLanguageCode();
	}

}
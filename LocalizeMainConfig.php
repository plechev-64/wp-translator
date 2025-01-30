<?php

namespace Src\Module\Translator;

use JetBrains\PhpStorm\ArrayShape;

class LocalizeMainConfig {

	public function __construct(
		private readonly TranslatorState $state,
        private readonly string $restEndpoint
	) {
	}

	#[ArrayShape( [
		'defaultCode'  => "string",
		'currentCode'  => "string",
		'restEndpoint' => "string",
        '_wpnonce'     => "string"
	] )] public function get(): array {
		return [
			'defaultCode'  => Config::getDefaultLanguage(),
			'currentCode'  => $this->state->getCurrentLanguage()->code,
			'restEndpoint' => $this->restEndpoint,
			'_wpnonce'     => wp_create_nonce( 'wp_rest' )
		];
	}

}
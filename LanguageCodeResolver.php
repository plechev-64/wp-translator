<?php

namespace Src\Module\Translator;

use Psr\Container\ContainerInterface;
use Src\Module\Translator\LanguageCodeChanger\LanguageCodeAdminChanger;
use Src\Module\Translator\LanguageCodeChanger\LanguageCodeAdminRestChanger;
use Src\Module\Translator\LanguageCodeChanger\LanguageCodeChangerInterface;
use Src\Module\Translator\LanguageCodeChanger\LanguageCodeUrlStrategyChanger;

class LanguageCodeResolver {

	private const CHANGERS = [
		LanguageCodeAdminChanger::class,
		LanguageCodeUrlStrategyChanger::class,
		LanguageCodeAdminRestChanger::class
	];

	public function __construct(
		private readonly ContainerInterface $container
	) {
	}

	public function getChanger(): ?LanguageCodeChangerInterface {

		foreach(self::CHANGERS as $CHANGER){

			/** @var LanguageCodeChangerInterface $changer */
			$changer = $this->container->get($CHANGER);

			if($changer->isSupport()){
				return $changer;
			}

		}

		return null;

	}

}
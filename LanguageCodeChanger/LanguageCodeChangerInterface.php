<?php

namespace Src\Module\Translator\LanguageCodeChanger;

interface LanguageCodeChangerInterface {
	public function isSupport(): bool;
	public function change(): ?string;
}
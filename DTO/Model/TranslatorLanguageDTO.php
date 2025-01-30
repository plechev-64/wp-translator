<?php

namespace Src\Module\Translator\DTO\Model;

use Src\Module\Translator\Model\LanguageFlag;

class TranslatorLanguageDTO {
	public ?int $id = null;
	public string $code;
	public string $locale;
	public string $englishName;
	public ?string $nativeName = null;
	public bool $isCustomImage;
	public bool $isRtl = false;
	public ?int $customImageId = null;
	public LanguageFlag $flag;
}
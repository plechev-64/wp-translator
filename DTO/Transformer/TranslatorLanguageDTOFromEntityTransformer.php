<?php

namespace Src\Module\Translator\DTO\Transformer;

use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Core\Transformer\TransformerAbstract;

class TranslatorLanguageDTOFromEntityTransformer extends TransformerAbstract {

	/**
	 * @param TranslatorLanguage $data
	 * @param array $context
	 *
	 * @return TranslatorLanguageDTO
	 */
	public function transform( $data, array $context = [] ): TranslatorLanguageDTO {

		$model                = new TranslatorLanguageDTO();
		$model->id            = $data->getId();
		$model->code          = $data->getCode();
		$model->englishName   = $data->getEnglishName();
		$model->nativeName    = $data->getNativeName();
		$model->locale        = $data->getLocale();
		$model->isCustomImage = $data->isCustomImage();
		$model->customImageId = $data->getCustomImageId();
		$model->flag          = $data->getFlag();
		$model->isRtl         = $data->isRtl();

		return $model;
	}

	static function supportsTransformation( $data, string $to = null, array $context = [] ): bool {
		return $data instanceof TranslatorLanguage && TranslatorLanguageDTO::class === $to;
	}
}

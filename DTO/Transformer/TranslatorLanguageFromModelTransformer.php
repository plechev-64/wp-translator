<?php

namespace Src\Module\Translator\DTO\Transformer;

use Doctrine\ORM\EntityManagerInterface;
use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Core\Exception\NotFoundEntityException;
use Src\Module\Translator\DTO\Model\TranslatorLanguageDTO;
use Src\Core\Transformer\TransformerAbstract;
use Src\Module\Translator\Repository\TranslatorLangRefRepository;

class TranslatorLanguageFromModelTransformer extends TransformerAbstract {
	public function __construct(
		private readonly EntityManagerInterface $entityManager,
		private readonly TranslatorLangRefRepository $langRefRepository
	) {
	}

	/**
	 * @param TranslatorLanguageDTO $data
	 * @param array $context
	 *
	 * @return TranslatorLanguage
	 * @throws NotFoundEntityException
	 */
	public function transform( $data, array $context = [] ): TranslatorLanguage {

		if ( $data->id ) {
			/** @var TranslatorLanguage|null $group */
			$entity = $this->langRefRepository->find( $data->id );
			if ( ! $entity ) {
				throw new NotFoundEntityException( 'Не найден язык с идентификатором: ' . $data->id );
			}
		} else {
			$entity = new TranslatorLanguage();
			$this->entityManager->persist( $entity );
		}

		$entity->setCode( $data->code );
		$entity->setEnglishName( $data->englishName );
		$entity->setNativeName( $data->nativeName );
		$entity->setLocale( $data->locale );
		$entity->setIsCustomImage( $data->isCustomImage );
		$entity->setCustomImageId( $data->isCustomImage? $data->customImageId: null );
		$entity->setIsRtl( $data->isRtl );

		return $entity;
	}

	static function supportsTransformation( $data, string $to = null, array $context = [] ): bool {
		return $data instanceof TranslatorLanguageDTO && TranslatorLanguage::class === $to;
	}
}

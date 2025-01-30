<?php

namespace Src\Module\Translator\Service;

use Doctrine\ORM\EntityManagerInterface;
use Src\Core\Transformer\TransformerManager;
use Src\Module\Translator\DTO\Model\TranslatorEntityDTO;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Config;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\PostsFilter;
use Src\Module\Translator\Model\TermsFilter;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Repository\TranslatorEntityRepository;
use Src\Module\Translator\TranslatorState;

class TranslatorEntityService {

	public function __construct(
		private readonly TranslatorEntityRepository $entityRepository,
		private readonly TranslatorState $state,
        private readonly TransformerManager $transformerManager,
        private readonly EntityManagerInterface $entityManager
	) {
	}

	public function isSupportEntityType( EntityType $type, string $subType ): bool {

		if ( $type->value === EntityType::Post->value ) {
			return in_array( $subType, Config::getPostTypes() );
		}

		if ( $type->value === EntityType::Tax->value ) {
			return in_array( $subType, Config::getTaxonomies() );
		}

		return false;

	}

    public function deleteEntity( TranslatorEntityObject $entityObject ): void {
        $entity = $this->entityRepository->findOneBy( [
            'entityType' => $entityObject->type,
            'entityId'   => $entityObject->id
        ] );

        if($entity){
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }
    }

    public function getSourceEntityByObject(TranslatorEntityObject $entityObject): ?TranslatorEntity {
        return $this->entityRepository->findSourceEntityByObject( $entityObject );
    }

    public function getTranslateEntitiesByPostObject( TranslatorEntityObject $entity, string $postType ): array {
        return $this->entityRepository->findTranslateEntitiesByPostObject( $entity, $postType );
    }

	public function getFirstTranslationEntityByCodeAndType( string $languageCode, EntityType $entityType ): ?TranslatorEntity {
		return $this->entityRepository->findOneBy( [
			'entityType' => $entityType->value,
			'codeLang'   => $languageCode
		] );
	}

    public function getTranslationByLanguageCodeAndEntityObject( string $languageCode, TranslatorEntityObject $entityObject ): ?TranslatorEntity {
        return $this->entityRepository->findTranslationEntityByLanguageCodeAndEntityObject( $languageCode, $entityObject );
    }

	public function getTranslateEntityByCodeAndObject( string $languageCode, TranslatorEntityObject $entityObject ): ?TranslatorEntity {
		return $this->entityRepository->findTranslateEntityByCodeAndObject( $languageCode, $entityObject );
	}

	public function getTranslationPostBySlugAndLanguageCode( string $languageCode, string $postSlug ): ?TranslatorEntity {
		return $this->entityRepository->findTranslationPostBySlugAndLanguageCode( $languageCode, $postSlug );
	}

	public function getCountersTranslationsByPostFilter( PostsFilter $filter ): array {
		return $this->entityRepository->countTranslationByPostFilter( $filter );
	}

	public function getCountersTranslationsByTermFilter( TermsFilter $filter ): array {
		return $this->entityRepository->countTranslationByTermFilter( $filter );
	}

	public function getEntityByObject( TranslatorEntityObject $entity ): ?TranslatorEntity {
		return $this->entityRepository->findOneBy( [
			'entityId'   => $entity->id,
			'entityType' => $entity->type
		] );
	}

	/**
	 * @param TranslatorEntityObject $entity
	 *
	 * @return array<TranslatorEntity>
	 */
	public function getTranslateEntitiesByObject( TranslatorEntityObject $entity ): array {
		return $this->entityRepository->findTranslateEntitiesByObject( $entity );
	}

	/**
	 * @param array $menuLocations
	 * @param EntityType $entityType
	 *
	 * @return array<TranslatorEntity>
	 */
	public function getPairsEntitiesWithCurrentAndDefaultLanguages( array $menuLocations, EntityType $entityType ): array {
		$currentLangCode = $this->state->getCurrentLanguage()->code;

		return $this->entityRepository->findPairsEntitiesWithCurrentAndDefaultLanguages( $menuLocations, $entityType, $currentLangCode );
	}

    public function updateEntityByObject(TranslatorEntityObject $entityObject, ?int $initialGroupId = null): TranslatorEntity
    {

        $entity = $this->getEntityByObject($entityObject);

        $model             = new TranslatorEntityDTO();
        $model->entityId   = $entityObject->id;
        $model->entityType = $entityObject->type;
        $model->codeLang   = $this->state->getCurrentLanguage()->code;
        $model->groupId    = (int) $initialGroupId;

        if ( $entity ) {
            $model->id      = $entity->getId();
            $model->groupId = $entity->getGroup()->getId();
        }

        /** @var TranslatorEntity $entity */
        $entity = $this->transformerManager->transform($model, TranslatorEntity::class);

        return $entity;
    }

}
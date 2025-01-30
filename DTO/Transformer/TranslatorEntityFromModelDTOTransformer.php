<?php

namespace Src\Module\Translator\DTO\Transformer;

use Doctrine\ORM\EntityManagerInterface;
use Src\Core\Exception\NotFoundEntityException;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorGroup;
use Src\Module\Translator\DTO\Model\TranslatorEntityDTO;
use Src\Module\Translator\DTO\Model\TranslatorGroupDTO;
use Src\Core\Transformer\Exception\TransformerException;
use Src\Core\Transformer\TransformerAbstract;
use Src\Core\Transformer\TransformerManager;
use Src\Module\Translator\Repository\TranslatorEntityRepository;
use Src\Module\Translator\Repository\TranslatorGroupRepository;

class TranslatorEntityFromModelDTOTransformer extends TransformerAbstract {
	public function __construct(
		private readonly TransformerManager $transformerManager,
		private readonly TranslatorEntityRepository $translatorEntityRepository,
		private readonly TranslatorGroupRepository $translatorGroupRepository,
		private readonly EntityManagerInterface $entityManager
	) {
	}

	/**
	 * @param TranslatorEntityDTO $data
	 * @param array $context
	 *
	 * @return TranslatorEntity
	 * @throws NotFoundEntityException
	 * @throws TransformerException
	 */
	public function transform( $data, array $context = [] ): TranslatorEntity {

		if ( $data->id ) {
			/** @var TranslatorEntity $entity */
			$entity = $this->translatorEntityRepository->find( $data->id );
			if ( ! $entity ) {
				throw new NotFoundEntityException( 'Не найдена сущность перевода с идентификатором: ' . $data->id );
			}
		} else {
			$entity = new TranslatorEntity();
			$this->entityManager->persist( $entity );

			if ( ! $data->groupId ) {
				$groupModel             = new TranslatorGroupDTO();
				$groupModel->sourceType = $data->entityType;
				$groupModel->sourceId   = $data->entityId;

				/** @var TranslatorGroup $group */
				$group = $this->transformerManager->transform( $groupModel, TranslatorGroup::class );
				$entity->setGroup( $group );
			}
		}

		if ( $data->groupId ) {
			/** @var TranslatorGroup $group */
			$group = $this->translatorGroupRepository->find( $data->groupId );
			if ( ! $group ) {
				throw new NotFoundEntityException( 'Не найдена группа перевода с идентификатором: ' . $data->groupId );
			}
			$entity->setGroup( $group );
		}

		$entity->setEntityId( $data->entityId );
		$entity->setEntityType( $data->entityType );
		$entity->setCodeLang( $data->codeLang );

		return $entity;
	}

	static function supportsTransformation( $data, string $to = null, array $context = [] ): bool {
		return $data instanceof TranslatorEntityDTO && TranslatorEntity::class === $to;
	}
}

<?php

namespace Src\Module\Translator\Compatibility;

use Doctrine\ORM\EntityManagerInterface;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorGroup;
use Src\Module\Translator\DTO\Model\TranslatorEntityDTO;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Module\Translator\TranslatorState;
use Src\Core\Transformer\Exception\TransformerException;
use Src\Core\Transformer\TransformerManager;
use WP_Post;

class DuplicatePostPlugin {

	public function __construct(
		private readonly TranslatorState $state,
		private  readonly TranslatorEntityService $translatorEntityService,
		private readonly TransformerManager $transformerManager,
		private readonly EntityManagerInterface $entityManager
	) {
	}

	public function init(): void {
		add_action( 'dp_duplicate_page', [ $this, 'copyTranslations' ], 10, 3 );
		add_action( 'dp_duplicate_post', [ $this, 'copyTranslations' ], 10, 3 );
	}

	private function removeFilters(): void {
		remove_action( 'dp_duplicate_page', [ $this, 'copyTranslations' ] );
		remove_action( 'dp_duplicate_post', [ $this, 'copyTranslations' ] );
	}

	/**
	 * @param int $copiedPostId
	 * @param WP_Post $originalPost
	 * @param string|null $status
	 *
	 * @return void
	 * @throws TransformerException
	 */
	public function copyTranslations( int $copiedPostId, WP_Post $originalPost, ?string $status = '' ): void {
		global $duplicated_posts;

		if ( !$this->translatorEntityService->isSupportEntityType( EntityType::Post, $originalPost->post_type ) ) {
			return;
		}

		$this->removeFilters();

		$translateEntities = $this->translatorEntityService->getTranslateEntitiesByObject(
			new TranslatorEntityObject( $originalPost->ID, EntityType::Post )
		);

		if ( ! $translateEntities ) {
			return;
		}

		$group = $this->createTranslationGroup( $copiedPostId, $translateEntities, $originalPost );

		if ( ! $group ) {
			return;
		}

		/** @var TranslatorEntity $translateEntity */
		foreach ( $translateEntities as $translateEntity ) {
			if ( $translateEntity->getEntityId() === $originalPost->ID ) {
				continue;
			}

			$translation = get_post( $translateEntity->getEntityId() );
			if ( ! $translation ) {
				continue;
			}

			$newPostId = duplicate_post_create_duplicate( $translation, $status );

			if ( ! is_wp_error( $newPostId ) ) {
				$this->createTranslationEntity( $newPostId, $translateEntity->getCodeLang(), $group->getId() );
			}

		}

		$this->entityManager->flush();

		$duplicated_posts[ $originalPost->ID ] = $copiedPostId;

	}

	/**
	 * @param int $postId
	 * @param string $codeLang
	 * @param int|null $groupId
	 *
	 * @return TranslatorEntity
	 * @throws TransformerException
	 */
	private function createTranslationEntity( int $postId, string $codeLang, ?int $groupId = null ): TranslatorEntity {

		$entityDto             = new TranslatorEntityDTO();
		$entityDto->entityId   = $postId;
		$entityDto->entityType = EntityType::Post;
		$entityDto->codeLang   = $codeLang;
		if ( $groupId ) {
			$entityDto->groupId = $groupId;
		}

		return $this->transformerManager->transform( $entityDto, TranslatorEntity::class );

	}

	/**
	 * @param int $copiedPostId
	 * @param TranslatorEntity[] $translateEntities
	 * @param WP_Post $originalPost
	 *
	 * @return TranslatorGroup|null
	 * @throws TransformerException
	 */
	private function createTranslationGroup( int $copiedPostId, array $translateEntities, WP_Post $originalPost ): ?TranslatorGroup {
		foreach ( $translateEntities as $translateEntity ) {
			if ( $translateEntity->getEntityId() === $originalPost->ID ) {
				$entity = $this->createTranslationEntity( $copiedPostId, $this->state->getCurrentLanguageCode() );
				$this->entityManager->flush();

				return $entity->getGroup();
			}
		}

		return null;
	}

}
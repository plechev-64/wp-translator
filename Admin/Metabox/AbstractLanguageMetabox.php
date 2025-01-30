<?php

namespace Src\Module\Translator\Admin\Metabox;

use Doctrine\ORM\EntityManagerInterface;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\TranslatorEntityObject;
use Src\Module\Translator\Service\TranslatorEntityService;
use Src\Core\Transformer\Exception\TransformerException;
use Src\Core\Transformer\TransformerManager;

abstract class AbstractLanguageMetabox extends AbstractMetabox {

	public function __construct(
		private readonly TranslatorEntityService $translatorEntityService,
		private readonly TransformerManager $transformerManager,
		private readonly EntityManagerInterface $entityManager
	) {
	}

    abstract function getEntityType(): EntityType;

	/**
	 * @throws TransformerException
	 */
	public function update( int $itemId ): void {

		$this->translatorEntityService->updateEntityByObject(
			new TranslatorEntityObject( $itemId, $this->getEntityType() ),
            (int) $_POST['trl_group_id']
		);

		$this->entityManager->flush();

	}
}

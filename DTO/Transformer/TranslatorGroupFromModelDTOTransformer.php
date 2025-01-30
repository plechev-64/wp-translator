<?php

namespace Src\Module\Translator\DTO\Transformer;

use Doctrine\ORM\EntityManagerInterface;
use Src\Core\Exception\NotFoundEntityException;
use Src\Module\Translator\Entity\TranslatorGroup;
use Src\Module\Translator\DTO\Model\TranslatorGroupDTO;
use Src\Core\Transformer\TransformerAbstract;
use Src\Module\Translator\Repository\TranslatorGroupRepository;

class TranslatorGroupFromModelDTOTransformer extends TransformerAbstract
{
    public function __construct(
        private readonly TranslatorGroupRepository $translatorGroupRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * @param TranslatorGroupDTO $data
     * @param array $context
     *
     * @return TranslatorGroup
     * @throws NotFoundEntityException
     */
    public function transform($data, array $context = []): TranslatorGroup
    {

        if($data->id){
            /** @var TranslatorGroup $group */
            $entity = $this->translatorGroupRepository->find($data->id);
            if(!$entity){
                throw new NotFoundEntityException('Не найдена группа перевода с идентификатором: ' . $data->id);
            }
        }else {
            $entity = new TranslatorGroup();
            $this->entityManager->persist($entity);
        }

        $entity->setSourceId($data->sourceId);
        $entity->setSourceType($data->sourceType);

        return $entity;
    }

    static function supportsTransformation($data, string $to = null, array $context = []): bool
    {
        return $data instanceof TranslatorGroupDTO && TranslatorGroup::class === $to;
    }
}

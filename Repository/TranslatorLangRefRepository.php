<?php

namespace Src\Module\Translator\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Src\Core\Repository\EntityRepositoryAbstract;
use Src\Module\Translator\Config;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorLanguage;
use Src\Module\Translator\Model\TranslatorEntityObject;

/**
 * @method TranslatorLanguage|null findOneBy(array $conditions )
 * @method TranslatorLanguage|null find(int $id )
 * @method TranslatorLanguage[] findBy(array $conditions )
 */
class TranslatorLangRefRepository extends EntityRepositoryAbstract
{
    public function getEntityClassName(): string
    {
        return TranslatorLanguage::class;
    }

    public function findCurrentLanguageByCode(string $code): ?TranslatorLanguage {

        return $this->createQueryBuilder('l')
            ->addSelect('(CASE WHEN l.code = :defaultCode THEN 1 ELSE 2 END) AS HIDDEN ORD')
            ->where('l.code=:code OR l.code=:defaultCode')
            ->andWhere('l.code IN (:usingCodes)')
            ->setParameter('usingCodes', Config::getUsingLanguages())
            ->setParameter('code', $code)
            ->setParameter('defaultCode', Config::getDefaultLanguage())
            ->orderBy('ORD', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

    }

    public function findEntityLanguage(TranslatorEntityObject $entity): ?TranslatorLanguage {

        return $this->createQueryBuilder('l')
            ->join(TranslatorEntity::class, 'te', Join::WITH, 'l.code=te.codeLang')
            ->addSelect('(CASE WHEN l.code = :defaultCode THEN 1 ELSE 2 END) AS HIDDEN ORD')
            ->where('l.code=:defaultCode OR (te.entityId=:entityId AND te.entityType=:entityType)')
            ->setParameter('entityId', $entity->id)
            ->setParameter('entityType', $entity->type->value)
            ->setParameter('defaultCode', Config::getDefaultLanguage())
            ->orderBy('ORD', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

    }

    public function getUsingLanguages(): array {
        return $this->createQueryBuilder('l')
            ->where('l.code IN (:usingCodes)')
            ->setParameter('usingCodes', Config::getUsingLanguages())
	        ->orderBy('l.id', 'ASC')
            ->getQuery()
            ->getResult();

    }
}

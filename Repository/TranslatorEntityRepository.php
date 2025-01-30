<?php

namespace Src\Module\Translator\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Src\Core\Entity\Post;
use Src\Core\Entity\Term;
use Src\Core\Entity\TermTaxonomy;
use Src\Core\Repository\EntityRepositoryAbstract;
use Src\Module\Translator\Config;
use Src\Module\Translator\Entity\TranslatorEntity;
use Src\Module\Translator\Entity\TranslatorGroup;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Model\PostsFilter;
use Src\Module\Translator\Model\TermsFilter;
use Src\Module\Translator\Model\TranslatorEntityObject;

/**
 * @method TranslatorEntity|null findOneBy(array $conditions)
 * @method TranslatorEntity|null find(int $id)
 * @method TranslatorEntity[] findBy(array $conditions)
 */
class TranslatorEntityRepository extends EntityRepositoryAbstract
{
    public function getEntityClassName(): string
    {
        return TranslatorEntity::class;
    }

    public function findSourceEntityByObject(TranslatorEntityObject $entityObject): ?TranslatorEntity
    {
        return $this->createQueryBuilder('te')
                    ->join(TranslatorGroup::class, 'g', Join::WITH, 'te.entityId=g.sourceId')
                    ->join(TranslatorEntity::class, 't', Join::WITH, 'g.id=t.group')
                    ->andWhere('t.entityType=:entityType')
                    ->andWhere('te.entityType=:entityType')
                    ->andWhere('t.entityId=:entityId')
                    ->andWhere('g.sourceType=:entityType')
                    ->setParameter('entityId', $entityObject->id)
                    ->setParameter('entityType', $entityObject->type->value)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    public function findTranslateEntitiesByPostObject(TranslatorEntityObject $entity, string $postType): array
    {

        $subQuery = $this->getEntityManager()->createQueryBuilder();
        $subQuery = $subQuery
            ->select('g.id')
            ->from(TranslatorGroup::class, 'g')
            ->join(TranslatorEntity::class, 'e', Join::WITH, 'g.id=e.group')
            ->andWhere('e.entityId=:entityId')
            ->andWhere('e.entityType=:entityType');

        $query = $this->createQueryBuilder('te');
        $query->select('te')
              ->join(Post::class, 'p', Join::WITH, 'te.entityId=p.id')
              ->where($query->expr()->in('te.group', $subQuery->getDQL()))
              ->andWhere('p.postType=:postType')
              ->setParameter('postType', $postType)
              ->setParameter('entityId', $entity->id)
              ->setParameter('entityType', $entity->type->value);

        return $query
            ->getQuery()
            ->getResult();

    }

    public function countTranslationByPostFilter(PostsFilter $filter): array
    {

        $selectQueryArray = [
            "COUNT(CASE WHEN p.postType = '$filter->postType' THEN 1 ELSE :null END) AS " . Config::ALL_LANGUAGES_CODE
        ];
        foreach (Config::getUsingLanguages() as $code) {
            $escCode = preg_replace('/\W/', '', $code);
            $selectQueryArray[] = "COUNT(CASE WHEN te.codeLang = '$code' THEN 1 ELSE :null END) AS $escCode";
        }

        $query = $this->createQueryBuilder('te')
                      ->select(implode(', ', $selectQueryArray))
                      ->leftJoin(Post::class, 'p', Join::WITH, 'te.entityId=p.id')
                      ->andWhere('te.entityType=:entityType')
                      ->andWhere('p.postType=:postType')
                      ->setParameter('postType', $filter->postType)
                      ->setParameter('null', null)
                      ->setParameter('entityType', EntityType::Post->value);

        if(!$filter->statuses){
            $filter->statuses = ['publish', 'draft', 'pending'];
        }

        $query
            ->andWhere('p.postStatus IN (:postStatuses)')
            ->setParameter('postStatuses', $filter->statuses);

        if ($filter->authorId) {
            $query
                ->andWhere('p.postAuthor=:authorId')
                ->setParameter('authorId', $filter->authorId);
        }

        $result = $query->getQuery()
                        ->getResult();

        return $result[0];

    }

    public function countTranslationByTermFilter(TermsFilter $filter): array
    {

        $selectQueryArray = [
            "COUNT(CASE WHEN tx.taxonomy = '$filter->taxonomy' THEN 1 ELSE :null END) AS " . Config::ALL_LANGUAGES_CODE
        ];
        foreach (Config::getUsingLanguages() as $code) {
            $selectQueryArray[] = "COUNT(CASE WHEN te.codeLang = '$code' THEN 1 ELSE :null END) AS $code";
        }

        $query = $this->createQueryBuilder('te')
                      ->select(implode(', ', $selectQueryArray))
                      ->leftJoin(Term::class, 't', Join::WITH, 'te.entityId=t.id')
                      ->leftJoin(TermTaxonomy::class, 'tx', Join::WITH, 't.id=tx.termId')
                      ->andWhere('te.entityType=:entityType')
                      ->andWhere('tx.taxonomy=:taxonomy')
                      ->setParameter('taxonomy', $filter->taxonomy)
                      ->setParameter('null', null)
                      ->setParameter('entityType', EntityType::Tax->value);

        $result = $query->getQuery()
                        ->getResult();

        return $result[0];

    }

    public function findTranslateEntityByCodeAndObject(
        string $languageCode,
        TranslatorEntityObject $entity
    ): ?TranslatorEntity {

        $subQuery = $this->getEntityManager()->createQueryBuilder();
        $subQuery = $subQuery
            ->select('g.id')
            ->from(TranslatorGroup::class, 'g')
            ->join(TranslatorEntity::class, 'e', Join::WITH, 'g.id=e.group')
            ->andWhere('e.entityId=:entityId')
            ->andWhere('e.entityType=:entityType');

        $query = $this->createQueryBuilder('te');
        $query->select('te')
              ->where($query->expr()->in('te.group', $subQuery->getDQL()))
              ->andWhere('te.codeLang=:codeLang')
              ->setParameter('codeLang', $languageCode)
              ->setParameter('entityId', $entity->id)
              ->setParameter('entityType', $entity->type->value);

        return $query
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

    }

    /**
     * @param TranslatorEntityObject $entity
     *
     * @return array<TranslatorEntity>
     */
    public function findTranslateEntitiesByObject(TranslatorEntityObject $entity): array
    {

        $subQuery = $this->getEntityManager()->createQueryBuilder();
        $subQuery = $subQuery
            ->select('g.id')
            ->from(TranslatorGroup::class, 'g')
            ->join(TranslatorEntity::class, 'e', Join::WITH, 'g.id=e.group')
            ->andWhere('e.entityId=:entityId')
            ->andWhere('e.entityType=:entityType');

        $query = $this->createQueryBuilder('te');
        $query->select('te')
              ->where($query->expr()->in('te.group', $subQuery->getDQL()))
              ->setParameter('entityId', $entity->id)
              ->setParameter('entityType', $entity->type->value);

        return $query
            ->getQuery()
            ->getResult();

    }

    public function findPairsEntitiesWithCurrentAndDefaultLanguages(
        array $entityIds,
        EntityType $entityType,
        string $currentLangCode
    ): array {

        $subQuery = $this->getEntityManager()->createQueryBuilder();
        $subQuery = $subQuery
            ->select('g.id')
            ->from(TranslatorGroup::class, 'g')
            ->join(TranslatorEntity::class, 'e', Join::WITH, 'g.id=e.group')
            ->andWhere('e.entityId IN (:entityIds)')
            ->andWhere('e.entityType=:entityType');

        $query = $this->createQueryBuilder('te');
        $query->select('te')
              ->where($query->expr()->in('te.group', $subQuery->getDQL()))
              ->andWhere('te.codeLang IN (:codes)')
              ->setParameter('entityIds', $entityIds)
              ->setParameter('entityType', $entityType->value)
              ->setParameter('codes', [$currentLangCode, Config::getDefaultLanguage()]);

        return $query
            ->getQuery()
            ->getResult();

    }

    public function findTranslationEntityByLanguageCodeAndEntityObject(
        string $languageCode,
        TranslatorEntityObject $entity
    ): ?TranslatorEntity {

        $query = $this->createQueryBuilder('te');
        $query->select('te')
              ->andWhere('te.entityId=:entityId')
              ->andWhere('te.entityType=:entityType')
              ->andWhere('te.codeLang=:code')
              ->setParameter('entityId', $entity->id)
              ->setParameter('entityType', $entity->type)
              ->setParameter('code', $languageCode);

        return $query
            ->getQuery()
            ->getOneOrNullResult();

    }

    public function findTranslationPostBySlugAndLanguageCode(string $languageCode, string $postSlug): ?TranslatorEntity
    {

        $query = $this->createQueryBuilder('te');
        $query->select('te')
              ->join(Post::class, 'p', Join::WITH, 'te.entityId=p.id')
              ->andWhere('p.postName=:slug')
              ->andWhere('te.entityType=:entityType')
              ->andWhere('te.codeLang=:code')
              ->setParameter('slug', $postSlug)
              ->setParameter('entityType', EntityType::Post->value)
              ->setParameter('code', $languageCode);

        return $query
            ->getQuery()
            ->getOneOrNullResult();

    }
}
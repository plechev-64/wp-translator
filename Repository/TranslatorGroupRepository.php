<?php

namespace Src\Module\Translator\Repository;

use Src\Core\Repository\EntityRepositoryAbstract;
use Src\Module\Translator\Entity\TranslatorGroup;

/**
 * @method TranslatorGroup|null findOneBy( array $conditions )
 * @method TranslatorGroup|null find( int $id )
 * @method TranslatorGroup[] findBy( array $conditions )
 */
class TranslatorGroupRepository extends EntityRepositoryAbstract
{
    public function getEntityClassName(): string
    {
        return TranslatorGroup::class;
    }
}

<?php

namespace Src\Module\Translator\DTO\Model;

use Src\Module\Translator\Enum\EntityType;

class TranslatorGroupDTO
{
    public ?int $id = null;
    public ?int $sourceId = null;
    public EntityType $sourceType;
}
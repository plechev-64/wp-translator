<?php

namespace Src\Module\Translator\DTO\Model;

use Src\Module\Translator\Enum\EntityType;

class TranslatorEntityDTO
{
    public ?int $id = null;
    public ?int $groupId = null;
    public int $entityId;
    public EntityType $entityType;
    public string $codeLang;
}
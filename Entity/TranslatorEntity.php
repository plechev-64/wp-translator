<?php

namespace Src\Module\Translator\Entity;

use Doctrine\ORM\Mapping as ORM;
use Src\Module\Translator\Enum\EntityType;
use Src\Module\Translator\Repository\TranslatorEntityRepository;

#[ORM\Entity(repositoryClass: TranslatorEntityRepository::class)]
#[ORM\Table(name: 'wp_translator_entities')]
#[ORM\Index(columns: ['entity_id'], name: 'entity_idx')]
#[ORM\Index(columns: ['entity_id', 'entity_type', 'code_lang'], name: 'entity_lang_idx')]
#[ORM\Index(columns: ['entity_id', 'group_id'], name: 'entity_idx')]
class TranslatorEntity
{

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TranslatorGroup::class, inversedBy: 'entities')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id')]
    private ?TranslatorGroup $group = null;

	#[ORM\Column(name: "group_id", type: "integer")]
	private int $groupId;

    #[ORM\Column(name: "entity_id", type: "integer")]
    private int $entityId;

    #[ORM\Column(name: "entity_type", enumType: EntityType::class)]
    private EntityType $entityType;

    #[ORM\Column(name: "code_lang", type: "string")]
    private string $codeLang;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

	/**
	 * @return TranslatorGroup|null
	 */
    public function getGroup(): ?TranslatorGroup
    {
        return $this->group;
    }

    /**
     * @param TranslatorGroup $group
     */
    public function setGroup(TranslatorGroup $group): void
    {
        $this->group = $group;
    }

	/**
	 * @return int
	 */
	public function getGroupId(): int {
		return $this->groupId;
	}

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     */
    public function setEntityId(int $entityId): void
    {
        $this->entityId = $entityId;
    }

    /**
     * @return EntityType
     */
    public function getEntityType(): EntityType
    {
        return $this->entityType;
    }

    /**
     * @param EntityType $entityType
     */
    public function setEntityType(EntityType $entityType): void
    {
        $this->entityType = $entityType;
    }

    /**
     * @return string
     */
    public function getCodeLang(): string
    {
        return $this->codeLang;
    }

    /**
     * @param string $codeLang
     */
    public function setCodeLang(string $codeLang): void
    {
        $this->codeLang = $codeLang;
    }

}
